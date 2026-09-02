<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "project_db";

// ซ่อนการแสดง mysqli error แจ้งเตือนดิบ เพื่อป้องกัน Security Leak
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $user, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    // บันทึก Error ไว้ใน Log ไฟล์ของเซิร์ฟเวอร์
    error_log("Database Connection Error: " . $conn->connect_error);
    
    // แสดงข้อความที่ปลอดภัยต่อผู้ใช้งาน
    die("ขออภัย ไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง");
}

// กำหนด Character set ให้รองรับภาษาไทยและอักขระพิเศษ
$conn->set_charset("utf8mb4");
?>