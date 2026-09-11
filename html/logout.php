<?php
session_start();
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: sign-up.php");
exit();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบสืบค้นโปรเจกต์</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #e8f4fc 0%, #f4f8fb 100%);
            padding: 20px;
        }

        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin-top: 40px;
        }

        .logo-container {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
        }

        .logo-container img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            filter: drop-shadow(0px 4px 6px rgba(0, 0, 0, 0.15));
        }

        .login-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 60px 30px 30px 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-card h2 {
            font-size: 1.5rem;
            color: #2b2b2b;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            font-size: 0.95rem;
            border: 1px solid #b9dced;
            background-color: #f4fafe;
            border-radius: 6px;
            outline: none;
            transition: all 0.2s ease;
            color: #333;
        }

        .input-wrapper input:focus {
            border-color: #4297CD;
            box-shadow: 0 0 5px rgba(41, 182, 246, 0.4);
        }

        .input-wrapper i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            font-size: 1rem;
            pointer-events: none;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to bottom, #4aa4d6, #3287BB);
            border: none;
            border-radius: 6px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: opacity 0.2s ease;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        .footer-links {
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: #287cab;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .online-count {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #eeeeee;
            font-size: 0.85rem;
            color: #555;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <div class="logo-container">
            <img src="https://academic.dusit.ac.th/academic/edu/util/img/login/sdu-newlogo.png" alt="ตราสัญลักษณ์">
        </div>

        <div class="login-card">

            <h2>เข้าสู่ระบบ</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">

                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้งาน</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" placeholder="user" required>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    ลงชื่อเข้าใช้
                </button>

            </form>

            <!-- ลิงก์สำหรับคนที่ยังไม่ได้สมัคร -> กลับไปหน้า sign-up.php -->
            <div class="footer-links">
                <a href="sign-up.php">สมัครสมาชิก</a>
            </div>

            <div class="online-count">
                จำนวนผู้ใช้งานระบบปัจจุบัน <strong>0</strong> คน
            </div>

        </div>

    </div>

</body>
</html>