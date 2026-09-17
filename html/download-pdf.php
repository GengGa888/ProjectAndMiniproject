<?php
session_start();
require_once 'db_connect.php';

// รับ ID โปรเจกต์
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    die('ไม่พบโปรเจกต์');
}

/* =========================
   ดึงข้อมูล PDF
========================= */

$sql = "SELECT id, pdf_file 
        FROM projects 
        WHERE id = $project_id 
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "เกิดข้อผิดพลาด SQL: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

if (mysqli_num_rows($result) === 0) {
    die('ไม่พบโปรเจกต์');
}

$project = mysqli_fetch_assoc($result);

/* =========================
   ตรวจสอบไฟล์ PDF
========================= */

$pdf_file = trim($project['pdf_file'] ?? '');

if ($pdf_file === '') {
    die('โปรเจกต์นี้ไม่มีไฟล์ PDF');
}

// ป้องกัน path แปลก ๆ
$safe_pdf = basename($pdf_file);

// ตำแหน่งไฟล์จริง
$pdf_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $safe_pdf;

if (!is_file($pdf_path)) {
    die('ไม่พบไฟล์ PDF ในโฟลเดอร์ uploads');
}

/* =========================
   เพิ่มยอดดาวน์โหลด
========================= */

$update_sql = "
    UPDATE projects
    SET downloads = downloads + 1
    WHERE id = $project_id
";

$update_result = mysqli_query($conn, $update_sql);

// ถ้าอัปเดตไม่ได้ ยังให้ดาวน์โหลดไฟล์ต่อ
if (!$update_result) {
    // ไม่หยุดการดาวน์โหลด
}

/* =========================
   ส่งไฟล์ PDF ให้ดาวน์โหลด
========================= */

$file_size = filesize($pdf_path);

header('Content-Type: application/pdf');
header('Content-Length: ' . $file_size);
header(
    'Content-Disposition: attachment; filename="' .
    str_replace('"', '', $safe_pdf) .
    '"'
);
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($pdf_path);
exit();
?>