<?php
session_start();
include 'db_connect.php';

/* ==============================
   PROJECT ID
============================== */

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: index2.php");
    exit();
}


/* ==============================
   GET PROJECT
============================== */

$sql = "SELECT * FROM projects WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
}

mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: index2.php");
    exit();
}

$row = mysqli_fetch_assoc($result);


/* ==============================
   DATA
============================== */

$title = $row['title'] ?? 'ไม่พบชื่อโปรเจกต์';

$description = $row['description'] ?? 'ไม่มีคำอธิบาย';

$authors = $row['authors'] ?? 'ไม่ระบุผู้แต่ง';

$advisor = $row['advisor'] ?? 'ไม่ระบุ';

$academic_year = $row['academic_year'] ?? '-';

$github_url = trim($row['github_url'] ?? '');

$pdf_file = $row['pdf_file'] ?? '';

$created_at = '-';


/* ==============================
   DATE
============================== */

if (!empty($row['created_at'])) {

    $timestamp = strtotime($row['created_at']);

    if ($timestamp !== false) {
        $created_at = date('d/m/Y', $timestamp);
    }
}


/* ==============================
   KEYWORDS
============================== */

$keywords = [];

if (!empty($row['keywords'])) {
    $keywords = explode(',', $row['keywords']);
}


/* ==============================
   PDF
============================== */

$pdf_path = '';

