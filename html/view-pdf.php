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
   ดึงข้อมูล PDF
========================================================= */

$sql = "
    SELECT
        id,
        project_name,
        title,
        pdf_file
    FROM projects
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        'เกิดข้อผิดพลาด SQL: ' .
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

if (
    !$result ||
    mysqli_num_rows($result) === 0
) {
    mysqli_stmt_close($stmt);
    die('ไม่พบโปรเจกต์');
}

$project = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   ตรวจสอบชื่อ PDF
========================================================= */

$pdf_file = trim(
    $project['pdf_file'] ?? ''
);

if ($pdf_file === '') {
    die('โปรเจกต์นี้ไม่มีไฟล์ PDF');
}

/* =========================================================
   เอาเฉพาะชื่อไฟล์
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
   หาตำแหน่งไฟล์จริง
========================================================= */

$possible_paths = [];

/* กรณี pdf_file มี uploads/ อยู่แล้ว */

if (
    stripos($pdf_file, 'uploads/') === 0
) {
    $possible_paths[] =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $pdf_file
        );
}

/* กรณีไฟล์อยู่ใน uploads */

$possible_paths[] =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'uploads' .
    DIRECTORY_SEPARATOR .
    $safe_pdf;

/* กรณีไฟล์อยู่ใน html โดยตรง */

$possible_paths[] =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    $safe_pdf;

/* =========================================================
   หาไฟล์ที่มีอยู่จริง
========================================================= */

$pdf_path = '';

foreach ($possible_paths as $possible_path) {

    if (is_file($possible_path)) {

        $pdf_path = $possible_path;

        break;
    }
}

/* =========================================================
   ถ้าหาไฟล์ไม่เจอ
========================================================= */

if ($pdf_path === '') {

    echo '
    <!DOCTYPE html>

    <html lang="th">

    <head>

        <meta charset="UTF-8">

        <title>ไม่พบไฟล์ PDF</title>

        <style>

            body {
                font-family: Arial, sans-serif;
                background: #f5f9fc;
                padding: 40px;
            }

            .box {
                max-width: 700px;
                margin: auto;
                background: white;
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,.08);
            }

            h2 {
                color: #dc3545;
            }

            code {
                display: block;
                background: #f1f3f5;
                padding: 12px;
                border-radius: 8px;
                margin-top: 10px;
                word-break: break-all;
            }

        </style>

    </head>

    <body>

        <div class="box">

            <h2>ไม่พบไฟล์ PDF</h2>

            <p>
                ระบบกำลังหาไฟล์:
            </p>

            <code>
                ' .
                htmlspecialchars(
                    $safe_pdf,
                    ENT_QUOTES,
                    'UTF-8'
                ) .
            '
            </code>

            <p>
                ตรวจสอบว่าไฟล์อยู่ในโฟลเดอร์
                <strong>html</strong>
                หรือ
                <strong>html/uploads</strong>
            </p>

        </div>

    </body>

    </html>
    ';

    exit();
}

/* =========================================================
   ตรวจสอบว่าอ่านไฟล์ได้
========================================================= */

if (!is_readable($pdf_path)) {
    die('ไม่สามารถอ่านไฟล์ PDF ได้');
}

/* =========================================================
   ⭐ เพิ่มยอดเข้าชม
========================================================= */

$update_views = mysqli_prepare(
    $conn,
    "UPDATE projects
     SET views = COALESCE(views, 0) + 1
     WHERE id = ?"
);

if ($update_views) {

    mysqli_stmt_bind_param(
        $update_views,
        "i",
        $project_id
    );

    mysqli_stmt_execute($update_views);

    mysqli_stmt_close($update_views);
}

/* =========================================================
   ชื่อไฟล์
========================================================= */

$file_name = str_replace(
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
   เปิด PDF ให้ดู
========================================================= */

header(
    'Content-Type: application/pdf'
);

header(
    'Content-Length: ' .
    $file_size
);

/*
 * inline = เปิดดูใน Browser
 *
 * ทุกครั้งที่เข้ามาผ่านไฟล์นี้
 * จะเพิ่ม views +1
 */

header(
    'Content-Disposition: inline; filename="' .
    $file_name .
    '"'
);

header(
    'Content-Transfer-Encoding: binary'
);

header(
    'Accept-Ranges: bytes'
);

header(
    'Cache-Control: private, max-age=0, must-revalidate'
);

header(
    'Pragma: public'
);

header(
    'Expires: 0'
);

/* =========================================================
   แสดงไฟล์ PDF
========================================================= */

readfile($pdf_path);

exit();

?>