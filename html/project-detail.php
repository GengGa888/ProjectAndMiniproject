<?php

session_start();
require_once 'db_connect.php';

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
 * ลบคำนำหน้าออกจากชื่อ เพื่อใช้ตรวจสอบว่า
 * เป็นคนเดียวกับอาจารย์ที่ปรึกษาหรือไม่
 */
function normalize_teacher_name($name)
{
    $name = trim($name);

    // จัดช่องว่าง
    $name = preg_replace('/\s+/u', ' ', $name);

    // คำนำหน้าที่ระบบรองรับ
    $prefixes = [
        'ศาสตราจารย์ ดร.',
        'รองศาสตราจารย์ ดร.',
        'ผู้ช่วยศาสตราจารย์ ดร.',
        'ศ.ดร.',
        'รศ.ดร.',
        'ผศ.ดร.',
        'ศ.',
        'รศ.',
        'ผศ.',
        'อ.',
        'ดร.',
        'นาย',
        'นางสาว',
        'นาง'
    ];

    foreach ($prefixes as $prefix) {

        if (mb_strpos($name, $prefix, 0, 'UTF-8') === 0) {

            $name = mb_substr(
                $name,
                mb_strlen($prefix, 'UTF-8'),
                null,
                'UTF-8'
            );

            $name = trim($name);

            break;
        }
    }

    return mb_strtolower($name, 'UTF-8');
}


/* =========================================================
   LOGIN
========================================================= */

$logged_in = isset($_SESSION['user_id']);

$user_id = $logged_in
    ? (int)$_SESSION['user_id']
    : 0;

$user_role = $_SESSION['role'] ?? '';

$session_first_name = trim($_SESSION['first_name'] ?? '');
$session_last_name  = trim($_SESSION['last_name'] ?? '');


/* =========================================================
   PROJECT ID
========================================================= */

$project_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($project_id <= 0) {
    header("Location: index2.php");
    exit();
}


/* =========================================================
   GET PROJECT
========================================================= */

$sql = "
    SELECT
        id,
        project_name,
        project_type,
        student_name,
        advisor,
        description,
        created_at,
        title,
        degree,
        department,
        authors,
        pdf_file,
        status,
        student_id,
        github_url
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
        alert('ไม่พบโปรเจกต์ที่ต้องการ');
        window.location.href='index2.php';
    </script>
    ";

    exit();
}

$row = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   PROJECT DATA
========================================================= */

$title = trim($row['title'] ?? '');

if ($title === '') {
    $title = trim($row['project_name'] ?? '');
}

if ($title === '') {
    $title = 'ไม่พบชื่อโปรเจกต์';
}


$description = trim($row['description'] ?? '');

if ($description === '') {
    $description = 'ไม่มีคำอธิบายหรือบทคัดย่อ';
}


$authors = trim($row['authors'] ?? '');

if ($authors === '') {
    $authors = trim($row['student_name'] ?? '');
}

if ($authors === '') {
    $authors = 'ไม่ระบุผู้แต่ง';
}


$advisor = trim($row['advisor'] ?? '');

if ($advisor === '') {
    $advisor = 'ไม่ระบุ';
}


$degree = trim($row['degree'] ?? '');

if ($degree === '') {
    $degree = trim($row['project_type'] ?? '');
}

if ($degree === '') {
    $degree = '-';
}


$department = trim($row['department'] ?? '');

if ($department === '') {
    $department = '-';
}


$status = trim($row['status'] ?? '');

if ($status === '') {
    $status = 'ส่งแล้ว';
}


/* =========================================================
   GITHUB
========================================================= */

$github_url = trim($row['github_url'] ?? '');


/* =========================================================
   PDF
========================================================= */

$pdf_file = trim($row['pdf_file'] ?? '');

$pdf_path = '';

if ($pdf_file !== '') {

    $safe_pdf = basename($pdf_file);

    if ($safe_pdf !== '') {
        $pdf_path = 'uploads/' . $safe_pdf;
    }
}


/* =========================================================
   DATE
========================================================= */

$created_at = '-';

if (!empty($row['created_at'])) {

    $timestamp = strtotime($row['created_at']);

    if ($timestamp !== false) {
        $created_at = date('d/m/Y', $timestamp);
    }
}


