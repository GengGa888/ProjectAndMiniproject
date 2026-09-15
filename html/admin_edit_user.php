<?php

session_start();
require_once "db_connect.php";


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   ADMIN CHECK
========================================================= */

if (($_SESSION['role'] ?? '') !== 'admin') {

    echo "<script>
        alert('ไม่มีสิทธิ์เข้าหน้านี้');
        window.location.href='index2.php';
    </script>";

    exit;
}


/* =========================================================
   GET USER ID
========================================================= */

$user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($user_id <= 0) {

    header("Location: admin.php");
    exit;
}


/* =========================================================
   GET USER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        username,
        user_code,
        prefix,
        first_name,
        last_name,
        email,
        role,
        department
    FROM users
    WHERE id = ?
    LIMIT 1
");


if (!$stmt) {

    die(
        "เกิดข้อผิดพลาด SQL: "
        . e($conn->error)
    );
}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();


$user = $result->fetch_assoc();


$stmt->close();


/* =========================================================
   USER NOT FOUND
========================================================= */

if (!$user) {

    echo "<script>
        alert('ไม่พบผู้ใช้งาน');
        window.location.href='admin.php';
    </script>";

    exit;
}


$error = "";
$saved = false;


/* =========================================================
   FORM SUBMIT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================================
       USERNAME
    ===================================================== */

    $username = trim(
        $_POST["username"] ?? ""
    );


    /* =====================================================
       USER CODE
    ===================================================== */

    $user_code = trim(
        $_POST["user_code"] ?? ""
    );


    /* =====================================================
       PREFIX
    ===================================================== */

    $prefix = trim(
        $_POST["prefix"] ?? ""
    );


    $other_prefix = trim(
        $_POST["other_prefix"] ?? ""
    );


    /* =====================================================
       NAME
    ===================================================== */

    $first_name = trim(
        $_POST["first_name"] ?? ""
    );


    $last_name = trim(
        $_POST["last_name"] ?? ""
    );


    /* =====================================================
       EMAIL
    ===================================================== */

    $email = trim(
        $_POST["email"] ?? ""
    );


    /* =====================================================
       ROLE
    ===================================================== */

    $role = trim(
        $_POST["role"] ?? ""
    );


    /* =====================================================
       DEPARTMENT
    ===================================================== */

    $department_select = trim(
        $_POST["department"] ?? ""
    );


    $other_department = trim(
        $_POST["other_department"] ?? ""
    );


    if ($department_select === "อื่นๆ") {

        $department = $other_department;

    } else {

        $department = $department_select;
    }


    /* =====================================================
       PASSWORD
    ===================================================== */

    $password = $_POST["password"] ?? "";


    /* =====================================================
       OTHER PREFIX
    ===================================================== */

    if ($prefix === "other") {

        if ($other_prefix === "") {

            $error =
                "กรุณาระบุคำนำหน้า";

        } else {

            $prefix =
                $other_prefix;
        }
    }


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

    if ($error === "") {

        if ($username === "") {

            $error =
                "กรุณากรอก Username";

        } elseif ($user_code === "") {

            $error =
                "กรุณากรอกรหัสนักศึกษา / รหัสบุคลากร";

        } elseif ($prefix === "") {

            $error =
                "กรุณาเลือกคำนำหน้า";

        } elseif ($first_name === "") {

            $error =
                "กรุณากรอกชื่อ";

        } elseif ($last_name === "") {

            $error =
                "กรุณากรอกนามสกุล";

        } elseif ($email === "") {

            $error =
                "กรุณากรอก Email";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                "รูปแบบ Email ไม่ถูกต้อง";

        } elseif (
            !in_array(
                $role,
                [
                    "student",
                    "teacher",
                    "admin"
                ],
                true
            )
        ) {

            $error =
                "Role ไม่ถูกต้อง";

        } elseif (
            $department === ""
        ) {

            $error =
                "กรุณาเลือกสาขา";
        }
    }


    /* =====================================================
       CHECK USERNAME DUPLICATE
    ===================================================== */

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            AND id != ?
            LIMIT 1
        ");


        if (!$stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบ Username: "
                . $conn->error;

        } else {

            $stmt->bind_param(
                "si",
                $username,
                $user_id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            if ($result->num_rows > 0) {

                $error =
                    "Username นี้ถูกใช้งานแล้ว";
            }


            $stmt->close();
        }
    }


    /* =====================================================
       CHECK USER CODE DUPLICATE
    ===================================================== */

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE user_code = ?
            AND id != ?
            LIMIT 1
        ");


        if (!$stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบรหัสประจำตัว: "
                . $conn->error;

        } else {

            $stmt->bind_param(
                "si",
                $user_code,
                $user_id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            if ($result->num_rows > 0) {

                $error =
                    "รหัสประจำตัวนี้ถูกใช้งานแล้ว";
            }


            $stmt->close();
        }
    }


    /* =====================================================
       CHECK EMAIL DUPLICATE
    ===================================================== */

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");


        if (!$stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบ Email: "
                . $conn->error;

        } else {

            $stmt->bind_param(
                "si",
                $email,
                $user_id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            if ($result->num_rows > 0) {

                $error =
                    "Email นี้ถูกใช้งานแล้ว";
            }


            $stmt->close();
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === "") {


        /* =================================================
           CHANGE PASSWORD
        ================================================= */

        if ($password !== "") {


            if (strlen($password) < 4) {

                $error =
                    "Password ต้องมีอย่างน้อย 4 ตัวอักษร";

            } else {


                $hashed_password =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        user_code = ?,
                        prefix = ?,
                        first_name = ?,
                        last_name = ?,
                        email = ?,
                        role = ?,
                        department = ?,
                        password = ?
                    WHERE id = ?
                ");


                if (!$stmt) {

                    $error =
                        "เกิดข้อผิดพลาดในการเตรียมคำสั่ง: "
                        . $conn->error;

                } else {

                    $stmt->bind_param(
                        "sssssssssi",
                        $username,
                        $user_code,
                        $prefix,
                        $first_name,
                        $last_name,
                        $email,
                        $role,
                        $department,
                        $hashed_password,
                        $user_id
                    );


                    $saved =
                        $stmt->execute();


                    if (!$saved) {

                        $error =
                            "เกิดข้อผิดพลาดในการบันทึกข้อมูล: "
                            . $stmt->error;
                    }


                    $stmt->close();
                }
            }


        } else {


            /* =================================================
               KEEP OLD PASSWORD
            ================================================= */

            $stmt = $conn->prepare("
                UPDATE users
                SET
                    username = ?,
                    user_code = ?,
                    prefix = ?,
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    role = ?,
                    department = ?
                WHERE id = ?
            ");


            if (!$stmt) {

                $error =
                    "เกิดข้อผิดพลาดในการเตรียมคำสั่ง: "
                    . $conn->error;

            } else {

                $stmt->bind_param(
                    "ssssssssi",
                    $username,
                    $user_code,
                    $prefix,
                    $first_name,
                    $last_name,
                    $email,
                    $role,
                    $department,
                    $user_id
                );


                $saved =
                    $stmt->execute();


                if (!$saved) {

                    $error =
                        "เกิดข้อผิดพลาดในการบันทึกข้อมูล: "
                        . $stmt->error;
                }


                $stmt->close();
            }
        }
    }


    /* =====================================================
       SAVE SUCCESS
    ===================================================== */

    if (
        $error === "" &&
        $saved
    ) {


        /* =================================================
           UPDATE SESSION
           กรณีแอดมินแก้บัญชีตัวเอง
        ================================================= */

        if (
            isset($_SESSION['user_id']) &&
            (int)$_SESSION['user_id'] === $user_id
        ) {

            $_SESSION['username'] =
                $username;

            $_SESSION['user_code'] =
                $user_code;

            $_SESSION['prefix'] =
                $prefix;

            $_SESSION['first_name'] =
                $first_name;

            $_SESSION['last_name'] =
                $last_name;

            $_SESSION['email'] =
                $email;

            $_SESSION['role'] =
                $role;

            $_SESSION['department'] =
                $department;
        }


        echo "<script>
            alert('แก้ไขผู้ใช้สำเร็จ');
            window.location.href='admin.php';
        </script>";

        exit;
    }


    /* =====================================================
       ERROR
       KEEP LATEST DATA
    ===================================================== */

    $user['username'] =
        $username;

    $user['user_code'] =
        $user_code;

    $user['prefix'] =
        $prefix;

    $user['first_name'] =
        $first_name;

    $user['last_name'] =
        $last_name;

    $user['email'] =
        $email;

    $user['role'] =
        $role;

    $user['department'] =
        $department;
}


/* =========================================================
   DEPARTMENT OPTIONS
========================================================= */

$departments = [

    "เทคโนโลยีสารสนเทศ",

    "วิทยาการคอมพิวเตอร์",

    "เทคโนโลยีดิจิทัล",

    "คอมพิวเตอร์ธุรกิจ",

    "มัลติมีเดีย",

    "วิทยาศาสตร์สิ่งแวดล้อม",

    "เทคโนโลยีการประกอบอาหาร",

    "อื่นๆ"
];


$current_department =
    trim(
        $user['department'] ?? ''
    );


$is_other_department =
    $current_department !== ''
    &&
    !in_array(
        $current_department,
        array_slice(
            $departments,
            0,
            -1
        ),
        true
    );


/* =========================================================
   PREFIX DATA
========================================================= */

$teacher_prefixes = [

    "อ." => "อาจารย์",

    "ผศ." => "ผู้ช่วยศาสตราจารย์",

    "ผศ.ดร." => "ผู้ช่วยศาสตราจารย์ ดร.",

    "รศ." => "รองศาสตราจารย์",

    "รศ.ดร." => "รองศาสตราจารย์ ดร.",

    "ศ." => "ศาสตราจารย์",

    "ศ.ดร." => "ศาสตราจารย์ ดร.",

    "ดร." => "ดอกเตอร์"
];


$normal_prefixes = [

    "นาย" => "นาย",

    "นาง" => "นาง",

    "นางสาว" => "นางสาว"
];


$current_prefix =
    trim(
        $user['prefix'] ?? ''
    );


$is_custom_prefix =
    $current_prefix !== ''
    &&
    !array_key_exists(
        $current_prefix,
        $teacher_prefixes
    )
    &&
    !array_key_exists(
        $current_prefix,
        $normal_prefixes
    );

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
        แก้ไขผู้ใช้ - Admin
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
                Arial,
                "Sarabun",
                sans-serif;

            background:
                #f4f8fb;

            color:
                #333;
        }


        /* =================================================
           HEADER
        ================================================= */

        .header {

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6,
                    #3287BB
                );

            color:
                white;

            padding:
                18px 40px;

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            box-shadow:
                0 2px 8px
                rgba(0,0,0,.08);
        }


        .header h2 {

            margin:
                0;

            font-size:
                23px;

            display:
                flex;

            align-items:
                center;

            gap:
                10px;
        }


        .header a {

            color:
                white;

            text-decoration:
                none;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .18
                );

            padding:
                10px 15px;

            border-radius:
                8px;

            transition:
                .2s;
        }


        .header a:hover {

            background:
                rgba(
                    255,
                    255,
                    255,
                    .28
                );
        }


        /* =================================================
           CONTAINER
        ================================================= */

        .container {

            max-width:
                900px;

            margin:
                40px auto;

            padding:
                0 20px;
        }


        /* =================================================
           BOX
        ================================================= */

        .box {

            background:
                white;

            border-radius:
                15px;

            padding:
                30px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.08);
        }


        .box h2 {

            margin-top:
                0;

            margin-bottom:
                25px;

            color:
                #287cab;

            display:
                flex;

            align-items:
                center;

            gap:
                10px;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-group {

            margin-bottom:
                18px;
        }


        label {

            display:
                block;

            margin-bottom:
                7px;

            font-weight:
                bold;

            color:
                #444;
        }


        input,
        select {

            width:
                100%;

            padding:
                12px 14px;

            border:
                1px solid #d8d8d8;

            border-radius:
                8px;

            font-size:
                15px;

            outline:
                none;

            background:
                white;

            transition:
                .2s;
        }


        input:focus,
        select:focus {

            border-color:
                #4297cd;

            box-shadow:
                0 0 0 3px
                rgba(
                    66,
                    151,
                    205,
                    .12
                );
        }


        /* =================================================
           USERNAME INFO
        ================================================= */

        .username-info {

            background:
                #eef8fd;

            border-left:
                4px solid #4297cd;

            padding:
                13px 15px;

            border-radius:
                8px;

            margin-bottom:
                22px;

            color:
                #555;
        }


        .username-info strong {

            color:
                #287cab;
        }


        /* =================================================
           NAME GRID
        ================================================= */

        .name-grid {

            display:
                grid;

            grid-template-columns:
                1fr 1.5fr 1.5fr;

            gap:
                15px;
        }


        /* =================================================
           PREFIX
        ================================================= */

        .prefix-note {

            font-size:
                13px;

            color:
                #777;

            margin-top:
                6px;

            line-height:
                1.5;
        }


        #otherPrefixBox {

            display:
                none;

            background:
                #f8fbfd;

            padding:
                15px;

            border-radius:
                10px;

            border-left:
                4px solid #4297cd;

            margin-bottom:
                18px;
        }


        /* =================================================
           DEPARTMENT
        ================================================= */

        .department-note {

            font-size:
                13px;

            color:
                #777;

            margin-top:
                6px;
        }


        #otherDepartmentBox {

            display:
                none;

            background:
                #f8fbfd;

            padding:
                15px;

            border-radius:
                10px;

            border-left:
                4px solid #4297cd;

            margin-top:
                10px;
        }


        /* =================================================
           PASSWORD
        ================================================= */

        .password-note {

            font-size:
                13px;

            color:
                #777;

            margin-top:
                6px;
        }


        /* =================================================
           ERROR
        ================================================= */

        .error {

            background:
                #ffe6e6;

            color:
                #b00000;

            padding:
                12px;

            border-radius:
                8px;

            margin-bottom:
                20px;

            text-align:
                center;
        }


        /* =================================================
           REQUIRED
        ================================================= */

        .required {

            color:
                #dc3545;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .buttons {

            display:
                flex;

            gap:
                10px;

            margin-top:
                25px;
        }


        .btn {

            flex:
                1;

            border:
                none;

            padding:
                12px;

            border-radius:
                8px;

            text-decoration:
                none;

            text-align:
                center;

            cursor:
                pointer;

            font-size:
                15px;

            transition:
                .2s;
        }


        .save {

            background:
                #198754;

            color:
                white;
        }


        .save:hover {

            background:
                #157347;
        }


        .cancel {

            background:
                #6c757d;

            color:
                white;
        }


        .cancel:hover {

            background:
                #5c636a;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (
            max-width: 700px
        ) {

            .name-grid {

                grid-template-columns:
                    1fr;
            }


            .header {

                padding:
                    15px 20px;
            }


            .header h2 {

                font-size:
                    20px;
            }


            .header a {

                padding:
                    8px 10px;

                font-size:
                    14px;
            }


            .box {

                padding:
                    22px;
            }


            .buttons {

                flex-direction:
                    column;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

    <h2>

        <i class="bi bi-person-gear"></i>

        แก้ไขผู้ใช้งาน

    </h2>


    <a href="admin.php">

        <i class="bi bi-arrow-left"></i>

        กลับ Admin

    </a>

</div>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container">

    <div class="box">

        <h2>

            <i class="bi bi-person-gear"></i>

            แก้ไขข้อมูลผู้ใช้งาน

        </h2>


        <!-- =================================================
             ERROR
        ================================================= -->

        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="bi bi-exclamation-circle"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             USERNAME
        ================================================= -->

        <div class="username-info">

            <i class="bi bi-person"></i>

            กำลังแก้ไขบัญชี:

            <strong>
                <?= e($user['username'] ?? '') ?>
            </strong>

        </div>


        <form
            method="POST"
            autocomplete="off"
        >


            <!-- =================================================
                 USERNAME
            ================================================= -->

            <div class="form-group">

                <label for="username">

                    Username

                    <span class="required">*</span>

                </label>


                <input
                    type="text"
                    name="username"
                    id="username"
                    value="<?= e(
                        $user['username'] ?? ''
                    ) ?>"
                    placeholder="กรอก Username"
                    maxlength="50"
                    required
                >


                <div class="prefix-note">

                    <i class="bi bi-info-circle"></i>

                    สามารถเปลี่ยน Username ได้
                    แต่ต้องไม่ซ้ำกับสมาชิกคนอื่น

                </div>

            </div>


            <!-- =================================================
                 USER CODE
            ================================================= -->

            <div class="form-group">

                <label for="user_code">

                    รหัสนักศึกษา / รหัสบุคลากร

                    <span class="required">*</span>

                </label>


                <input
                    type="text"
                    name="user_code"
                    id="user_code"
                    value="<?= e(
                        $user['user_code'] ?? ''
                    ) ?>"
                    placeholder="เช่น 6600000000"
                    maxlength="50"
                    required
                >

            </div>


            <!-- =================================================
                 PREFIX + NAME
            ================================================= -->

            <div class="name-grid">


                <!-- PREFIX -->

                <div class="form-group">

                    <label for="prefix">

                        คำนำหน้า

                        <span class="required">*</span>

                    </label>


                    <select
                        name="prefix"
                        id="prefix"
                        required
                    >

                        <!-- JavaScript -->

                    </select>


                    <div class="prefix-note">

                        <i class="bi bi-info-circle"></i>

                        สำหรับอาจารย์
                        Dropdown จะแสดงชื่อเต็ม
                        แต่ระบบจะบันทึกเป็นตัวย่อ

                    </div>

                </div>


                <!-- FIRST NAME -->

                <div class="form-group">

                    <label for="first_name">

                        ชื่อ

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        name="first_name"
                        id="first_name"
                        value="<?= e(
                            $user['first_name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <!-- LAST NAME -->

                <div class="form-group">

                    <label for="last_name">

                        นามสกุล

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        name="last_name"
                        id="last_name"
                        value="<?= e(
                            $user['last_name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 OTHER PREFIX
            ================================================= -->

            <div
                class="form-group"
                id="otherPrefixBox"
            >

                <label for="other_prefix">

                    ระบุคำนำหน้าเอง

                    <span class="required">*</span>

                </label>


                <input
                    type="text"
                    name="other_prefix"
                    id="other_prefix"
                    placeholder="พิมพ์คำนำหน้าที่ต้องการ"
                    maxlength="50"
                    value="<?= $is_custom_prefix
                        ? e($current_prefix)
                        : '' ?>"
                >

            </div>


            <!-- =================================================
                 EMAIL
            ================================================= -->

            <div class="form-group">

                <label for="email">

                    Email

                    <span class="required">*</span>

                </label>


                <input
                    type="email"
                    name="email"
                    id="email"
                    value="<?= e(
                        $user['email'] ?? ''
                    ) ?>"
                    placeholder="example@email.com"
                    maxlength="100"
                    required
                >

            </div>


            <!-- =================================================
                 ROLE
            ================================================= -->

            <div class="form-group">

                <label for="role">

                    Role

                    <span class="required">*</span>

                </label>


                <select
                    name="role"
                    id="role"
                    required
                >

                    <option
                        value="student"
                        <?= (
                            ($user['role'] ?? '')
                            === 'student'
                        )
                        ? 'selected'
                        : ''
                        ?>
                    >

                        Student

                    </option>


                    <option
                        value="teacher"
                        <?= (
                            ($user['role'] ?? '')
                            === 'teacher'
                        )
                        ? 'selected'
                        : ''
                        ?>
                    >

                        Teacher

                    </option>


                    <option
                        value="admin"
                        <?= (
                            ($user['role'] ?? '')
                            === 'admin'
                        )
                        ? 'selected'
                        : ''
                        ?>
                    >

                        Admin

                    </option>

                </select>

            </div>


            <!-- =================================================
                 DEPARTMENT
            ================================================= -->

            <div class="form-group">

                <label for="department">

                    สาขาวิชา

                    <span class="required">*</span>

                </label>


                <select
                    name="department"
                    id="department"
                    required
                >

                    <option value="">

                        -- เลือกสาขาวิชา --

                    </option>


                    <?php foreach (
                        $departments as $dept
                    ): ?>

                        <option
                            value="<?= e($dept) ?>"
                            <?= (
                                !$is_other_department &&
                                $current_department === $dept
                            )
                            ? 'selected'
                            : ''
                            ?>
                        >

                            <?= e($dept) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- OTHER DEPARTMENT -->

                <div
                    id="otherDepartmentBox"
                >

                    <label
                        for="other_department"
                    >

                        ระบุสาขาวิชาเอง

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        name="other_department"
                        id="other_department"
                        maxlength="100"
                        placeholder="พิมพ์ชื่อสาขาวิชา"
                        value="<?= $is_other_department
                            ? e($current_department)
                            : '' ?>"
                    >

                </div>


                <div class="department-note">

                    <i class="bi bi-info-circle"></i>

                    หากเลือก "อื่นๆ"
                    สามารถพิมพ์ชื่อสาขาวิชาเองได้

                </div>

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================= -->

            <div class="form-group">

                <label for="password">

                    Password ใหม่

                </label>


                <input
                    type="password"
                    name="password"
                    id="password"
                    minlength="4"
                    maxlength="255"
                    placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยน"
                    autocomplete="new-password"
                >


                <div class="password-note">

                    <i class="bi bi-info-circle"></i>

                    ถ้าไม่กรอก
                    ระบบจะใช้ Password เดิม

                </div>

            </div>


            <!-- =================================================
                 BUTTONS
            ================================================= -->

            <div class="buttons">

                <a
                    href="admin.php"
                    class="btn cancel"
                >

                    <i class="bi bi-x-circle"></i>

                    ยกเลิก

                </a>


                <button
                    type="submit"
                    class="btn save"
                >

                    <i class="bi bi-check-circle"></i>

                    บันทึกการแก้ไข

                </button>

            </div>


        </form>

    </div>

</div>


<script>

/* =========================================================
   PREFIX DATA
========================================================= */

const teacherPrefixes = [

    {
        label: "อาจารย์",
        value: "อ."
    },

    {
        label: "ผู้ช่วยศาสตราจารย์",
        value: "ผศ."
    },

    {
        label: "ผู้ช่วยศาสตราจารย์ ดร.",
        value: "ผศ.ดร."
    },

    {
        label: "รองศาสตราจารย์",
        value: "รศ."
    },

    {
        label: "รองศาสตราจารย์ ดร.",
        value: "รศ.ดร."
    },

    {
        label: "ศาสตราจารย์",
        value: "ศ."
    },

    {
        label: "ศาสตราจารย์ ดร.",
        value: "ศ.ดร."
    },

    {
        label: "ดอกเตอร์",
        value: "ดร."
    },

    {
        label: "อื่น ๆ",
        value: "other"
    }

];


const normalPrefixes = [

    {
        label: "นาย",
        value: "นาย"
    },

    {
        label: "นาง",
        value: "นาง"
    },

    {
        label: "นางสาว",
        value: "นางสาว"
    },

    {
        label: "อื่น ๆ",
        value: "other"
    }

];


/* =========================================================
   ELEMENTS
========================================================= */

const roleSelect =
    document.getElementById(
        "role"
    );


const prefixSelect =
    document.getElementById(
        "prefix"
    );


const otherPrefixBox =
    document.getElementById(
        "otherPrefixBox"
    );


const otherPrefixInput =
    document.getElementById(
        "other_prefix"
    );


const departmentSelect =
    document.getElementById(
        "department"
    );


const otherDepartmentBox =
    document.getElementById(
        "otherDepartmentBox"
    );


const otherDepartmentInput =
    document.getElementById(
        "other_department"
    );


/* =========================================================
   CURRENT PREFIX
========================================================= */

let currentPrefix =
    <?= json_encode(
        $current_prefix,
        JSON_UNESCAPED_UNICODE
    ) ?>;


/* =========================================================
   UPDATE PREFIX
========================================================= */

function updatePrefixOptions(
    keepCurrent = true
) {


    const role =
        roleSelect.value;


    const options =
        role === "teacher"
            ? teacherPrefixes
            : normalPrefixes;


    prefixSelect.innerHTML = "";


    /* =====================================================
       DEFAULT
    ===================================================== */

    const defaultOption =
        document.createElement(
            "option"
        );


    defaultOption.value =
        "";


    defaultOption.textContent =
        "-- เลือกคำนำหน้า --";


    prefixSelect.appendChild(
        defaultOption
    );


    /* =====================================================
       OPTIONS
    ===================================================== */

    options.forEach(
        function(item) {


            const option =
                document.createElement(
                    "option"
                );


            option.value =
                item.value;


            option.textContent =
                item.label;


            prefixSelect.appendChild(
                option
            );

        }
    );


    /* =====================================================
       SELECT CURRENT
    ===================================================== */

    let found =
        false;


    if (keepCurrent) {

        options.forEach(
            function(item) {


                if (
                    item.value ===
                    currentPrefix
                ) {

                    prefixSelect.value =
                        currentPrefix;

                    found =
                        true;
                }

            }
        );


        /* =================================================
           CUSTOM PREFIX
        ================================================= */

        if (
            !found &&
            currentPrefix !== ""
        ) {

            prefixSelect.value =
                "other";


            otherPrefixInput.value =
                currentPrefix;
        }

    }


    checkOtherPrefix();
}


/* =========================================================
   CHECK OTHER PREFIX
========================================================= */

function checkOtherPrefix()
{

    if (
        prefixSelect.value ===
        "other"
    ) {


        otherPrefixBox.style.display =
            "block";


        otherPrefixInput.required =
            true;


    } else {


        otherPrefixBox.style.display =
            "none";


        otherPrefixInput.required =
            false;


        /*
         * ไม่ล้างค่าตรงนี้
         * เพื่อไม่ให้ข้อมูลหายเวลาเปลี่ยน Role
         */

    }
}


/* =========================================================
   ROLE CHANGE
========================================================= */

roleSelect.addEventListener(
    "change",
    function()
    {

        /*
         * เปลี่ยน Role
         * แล้วเลือก Prefix ใหม่
         */

        currentPrefix =
            "";


        prefixSelect.value =
            "";


        updatePrefixOptions(
            false
        );

    }
);


/* =========================================================
   PREFIX CHANGE
========================================================= */

prefixSelect.addEventListener(
    "change",
    function()
    {

        checkOtherPrefix();

    }
);


/* =========================================================
   DEPARTMENT
========================================================= */

function checkOtherDepartment()
{

    if (
        departmentSelect.value ===
        "อื่นๆ"
    ) {


        otherDepartmentBox.style.display =
            "block";


        otherDepartmentInput.required =
            true;


    } else {


        otherDepartmentBox.style.display =
            "none";


        otherDepartmentInput.required =
            false;

    }
}


departmentSelect.addEventListener(
    "change",
    function()
    {

        checkOtherDepartment();

    }
);


/* =========================================================
   INITIAL
========================================================= */

updatePrefixOptions(
    true
);


checkOtherDepartment();

</script>


</body>

</html>