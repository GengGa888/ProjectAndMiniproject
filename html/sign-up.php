<?php

session_start();
require_once 'db_connect.php';

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
   ถ้า Login อยู่แล้ว
========================================================= */

if (isset($_SESSION['user_id'])) {

    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index2.php");
    }

    exit;
}

/* =========================================================
   ตัวแปร
========================================================= */

$error = '';
$success = '';

/* =========================================================
   REGISTER
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $user_code = trim(
        $_POST['user_code'] ?? ''
    );

    $prefix = trim(
        $_POST['prefix'] ?? ''
    );

    $firstname = trim(
        $_POST['firstname'] ?? ''
    );

    $lastname = trim(
        $_POST['lastname'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $role = trim(
        $_POST['role'] ?? ''
    );

    /* สาขาที่เลือก */
    $department_select = trim(
        $_POST['department_select'] ?? ''
    );

    /* สาขาที่พิมพ์เอง */
    $other_department = trim(
        $_POST['other_department'] ?? ''
    );

    /* ถ้าเลือกอื่นๆ ให้ใช้ค่าที่พิมพ์เอง */
    if ($department_select === 'อื่นๆ') {
        $department = $other_department;
    } else {
        $department = $department_select;
    }

    $password = $_POST['password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';

    /* รหัสสมัคร Admin */
    $admin_code = trim(
        $_POST['admin_code'] ?? ''
    );

    /* ID ของรหัส Admin ที่ผ่านการตรวจสอบ */
    $admin_code_id = 0;


    /* =====================================================
       ตรวจสอบข้อมูลพื้นฐาน
    ===================================================== */

    if (
        $username === '' ||
        $user_code === '' ||
        $prefix === '' ||
        $firstname === '' ||
        $lastname === '' ||
        $email === '' ||
        $role === '' ||
        $department === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {

        $error =
            "กรุณากรอกข้อมูลให้ครบทุกช่อง";

    } elseif (
        !in_array(
            $role,
            ['student', 'teacher', 'admin'],
            true
        )
    ) {

        $error =
            "ประเภทบัญชีไม่ถูกต้อง";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "รูปแบบอีเมลไม่ถูกต้อง";

    } elseif (
        !str_ends_with(
            strtolower($email),
            '@mail.dusit.ac.th'
        ) &&
        !str_ends_with(
            strtolower($email),
            '@dusit.ac.th'
        )
    ) {

        $error =
            "กรุณาใช้อีเมลของมหาวิทยาลัยสวนดุสิต";

    } elseif (
        strlen($password) < 4
    ) {

        $error =
            "รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร";

    } elseif (
        $password !== $confirm_password
    ) {

        $error =
            "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    }


    /* =====================================================
       ตรวจสอบคำนำหน้าให้ตรงกับประเภทบัญชี
    ===================================================== */

    if ($error === '') {

        $student_prefixes = [
            'นาย',
            'นางสาว'
        ];

        $teacher_prefixes = [
            'อ.',
            'ผศ.',
            'ผศ.ดร.',
            'รศ.',
            'รศ.ดร.',
            'ศ.',
            'ศ.ดร.',
            'ดร.'
        ];

        $admin_prefixes = [
            'นาย',
            'นาง',
            'นางสาว',
            'ดร.'
        ];


        if ($role === 'student') {

            if (
                !in_array(
                    $prefix,
                    $student_prefixes,
                    true
                )
            ) {

                $error =
                    "คำนำหน้าไม่ถูกต้องสำหรับนักศึกษา";
            }

        } elseif ($role === 'teacher') {

            if (
                !in_array(
                    $prefix,
                    $teacher_prefixes,
                    true
                )
            ) {

                $error =
                    "คำนำหน้าไม่ถูกต้องสำหรับอาจารย์";
            }

        } elseif ($role === 'admin') {

            if (
                !in_array(
                    $prefix,
                    $admin_prefixes,
                    true
                )
            ) {

                $error =
                    "คำนำหน้าไม่ถูกต้องสำหรับแอดมิน";
            }
        }
    }


    /* =====================================================
       ตรวจสอบกรณีเลือก "อื่นๆ"
    ===================================================== */

    if (
        $error === '' &&
        $department_select === 'อื่นๆ' &&
        $other_department === ''
    ) {

        $error =
            "กรุณาระบุชื่อสาขา / ภาควิชา";
    }


    /* =====================================================
       ตรวจสอบรหัสสมัคร Admin
    ===================================================== */

    if (
        $error === '' &&
        $role === 'admin'
    ) {

        if ($admin_code === '') {

            $error =
                "กรุณากรอกรหัสสมัครแอดมิน";

        } else {

            $check_admin_stmt = mysqli_prepare(
                $conn,
                "
                SELECT
                    id
                FROM admin_register_codes
                WHERE
                    code = ?
                    AND used = 0
                LIMIT 1
                "
            );

            if (!$check_admin_stmt) {

                $error =
                    "ไม่สามารถตรวจสอบรหัสสมัคร Admin ได้: " .
                    mysqli_error($conn);

            } else {

                mysqli_stmt_bind_param(
                    $check_admin_stmt,
                    "s",
                    $admin_code
                );

                mysqli_stmt_execute(
                    $check_admin_stmt
                );

                $admin_result =
                    mysqli_stmt_get_result(
                        $check_admin_stmt
                    );

                if (
                    !$admin_result ||
                    mysqli_num_rows(
                        $admin_result
                    ) === 0
                ) {

                    $error =
                        "รหัสสมัครแอดมินไม่ถูกต้อง หรือรหัสนี้ถูกใช้ไปแล้ว";

                } else {

                    $admin_row =
                        mysqli_fetch_assoc(
                            $admin_result
                        );

                    $admin_code_id =
                        (int)$admin_row['id'];
                }

                mysqli_stmt_close(
                    $check_admin_stmt
                );
            }
        }
    }


    /* =====================================================
       ตรวจสอบ Username ซ้ำ
    ===================================================== */

    if ($error === '') {

        $check_sql = "
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบ Username";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $username
            );

            mysqli_stmt_execute(
                $check_stmt
            );

            mysqli_stmt_store_result(
                $check_stmt
            );

            if (
                mysqli_stmt_num_rows(
                    $check_stmt
                ) > 0
            ) {

                $error =
                    "Username นี้ถูกใช้งานแล้ว";
            }

            mysqli_stmt_close(
                $check_stmt
            );
        }
    }


    /* =====================================================
       ตรวจสอบ User Code ซ้ำ
    ===================================================== */

    if ($error === '') {

        $check_sql = "
            SELECT id
            FROM users
            WHERE user_code = ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบรหัสประจำตัว";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $user_code
            );

            mysqli_stmt_execute(
                $check_stmt
            );

            mysqli_stmt_store_result(
                $check_stmt
            );

            if (
                mysqli_stmt_num_rows(
                    $check_stmt
                ) > 0
            ) {

                $error =
                    "รหัสประจำตัวนี้ถูกใช้งานแล้ว";
            }

            mysqli_stmt_close(
                $check_stmt
            );
        }
    }


    /* =====================================================
       ตรวจสอบ Email ซ้ำ
    ===================================================== */

    if ($error === '') {

        $check_sql = "
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $error =
                "เกิดข้อผิดพลาดในการตรวจสอบอีเมล";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $email
            );

            mysqli_stmt_execute(
                $check_stmt
            );

            mysqli_stmt_store_result(
                $check_stmt
            );

            if (
                mysqli_stmt_num_rows(
                    $check_stmt
                ) > 0
            ) {

                $error =
                    "อีเมลนี้ถูกใช้งานแล้ว";
            }

            mysqli_stmt_close(
                $check_stmt
            );
        }
    }


    /* =====================================================
       INSERT USER
    ===================================================== */

    if ($error === '') {

        /*
         * ถ้าเป็น Admin
         * ใช้ Transaction เพื่อป้องกันกรณี
         * สร้าง User ไม่สำเร็จแต่รหัสถูกใช้ไปแล้ว
         */

        if ($role === 'admin') {

            mysqli_begin_transaction($conn);
        }


        $hashed_password =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $insert_sql = "
            INSERT INTO users
            (
                username,
                user_code,
                prefix,
                first_name,
                last_name,
                email,
                role,
                department,
                password
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";


        $insert_stmt = mysqli_prepare(
            $conn,
            $insert_sql
        );


        if (!$insert_stmt) {

            if ($role === 'admin') {
                mysqli_rollback($conn);
            }

            $error =
                "ไม่สามารถสร้างบัญชีได้: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $insert_stmt,
                "sssssssss",
                $username,
                $user_code,
                $prefix,
                $firstname,
                $lastname,
                $email,
                $role,
                $department,
                $hashed_password
            );


            if (
                mysqli_stmt_execute(
                    $insert_stmt
                )
            ) {

                /*
                 * ถ้าเป็น Admin
                 * ให้เปลี่ยนรหัสเป็นใช้แล้ว
                 */

                if (
                    $role === 'admin'
                ) {

                    $use_code_stmt =
                        mysqli_prepare(
                            $conn,
                            "
                            UPDATE admin_register_codes
                            SET
                                used = 1,
                                used_at = NOW()
                            WHERE
                                id = ?
                                AND used = 0
                            "
                        );


                    if (!$use_code_stmt) {

                        mysqli_rollback(
                            $conn
                        );

                        $error =
                            "ไม่สามารถยืนยันการใช้รหัส Admin ได้: " .
                            mysqli_error($conn);

                    } else {

                        mysqli_stmt_bind_param(
                            $use_code_stmt,
                            "i",
                            $admin_code_id
                        );

                        mysqli_stmt_execute(
                            $use_code_stmt
                        );


                        /*
                         * ต้องอัปเดตได้ 1 แถวเท่านั้น
                         */

                        if (
                            mysqli_stmt_affected_rows(
                                $use_code_stmt
                            ) !== 1
                        ) {

                            mysqli_rollback(
                                $conn
                            );

                            $error =
                                "รหัสสมัคร Admin นี้ถูกใช้งานไปแล้ว";
                        }

                        mysqli_stmt_close(
                            $use_code_stmt
                        );
                    }
                }


                /*
                 * ถ้าไม่มี Error
                 * Commit การสมัคร
                 */

                if ($error === '') {

                    if (
                        $role === 'admin'
                    ) {

                        mysqli_commit(
                            $conn
                        );
                    }

                    $success =
                        "สมัครสมาชิกสำเร็จ สามารถเข้าสู่ระบบได้เลย";

                    $password = '';

                    $confirm_password = '';

                    $admin_code = '';
                }

            } else {

                if (
                    $role === 'admin'
                ) {

                    mysqli_rollback(
                        $conn
                    );
                }

                $error =
                    "เกิดข้อผิดพลาดในการสมัครสมาชิก: " .
                    mysqli_stmt_error(
                        $insert_stmt
                    );
            }


            mysqli_stmt_close(
                $insert_stmt
            );
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
        สมัครสมาชิก - คลังโปรเจกต์ SDU
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

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;

            background:
                linear-gradient(
                    135deg,
                    #eaf7fd,
                    #ffffff,
                    #dff2fc
                );

            font-family:
                'Segoe UI',
                Tahoma,
                Arial,
                sans-serif;
        }


        .register-container {

            width: 100%;

            max-width: 520px;

            background: white;

            border-radius: 20px;

            padding: 35px;

            box-shadow:
                0 10px 35px
                rgba(48, 105, 139, 0.15);

            border:
                1px solid #e1edf4;
        }


        .logo {

            display: block;

            width: 75px;

            height: 75px;

            object-fit: contain;

            margin:
                0 auto 15px;

            background: white;

            border-radius: 50%;

            padding: 4px;

            box-shadow:
                0 4px 12px
                rgba(0,0,0,0.12);
        }


        h2 {

            text-align: center;

            color: #245c7d;

            font-weight: 700;

            margin-bottom: 6px;
        }


        .subtitle {

            text-align: center;

            color: #7a8d98;

            font-size: 14px;

            margin-bottom: 25px;
        }


        .form-group {

            margin-bottom: 17px;
        }


        label {

            display: block;

            margin-bottom: 7px;

            color: #315d73;

            font-size: 14px;

            font-weight: 600;
        }


        .input-wrapper {

            position: relative;
        }


        .input-wrapper i {

            position: absolute;

            left: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #4297CD;

            font-size: 17px;

            z-index: 2;
        }


        .input-wrapper input,
        .input-wrapper select {

            width: 100%;

            height: 46px;

            padding:
                0 14px 0 42px;

            border:
                1px solid #d5e3eb;

            border-radius: 10px;

            outline: none;

            font-size: 14px;

            background: white;

            color: #263238;
        }


        .input-wrapper select {

            cursor: pointer;

            appearance: auto;
        }


        .input-wrapper input:focus,
        .input-wrapper select:focus {

            border-color: #4297CD;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,0.12);
        }


        .role-box {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 10px;
        }


        .role-option {

            position: relative;
        }


        .role-option input {

            position: absolute;

            opacity: 0;
        }


        .role-option label {

            margin: 0;

            padding: 13px 8px;

            text-align: center;

            border:
                1px solid #d5e3eb;

            border-radius: 10px;

            cursor: pointer;

            color: #527184;

            background: #fff;

            font-size: 13px;

            transition: 0.2s;
        }


        .role-option label i {

            display: block;

            font-size: 21px;

            margin-bottom: 5px;

            color: #4297CD;
        }


        .role-option input:checked + label {

            border-color: #4297CD;

            background: #eef8fd;

            color: #245c7d;

            box-shadow:
                0 0 0 2px
                rgba(66,151,205,0.10);
        }


        /* =================================================
           ADMIN CODE
        ================================================= */

        .admin-code-box {

            display: none;

            background: #fff8e8;

            border:
                1px solid #f0d993;

            border-radius: 10px;

            padding: 13px;

            margin-top: 12px;
        }


        .admin-code-box.show {

            display: block;
        }


        .admin-code-title {

            color: #80651e;

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 7px;
        }


        .admin-code-box input {

            width: 100%;

            height: 42px;

            border:
                1px solid #dfcb8a;

            border-radius: 8px;

            padding: 0 12px;

            outline: none;

            font-size: 14px;
        }


        .admin-code-box input:focus {

            border-color: #d0ae3d;

            box-shadow:
                0 0 0 3px
                rgba(208,174,61,0.12);
        }


        /* =================================================
           OTHER DEPARTMENT
        ================================================= */

        .other-department-box {

            display: none;

            margin-top: 10px;
        }


        .other-department-box.show {

            display: block;
        }


        /* =================================================
           ALERT
        ================================================= */

        .alert {

            border-radius: 10px;

            font-size: 14px;
        }


        /* =================================================
           REGISTER BUTTON
        ================================================= */

        .register-button {

            width: 100%;

            border: none;

            height: 48px;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #58b4df,
                    #358abd
                );

            color: white;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;

            margin-top: 8px;
        }


        .register-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 5px 15px
                rgba(53,138,189,0.25);
        }


        /* =================================================
           LOGIN
        ================================================= */

        .login-link {

            text-align: center;

            margin-top: 20px;

            color: #7a8d98;

            font-size: 14px;
        }


        .login-link a {

            color: #287cab;

            font-weight: 700;

            text-decoration: none;
        }


        .login-link a:hover {

            text-decoration: underline;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 500px) {

            .register-container {

                padding:
                    25px 20px;
            }


            .role-box {

                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="register-container">


    <!-- =================================================
         LOGO
    ================================================== -->

    <img
        src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
        class="logo"
        alt="SDU Logo"
    >


    <h2>
        สมัครสมาชิก
    </h2>


    <div class="subtitle">

        คลังโปรเจกต์ มหาวิทยาลัยสวนดุสิต

    </div>


    <!-- =================================================
         ERROR
    ================================================== -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-circle"></i>

            <?php echo e($error); ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         SUCCESS
    ================================================== -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            <?php echo e($success); ?>

            <br>

            <a href="login.php">
                ไปหน้าเข้าสู่ระบบ
            </a>

        </div>

    <?php endif; ?>


    <!-- =================================================
         FORM
    ================================================== -->

    <form
        method="POST"
        action=""
    >


        <!-- USERNAME -->

        <div class="form-group">

            <label>
                Username
            </label>

            <div class="input-wrapper">

                <input
                    type="text"
                    name="username"
                    placeholder="กรอก Username"
                    value="<?php echo e(
                        $_POST['username'] ?? ''
                    ); ?>"
                    maxlength="50"
                    required
                >

                <i class="bi bi-person"></i>

            </div>

        </div>


        <!-- USER CODE -->

        <div class="form-group">

            <label>
                รหัสนักศึกษา / รหัสประจำตัว
            </label>

            <div class="input-wrapper">

                <input
                    type="text"
                    name="user_code"
                    placeholder="กรอกรหัสประจำตัว"
                    value="<?php echo e(
                        $_POST['user_code'] ?? ''
                    ); ?>"
                    maxlength="50"
                    required
                >

                <i class="bi bi-person-vcard"></i>

            </div>

        </div>


        <!-- ROLE -->

        <div class="form-group">

            <label>
                ประเภทบัญชี
            </label>


            <div class="role-box">


                <!-- STUDENT -->

                <div class="role-option">

                    <input
                        type="radio"
                        id="student"
                        name="role"
                        value="student"

                        <?php
                        echo (
                            ($_POST['role'] ?? '') === 'student'
                        )
                            ? 'checked'
                            : '';
                        ?>

                        required
                    >

                    <label for="student">

                        <i class="bi bi-mortarboard-fill"></i>

                        นักศึกษา

                    </label>

                </div>


                <!-- TEACHER -->

                <div class="role-option">

                    <input
                        type="radio"
                        id="teacher"
                        name="role"
                        value="teacher"

                        <?php
                        echo (
                            ($_POST['role'] ?? '') === 'teacher'
                        )
                            ? 'checked'
                            : '';
                        ?>
                    >

                    <label for="teacher">

                        <i class="bi bi-person-workspace"></i>

                        อาจารย์

                    </label>

                </div>


                <!-- ADMIN -->

                <div class="role-option">

                    <input
                        type="radio"
                        id="admin"
                        name="role"
                        value="admin"

                        <?php
                        echo (
                            ($_POST['role'] ?? '') === 'admin'
                        )
                            ? 'checked'
                            : '';
                        ?>
                    >

                    <label for="admin">

                        <i class="bi bi-shield-lock-fill"></i>

                        แอดมิน

                    </label>

                </div>

            </div>


            <!-- ADMIN CODE -->

            <div
                id="adminCodeBox"
                class="admin-code-box"
            >

                <div class="admin-code-title">

                    <i class="bi bi-shield-lock-fill"></i>

                    รหัสสมัครแอดมิน

                </div>

                <input
                    type="password"
                    name="admin_code"
                    id="admin_code"
                    placeholder="กรอกรหัสสมัครแอดมิน"
                    value="<?php echo e(
                        $_POST['admin_code'] ?? ''
                    ); ?>"
                >

            </div>

        </div>


        <!-- PREFIX -->

        <div class="form-group">

            <label>
                คำนำหน้า
            </label>

            <div class="input-wrapper">

                <select
                    name="prefix"
                    id="prefix"
                    required
                >

                    <option value="">
                        -- เลือกคำนำหน้า --
                    </option>

                </select>

                <i class="bi bi-person-badge"></i>

            </div>

        </div>


        <!-- NAME -->

        <div class="row">

            <div class="col-md-6">

                <div class="form-group">

                    <label>
                        ชื่อ
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            name="firstname"
                            placeholder="ชื่อ"
                            value="<?php echo e(
                                $_POST['firstname'] ?? ''
                            ); ?>"
                            maxlength="50"
                            required
                        >

                        <i class="bi bi-person"></i>

                    </div>

                </div>

            </div>


            <div class="col-md-6">

                <div class="form-group">

                    <label>
                        นามสกุล
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            name="lastname"
                            placeholder="นามสกุล"
                            value="<?php echo e(
                                $_POST['lastname'] ?? ''
                            ); ?>"
                            maxlength="50"
                            required
                        >

                        <i class="bi bi-person"></i>

                    </div>

                </div>

            </div>

        </div>


        <!-- EMAIL -->

        <div class="form-group">

            <label>
                อีเมล
            </label>

            <div class="input-wrapper">

                <input
                    type="email"
                    name="email"
                    placeholder="example@mail.dusit.ac.th"
                    value="<?php echo e(
                        $_POST['email'] ?? ''
                    ); ?>"
                    maxlength="100"
                    required
                >

                <i class="bi bi-envelope"></i>

            </div>

        </div>


        <!-- DEPARTMENT -->

        <div class="form-group">

            <label>
                สาขา / ภาควิชา
            </label>

            <div class="input-wrapper">

                <select
                    name="department_select"
                    id="department_select"
                    required
                >

                    <option value="">
                        -- เลือกสาขา / ภาควิชา --
                    </option>


                    <option
                        value="เทคโนโลยีสารสนเทศ"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'เทคโนโลยีสารสนเทศ'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        เทคโนโลยีสารสนเทศ

                    </option>


                    <option
                        value="วิทยาการคอมพิวเตอร์"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'วิทยาการคอมพิวเตอร์'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        วิทยาการคอมพิวเตอร์

                    </option>


                    <option
                        value="เทคโนโลยีดิจิทัล"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'เทคโนโลยีดิจิทัล'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        เทคโนโลยีดิจิทัล

                    </option>


                    <option
                        value="คอมพิวเตอร์ธุรกิจ"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'คอมพิวเตอร์ธุรกิจ'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        คอมพิวเตอร์ธุรกิจ

                    </option>


                    <option
                        value="มัลติมีเดีย"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'มัลติมีเดีย'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        มัลติมีเดีย

                    </option>


                    <option
                        value="วิทยาศาสตร์สิ่งแวดล้อม"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'วิทยาศาสตร์สิ่งแวดล้อม'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        วิทยาศาสตร์สิ่งแวดล้อม

                    </option>


                    <option
                        value="เทคโนโลยีการประกอบอาหาร"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'เทคโนโลยีการประกอบอาหาร'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        เทคโนโลยีการประกอบอาหาร

                    </option>


                    <option
                        value="อื่นๆ"

                        <?php
                        echo (
                            ($_POST['department_select'] ?? '') ===
                            'อื่นๆ'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        อื่นๆ

                    </option>

                </select>

                <i class="bi bi-building"></i>

            </div>


            <!-- OTHER DEPARTMENT -->

            <div
                id="otherDepartmentBox"
                class="other-department-box"
            >

                <div class="input-wrapper">

                    <input
                        type="text"
                        name="other_department"
                        id="other_department"
                        placeholder="พิมพ์ชื่อสาขา / ภาควิชา"
                        maxlength="255"
                        value="<?php echo e(
                            $_POST['other_department'] ?? ''
                        ); ?>"
                    >

                    <i class="bi bi-pencil"></i>

                </div>

            </div>

        </div>


        <!-- PASSWORD -->

        <div class="form-group">

            <label>
                รหัสผ่าน
            </label>

            <div class="input-wrapper">

                <input
                    type="password"
                    name="password"
                    placeholder="กรอกรหัสผ่าน"
                    required
                >

                <i class="bi bi-lock"></i>

            </div>

        </div>


        <!-- CONFIRM PASSWORD -->

        <div class="form-group">

            <label>
                ยืนยันรหัสผ่าน
            </label>

            <div class="input-wrapper">

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="กรอกรหัสผ่านอีกครั้ง"
                    required
                >

                <i class="bi bi-lock-fill"></i>

            </div>

        </div>


        <!-- REGISTER BUTTON -->

        <button
            type="submit"
            class="register-button"
        >

            <i class="bi bi-person-plus-fill"></i>

            สมัครสมาชิก

        </button>

    </form>


    <!-- LOGIN -->

    <div class="login-link">

        มีบัญชีอยู่แล้ว?

        <a href="login.php">
            เข้าสู่ระบบ
        </a>

    </div>

