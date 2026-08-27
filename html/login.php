<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
session_start();

// 1. เชื่อมต่อฐานข้อมูล Localhost (เปลี่ยน your_database_name เป็นชื่อ DB ของคุณ)
$conn = mysqli_connect("localhost", "root", "", "your_database_name");

// เช็กการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 2. ค้นหาผู้ใช้จากตาราง users
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // เก็บข้อมูลลง Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; // เช่น 'admin', 'teacher', 'student'

        // 3. เช็ก Role แล้วส่งผู้ใช้ไปหน้า UI ของตัวเอง
        if ($user['role'] == 'admin') {
            header("Location: admin.html");
        } else if ($user['role'] == 'teacher') {
            header("Location: arjarn.html");
        } else if ($user['role'] == 'student') {
            header("Location: Personal Information.html");
        }
        exit();
    } else {
        echo "<script>alert('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'); window.location.href='login.html';</script>";
    }
}
?>
</body>
</html>