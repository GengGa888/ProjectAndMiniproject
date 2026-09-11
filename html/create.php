<?php
session_start();
require_once 'db_connect.php';


/* =====================================================
   ตรวจสอบการเข้าสู่ระบบ
===================================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$user_id = intval($_SESSION['user_id']);

$role = $_SESSION['role'] ?? '';


// อนุญาตเฉพาะ student / teacher / admin
$allowed_roles = ['student', 'teacher', 'admin'];

if (!in_array($role, $allowed_roles, true)) {
    header("Location: index2.php");
    exit;
}


/* =====================================================
   ดึงข้อมูลผู้ใช้
===================================================== */

$user_sql = "
    SELECT
        id,
        username,
        first_name,
        last_name,
        email,
        role,
        department
    FROM users
    WHERE id = ?
    LIMIT 1
";


$user_stmt = mysqli_prepare($conn, $user_sql);

if (!$user_stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
}


mysqli_stmt_bind_param(
    $user_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($user_stmt);


$user_result = mysqli_stmt_get_result($user_stmt);


$user_data = mysqli_fetch_assoc($user_result);


if (!$user_data) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


$first_name = $user_data['first_name'] ?? '';
$last_name  = $user_data['last_name'] ?? '';
$username   = $user_data['username'] ?? '';
$email      = $user_data['email'] ?? '';
$role       = $user_data['role'] ?? '';
$department = $user_data['department'] ?? '';


$submitter_fullname = trim(
    $first_name . ' ' . $last_name
);


if ($submitter_fullname === '') {
    $submitter_fullname = $username;
}


/* =====================================================
   เมื่อกดส่งโปรเจกต์
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* =================================================
       ตรวจสอบ Login อีกครั้ง
       ป้องกันการ POST ตรง
    ================================================= */

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }


    $user_id = intval($_SESSION['user_id']);


    /* =================================================
       รับข้อมูล
    ================================================= */

    $title = trim(
        $_POST['title'] ?? ''
    );


    $description = trim(
        $_POST['description'] ?? ''
    );


    $degree = trim(
        $_POST['degree'] ?? ''
    );


    $project_department = trim(
        $_POST['department'] ?? ''
    );


    if ($project_department === '') {
        $project_department = $department;
    }


    $authors = trim(
        $_POST['authors'] ?? ''
    );


    $advisor = trim(
        $_POST['advisor'] ?? ''
    );


    $github_url = trim(
        $_POST['github_url'] ?? ''
    );


    /* =================================================
       ตรวจสอบชื่อโปรเจกต์
    ================================================= */

    if ($title === '') {
        die("กรุณากรอกชื่อโปรเจกต์");
    }


    /* =================================================
       ตรวจสอบ GitHub URL
    ================================================= */

    if ($github_url !== '') {

        if (!filter_var($github_url, FILTER_VALIDATE_URL)) {
            die("รูปแบบ GitHub URL ไม่ถูกต้อง");
        }
    }


    /* =================================================
       ถ้าไม่ได้กรอกผู้จัดทำ
    ================================================= */

    if ($authors === '') {

        $authors = $submitter_fullname;
    }


    /* =================================================
       ค่า Legacy
    ================================================= */

    $project_name = $title;

    $project_type = $degree;

    $student_name = $authors;

    $status = "ส่งแล้ว";


    /* =================================================
       PDF
    ================================================= */

    $pdf_filename = null;


    if (
        isset($_FILES['pdf_file']) &&
        $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        /* =============================================
           ตรวจสอบ Upload Error
        ============================================= */

        if (
            $_FILES['pdf_file']['error']
            !== UPLOAD_ERR_OK
        ) {

            die(
                "เกิดข้อผิดพลาดในการอัปโหลด PDF"
            );
        }


        $file = $_FILES['pdf_file'];


        /* =============================================
           จำกัดขนาด 10 MB
        ============================================= */

        $max_size = 10 * 1024 * 1024;


        if ($file['size'] > $max_size) {

            die(
                "ไฟล์ PDF ต้องมีขนาดไม่เกิน 10 MB"
            );
        }


        /* =============================================
           ตรวจสอบนามสกุล
        ============================================= */

        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );


        if ($extension !== 'pdf') {

            die(
                "กรุณาอัปโหลดเฉพาะไฟล์ PDF"
            );
        }


        /* =============================================
           ตรวจสอบ MIME Type
        ============================================= */

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime_type = finfo_file(
            $finfo,
            $file['tmp_name']
        );

        finfo_close($finfo);


        if ($mime_type !== 'application/pdf') {

            die(
                "ไฟล์ที่อัปโหลดไม่ใช่ไฟล์ PDF ที่ถูกต้อง"
            );
        }


        /* =============================================
           สร้างชื่อไฟล์ใหม่
        ============================================= */

        $pdf_filename =
            "project_" .
            $user_id .
            "_" .
            time() .
            "_" .
            bin2hex(
                random_bytes(4)
            ) .
            ".pdf";


        /* =============================================
           Upload Directory
        ============================================= */

        $upload_dir = __DIR__ . "/uploads/";


        if (!is_dir($upload_dir)) {

            if (!mkdir($upload_dir, 0777, true)) {

                die(
                    "ไม่สามารถสร้างโฟลเดอร์ uploads ได้"
                );
            }
        }


        /* =============================================
           Destination
        ============================================= */

        $destination =
            $upload_dir .
            $pdf_filename;


        /* =============================================
           Move File
        ============================================= */

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {

            die(
                "ไม่สามารถบันทึกไฟล์ PDF ได้"
            );
        }
    }


    /* =================================================
       INSERT PROJECT
    ================================================= */

    $sql = "
        INSERT INTO projects
        (
            project_name,
            project_type,
            student_name,
            title,
            description,
            degree,
            department,
            authors,
            advisor,
            pdf_file,
            github_url,
            status,
            student_id
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    /* =================================================
       ถ้า Prepare ไม่สำเร็จ
    ================================================= */

    if (!$stmt) {


        if (
            $pdf_filename &&
            file_exists(
                __DIR__ .
                "/uploads/" .
                $pdf_filename
            )
        ) {

            unlink(
                __DIR__ .
                "/uploads/" .
                $pdf_filename
            );
        }


        die(
            "SQL Error: " .
            mysqli_error($conn)
        );
    }


    /* =================================================
       Bind Parameters
    ================================================= */

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssssssi",
        $project_name,
        $project_type,
        $student_name,
        $title,
        $description,
        $degree,
        $project_department,
        $authors,
        $advisor,
        $pdf_filename,
        $github_url,
        $status,
        $user_id
    );


    /* =================================================
       Execute
    ================================================= */

    if (
        mysqli_stmt_execute($stmt)
    ) {


        mysqli_stmt_close($stmt);

        mysqli_stmt_close($user_stmt);


        header(
            "Location: profile.php?success=project"
        );

        exit;


    } else {


        /* =============================================
           ถ้า INSERT ไม่สำเร็จ
           ลบ PDF
        ============================================= */

        if (
            $pdf_filename &&
            file_exists(
                __DIR__ .
                "/uploads/" .
                $pdf_filename
            )
        ) {

            unlink(
                __DIR__ .
                "/uploads/" .
                $pdf_filename
            );
        }


        die(
            "ไม่สามารถบันทึกโปรเจกต์ได้: " .
            mysqli_stmt_error($stmt)
        );
    }
}

