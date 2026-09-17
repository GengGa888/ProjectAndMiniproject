<?php

session_start();
require_once 'db_connect.php';


/* =========================================================
   PROJECT ID
========================================================= */

$project_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($project_id <= 0) {
    die('ไม่พบรหัสโปรเจกต์');
}


/* =========================================================
   GET PDF FILE FROM DATABASE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT pdf_file
    FROM projects
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {
    die('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล');
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close($stmt);

    die('ไม่พบโปรเจกต์');
}


$row = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/* =========================================================
   PDF FILE
========================================================= */

$pdf_file = trim(
    $row['pdf_file'] ?? ''
);


if ($pdf_file === '') {
    die('โปรเจกต์นี้ไม่มีไฟล์ PDF');
}


/* =========================================================
   FILE NAME
========================================================= */

$file_name = basename($pdf_file);


/* =========================================================
   SEARCH PDF FILE
========================================================= */

$possible_files = [

    // กรณีเก็บตรงโฟลเดอร์เดียวกับ PHP
    __DIR__ . '/' . $file_name,

    // โฟลเดอร์ uploads
    __DIR__ . '/uploads/' . $file_name,

    // โฟลเดอร์ pdf
    __DIR__ . '/pdf/' . $file_name,

    // โฟลเดอร์ files
    __DIR__ . '/files/' . $file_name,

    // โฟลเดอร์ project_pdf
    __DIR__ . '/project_pdf/' . $file_name,

    // โฟลเดอร์ project_uploads
    __DIR__ . '/project_uploads/' . $file_name,

    // ถ้าใน DB มี path เต็มของโปรเจกต์
    __DIR__ . '/' . ltrim($pdf_file, '/\\')
];


$real_file = '';


foreach (
    $possible_files
    as $possible_file
) {

    if (
        is_file($possible_file) &&
        is_readable($possible_file)
    ) {

        $real_file = $possible_file;

        break;
    }
}


/* =========================================================
   FILE NOT FOUND
========================================================= */

if ($real_file === '') {

    http_response_code(404);

    die(
        'ไม่พบไฟล์ PDF บนเซิร์ฟเวอร์<br><br>' .
        'ชื่อไฟล์: ' .
        htmlspecialchars(
            $file_name,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


/* =========================================================
   CHECK PDF
========================================================= */

$mime_type = mime_content_type(
    $real_file
);


if (
    $mime_type !== 'application/pdf'
) {

    $mime_type = 'application/pdf';
}


/* =========================================================
   SHOW PDF IN BROWSER
========================================================= */

header(
    'Content-Type: ' . $mime_type
);

header(
    'Content-Disposition: inline; filename="' .
    $file_name .
    '"'
);

header(
    'Content-Length: ' .
    filesize($real_file)
);

header(
    'Cache-Control: private, max-age=0, must-revalidate'
);

header(
    'Pragma: public'
);


/* =========================================================
   OUTPUT PDF
========================================================= */

readfile($real_file);

exit();

?>