<?php

session_start();
require_once 'db_connect.php';


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo "
    <script>
        alert('กรุณาเข้าสู่ระบบก่อน');
        window.location.href='login.php';
    </script>
    ";

    exit();
}


$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';


/* =========================================================
   GET DATA
========================================================= */

$comment_id = isset($_POST['comment_id'])
    ? (int)$_POST['comment_id']
    : 0;

$project_id = isset($_POST['project_id'])
    ? (int)$_POST['project_id']
    : 0;


if ($comment_id <= 0 || $project_id <= 0) {

    echo "
    <script>
        alert('ข้อมูลไม่ถูกต้อง');
        history.back();
    </script>
    ";

    exit();
}


/* =========================================================
   GET COMMENT
========================================================= */

$sql = "
    SELECT
        id,
        project_id,
        teacher_id
    FROM project_comments
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียม SQL");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $comment_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (!$result || mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    echo "
    <script>
        alert('ไม่พบความคิดเห็นนี้');
        history.back();
    </script>
    ";

    exit();
}


$comment = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   CHECK PROJECT
========================================================= */

$comment_project_id =
    (int)$comment['project_id'];

$comment_teacher_id =
    (int)$comment['teacher_id'];


if ($comment_project_id !== $project_id) {

    echo "
    <script>
        alert('ข้อมูลโปรเจกต์ไม่ถูกต้อง');
        history.back();
    </script>
    ";

    exit();
}


/* =========================================================
   PERMISSION
========================================================= */

/*
    ADMIN
    - ลบความคิดเห็นของใครก็ได้

    TEACHER
    - ลบได้เฉพาะความคิดเห็นของตัวเอง
*/

$can_delete = false;


if ($user_role === 'admin') {

    $can_delete = true;

} elseif (
    $user_role === 'teacher' &&
    $comment_teacher_id === $user_id
) {

    $can_delete = true;
}


if (!$can_delete) {

    echo "
    <script>
        alert('คุณไม่มีสิทธิ์ลบความคิดเห็นนี้');
        history.back();
    </script>
    ";

    exit();
}


/* =========================================================
   DELETE
========================================================= */

$sql = "
    DELETE FROM project_comments
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียม SQL");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $comment_id
);


if (!mysqli_stmt_execute($stmt)) {

    die(
        "SQL Error: " .
        mysqli_stmt_error($stmt)
    );
}


mysqli_stmt_close($stmt);


/* =========================================================
   SUCCESS
========================================================= */

header(
    "Location: project-detail.php?id=" .
    $project_id
);

exit();

?>