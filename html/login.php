<?php
session_start();
require_once "db_connect.php";

$error = "";


/* =========================================================
   ถ้า Login อยู่แล้ว
========================================================= */

if (isset($_SESSION['user_id'])) {

    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index2.php");
    }

    exit();
}


/* =========================================================
   LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =====================================================
       VALIDATE
    ===================================================== */

    if ($username === "" || $password === "") {

        $error = "กรุณากรอก Username และ Password";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                username,
                first_name,
                last_name,
                email,
                role,
                department,
                password
            FROM users
            WHERE username = ?
            LIMIT 1
        ");


        if (!$stmt) {

            $error = "เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่อีกครั้ง";

        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $login_success = false;


            /* =================================================
               CHECK PASSWORD
            ================================================= */

            if ($user) {

                /*
                 * รองรับ Password ที่ Hash ด้วย password_hash()
                 */
                if (
                    !empty($user["password"]) &&
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    $login_success = true;

                }

                /*
                 * รองรับ Password เก่าแบบ Plain Text
                 *
                 * ถ้าเจอ จะ Hash ใหม่ให้อัตโนมัติ
                 */
                elseif (
                    !empty($user["password"]) &&
                    hash_equals(
                        (string)$user["password"],
                        (string)$password
                    )
                ) {

                    $login_success = true;


                    /* =========================================
                       HASH PASSWORD ใหม่
                    ========================================= */

                    $new_password =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );


                    $update = $conn->prepare("
                        UPDATE users
                        SET password = ?
                        WHERE id = ?
                        LIMIT 1
                    ");


                    if ($update) {

                        $update->bind_param(
                            "si",
                            $new_password,
                            $user["id"]
                        );

                        $update->execute();
                        $update->close();
                    }
                }
            }


            /* =================================================
               LOGIN SUCCESS
            ================================================= */

            if ($login_success) {

                /*
                 * ป้องกัน Session Fixation
                 */
                session_regenerate_id(true);


                /* =============================================
                   SESSION
                ============================================= */

                $_SESSION['user_id'] =
                    (int)$user['id'];

                $_SESSION['username'] =
                    $user['username'];

                $_SESSION['role'] =
                    $user['role'];

                $_SESSION['first_name'] =
                    $user['first_name'];

                $_SESSION['last_name'] =
                    $user['last_name'];

                $_SESSION['email'] =
                    $user['email'];

                $_SESSION['department'] =
                    $user['department'];


                /* =============================================
                   REDIRECT
                ============================================= */

                if ($user['role'] === 'admin') {

                    header("Location: admin.php");
                    exit();

                }


                /*
                 * Student / Teacher
                 */
                header("Location: index2.php");
                exit();

            } else {

                $error =
                    "Username หรือ Password ไม่ถูกต้อง";
            }


            $stmt->close();
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
        เข้าสู่ระบบ - ระบบสืบค้นโปรเจกต์
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                "Sarabun",
                "Segoe UI",
                Tahoma,
                Arial,
                sans-serif;

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #3287BB
                );

            padding: 20px;
        }


        /* =====================================================
           WRAPPER
        ===================================================== */

        .login-wrapper {

            width: 100%;

            max-width: 400px;
        }


        /* =====================================================
           LOGIN BOX
        ===================================================== */

        .login-box {

            background: white;

            border-radius: 20px;

            padding: 35px 30px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.20);
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .logo {

            text-align: center;

            margin-bottom: 20px;
        }


        .logo img {

            width: 85px;

            height: 85px;

            object-fit: contain;
        }


        /* =====================================================
           TITLE
        ===================================================== */

        h2 {

            text-align: center;

            color: #287cab;

            margin-bottom: 8px;

            font-size: 28px;
        }


        .subtitle {

            text-align: center;

            color: #777;

            margin-bottom: 25px;

            font-size: 14px;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .error {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            background: #ffe6e6;

            color: #d60000;

            padding: 10px 12px;

            border-radius: 8px;

            margin-bottom: 18px;

            text-align: center;

            font-size: 14px;

            line-height: 1.5;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 18px;
        }


        label {

            display: block;

            margin-bottom: 7px;

            font-weight: 600;

            color: #444;
        }


        .input-box {

            position: relative;
        }


        .input-box i {

            position: absolute;

            left: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #4297cd;

            pointer-events: none;
        }


        input {

            width: 100%;

            padding:
                13px
                15px
                13px
                42px;

            border:
                1px solid #ddd;

            border-radius: 10px;

            font-size: 15px;

            outline: none;

            transition: 0.2s;
        }


        input:focus {

            border-color: #4297cd;

            box-shadow:
                0 0 0 3px
                rgba(66, 151, 205, 0.12);
        }


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

        .btn-login {

            width: 100%;

            border: none;

            padding: 13px;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #3287BB
                );

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;
        }


        .btn-login:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 6px 15px
                rgba(43, 123, 179, 0.25);
        }


        .btn-login:active {

            transform:
                translateY(0);
        }


        /* =====================================================
           BACK HOME
        ===================================================== */

        .back-home {

            text-align: center;

            margin-top: 20px;
        }


        .back-home a {

            color: #287cab;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s;
        }


        .back-home a:hover {

            text-decoration: underline;

            color: #17628f;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 480px) {

            body {
                padding: 15px;
            }


            .login-box {

                padding:
                    30px
                    20px;

                border-radius: 16px;
            }


            .logo img {

                width: 75px;

                height: 75px;
            }


            h2 {

                font-size: 25px;
            }

        }

    </style>

</head>


<body>


<div class="login-wrapper">

    <div class="login-box">


        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="logo">

            <img
                src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                alt="SDU Logo"
            >

        </div>


        <!-- =================================================
             TITLE
        ================================================== -->

        <h2>
            เข้าสู่ระบบ
        </h2>


        <div class="subtitle">
            ระบบสืบค้นโปรเจกต์ SDU
        </div>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ""): ?>

            <div class="error">

                <i
                    class="fa-solid fa-circle-exclamation"
                ></i>

                <span>
                    <?php echo htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="POST"
            action=""
            autocomplete="on"
        >


            <!-- USERNAME -->

            <div class="form-group">

                <label for="username">
                    Username
                </label>


                <div class="input-box">

                    <i class="fa-solid fa-user"></i>


                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="กรอก Username"
                        value="<?php echo htmlspecialchars(
                            $username ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        required
                        autocomplete="username"
                        maxlength="50"
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>


                <div class="input-box">

                    <i class="fa-solid fa-lock"></i>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="กรอก Password"
                        required
                        autocomplete="current-password"
                    >

                </div>

            </div>


            <!-- LOGIN -->

            <button
                type="submit"
                class="btn-login"
            >

                <i class="fa-solid fa-right-to-bracket"></i>

                เข้าสู่ระบบ

            </button>


        </form>


        <!-- =================================================
             BACK HOME
        ================================================== -->

        <div class="back-home">

            <a href="index2.php">

                <i class="fa-solid fa-house"></i>

                กลับหน้าแรก

            </a>

        </div>


    </div>

</div>

</body>

</html>