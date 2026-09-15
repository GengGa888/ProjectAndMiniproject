<?php

session_start();
require_once "db_connect.php";

/* =========================================================
   ตรวจสอบ Login
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$first_name = trim($_SESSION['first_name'] ?? '');
$last_name  = trim($_SESSION['last_name'] ?? '');

$submitter_fullname = trim($first_name . ' ' . $last_name);


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   ดึงข้อมูลผู้ใช้
========================================================= */

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
    die("เกิดข้อผิดพลาดในการเตรียมข้อมูลผู้ใช้");
}

mysqli_stmt_bind_param(
    $user_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($user_stmt);

$user_result = mysqli_stmt_get_result($user_stmt);

if (!$user_result || mysqli_num_rows($user_result) === 0) {
    mysqli_stmt_close($user_stmt);

    session_destroy();

    header("Location: login.php");
    exit();
}

$current_user = mysqli_fetch_assoc($user_result);

mysqli_stmt_close($user_stmt);


/* =========================================================
   ดึงรายชื่ออาจารย์
========================================================= */

$teachers = [];

$teacher_sql = "
    SELECT
        id,
        first_name,
        last_name,
        department
    FROM users
    WHERE role = 'teacher'
    ORDER BY first_name ASC, last_name ASC
";

$teacher_result = mysqli_query($conn, $teacher_sql);

if ($teacher_result) {

    while ($teacher = mysqli_fetch_assoc($teacher_result)) {

        $teacher_fullname = trim(
            ($teacher['first_name'] ?? '') .
            ' ' .
            ($teacher['last_name'] ?? '')
        );

        if ($teacher_fullname !== '') {

            $teachers[] = [
                'id' => (int)$teacher['id'],
                'name' => $teacher_fullname,
                'department' => trim($teacher['department'] ?? '')
            ];
        }
    }
}


/* =========================================================
   ค่าเริ่มต้น
========================================================= */

$title       = '';
$description = '';
$degree      = '';
$department  = '';
$authors     = '';
$advisor     = '';
$github_url  = '';

$error = '';
$success = '';


/* =========================================================
   SUBMIT
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');

    $description = trim(
        $_POST['description'] ?? ''
    );

    $degree = trim(
        $_POST['degree'] ?? ''
    );

    $department = trim(
        $_POST['department'] ?? ''
    );

    $authors = trim(
        $_POST['authors'] ?? ''
    );

    $advisor = trim(
        $_POST['advisor'] ?? ''
    );

    $github_url = trim(
        $_POST['github_url'] ?? ''
    );


    /* =====================================================
       ตรวจสอบข้อมูล
    ===================================================== */

    if ($title === '') {

        $error = 'กรุณากรอกชื่อโปรเจกต์';

    } elseif ($degree === '') {

        $error = 'กรุณาเลือกระดับการศึกษา';

    } elseif ($department === '') {

        $error = 'กรุณากรอกสาขา / ภาควิชา';

    } elseif ($authors === '') {

        $error = 'กรุณากรอกชื่อสมาชิกกลุ่ม';

    } elseif ($advisor === '') {

        $error = 'กรุณาเลือกอาจารย์ที่ปรึกษา';

    }


    /* =====================================================
       ตรวจสอบอาจารย์ที่เลือก
    ===================================================== */

    if ($error === '') {

        $advisor_found = false;

        foreach ($teachers as $teacher) {

            if ($teacher['name'] === $advisor) {

                $advisor_found = true;
                break;
            }
        }

        if (!$advisor_found) {
            $error = 'อาจารย์ที่ปรึกษาที่เลือกไม่ถูกต้อง';
        }
    }


    /* =====================================================
       ตรวจสอบ GitHub
    ===================================================== */

    if (
        $error === '' &&
        $github_url !== ''
    ) {

        if (!filter_var($github_url, FILTER_VALIDATE_URL)) {

            $error = 'ลิงก์ GitHub ไม่ถูกต้อง';

        } else {

            $github_host = strtolower(
                parse_url($github_url, PHP_URL_HOST) ?? ''
            );

            if (
                $github_host !== 'github.com' &&
                $github_host !== 'www.github.com'
            ) {

                $error = 'กรุณาใส่ลิงก์จาก GitHub เท่านั้น';
            }
        }
    }


    /* =====================================================
       PDF
    ===================================================== */

    $pdf_file = '';

    if (
        $error === '' &&
        isset($_FILES['pdf_file']) &&
        $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {

            $error = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์ PDF';

        } else {

            $file_name = $_FILES['pdf_file']['name'];
            $file_tmp  = $_FILES['pdf_file']['tmp_name'];
            $file_size = $_FILES['pdf_file']['size'];

            $extension = strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );


            /* จำกัด 10 MB */

            if ($file_size > 10 * 1024 * 1024) {

                $error = 'ไฟล์ PDF ต้องมีขนาดไม่เกิน 10 MB';

            } elseif ($extension !== 'pdf') {

                $error = 'กรุณาอัปโหลดเฉพาะไฟล์ PDF เท่านั้น';

            } else {

                $upload_dir = __DIR__ . "/uploads/";

                if (!is_dir($upload_dir)) {

                    mkdir(
                        $upload_dir,
                        0777,
                        true
                    );
                }


                /* สร้างชื่อไฟล์ใหม่ */

                $new_file_name =
                    'project_' .
                    $user_id .
                    '_' .
                    time() .
                    '_' .
                    bin2hex(random_bytes(4)) .
                    '.pdf';


                $destination =
                    $upload_dir .
                    $new_file_name;


                if (
                    move_uploaded_file(
                        $file_tmp,
                        $destination
                    )
                ) {

                    $pdf_file = $new_file_name;

                } else {

                    $error = 'ไม่สามารถบันทึกไฟล์ PDF ได้';
                }
            }
        }
    }


    /* =====================================================
       บันทึกข้อมูล
    ===================================================== */

    if ($error === '') {

        /*
            project_name
            ใช้ชื่อเดียวกับ title เพื่อรองรับข้อมูลเดิม
        */

        $project_name = $title;


        /*
            project_type
            ใช้ degree เพื่อรองรับโครงสร้างเดิม
        */

        $project_type = $degree;


        /*
            student_name
            ใช้ชื่อสมาชิกกลุ่ม
        */

        $student_name = $authors;


        $status = 'ส่งแล้ว';


        $insert_sql = "
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
            $insert_sql
        );


        if (!$stmt) {

            /*
                ถ้า INSERT ไม่ผ่าน
                และมี PDF ที่อัปโหลดไปแล้ว
                ให้ลบ PDF ทิ้ง
            */

            if ($pdf_file !== '') {

                $uploaded_file =
                    __DIR__ .
                    "/uploads/" .
                    basename($pdf_file);

                if (file_exists($uploaded_file)) {
                    unlink($uploaded_file);
                }
            }

            $error =
                'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssssssi",
                $project_name,
                $project_type,
                $student_name,
                $title,
                $description,
                $degree,
                $department,
                $authors,
                $advisor,
                $pdf_file,
                $github_url,
                $status,
                $user_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $new_project_id =
                    mysqli_insert_id($conn);

                mysqli_stmt_close($stmt);


                echo "
                <script>
                    alert('สร้างโปรเจกต์เรียบร้อยแล้ว');
                    window.location.href =
                        'project-detail.php?id={$new_project_id}';
                </script>
                ";

                exit();

            } else {

                $error =
                    'ไม่สามารถบันทึกโปรเจกต์ได้: ' .
                    mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);


                /*
                    ถ้า INSERT ไม่สำเร็จ
                    ลบ PDF ที่อัปโหลดไปแล้ว
                */

                if ($pdf_file !== '') {

                    $uploaded_file =
                        __DIR__ .
                        "/uploads/" .
                        basename($pdf_file);

                    if (file_exists($uploaded_file)) {
                        unlink($uploaded_file);
                    }
                }
            }
        }
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
        สร้างโปรเจกต์ - คลังโปรเจกต์ SDU
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

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

            background:
                linear-gradient(
                    180deg,
                    #f5f9fc 0%,
                    #ffffff 45%
                );

            font-family:
                'Segoe UI',
                Tahoma,
                Arial,
                sans-serif;

            color: #263238;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .custom-header {

            height: 90px;

            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #4297CD,
                    #3287BB
                );

            box-shadow:
                0 4px 18px
                rgba(38, 119, 164, 0.20);

        }


        .header-inner {

            max-width: 1280px;

            height: 90px;

            margin: auto;

            padding: 0 25px;

            display: flex;

            align-items: center;

            justify-content: space-between;

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


        .home-link {

            display: flex;

            align-items: center;

            gap: 9px;

            color: white;

            text-decoration: none;

            font-size: 18px;

            font-weight: 600;

        }


        .home-link:hover {

            color: white;

        }


        .home-icon {

            font-size: 22px;

        }


        .header-right {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .profile-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 45px;

            height: 45px;

            color: white;

            font-size: 30px;

            text-decoration: none;

            border-radius: 50%;

        }


        .profile-icon:hover {

            color: white;

            background:
                rgba(255,255,255,0.15);

        }


        /* =====================================================
           MAIN
        ===================================================== */

        .page-container {

            max-width: 1000px;

            margin: 45px auto;

            padding: 0 20px;

        }


        .form-card {

            background: white;

            border: 1px solid #e2edf3;

            border-radius: 18px;

            box-shadow:
                0 8px 30px
                rgba(48,105,139,0.10);

            overflow: hidden;

        }


        .form-header {

            padding: 28px 32px;

            background:
                linear-gradient(
                    135deg,
                    #f4fbff,
                    #ffffff
                );

            border-bottom:
                1px solid #e5eef3;

        }


        .form-header h1 {

            margin: 0;

            color: #174f70;

            font-size: 28px;

            font-weight: 750;

        }


        .form-header p {

            margin: 8px 0 0;

            color: #7a8d98;

            font-size: 14px;

        }


        .form-body {

            padding: 32px;

        }


        /* =====================================================
           ALERT
        ===================================================== */

        .error-box {

            padding: 14px 16px;

            margin-bottom: 22px;

            background: #fff1f1;

            border:
                1px solid #f2c5c5;

            border-radius: 10px;

            color: #b02a37;

            font-size: 14px;

        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 22px;

        }


        .form-label {

            display: block;

            margin-bottom: 8px;

            color: #245c7d;

            font-size: 14px;

            font-weight: 700;

        }


        .required {

            color: #dc3545;

        }


        .form-control,
        .form-select {

            min-height: 48px;

            border:
                1px solid #cddfe9;

            border-radius: 9px;

            padding: 10px 13px;

            font-size: 14px;

            color: #263238;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #4297CD;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,0.12);

        }


        textarea.form-control {

            min-height: 140px;

            resize: vertical;

        }


        .form-help {

            margin-top: 6px;

            color: #8999a2;

            font-size: 12px;

        }


        /* =====================================================
           ADVISOR SELECT
        ===================================================== */

        .advisor-box {

            padding: 16px;

            background: #f5faff;

            border:
                1px solid #dceef8;

            border-radius: 11px;

        }


        .advisor-icon {

            color: #4297CD;

            margin-right: 5px;

        }


        .no-teacher {

            padding: 12px;

            background: #fff8e8;

            border:
                1px solid #f3dfaa;

            border-radius: 8px;

            color: #80651e;

            font-size: 13px;

        }


        /* =====================================================
           MEMBERS
        ===================================================== */

        .member-box {

            padding: 16px;

            background: #fbfdfe;

            border:
                1px solid #e3edf3;

            border-radius: 11px;

        }


        .member-title {

            color: #245c7d;

            font-weight: 700;

            font-size: 14px;

            margin-bottom: 8px;

        }


        /* =====================================================
           FILE
        ===================================================== */

        .file-box {

            padding: 18px;

            background: #f8fbfd;

            border:
                1px dashed #b9d6e5;

            border-radius: 11px;

        }


        .file-box input {

            background: white;

        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .form-actions {

            display: flex;

            gap: 10px;

            justify-content: flex-end;

            margin-top: 30px;

            padding-top: 22px;

            border-top:
                1px solid #e7eef2;

        }


        .submit-button {

            border: none;

            background:
                linear-gradient(
                    135deg,
                    #58b4df,
                    #358abd
                );

            color: white;

            padding: 12px 24px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 700;

        }


        .submit-button:hover {

            background:
                linear-gradient(
                    135deg,
                    #429fd0,
                    #287cab
                );

            color: white;

        }


        .cancel-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 12px 20px;

            border-radius: 9px;

            border:
                1px solid #ccdce5;

            background: white;

            color: #527184;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

        }


        .cancel-button:hover {

            background: #f5f9fc;

            color: #245c7d;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .custom-header {

                height: 75px;

            }


            .header-inner {

                height: 75px;

                padding: 0 15px;

            }


            .header-left-area {

                gap: 12px;

            }


            .sdu-logo {

                width: 48px;

                height: 48px;

            }


            .home-link {

                font-size: 15px;

            }


            .profile-icon {

                font-size: 27px;

            }


            .page-container {

                margin: 25px auto;

                padding: 0 12px;

            }


            .form-header {

                padding: 22px 18px;

            }


            .form-header h1 {

                font-size: 24px;

            }


            .form-body {

                padding: 18px;

            }


            .form-actions {

                flex-direction: column-reverse;

            }


            .submit-button,
            .cancel-button {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">

            <a
                href="index2.php"
                class="logo-link"
            >

                <img
                    src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                    alt="SDU Logo"
                    class="sdu-logo"
                >

            </a>


            <a
                href="index2.php"
                class="home-link"
            >

                <i class="bi bi-house-fill home-icon"></i>

                <span>
                    หน้าแรก
                </span>

            </a>

        </div>


        <div class="header-right">

            <a
                href="profile.php"
                class="profile-icon"
                title="ข้อมูลส่วนตัว"
            >

                <i class="bi bi-person-circle"></i>

            </a>

        </div>

    </div>

</header>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="page-container">

    <div class="form-card">


        <!-- HEADER -->

        <div class="form-header">

            <h1>

                <i class="bi bi-folder-plus"></i>

                สร้างโปรเจกต์ใหม่

            </h1>

            <p>

                กรุณากรอกข้อมูลโปรเจกต์ให้ครบถ้วนก่อนส่งข้อมูล

            </p>

        </div>


        <!-- BODY -->

        <div class="form-body">


            <?php if ($error !== ''): ?>

                <div class="error-box">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <?php echo e($error); ?>

                </div>

            <?php endif; ?>


            <form
                action=""
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =================================================
                     PROJECT TITLE
                ================================================== -->

                <div class="form-group">

                    <label
                        for="title"
                        class="form-label"
                    >

                        ชื่อโปรเจกต์

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        placeholder="กรอกชื่อโปรเจกต์"
                        value="<?php echo e($title); ?>"
                        maxlength="255"
                        required
                    >

                </div>


                <!-- =================================================
                     DESCRIPTION
                ================================================== -->

                <div class="form-group">

                    <label
                        for="description"
                        class="form-label"
                    >

                        คำอธิบาย / บทคัดย่อ

                    </label>


                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        placeholder="กรอกคำอธิบายหรือบทคัดย่อของโปรเจกต์"
                    ><?php echo e($description); ?></textarea>

                </div>


                <!-- =================================================
                     DEGREE + DEPARTMENT
                ================================================== -->

                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label
                                for="degree"
                                class="form-label"
                            >

                                ระดับการศึกษา

                                <span class="required">*</span>

                            </label>


                            <select
                                id="degree"
                                name="degree"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- เลือกระดับการศึกษา --
                                </option>

                                <option
                                    value="ปริญญาตรี"
                                    <?php echo $degree === 'ปริญญาตรี' ? 'selected' : ''; ?>
                                >
                                    ปริญญาตรี
                                </option>

                                <option
                                    value="ปริญญาโท"
                                    <?php echo $degree === 'ปริญญาโท' ? 'selected' : ''; ?>
                                >
                                    ปริญญาโท
                                </option>

                                <option
                                    value="ปริญญาเอก"
                                    <?php echo $degree === 'ปริญญาเอก' ? 'selected' : ''; ?>
                                >
                                    ปริญญาเอก
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="form-group">

                            <label
                                for="department"
                                class="form-label"
                            >

                                สาขา / ภาควิชา

                                <span class="required">*</span>

                            </label>


                            <input
                                type="text"
                                id="department"
                                name="department"
                                class="form-control"
                                placeholder="เช่น เทคโนโลยีสารสนเทศ"
                                value="<?php echo e($department); ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     MEMBERS
                ================================================== -->

                <div class="form-group">

                    <div class="member-box">

                        <div class="member-title">

                            <i class="bi bi-people-fill"></i>

                            สมาชิกกลุ่ม

                            <span class="required">*</span>

                        </div>


                        <textarea
                            id="authors"
                            name="authors"
                            class="form-control"
                            placeholder="กรอกชื่อสมาชิกกลุ่ม เช่น&#10;นายสมชาย ใจดี&#10;นางสาวสมหญิง รักเรียน&#10;นายกิตติ ตั้งใจเรียน"
                            required
                        ><?php echo e($authors); ?></textarea>


                        <div class="form-help">

                            สามารถใส่ชื่อสมาชิกหลายคนได้
                            โดยแยกเป็นคนละบรรทัด

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     ADVISOR
                ================================================== -->

                <div class="form-group">

                    <div class="advisor-box">

                        <label
                            for="advisor"
                            class="form-label"
                        >

                            <i class="bi bi-person-workspace advisor-icon"></i>

                            อาจารย์ที่ปรึกษา

                            <span class="required">*</span>

                        </label>


                        <?php if (count($teachers) > 0): ?>

                            <select
                                id="advisor"
                                name="advisor"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- กรุณาเลือกอาจารย์ที่ปรึกษา --
                                </option>


                                <?php foreach ($teachers as $teacher): ?>

                                    <option
                                        value="<?php echo e($teacher['name']); ?>"
                                        <?php echo $advisor === $teacher['name'] ? 'selected' : ''; ?>
                                    >

                                        <?php echo e($teacher['name']); ?>

                                        <?php if ($teacher['department'] !== ''): ?>

                                            —
                                            <?php echo e($teacher['department']); ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <div class="form-help">

                                รายชื่ออาจารย์มาจากบัญชีผู้ใช้ที่มีสถานะเป็นอาจารย์

                            </div>

                        <?php else: ?>

                            <div class="no-teacher">

                                <i class="bi bi-exclamation-triangle-fill"></i>

                                ยังไม่มีรายชื่ออาจารย์ในระบบ
                                กรุณาให้ผู้ดูแลระบบเพิ่มบัญชีอาจารย์ก่อน

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =================================================
                     GITHUB
                ================================================== -->

                <div class="form-group">

                    <label
                        for="github_url"
                        class="form-label"
                    >

                        <i class="bi bi-github"></i>

                        GitHub Repository

                    </label>


                    <input
                        type="url"
                        id="github_url"
                        name="github_url"
                        class="form-control"
                        placeholder="https://github.com/username/project"
                        value="<?php echo e($github_url); ?>"
                        maxlength="500"
                    >


                    <div class="form-help">

                        หากไม่มี GitHub สามารถเว้นว่างได้

                    </div>

                </div>


                <!-- =================================================
                     PDF
                ================================================== -->

                <div class="form-group">

                    <label
                        for="pdf_file"
                        class="form-label"
                    >

                        <i class="bi bi-file-earmark-pdf"></i>

                        ไฟล์ PDF

                    </label>


                    <div class="file-box">

                        <input
                            type="file"
                            id="pdf_file"
                            name="pdf_file"
                            class="form-control"
                            accept=".pdf,application/pdf"
                        >


                        <div class="form-help">

                            รองรับเฉพาะไฟล์ PDF ขนาดไม่เกิน 10 MB

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     BUTTON
                ================================================== -->

                <div class="form-actions">

                    <a
                        href="index2.php"
                        class="cancel-button"
                    >

                        <i class="bi bi-arrow-left"></i>

                        ยกเลิก

                    </a>


                    <button
                        type="submit"
                        class="submit-button"
                    >

                        <i class="bi bi-cloud-arrow-up-fill"></i>

                        สร้างโปรเจกต์

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>