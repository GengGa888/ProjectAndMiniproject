<?php
session_start();
include 'db_connect.php'; 

// ===============================
// เมื่อกดปุ่มสมัครสมาชิก
// ===============================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // รับข้อมูลจากฟอร์ม
    $username   = trim($_POST['username'] ?? '');
    $firstname  = trim($_POST['firstname'] ?? '');
    $lastname   = trim($_POST['lastname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = trim($_POST['role'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. ตรวจสอบรหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        echo "<script>
                alert('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
                window.history.back();
              </script>";
        exit();
    }

    // 2. ตรวจสอบ Username และ Email ซ้ำ
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        echo "<script>
                alert('Username หรือ อีเมล นี้ถูกใช้งานในระบบแล้ว');
                window.history.back();
              </script>";
        mysqli_stmt_close($stmt_check);
        exit();
    }
    mysqli_stmt_close($stmt_check);

    // 3. เข้ารหัส Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. บันทึกข้อมูลลงฐานข้อมูล
    $sql = "INSERT INTO users (username, first_name, last_name, email, role, department, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt_insert, "sssssss", $username, $firstname, $lastname, $email, $role, $department, $hashed_password);

    if (mysqli_stmt_execute($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
        mysqli_close($conn);
        echo "<script>
                alert('สมัครสมาชิกสำเร็จ!');
                window.location.href='login.php';
              </script>";
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn);
        mysqli_stmt_close($stmt_insert);
        mysqli_close($conn);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>สมัครสมาชิก - ระบบสืบค้นโปรเจกต์และมินิโปรเจกต์</title>
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
      background: linear-gradient(135deg, #29b6f6 0%, #b3e5fc 50%, #e1f5fe 100%);
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
      filter: drop-shadow(0px 4px 6px rgba(0, 0, 0, 0.15));
    }

    .register-card {
      background: #ffffff;
      border-radius: 6px;
      padding: 70px 35px 35px 35px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
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
      box-shadow: 0 0 5px rgba(41, 182, 246, 0.5);
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
      background: linear-gradient(to bottom, #34b3c7, #258ca3);
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
  </style>
</head>

<body>

  <div class="register-wrapper">
    <div class="logo-container">
      <img src="https://academic.dusit.ac.th/academic/edu/util/img/login/sdu-newlogo.png" alt="ตราสัญลักษณ์">
    </div>

    <div class="register-card">
      <h2>ลงทะเบียนใช้งานระบบ</h2>

      <form action="sign-up.php" method="POST" id="registerForm">
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required>
            <i class="fa-solid fa-user"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="firstname">ชื่อ</label>
          <div class="input-wrapper">
            <input type="text" id="firstname" name="firstname" placeholder="กรอกชื่อ" required>
            <i class="fa-solid fa-id-card"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="lastname">นามสกุล</label>
          <div class="input-wrapper">
            <input type="text" id="lastname" name="lastname" placeholder="กรอกนามสกุล" required>
            <i class="fa-solid fa-id-card"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="email">อีเมล</label>
          <div class="input-wrapper">
            <input type="email" id="email" name="email" placeholder="example@mail.dusit.ac.th" required>
            <i class="fa-solid fa-envelope"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="role">ประเภทผู้ใช้งาน</label>
          <div class="input-wrapper">
            <select id="role" name="role" required>
              <option value="" disabled selected>-- เลือกสิทธิ์การใช้งาน --</option>
              <option value="student">นักศึกษา</option>
              <option value="teacher">อาจารย์ / ที่ปรึกษา</option>
            </select>
            <i class="fa-solid fa-users"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="department">สาขาวิชา / คณะ</label>
          <div class="input-wrapper">
            <select id="department" name="department" required>
              <option value="" disabled selected>-- เลือกสาขาวิชา --</option>
              <option value="it">เทคโนโลยีสารสนเทศ</option>
              <option value="cs">วิทยาการคอมพิวเตอร์</option>
              <option value="se">วิศวกรรมซอฟต์แวร์</option>
              <option value="other_dept">สาขาอื่นๆ</option>
            </select>
            <i class="fa-solid fa-chevron-down"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="password">รหัสผ่าน</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password" placeholder="กำหนดรหัสผ่าน" required>
            <i class="fa-solid fa-lock"></i>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm_password">ยืนยันรหัสผ่าน</label>
          <div class="input-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
            <i class="fa-solid fa-shield-halved"></i>
          </div>
        </div>

        <button type="submit" class="btn-submit">ยืนยันการสมัครสมาชิก</button>
      </form>

      <div class="footer-links">
        มีบัญชีผู้ใช้งานอยู่แล้ว?
        <a href="login.php">เข้าสู่ระบบที่นี่</a>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (password !== confirmPassword) {
        e.preventDefault();
        alert("รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน");
      }
    });
  </script>
</body>
</html>