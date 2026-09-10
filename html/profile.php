<?php
session_start();
include 'db_connect.php';

/* ================= CHECK LOGIN ================= */

if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('กรุณาเข้าสู่ระบบก่อน!');
            window.location.href='login.php';
          </script>";
    exit();
}

$user_id = intval($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'student';


/* ================= UPLOAD PROFILE IMAGE ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {

    $file = $_FILES['profile_image'];

    if ($file['error'] === UPLOAD_ERR_OK) {

        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($file['type'], $allowed_types)) {
            echo "<script>
                    alert('กรุณาเลือกไฟล์ JPG, PNG, GIF หรือ WEBP เท่านั้น');
                    window.location.href='profile.php';
                  </script>";
            exit();
        }

        /* จำกัดขนาด 5 MB */

        if ($file['size'] > 5 * 1024 * 1024) {
            echo "<script>
                    alert('รูปภาพต้องมีขนาดไม่เกิน 5 MB');
                    window.location.href='profile.php';
                  </script>";
            exit();
        }

        /* สร้างชื่อไฟล์ใหม่ */

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $new_filename =
            'profile_' .
            $user_id .
            '_' .
            time() .
            '.' .
            $extension;

        $upload_dir = __DIR__ . '/profile_uploads/';
        $upload_path = $upload_dir . $new_filename;

        /* สร้างโฟลเดอร์ถ้ายังไม่มี */

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        /* ย้ายไฟล์ */

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {

            /* ดึงรูปเก่า */

            $old_stmt = $conn->prepare(
                "SELECT profile_image FROM users WHERE id = ? LIMIT 1"
            );

            $old_stmt->bind_param("i", $user_id);
            $old_stmt->execute();

            $old_result = $old_stmt->get_result();
            $old_data = $old_result->fetch_assoc();

            $old_stmt->close();

            $old_image = $old_data['profile_image'] ?? '';

            /* บันทึกชื่อไฟล์ใหม่ */

            $update_stmt = $conn->prepare(
                "UPDATE users
                 SET profile_image = ?
                 WHERE id = ?"
            );

            $update_stmt->bind_param(
                "si",
                $new_filename,
                $user_id
            );

            $update = $update_stmt->execute();

            $update_stmt->close();

            /* ลบรูปเก่า */

            if (
                $update &&
                !empty($old_image) &&
                $old_image !== $new_filename
            ) {

                $old_path =
                    $upload_dir . basename($old_image);

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

                /* ถ้าบันทึก DB ไม่สำเร็จ ลบไฟล์ใหม่ทิ้ง */

                if (file_exists($upload_path)) {
                    unlink($upload_path);
                }

                echo "<script>
                        alert('ไม่สามารถบันทึกรูปโปรไฟล์ลงฐานข้อมูลได้');
                        window.location.href='profile.php';
                      </script>";
                exit();
            }

        } else {

            echo "<script>
                    alert('ไม่สามารถอัปโหลดรูปได้');
                    window.location.href='profile.php';
                  </script>";
            exit();
        }

    } else {

        echo "<script>
                alert('กรุณาเลือกรูปภาพ');
                window.location.href='profile.php';
              </script>";
        exit();
    }
}


/* ================= GET USER ================= */

