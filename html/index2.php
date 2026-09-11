<?php

session_start();
require_once "db_connect.php";


// =====================================================
// ตรวจสอบสถานะ Login
// =====================================================

$logged_in = isset($_SESSION['user_id']);

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';


// =====================================================
// รับค่าจากช่องค้นหา
// =====================================================

$keyword = trim($_GET['keyword'] ?? '');
$degree  = $_GET['degree'] ?? 'all';
$major   = $_GET['major'] ?? 'all';


// =====================================================
// SQL เริ่มต้น
// =====================================================

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
        pages,
        pdf_file,
        status,
        student_id,
        github_url
    FROM projects
    WHERE 1=1
";

$params = [];
$types  = "";


// =====================================================
// สิทธิ์การมองเห็นโปรเจกต์
// =====================================================
//
// Guest   = เห็นทั้งหมด
// Student = เห็นทั้งหมด
// Teacher = เห็นทั้งหมด
// Admin   = เห็นทั้งหมด
//
// ทุกคนสามารถดูรายละเอียดโปรเจกต์ได้
// การจำกัดสิทธิ์แก้ไข/ลบ จะตรวจสอบในไฟล์แยก
// =====================================================


// =====================================================
// ค้นหาโปรเจกต์
// =====================================================

if ($keyword !== '') {

    $search = "%" . $keyword . "%";

    $sql .= "
        AND (
            LOWER(project_name) LIKE LOWER(?)
            OR LOWER(title) LIKE LOWER(?)
            OR LOWER(authors) LIKE LOWER(?)
            OR LOWER(description) LIKE LOWER(?)
            OR LOWER(department) LIKE LOWER(?)
            OR LOWER(degree) LIKE LOWER(?)
            OR LOWER(advisor) LIKE LOWER(?)
            OR LOWER(student_name) LIKE LOWER(?)
        )
    ";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "ssssssss";
}


// =====================================================
// กรองระดับการศึกษา
// =====================================================

if ($degree === 'bachelor') {

    $sql .= "
        AND (
            degree = 'ปริญญาตรี'
            OR LOWER(degree) = 'bachelor'
        )
    ";

} elseif ($degree === 'master') {

    $sql .= "
        AND (
            degree = 'ปริญญาโท'
            OR LOWER(degree) = 'master'
        )
    ";

} elseif ($degree === 'doctorate') {

    $sql .= "
        AND (
            degree = 'ปริญญาเอก'
            OR LOWER(degree) = 'doctorate'
        )
    ";
}


// =====================================================
// กรองสาขาวิชา
// =====================================================

if ($major === 'it') {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีสารสนเทศ%'
            OR LOWER(department) LIKE '%information technology%'
            OR department = 'IT'
        )
    ";

} elseif ($major === 'cs') {

    $sql .= "
        AND (
            department LIKE '%วิทยาการคอมพิวเตอร์%'
            OR LOWER(department) LIKE '%computer science%'
            OR department = 'CS'
        )
    ";

} elseif ($major === 'env') {

    $sql .= "
        AND (
            department LIKE '%วิทยาศาสตร์สิ่งแวดล้อม%'
            OR LOWER(department) LIKE '%environmental science%'
        )
    ";

} elseif ($major === 'food') {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีการประกอบอาหาร%'
            OR LOWER(department) LIKE '%food technology%'
        )
    ";
}


// =====================================================
// เรียงจากโปรเจกต์ล่าสุด
// =====================================================

$sql .= "
    ORDER BY id DESC
";


