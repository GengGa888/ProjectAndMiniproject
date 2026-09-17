<?php

session_start();
include 'db_connect.php';

/* =====================================================
   CHECK LOGIN
===================================================== */

if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('กรุณาเข้าสู่ระบบก่อน!');
        window.location.href='login.php';
    </script>";
    exit();
}

$login_user_id = (int)$_SESSION['user_id'];

/* =====================================================
   TARGET PROFILE
===================================================== */

$profile_user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : $login_user_id;

if ($profile_user_id <= 0) {
    $profile_user_id = $login_user_id;
}

$is_own_profile = ($profile_user_id === $login_user_id);

/* =====================================================
   GET USER
===================================================== */

$user_stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$user_stmt) {
    die("ไม่สามารถเตรียมคำสั่งดึงข้อมูลผู้ใช้ได้");
}

$user_stmt->bind_param("i", $profile_user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

$user_stmt->close();

if (!$user_data) {
    echo "<script>
        alert('ไม่พบข้อมูลผู้ใช้งาน');
        window.location.href='index2.php';
    </script>";
    exit();
}

/* =====================================================
   USER DATA
===================================================== */

$firstname = $user_data['first_name'] ?? '';
$lastname = $user_data['last_name'] ?? '';
$username = $user_data['username'] ?? '';
$email = $user_data['email'] ?? '';
$role = $user_data['role'] ?? 'student';
$department = $user_data['department'] ?? '';
$profile_image = $user_data['profile_image'] ?? '';
$prefix = $user_data['prefix'] ?? '';

$display_full_name = trim(
    $prefix . ' ' . $firstname . ' ' . $lastname
);

/* =====================================================
   ROLE
===================================================== */

if ($role === 'teacher') {

    $role_text = 'อาจารย์';
    $role_icon = 'bi-person-workspace';

} elseif ($role === 'admin') {

    $role_text = 'ผู้ดูแลระบบ';
    $role_icon = 'bi-shield-lock-fill';

} else {

    $role_text = 'นักศึกษา';
    $role_icon = 'bi-mortarboard-fill';
}

/* =====================================================
   PROFILE IMAGE
===================================================== */

if (!empty($profile_image)) {

    $profile_image_url =
        'profile_uploads/' .
        rawurlencode(basename($profile_image));

} else {

    $profile_image_url =
        'https://cdn-icons-png.flaticon.com/512/149/149071.png';
}

/* =====================================================
   UPDATE PROFILE
   แก้ได้เฉพาะเจ้าของโปรไฟล์
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    if (!$is_own_profile) {

        echo "<script>
            alert('คุณไม่มีสิทธิ์แก้ไขข้อมูลของผู้ใช้งานคนนี้');
            window.location.href='profile.php?id=" . $profile_user_id . "';
        </script>";

        exit();
    }

    $new_first_name = trim($_POST['first_name'] ?? '');
    $new_last_name = trim($_POST['last_name'] ?? '');
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_department = trim($_POST['department'] ?? '');
    $other_department = trim($_POST['other_department'] ?? '');

    /* =================================================
       OTHER DEPARTMENT
    ================================================= */

    if ($new_department === 'อื่นๆ') {
        $new_department = $other_department;
    }

    /* =================================================
       VALIDATE
    ================================================= */

    if ($new_first_name === '') {
        echo "<script>
            alert('กรุณากรอกชื่อ');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if (mb_strlen($new_first_name, 'UTF-8') > 50) {
        echo "<script>
            alert('ชื่อต้องมีความยาวไม่เกิน 50 ตัวอักษร');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if ($new_last_name === '') {
        echo "<script>
            alert('กรุณากรอกนามสกุล');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if (mb_strlen($new_last_name, 'UTF-8') > 50) {
        echo "<script>
            alert('นามสกุลต้องมีความยาวไม่เกิน 50 ตัวอักษร');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if ($new_username === '') {
        echo "<script>
            alert('กรุณากรอก Username');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if (mb_strlen($new_username, 'UTF-8') > 50) {
        echo "<script>
            alert('Username ต้องมีความยาวไม่เกิน 50 ตัวอักษร');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    /* =================================================
       CHECK USERNAME
    ================================================= */

    $check_username_stmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE username = ?
        AND id != ?
        LIMIT 1
    ");

    if (!$check_username_stmt) {
        echo "<script>
            alert('เกิดข้อผิดพลาดในการตรวจสอบ Username');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    $check_username_stmt->bind_param(
        "si",
        $new_username,
        $login_user_id
    );

    $check_username_stmt->execute();

    $check_username_result =
        $check_username_stmt->get_result();

    if ($check_username_result->num_rows > 0) {

        $check_username_stmt->close();

        echo "<script>
            alert('Username นี้ถูกใช้งานแล้ว กรุณาใช้ชื่ออื่น');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    $check_username_stmt->close();

    /* =================================================
       EMAIL
    ================================================= */

    if ($new_email === '') {
        echo "<script>
            alert('กรุณากรอกอีเมล');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
            alert('รูปแบบอีเมลไม่ถูกต้อง');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if (mb_strlen($new_email, 'UTF-8') > 100) {
        echo "<script>
            alert('อีเมลต้องมีความยาวไม่เกิน 100 ตัวอักษร');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    /* =================================================
       CHECK EMAIL
    ================================================= */

    $check_email_stmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        AND id != ?
        LIMIT 1
    ");

    if (!$check_email_stmt) {
        echo "<script>
            alert('เกิดข้อผิดพลาดในการตรวจสอบอีเมล');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    $check_email_stmt->bind_param(
        "si",
        $new_email,
        $login_user_id
    );

    $check_email_stmt->execute();

    $check_email_result =
        $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {

        $check_email_stmt->close();

        echo "<script>
            alert('อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    $check_email_stmt->close();

    /* =================================================
       DEPARTMENT
    ================================================= */

    if ($new_department === '') {
        echo "<script>
            alert('กรุณาเลือกหรือกรอกสาขา / ภาควิชา');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    if (mb_strlen($new_department, 'UTF-8') > 50) {
        echo "<script>
            alert('สาขา / ภาควิชาต้องมีความยาวไม่เกิน 50 ตัวอักษร');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    /* =================================================
       UPDATE
    ================================================= */

    $update_profile_stmt = $conn->prepare("
        UPDATE users
        SET
            first_name = ?,
            last_name = ?,
            username = ?,
            email = ?,
            department = ?
        WHERE id = ?
    ");

    if (!$update_profile_stmt) {
        echo "<script>
            alert('ไม่สามารถเตรียมคำสั่งแก้ไขข้อมูลได้');
            window.location.href='profile.php';
        </script>";
        exit();
    }

    $update_profile_stmt->bind_param(
        "sssssi",
        $new_first_name,
        $new_last_name,
        $new_username,
        $new_email,
        $new_department,
        $login_user_id
    );

    $profile_updated =
        $update_profile_stmt->execute();

    $update_profile_stmt->close();

    if ($profile_updated) {

        $_SESSION['first_name'] = $new_first_name;
        $_SESSION['last_name'] = $new_last_name;
        $_SESSION['username'] = $new_username;
        $_SESSION['email'] = $new_email;
        $_SESSION['department'] = $new_department;

        echo "<script>
            alert('แก้ไขข้อมูลส่วนตัวเรียบร้อยแล้ว');
            window.location.href='profile.php';
        </script>";

        exit();

    } else {

        echo "<script>
            alert('ไม่สามารถแก้ไขข้อมูลส่วนตัวได้');
            window.location.href='profile.php';
        </script>";

        exit();
    }
}

/* =====================================================
   UPLOAD PROFILE IMAGE
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['profile_image'])
) {

    if (!$is_own_profile) {

        echo "<script>
            alert('คุณไม่มีสิทธิ์เปลี่ยนรูปโปรไฟล์ของผู้ใช้งานคนนี้');
            window.location.href='profile.php?id=" . $profile_user_id . "';
        </script>";

        exit();
    }

    $file = $_FILES['profile_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {

        echo "<script>
            alert('กรุณาเลือกรูปภาพ');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    /* =================================================
       CHECK MIME
    ================================================= */

    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_type = finfo_file(
        $finfo,
        $file['tmp_name']
    );
    finfo_close($finfo);

    if (!in_array($real_type, $allowed_types, true)) {

        echo "<script>
            alert('กรุณาเลือกไฟล์ JPG, PNG, GIF หรือ WEBP เท่านั้น');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    /* =================================================
       SIZE
    ================================================= */

    if ($file['size'] > 5 * 1024 * 1024) {

        echo "<script>
            alert('รูปภาพต้องมีขนาดไม่เกิน 5 MB');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    /* =================================================
       EXTENSION
    ================================================= */

    $extension_map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    $extension =
        $extension_map[$real_type];

    $new_filename =
        'profile_' .
        $login_user_id .
        '_' .
        time() .
        '.' .
        $extension;

    $upload_dir =
        __DIR__ . '/profile_uploads/';

    $upload_path =
        $upload_dir . $new_filename;

    if (!is_dir($upload_dir)) {

        if (!mkdir($upload_dir, 0777, true)) {

            echo "<script>
                alert('ไม่สามารถสร้างโฟลเดอร์เก็บรูปได้');
                window.location.href='profile.php';
            </script>";

            exit();
        }
    }

    /* =================================================
       MOVE
    ================================================= */

    if (!move_uploaded_file(
        $file['tmp_name'],
        $upload_path
    )) {

        echo "<script>
            alert('ไม่สามารถอัปโหลดรูปได้');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    /* =================================================
       OLD IMAGE
    ================================================= */

    $old_stmt = $conn->prepare("
        SELECT profile_image
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$old_stmt) {

        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        echo "<script>
            alert('ไม่สามารถตรวจสอบรูปเดิมได้');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    $old_stmt->bind_param(
        "i",
        $login_user_id
    );

    $old_stmt->execute();

    $old_result =
        $old_stmt->get_result();

    $old_data =
        $old_result->fetch_assoc();

    $old_stmt->close();

    $old_image =
        $old_data['profile_image'] ?? '';

    /* =================================================
       SAVE NEW IMAGE
    ================================================= */

    $update_stmt = $conn->prepare("
        UPDATE users
        SET profile_image = ?
        WHERE id = ?
    ");

    if (!$update_stmt) {

        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        echo "<script>
            alert('ไม่สามารถบันทึกรูปโปรไฟล์ได้');
            window.location.href='profile.php';
        </script>";

        exit();
    }

    $update_stmt->bind_param(
        "si",
        $new_filename,
        $login_user_id
    );

    $update =
        $update_stmt->execute();

    $update_stmt->close();

    /* =================================================
       DELETE OLD
    ================================================= */

    if (
        $update &&
        !empty($old_image) &&
        $old_image !== $new_filename
    ) {

        $old_path =
            $upload_dir .
            basename($old_image);

        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }

    if ($update) {

        echo "<script>
            alert('เปลี่ยนรูปโปรไฟล์เรียบร้อยแล้ว');
            window.location.href='profile.php';
        </script>";

        exit();

    } else {

        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        echo "<script>
            alert('ไม่สามารถบันทึกรูปโปรไฟล์ลงฐานข้อมูลได้');
            window.location.href='profile.php';
        </script>";

        exit();
    }
}

/* =====================================================
   DEPARTMENT OPTIONS
===================================================== */

$department_options = [
    'เทคโนโลยีสารสนเทศ',
    'วิทยาการคอมพิวเตอร์',
    'เทคโนโลยีดิจิทัล',
    'คอมพิวเตอร์ธุรกิจ',
    'มัลติมีเดีย',
    'วิทยาศาสตร์สิ่งแวดล้อม',
    'เทคโนโลยีการประกอบอาหาร'
];

$is_known_department =
    in_array(
        $department,
        $department_options,
        true
    );

$department_select_value =
    $is_known_department
        ? $department
        : 'อื่นๆ';

$other_department_value =
    $is_known_department
        ? ''
        : $department;

/* =====================================================
   PROJECTS
   สำคัญ:
   แสดงโปรเจกต์ของเจ้าของ
   และโปรเจกต์ที่ชื่อของเจ้าของอยู่ใน authors
===================================================== */

/*
   ชื่อสำหรับค้นหาใน authors
*/
$full_name_no_prefix =
    trim($firstname . ' ' . $lastname);

$full_name_with_prefix =
    trim($prefix . ' ' . $firstname . ' ' . $lastname);

$authors_name_1 =
    '%' . $full_name_no_prefix . '%';

$authors_name_2 =
    '%' . $full_name_with_prefix . '%';

$authors_username =
    '%' . $username . '%';

/*
   ใช้ student_id หรือ authors
*/
$projects_sql = "
    SELECT *
    FROM projects
    WHERE
        student_id = ?
        OR (
            authors IS NOT NULL
            AND authors <> ''
            AND (
                authors LIKE ?
                OR authors LIKE ?
                OR (
                    ? <> ''
                    AND authors LIKE ?
                )
            )
        )
    ORDER BY id DESC
";

$projects_stmt =
    $conn->prepare($projects_sql);

if (!$projects_stmt) {

    die(
        "ไม่สามารถดึงข้อมูลโปรเจกต์ได้: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$projects_stmt->bind_param(
    "issss",
    $profile_user_id,
    $authors_name_1,
    $authors_name_2,
    $username,
    $authors_username
);

$projects_stmt->execute();

$projects_query =
    $projects_stmt->get_result();

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
        โปรไฟล์ -
        <?php
        echo htmlspecialchars(
            $display_full_name !== ''
                ? $display_full_name
                : $username,
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
        - คลังโปรเจกต์ SDU
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
            font-family:
                "Sarabun",
                "Segoe UI",
                Arial,
                sans-serif;
            background: #f4f8fb;
            color: #333;
        }

        /* ================= HEADER ================= */

        .custom-header {
            height: 90px;
            background:
                linear-gradient(
                    135deg,
                    #4aa4d6 0%,
                    #4297CD 50%,
                    #3287BB 100%
                );
            color: white;
            box-shadow:
                0 4px 15px rgba(0,0,0,0.12);
        }

        .header-inner {
            max-width: 1280px;
            height: 100%;
            margin: auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
        }

        .header-left-area {
            display: flex;
            align-items: center;
            gap: 18px;
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
            padding: 4px;
            box-shadow:
                0 3px 10px rgba(0,0,0,0.12);
        }

        .header-title {
            font-size: 22px;
            font-weight: 700;
            color: white;
        }

        .home-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-size: 17px;
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 9px;
            transition: 0.2s;
        }

        .home-link:hover {
            color: white;
            background: rgba(255,255,255,0.15);
        }

        .home-link i {
            font-size: 20px;
        }

        /* ================= MAIN ================= */

        .container-main {
            max-width: 1150px;
            margin: 35px auto 60px;
            padding: 0 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0;
            color: #1f6f9f;
            font-size: 30px;
            font-weight: 700;
        }

        .page-heading p {
            margin: 7px 0 0;
            color: #777;
            font-size: 15px;
        }

        /* ================= NOTICE ================= */

        .view-only-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff8e6;
            border: 1px solid #f1dfaa;
            color: #856404;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .view-only-notice i {
            font-size: 19px;
        }

        /* ================= PROFILE ================= */

        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow:
                0 5px 20px rgba(0,0,0,0.07);
            margin-bottom: 35px;
        }

        .profile-cover {
            height: 165px;
            background:
                linear-gradient(
                    135deg,
                    #4aa4d6,
                    #3287BB
                );
            position: relative;
        }

        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: white;
            border: 6px solid white;
            position: absolute;
            left: 45px;
            bottom: -65px;
            overflow: hidden;
            box-shadow:
                0 5px 18px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .change-photo-btn {
            position: absolute;
            bottom: 15px;
            left: 190px;
            background: white;
            color: #287cab;
            border: none;
            border-radius: 9px;
            padding: 9px 15px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow:
                0 3px 12px rgba(0,0,0,0.15);
            transition: 0.2s;
        }

        .change-photo-btn:hover {
            background: #f1f8fc;
            transform: translateY(-2px);
        }

        .profile-body {
            padding: 82px 45px 40px;
        }

        .profile-name {
            font-size: 30px;
            font-weight: 700;
            color: #222;
            margin-bottom: 5px;
        }

        .profile-username {
            color: #777;
            font-size: 15px;
            margin-bottom: 15px;
        }

        /* ================= ROLE ================= */

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 30px;
            background: #e8f4fc;
            color: #287cab;
            font-weight: 600;
            margin-bottom: 28px;
        }

        /* ================= INFO ================= */

        .info-title {
            font-size: 21px;
            font-weight: 700;
            color: #287cab;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-box {
            background: #f8fbfd;
            border: 1px solid #e3edf3;
            border-radius: 12px;
            padding: 18px;
            transition: 0.2s;
        }

        .info-box:hover {
            border-color: #b9dced;
            background: #f4fafe;
        }

        .info-label {
            font-size: 14px;
            color: #777;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            word-break: break-word;
        }

        /* ================= INPUT ================= */

        .account-input,
        .account-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8e1e7;
            border-radius: 8px;
            background: white;
            color: #333;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
        }

        .account-input:focus,
        .account-select:focus {
            border-color: #4297CD;
            box-shadow:
                0 0 0 3px rgba(66,151,205,0.12);
        }

        .other-department-input {
            display: none;
            margin-top: 10px;
        }

        .other-department-input.show {
            display: block;
        }

        /* ================= SAVE ================= */

        .save-profile-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 18px;
            padding: 10px 18px;
            border: none;
            border-radius: 9px;
            background: #4297CD;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .save-profile-btn:hover {
            background: #287cab;
            transform: translateY(-1px);
        }

        /* ================= PROJECT ================= */

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 25px;
            font-weight: 700;
            color: #287cab;
            margin: 0;
        }

        .section-title i {
            margin-right: 5px;
        }

        .project-count {
            background: #e8f4fc;
            color: #287cab;
            padding: 6px 13px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* ================= PROJECT CARD ================= */

        .project-card {
            background: white;
            border-radius: 16px;
            padding: 24px 25px;
            margin-bottom: 18px;
            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);
            border-left: 5px solid #4aa4d6;
            transition: 0.2s;
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow:
                0 8px 22px rgba(0,0,0,0.09);
        }

        .project-title {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .project-title-link {
            color: #287cab;
            text-decoration: none;
            transition: 0.2s;
        }

        .project-title-link:hover {
            color: #185d84;
            text-decoration: underline;
        }

        .project-title-link i {
            margin-right: 6px;
        }

        .project-info {
            margin-bottom: 8px;
            color: #555;
            font-size: 15px;
            line-height: 1.6;
        }

        .project-info strong {
            color: #444;
        }

        .project-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .pdf-btn {
            border-radius: 8px;
            padding: 8px 14px;
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            font-size: 14px;
            border: none;
            transition: 0.2s;
        }

        .btn-delete:hover {
            background: #b02a37;
            color: white;
        }

        .btn-edit-project {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            background: #4297cd;
            color: white;
            text-decoration: none;
            font-size: 14px;
            border: none;
            transition: 0.2s;
        }

        .btn-edit-project:hover {
            background: #287cab;
            color: white;
        }

        /* ================= NO PROJECT ================= */

        .no-project {
            background: white;
            padding: 50px 30px;
            text-align: center;
            border-radius: 16px;
            color: #777;
            box-shadow:
                0 4px 15px rgba(0,0,0,0.05);
        }

        .no-project i {
            color: #8bbbd5;
        }

        .no-project p {
            font-size: 16px;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 700px) {

            .custom-header {
                height: 75px;
            }

            .header-inner {
                padding: 0 15px;
            }

            .sdu-logo {
                width: 45px;
                height: 45px;
            }

            .header-title {
                display: none;
            }

            .home-link {
                font-size: 14px;
                padding: 8px 10px;
            }

            .container-main {
                margin-top: 25px;
                padding: 0 15px;
            }

            .page-heading h1 {
                font-size: 25px;
            }

            .profile-cover {
                height: 145px;
            }

            .profile-avatar {
                width: 110px;
                height: 110px;
                left: 25px;
                bottom: -55px;
            }

            .change-photo-btn {
                left: 145px;
                bottom: 12px;
                padding: 7px 10px;
                font-size: 13px;
            }

            .profile-body {
                padding: 72px 22px 30px;
            }

            .profile-name {
                font-size: 25px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                align-items: flex-start;
                gap: 10px;
                flex-direction: column;
            }

            .section-title {
                font-size: 22px;
            }

            .project-card {
                padding: 20px;
            }

            .save-profile-btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<!-- =====================================================
     HEADER
===================================================== -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">

            <a
                href="index2.php"
                class="logo-link"
            >

                <img
                    src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                    class="sdu-logo"
                    alt="SDU Logo"
                >

            </a>

            <div class="header-title">
                คลังโปรเจกต์ SDU
            </div>

            <a
                href="index2.php"
                class="home-link"
            >

                <i class="bi bi-house-fill"></i>

                หน้าแรก

            </a>

        </div>

    </div>

</header>

<!-- =====================================================
     MAIN
===================================================== -->

<div class="container-main">

    <div class="page-heading">

        <h1>

            <i class="bi bi-person-circle"></i>

            <?php if ($is_own_profile): ?>

                โปรไฟล์ของฉัน

            <?php else: ?>

                โปรไฟล์ผู้ใช้งาน

            <?php endif; ?>

        </h1>

        <p>

            <?php if ($is_own_profile): ?>

                ข้อมูลบัญชีและโปรเจกต์ของคุณ

            <?php else: ?>

                ข้อมูลโปรไฟล์และโปรเจกต์ของผู้ใช้งาน

            <?php endif; ?>

        </p>

    </div>

    <!-- VIEW ONLY -->

    <?php if (!$is_own_profile): ?>

        <div class="view-only-notice">

            <i class="bi bi-eye-fill"></i>

            โปรไฟล์นี้เป็นโหมดดูข้อมูลอย่างเดียว
            ไม่สามารถแก้ไขข้อมูลของผู้ใช้งานได้

        </div>

    <?php endif; ?>

    <!-- =================================================
         PROFILE CARD
    ================================================== -->

    <div class="profile-card">

        <div class="profile-cover">

            <div class="profile-avatar">

                <img
                    src="<?php
                    echo htmlspecialchars(
                        $profile_image_url,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>"
                    alt="รูปโปรไฟล์"
                >

            </div>

            <!-- CHANGE PHOTO -->

            <?php if ($is_own_profile): ?>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="profileImageForm"
                >

                    <input
                        type="file"
                        name="profile_image"
                        id="profileImageInput"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        style="display:none;"
                        onchange="
                            document
                            .getElementById('profileImageForm')
                            .submit();
                        "
                    >

                    <label
                        for="profileImageInput"
                        class="change-photo-btn"
                    >

                        <i class="bi bi-camera-fill"></i>

                        เปลี่ยนรูปโปรไฟล์

                    </label>

                </form>

            <?php endif; ?>

        </div>

        <div class="profile-body">

            <!-- NAME -->

            <div class="profile-name">

                <?php
                echo htmlspecialchars(
                    $display_full_name !== ''
                        ? $display_full_name
                        : $username,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

            <!-- USERNAME -->

            <div class="profile-username">

                @<?php
                echo htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

            <!-- ROLE -->

            <div class="role-badge">

                <i class="bi <?php echo $role_icon; ?>"></i>

                <?php
                echo htmlspecialchars(
                    $role_text,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

            <!-- ACCOUNT -->

            <div class="info-title">

                <i class="bi bi-person-vcard-fill"></i>

                ข้อมูลบัญชี

            </div>

            <!-- =================================================
                 OWN PROFILE
            ================================================== -->

            <?php if ($is_own_profile): ?>

                <form
                    method="POST"
                    id="profileUpdateForm"
                >

                    <div class="info-grid">

                        <!-- FIRST NAME -->

                        <div class="info-box">

                            <div class="info-label">

                                <i class="bi bi-person-fill"></i>

                                ชื่อ

                            </div>

                            <input
                                type="text"
                                name="first_name"
                                class="account-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $firstname,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                maxlength="50"
                                required
                            >

                        </div>

                        <!-- LAST NAME -->

                        <div class="info-box">

                            <div class="info-label">

                                <i class="bi bi-person-fill"></i>

                                นามสกุล

                            </div>

                            <input
                                type="text"
                                name="last_name"
                                class="account-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $lastname,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                maxlength="50"
                                required
                            >

                        </div>

                        <!-- EMAIL -->

                        <div class="info-box">

                            <div class="info-label">

                                <i class="bi bi-envelope-fill"></i>

                                อีเมล

                            </div>

                            <input
                                type="email"
                                name="email"
                                class="account-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                maxlength="100"
                                required
                            >

                        </div>

                        <!-- ROLE -->

                        <div class="info-box">

                            <div class="info-label">
                                ประเภทผู้ใช้งาน
                            </div>

                            <div class="info-value">

                                <i class="bi <?php echo $role_icon; ?>"></i>

                                <?php
                                echo htmlspecialchars(
                                    $role_text,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </div>

                        </div>

                        <!-- DEPARTMENT -->

                        <div class="info-box">

                            <div class="info-label">

                                <i class="bi bi-building"></i>

                                สาขา / ภาควิชา

                            </div>

                            <select
                                name="department"
                                id="departmentSelect"
                                class="account-select"
                                required
                            >

                                <option
                                    value=""
                                    disabled
                                    <?php
                                    echo $department_select_value === ''
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    -- เลือกสาขา / ภาควิชา --
                                </option>

                                <?php foreach (
                                    $department_options
                                    as $option
                                ): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars(
                                            $option,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>"
                                        <?php
                                        echo
                                            $department_select_value === $option
                                                ? 'selected'
                                                : '';
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $option,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                                <option
                                    value="อื่นๆ"
                                    <?php
                                    echo
                                        $department_select_value === 'อื่นๆ'
                                            ? 'selected'
                                            : '';
                                    ?>
                                >
                                    อื่นๆ
                                </option>

                            </select>

                            <input
                                type="text"
                                name="other_department"
                                id="otherDepartment"
                                class="
                                    account-input
                                    other-department-input
                                    <?php
                                    echo
                                        $department_select_value === 'อื่นๆ'
                                            ? 'show'
                                            : '';
                                    ?>
                                "
                                value="<?php
                                echo htmlspecialchars(
                                    $other_department_value,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                maxlength="50"
                                placeholder="กรอกสาขา / ภาควิชา"
                            >

                        </div>

                        <!-- USERNAME -->

                        <div class="info-box">

                            <div class="info-label">

                                <i class="bi bi-person-badge-fill"></i>

                                ชื่อผู้ใช้งาน (Username)

                            </div>

                            <input
                                type="text"
                                name="username"
                                class="account-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $username,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                maxlength="50"
                                required
                            >

                        </div>

                    </div>

                    <button
                        type="submit"
                        name="update_profile"
                        value="1"
                        class="save-profile-btn"
                    >

                        <i class="bi bi-check2-circle"></i>

                        บันทึกข้อมูลส่วนตัว

                    </button>

                </form>

            <?php else: ?>

                <!-- =================================================
                     OTHER USER - READ ONLY
                ================================================== -->

                <div class="info-grid">

                    <!-- NAME -->

                    <div class="info-box">

                        <div class="info-label">

                            <i class="bi bi-person-fill"></i>

                            ชื่อ

                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $firstname ?: '-',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    </div>

                    <!-- LAST NAME -->

                    <div class="info-box">

                        <div class="info-label">

                            <i class="bi bi-person-fill"></i>

                            นามสกุล

                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $lastname ?: '-',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    </div>

                    <!-- EMAIL -->

                    <div class="info-box">

                        <div class="info-label">

                            <i class="bi bi-envelope-fill"></i>

                            อีเมล

                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $email ?: '-',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    </div>

                    <!-- ROLE -->

                    <div class="info-box">

                        <div class="info-label">
                            ประเภทผู้ใช้งาน
                        </div>

                        <div class="info-value">

                            <i class="bi <?php echo $role_icon; ?>"></i>

                            <?php
                            echo htmlspecialchars(
                                $role_text,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    </div>

                    <!-- DEPARTMENT -->

                    <div class="info-box">

                        <div class="info-label">

                            <i class="bi bi-building"></i>

                            สาขา / ภาควิชา

                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $department ?: '-',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    </div>

                    <!-- USERNAME -->

                    <div class="info-box">

                        <div class="info-label">

                            <i class="bi bi-person-badge-fill"></i>

                            ชื่อผู้ใช้งาน (Username)

                        </div>

                        <div class="info-value">

                            @<?php
                            echo htmlspecialchars(
                                $username,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- =================================================
         PROJECTS
    ================================================== -->

    <div class="section-header">

        <h2 class="section-title">

            <i class="bi bi-folder-fill"></i>

            <?php if ($is_own_profile): ?>

                โปรเจกต์ของฉัน

            <?php else: ?>

                โปรเจกต์ของผู้ใช้งาน

            <?php endif; ?>

        </h2>

        <div class="project-count">

            <?php
            echo $projects_query->num_rows;
            ?>

            โปรเจกต์

        </div>

    </div>

    <?php if ($projects_query->num_rows > 0): ?>

        <?php while (
            $row = $projects_query->fetch_assoc()
        ): ?>

            <div class="project-card">

                <!-- TITLE -->

                <div class="project-title">

                    <a
                        href="project-detail.php?id=<?php
                        echo (int)$row['id'];
                        ?>"
                        class="project-title-link"
                    >

                        <i class="bi bi-file-earmark-text"></i>

                        <?php

                        $display_title =
                            !empty($row['title'])
                                ? $row['title']
                                : (
                                    !empty($row['project_name'])
                                        ? $row['project_name']
                                        : 'ไม่มีชื่อโปรเจกต์'
                                );

                        echo htmlspecialchars(
                            $display_title,
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>

                    </a>

                </div>

                <!-- DEGREE -->

                <div class="project-info">

                    <strong>

                        <i class="bi bi-mortarboard"></i>

                        ระดับการศึกษา:

                    </strong>

                    <?php

                    echo htmlspecialchars(
                        !empty($row['degree'])
                            ? $row['degree']
                            : (
                                $row['project_type']
                                ?? '-'
                            ),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </div>

                <!-- DEPARTMENT -->

                <div class="project-info">

                    <strong>

                        <i class="bi bi-building"></i>

                        สาขา:

                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $row['department'] ?? '-',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </div>

                <!-- AUTHORS -->

                <div class="project-info">

                    <strong>

                        <i class="bi bi-people"></i>

                        ผู้จัดทำ:

                    </strong>

                    <div style="margin-top:5px;">

                        <?php

                        $display_authors =
                            !empty($row['authors'])
                                ? $row['authors']
                                : (
                                    $row['student_name']
                                    ?? '-'
                                );

                        echo nl2br(
                            htmlspecialchars(
                                $display_authors,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );

                        ?>

                    </div>

                </div>

                <!-- BUTTONS -->

                <div class="project-buttons">

                    <!-- PDF -->

                    <?php if (!empty($row['pdf_file'])): ?>

                        <a
                            href="download-pdf.php?id=<?php
                            echo (int)$row['id'];
                            ?>"
                            class="btn btn-primary pdf-btn"
                        >

                            <i class="bi bi-file-earmark-pdf"></i>

                            เปิดไฟล์ PDF

                        </a>

                    <?php endif; ?>

                    <!-- OWNER ACTIONS -->

                    <?php if (
                        $is_own_profile &&
                        $role === 'student' &&
                        isset($row['student_id']) &&
                        (int)$row['student_id'] === $login_user_id
                    ): ?>

                        <a
                            href="edit-project.php?id=<?php
                            echo (int)$row['id'];
                            ?>"
                            class="btn-edit-project"
                        >

                            <i class="bi bi-pencil-square"></i>

                            แก้ไขโปรเจกต์

                        </a>

                        <a
                            href="delete-project.php?id=<?php
                            echo (int)$row['id'];
                            ?>"
                            class="btn-delete"
                            onclick="
                                return confirm(
                                    'ต้องการลบโปรเจกต์นี้ใช่หรือไม่?\n\nเมื่อลบแล้วจะไม่สามารถกู้คืนได้'
                                );
                            "
                        >

                            <i class="bi bi-trash-fill"></i>

                            ลบโปรเจกต์

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="no-project">

            <i
                class="bi bi-folder-x"
                style="font-size:48px;"
            ></i>

            <p class="mt-3 mb-0">

                ยังไม่มีโปรเจกต์

            </p>

        </div>

    <?php endif; ?>

</div>

<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

const departmentSelect =
    document.getElementById('departmentSelect');

const otherDepartment =
    document.getElementById('otherDepartment');

function updateDepartmentInput() {

    if (
        !departmentSelect ||
        !otherDepartment
    ) {
        return;
    }

    if (
        departmentSelect.value === 'อื่นๆ'
    ) {

        otherDepartment.classList.add('show');
        otherDepartment.required = true;

    } else {

        otherDepartment.classList.remove('show');
        otherDepartment.required = false;

    }
}

if (departmentSelect) {

    departmentSelect.addEventListener(
        'change',
        updateDepartmentInput
    );

    updateDepartmentInput();
}

</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>

<?php

$projects_stmt->close();

?>