if (!empty($pdf_file)) {
    $pdf_path = 'uploads/' . basename($pdf_file);
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
        <?php echo htmlspecialchars($title); ?>
        - คลังโปรเจกต์ SDU
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

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


        /* =========================================
           HEADER
        ========================================= */

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

            transition: 0.25s;

        }


        .sdu-logo:hover {

            transform: scale(1.06);

        }


        .home-link {

            display: flex;

            align-items: center;

            gap: 9px;

            color: white;

            text-decoration: none;

            font-size: 18px;

            font-weight: 600;

            transition: 0.25s;

        }


        .home-link:hover {

            color: white;

            transform: translateY(-2px);

        }


        .home-icon {

            font-size: 22px;

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

            transition: 0.25s;

        }


        .profile-icon:hover {

            color: white;

            background:
                rgba(255,255,255,0.15);

            transform: scale(1.05);

        }


        /* =========================================
           MAIN
        ========================================= */

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


        /* =========================================
           LEFT SIDE
        ========================================= */

        .side-panel {

            height: 100%;

            background:
                linear-gradient(
                    180deg,
                    #f7fbfe,
                    #ffffff
                );

            border: 1px solid #e4edf3;

            border-radius: 14px;

            padding: 22px;

        }


        .side-title {

            font-size: 17px;

            font-weight: 700;

            color: #245c7d;

            margin-bottom: 18px;

        }


        /* =========================================
           PDF
        ========================================= */

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

            box-shadow:
                0 5px 15px
                rgba(53, 138, 189, 0.25);

            transition: 0.25s;

            margin-bottom: 20px;

        }


        .pdf-button:hover {

            color: white;

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px
                rgba(53, 138, 189, 0.32);

        }


        .pdf-button i {

            font-size: 20px;

        }


        /* =========================================
           INFO ITEM
        ========================================= */

        .info-item {

            padding: 15px 0;

            border-top: 1px solid #e7eef2;

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

        }


        /* =========================================
           GITHUB
        ========================================= */

        .github-card {

            margin-top: 18px;

            padding: 17px;

            background: #f8fafc;

            border: 1px solid #e1e7ec;

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


        .github-title i {

            font-size: 21px;

        }


        .github-url {

            display: block;

            padding: 10px;

            background: white;

            border: 1px solid #dce4e9;

            border-radius: 8px;

            color: #3287BB;

            font-size: 13px;

            line-height: 1.5;

            word-break: break-all;

            text-decoration: none;

            margin-bottom: 10px;

        }


        .github-url:hover {

            color: #17628f;

            text-decoration: underline;

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

            transition: 0.25s;

        }


        .github-button:hover {

            background: #111;

            color: white;

            transform: translateY(-1px);

        }


        /* =========================================
           RIGHT CONTENT
        ========================================= */

        .project-content {

            padding-left: 25px;

        }


        .project-category {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            background: #eaf6fc;

            color: #3287BB;

            border: 1px solid #cce9f7;

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


        /* =========================================
           KEYWORDS
        ========================================= */

        .keyword-section {

            padding: 18px 0;

            border-top: 1px solid #edf1f3;

            border-bottom: 1px solid #edf1f3;

            margin-bottom: 28px;

        }


        .keyword-title {

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 10px;

            color: #526b79;

        }


        .keyword-list {

            display: flex;

            flex-wrap: wrap;

            gap: 8px;

        }


        .keyword-badge {

            background: #f3f8fb;

            color: #3d6579;

            border: 1px solid #dbe9f0;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 13px;

        }


        /* =========================================
           DESCRIPTION
        ========================================= */

        .description-box {

            background: #fbfdfe;

            border: 1px solid #edf2f5;

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


        /* =========================================
           MOBILE
        ========================================= */

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


            .home-icon {

                font-size: 19px;

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


<!-- =========================================
     HEADER
========================================= -->

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


        <a
            href="profile.php"
            class="profile-icon"
            title="ข้อมูลส่วนตัว"
        >

            <i class="bi bi-person-circle"></i>

        </a>

    </div>

</header>



<!-- =========================================
     MAIN
========================================= -->

<div class="detail-container">

    <div class="project-card">

        <div class="project-card-body">

            <div class="row g-4">


                <!-- =================================
                     LEFT
                ================================== -->

                <div class="col-md-4">

                    <div class="side-panel">


                        <div class="side-title">

                            <i class="bi bi-info-circle"></i>

                            ข้อมูลโปรเจกต์

                        </div>


                        <!-- PDF -->

                        <?php if (!empty($pdf_path)): ?>

                            <a
                                href="<?php echo htmlspecialchars($pdf_path); ?>"
                                target="_blank"
                                class="pdf-button"
                            >

                                <i class="bi bi-file-earmark-pdf"></i>

                                ดูไฟล์ PDF

                            </a>

                        <?php else: ?>

                            <button
                                class="btn btn-secondary w-100 mb-3"
                                disabled
                            >

                                ไม่มีไฟล์ PDF

                            </button>

                        <?php endif; ?>


                        <!-- DATE -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-calendar3"></i>

                                เผยแพร่เมื่อ

                            </div>

                            <div class="info-value">

                                <?php
                                echo htmlspecialchars($created_at);
                                ?>

                            </div>

                        </div>


                        <!-- YEAR -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-mortarboard"></i>

                                ปีการศึกษา

                            </div>

                            <div class="info-value">

                                <?php
                                echo htmlspecialchars($academic_year);
                                ?>

                            </div>

                        </div>


                        <!-- ADVISOR -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-person-workspace"></i>

                                อาจารย์ที่ปรึกษา

                            </div>

                            <div class="info-value">

                                <?php
                                echo htmlspecialchars($advisor);
                                ?>

                            </div>

                        </div>


                        <!-- AUTHORS -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-people"></i>

                                สมาชิกกลุ่ม

                            </div>

                            <div class="info-value">

                                <?php

                                echo nl2br(
                                    htmlspecialchars($authors)
                                );

                                ?>

                            </div>

                        </div>


                        <!-- =================================
                             GITHUB
                        ================================== -->

                        <div class="github-card">


                            <div class="github-title">

                                <i class="bi bi-github"></i>

                                GitHub Repository

                            </div>


                            <?php if (!empty($github_url)): ?>


                                <a
                                    href="<?php echo htmlspecialchars($github_url); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="github-url"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $github_url
                                    );

                                    ?>

                                </a>


                                <a
                                    href="<?php echo htmlspecialchars($github_url); ?>"
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



                <!-- =================================
                     RIGHT
                ================================== -->

                <div class="col-md-8">

                    <div class="project-content">


                        <!-- CATEGORY -->

                        <div class="project-category">

                            <i class="bi bi-folder2-open"></i>

                            โปรเจกต์นักศึกษา

                        </div>


                        <!-- TITLE -->

                        <h1 class="project-title">

                            <?php

                            echo htmlspecialchars(
                                $title
                            );

                            ?>

                        </h1>


                        <!-- UNIVERSITY -->

                        <div class="university-text">

                            <i class="bi bi-building"></i>

                            มหาวิทยาลัยสวนดุสิต

                        </div>


                        <!-- KEYWORDS -->

                        <?php if (!empty($keywords)): ?>

                            <div class="keyword-section">

                                <div class="keyword-title">

                                    <i class="bi bi-tags"></i>

                                    คำสำคัญ

                                </div>


                                <div class="keyword-list">

                                    <?php foreach ($keywords as $kw): ?>

                                        <?php

                                        $kw = trim($kw);

                                        if ($kw === '') {
                                            continue;
                                        }

                                        ?>

                                        <span class="keyword-badge">

                                            <?php

                                            echo htmlspecialchars(
                                                $kw
                                            );

                                            ?>

                                        </span>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- DESCRIPTION -->

                        <div class="description-box">


                            <div class="description-title">

                                <i class="bi bi-file-text"></i>

                                คำอธิบาย / บทคัดย่อ

                            </div>


                            <p class="description-text">

                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $description
                                    )
                                );

                                ?>

                            </p>


                        </div>


                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>