$user_stmt = $conn->prepare(
    "SELECT *
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

$user_stmt->close();


if (!$user_data) {

    session_destroy();

    echo "<script>
            alert('ไม่พบข้อมูลผู้ใช้งาน');
            window.location.href='login.php';
          </script>";
    exit();
}


/* ================= USER DATA ================= */
/*
   ใช้ชื่อคอลัมน์ให้ตรงกับ DB:
   first_name
   last_name
*/

$firstname = $user_data['first_name'] ?? '';
$lastname = $user_data['last_name'] ?? '';

$username = $user_data['username'] ?? '-';
$email = $user_data['email'] ?? '-';
$role = $user_data['role'] ?? 'student';
$department = $user_data['department'] ?? '-';
$profile_image = $user_data['profile_image'] ?? '';


/* ================= ROLE ================= */

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


/* ================= PROFILE IMAGE ================= */

if (!empty($profile_image)) {

    $profile_image_url =
        'profile_uploads/' .
        rawurlencode(basename($profile_image));

} else {

    $profile_image_url =
        'https://cdn-icons-png.flaticon.com/512/149/149071.png';
}


/* =====================================================
   PROJECTS
   ===================================================== */

/*
   นักศึกษา:
   แสดงเฉพาะโปรเจกต์ที่ student_id ตรงกับ user_id

   อาจารย์:
   แสดงโปรเจกต์ทั้งหมด

   admin:
   แสดงโปรเจกต์ทั้งหมด
*/

if ($role === 'student') {

    $projects_stmt = $conn->prepare(
        "SELECT *
         FROM projects
         WHERE student_id = ?
         ORDER BY id DESC"
    );

    $projects_stmt->bind_param("i", $user_id);

} else {

    $projects_stmt = $conn->prepare(
        "SELECT *
         FROM projects
         ORDER BY id DESC"
    );
}

$projects_stmt->execute();

$projects_query = $projects_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>โปรไฟล์ - คลังโปรเจกต์ SDU</title>

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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


        /* =====================================================
           HEADER
        ===================================================== */

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

            justify-content: space-between;
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

            background:
                rgba(255,255,255,0.15);

            transform: translateY(-1px);
        }


        .home-link i {

            font-size: 20px;
        }


        .header-title {

            font-size: 22px;

            font-weight: 700;

            color: white;
        }


        .header-right {

            display: flex;

            align-items: center;
        }


        .profile-header {

            width: 46px;

            height: 46px;

            border-radius: 50%;

            object-fit: cover;

            border: 3px solid rgba(255,255,255,0.9);

            background: white;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .container-main {

            max-width: 1150px;

            margin: 35px auto 60px;

            padding: 0 20px;
        }


        /* =====================================================
           PAGE TITLE
        ===================================================== */

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


        /* =====================================================
           PROFILE CARD
        ===================================================== */

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


        /* =====================================================
           PROFILE AVATAR
        ===================================================== */

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


        /* =====================================================
           CHANGE PHOTO
        ===================================================== */

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


        /* =====================================================
           PROFILE BODY
        ===================================================== */

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


        /* =====================================================
           ROLE
        ===================================================== */

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


        /* =====================================================
           INFORMATION
        ===================================================== */

        .info-title {

            font-size: 21px;

            font-weight: 700;

            color: #287cab;

            margin-bottom: 16px;
        }


        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

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

            margin-bottom: 6px;
        }


        .info-value {

            font-size: 16px;

            font-weight: 600;

            color: #333;

            word-break: break-word;
        }


        /* =====================================================
           PROJECT SECTION
        ===================================================== */

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


        /* =====================================================
           PROJECT CARD
        ===================================================== */

        .project-card {

            background: white;

            border-radius: 16px;

            padding: 24px 25px;

            margin-bottom: 18px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);

            border-left:
                5px solid #4aa4d6;

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


        /* =====================================================
           DELETE BUTTON
        ===================================================== */

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

            transform: translateY(-1px);
        }


        /* =====================================================
           NO PROJECT
        ===================================================== */

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


        /* =====================================================
           RESPONSIVE
        ===================================================== */

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


            .profile-header {

                width: 40px;

                height: 40px;
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

                padding:
                    72px 22px 30px;
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


        <div class="header-right">

            <img
                src="<?php echo htmlspecialchars($profile_image_url); ?>"
                class="profile-header"
                alt="Profile"
            >

        </div>

    </div>

</header>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container-main">


    <!-- PAGE HEADING -->

    <div class="page-heading">

        <h1>

            <i class="bi bi-person-circle"></i>

            โปรไฟล์ของฉัน

        </h1>

        <p>
            ข้อมูลบัญชีและโปรเจกต์ของผู้ใช้งาน
        </p>

    </div>



    <!-- =================================================
         PROFILE
    ================================================= -->

    <div class="profile-card">


        <div class="profile-cover">


            <!-- PROFILE IMAGE -->

            <div class="profile-avatar">

                <img
                    src="<?php echo htmlspecialchars($profile_image_url); ?>"
                    alt="รูปโปรไฟล์"
                >

            </div>


            <!-- CHANGE PHOTO -->

            <form
                method="POST"
                enctype="multipart/form-data"
                id="profileForm"
            >

                <input
                    type="file"
                    name="profile_image"
                    id="profileImageInput"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    style="display:none;"
                    onchange="document.getElementById('profileForm').submit();"
                >


                <label
                    for="profileImageInput"
                    class="change-photo-btn"
                >

                    <i class="bi bi-camera-fill"></i>

                    เปลี่ยนรูปโปรไฟล์

                </label>

            </form>

        </div>



        <!-- PROFILE BODY -->

        <div class="profile-body">


            <div class="profile-name">

                <?php

                echo htmlspecialchars(
                    trim(
                        $firstname . ' ' . $lastname
                    ) ?: $username
                );

                ?>

            </div>


            <div class="profile-username">

                @<?php echo htmlspecialchars($username); ?>

            </div>


            <!-- ROLE -->

            <div class="role-badge">

                <i class="bi <?php echo $role_icon; ?>"></i>

                <?php echo $role_text; ?>

            </div>



            <!-- ACCOUNT -->

            <div class="info-title">

                <i class="bi bi-person-vcard-fill"></i>

                ข้อมูลบัญชี

            </div>


            <div class="info-grid">


                <!-- EMAIL -->

                <div class="info-box">

                    <div class="info-label">
                        อีเมล
                    </div>

                    <div class="info-value">

                        <?php echo htmlspecialchars($email); ?>

                    </div>

                </div>


                <!-- ROLE -->

                <div class="info-box">

                    <div class="info-label">
                        ประเภทผู้ใช้งาน
                    </div>

                    <div class="info-value">

                        <i class="bi <?php echo $role_icon; ?>"></i>

                        <?php echo $role_text; ?>

                    </div>

                </div>


                <!-- DEPARTMENT -->

                <div class="info-box">

                    <div class="info-label">
                        สาขา / ภาควิชา
                    </div>

                    <div class="info-value">

                        <?php echo htmlspecialchars($department); ?>

                    </div>

                </div>


                <!-- USERNAME -->

                <div class="info-box">

                    <div class="info-label">
                        ชื่อผู้ใช้งาน
                    </div>

                    <div class="info-value">

                        <?php echo htmlspecialchars($username); ?>

                    </div>

                </div>


            </div>

        </div>

    </div>



    <!-- =================================================
         PROJECTS
    ================================================= -->

    <div class="section-header">

        <h2 class="section-title">

            <i class="bi bi-folder-fill"></i>

            โปรเจกต์ของผู้ใช้งาน

        </h2>


        <div class="project-count">

            <?php echo $projects_query->num_rows; ?>

            โปรเจกต์

        </div>

    </div>



    <?php if ($projects_query->num_rows > 0): ?>


        <?php while ($row = $projects_query->fetch_assoc()): ?>


            <div class="project-card">


                <!-- TITLE -->

                <div class="project-title">

                    <a
                        href="project-detail.php?id=<?php echo intval($row['id']); ?>"
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

                        echo htmlspecialchars($display_title);

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
                        : ($row['project_type'] ?? '-')
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
                        $row['department'] ?? '-'
                    );

                    ?>

                </div>


                <!-- AUTHORS -->

                <div class="project-info">

                    <strong>

                        <i class="bi bi-people"></i>

                        ผู้จัดทำ:

                    </strong>

                    <?php

                    echo htmlspecialchars(
                        !empty($row['authors'])
                        ? $row['authors']
                        : ($row['student_name'] ?? '-')
                    );

                    ?>

                </div>


                <!-- BUTTONS -->

                <div class="project-buttons">


                    <!-- PDF -->

                    <?php if (!empty($row['pdf_file'])): ?>

                        <?php

                        $pdf_file =
                            'uploads/' .
                            rawurlencode(
                                basename($row['pdf_file'])
                            );

                        ?>

                        <a
                            href="<?php echo htmlspecialchars($pdf_file); ?>"
                            target="_blank"
                            class="btn btn-primary pdf-btn"
                        >

                            <i class="bi bi-file-earmark-pdf"></i>

                            เปิดไฟล์ PDF

                        </a>

                    <?php endif; ?>


                    <!-- DELETE -->

                    <?php
                    /*
                       นักศึกษาลบได้เฉพาะโปรเจกต์ของตัวเอง
                       อาจารย์/แอดมินไม่ต้องมีปุ่มนี้ในหน้าโปรไฟล์
                    */

                    if (
                        $role === 'student' &&
                        isset($row['student_id']) &&
                        intval($row['student_id']) === $user_id
                    ):
                    ?>

                        <a
                            href="delete-project.php?id=<?php echo intval($row['id']); ?>"
                            class="btn-delete"
                            onclick="return confirm('ต้องการลบโปรเจกต์นี้ใช่หรือไม่?\\n\\nเมื่อลบแล้วจะไม่สามารถกู้คืนได้');"
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



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>
</html>

<?php
$projects_stmt->close();
?>