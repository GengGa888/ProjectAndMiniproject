<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    header("Location: profile.php");
    exit;
}


/* ===============================
   ตรวจสอบโปรเจกต์
================================ */

$stmt = $conn->prepare("
    SELECT id, pdf_file, student_id
    FROM projects
    WHERE id = ?
");

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();
$project = $result->fetch_assoc();

$stmt->close();


if (!$project) {

    echo "<script>
            alert('ไม่พบโปรเจกต์');
            window.location.href='profile.php';
          </script>";

    exit;
}


/* ===============================
   ตรวจสอบสิทธิ์
================================ */

if ($role !== 'admin') {

    if ((int)$project['student_id'] !== $user_id) {

        echo "<script>
                alert('คุณไม่มีสิทธิ์ลบโปรเจกต์นี้');
                window.location.href='profile.php';
              </script>";

        exit;
    }
}


/* ===============================
   ลบ PDF
================================ */

if (!empty($project['pdf_file'])) {

    $pdf_path =
        __DIR__ .
        "/uploads/" .
        basename($project['pdf_file']);

    if (file_exists($pdf_path)) {
        unlink($pdf_path);
    }
}


/* ===============================
   ลบโปรเจกต์
================================ */

$stmt = $conn->prepare(
    "DELETE FROM projects WHERE id = ?"
);

$stmt->bind_param("i", $project_id);


if ($stmt->execute()) {

    $stmt->close();

    if ($role === 'admin') {

        header("Location: admin.php?success=deleted");

    } else {

        header("Location: profile.php?success=deleted");
    }

    exit;

} else {

    $stmt->close();

    echo "<script>
            alert('เกิดข้อผิดพลาด ไม่สามารถลบโปรเจกต์ได้');
            window.location.href='profile.php';
          </script>";

    exit;
}
?>