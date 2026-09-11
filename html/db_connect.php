<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "project_db";

$conn = mysqli_connect(
    $host,
    $user,
    $pass,
    $dbname
);

if (!$conn) {
    die(
        "เชื่อมต่อฐานข้อมูลล้มเหลว: " .
        mysqli_connect_error()
    );
}

/* รองรับภาษาไทย */
mysqli_set_charset($conn, "utf8mb4");

?>