// =====================================================
// Prepare SQL
// =====================================================

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        htmlspecialchars(
            mysqli_error($conn),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


// =====================================================
// Bind Parameters
// =====================================================

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


// =====================================================
// Execute
// =====================================================

if (!mysqli_stmt_execute($stmt)) {

    die(
        "Execute Error: " .
        htmlspecialchars(
            mysqli_stmt_error($stmt),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


// =====================================================
// Get Result
// =====================================================

$result = mysqli_stmt_get_result($stmt);

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
        หน้าแรก - คลังโปรเจกต์ SDU
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

        :root {
            --sdu-pdf-blue: #4aa4d6;
        }


        body {
            background-color: #ffffff;

            font-family:
                'Segoe UI',
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .custom-header {

            background:
                linear-gradient(
                    to right,
                    #4aa4d6,
                    #3287BB
                );

            padding: 8px 0 15px 0;

            border-bottom:
                2px solid #287cab;
        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .profile-image {

            width: 45px;

            height: 45px;

            border-radius: 50%;

            object-fit: cover;

            border: 2px solid white;

            cursor: pointer;

            transition: 0.2s;
        }


        .profile-image:hover {

            opacity: 0.85;

            transform: scale(1.05);
        }


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

        .btn-login {

            min-height: 45px;

            padding:
                0 17px;

            border-radius: 25px;

            background-color:
                rgba(255, 255, 255, 0.18);

            border:
                2px solid white;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            text-decoration: none;

            font-size: 0.95rem;

            font-weight: 500;

            transition: all 0.2s ease;
        }


        .btn-login:hover {

            background-color: white;

            color: #3287BB;

            transform: translateY(-1px);
        }


        /* =====================================================
           UPLOAD BUTTON
        ===================================================== */

        .btn-upload-project {

            width: 45px;

            height: 45px;

            border-radius: 50%;

            background-color:
                rgba(255, 255, 255, 0.2);

            border:
                2px solid white;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            font-size: 1.4rem;

            transition: all 0.2s ease;
        }


        .btn-upload-project:hover {

            background-color: white;

            color: #3287BB;

            transform: scale(1.05);
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .sdu-logo {

            width: 45px;

            height: auto;

            background-color: white;

            border-radius: 50%;

            padding: 2px;
        }


        /* =====================================================
           MENU
        ===================================================== */

        .main-menu .nav-link {

            color: white !important;

            font-size: 1.05rem;

            padding-left: 0;

            margin-right: 20px;

            font-weight: 500;
        }


        .main-menu .nav-link:hover {

            color: #e8f4fc !important;

            text-decoration: underline;
        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .custom-profile-menu {

            background-color: #173f5f;

            border:
                1px solid #285776;

            border-radius: 12px;

            box-shadow:
                0 10px 25px
                rgba(0, 0, 0, 0.3);

            min-width: 220px;

            padding: 8px;
        }


        .custom-profile-menu .dropdown-item {

            color: #b9dced;

            font-size: 0.95rem;

            padding: 10px 14px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            gap: 12px;

            transition: all 0.2s ease;
        }


        .custom-profile-menu
        .dropdown-item:hover {

            background-color: #204b6d;

            color: #ffffff;
        }


        .custom-profile-menu
        .dropdown-item.logout-btn {

            color: #f7768e;
        }


        .custom-profile-menu
        .dropdown-item.logout-btn:hover {

            background-color:
                rgba(247, 118, 142, 0.15);

            color: #ff6c6b;
        }


        .custom-profile-menu
        .dropdown-divider {

            border-color: #285776;

            margin: 6px 0;
        }


        /* =====================================================
           FILTER
        ===================================================== */

        .filter-section {

            background-color: #f4f8fb;

            border:
                1px solid #e9ecef;

            border-radius: 6px;

            padding: 15px 20px;
        }


        /* =====================================================
           PROJECT
        ===================================================== */

        .project-item {

            padding-bottom: 25px;

            margin-bottom: 25px;

            border-bottom:
                1px solid #dee2e6;
        }


        .project-title {

            font-size: 1.1rem;

            color: #3287BB;

            text-decoration: none;

            font-weight: 500;

            line-height: 1.6;
        }


        .project-title:hover {

            text-decoration: underline;

            color: #287cab;
        }


        .author-text {

            font-size: 0.9rem;

            color: #737373;

            margin-bottom: 0.15rem;
        }


        .page-text {

            font-size: 0.85rem;

            color: #737373;
        }


        .description-text {

            font-size: 0.9rem;

            color: #555;

            line-height: 1.6;

            margin-top: 10px;

            margin-bottom: 8px;

            max-width: 950px;
        }


        .description-label {

            color: #555;

            font-weight: 600;
        }


        /* =====================================================
           PDF
        ===================================================== */

        .btn-pdf {

            background-color:
                var(--sdu-pdf-blue);

            border: none;

            color: white;

            border-radius: 4px;

            padding: 4px 16px;

            font-size: 0.9rem;

            text-decoration: none;

            display: inline-block;
        }


        .btn-pdf:hover {

            background-color: #4297CD;

            color: white;
        }


        /* =====================================================
           GITHUB
        ===================================================== */

        .btn-github {

            background-color: #24292f;

            border: none;

            color: white;

            border-radius: 4px;

            padding: 4px 14px;

            font-size: 0.9rem;

            text-decoration: none;

            display: inline-block;

            margin-left: 5px;
        }


        .btn-github:hover {

            background-color: #000000;

            color: white;
        }


        /* =====================================================
           NO PROJECT
        ===================================================== */

        .no-project {

            text-align: center;

            padding: 60px 20px;

            color: #888;
        }


        .no-project i {

            font-size: 50px;

            color: #b9dced;

            margin-bottom: 10px;
        }


        .no-project h5 {

            color: #666;
        }


        /* =====================================================
           GUEST NOTICE
        ===================================================== */

        .guest-notice {

            background-color: #eef8fd;

            border:
                1px solid #d5ebf6;

            color: #527487;

            border-radius: 6px;

            padding: 10px 15px;

            margin-bottom: 20px;

            font-size: 0.9rem;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 576px) {

            .main-menu .nav-link {

                margin-right: 8px;

                font-size: 0.95rem;
            }


            .sdu-logo {

                width: 40px;
            }


            .btn-login {

                min-height: 40px;

                padding: 0 12px;

                font-size: 0.85rem;
            }


            .btn-upload-project {

                width: 40px;

                height: 40px;
            }

        }

    </style>

</head>


<body>


<header class="custom-header">

    <div class="container">

        <div
            class="d-flex justify-content-between align-items-center flex-wrap mt-2"
        >


            <!-- =================================================
                 ซ้าย
            ================================================== -->

            <div class="d-flex align-items-center gap-3">

                <a href="index2.php">

                    <img
                        src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                        alt="SDU Logo"
                        class="sdu-logo"
                    >

                </a>


                <ul class="nav main-menu">

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="index2.php"
                        >

                            <i class="bi bi-house-fill"></i>

                            หน้าแรก

                        </a>

                    </li>


                    <?php if (
                        $logged_in &&
                        $user_role === 'admin'
                    ): ?>

                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="admin.php"
                            >

                                <i
                                    class="bi bi-shield-lock-fill"
                                ></i>

                                Admin

                            </a>

                        </li>

                    <?php endif; ?>

                </ul>

            </div>


            <!-- =================================================
                 ขวา
            ================================================== -->

            <div
                class="d-flex align-items-center gap-3"
            >


                <?php if ($logged_in): ?>


                    <?php if (
                        $user_role === 'student' ||
                        $user_role === 'teacher' ||
                        $user_role === 'admin'
                    ): ?>

                        <a
                            href="create.php"
                            class="btn-upload-project"
                            title="ส่งโปรเจกต์"
                        >

                            <i class="bi bi-plus-lg"></i>

                        </a>

                    <?php endif; ?>


                    <div class="dropdown">

                        <a
                            href="#"
                            role="button"
                            id="profileDropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <img
                                src="https://cdn-icons-png.flaticon.com/512/149/149071.png"
                                alt="โปรไฟล์"
                                class="profile-image"
                            >

                        </a>


                        <ul
                            class="dropdown-menu dropdown-menu-end custom-profile-menu mt-2"
                        >


                            <li>

                                <a
                                    class="dropdown-item"
                                    href="profile.php"
                                >

                                    <i
                                        class="bi bi-person-fill"
                                    ></i>

                                    <span>
                                        ข้อมูลส่วนตัว
                                    </span>

                                </a>

                            </li>


                            <?php if (
                                $user_role === 'admin'
                            ): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="admin.php"
                                    >

                                        <i
                                            class="bi bi-shield-lock-fill"
                                        ></i>

                                        <span>
                                            จัดการระบบ
                                        </span>

                                    </a>

                                </li>

                            <?php endif; ?>


                            <li>

                                <hr
                                    class="dropdown-divider"
                                >

                            </li>


                            <li>

                                <a
                                    class="dropdown-item logout-btn"
                                    href="logout.php"
                                >

                                    <i
                                        class="bi bi-box-arrow-right"
                                    ></i>

                                    <span>
                                        ออกจากระบบ
                                    </span>

                                </a>

                            </li>

                        </ul>

                    </div>


                <?php else: ?>


                    <a
                        href="login.php"
                        class="btn-login"
                        title="เข้าสู่ระบบ"
                    >

                        <i
                            class="bi bi-box-arrow-in-right"
                        ></i>

                        เข้าสู่ระบบ

                    </a>


                <?php endif; ?>


            </div>

        </div>

    </div>

</header>



<div class="container mt-4 mb-5">


    <!-- =================================================
         แจ้ง Guest
    ================================================== -->

    <?php if (!$logged_in): ?>

        <div class="guest-notice">

            <i class="bi bi-eye-fill"></i>

            กำลังเข้าชมในฐานะบุคคลทั่วไป
            สามารถค้นหาและดูรายละเอียดโปรเจกต์ได้
            แต่ไม่สามารถลง แก้ไข หรือลบโปรเจกต์ได้

        </div>

    <?php endif; ?>



    <!-- =================================================
         ค้นหา
    ================================================== -->

    <div class="filter-section mb-4">

        <form
            action="index2.php"
            method="GET"
            class="row g-3 align-items-end"
        >


            <div class="col-md-4">

                <label
                    for="searchKeyword"
                    class="form-label fw-bold text-secondary mb-1"
                >

                    ค้นหาโปรเจกต์:

                </label>


                <input
                    type="text"
                    name="keyword"
                    class="form-control form-control-sm"
                    id="searchKeyword"
                    placeholder="พิมพ์ชื่อโปรเจกต์..."
                    value="<?php
                        echo htmlspecialchars(
                            $keyword,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                >

            </div>


            <div class="col-md-3">

                <label
                    for="degreeSelect"
                    class="form-label fw-bold text-secondary mb-1"
                >

                    ระดับหลักสูตร:

                </label>


                <select
                    name="degree"
                    class="form-select form-select-sm"
                    id="degreeSelect"
                >

                    <option
                        value="all"
                        <?= $degree === 'all'
                            ? 'selected'
                            : '' ?>
                    >
                        ทุกระดับการศึกษา
                    </option>


                    <option
                        value="bachelor"
                        <?= $degree === 'bachelor'
                            ? 'selected'
                            : '' ?>
                    >
                        ปริญญาตรี
                    </option>


                    <option
                        value="master"
                        <?= $degree === 'master'
                            ? 'selected'
                            : '' ?>
                    >
                        ปริญญาโท
                    </option>


                    <option
                        value="doctorate"
                        <?= $degree === 'doctorate'
                            ? 'selected'
                            : '' ?>
                    >
                        ปริญญาเอก
                    </option>

                </select>

            </div>


            <div class="col-md-3">

                <label
                    for="majorSelect"
                    class="form-label fw-bold text-secondary mb-1"
                >

                    สาขาวิชา:

                </label>


                <select
                    name="major"
                    class="form-select form-select-sm"
                    id="majorSelect"
                >

                    <option
                        value="all"
                        <?= $major === 'all'
                            ? 'selected'
                            : '' ?>
                    >
                        ทุกสาขาวิชา
                    </option>


                    <option
                        value="it"
                        <?= $major === 'it'
                            ? 'selected'
                            : '' ?>
                    >
                        เทคโนโลยีสารสนเทศ
                    </option>


                    <option
                        value="cs"
                        <?= $major === 'cs'
                            ? 'selected'
                            : '' ?>
                    >
                        วิทยาการคอมพิวเตอร์
                    </option>


                    <option
                        value="env"
                        <?= $major === 'env'
                            ? 'selected'
                            : '' ?>
                    >
                        วิทยาศาสตร์สิ่งแวดล้อม
                    </option>


                    <option
                        value="food"
                        <?= $major === 'food'
                            ? 'selected'
                            : '' ?>
                    >
                        เทคโนโลยีการประกอบอาหาร
                    </option>

                </select>

            </div>


            <div class="col-md-2">

                <button
                    type="submit"
                    class="btn btn-primary btn-sm w-100"
                >

                    <i class="bi bi-search"></i>

                    ค้นหา

                </button>

            </div>

        </form>

    </div>



    <!-- =================================================
         จำนวน
    ================================================== -->

    <div class="mb-3 text-secondary">

        <small>

            พบโปรเจกต์

            <strong>
                <?= mysqli_num_rows($result) ?>
            </strong>

            รายการ


            <?php if ($keyword !== ''): ?>

                สำหรับคำค้นหา

                <strong>
                    "<?= htmlspecialchars(
                        $keyword,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                </strong>

            <?php endif; ?>

        </small>

    </div>



    <!-- =================================================
         แสดงโปรเจกต์
    ================================================== -->

    <?php if (
        mysqli_num_rows($result) > 0
    ): ?>


        <?php while (
            $project =
            mysqli_fetch_assoc($result)
        ): ?>


            <div class="project-item">


                <!-- =========================================
                     ชื่อโปรเจกต์
                ========================================== -->

                <a
                    href="project-detail.php?id=<?= (int)$project['id'] ?>"
                    class="project-title"
                >

                    <?php

                    $display_title =
                        trim(
                            $project['title'] ?? ''
                        );


                    if (
                        $display_title === ''
                    ) {

                        $display_title =
                            trim(
                                $project['project_name'] ?? ''
                            );
                    }


                    if (
                        $display_title === ''
                    ) {

                        $display_title =
                            'ไม่ระบุชื่อโปรเจกต์';
                    }


                    echo htmlspecialchars(
                        $display_title,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </a>



                <!-- =========================================
                     ระดับ
                ========================================== -->

                <?php if (
                    !empty($project['degree'])
                ): ?>

                    <span
                        class="badge bg-light text-dark ms-2 border"
                    >

                        <?= htmlspecialchars(
                            $project['degree'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                <?php endif; ?>



                <!-- =========================================
                     สาขา
                ========================================== -->

                <?php if (
                    !empty($project['department'])
                ): ?>

                    <span
                        class="badge bg-info text-dark ms-1"
                    >

                        <?= htmlspecialchars(
                            $project['department'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                <?php endif; ?>



                <!-- =========================================
                     สมาชิก
                ========================================== -->

                <?php if (
                    !empty($project['authors'])
                ): ?>

                    <p class="author-text mt-2">

                        <?= htmlspecialchars(
                            $project['authors'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                <?php endif; ?>



                <!-- =========================================
                     อาจารย์
                ========================================== -->

                <?php if (
                    !empty($project['advisor'])
                ): ?>

                    <p class="author-text">

                        <?= htmlspecialchars(
                            $project['advisor'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                <?php endif; ?>



                <!-- =========================================
                     คำอธิบาย
                ========================================== -->

                <?php if (
                    !empty($project['description'])
                ): ?>

                    <p class="description-text">

                        <span
                            class="description-label"
                        >
                            คำอธิบาย:
                        </span>


                        <?php

                        $description =
                            trim(
                                $project['description']
                            );


                        if (
                            mb_strlen(
                                $description,
                                'UTF-8'
                            ) > 250
                        ) {

                            $description =
                                mb_substr(
                                    $description,
                                    0,
                                    250,
                                    'UTF-8'
                                ) . '...';
                        }


                        echo nl2br(
                            htmlspecialchars(
                                $description,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );

                        ?>

                    </p>

                <?php endif; ?>



                <!-- =========================================
                     จำนวนหน้า
                ========================================== -->

                <?php if (
                    !empty($project['pages'])
                ): ?>

                    <p class="page-text">

                        <?= htmlspecialchars(
                            $project['pages'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                <?php endif; ?>



                <!-- =========================================
                     PDF
                ========================================== -->

                <?php if (
                    !empty($project['pdf_file'])
                ): ?>

                    <a
                        href="uploads/<?= rawurlencode(
                            basename(
                                $project['pdf_file']
                            )
                        ) ?>"
                        target="_blank"
                        class="btn btn-pdf mt-1"
                    >

                        <i
                            class="bi bi-file-earmark-pdf"
                        ></i>

                        PDF

                    </a>

                <?php endif; ?>



                <!-- =========================================
                     GitHub
                ========================================== -->

                <?php if (
                    !empty($project['github_url'])
                ): ?>

                    <a
                        href="<?= htmlspecialchars(
                            $project['github_url'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn-github"
                    >

                        <i class="bi bi-github"></i>

                        GitHub

                    </a>

                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <div class="no-project">

            <i class="bi bi-folder-x"></i>


            <h5>
                ไม่พบโปรเจกต์
            </h5>


            <?php if ($keyword !== ''): ?>

                <p>

                    ไม่พบโปรเจกต์ที่ตรงกับ

                    "<strong>
                        <?= htmlspecialchars(
                            $keyword,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>"

                </p>

            <?php else: ?>

                <p>
                    ยังไม่มีโปรเจกต์ที่ตรงกับข้อมูลที่ค้นหา
                </p>

            <?php endif; ?>

        </div>


    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>