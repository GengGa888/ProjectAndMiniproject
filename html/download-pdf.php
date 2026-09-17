<?php

session_start();
require_once 'db_connect.php';


/* =====================================================
   PROJECT ID
===================================================== */

$project_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($project_id <= 0) {

    die('ไม่พบโปรเจกต์');
}


/* =====================================================
   GET PDF
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        pdf_file
    FROM projects
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    die(
        'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL'
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close($stmt);

    die('ไม่พบโปรเจกต์');
}


$project =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/* =====================================================
   CHECK PDF
===================================================== */

$pdf_file =
    trim(
        $project['pdf_file'] ?? ''
    );


if ($pdf_file === '') {

    die(
        'โปรเจกต์นี้ไม่มีไฟล์ PDF'
    );
}


/* =====================================================
   PDF PATH
===================================================== */

$safe_pdf =
    basename($pdf_file);


$pdf_path =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'uploads' .
    DIRECTORY_SEPARATOR .
    $safe_pdf;


if (!is_file($pdf_path)) {

    die(
        'ไม่พบไฟล์ PDF'
    );
}


/* =====================================================
   INCREASE DOWNLOAD COUNT
===================================================== */

$download_stmt =
    mysqli_prepare(
        $conn,
        "
        UPDATE projects
        SET downloads = downloads + 1
        WHERE id = ?
        "
    );


if ($download_stmt) {

    mysqli_stmt_bind_param(
        $download_stmt,
        "i",
        $project_id
    );


    mysqli_stmt_execute(
        $download_stmt
    );


    mysqli_stmt_close(
        $download_stmt
    );
}


/* =====================================================
   SEND PDF
===================================================== */

$file_size =
    filesize($pdf_path);


header(
    'Content-Type: application/pdf'
);


header(
    'Content-Length: ' . $file_size
);


/*
    บังคับให้ดาวน์โหลดไฟล์
*/

header(
    'Content-Disposition: attachment; filename="' .
    str_replace(
        '"',
        '',
        $safe_pdf
    ) .
    '"'
);


header(
    'Cache-Control: private, max-age=0, must-revalidate'
);


header(
    'Pragma: public'
);


/* =====================================================
   OUTPUT FILE
===================================================== */

readfile(
    $pdf_path
);


exit();

?>