</div>


<script>

/* =====================================================
   PREFIX
===================================================== */

const studentRadio =
    document.getElementById('student');

const teacherRadio =
    document.getElementById('teacher');

const adminRadio =
    document.getElementById('admin');

const prefixSelect =
    document.getElementById('prefix');

const oldPrefix =
    <?php
    echo json_encode(
        $_POST['prefix'] ?? '',
        JSON_UNESCAPED_UNICODE
    );
    ?>;


/* =====================================================
   ตัวเลือกคำนำหน้า
===================================================== */

const prefixOptions = {

    student: [

        {
            value: 'นาย',
            text: 'นาย'
        },

        {
            value: 'นางสาว',
            text: 'นางสาว'
        }

    ],

    teacher: [

        {
            value: 'อ.',
            text: 'อาจารย์'
        },

        {
            value: 'ผศ.',
            text: 'ผู้ช่วยศาสตราจารย์'
        },

        {
            value: 'ผศ.ดร.',
            text: 'ผู้ช่วยศาสตราจารย์ ดร.'
        },

        {
            value: 'รศ.',
            text: 'รองศาสตราจารย์'
        },

        {
            value: 'รศ.ดร.',
            text: 'รองศาสตราจารย์ ดร.'
        },

        {
            value: 'ศ.',
            text: 'ศาสตราจารย์'
        },

        {
            value: 'ศ.ดร.',
            text: 'ศาสตราจารย์ ดร.'
        },

        {
            value: 'ดร.',
            text: 'ดอกเตอร์'
        }

    ],

    admin: [

        {
            value: 'นาย',
            text: 'นาย'
        },

        {
            value: 'นาง',
            text: 'นาง'
        },

        {
            value: 'นางสาว',
            text: 'นางสาว'
        },

        {
            value: 'ดร.',
            text: 'ดอกเตอร์'
        }

    ]

};


