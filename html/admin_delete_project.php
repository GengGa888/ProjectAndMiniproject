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
    ? (int)$_GET['id']
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

$stmt->bind_param("i", $project_id);

$stmt->execute();

$result = $stmt->get_result();

$project = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   ตรวจสอบโปรเจกต์
========================================================= */

if (!$project) {

    echo "<script>
        alert('ไม่พบโปรเจกต์ที่ต้องการลบ');
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   ลบไฟล์ PDF
========================================================= */

if (!empty($project['pdf_file'])) {

    $file_name =
        basename($project['pdf_file']);

    $file_path =
        __DIR__ . DIRECTORY_SEPARATOR .
        "uploads" . DIRECTORY_SEPARATOR .
        $file_name;


    if (file_exists($file_path)) {

        if (!unlink($file_path)) {

            echo "<script>
                alert('ไม่สามารถลบไฟล์ PDF ได้');
                window.location.href='admin.php';
            </script>";

            exit;
        }
    }
}


/* =========================================================
   ลบข้อมูลจาก Database
========================================================= */

$stmt = $conn->prepare("
    DELETE FROM projects
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $project_id
);


if ($stmt->execute()) {

    $stmt->close();

    echo "<script>

        alert('ลบโปรเจกต์สำเร็จ');

        window.location.href='admin.php';

    </script>";

    exit;

} else {

    $error =
        $stmt->error;

    $stmt->close();

    echo "<script>

        alert(
            'ไม่สามารถลบโปรเจกต์ได้\\n\\n"
            . addslashes($error)
            . "'
        );

        window.location.href='admin.php';

    </script>";

    exit;
}

?>