?>


<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        ส่งโปรเจกต์ - คลังโปรเจกต์ SDU
    </title>


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                'Segoe UI',
                Tahoma,
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    180deg,
                    #f5f9fc 0%,
                    #ffffff 45%
                );

            color: #333;
        }


        /* =================================================
           HEADER
        ================================================= */

        .custom-header {

            height: 90px;

            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #4297cd,
                    #3287bb
                );

            color: white;

            box-shadow:
                0 4px 18px
                rgba(38, 119, 164, 0.20);
        }


        .header-inner {

            max-width: 1280px;

            height: 100%;

            margin: auto;

            padding: 0 25px;

            display: flex;

            align-items: center;
        }


        .header-left-area {

            display: flex;

            align-items: center;

            gap: 20px;
        }


        .logo-link {

            display: flex;

            align-items: center;

            text-decoration: none;
        }


        .sdu-logo {

            width: 58px;

            height: 58px;

            object-fit: contain;

            background: white;

            border-radius: 50%;

            padding: 3px;

            box-shadow:
                0 3px 10px
                rgba(0,0,0,0.15);
        }


        .header-title {

            font-size: 24px;

            font-weight: 700;
        }


        .home-link {

            display: flex;

            align-items: center;

            gap: 7px;

            color: white;

            text-decoration: none;

            font-size: 16px;

            font-weight: 600;

            transition: 0.25s;
        }


        .home-link:hover {

            color: white;

            transform: translateY(-1px);
        }


        /* =================================================
           CONTAINER
        ================================================= */

        .container {

            max-width: 900px;

            margin: 45px auto;

            padding: 0 20px;
        }


        /* =================================================
           CARD
        ================================================= */

        .card {

            background: white;

            border-radius: 18px;

            padding: 32px;

            border:
                1px solid #e3edf3;

            box-shadow:
                0 8px 30px
                rgba(48, 105, 139, 0.10);
        }


        h1 {

            margin-top: 0;

            margin-bottom: 25px;

            color: #3287bb;

            font-size: 30px;
        }


        /* =================================================
           USER INFO
        ================================================= */

        .user-info {

            background:
                linear-gradient(
                    135deg,
                    #eef8fd,
                    #f7fbfe
                );

            border:
                1px solid #d8edf7;

            border-radius: 12px;

            padding: 17px;

            margin-bottom: 27px;

            color: #3d5d6d;

            line-height: 1.8;
        }


        .user-info strong {

            color: #245c7d;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: 700;

            color: #344b58;
        }


        input,
        textarea,
        select {

            width: 100%;

            padding: 12px 14px;

            border:
                1px solid #ccd9e0;

            border-radius: 9px;

            font-size: 15px;

            font-family: inherit;

            background: white;

            transition: 0.2s;
        }


        input:focus,
        textarea:focus,
        select:focus {

            outline: none;

            border-color: #3287bb;

            box-shadow:
                0 0 0 3px
                rgba(50, 135, 187, 0.12);
        }


        textarea {

            min-height: 140px;

            resize: vertical;

            line-height: 1.7;
        }


        input[type="file"] {

            padding: 10px;
        }


        /* =================================================
           REQUIRED
        ================================================= */

        .required {

            color: #dc3545;
        }


        /* =================================================
           HELP TEXT
        ================================================= */

        small {

            display: block;

            margin-top: 7px;

            color: #748692;

            font-size: 13px;
        }


        /* =================================================
           BUTTON
        ================================================= */

        .form-actions {

            display: flex;

            gap: 10px;

            margin-top: 28px;

            flex-wrap: wrap;
        }


        .btn {

            border: none;

            padding: 13px 22px;

            border-radius: 9px;

            cursor: pointer;

            font-size: 16px;

            font-weight: 600;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            transition: 0.25s;
        }


        .btn-primary {

            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #3287bb
                );

            color: white;

            box-shadow:
                0 5px 15px
                rgba(50, 135, 187, 0.20);
        }


        .btn-primary:hover {

            color: white;

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px
                rgba(50, 135, 187, 0.28);
        }


        .btn-secondary {

            background: #eef3f6;

            color: #526570;
        }


        .btn-secondary:hover {

            background: #e1e9ee;

            color: #344b58;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .custom-header {

                height: 75px;
            }


            .header-left-area {

                gap: 12px;
            }


            .header-title {

                font-size: 18px;
            }


            .sdu-logo {

                width: 48px;

                height: 48px;
            }


            .home-link {

                font-size: 14px;
            }


            .container {

                margin: 25px auto;

                padding: 0 12px;
            }


            .card {

                padding: 20px;
            }


            h1 {

                font-size: 25px;
            }

        }

    </style>

