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
   USER
========================================================= */

$user_id = (int)$_SESSION['user_id'];

$user_role = $_SESSION['role'] ?? '';


/* =========================================================
   GET COMMENT ID
========================================================= */

$comment_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

$project_id = isset($_GET['project_id'])
    ? (int)$_GET['project_id']
    : 0;


if ($comment_id <= 0) {

    echo "
    <script>
        alert('ไม่พบความคิดเห็นที่ต้องการลบ');
        window.location.href='index2.php';
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
        teacher_id,
        comment
    FROM project_comments
    WHERE id = ?
    LIMIT 1
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    die(
        "SQL Error: " .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $comment_id
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close(
        $stmt
    );

    echo "
    <script>
        alert('ไม่พบความคิดเห็นนี้');
        window.location.href='index2.php';
    </script>
    ";

    exit();
}


$comment =
    mysqli_fetch_assoc(
        $result
    );


mysqli_stmt_close(
    $stmt
);


/* =========================================================
   PROJECT ID
========================================================= */

$comment_project_id =
    (int)$comment['project_id'];


if ($project_id <= 0) {

    $project_id =
        $comment_project_id;
}


/* =========================================================
   CHECK PERMISSION
========================================================= */

$comment_teacher_id =
    (int)$comment['teacher_id'];


/*
 * ADMIN
 * ลบความคิดเห็นของใครก็ได้
 */

if ($user_role === 'admin') {

    $can_delete = true;

}


/*
 * TEACHER
 * ลบเฉพาะความคิดเห็นของตัวเอง
 */

elseif (
    $user_role === 'teacher' &&
    $comment_teacher_id === $user_id
) {

    $can_delete = true;

}


/*
 * STUDENT / GUEST / OTHER
 */

else {

    $can_delete = false;
}


/* =========================================================
   NO PERMISSION
========================================================= */

if (!$can_delete) {

    echo "
    <script>
        alert('คุณไม่มีสิทธิ์ลบความคิดเห็นนี้');
        window.location.href='project-detail.php?id={$project_id}';
    </script>
    ";

    exit();
}


/* =========================================================
   DELETE
========================================================= */

$delete_sql = "
    DELETE FROM project_comments
    WHERE id = ?
    LIMIT 1
";


$delete_stmt =
    mysqli_prepare(
        $conn,
        $delete_sql
    );


if (!$delete_stmt) {

    die(
        "SQL Error: " .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $delete_stmt,
    "i",
    $comment_id
);


if (
    !mysqli_stmt_execute(
        $delete_stmt
    )
) {

    $error =
        mysqli_error($conn);

    mysqli_stmt_close(
        $delete_stmt
    );

    die(
        "SQL Error: " .
        htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


mysqli_stmt_close(
    $delete_stmt
);


/* =========================================================
   SUCCESS
========================================================= */

echo "
<script>
    alert('ลบความคิดเห็นเรียบร้อยแล้ว');
    window.location.href='project-detail.php?id={$project_id}';
</script>
";

exit();

?>