/* =========================================================
   OWNER
========================================================= */

$student_id = isset($row['student_id'])
    ? (int)$row['student_id']
    : 0;

$is_owner = (
    $logged_in &&
    $user_id > 0 &&
    $student_id > 0 &&
    $student_id === $user_id
);


/* =========================================================
   PERMISSION
========================================================= */

$can_edit = false;
$can_delete = false;

if ($logged_in) {

    if ($user_role === 'admin') {

        $can_edit = true;
        $can_delete = true;

    } elseif ($user_role === 'student' && $is_owner) {

        $can_delete = true;
    }
}


/* =========================================================
   GET CURRENT USER
   ใช้ตรวจสอบอาจารย์ที่ปรึกษาโดยตรง
========================================================= */

$current_user_prefix = '';
$current_user_first_name = $session_first_name;
$current_user_last_name = $session_last_name;

if ($logged_in && $user_id > 0) {

    $user_sql = "
        SELECT
            prefix,
            first_name,
            last_name,
            role
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    $user_stmt = mysqli_prepare($conn, $user_sql);

    if ($user_stmt) {

        mysqli_stmt_bind_param(
            $user_stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute($user_stmt);

        $user_result = mysqli_stmt_get_result($user_stmt);

        if ($user_result && mysqli_num_rows($user_result) > 0) {

            $current_user = mysqli_fetch_assoc($user_result);

            $current_user_prefix =
                trim($current_user['prefix'] ?? '');

            $current_user_first_name =
                trim($current_user['first_name'] ?? '');

            $current_user_last_name =
                trim($current_user['last_name'] ?? '');
        }

        mysqli_stmt_close($user_stmt);
    }
}


/* =========================================================
   CURRENT TEACHER FULL NAME
========================================================= */

$current_teacher_fullname = trim(
    $current_user_prefix . ' ' .
    $current_user_first_name . ' ' .
    $current_user_last_name
);


/* =========================================================
   TEACHER COMMENT PERMISSION
========================================================= */

$can_comment = false;

if (
    $logged_in &&
    $user_role === 'teacher'
) {

    /*
     * เปรียบเทียบชื่อโดยตัดคำนำหน้าออก
     *
     * เช่น
     * โปรเจกต์ = ผศ.ดร.สมชาย ใจดี
     * ผู้ล็อกอิน = ผศ.ดร.สมชาย ใจดี
     *
     * หรือ
     * โปรเจกต์ = สมชาย ใจดี
     * ผู้ล็อกอิน = อ.สมชาย ใจดี
     *
     * จะถือว่าเป็นคนเดียวกัน
     */

    $advisor_normalized =
        normalize_teacher_name($advisor);

    $teacher_normalized =
        normalize_teacher_name($current_teacher_fullname);

    if (
        $advisor_normalized !== '' &&
        $teacher_normalized !== '' &&
        $advisor_normalized === $teacher_normalized
    ) {

        $can_comment = true;
    }
}


/* =========================================================
   PROJECT TYPE
========================================================= */

$project_type_label = trim(
    $row['project_type'] ?? ''
);

if ($project_type_label === '') {
    $project_type_label = 'โปรเจกต์นักศึกษา';
}


/* =========================================================
   GET COMMENTS
========================================================= */

$comments = [];

$comment_sql = "
    SELECT
        pc.id,
        pc.project_id,
        pc.teacher_id,
        pc.comment,
        pc.created_at,
        u.prefix,
        u.first_name,
        u.last_name
    FROM project_comments pc
    LEFT JOIN users u
        ON pc.teacher_id = u.id
    WHERE pc.project_id = ?
    ORDER BY pc.id DESC
";

$comment_stmt = mysqli_prepare(
    $conn,
    $comment_sql
);

if ($comment_stmt) {

    mysqli_stmt_bind_param(
        $comment_stmt,
        "i",
        $project_id
    );

    mysqli_stmt_execute(
        $comment_stmt
    );

    $comment_result =
        mysqli_stmt_get_result(
            $comment_stmt
        );

    if ($comment_result) {

        while (
            $comment_row =
            mysqli_fetch_assoc(
                $comment_result
            )
        ) {

            $comments[] = $comment_row;
        }
    }

    mysqli_stmt_close(
        $comment_stmt
    );
}

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo e($title); ?> - คลังโปรเจกต์ SDU
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            background:
                linear-gradient(
                    180deg,
                    #f5f9fc 0%,
                    #ffffff 35%
                );

            font-family:
                'Segoe UI',
                Tahoma,
                Arial,
                sans-serif;

            color: #263238;
        }


        /* HEADER */

        .custom-header {
            height: 90px;

            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #4297CD,
                    #3287BB
                );

            box-shadow:
                0 4px 18px
                rgba(38, 119, 164, 0.20);
        }


        .header-inner {
            max-width: 1280px;
            height: 90px;

            margin: auto;
            padding: 0 25px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }


        .header-left-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }


        .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
        }


        .sdu-logo {
            width: 58px;
            height: 58px;

            object-fit: contain;

            background: white;
            border-radius: 50%;
            padding: 3px;

            box-shadow:
                0 3px 10px
                rgba(0,0,0,0.15);
        }


        .home-link {
            display: flex;
            align-items: center;

            gap: 9px;

            color: white;
            text-decoration: none;

            font-size: 18px;
            font-weight: 600;
        }


        .home-link:hover {
            color: white;
        }


        .home-icon {
            font-size: 22px;
        }


        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .profile-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 45px;
            height: 45px;

            color: white;
            font-size: 30px;

            text-decoration: none;
            border-radius: 50%;
        }


        .profile-icon:hover {
            color: white;
            background: rgba(255,255,255,0.15);
        }


        .login-button {
            display: flex;
            align-items: center;

            gap: 7px;

            color: white;
            text-decoration: none;

            border:
                1px solid
                rgba(255,255,255,0.5);

            padding: 9px 15px;
            border-radius: 9px;

            font-size: 14px;
            font-weight: 600;
        }


        .login-button:hover {
            color: white;
            background: rgba(255,255,255,0.15);
        }


        /* MAIN */

        .detail-container {
            max-width: 1200px;

            margin: 45px auto;
            padding: 0 20px;
        }


        .project-card {
            background: white;

            border-radius: 18px;
            border: 1px solid #e5edf3;

            box-shadow:
                0 8px 30px
                rgba(48, 105, 139, 0.10);

            overflow: hidden;
        }


        .project-card-body {
            padding: 35px;
        }


        /* SIDE */

        .side-panel {
            height: 100%;

            background:
                linear-gradient(
                    180deg,
                    #f7fbfe,
                    #ffffff
                );

            border:
                1px solid #e4edf3;

            border-radius: 14px;

            padding: 22px;
        }


        .side-title {
            font-size: 17px;
            font-weight: 700;

            color: #245c7d;

            margin-bottom: 18px;
        }


        /* PDF */

        .pdf-button {
            width: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 10px;

            padding: 13px 15px;

            background:
                linear-gradient(
                    135deg,
                    #58b4df,
                    #358abd
                );

            color: white;

            border-radius: 10px;

            text-decoration: none;

            font-weight: 700;

            margin-bottom: 20px;

            transition: 0.2s;
        }


        .pdf-button:hover {
            color: white;
            transform: translateY(-2px);
        }


        .pdf-button i {
            font-size: 20px;
        }


        /* INFO */

        .info-item {
            padding: 15px 0;

            border-top:
                1px solid #e7eef2;
        }


        .info-label {
            display: flex;
            align-items: center;

            gap: 8px;

            color: #527184;

            font-size: 13px;
            font-weight: 600;

            margin-bottom: 6px;
        }


        .info-label i {
            color: #4297CD;
            font-size: 16px;
        }


        .info-value {
            color: #263238;

            font-size: 14px;
            line-height: 1.6;

            word-break: break-word;
        }


        /* GITHUB */

        .github-card {
            margin-top: 18px;

            padding: 17px;

            background: #f8fafc;

            border:
                1px solid #e1e7ec;

            border-radius: 12px;
        }


        .github-title {
            display: flex;
            align-items: center;

            gap: 8px;

            font-weight: 700;

            color: #24292f;

            margin-bottom: 10px;
        }


        .github-url {
            display: block;

            padding: 10px;

            background: white;

            border:
                1px solid #dce4e9;

            border-radius: 8px;

            color: #3287BB;

            font-size: 13px;

            word-break: break-all;

            text-decoration: none;

            margin-bottom: 10px;
        }


        .github-button {
            width: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 10px;

            background: #24292f;

            color: white;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;
        }


        .github-button:hover {
            background: #111;
            color: white;
        }


        /* CONTENT */

        .project-content {
            padding-left: 25px;
        }


        .project-category {
            display: inline-flex;
            align-items: center;

            gap: 7px;

            background: #eaf6fc;
            color: #3287BB;

            border:
                1px solid #cce9f7;

            padding: 7px 13px;

            border-radius: 30px;

            font-size: 13px;
            font-weight: 600;

            margin-bottom: 16px;
        }


        .project-title {
            font-size: 34px;
            line-height: 1.35;

            font-weight: 750;

            color: #174f70;

            margin: 0 0 15px;
        }


        .university-text {
            display: flex;
            align-items: center;

            gap: 8px;

            color: #7a8d98;

            font-size: 14px;

            margin-bottom: 25px;
        }


        .university-text i {
            color: #4297CD;
        }


        /* DESCRIPTION */

        .description-box {
            background: #fbfdfe;

            border:
                1px solid #edf2f5;

            border-radius: 12px;

            padding: 22px;
        }


        .description-title {
            display: flex;
            align-items: center;

            gap: 9px;

            font-size: 20px;
            font-weight: 700;

            color: #245c7d;

            margin-bottom: 13px;
        }


        .description-title i {
            color: #4297CD;
        }


        .description-text {
            color: #53636c;

            font-size: 15px;
            line-height: 1.9;

            white-space: pre-line;

            margin: 0;
        }


        /* ACTION */

        .admin-actions {
            display: flex;

            gap: 10px;

            margin-top: 20px;

            flex-wrap: wrap;
        }


        .edit-button,
        .delete-button {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 10px 16px;

            color: white;

            border-radius: 9px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;
        }


        .edit-button {
            background: #4297CD;
        }


        .edit-button:hover {
            background: #3287BB;
            color: white;
        }


        .delete-button {
            background: #dc3545;
        }


        .delete-button:hover {
            background: #bb2d3b;
            color: white;
        }


        /* COMMENTS */

        .comments-box {
            margin-top: 25px;

            background: white;

            border:
                1px solid #e3edf3;

            border-radius: 14px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(48,105,139,0.06);
        }


        .comments-title {
            display: flex;
            align-items: center;

            gap: 9px;

            font-size: 20px;
            font-weight: 700;

            color: #245c7d;

            margin-bottom: 8px;
        }


        .comments-title i {
            color: #4297CD;
        }


        /* ADVISOR NAME */

        .advisor-comment-name {
            display: flex;
            align-items: center;

            gap: 7px;

            color: #4297CD;

            font-size: 14px;
            font-weight: 600;

            margin-bottom: 18px;

            padding-bottom: 14px;

            border-bottom:
                1px solid #e7eef2;
        }


        /* COMMENT FORM */

        .teacher-comment-form {
            background: #f5faff;

            border:
                1px solid #dceef8;

            border-radius: 11px;

            padding: 16px;

            margin-bottom: 20px;
        }


        .teacher-comment-form textarea {
            width: 100%;

            min-height: 120px;

            resize: vertical;

            border:
                1px solid #cedee8;

            border-radius: 9px;

            padding: 12px;

            font-size: 14px;

            outline: none;
        }


        .teacher-comment-form textarea:focus {
            border-color: #4297CD;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,0.12);
        }


        .comment-submit {
            margin-top: 10px;

            background: #4297CD;

            border: none;

            color: white;

            padding: 10px 18px;

            border-radius: 8px;

            font-weight: 600;

            cursor: pointer;
        }


        .comment-submit:hover {
            background: #3287BB;
        }


        /* COMMENT ITEM */

        .comment-item {
            padding: 16px 0;

            border-top:
                1px solid #e7eef2;

            position: relative;
        }


        .comment-author {
            display: flex;
            align-items: center;

            gap: 8px;

            color: #245c7d;

            font-weight: 700;

            margin-bottom: 6px;

            flex-wrap: wrap;
        }


        .comment-author i {
            color: #4297CD;
        }


        .comment-date {
            color: #8999a2;

            font-size: 12px;

            margin-left: 5px;
        }


        .comment-text {
            color: #53636c;

            font-size: 14px;

            line-height: 1.8;

            white-space: pre-line;

            margin: 12px 0 0;
        }


        /* DELETE COMMENT */

        .comment-delete-form {
            margin-top: 12px;
        }


        .comment-delete-button {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 6px;

            border: none;

            background: #fff0f1;

            color: #dc3545;

            border:
                1px solid #f3c6ca;

            padding: 6px 11px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;
        }


        .comment-delete-button:hover {
            background: #dc3545;
            color: white;
        }


        .no-comments {
            color: #8999a2;

            text-align: center;

            padding: 20px 10px;

            font-size: 14px;
        }


        .teacher-only-notice {
            background: #fff8e8;

            border:
                1px solid #f3dfaa;

            color: #80651e;

            border-radius: 9px;

            padding: 11px 13px;

            font-size: 13px;

            margin-bottom: 18px;
        }


        .guest-notice {
            margin-top: 20px;

            padding: 14px 16px;

            background: #eef8fd;

            border:
                1px solid #cfeaf7;

            border-radius: 10px;

            color: #35677f;

            font-size: 13px;

            line-height: 1.6;
        }


        /* MOBILE */

        @media (max-width: 768px) {

            .custom-header {
                height: 75px;
            }

            .header-inner {
                height: 75px;
                padding: 0 15px;
            }

            .header-left-area {
                gap: 12px;
            }

            .sdu-logo {
                width: 48px;
                height: 48px;
            }

            .home-link {
                font-size: 15px;
            }

            .profile-icon {
                font-size: 27px;
            }

            .detail-container {
                margin: 25px auto;
                padding: 0 12px;
            }

            .project-card-body {
                padding: 18px;
            }

            .project-content {
                padding-left: 0;
                margin-top: 25px;
            }

            .project-title {
                font-size: 27px;
            }

            .side-panel {
                padding: 18px;
            }

        }

    </style>

