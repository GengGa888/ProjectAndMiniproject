<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<?php
// 1. เชื่อมต่อฐานข้อมูล (อย่าลืมเปลี่ยน your_database_name เป็นชื่อ DB จริงของคุณ)
$conn = mysqli_connect("localhost", "root", "", "your_database_name");

// เช็กการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม และป้องกันตัวอักษรพิเศษ (SQL Injection)
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $firstname  = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname   = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $role       = mysqli_real_escape_string($conn, $_POST['role']);       // 'student' หรือ 'teacher'
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $password   = mysqli_real_escape_string($conn, $_POST['password']);

    // 2. เช็กว่า Username ซ้ำในระบบหรือไม่
    $check_user = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $check_user);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Username นี้มีผู้ใช้งานแล้ว'); window.history.back();</script>";
    } else {
        // 3. บันทึกข้อมูลเข้าตาราง users
        $sql = "INSERT INTO users (username, firstname, lastname, email, role, department, password) 
                VALUES ('$username', '$firstname', '$lastname', '$email', '$role', '$department', '$password')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location.href='login.html';</script>";
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    }
}
?>, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>