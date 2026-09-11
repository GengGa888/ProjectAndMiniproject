<?php

session_start();
require_once "db_connect.php";


/* =========================
   ตรวจสอบ Login
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


/* =========================
   ตรวจสอบ Admin
========================= */

if (($_SESSION['role'] ?? '') !== 'admin') {

    echo "<script>
        alert('ไม่มีสิทธิ์เข้าหน้านี้');
        window.location.href='index2.php';
    </script>";

    exit;
}


/* =========================
   รับ ID ผู้ใช้
========================= */

$user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($user_id <= 0) {
    header("Location: admin.php");
    exit;
}


/* =========================
   ดึงข้อมูลผู้ใช้
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        username,
        user_code,
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
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง");
}


$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


/* =========================
   ไม่พบผู้ใช้
========================= */

if (!$user) {

    echo "<script>
        alert('ไม่พบผู้ใช้งาน');
        window.location.href='admin.php';
    </script>";

    exit;
}


$error = "";


/* =========================
   เมื่อกดบันทึก
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_code  = trim($_POST["user_code"] ?? "");

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");

    $email      = trim($_POST["email"] ?? "");

    $role       = trim($_POST["role"] ?? "");

    $department = trim($_POST["department"] ?? "");

    $password   = $_POST["password"] ?? "";


    /* =========================
       ตรวจสอบข้อมูล
    ========================= */

    if (
        $user_code === "" ||
        $first_name === "" ||
        $last_name === "" ||
        $email === ""
    ) {

        $error = "กรุณากรอกข้อมูลให้ครบ";

    } elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $error = "รูปแบบ Email ไม่ถูกต้อง";

    } elseif (!in_array(
        $role,
        ["student", "teacher", "admin"],
        true
    )) {

        $error = "Role ไม่ถูกต้อง";
    }


    /* =========================
       ตรวจสอบ User Code ซ้ำ
    ========================= */

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
                "เกิดข้อผิดพลาดในการตรวจสอบรหัสประจำตัว";

        } else {

            $stmt->bind_param(
                "si",
                $user_code,
                $user_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if (
                $result->num_rows > 0
            ) {

                $error =
                    "รหัสประจำตัวนี้ถูกใช้งานแล้ว";
            }

            $stmt->close();
        }
    }


    /* =========================
       ตรวจสอบ Email ซ้ำ
    ========================= */

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
                "เกิดข้อผิดพลาดในการตรวจสอบ Email";

        } else {

            $stmt->bind_param(
                "si",
                $email,
                $user_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if (
                $result->num_rows > 0
            ) {

                $error =
                    "Email นี้ถูกใช้งานแล้ว";
            }

            $stmt->close();
        }
    }


    /* =========================
       บันทึกข้อมูล
    ========================= */

    $saved = false;


    if ($error === "") {


        /* =========================
           เปลี่ยน Password
        ========================= */

        if ($password !== "") {

            if (
                strlen($password) < 4
            ) {

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
                        "เกิดข้อผิดพลาดในการเตรียมคำสั่ง";

                } else {

                    $stmt->bind_param(
                        "sssssssi",
                        $user_code,
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
                            "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                    }


                    $stmt->close();
                }
            }


        } else {


            /* =========================
               ใช้ Password เดิม
            ========================= */

            $stmt = $conn->prepare("
                UPDATE users
                SET
                    user_code = ?,
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    role = ?,
                    department = ?
                WHERE id = ?
            ");


            if (!$stmt) {

                $error =
                    "เกิดข้อผิดพลาดในการเตรียมคำสั่ง";

            } else {

                $stmt->bind_param(
                    "ssssssi",
                    $user_code,
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
                        "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                }


                $stmt->close();
            }
        }
    }


    /* =========================
       ถ้าบันทึกสำเร็จ
    ========================= */

    if (
        $error === "" &&
        $saved
    ) {


        /* =========================
           ถ้าแก้บัญชีตัวเอง
           ให้อัปเดต Session
        ========================= */

        if (
            isset($_SESSION['user_id']) &&
            (int)$_SESSION['user_id'] === $user_id
        ) {

            $_SESSION['user_code'] =
                $user_code;

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


    /* =========================
       ถ้าเกิด Error
       แสดงข้อมูลที่กรอกล่าสุด
    ========================= */

    $user['user_code'] =
        $user_code;

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

            max-width: 700px;

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
           GRID
        ========================= */

        .grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;
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

        @media (max-width: 600px) {

            .grid {

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


<!-- =========================
     HEADER
========================= -->

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


<!-- =========================
     CONTENT
========================= -->

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


        <!-- USERNAME -->

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


            <!-- USER CODE -->

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


            <!-- NAME -->

            <div class="grid">

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


            <!-- EMAIL -->

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


            <!-- ROLE -->

            <div class="form-group">

                <label>

                    Role

                    <span class="required">*</span>

                </label>

                <select
                    name="role"
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


            <!-- DEPARTMENT -->

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


            <!-- PASSWORD -->

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


            <!-- BUTTONS -->

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


</body>

</html>