</head>


<body>


<!-- HEADER -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">

            <a
                href="index2.php"
                class="logo-link"
            >

                <img
                    src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                    alt="SDU Logo"
                    class="sdu-logo"
                >

            </a>


            <a
                href="index2.php"
                class="home-link"
            >

                <i class="bi bi-house-fill home-icon"></i>

                <span>
                    หน้าแรก
                </span>

            </a>

        </div>


        <div class="header-right">

            <?php if ($logged_in): ?>

                <a
                    href="profile.php"
                    class="profile-icon"
                    title="ข้อมูลส่วนตัว"
                >

                    <i class="bi bi-person-circle"></i>

                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="login-button"
                >

                    <i class="bi bi-box-arrow-in-right"></i>

                    เข้าสู่ระบบ

                </a>

            <?php endif; ?>

        </div>

    </div>

</header>


<!-- MAIN -->

<div class="detail-container">

    <div class="project-card">

        <div class="project-card-body">

            <div class="row g-4">


                <!-- LEFT -->

                <div class="col-md-4">

                    <div class="side-panel">

                        <div class="side-title">

                            <i class="bi bi-info-circle"></i>

                            ข้อมูลโปรเจกต์

                        </div>


                        <?php if ($pdf_path !== ''): ?>

                            <a
                                href="<?php echo e($pdf_path); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pdf-button"
                            >

                                <i class="bi bi-file-earmark-pdf"></i>

                                ดูไฟล์ PDF

                            </a>

                        <?php else: ?>

                            <button
                                type="button"
                                class="btn btn-secondary w-100 mb-3"
                                disabled
                            >

                                <i class="bi bi-file-earmark-x"></i>

                                ไม่มีไฟล์ PDF

                            </button>

                        <?php endif; ?>


                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-calendar3"></i>

                                เผยแพร่เมื่อ

                            </div>

                            <div class="info-value">

                                <?php echo e($created_at); ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-check-circle"></i>

                                สถานะ

                            </div>

                            <div class="info-value">

                                <?php echo e($status); ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-mortarboard"></i>

                                ระดับการศึกษา

                            </div>

                            <div class="info-value">

                                <?php echo e($degree); ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-building"></i>

                                สาขา / ภาควิชา

                            </div>

                            <div class="info-value">

                                <?php echo e($department); ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-person-workspace"></i>

                                อาจารย์ที่ปรึกษา

                            </div>

                            <div class="info-value">

                                <?php echo e($advisor); ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-people"></i>

                                สมาชิกกลุ่ม

                            </div>

                            <div class="info-value">

                                <?php echo nl2br(e($authors)); ?>

                            </div>

                        </div>


                        <!-- จำนวนหน้าเอาออกแล้ว -->


                        <div class="github-card">

                            <div class="github-title">

                                <i class="bi bi-github"></i>

                                GitHub Repository

                            </div>


                            <?php if ($github_url !== ''): ?>

                                <a
                                    href="<?php echo e($github_url); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="github-url"
                                >

                                    <?php echo e($github_url); ?>

                                </a>


                                <a
                                    href="<?php echo e($github_url); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="github-button"
                                >

                                    <i class="bi bi-github"></i>

                                    ดูโปรเจกต์บน GitHub

                                </a>

                            <?php else: ?>

                                <div
                                    class="text-muted"
                                    style="font-size:13px;"
                                >

                                    ยังไม่ได้เพิ่มลิงก์ GitHub

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- RIGHT -->

                <div class="col-md-8">

                    <div class="project-content">


                        <div class="project-category">

                            <i class="bi bi-folder2-open"></i>

                            <?php echo e($project_type_label); ?>

                        </div>


                        <h1 class="project-title">

                            <?php echo e($title); ?>

                        </h1>


                        <div class="university-text">

                            <i class="bi bi-building"></i>

                            มหาวิทยาลัยสวนดุสิต

                        </div>


                        <div class="description-box">

                            <div class="description-title">

                                <i class="bi bi-file-text"></i>

                                คำอธิบาย / บทคัดย่อ

                            </div>


                            <p class="description-text">

                                <?php echo nl2br(e($description)); ?>

                            </p>

                        </div>


                        <!-- COMMENTS -->

                        <div class="comments-box">

                            <div class="comments-title">

                                <i class="bi bi-chat-left-text-fill"></i>

                                ความคิดเห็นจากอาจารย์ที่ปรึกษา

                            </div>


                            <!-- ADVISOR -->

                            <div class="advisor-comment-name">

                                <i class="bi bi-person-workspace"></i>

                                อาจารย์ที่ปรึกษา:

                                <strong>
                                    <?php echo e($advisor); ?>
                                </strong>

                            </div>


                            <!-- TEACHER FORM -->

                            <?php if ($can_comment): ?>

                                <div class="teacher-comment-form">

                                    <div
                                        class="mb-2"
                                        style="
                                            font-size:14px;
                                            font-weight:600;
                                            color:#245c7d;
                                        "
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                        แสดงความคิดเห็นต่อโปรเจกต์นี้

                                    </div>


                                    <form
                                        action="add-comment.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="project_id"
                                            value="<?php echo $project_id; ?>"
                                        >


                                        <textarea
                                            name="comment"
                                            placeholder="พิมพ์ความคิดเห็นหรือคำแนะนำ..."
                                            required
                                        ></textarea>


                                        <button
                                            type="submit"
                                            class="comment-submit"
                                        >

                                            <i class="bi bi-send-fill"></i>

                                            ส่งความคิดเห็น

                                        </button>

                                    </form>

                                </div>


                            <?php elseif (
                                $logged_in &&
                                $user_role === 'teacher'
                            ): ?>

                                <div class="teacher-only-notice">

                                    <i class="bi bi-info-circle"></i>

                                    โปรเจกต์นี้อยู่ในความดูแลของ

                                    <strong>
                                        <?php echo e($advisor); ?>
                                    </strong>

                                    จึงมีเฉพาะอาจารย์ที่ปรึกษาเท่านั้น
                                    ที่สามารถแสดงความคิดเห็นได้

                                </div>

                            <?php endif; ?>


                            <!-- SHOW COMMENTS -->

                            <?php if (count($comments) > 0): ?>

                                <?php foreach ($comments as $comment): ?>

                                    <?php

                                    $comment_prefix =
                                        trim(
                                            $comment['prefix'] ?? ''
                                        );

                                    $comment_first_name =
                                        trim(
                                            $comment['first_name'] ?? ''
                                        );

                                    $comment_last_name =
                                        trim(
                                            $comment['last_name'] ?? ''
                                        );


                                    $comment_teacher = trim(
                                        $comment_prefix . ' ' .
                                        $comment_first_name . ' ' .
                                        $comment_last_name
                                    );


                                    if ($comment_teacher === '') {
                                        $comment_teacher = 'อาจารย์';
                                    }


                                    $comment_date = '-';

                                    if (!empty($comment['created_at'])) {

                                        $comment_timestamp =
                                            strtotime(
                                                $comment['created_at']
                                            );

                                        if (
                                            $comment_timestamp !== false
                                        ) {

                                            $comment_date =
                                                date(
                                                    'd/m/Y H:i',
                                                    $comment_timestamp
                                                );
                                        }
                                    }


                                    $comment_teacher_id =
                                        isset($comment['teacher_id'])
                                        ? (int)$comment['teacher_id']
                                        : 0;


                                    /*
                                     * ลบได้เมื่อ
                                     *
                                     * Admin = ลบได้ทุกความคิดเห็น
                                     *
                                     * Teacher = ลบความคิดเห็นตัวเอง
                                     */

                                    $can_delete_comment = (
                                        $logged_in &&
                                        (
                                            $user_role === 'admin' ||
                                            (
                                                $user_role === 'teacher' &&
                                                $comment_teacher_id === $user_id
                                            )
                                        )
                                    );

                                    ?>

                                    <div class="comment-item">

                                        <div class="comment-author">

                                            <i class="bi bi-person-workspace"></i>

                                            <?php echo e($comment_teacher); ?>

                                            <span class="comment-date">

                                                <?php echo e($comment_date); ?>

                                            </span>

                                        </div>


                                        <p class="comment-text">

                                            <?php
                                            echo nl2br(
                                                e(
                                                    $comment['comment']
                                                )
                                            );
                                            ?>

                                        </p>


                                        <!-- DELETE COMMENT -->

                                        <?php if ($can_delete_comment): ?>

                                            <form
                                                action="delete-comment.php"
                                                method="POST"
                                                class="comment-delete-form"
                                                onsubmit="return confirm('ต้องการลบความคิดเห็นนี้ใช่หรือไม่?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="comment_id"
                                                    value="<?php echo (int)$comment['id']; ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="project_id"
                                                    value="<?php echo $project_id; ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="comment-delete-button"
                                                >

                                                    <i class="bi bi-trash3"></i>

                                                    ลบความคิดเห็น

                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                <?php endforeach; ?>


                            <?php else: ?>

                                <div class="no-comments">

                                    <i class="bi bi-chat-square-text"></i>

                                    ยังไม่มีความคิดเห็นจากอาจารย์ที่ปรึกษา

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- PROJECT ACTIONS -->

                        <?php if ($can_edit || $can_delete): ?>

                            <div class="admin-actions">

                                <?php if ($can_edit): ?>

                                    <a
                                        href="admin_edit_project.php?id=<?php echo $project_id; ?>"
                                        class="edit-button"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                        แก้ไขโปรเจกต์

                                    </a>

                                <?php endif; ?>


                                <?php if ($can_delete): ?>

                                    <a
                                        href="delete-project.php?id=<?php echo $project_id; ?>"
                                        class="delete-button"
                                        onclick="return confirm('ต้องการลบโปรเจกต์นี้ใช่หรือไม่?\n\nเมื่อลบแล้วจะไม่สามารถกู้คืนได้');"
                                    >

                                        <i class="bi bi-trash3"></i>

                                        ลบโปรเจกต์

                                    </a>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>


                        <?php if (!$logged_in): ?>

                            <div class="guest-notice">

                                <i class="bi bi-eye"></i>

                                บุคคลทั่วไปสามารถดูรายละเอียดโปรเจกต์
                                และอ่านความคิดเห็นจากอาจารย์ที่ปรึกษาได้
                                แต่ไม่สามารถแสดงความคิดเห็นได้

                            </div>

                        <?php endif; ?>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>