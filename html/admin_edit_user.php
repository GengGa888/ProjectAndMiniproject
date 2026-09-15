<?php

session_start();
require_once "db_connect.php";


/* =========================================================
   ตรวจสอบ Login
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   ตรวจสอบ Admin
========================================================= */

if (($_SESSION['role'] ?? '') !== 'admin') {

    echo "<script>
        alert('ไม่มีสิทธิ์เข้าหน้านี้');
        window.location.href='index2.php';
    </script>";

    exit;
}


/* =========================================================
   รับ ID ผู้ใช้
========================================================= */

$user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($user_id <= 0) {

    header("Location: admin.php");
    exit;
}


/* =========================================================
   ดึงข้อมูลผู้ใช้
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
        . htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
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
   ไม่พบผู้ใช้
========================================================= */

if (!$user) {

    echo "<script>
        alert('ไม่พบผู้ใช้งาน');
        window.location.href='admin.php';
    </script>";

    exit;
}


$error = "";


/* =========================================================
   เมื่อกดบันทึก
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================================
       รับข้อมูล
    ===================================================== */

    $user_code = trim(
        $_POST["user_code"] ?? ""
    );


    $prefix = trim(
        $_POST["prefix"] ?? ""
    );


    $other_prefix = trim(
        $_POST["other_prefix"] ?? ""
    );


    $first_name = trim(
        $_POST["first_name"] ?? ""
    );


    $last_name = trim(
        $_POST["last_name"] ?? ""
    );


    $email = trim(
        $_POST["email"] ?? ""
    );


    $role = trim(
        $_POST["role"] ?? ""
    );


    $department = trim(
        $_POST["department"] ?? ""
    );


    $password = $_POST["password"] ?? "";


    /* =====================================================
       ถ้าเลือก "อื่น ๆ"
       ใช้ค่าที่พิมพ์เอง
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
       ตรวจสอบข้อมูล
    ===================================================== */

    if ($error === "") {

        if (
            $user_code === "" ||
            $prefix === "" ||
            $first_name === "" ||
            $last_name === "" ||
            $email === ""
        ) {

            $error =
                "กรุณากรอกข้อมูลให้ครบ";

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
        }
    }


    /* =====================================================
       ตรวจสอบ User Code ซ้ำ
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
       ตรวจสอบ Email ซ้ำ
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
       บันทึกข้อมูล
    ===================================================== */

    $saved = false;


    if ($error === "") {


        /* =================================================
           ถ้าเปลี่ยน Password
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
                        "ssssssssi",
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


            /* =============================================
               ใช้ Password เดิม
            ============================================= */

            $stmt = $conn->prepare("
                UPDATE users
                SET
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
                    "sssssssi",
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
       บันทึกสำเร็จ
    ===================================================== */

    if (
        $error === "" &&
        $saved
    ) {


        /* =================================================
           ถ้าแก้บัญชีตัวเอง
           อัปเดต Session
        ================================================= */

        if (
            isset($_SESSION['user_id']) &&
            (int)$_SESSION['user_id'] === $user_id
        ) {

            $_SESSION['user_code'] =
                $user_code;


            $_SESSION['prefix'] =
                $prefix;


            $_SESSION['role'] =
                $role;


            $_SESSION['first_name'] =
                $first_name;


            $_SESSION['last_name'] =
                $last_name;


            $_SESSION['email'] =
                $email;


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
       ถ้า Error
       แสดงค่าที่กรอกล่าสุด
    ===================================================== */

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

?>


<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>แก้ไขผู้ใช้ - Admin</title>


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

            background: #f4f8fb;

            color: #333;
        }


        /* =========================
           HEADER
        ========================= */

        .header {

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6,
                    #3287BB
                );

            color: white;

            padding: 18px 40px;

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            box-shadow:
                0 2px 8px
                rgba(0,0,0,.08);
        }


        .header h2 {

            margin: 0;

            font-size: 23px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .header a {

            color: white;

            text-decoration: none;

            background:
                rgba(255,255,255,.18);

            padding: 10px 15px;

            border-radius: 8px;

            transition: .2s;
        }


        .header a:hover {

            background:
                rgba(255,255,255,.28);
        }


        /* =========================
           CONTAINER
        ========================= */

        .container {

            max-width: 850px;

            margin: 40px auto;

            padding: 0 20px;
        }


        /* =========================
           BOX
        ========================= */

        .box {

            background: white;

            border-radius: 15px;

            padding: 30px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.08);
        }


        .box h2 {

            margin-top: 0;

            margin-bottom: 25px;

            color: #287cab;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        /* =========================
           USERNAME
        ========================= */

        .username-box {

            background: #f4f8fb;

            padding: 13px 15px;

            border-radius: 8px;

            margin-bottom: 22px;

            color: #555;

            border-left:
                4px solid #4297cd;
        }


        .username-box strong {

            color: #287cab;
        }


        /* =========================
           FORM
        ========================= */

        .form-group {

            margin-bottom: 18px;
        }


        label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            color: #444;
        }


        input,
        select {

            width: 100%;

            padding: 12px 14px;

            border:
                1px solid #d8d8d8;

            border-radius: 8px;

            font-size: 15px;

            outline: none;

            background: white;

            transition: .2s;
        }


        input:focus,
        select:focus {

            border-color: #4297cd;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,.12);
        }


        /* =========================
           NAME GRID
        ========================= */

        .name-grid {

            display: grid;

            grid-template-columns:
                1fr 1.5fr 1.5fr;

            gap: 15px;
        }


        /* =========================
           PREFIX NOTE
        ========================= */

        .prefix-note {

            font-size: 13px;

            color: #777;

            margin-top: 6px;

            line-height: 1.5;
        }


        /* =========================
           OTHER PREFIX
        ========================= */

        #otherPrefixBox {

            display: none;

            background: #f8fbfd;

            padding: 15px;

            border-radius: 10px;

            border-left:
                4px solid #4297cd;

            margin-top: -5px;
        }


        /* =========================
           ERROR
        ========================= */

        .error {

            background: #ffe6e6;

            color: #b00000;

            padding: 12px;

            border-radius: 8px;

            margin-bottom: 20px;

            text-align: center;
        }


        /* =========================
           PASSWORD NOTE
        ========================= */

        .password-note {

            font-size: 13px;

            color: #777;

            margin-top: 6px;
        }


        /* =========================
           BUTTONS
        ========================= */

        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 25px;
        }


        .btn {

            flex: 1;

            border: none;

            padding: 12px;

            border-radius: 8px;

            text-decoration: none;

            text-align: center;

            cursor: pointer;

            font-size: 15px;

            transition: .2s;
        }


        .save {

            background: #198754;

            color: white;
        }


        .save:hover {

            background: #157347;
        }


        .cancel {

            background: #6c757d;

            color: white;
        }


        .cancel:hover {

            background: #5c636a;
        }


        /* =========================
           REQUIRED
        ========================= */

        .required {

            color: #dc3545;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 700px) {

            .name-grid {

                grid-template-columns: 1fr;
            }


            .header {

                padding:
                    15px 20px;
            }


            .header h2 {

                font-size: 20px;
            }


            .header a {

                padding:
                    8px 10px;

                font-size: 14px;
            }


            .box {

                padding: 22px;
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


        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="bi bi-exclamation-circle"></i>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             USERNAME
        ================================================= -->

        <div class="username-box">

            <i class="bi bi-person"></i>

            Username:

            <strong>

                <?= htmlspecialchars(
                    $user['username'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </strong>

        </div>


        <form method="POST">


            <!-- =================================================
                 USER CODE
            ================================================= -->

            <div class="form-group">

                <label>

                    รหัสนักศึกษา / รหัสบุคลากร

                    <span class="required">*</span>

                </label>

                <input
                    type="text"
                    name="user_code"
                    value="<?= htmlspecialchars(
                        $user['user_code'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="เช่น 6600000000"
                    required
                >

            </div>


            <!-- =================================================
                 PREFIX + NAME
            ================================================= -->

            <div class="name-grid">


                <!-- PREFIX -->

                <div class="form-group">

                    <label>

                        คำนำหน้า

                        <span class="required">*</span>

                    </label>


                    <select
                        name="prefix"
                        id="prefix"
                        required
                    >

                        <!-- JavaScript สร้างรายการ -->

                    </select>


                    <div class="prefix-note">

                        <i class="bi bi-info-circle"></i>

                        สำหรับอาจารย์ รายการจะแสดงชื่อเต็ม
                        แต่ระบบจะบันทึกเป็นตัวย่อ

                    </div>

                </div>


                <!-- FIRST NAME -->

                <div class="form-group">

                    <label>

                        ชื่อ

                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        name="first_name"
                        value="<?= htmlspecialchars(
                            $user['first_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>


                <!-- LAST NAME -->

                <div class="form-group">

                    <label>

                        นามสกุล

                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        name="last_name"
                        value="<?= htmlspecialchars(
                            $user['last_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
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

                <label>

                    ระบุคำนำหน้าเอง

                    <span class="required">*</span>

                </label>


                <input
                    type="text"
                    name="other_prefix"
                    id="other_prefix"
                    placeholder="พิมพ์คำนำหน้าที่ต้องการ"
                    maxlength="50"
                >

            </div>


            <!-- =================================================
                 EMAIL
            ================================================= -->

            <div class="form-group">

                <label>

                    Email

                    <span class="required">*</span>

                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $user['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- =================================================
                 ROLE
            ================================================= -->

            <div class="form-group">

                <label>

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

                <label>
                    สาขา
                </label>

                <input
                    type="text"
                    name="department"
                    value="<?= htmlspecialchars(
                        $user['department'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="เช่น เทคโนโลยีสารสนเทศ"
                >

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================= -->

            <div class="form-group">

                <label>
                    Password ใหม่
                </label>

                <input
                    type="password"
                    name="password"
                    minlength="4"
                    placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยน"
                >

                <div class="password-note">

                    <i class="bi bi-info-circle"></i>

                    ถ้าไม่กรอก จะใช้ Password เดิม

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
   คำนำหน้าอาจารย์

   label = สิ่งที่เห็นใน Dropdown
   value = สิ่งที่ส่งไปบันทึก Database
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


/* =========================================================
   คำนำหน้าทั่วไป
========================================================= */

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
   ตัวแปร
========================================================= */

const roleSelect =
    document.getElementById("role");


const prefixSelect =
    document.getElementById("prefix");


const otherPrefixBox =
    document.getElementById("otherPrefixBox");


const otherPrefixInput =
    document.getElementById("other_prefix");


/* =========================================================
   Prefix เดิมจาก Database
========================================================= */

let currentPrefix =
    <?= json_encode(
        $user['prefix'] ?? '',
        JSON_UNESCAPED_UNICODE
    ) ?>;


/* =========================================================
   สร้างรายการคำนำหน้า
========================================================= */

function updatePrefixOptions() {


    const role =
        roleSelect.value;


    const options =
        role === "teacher"
            ? teacherPrefixes
            : normalPrefixes;


    prefixSelect.innerHTML = "";


    /* =====================================================
       ตัวเลือกแรก
    ===================================================== */

    const defaultOption =
        document.createElement("option");


    defaultOption.value =
        "";


    defaultOption.textContent =
        "-- เลือกคำนำหน้า --";


    prefixSelect.appendChild(
        defaultOption
    );


    /* =====================================================
       สร้าง Options
    ===================================================== */

    options.forEach(
        function(item) {


            const option =
                document.createElement("option");


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
       เช็ก Prefix เดิม
    ===================================================== */

    let found = false;


    options.forEach(
        function(item) {


            if (
                item.value === currentPrefix
            ) {

                prefixSelect.value =
                    currentPrefix;


                found = true;
            }

        }
    );


    /* =====================================================
       ถ้าเป็น Prefix ที่พิมพ์เอง
    ===================================================== */

    if (
        !found &&
        currentPrefix !== ""
    ) {

        prefixSelect.value =
            "other";


        otherPrefixInput.value =
            currentPrefix;
    }


    checkOtherPrefix();
}


/* =========================================================
   ตรวจสอบ "อื่น ๆ"
========================================================= */

function checkOtherPrefix() {


    if (
        prefixSelect.value === "other"
    ) {


        /* แสดงช่อง */

        otherPrefixBox.style.display =
            "block";


        /* บังคับกรอก */

        otherPrefixInput.required =
            true;


        /* โฟกัสช่อง */

        otherPrefixInput.focus();


    } else {


        /* ซ่อนช่อง */

        otherPrefixBox.style.display =
            "none";


        /* ไม่บังคับ */

        otherPrefixInput.required =
            false;


        /* ล้างค่า */

        otherPrefixInput.value =
            "";
    }
}


/* =========================================================
   เปลี่ยน Role
========================================================= */

roleSelect.addEventListener(
    "change",
    function() {


        /*
         * ถ้าเปลี่ยน Role
         * ให้สร้างรายการ Prefix ใหม่
         */

        currentPrefix = "";


        updatePrefixOptions();

    }
);


/* =========================================================
   เปลี่ยน Prefix
========================================================= */

prefixSelect.addEventListener(
    "change",
    function() {

        checkOtherPrefix();

    }
);


/* =========================================================
   เริ่มต้นหน้า
========================================================= */

updatePrefixOptions();

</script>


</body>

</html>