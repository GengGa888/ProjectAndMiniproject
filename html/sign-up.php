<?php

session_start();
require_once "db_connect.php";


/* =====================================================
   สมัครสมาชิก
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $user_code = trim($_POST['user_code'] ?? '');

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $department = trim($_POST['department'] ?? '');

    $role = $_POST['role'] ?? 'student';

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /* =================================================
       ตรวจสอบข้อมูล
    ================================================= */

    if (
        $username === '' ||
        $user_code === '' ||
        $firstname === '' ||
        $lastname === '' ||
        $email === '' ||
        $department === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {

        echo "<script>
            alert('กรุณากรอกข้อมูลให้ครบทุกช่อง');
            window.history.back();
        </script>";

        exit;
    }


    /* =================================================
       ตรวจสอบ Role
    ================================================= */

    if (
        !in_array(
            $role,
            ['student', 'teacher'],
            true
        )
    ) {

        echo "<script>
            alert('ประเภทผู้ใช้งานไม่ถูกต้อง');
            window.history.back();
        </script>";

        exit;
    }


    /* =================================================
       ตรวจสอบ Email
    ================================================= */

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "<script>
            alert('รูปแบบ Email ไม่ถูกต้อง');
            window.history.back();
        </script>";

        exit;
    }


    /* =================================================
       ตรวจสอบ Gmail มหาวิทยาลัย
    ================================================= */

    $email_domain = strtolower(
        substr(
            strrchr($email, "@"),
            1
        )
    );

    if (
        $email_domain !== 'mail.dusit.ac.th' &&
        $email_domain !== 'dusit.ac.th'
    ) {

        echo "<script>
            alert('กรุณาใช้อีเมลมหาวิทยาลัย เช่น example@mail.dusit.ac.th');
            window.history.back();
        </script>";

        exit;
    }


    /* =================================================
       ตรวจสอบ Password
    ================================================= */

    if ($password !== $confirm_password) {

        echo "<script>
            alert('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
            window.history.back();
        </script>";

        exit;
    }


    if (strlen($password) < 4) {

        echo "<script>
            alert('รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร');
            window.history.back();
        </script>";

        exit;
    }


    /* =================================================
       ตรวจสอบ Username / Email / User Code ซ้ำ
    ================================================= */

    $stmt_check = mysqli_prepare(
        $conn,
        "
        SELECT id
        FROM users
        WHERE username = ?
           OR email = ?
           OR user_code = ?
        LIMIT 1
        "
    );


    if (!$stmt_check) {

        die(
            "SQL Error: " .
            htmlspecialchars(
                mysqli_error($conn)
            )
        );
    }


    mysqli_stmt_bind_param(
        $stmt_check,
        "sss",
        $username,
        $email,
        $user_code
    );


    mysqli_stmt_execute(
        $stmt_check
    );


    mysqli_stmt_store_result(
        $stmt_check
    );


    if (
        mysqli_stmt_num_rows(
            $stmt_check
        ) > 0
    ) {

        mysqli_stmt_close(
            $stmt_check
        );

        echo "<script>
            alert('Username, Email หรือรหัสประจำตัวนี้ถูกใช้งานแล้ว');
            window.history.back();
        </script>";

        exit;
    }


    mysqli_stmt_close(
        $stmt_check
    );


    /* =================================================
       เข้ารหัส Password
    ================================================= */

    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /* =================================================
       เพิ่มผู้ใช้
    ================================================= */

    $sql = "
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
    ";


    $stmt_insert = mysqli_prepare(
        $conn,
        $sql
    );


    if (!$stmt_insert) {

        die(
            "SQL Error (Insert): " .
            htmlspecialchars(
                mysqli_error($conn)
            )
        );
    }


    mysqli_stmt_bind_param(
        $stmt_insert,
        "ssssssss",
        $username,
        $user_code,
        $firstname,
        $lastname,
        $email,
        $role,
        $department,
        $hashed_password
    );


    if (
        mysqli_stmt_execute(
            $stmt_insert
        )
    ) {

        mysqli_stmt_close(
            $stmt_insert
        );

        mysqli_close(
            $conn
        );


        echo "<script>
            alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ');
            window.location.href='login.php';
        </script>";

        exit;

    } else {

        $error =
            mysqli_stmt_error(
                $stmt_insert
            );

        mysqli_stmt_close(
            $stmt_insert
        );

        mysqli_close(
            $conn
        );


        echo "<script>
            alert('เกิดข้อผิดพลาด: " .
            htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) .
            "');
            window.history.back();
        </script>";

        exit;
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

    <title>สมัครสมาชิก - ระบบสืบค้นโปรเจกต์</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family:
                'Sarabun',
                'Segoe UI',
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }


        body {

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            background:
                linear-gradient(
                    135deg,
                    #29b6f6 0%,
                    #b3e5fc 50%,
                    #e1f5fe 100%
                );

            padding: 40px 0;
        }


        .register-wrapper {

            position: relative;

            width: 100%;

            max-width: 480px;

            padding: 0 20px;

            margin-top: 40px;
        }


        .logo-container {

            position: absolute;

            top: -55px;

            left: 50%;

            transform: translateX(-50%);

            z-index: 2;
        }


        .logo-container img {

            width: 110px;

            height: 110px;

            object-fit: contain;

            filter:
                drop-shadow(
                    0px 4px 6px
                    rgba(0, 0, 0, 0.15)
                );
        }


        .register-card {

            background: #ffffff;

            border-radius: 6px;

            padding: 70px 35px 35px 35px;

            box-shadow:
                0 10px 25px
                rgba(0, 0, 0, 0.15);
        }


        .register-card h2 {

            font-size: 1.25rem;

            color: #2b2b2b;

            font-weight: 600;

            margin-bottom: 20px;

            text-align: center;
        }


        .form-group {

            margin-bottom: 15px;

            text-align: left;
        }


        .form-group label {

            display: block;

            font-size: 0.85rem;

            color: #333333;

            margin-bottom: 5px;

            font-weight: 500;
        }


        .input-wrapper {

            position: relative;
        }


        .input-wrapper input,
        .input-wrapper select {

            width: 100%;

            padding: 9px 35px 9px 12px;

            font-size: 0.9rem;

            border: 1px solid #bce0fd;

            background-color: #f0f7ff;

            border-radius: 5px;

            outline: none;

            transition: all 0.2s ease;

            color: #333;

            appearance: none;
        }


        .input-wrapper input:focus,
        .input-wrapper select:focus {

            border-color: #29b6f6;

            box-shadow:
                0 0 5px
                rgba(41, 182, 246, 0.5);
        }


        .input-wrapper i {

            position: absolute;

            right: 12px;

            top: 50%;

            transform: translateY(-50%);

            color: #888888;

            font-size: 0.85rem;

            pointer-events: none;
        }


        .btn-submit {

            width: 100%;

            padding: 11px;

            background:
                linear-gradient(
                    to bottom,
                    #34b3c7,
                    #258ca3
                );

            border: none;

            border-radius: 5px;

            color: #ffffff;

            font-size: 0.95rem;

            font-weight: bold;

            cursor: pointer;

            margin-top: 15px;

            transition: opacity 0.2s ease;
        }


        .btn-submit:hover {

            opacity: 0.9;
        }


        .footer-links {

            margin-top: 20px;

            text-align: center;

            font-size: 0.85rem;
        }


        .footer-links a {

            color: #0088cc;

            text-decoration: none;

            font-weight: 500;
        }


        .footer-links a:hover {

            text-decoration: underline;
        }


        @media (max-width: 500px) {

            .register-card {

                padding:
                    65px
                    25px
                    30px
                    25px;
            }

        }

    </style>

</head>


<body>

<div class="register-wrapper">

    <div class="logo-container">

        <img
            src="https://academic.dusit.ac.th/academic/edu/util/img/login/sdu-newlogo.png"
            alt="ตราสัญลักษณ์"
        >

    </div>


    <div class="register-card">

        <h2>ลงทะเบียนใช้งานระบบ</h2>


        <form
            action="sign-up.php"
            method="POST"
            id="registerForm"
        >


            <!-- ประเภทผู้ใช้งาน -->

            <div class="form-group">

                <label for="role">
                    ประเภทผู้ใช้งาน
                </label>

                <div class="input-wrapper">

                    <select
                        id="role"
                        name="role"
                        required
                    >

                        <option
                            value="student"
                            selected
                        >
                            นักศึกษา (Student)
                        </option>

                        <option value="teacher">
                            อาจารย์ / ที่ปรึกษา (Teacher)
                        </option>

                    </select>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>


            <!-- Username -->

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <div class="input-wrapper">

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="ชื่อผู้ใช้งาน"
                        maxlength="50"
                        required
                    >

                    <i class="fa-solid fa-user"></i>

                </div>

            </div>


            <!-- ชื่อ -->

            <div class="form-group">

                <label for="firstname">
                    ชื่อ
                </label>

                <div class="input-wrapper">

                    <input
                        type="text"
                        id="firstname"
                        name="firstname"
                        placeholder="กรอกชื่อ"
                        maxlength="50"
                        required
                    >

                    <i class="fa-solid fa-id-card"></i>

                </div>

            </div>


            <!-- นามสกุล -->

            <div class="form-group">

                <label for="lastname">
                    นามสกุล
                </label>

                <div class="input-wrapper">

                    <input
                        type="text"
                        id="lastname"
                        name="lastname"
                        placeholder="กรอกนามสกุล"
                        maxlength="50"
                        required
                    >

                    <i class="fa-solid fa-id-card"></i>

                </div>

            </div>


            <!-- Email -->

            <div class="form-group">

                <label for="email">
                    อีเมลมหาวิทยาลัย
                </label>

                <div class="input-wrapper">

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="example@mail.dusit.ac.th"
                        maxlength="100"
                        required
                    >

                    <i class="fa-solid fa-envelope"></i>

                </div>

            </div>


            <!-- Department -->

            <div class="form-group">

                <label for="department">
                    สาขาวิชา / คณะ
                </label>

                <div class="input-wrapper">

                    <select
                        id="department"
                        name="department"
                        required
                    >

                        <option
                            value=""
                            disabled
                            selected
                        >
                            -- เลือกสาขาวิชา --
                        </option>

                        <option value="เทคโนโลยีสารสนเทศ">
                            เทคโนโลยีสารสนเทศ
                        </option>

                        <option value="วิทยาการคอมพิวเตอร์">
                            วิทยาการคอมพิวเตอร์
                        </option>

                        <option value="วิศวกรรมซอฟต์แวร์">
                            วิศวกรรมซอฟต์แวร์
                        </option>

                        <option value="สาขาอื่นๆ">
                            สาขาอื่นๆ
                        </option>

                    </select>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>


            <!-- Password -->

            <div class="form-group">

                <label for="password">
                    รหัสผ่าน
                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="กำหนดรหัสผ่าน"
                        required
                    >

                    <i class="fa-solid fa-lock"></i>

                </div>

            </div>


            <!-- Confirm Password -->

            <div class="form-group">

                <label for="confirm_password">
                    ยืนยันรหัสผ่าน
                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="กรอกรหัสผ่านอีกครั้ง"
                        required
                    >

                    <i class="fa-solid fa-shield-halved"></i>

                </div>

            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="btn-submit"
            >
                ยืนยันการสมัครสมาชิก
            </button>

        </form>


        <div class="footer-links">

            มีบัญชีผู้ใช้งานอยู่แล้ว?

            <a href="login.php">
                เข้าสู่ระบบที่นี่
            </a>

        </div>

    </div>

</div>

</body>

</html>