/* =====================================================
   เปลี่ยนคำนำหน้าตาม Role
===================================================== */

function updatePrefixOptions(
    keepOld = false
) {

    let role = '';

    if (studentRadio.checked) {

        role = 'student';

    } else if (teacherRadio.checked) {

        role = 'teacher';

    } else if (adminRadio.checked) {

        role = 'admin';
    }


    prefixSelect.innerHTML = '';


    const firstOption =
        document.createElement('option');

    firstOption.value = '';

    firstOption.textContent =
        '-- เลือกคำนำหน้า --';

    prefixSelect.appendChild(
        firstOption
    );


    if (role !== '') {

        prefixOptions[role].forEach(
            function(option) {

                const optionElement =
                    document.createElement('option');

                optionElement.value =
                    option.value;

                optionElement.textContent =
                    option.text;


                if (
                    keepOld &&
                    oldPrefix === option.value
                ) {

                    optionElement.selected =
                        true;
                }


                prefixSelect.appendChild(
                    optionElement
                );

            }
        );
    }
}


/* =====================================================
   ADMIN CODE
===================================================== */

const adminCodeBox =
    document.getElementById(
        'adminCodeBox'
    );

const adminCodeInput =
    document.getElementById(
        'admin_code'
    );


function checkAdmin()
{

    if (adminRadio.checked) {

        adminCodeBox.classList.add(
            'show'
        );

        adminCodeInput.required =
            true;

    } else {

        adminCodeBox.classList.remove(
            'show'
        );

        adminCodeInput.required =
            false;
    }
}


