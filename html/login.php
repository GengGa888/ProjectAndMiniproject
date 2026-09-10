<?php
session_start();
require_once "db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "กรุณากรอก Username และ Password";
    } else {

        $stmt = $conn->prepare("
            SELECT id, username, first_name, last_name, email, role, department, password
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $login_success = false;

        if ($user) {

            // รองรับรหัสผ่านที่สร้างด้วย password_hash()
            if (password_verify($password, $user["password"])) {
                $login_success = true;
            }

            // รองรับรหัสผ่านเก่าแบบข้อความธรรมดา
            // เผื่อข้อมูลเดิมในฐานข้อมูลยังไม่ได้ Hash
            elseif ($password === $user["password"]) {
                $login_success = true;

                // เปลี่ยนรหัสผ่านเก่าให้เป็น Hash อัตโนมัติ
                $new_password = password_hash($password, PASSWORD_DEFAULT);

                $update = $conn->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE id = ?
                ");

                $update->bind_param(
                    "si",
                    $new_password,
                    $user["id"]
                );

                $update->execute();
                $update->close();
            }
        }

        if ($login_success) {

            // =====================================
            // เก็บข้อมูลผู้ใช้ลง Session
            // =====================================

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // เก็บข้อมูลเพิ่มเติม
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['department'] = $user['department'];

            // =====================================
            // ถ้าเป็น Admin → ไปหน้า Admin
            // =====================================

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
                exit;
            }

            // Student / Teacher → หน้าแรก
            header("Location: index2.php");
            exit;

        } else {
            $error = "Username หรือ Password ไม่ถูกต้อง";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>เข้าสู่ระบบ - ระบบสืบค้นโปรเจกต์</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Sarabun", "Segoe UI", sans-serif;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(
                135deg,
                #4da4d9,
                #2b7bb3
            );
        }

        .login-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-box {
            background: white;
            border-radius: 20px;
            padding: 35px 30px;

            box-shadow:
                0 15px 40px rgba(0, 0, 0, 0.20);
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }

        h2 {
            text-align: center;
            color: #2879ad;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

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
            transform: translateY(-50%);
            color: #4297cd;
        }

        input {
            width: 100%;
            padding: 13px 15px 13px 42px;

            border: 1px solid #ddd;
            border-radius: 10px;

            font-size: 15px;
            outline: none;

            transition: 0.2s;
        }

        input:focus {
            border-color: #4297cd;

            box-shadow:
                0 0 0 3px rgba(66, 151, 205, 0.12);
        }

        .btn-login {
            width: 100%;
            border: none;

            padding: 13px;

            border-radius: 10px;

            background: linear-gradient(
                135deg,
                #4da4d9,
                #2b7bb3
            );

            color: white;

            font-size: 16px;
            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-1px);

            box-shadow:
                0 6px 15px rgba(43, 123, 179, 0.25);
        }

        .error {
            background: #ffe6e6;
            color: #d60000;

            padding: 10px 12px;

            border-radius: 8px;

            margin-bottom: 18px;

            text-align: center;

            font-size: 14px;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: #2879ad;
            text-decoration: none;
            font-size: 14px;
        }

        .back-home a:hover {
            text-decoration: underline;
        }

    </style>
</head>

<body>

<div class="login-wrapper">

    <div class="login-box">

        <div class="logo">
            <img
                src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                alt="SDU Logo">
        </div>

        <h2>เข้าสู่ระบบ</h2>

        <div class="subtitle">
            ระบบสืบค้นโปรเจกต์ SDU
        </div>

        <?php if ($error !== ""): ?>

            <div class="error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>
                    Username
                </label>

                <div class="input-box">

                    <i class="fa-solid fa-user"></i>

                    <input
                        type="text"
                        name="username"
                        placeholder="กรอก Username"
                        required
                        autocomplete="username">

                </div>

            </div>

            <div class="form-group">

                <label>
                    Password
                </label>

                <div class="input-box">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        name="password"
                        placeholder="กรอก Password"
                        required
                        autocomplete="current-password">

                </div>

            </div>

            <button
                type="submit"
                class="btn-login">

                <i class="fa-solid fa-right-to-bracket"></i>
                เข้าสู่ระบบ

            </button>

        </form>

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