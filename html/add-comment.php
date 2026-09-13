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


/* =========================================================
   CHECK TEACHER
========================================================= */

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

if ($user_role !== 'teacher') {
    echo "
    <script>
        alert('เฉพาะอาจารย์เท่านั้นที่สามารถแสดงความคิดเห็นได้');
        window.history.back();
    </script>
    ";
    exit();
}


/* =========================================================
   GET DATA
========================================================= */

$project_id = isset($_POST['project_id'])
    ? (int)$_POST['project_id']
    : 0;

$comment = trim($_POST['comment'] ?? '');


/* =========================================================
   CHECK DATA
========================================================= */

if ($project_id <= 0) {
    echo "
    <script>
        alert('ไม่พบโปรเจกต์ที่ต้องการ');
        window.location.href='index2.php';
    </script>
    ";
    exit();
}

if ($comment === '') {
    echo "
    <script>
        alert('กรุณากรอกความคิดเห็น');
        window.history.back();
    </script>
    ";
    exit();
}


/* =========================================================
   CHECK PROJECT
========================================================= */

$sql = "
    SELECT
        id,
        title,
        project_name,
        advisor
    FROM projects
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
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

    echo "
    <script>
        alert('ไม่พบโปรเจกต์นี้');
        window.location.href='index2.php';
    </script>
    ";
    exit();
}

$project = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   GET TEACHER NAME
========================================================= */

$teacher_sql = "
    SELECT
        id,
        first_name,
        last_name
    FROM users
    WHERE id = ?
      AND role = 'teacher'
    LIMIT 1
";

$teacher_stmt = mysqli_prepare(
    $conn,
    $teacher_sql
);

if (!$teacher_stmt) {
    die("เกิดข้อผิดพลาดในการตรวจสอบข้อมูลอาจารย์");
}

mysqli_stmt_bind_param(
    $teacher_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($teacher_stmt);

$teacher_result =
    mysqli_stmt_get_result($teacher_stmt);

if (
    !$teacher_result ||
    mysqli_num_rows($teacher_result) === 0
) {

    mysqli_stmt_close($teacher_stmt);

    echo "
    <script>
        alert('ไม่พบข้อมูลอาจารย์');
        window.location.href='index2.php';
    </script>
    ";
    exit();
}

$teacher = mysqli_fetch_assoc(
    $teacher_result
);

mysqli_stmt_close($teacher_stmt);


/* =========================================================
   CHECK ADVISOR
========================================================= */

$teacher_fullname = trim(
    ($teacher['first_name'] ?? '') .
    ' ' .
    ($teacher['last_name'] ?? '')
);

$advisor = trim(
    $project['advisor'] ?? ''
);


/*
    ทำให้ช่องว่างเหมือนกัน
*/

$teacher_normalized = preg_replace(
    '/\s+/u',
    ' ',
    $teacher_fullname
);

$advisor_normalized = preg_replace(
    '/\s+/u',
    ' ',
    $advisor
);


/*
    ตรวจสอบว่าอาจารย์เป็นที่ปรึกษาของโปรเจกต์หรือไม่
*/

if (
    $advisor_normalized === '' ||
    $teacher_normalized === '' ||
    mb_strtolower(
        $advisor_normalized,
        'UTF-8'
    ) !== mb_strtolower(
        $teacher_normalized,
        'UTF-8'
    )
) {

    echo "
    <script>
        alert('อาจารย์ท่านนี้ไม่ได้เป็นอาจารย์ที่ปรึกษาของโปรเจกต์นี้');
        window.history.back();
    </script>
    ";
    exit();
}


/* =========================================================
   INSERT COMMENT
========================================================= */

/*
    สำคัญ:
    ใช้ teacher_id ไม่ใช่ user_id
*/

$insert_sql = "
    INSERT INTO project_comments
    (
        project_id,
        teacher_id,
        comment
    )
    VALUES
    (
        ?,
        ?,
        ?
    )
";

$insert_stmt = mysqli_prepare(
    $conn,
    $insert_sql
);

if (!$insert_stmt) {
    die(
        "SQL Error: " .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $insert_stmt,
    "iis",
    $project_id,
    $user_id,
    $comment
);

if (!mysqli_stmt_execute($insert_stmt)) {

    $error = mysqli_error($conn);

    mysqli_stmt_close($insert_stmt);

    die(
        "SQL Error: " .
        htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

mysqli_stmt_close($insert_stmt);


/* =========================================================
   SUCCESS
========================================================= */

echo "
<script>
    alert('ส่งความคิดเห็นเรียบร้อยแล้ว');
    window.location.href='project-detail.php?id={$project_id}';
</script>
";

exit();

?>