<?php
$host = "localhost";
$user = "root";
$pass = ""; // ปกติ XAMPP รหัสผ่านว่างไว้
$dbname = "project_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}
?>