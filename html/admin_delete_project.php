<?php
session_start();
require_once "db_connect.php";


/* =========================================================
   ตรวจสอบ Login
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   ตรวจสอบ Admin
========================================================= */

if (($_SESSION['role'] ?? '') !== 'admin') {

    echo "<script>
        alert('ไม่มีสิทธิ์ใช้งานส่วนนี้');
        window.location.href='index2.php';
    </script>";

    exit;
}


/* =========================================================
   รับ Project ID
========================================================= */

$project_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($project_id <= 0) {

    echo "<script>
        alert('ไม่พบ ID โปรเจกต์');
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   ดึงข้อมูลโปรเจกต์
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        project_name,
        pdf_file
    FROM projects
    WHERE id = ?
    LIMIT 1
");


if (!$stmt) {

    echo "<script>
        alert('เกิดข้อผิดพลาดในการตรวจสอบโปรเจกต์');
        window.location.href='admin.php';
    </script>";

    exit;
}


$stmt->bind_param("i", $project_id);

$stmt->execute();

$result = $stmt->get_result();

$project = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   ตรวจสอบว่าโปรเจกต์มีอยู่จริงหรือไม่
========================================================= */

if (!$project) {

    echo "<script>
        alert('ไม่พบโปรเจกต์ที่ต้องการลบ');
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   เก็บชื่อไฟล์ PDF
========================================================= */

$pdf_file = '';

if (!empty($project['pdf_file'])) {

    $pdf_file = basename(
        $project['pdf_file']
    );
}


/* =========================================================
   ลบข้อมูลจาก Database ก่อน
========================================================= */

/*
   ลบข้อมูลโปรเจกต์ออกจาก Database
*/

$stmt = $conn->prepare("
    DELETE FROM projects
    WHERE id = ?
    LIMIT 1
");


if (!$stmt) {

    echo "<script>
        alert('ไม่สามารถเตรียมคำสั่งลบโปรเจกต์ได้');
        window.location.href='admin.php';
    </script>";

    exit;
}


$stmt->bind_param(
    "i",
    $project_id
);


if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    echo "<script>
        alert('ไม่สามารถลบโปรเจกต์ได้');
        window.location.href='admin.php';
    </script>";

    exit;
}


$stmt->close();


/* =========================================================
   ลบไฟล์ PDF
========================================================= */

if ($pdf_file !== '') {

    $pdf_path =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        "uploads" .
        DIRECTORY_SEPARATOR .
        $pdf_file;


    /*
       ตรวจสอบว่าเป็นไฟล์จริง
    */

    if (is_file($pdf_path)) {

        /*
           ถ้าลบไฟล์ไม่ได้
           ข้อมูลใน Database ถูกลบไปแล้ว
           แต่ไม่ควรทำให้ระบบหยุด
        */

        @unlink($pdf_path);
    }
}


/* =========================================================
   สำเร็จ
========================================================= */

echo "<script>

    alert('ลบโปรเจกต์สำเร็จ');

    window.location.href='admin.php';

</script>";

exit;

?>