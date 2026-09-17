<?php

session_start();
require_once 'db_connect.php';

/* =========================================================
   รับ ID โปรเจกต์
========================================================= */

$project_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($project_id <= 0) {
    die('ไม่พบโปรเจกต์');
}


/* =========================================================
   ดึงข้อมูลโปรเจกต์
========================================================= */

$sql = "
    SELECT
        id,
        pdf_file
    FROM projects
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        'เกิดข้อผิดพลาดในการเตรียม SQL: ' .
        htmlspecialchars(
            mysqli_error($conn),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    die('ไม่พบโปรเจกต์');
}

$project = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   ตรวจสอบชื่อไฟล์
========================================================= */

$pdf_file = trim(
    $project['pdf_file'] ?? ''
);

if ($pdf_file === '') {
    die('โปรเจกต์นี้ไม่มีไฟล์ PDF');
}


/* =========================================================
   ป้องกัน Path แปลก ๆ
========================================================= */

$safe_pdf = basename($pdf_file);

if (
    $safe_pdf === '' ||
    $safe_pdf === '.' ||
    $safe_pdf === '..'
) {
    die('ชื่อไฟล์ PDF ไม่ถูกต้อง');
}


/* =========================================================
   ตำแหน่งไฟล์
========================================================= */

$pdf_path =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'uploads' .
    DIRECTORY_SEPARATOR .
    $safe_pdf;


/* =========================================================
   ตรวจสอบไฟล์
========================================================= */

if (!is_file($pdf_path)) {

    die(
        'ไม่พบไฟล์ PDF ในโฟลเดอร์ uploads<br><br>' .
        'ชื่อไฟล์ที่ระบบกำลังหา: <strong>' .
        htmlspecialchars(
            $safe_pdf,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</strong><br><br>' .
        'ตำแหน่งที่ระบบตรวจสอบ:<br>' .
        htmlspecialchars(
            $pdf_path,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


if (!is_readable($pdf_path)) {
    die('ไม่สามารถอ่านไฟล์ PDF ได้');
}


/* =========================================================
   เพิ่มยอดดาวน์โหลด
   เฉพาะตอนกดดาวน์โหลด
========================================================= */

$update_sql = "
    UPDATE projects
    SET downloads = COALESCE(downloads, 0) + 1
    WHERE id = ?
";

$update_stmt = mysqli_prepare(
    $conn,
    $update_sql
);

if ($update_stmt) {

    mysqli_stmt_bind_param(
        $update_stmt,
        "i",
        $project_id
    );

    mysqli_stmt_execute(
        $update_stmt
    );

    mysqli_stmt_close(
        $update_stmt
    );
}


/* =========================================================
   เตรียมชื่อไฟล์
========================================================= */

$download_name = str_replace(
    [
        '"',
        "\r",
        "\n"
    ],
    '',
    $safe_pdf
);


/* =========================================================
   ขนาดไฟล์
========================================================= */

$file_size = filesize($pdf_path);


/* =========================================================
   Header สำหรับดาวน์โหลด
========================================================= */

header(
    'Content-Type: application/pdf'
);

header(
    'Content-Length: ' . $file_size
);

header(
    'Content-Disposition: attachment; filename="' .
    $download_name .
    '"'
);

header(
    'Content-Transfer-Encoding: binary'
);

header(
    'Accept-Ranges: bytes'
);

header(
    'Cache-Control: private, no-store, no-cache, must-revalidate'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


/* =========================================================
   ส่งไฟล์
========================================================= */

readfile($pdf_path);

exit();

?>