</head>


<body>


<!-- =================================================
     HEADER
================================================== -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">


            <!-- LOGO -->

            <a
                href="index2.php"
                class="logo-link"
            >

                <img
                    src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                    class="sdu-logo"
                    alt="SDU Logo"
                >

            </a>


            <!-- TITLE -->

            <div class="header-title">

                คลังโปรเจกต์ SDU

            </div>


            <!-- HOME -->

            <a
                href="index2.php"
                class="home-link"
            >

                <i class="bi bi-house-fill"></i>

                หน้าแรก

            </a>


        </div>

    </div>

</header>



<!-- =================================================
     MAIN
================================================== -->

<div class="container">

    <div class="card">


        <h1>

            <i class="bi bi-file-earmark-plus"></i>

            ส่งโปรเจกต์นักศึกษา

        </h1>



        <!-- =================================================
             USER INFO
        ================================================== -->

        <div class="user-info">

            <strong>
                ผู้ส่ง:
            </strong>

            <?= htmlspecialchars(
                $submitter_fullname
            ) ?>

            <br>


            <strong>
                Username:
            </strong>

            <?= htmlspecialchars(
                $username
            ) ?>

            <br>


            <strong>
                สาขา:
            </strong>

            <?= htmlspecialchars(
                $department
            ) ?>

        </div>



        <!-- =================================================
             FORM
        ================================================== -->

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- ชื่อโปรเจกต์ -->

            <div class="form-group">

                <label>

                    ชื่อโปรเจกต์

                    <span class="required">
                        *
                    </span>

                </label>


                <input
                    type="text"
                    name="title"
                    placeholder="กรอกชื่อโปรเจกต์"
                    maxlength="255"
                    required
                >

            </div>



            <!-- รายละเอียด -->

            <div class="form-group">

                <label>
                    รายละเอียดโปรเจกต์
                </label>


                <textarea
                    name="description"
                    placeholder="กรอกรายละเอียดโปรเจกต์"
                ></textarea>

            </div>



            <!-- ระดับการศึกษา -->

            <div class="form-group">

                <label>
                    ระดับการศึกษา
                </label>


                <select name="degree">

                    <option value="">
                        -- เลือกระดับการศึกษา --
                    </option>


                    <option value="ปริญญาตรี">
                        ปริญญาตรี
                    </option>


                    <option value="ปริญญาโท">
                        ปริญญาโท
                    </option>


                    <option value="ปริญญาเอก">
                        ปริญญาเอก
                    </option>

                </select>

            </div>



            <!-- สาขา -->

            <div class="form-group">

                <label>
                    สาขา
                </label>


                <input
                    type="text"
                    name="department"
                    value="<?= htmlspecialchars(
                        $department
                    ) ?>"
                    maxlength="255"
                >

            </div>



            <!-- ผู้จัดทำ -->

            <div class="form-group">

                <label>
                    รายชื่อผู้จัดทำ
                </label>


                <input
                    type="text"
                    name="authors"
                    value="<?= htmlspecialchars(
                        $submitter_fullname
                    ) ?>"
                    placeholder="ชื่อผู้จัดทำ"
                    maxlength="500"
                >

            </div>



            <!-- อาจารย์ -->

            <div class="form-group">

                <label>
                    อาจารย์ที่ปรึกษา
                </label>


                <input
                    type="text"
                    name="advisor"
                    placeholder="ชื่ออาจารย์ที่ปรึกษา"
                    maxlength="100"
                >

            </div>



            <!-- GitHub -->

            <div class="form-group">

                <label>
                    GitHub URL
                </label>


                <input
                    type="url"
                    name="github_url"
                    placeholder="https://github.com/username/project"
                    maxlength="500"
                >

            </div>



            <!-- PDF -->

            <div class="form-group">

                <label>
                    ไฟล์ PDF
                </label>


                <input
                    type="file"
                    name="pdf_file"
                    accept=".pdf,application/pdf"
                >


                <small>

                    <i class="bi bi-info-circle"></i>

                    รองรับเฉพาะไฟล์ PDF
                    ขนาดไม่เกิน 10 MB

                </small>

            </div>



            <!-- BUTTONS -->

            <div class="form-actions">


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-cloud-arrow-up"></i>

                    ส่งโปรเจกต์

                </button>


                <a
                    href="index2.php"
                    class="btn btn-secondary"
                >

                    <i class="bi bi-arrow-left"></i>

                    กลับหน้าแรก

                </a>


            </div>


        </form>


    </div>

</div>


</body>

</html>