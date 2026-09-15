<?php

session_start();
require_once 'db_connect.php';

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   VARIABLES
========================================================= */

$error = '';
$success = '';


/* =========================================================
   RESET PASSWORD
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $user_code = trim($_POST['user_code'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /* =====================================================
       CHECK EMPTY
    ===================================================== */

    if (
        $username === '' ||
        $user_code === '' ||
        $email === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";

    }

    /* =====================================================
       CHECK EMAIL
    ===================================================== */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "รูปแบบอีเมลไม่ถูกต้อง";

    }

    /* =====================================================
       CHECK PASSWORD
    ===================================================== */

    elseif (strlen($new_password) < 4) {

        $error = "รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร";

    }

    elseif ($new_password !== $confirm_password) {

        $error = "รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน";

    }


    /* =====================================================
       FIND USER
    ===================================================== */

    if ($error === '') {

        $sql = "
            SELECT id
            FROM users
            WHERE username = ?
              AND user_code = ?
              AND email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $error = "เกิดข้อผิดพลาดในการตรวจสอบข้อมูล";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $user_code,
                $email
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            /* =================================================
               USER NOT FOUND
            ================================================= */

            if (!$user) {

                $error =
                    "ไม่พบข้อมูลบัญชี กรุณาตรวจสอบ Username, รหัสประจำตัว และอีเมล";

            } else {

                $user_id = intval($user['id']);


                /* =============================================
                   HASH PASSWORD
                ============================================= */

                $hashed_password = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


                /* =============================================
                   UPDATE PASSWORD
                ============================================= */

                $update_sql = "
                    UPDATE users
                    SET password = ?
                    WHERE id = ?
                    LIMIT 1
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );


                if (!$update_stmt) {

                    $error =
                        "ไม่สามารถเปลี่ยนรหัสผ่านได้";

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "si",
                        $hashed_password,
                        $user_id
                    );


                    if (mysqli_stmt_execute($update_stmt)) {

                        $success =
                            "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว สามารถเข้าสู่ระบบได้เลย";

                        /* ล้างข้อมูล */
                        $username = '';
                        $user_code = '';
                        $email = '';
                        $new_password = '';
                        $confirm_password = '';

                    } else {

                        $error =
                            "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
                    }


                    mysqli_stmt_close($update_stmt);
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

    <title>รีเซ็ตรหัสผ่าน - คลังโปรเจกต์ SDU</title>


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


        .reset-container {

            width: 100%;

            max-width: 480px;

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

            transform: translateY(-50%);

            color: #4297CD;

            font-size: 17px;

            z-index: 2;
        }


        .input-wrapper input {

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


        .input-wrapper input:focus {

            border-color: #4297CD;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,0.12);
        }


        .info-box {

            background: #eef8fd;

            border:
                1px solid #cfe8f5;

            border-radius: 10px;

            padding: 12px 15px;

            margin-bottom: 20px;

            color: #527184;

            font-size: 13px;

            line-height: 1.6;
        }


        .alert {

            border-radius: 10px;

            font-size: 14px;
        }


        .reset-button {

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


        .reset-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 5px 15px
                rgba(53,138,189,0.25);
        }


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


        @media (max-width: 500px) {

            .reset-container {

                padding: 25px 20px;
            }
        }

    </style>

</head>


<body>


<div class="reset-container">


    <!-- LOGO -->

    <img
        src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
        class="logo"
        alt="SDU Logo"
    >


    <h2>

        <i class="bi bi-key-fill"></i>

        รีเซ็ตรหัสผ่าน

    </h2>


    <div class="subtitle">

        คลังโปรเจกต์ มหาวิทยาลัยสวนดุสิต

    </div>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-circle"></i>

            <?php echo e($error); ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            <?php echo e($success); ?>

            <br><br>

            <a href="login.php">

                ไปหน้าเข้าสู่ระบบ

            </a>

        </div>

    <?php endif; ?>


    <?php if ($success === ''): ?>

        <div class="info-box">

            <i class="bi bi-info-circle-fill"></i>

            กรุณากรอก Username, รหัสประจำตัว และอีเมล
            ให้ตรงกับข้อมูลที่ใช้สมัครสมาชิก

        </div>


        <form method="POST" action="">


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
                        value="<?php echo e($_POST['username'] ?? ''); ?>"
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
                        value="<?php echo e($_POST['user_code'] ?? ''); ?>"
                        maxlength="50"
                        required
                    >

                    <i class="bi bi-person-vcard"></i>

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
                        value="<?php echo e($_POST['email'] ?? ''); ?>"
                        maxlength="100"
                        required
                    >

                    <i class="bi bi-envelope"></i>

                </div>

            </div>


            <!-- NEW PASSWORD -->

            <div class="form-group">

                <label>

                    รหัสผ่านใหม่

                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        name="new_password"
                        placeholder="กรอกรหัสผ่านใหม่"
                        minlength="4"
                        required
                    >

                    <i class="bi bi-lock"></i>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label>

                    ยืนยันรหัสผ่านใหม่

                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
                        minlength="4"
                        required
                    >

                    <i class="bi bi-lock-fill"></i>

                </div>

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="reset-button"
            >

                <i class="bi bi-arrow-repeat"></i>

                เปลี่ยนรหัสผ่าน

            </button>


        </form>

    <?php endif; ?>


    <div class="login-link">

        <i class="bi bi-arrow-left"></i>

        <a href="login.php">

            กลับไปหน้าเข้าสู่ระบบ

        </a>

    </div>


</div>


</body>

</html>