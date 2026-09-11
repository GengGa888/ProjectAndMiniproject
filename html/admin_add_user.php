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


$error = "";


/* =========================
   เพิ่มผู้ใช้
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username   = trim($_POST["username"] ?? "");
    $user_code  = trim($_POST["user_code"] ?? "");

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");

    $email      = trim($_POST["email"] ?? "");

    $role       = trim($_POST["role"] ?? "student");

    $department = trim($_POST["department"] ?? "");

    $password   = $_POST["password"] ?? "";


    /* =========================
       ตรวจสอบข้อมูล
    ========================= */

    if (
        $username === "" ||
        $user_code === "" ||
        $first_name === "" ||
        $last_name === "" ||
        $email === "" ||
        $password === ""
    ) {

        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";

    } elseif (
        !in_array(
            $role,
            ["student", "teacher", "admin"],
            true
        )
    ) {

        $error = "Role ไม่ถูกต้อง";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = "รูปแบบ Email ไม่ถูกต้อง";

    } elseif (
        strlen($password) < 4
    ) {

        $error = "Password ต้องมีอย่างน้อย 4 ตัวอักษร";

    } else {


        /* =========================
           ตรวจสอบ Username ซ้ำ
        ========================= */

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบ Username";

        } else {

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if (
                $result->num_rows > 0
            ) {

                $error =
                    "Username นี้มีอยู่แล้ว";
            }

            $stmt->close();
        }


        /* =========================
           ตรวจสอบ User Code ซ้ำ
        ========================= */

        if ($error === "") {

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE user_code = ?
                LIMIT 1
            ");

            if (!$stmt) {

                $error =
                    "เกิดข้อผิดพลาดในการตรวจสอบรหัสประจำตัว";

            } else {

                $stmt->bind_param(
                    "s",
                    $user_code
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                if (
                    $result->num_rows > 0
                ) {

                    $error =
                        "รหัสประจำตัวนี้มีอยู่แล้ว";
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
                LIMIT 1
            ");

            if (!$stmt) {

                $error =
                    "เกิดข้อผิดพลาดในการตรวจสอบ Email";

            } else {

                $stmt->bind_param(
                    "s",
                    $email
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                if (
                    $result->num_rows > 0
                ) {

                    $error =
                        "Email นี้มีอยู่แล้ว";
                }

                $stmt->close();
            }
        }


        /* =========================
           เพิ่มข้อมูลผู้ใช้
        ========================= */

        if ($error === "") {


            /* เข้ารหัส Password */

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    username,
                    user_code,
                    first_name,
                    last_name,
                    email,
                    role,
                    department,
                    password
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");


            if (!$stmt) {

                $error =
                    "ไม่สามารถเตรียมคำสั่งเพิ่มผู้ใช้ได้";

            } else {


                $stmt->bind_param(
                    "ssssssss",
                    $username,
                    $user_code,
                    $first_name,
                    $last_name,
                    $email,
                    $role,
                    $department,
                    $hashed_password
                );


                if ($stmt->execute()) {

                    $stmt->close();

                    echo "<script>
                        alert('เพิ่มผู้ใช้สำเร็จ');
                        window.location.href='admin.php';
                    </script>";

                    exit;

                } else {

                    $error =
                        "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้";

                    $stmt->close();
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

    <title>เพิ่มผู้ใช้ - Admin</title>


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

            justify-content: space-between;

            align-items: center;

            box-shadow:
                0 2px 8px
                rgba(0,0,0,.08);
        }


        .header h2 {

            margin: 0;

            font-size: 24px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .header a {

            color: white;

            text-decoration: none;

            background:
                rgba(255,255,255,.18);

            padding:
                10px 15px;

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

                padding: 15px 20px;

                gap: 10px;
            }


            .header h2 {

                font-size: 20px;
            }


            .header a {

                padding: 8px 10px;

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

        <i class="bi bi-person-plus-fill"></i>

        เพิ่มผู้ใช้งาน

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

            <i class="bi bi-person-plus-fill"></i>

            สร้างบัญชีผู้ใช้งาน

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


        <form method="POST">


            <!-- USERNAME -->

            <div class="form-group">

                <label>

                    Username

                    <span class="required">*</span>

                </label>

                <input
                    type="text"
                    name="username"
                    placeholder="เช่น student01"
                    value="<?= htmlspecialchars(
                        $_POST['username'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- USER CODE -->

            <div class="form-group">

                <label>

                    รหัสนักศึกษา / รหัสบุคลากร

                    <span class="required">*</span>

                </label>

                <input
                    type="text"
                    name="user_code"
                    placeholder="เช่น 6600000000"
                    value="<?= htmlspecialchars(
                        $_POST['user_code'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
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
                        placeholder="ชื่อ"
                        value="<?= htmlspecialchars(
                            $_POST['first_name'] ?? '',
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
                        placeholder="นามสกุล"
                        value="<?= htmlspecialchars(
                            $_POST['last_name'] ?? '',
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
                    placeholder="example@email.com"
                    value="<?= htmlspecialchars(
                        $_POST['email'] ?? '',
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

                <select name="role" required>

                    <option
                        value="student"
                        <?= (
                            ($_POST['role'] ?? 'student')
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
                            ($_POST['role'] ?? '')
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
                            ($_POST['role'] ?? '')
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
                    placeholder="เช่น เทคโนโลยีสารสนเทศ"
                    value="<?= htmlspecialchars(
                        $_POST['department'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label>

                    Password

                    <span class="required">*</span>

                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="กำหนดรหัสผ่าน"
                    minlength="4"
                    required
                >

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

                    เพิ่มผู้ใช้

                </button>

            </div>


        </form>

    </div>

</div>


</body>

</html>