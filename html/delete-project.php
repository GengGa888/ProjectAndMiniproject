<?php
session_start();
require_once "db_connect.php";

/* ===============================
   ตรวจสอบการเข้าสู่ระบบ
================================ */

if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('กรุณาเข้าสู่ระบบก่อน');
            window.location.href='login.php';
          </script>";
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


/* ===============================
   ตรวจสอบ ID โปรเจกต์
================================ */

if ($project_id <= 0) {

    echo "<script>
            alert('ไม่พบโปรเจกต์ที่ต้องการลบ');
            window.location.href='profile.php';
          </script>";

    exit;
}


/* ===============================
   ดึงข้อมูลโปรเจกต์
================================ */

$stmt = $conn->prepare("
    SELECT 
        id,
        title,
        project_name,
        pdf_file,
        student_id
    FROM projects
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
}

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();
$project = $result->fetch_assoc();

$stmt->close();


/* ===============================
   ตรวจสอบว่ามีโปรเจกต์หรือไม่
================================ */

if (!$project) {

    echo "<script>
            alert('ไม่พบโปรเจกต์นี้ในระบบ');
            window.location.href='profile.php';
          </script>";

    exit;
}


/* ===============================
   ตรวจสอบสิทธิ์การลบ
================================ */

/*
   Admin
   → ลบโปรเจกต์ของใครก็ได้

   Student
   → ลบได้เฉพาะโปรเจกต์ของตัวเอง

   Teacher
   → ไม่มีสิทธิ์ลบ
*/

if ($role === 'admin') {

    // Admin ผ่าน

} elseif ($role === 'student') {

    if (
        empty($project['student_id']) ||
        (int)$project['student_id'] !== $user_id
    ) {

        echo "<script>
                alert('คุณไม่มีสิทธิ์ลบโปรเจกต์นี้');
                window.location.href='profile.php';
              </script>";

        exit;
    }

} else {

    echo "<script>
            alert('บัญชีนี้ไม่มีสิทธิ์ลบโปรเจกต์');
            window.location.href='index2.php';
          </script>";

    exit;
}


/* ===============================
   ลบไฟล์ PDF
================================ */

if (!empty($project['pdf_file'])) {

    $filename = basename($project['pdf_file']);

    $pdf_path = __DIR__ . "/uploads/" . $filename;

    if (is_file($pdf_path)) {

        if (!unlink($pdf_path)) {

            echo "<script>
                    alert('ไม่สามารถลบไฟล์ PDF ได้');
                    window.location.href='profile.php';
                  </script>";

            exit;
        }
    }
}


/* ===============================
   ลบข้อมูลโปรเจกต์จาก Database
================================ */

$stmt = $conn->prepare("
    DELETE FROM projects
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    echo "<script>
            alert('เกิดข้อผิดพลาดในการลบโปรเจกต์');
            window.location.href='profile.php';
          </script>";
    exit;
}

$stmt->bind_param("i", $project_id);

$success = $stmt->execute();

$stmt->close();


/* ===============================
   หลังจากลบสำเร็จ
================================ */

if ($success) {

    if ($role === 'admin') {

        echo "<script>
                alert('ลบโปรเจกต์เรียบร้อยแล้ว');
                window.location.href='admin.php';
              </script>";

    } else {

        echo "<script>
                alert('ลบโปรเจกต์เรียบร้อยแล้ว');
                window.location.href='profile.php';
              </script>";
    }

    exit;
}


/* ===============================
   กรณีลบไม่สำเร็จ
================================ */

echo "<script>
        alert('เกิดข้อผิดพลาด ไม่สามารถลบโปรเจกต์ได้');
        window.location.href='profile.php';
      </script>";

exit;
?>