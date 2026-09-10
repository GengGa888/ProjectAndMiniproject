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
   รับ ID ผู้ใช้
========================================================= */

$user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


/* ถ้าไม่มี ID */

if ($user_id <= 0) {

    echo "<script>
        alert('ไม่พบ ID ผู้ใช้');
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   ป้องกัน Admin ลบบัญชีตัวเอง
========================================================= */

$current_user_id = (int)$_SESSION['user_id'];

if ($user_id === $current_user_id) {

    echo "<script>
        alert('ไม่สามารถลบบัญชี Admin ที่กำลังใช้งานอยู่ได้');
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   ตรวจสอบว่ามี User หรือไม่
========================================================= */

$stmt = $conn->prepare("
    SELECT id, username, first_name, last_name, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {

    echo "<script>
        alert('ไม่พบผู้ใช้งานที่ต้องการลบ');
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   ลบ User
========================================================= */

$stmt = $conn->prepare("
    DELETE FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);


if ($stmt->execute()) {

    $stmt->close();

    echo "<script>

        alert(
            'ลบผู้ใช้ \""
            . addslashes(
                $user['username']
            )
            . "\" สำเร็จ'
        );

        window.location.href='admin.php';

    </script>";

    exit;

} else {

    $error = $stmt->error;

    $stmt->close();

    echo "<script>

        alert(
            'ไม่สามารถลบผู้ใช้ได้\\n\\n"
            . addslashes($error)
            . "'
        );

        window.location.href='admin.php';

    </script>";

    exit;
}

?>