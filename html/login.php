<?php
session_start(); 
include 'db_connect.php'; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            
            // เช็กรหัสผ่านทั้งแบบเข้ารหัส (Password Hash) และข้อความปกติ
            $password_check = password_verify($password, $user['password']) || ($password === $user['password']);

            if ($password_check) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // ส่งไปยังหน้าตามประเภทผู้ใช้
                if ($user['role'] == 'admin') {
                    header("Location: admin.php");
                } elseif ($user['role'] == 'teacher') {
                    header("Location: arjarn.php");
                } else {
                    // นักศึกษา / สมาชิกทั่วไป ส่งไปหน้า index2.php
                    header("Location: index2.php"); 
                }
                exit();
            } else {
                $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }

        mysqli_stmt_close($stmt);
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
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
            background: linear-gradient(135deg, #b3e5fc 0%, #e1f5fe 100%);
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
            border: 1px solid #bce0fd;
            background-color: #f0f7ff;
            border-radius: 6px;
            outline: none;
            transition: all 0.2s ease;
            color: #333;
        }

        .input-wrapper input:focus {
            border-color: #29b6f6;
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
            background: linear-gradient(to bottom, #34b3c7, #258ca3);
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
            color: #0088cc;
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