/* =====================================================
   ROLE CHANGE
===================================================== */

studentRadio.addEventListener(
    'change',
    function() {

        checkAdmin();

        updatePrefixOptions(false);

    }
);


teacherRadio.addEventListener(
    'change',
    function() {

        checkAdmin();

        updatePrefixOptions(false);

    }
);


adminRadio.addEventListener(
    'change',
    function() {

        checkAdmin();

        updatePrefixOptions(false);

    }
);


/* =====================================================
   INITIAL
===================================================== */

checkAdmin();

updatePrefixOptions(true);


/* =====================================================
   OTHER DEPARTMENT
===================================================== */

const departmentSelect =
    document.getElementById(
        'department_select'
    );

const otherDepartmentBox =
    document.getElementById(
        'otherDepartmentBox'
    );

const otherDepartmentInput =
    document.getElementById(
        'other_department'
    );


function checkDepartment()
{

    if (
        departmentSelect.value === 'อื่นๆ'
    ) {

        otherDepartmentBox.classList.add(
            'show'
        );

        otherDepartmentInput.required =
            true;

    } else {

        otherDepartmentBox.classList.remove(
            'show'
        );

        otherDepartmentInput.required =
            false;
    }
}


departmentSelect.addEventListener(
    'change',
    checkDepartment
);


checkDepartment();

</script>

</body>

</html>