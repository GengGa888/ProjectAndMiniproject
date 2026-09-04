<?php
$host = 'localhost';
$db   = 'sdu_project_db';
$user = 'root';
$pass = '';

// เชื่อมต่อฐานข้อมูลด้วย MySQLi
$conn = mysqli_connect($host, $user, $pass, $db);

// ตรวจสอบว่าเชื่อมต่อสำเร็จหรือไม่
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตั้งค่าให้รองรับภาษาไทย
mysqli_set_charset($conn, "utf8");
?>