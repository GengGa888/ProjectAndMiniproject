<?php
session_start();

include 'db_connect.php';

/* =====================================================
   ตรวจสอบ ID โปรเจกต์
===================================================== */

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: index2.php");
    exit();
}


/* =====================================================
   ดึงข้อมูลโปรเจกต์
===================================================== */

$sql = "SELECT * FROM projects WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
}

mysqli_stmt_bind_param($stmt, "i", $project_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* =====================================================
   ตรวจสอบว่าพบโปรเจกต์หรือไม่
===================================================== */

if (!$result || mysqli_num_rows($result) === 0) {
    ?>

    <!DOCTYPE html>
    <html lang="th">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>ไม่พบโปรเจกต์</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

    </head>

    <body>

        <div
            class="text-center"
            style="
                margin-top:120px;
                font-family:Arial;
            "
        >

            <h2>
                ไม่พบโปรเจกต์
            </h2>

            <p class="text-muted">
                ไม่พบข้อมูลโปรเจกต์ที่ต้องการ
            </p>

            <a
                href="index2.php"
                class="btn btn-primary"
            >
                กลับหน้าแรก
            </a>

        </div>

    </body>

    </html>

    <?php
    exit();
}


/* =====================================================
   เก็บข้อมูล
===================================================== */

$row = mysqli_fetch_assoc($result);


/* =====================================================
   ข้อมูลหลัก
===================================================== */

$title = $row['title'] ?? 'ไม่พบชื่อโปรเจกต์';

$description = $row['description'] ?? 'ไม่มีคำอธิบาย';

$authors = $row['authors'] ?? 'ไม่ระบุผู้แต่ง';

$advisor = $row['advisor'] ?? 'ไม่ระบุ';

$academic_year = $row['academic_year'] ?? '-';

$github_url = trim($row['github_url'] ?? '');

$pdf_file = $row['pdf_file'] ?? '';

$category_name = $row['category_name'] ?? 'เทคโนโลยีสารสนเทศ';

$created_at = '-';


/* =====================================================
   วันที่
===================================================== */

if (!empty($row['created_at'])) {

    $created_timestamp = strtotime($row['created_at']);

    if ($created_timestamp !== false) {

        $created_at = date(
            'd/m/Y',
            $created_timestamp
        );

    }

}


/* =====================================================
   Keywords
===================================================== */

$keywords = [];

if (!empty($row['keywords'])) {

    $keywords = explode(
        ',',
        $row['keywords']
    );

}


/* =====================================================
   รูปปก
===================================================== */

$cover_image =
    'https://ph01.tci-thaijo.org/public/journals/706/cover_issue_17385_th_TH.png';

if (!empty($row['cover_image'])) {

    $cover_image =
        'uploads/' . basename($row['cover_image']);

}


/* =====================================================
   PDF
===================================================== */

$pdf_path = '';

if (!empty($pdf_file)) {

    $pdf_path =
        'uploads/' . basename($pdf_file);

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

        /* =====================================================
           GENERAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            background: #ffffff;

            font-family:
                'Segoe UI',
                Tahoma,
                Arial,
                sans-serif;

            color: #333;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .custom-header {

            height: 90px;

            background:
                linear-gradient(
                    to right,
                    #4aa4d6,
                    #4297CD,
                    #3287BB
                );

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.12);

        }


        .header-inner {

            max-width: 1280px;

            height: 90px;

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

            padding: 3px;

            transition: 0.25s;

        }


        .logo-link:hover .sdu-logo {

            transform: scale(1.06);

        }


        .home-link {

            display: flex;

            align-items: center;

            gap: 8px;

            color: white;

            text-decoration: none;

            font-size: 18px;

            font-weight: 600;

            padding: 10px 8px;

            transition: 0.25s;

        }


        .home-link:hover {

            color: white;

            transform: translateY(-2px);

            text-shadow:
                0 3px 8px
                rgba(0, 0, 0, 0.2);

        }


        .home-icon {

            font-size: 21px;

        }


        .header-right {

            display: flex;

            align-items: center;

        }


        .profile-icon {

            color: white;

            font-size: 30px;

            text-decoration: none;

            transition: 0.25s;

        }


        .profile-icon:hover {

            color: white;

            transform: scale(1.08);

        }


        /* =====================================================
           MAIN CONTAINER
        ===================================================== */

        .detail-container {

            max-width: 1200px;

            margin: 40px auto;

            padding: 0 20px;

        }


        /* =====================================================
           COVER IMAGE
        ===================================================== */

        .cover-image {

            width: 100%;

            display: block;

            border: 1px solid #ddd;

            border-radius: 8px;

            box-shadow:
                0 3px 10px
                rgba(0, 0, 0, 0.08);

            margin-bottom: 15px;

        }


        /* =====================================================
           PROJECT TITLE
        ===================================================== */

        .detail-title {

            color: #2a7cbd;

            font-size: 32px;

            font-weight: 700;

            line-height: 1.4;

            margin-bottom: 20px;

        }


        /* =====================================================
           SECTION
        ===================================================== */

        .section-heading {

            font-size: 23px;

            font-weight: 700;

            margin-top: 30px;

            margin-bottom: 15px;

        }


        .description-text {

            line-height: 1.8;

            color: #444;

            white-space: pre-line;

        }


        /* =====================================================
           SIDEBAR INFORMATION
        ===================================================== */

        .sidebar-meta {

            border-top: 1px solid #eee;

            padding-top: 15px;

            margin-top: 15px;

        }


        .sidebar-meta-title {

            font-weight: 700;

            margin-bottom: 5px;

        }


        /* =====================================================
           PDF BUTTON
        ===================================================== */

        .btn-pdf-large {

            display: block;

            width: 100%;

            background: #5ab1d8;

            color: white;

            text-align: center;

            text-decoration: none;

            font-weight: 700;

            padding: 11px;

            border-radius: 6px;

            transition: 0.25s;

        }


        .btn-pdf-large:hover {

            background: #4297CD;

            color: white;

            transform: translateY(-2px);

        }


        /* =====================================================
           KEYWORDS
        ===================================================== */

        .keyword-badge {

            background: #f5f8fb;

            color: #555;

            border: 1px solid #ddd;

            padding: 7px 10px;

        }


        /* =====================================================
           GITHUB URL
        ===================================================== */

        .github-url-box {

            margin-bottom: 12px;

            padding: 10px 12px;

            background: #f6f8fa;

            border: 1px solid #ddd;

            border-radius: 6px;

        }


        .github-url {

            display: block;

            color: #4297CD;

            font-size: 14px;

            line-height: 1.5;

            word-break: break-all;

            text-decoration: none;

        }


        .github-url:hover {

            color: #17628f;

            text-decoration: underline;

        }


        /* =====================================================
           GITHUB BUTTON
        ===================================================== */

        .github-button {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            width: 100%;

            padding: 11px 15px;

            background: #24292f;

            color: white;

            text-decoration: none;

            border-radius: 6px;

            font-weight: 600;

            transition: 0.25s;

        }


        .github-button:hover {

            background: #000000;

            color: white;

            transform: translateY(-2px);

        }


        .github-button i {

            font-size: 21px;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .custom-header {

                height: auto;

            }


            .header-inner {

                height: auto;

                padding: 15px 20px;

            }


            .sdu-logo {

                width: 48px;

                height: 48px;

            }


            .home-link {

                font-size: 16px;

            }


            .detail-container {

                margin-top: 25px;

            }


            .detail-title {

                font-size: 26px;

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


        <!-- LEFT -->

        <div class="header-left-area">


            <!-- SDU LOGO -->

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


            <!-- HOME -->

            <a
                href="index2.php"
                class="home-link"
            >

                <i
                    class="bi bi-house-fill home-icon"
                ></i>

                <span>
                    หน้าแรก
                </span>

            </a>

        </div>


        <!-- RIGHT -->

        <div class="header-right">

            <a
                href="profile.php"
                class="profile-icon"
                title="ข้อมูลส่วนตัว"
            >

                <i
                    class="bi bi-person-circle"
                ></i>

            </a>

        </div>

    </div>

</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="detail-container">

    <div class="row">


        <!-- =================================================
             LEFT SIDEBAR
        ================================================== -->

        <div class="col-md-3 mb-4">


            <!-- COVER -->

            <img
                src="<?php echo htmlspecialchars($cover_image); ?>"
                alt="รูปปกโปรเจกต์"
                class="cover-image"
            >


            <!-- PDF -->

            <?php if (!empty($pdf_path)): ?>

                <a
                    href="<?php echo htmlspecialchars($pdf_path); ?>"
                    target="_blank"
                    class="btn-pdf-large mb-3"
                >

                    <i
                        class="bi bi-file-earmark-pdf"
                    ></i>

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

            <div class="sidebar-meta">

                <div class="sidebar-meta-title">

                    เผยแพร่เมื่อ:

                </div>

                <div class="text-muted">

                    <?php
                    echo htmlspecialchars($created_at);
                    ?>

                </div>

            </div>


            <!-- ACADEMIC YEAR -->

            <div class="sidebar-meta">

                <div class="sidebar-meta-title">

                    ปีการศึกษา:

                </div>

                <div class="text-muted">

                    <?php
                    echo htmlspecialchars(
                        $academic_year
                    );
                    ?>

                </div>

            </div>


            <!-- ADVISOR -->

            <div class="sidebar-meta">

                <div class="sidebar-meta-title">

                    อาจารย์ที่ปรึกษา:

                </div>

                <div class="text-muted">

                    <?php
                    echo htmlspecialchars(
                        $advisor
                    );
                    ?>

                </div>

            </div>


            <!-- AUTHORS -->

            <div class="sidebar-meta">

                <div class="sidebar-meta-title">

                    สมาชิกกลุ่ม:

                </div>

                <div
                    class="text-muted"
                    style="line-height:1.6;"
                >

                    <?php

                    echo nl2br(
                        htmlspecialchars(
                            $authors
                        )
                    );

                    ?>

                </div>

            </div>


            <!-- =================================================
                 GITHUB
            ================================================== -->

            <div class="sidebar-meta">

                <div class="sidebar-meta-title">

                    <i
                        class="bi bi-github"
                    ></i>

                    GitHub Repository

                </div>


                <?php if (!empty($github_url)): ?>


                    <!-- แสดงลิงก์เต็ม -->

                    <div class="github-url-box">

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

                    </div>


                    <!-- ปุ่ม GitHub -->

                    <a
                        href="<?php echo htmlspecialchars($github_url); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="github-button"
                    >

                        <i
                            class="bi bi-github"
                        ></i>

                        ดูโปรเจกต์บน GitHub

                    </a>


                <?php else: ?>


                    <div class="text-muted">

                        ยังไม่ได้เพิ่มลิงก์ GitHub

                    </div>


                <?php endif; ?>

            </div>


        </div>



        <!-- =================================================
             RIGHT CONTENT
        ================================================== -->

        <div class="col-md-9 px-md-4">


            <!-- TITLE -->

            <h1 class="detail-title">

                <?php

                echo htmlspecialchars(
                    $title
                );

                ?>

            </h1>


            <!-- KEYWORDS -->

            <?php if (!empty($keywords)): ?>

                <div class="mb-4">

                    <strong>
                        คำสำคัญ:
                    </strong>


                    <div
                        class="d-flex flex-wrap gap-2 mt-2"
                    >

                        <?php foreach ($keywords as $kw): ?>

                            <?php

                            $kw = trim($kw);

                            if ($kw === '') {
                                continue;
                            }

                            ?>

                            <span
                                class="badge keyword-badge"
                            >

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


            <!-- AUTHORS -->

            <p class="fs-5 text-muted mb-1">

                <?php

                echo htmlspecialchars(
                    $authors
                );

                ?>

            </p>


            <!-- UNIVERSITY -->

            <p class="text-muted mb-4">

                มหาวิทยาลัยสวนดุสิต

            </p>


            <!-- DESCRIPTION -->

            <h3 class="section-heading">

                คำอธิบาย / บทคัดย่อ

            </h3>


            <div class="description-text">

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $description
                    )
                );

                ?>

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