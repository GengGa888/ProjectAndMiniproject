<?php
session_start();
require_once 'db_connect.php';


// =====================================================
// ตรวจสอบการเข้าสู่ระบบ
// =====================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);


// =====================================================
// ดึงข้อมูลผู้ใช้
// =====================================================

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
    die("SQL Error: " . mysqli_error($conn));
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
    die("ไม่พบข้อมูลผู้ใช้");
}


$first_name = $user_data['first_name'];
$last_name  = $user_data['last_name'];
$username   = $user_data['username'];
$email      = $user_data['email'];
$role       = $user_data['role'];
$department = $user_data['department'];


// =====================================================
// เมื่อกดส่งโปรเจกต์
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // -------------------------------------------------
    // รับข้อมูลจากฟอร์ม
    // -------------------------------------------------

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
        $_POST['department'] ?? $department
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


    // -------------------------------------------------
    // ตรวจสอบชื่อโปรเจกต์
    // -------------------------------------------------

    if ($title === '') {
        die("กรุณากรอกชื่อโปรเจกต์");
    }


    // -------------------------------------------------
    // ถ้าไม่ได้กรอกผู้จัดทำ
    // ใช้ชื่อผู้ส่งแทน
    // -------------------------------------------------

    if ($authors === '') {

        $authors =
            $first_name . ' ' . $last_name;
    }


    // -------------------------------------------------
    // ค่าเก่าสำหรับตาราง projects
    // -------------------------------------------------

    /*
     * project_name
     * = ชื่อโปรเจกต์เหมือนกับ title
     */

    $project_name = $title;


    /*
     * project_type
     * = ใช้ระดับการศึกษา
     */

    $project_type = $degree;


    /*
     * student_name
     * = ชื่อผู้จัดทำ
     */

    $student_name = $authors;


    /*
     * status
     */

    $status = "ส่งแล้ว";


    // =================================================
    // อัปโหลด PDF
    // =================================================

    $pdf_filename = null;


    if (
        isset($_FILES['pdf_file']) &&
        $_FILES['pdf_file']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {


        // ---------------------------------------------
        // ตรวจสอบ Error
        // ---------------------------------------------

        if (
            $_FILES['pdf_file']['error']
            !== UPLOAD_ERR_OK
        ) {

            die(
                "เกิดข้อผิดพลาดในการอัปโหลด PDF"
            );
        }


        $file = $_FILES['pdf_file'];


        // ---------------------------------------------
        // จำกัดขนาด 10 MB
        // ---------------------------------------------

        if (
            $file['size']
            > 10 * 1024 * 1024
        ) {

            die(
                "ไฟล์ PDF ต้องมีขนาดไม่เกิน 10 MB"
            );
        }


        // ---------------------------------------------
        // ตรวจสอบนามสกุล
        // ---------------------------------------------

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


        // ---------------------------------------------
        // สร้างชื่อไฟล์ใหม่
        // ---------------------------------------------

        $pdf_filename =
            "project_" .
            $user_id . "_" .
            time() . "_" .
            bin2hex(
                random_bytes(4)
            ) .
            ".pdf";


        // ---------------------------------------------
        // โฟลเดอร์ uploads
        // ---------------------------------------------

        $upload_dir =
            __DIR__ . "/uploads/";


        if (!is_dir($upload_dir)) {

            mkdir(
                $upload_dir,
                0777,
                true
            );
        }


        $destination =
            $upload_dir .
            $pdf_filename;


        // ---------------------------------------------
        // ย้ายไฟล์
        // ---------------------------------------------

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


    // =================================================
    // บันทึกลงฐานข้อมูล
    // =================================================

    /*
     * บันทึกชื่อโปรเจกต์ลงทั้ง
     *
     * project_name
     * title
     *
     * เพื่อให้ระบบเก่ากับระบบใหม่ทำงานร่วมกัน
     */

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


    if (!$stmt) {

        // ถ้า SQL ผิดและมี PDF
        // ให้ลบไฟล์ออก

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


    /*
     * มีทั้งหมด 13 ค่า
     *
     * 12 ตัวแรก = string
     * ตัวสุดท้าย student_id = integer
     */

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


    // =================================================
    // Execute
    // =================================================

    if (
        mysqli_stmt_execute($stmt)
    ) {

        // สำเร็จ
        header(
            "Location: profile.php?success=project"
        );

        exit;

    } else {


        // ---------------------------------------------
        // ถ้าบันทึกไม่ได้ ลบ PDF
        // ---------------------------------------------

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
            font-family: Arial, sans-serif;
            background: #f4f8fb;
            color: #333;
        }


        /* =========================
           Header
        ========================= */

        .custom-header {
            height: 90px;

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6,
                    #4297cd,
                    #3287bb
                );

            color: white;
        }


        .header-inner {
            max-width: 1280px;
            height: 100%;
            margin: auto;
            padding: 0 20px;

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
        }


        .sdu-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }


        .header-title {
            font-size: 25px;
            font-weight: bold;
        }


        .home-link {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }


        .home-link:hover {
            text-decoration: underline;
        }


        /* =========================
           Container
        ========================= */

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }


        /* =========================
           Card
        ========================= */

        .card {
            background: white;

            border-radius: 16px;

            padding: 30px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.08);
        }


        h1 {
            margin-top: 0;
            color: #3287bb;
        }


        /* =========================
           User Info
        ========================= */

        .user-info {
            background: #eef8fd;

            border-radius: 12px;

            padding: 15px;

            margin-bottom: 25px;
        }


        /* =========================
           Form
        ========================= */

        .form-group {
            margin-bottom: 18px;
        }


        label {
            display: block;

            margin-bottom: 7px;

            font-weight: bold;
        }


        input,
        textarea,
        select {

            width: 100%;

            padding: 12px;

            border:
                1px solid #ccc;

            border-radius: 8px;

            font-size: 15px;
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

            min-height: 120px;

            resize: vertical;
        }


        /* =========================
           Button
        ========================= */

        .btn {

            border: none;

            padding: 13px 22px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 16px;
        }


        .btn-primary {

            background: #3287bb;

            color: white;
        }


        .btn-primary:hover {

            background: #256f9e;
        }


        .required {

            color: red;
        }


        small {

            color: #777;
        }

    </style>

</head>


<body>


<!-- =================================================
     Header
================================================== -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">


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


            <div class="header-title">

                คลังโปรเจกต์ SDU

            </div>


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
     Main
================================================== -->

<div class="container">

    <div class="card">


        <h1>

            <i class="bi bi-file-earmark-plus"></i>

            ส่งโปรเจกต์นักศึกษา

        </h1>



        <!-- =================================================
             User information
        ================================================== -->

        <div class="user-info">

            <strong>
                ผู้ส่ง:
            </strong>

            <?= htmlspecialchars(
                $first_name . ' ' . $last_name
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
             Form
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
                        $first_name .
                        ' ' .
                        $last_name
                    ) ?>"
                    placeholder="ชื่อผู้จัดทำ"
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
                    accept=".pdf"
                >


                <small>
                    รองรับไฟล์ PDF ขนาดไม่เกิน 10 MB
                </small>

            </div>



            <!-- Submit -->

            <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="bi bi-cloud-arrow-up"></i>

                ส่งโปรเจกต์

            </button>


        </form>


    </div>

</div>


</body>

</html>