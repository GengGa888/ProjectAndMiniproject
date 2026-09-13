<?php

session_start();
require_once "db_connect.php";


// =====================================================
// ต้อง Login
// =====================================================

if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('กรุณาเข้าสู่ระบบก่อน');
        window.location.href='login.php';
    </script>";
    exit;
}


// =====================================================
// ต้องเป็นอาจารย์เท่านั้น
// =====================================================

if (($_SESSION['role'] ?? '') !== 'teacher') {
    echo "<script>
        alert('เฉพาะอาจารย์เท่านั้นที่สามารถคอมเมนต์โปรเจกต์ได้');
        history.back();
    </script>";
    exit;
}


// =====================================================
// รับข้อมูล
// =====================================================

$project_id = (int)($_POST['project_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

$user_id = (int)$_SESSION['user_id'];


// =====================================================
// ตรวจสอบข้อมูล
// =====================================================

if ($project_id <= 0 || $comment === '') {
    echo "<script>
        alert('กรุณากรอกความคิดเห็น');
        history.back();
    </script>";
    exit;
}


// =====================================================
// ดึงข้อมูลโปรเจกต์
// =====================================================

$sql = "
    SELECT
        id,
        advisor
    FROM projects
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("SQL Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$project = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// =====================================================
// ไม่พบโปรเจกต์
// =====================================================

if (!$project) {
    echo "<script>
        alert('ไม่พบโปรเจกต์');
        window.location.href='index2.php';
    </script>";
    exit;
}


// =====================================================
// ตรวจสอบว่าอาจารย์เป็นผู้ดูแลโปรเจกต์นี้หรือไม่
// =====================================================

$first_name = trim($_SESSION['first_name'] ?? '');
$last_name  = trim($_SESSION['last_name'] ?? '');

$teacher_name = trim(
    $first_name . ' ' . $last_name
);

$advisor = trim($project['advisor'] ?? '');


// ใช้ชื่ออาจารย์ตรวจสอบ
$is_advisor = false;

if ($teacher_name !== '' && $advisor !== '') {

    if (
        mb_stripos(
            $advisor,
            $teacher_name,
            0,
            'UTF-8'
        ) !== false
    ) {
        $is_advisor = true;
    }
}


// =====================================================
// ถ้าไม่ใช่อาจารย์ที่ดูแล ห้ามคอมเมนต์
// =====================================================

if (!$is_advisor) {

    echo "<script>
        alert('คุณไม่ใช่อาจารย์ที่ดูแลโปรเจกต์นี้');
        history.back();
    </script>";

    exit;
}


// =====================================================
// บันทึกคอมเมนต์
// =====================================================

$sql = "
    INSERT INTO project_comments
    (
        project_id,
        user_id,
        comment
    )
    VALUES (?, ?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("SQL Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "iis",
    $project_id,
    $user_id,
    $comment
);

if (!mysqli_stmt_execute($stmt)) {

    die(
        "ไม่สามารถบันทึกความคิดเห็นได้: " .
        htmlspecialchars(
            mysqli_stmt_error($stmt),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

mysqli_stmt_close($stmt);


// =====================================================
// กลับหน้าโปรเจกต์
// =====================================================

header(
    "Location: project-detail.php?id=" .
    $project_id .
    "#comments"
);

exit;

?>