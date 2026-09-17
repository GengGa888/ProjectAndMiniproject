<?php
session_start();
require_once "db_connect.php";

/* =====================================================
   ฟังก์ชันป้องกัน XSS
===================================================== */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   ตรวจสอบการเข้าสู่ระบบ
===================================================== */
$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? (int)$_SESSION['user_id'] : 0;
$user_role = $_SESSION['role'] ?? '';

/* =====================================================
   รูปโปรไฟล์
===================================================== */
$profile_image_url = '';

if ($logged_in && $user_id > 0) {

    $profile_stmt = mysqli_prepare(
        $conn,
        "SELECT profile_image FROM users WHERE id = ? LIMIT 1"
    );

    if ($profile_stmt) {

        mysqli_stmt_bind_param(
            $profile_stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute($profile_stmt);

        $profile_result = mysqli_stmt_get_result($profile_stmt);

        if ($profile_result) {

            $profile_row = mysqli_fetch_assoc($profile_result);

            $profile_image =
                trim($profile_row['profile_image'] ?? '');

            if ($profile_image !== '') {

                $safe_profile_image =
                    basename($profile_image);

                $profile_file_path =
                    __DIR__ .
                    DIRECTORY_SEPARATOR .
                    "profile_uploads" .
                    DIRECTORY_SEPARATOR .
                    $safe_profile_image;

                if (is_file($profile_file_path)) {

                    $profile_image_url =
                        "profile_uploads/" .
                        rawurlencode($safe_profile_image);
                }
            }
        }

        mysqli_stmt_close($profile_stmt);
    }
}

/* =====================================================
   รูปโปรไฟล์เริ่มต้น
===================================================== */
if ($profile_image_url === '') {

    $profile_image_url =
        "https://cdn-icons-png.flaticon.com/512/149/149071.png";
}

/* =====================================================
   รับค่าค้นหา
===================================================== */
$keyword = trim($_GET['keyword'] ?? '');

$degree = $_GET['degree'] ?? 'all';

$major = $_GET['major'] ?? 'all';

/* =====================================================
   SQL
===================================================== */
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
$types = "";

/* =====================================================
   ค้นหาโปรเจกต์
===================================================== */
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

    $types = "ssssssss";
}

/* =====================================================
   กรองระดับการศึกษา
===================================================== */
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

/* =====================================================
   กรองสาขาวิชา
===================================================== */
if ($major === 'it') {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีสารสนเทศ%'
            OR LOWER(department)
               LIKE '%information technology%'
            OR department = 'IT'
        )
    ";

} elseif ($major === 'cs') {

    $sql .= "
        AND (
            department LIKE '%วิทยาการคอมพิวเตอร์%'
            OR LOWER(department)
               LIKE '%computer science%'
            OR department = 'CS'
        )
    ";

} elseif ($major === 'env') {

    $sql .= "
        AND (
            department LIKE '%วิทยาศาสตร์สิ่งแวดล้อม%'
            OR LOWER(department)
               LIKE '%environmental science%'
        )
    ";

} elseif ($major === 'food') {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีการประกอบอาหาร%'
            OR LOWER(department)
               LIKE '%food technology%'
        )
    ";
}

/* =====================================================
   เรียงจากโปรเจกต์ล่าสุด
===================================================== */
$sql .= "
    ORDER BY id DESC
";

/* =====================================================
   Prepare
===================================================== */
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        e(mysqli_error($conn))
    );
}

/* =====================================================
   Bind Parameter
===================================================== */
if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}

/* =====================================================
   Execute
===================================================== */
if (!mysqli_stmt_execute($stmt)) {

    die(
        "Execute Error: " .
        e(mysqli_stmt_error($stmt))
    );
}

/* =====================================================
   Result
===================================================== */
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

        :root {

            --sdu-pdf-blue:
                #5ab1d8;
        }


        /* =====================================================
           BODY
        ====================================================== */

        body {

            background:
                #ffffff;

            font-family:
                'Segoe UI',
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .custom-header {

            background:
                linear-gradient(
                    to right,
                    #4da4d9,
                    #2b7bb3
                );

            padding:
                8px 0 15px 0;

            border-bottom:
                2px solid #287cab;
        }


        .sdu-logo {

            width:
                45px;

            height:
                45px;

            object-fit:
                contain;

            background:
                #ffffff;

            border-radius:
                50%;

            padding:
                2px;
        }


        /* =====================================================
           MENU
        ====================================================== */

        .main-menu .nav-link {

            color:
                #ffffff !important;

            font-size:
                1.05rem;

            padding-left:
                0;

            margin-right:
                20px;

            font-weight:
                500;
        }


        .main-menu .nav-link:hover {

            color:
                #e8f4fc !important;

            text-decoration:
                underline;
        }


        /* =====================================================
           PROFILE
        ====================================================== */

        .profile-image {

            width:
                45px;

            height:
                45px;

            border-radius:
                50%;

            object-fit:
                cover;

            border:
                2px solid #ffffff;

            cursor:
                pointer;

            transition:
                0.2s;

            background:
                #ffffff;
        }


        .profile-image:hover {

            opacity:
                0.85;

            transform:
                scale(1.05);
        }


        /* =====================================================
           LOGIN
        ====================================================== */

        .btn-login {

            min-height:
                45px;

            padding:
                0 17px;

            border-radius:
                25px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.18
                );

            border:
                2px solid #ffffff;

            color:
                #ffffff;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                7px;

            text-decoration:
                none;

            font-size:
                0.95rem;

            font-weight:
                500;

            transition:
                all 0.2s ease;
        }


        .btn-login:hover {

            background:
                #ffffff;

            color:
                #3287bb;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           ปุ่มเพิ่มโปรเจกต์
        ====================================================== */

        .btn-upload-project {

            width:
                45px;

            height:
                45px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.2
                );

            border:
                2px solid #ffffff;

            color:
                #ffffff;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            text-decoration:
                none;

            font-size:
                1.4rem;

            transition:
                all 0.2s ease;
        }


        .btn-upload-project:hover {

            background:
                #ffffff;

            color:
                #3287bb;

            transform:
                scale(1.05);
        }


        /* =====================================================
           PROFILE DROPDOWN
        ====================================================== */

        .custom-profile-menu {

            background:
                #173f5f;

            border:
                1px solid #285776;

            border-radius:
                12px;

            box-shadow:
                0 10px 25px
                rgba(
                    0,
                    0,
                    0,
                    0.3
                );

            min-width:
                220px;

            padding:
                8px;
        }


        .custom-profile-menu
        .dropdown-item {

            color:
                #b9dced;

            font-size:
                0.95rem;

            padding:
                10px 14px;

            border-radius:
                8px;

            display:
                flex;

            align-items:
                center;

            gap:
                12px;
        }


        .custom-profile-menu
        .dropdown-item:hover {

            background:
                #204b6d;

            color:
                #ffffff;
        }


        .custom-profile-menu
        .logout-btn {

            color:
                #f7768e;
        }


        .custom-profile-menu
        .dropdown-divider {

            border-color:
                #285776;
        }


        /* =====================================================
           FILTER
        ====================================================== */

        .filter-section {

            background:
                #f4f8fb;

            border:
                1px solid #e9ecef;

            border-radius:
                6px;

            padding:
                15px 20px;
        }


        /* =====================================================
           PROJECT
        ====================================================== */

        .project-item {

            padding-bottom:
                25px;

            margin-bottom:
                25px;

            border-bottom:
                1px solid #dee2e6;
        }


        .project-title {

            font-size:
                1.1rem;

            color:
                #3287bb;

            text-decoration:
                none;

            font-weight:
                500;

            line-height:
                1.6;
        }


        .project-title:hover {

            color:
                #287cab;

            text-decoration:
                underline;
        }


        /* =====================================================
           INFO
        ====================================================== */

        .info-text {

            font-size:
                0.9rem;

            color:
                #737373;

            margin-bottom:
                5px;

            line-height:
                1.7;
        }


        .info-label {

            color:
                #555555;

            font-weight:
                600;
        }


        /* =====================================================
           MEMBER LIST
        ====================================================== */

        .author-list {

            margin-top:
                3px;

            margin-left:
                5px;

            padding-left:
                15px;

            color:
                #737373;

            font-size:
                0.9rem;

            line-height:
                1.8;
        }


        .author-list div {

            margin-bottom:
                2px;
        }


        /* =====================================================
           DESCRIPTION
        ====================================================== */

        .description-text {

            font-size:
                0.9rem;

            color:
                #555555;

            line-height:
                1.6;

            margin-top:
                10px;

            margin-bottom:
                8px;

            max-width:
                950px;
        }


        .description-label {

            color:
                #555555;

            font-weight:
                600;
        }


        .page-text {

            font-size:
                0.85rem;

            color:
                #737373;
        }


        /* =====================================================
           PDF
        ====================================================== */

        .btn-pdf {

            background:
                var(--sdu-pdf-blue);

            border:
                none;

            color:
                #ffffff;

            border-radius:
                4px;

            padding:
                4px 16px;

            font-size:
                0.9rem;

            text-decoration:
                none;

            display:
                inline-block;

            transition:
                all 0.2s ease;
        }


        .btn-pdf:hover {

            background:
                #4297cd;

            color:
                #ffffff;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           GITHUB
        ====================================================== */

        .btn-github {

            background:
                #24292f;

            border:
                none;

            color:
                #ffffff;

            border-radius:
                4px;

            padding:
                4px 14px;

            font-size:
                0.9rem;

            text-decoration:
                none;

            display:
                inline-block;

            margin-left:
                5px;
        }


        .btn-github:hover {

            background:
                #000000;

            color:
                #ffffff;
        }


        /* =====================================================
           GUEST NOTICE
        ====================================================== */

        .guest-notice {

            background:
                #eef8fd;

            border:
                1px solid #d5ebf6;

            color:
                #527487;

            border-radius:
                6px;

            padding:
                10px 15px;

            margin-bottom:
                20px;

            font-size:
                0.9rem;
        }


        /* =====================================================
           NO PROJECT
        ====================================================== */

        .no-project {

            text-align:
                center;

            padding:
                60px 20px;

            color:
                #888888;
        }


        .no-project i {

            font-size:
                50px;

            color:
                #b9dced;
        }


        .no-project h5 {

            color:
                #666666;
        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 576px) {

            .main-menu .nav-link {

                margin-right:
                    8px;

                font-size:
                    0.95rem;
            }


            .sdu-logo {

                width:
                    40px;

                height:
                    40px;
            }


            .btn-login {

                min-height:
                    40px;

                padding:
                    0 12px;

                font-size:
                    0.85rem;
            }


            .btn-upload-project {

                width:
                    40px;

                height:
                    40px;
            }


            .profile-image {

                width:
                    40px;

                height:
                    40px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="custom-header">

    <div class="container">

        <div
            class="
                d-flex
                justify-content-between
                align-items-center
                flex-wrap
                mt-2
            "
        >


            <!-- =================================================
                 ด้านซ้าย
            ================================================== -->

            <div
                class="
                    d-flex
                    align-items-center
                    gap-3
                "
            >

                <a href="index2.php">

                    <img
                        src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                        alt="SDU Logo"
                        class="sdu-logo"
                    >

                </a>


                <ul
                    class="
                        nav
                        main-menu
                    "
                >

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="index2.php"
                        >

                            <i
                                class="
                                    bi
                                    bi-house-fill
                                "
                            ></i>

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
                                    class="
                                        bi
                                        bi-shield-lock-fill
                                    "
                                ></i>

                                Admin

                            </a>

                        </li>

                    <?php endif; ?>

                </ul>

            </div>


            <!-- =================================================
                 ด้านขวา
            ================================================== -->

            <div
                class="
                    d-flex
                    align-items-center
                    gap-3
                "
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

                            <i
                                class="
                                    bi
                                    bi-plus-lg
                                "
                            ></i>

                        </a>

                    <?php endif; ?>


                    <!-- Profile -->

                    <div class="dropdown">

                        <a
                            href="#"
                            role="button"
                            id="profileDropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <img
                                src="<?php echo e($profile_image_url); ?>"
                                alt="โปรไฟล์"
                                class="profile-image"
                            >

                        </a>


                        <ul
                            class="
                                dropdown-menu
                                dropdown-menu-end
                                custom-profile-menu
                                mt-2
                            "
                            aria-labelledby="profileDropdown"
                        >

                            <li>

                                <a
                                    class="dropdown-item"
                                    href="profile.php"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-person-fill
                                        "
                                    ></i>

                                    ข้อมูลส่วนตัว

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
                                            class="
                                                bi
                                                bi-shield-lock-fill
                                            "
                                        ></i>

                                        จัดการระบบ

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
                                    class="
                                        dropdown-item
                                        logout-btn
                                    "
                                    href="logout.php"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-box-arrow-right
                                        "
                                    ></i>

                                    ออกจากระบบ

                                </a>

                            </li>

                        </ul>

                    </div>


                <?php else: ?>


                    <a
                        href="login.php"
                        class="btn-login"
                    >

                        <i
                            class="
                                bi
                                bi-box-arrow-in-right
                            "
                        ></i>

                        เข้าสู่ระบบ

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<div
    class="
        container
        mt-4
        mb-5
    "
>


    <!-- =================================================
         แจ้งบุคคลทั่วไป
    ================================================== -->

    <?php if (!$logged_in): ?>

        <div class="guest-notice">

            <i
                class="
                    bi
                    bi-eye-fill
                "
            ></i>

            กำลังเข้าชมในฐานะบุคคลทั่วไป
            สามารถค้นหาและดูโปรเจกต์ได้

        </div>

    <?php endif; ?>


    <!-- =================================================
         SEARCH / FILTER
    ================================================== -->

    <div
        class="
            filter-section
            mb-4
        "
    >

        <form
            action="index2.php"
            method="GET"
            class="
                row
                g-3
                align-items-end
            "
        >


            <!-- ค้นหา -->

            <div class="col-md-4">

                <label
                    for="searchKeyword"
                    class="
                        form-label
                        fw-bold
                        text-secondary
                        mb-1
                    "
                >

                    ค้นหาโปรเจกต์:

                </label>


                <input
                    type="text"
                    name="keyword"
                    id="searchKeyword"
                    class="form-control form-control-sm"
                    placeholder="พิมพ์ชื่อโปรเจกต์..."
                    value="<?php echo e($keyword); ?>"
                >

            </div>


            <!-- ระดับ -->

            <div class="col-md-3">

                <label
                    for="degreeSelect"
                    class="
                        form-label
                        fw-bold
                        text-secondary
                        mb-1
                    "
                >

                    ระดับหลักสูตร:

                </label>


                <select
                    name="degree"
                    id="degreeSelect"
                    class="
                        form-select
                        form-select-sm
                    "
                >

                    <option
                        value="all"
                        <?php
                        echo $degree === 'all'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        ทุกระดับการศึกษา

                    </option>


                    <option
                        value="bachelor"
                        <?php
                        echo $degree === 'bachelor'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        ปริญญาตรี

                    </option>


                    <option
                        value="master"
                        <?php
                        echo $degree === 'master'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        ปริญญาโท

                    </option>


                    <option
                        value="doctorate"
                        <?php
                        echo $degree === 'doctorate'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        ปริญญาเอก

                    </option>

                </select>

            </div>


            <!-- สาขา -->

            <div class="col-md-3">

                <label
                    for="majorSelect"
                    class="
                        form-label
                        fw-bold
                        text-secondary
                        mb-1
                    "
                >

                    สาขาวิชา:

                </label>


                <select
                    name="major"
                    id="majorSelect"
                    class="
                        form-select
                        form-select-sm
                    "
                >

                    <option
                        value="all"
                        <?php
                        echo $major === 'all'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        ทุกสาขาวิชา

                    </option>


                    <option
                        value="it"
                        <?php
                        echo $major === 'it'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        เทคโนโลยีสารสนเทศ

                    </option>


                    <option
                        value="cs"
                        <?php
                        echo $major === 'cs'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        วิทยาการคอมพิวเตอร์

                    </option>


                    <option
                        value="env"
                        <?php
                        echo $major === 'env'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        วิทยาศาสตร์สิ่งแวดล้อม

                    </option>


                    <option
                        value="food"
                        <?php
                        echo $major === 'food'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        เทคโนโลยีการประกอบอาหาร

                    </option>

                </select>

            </div>


            <!-- ค้นหา -->

            <div class="col-md-2">

                <button
                    type="submit"
                    class="
                        btn
                        btn-primary
                        btn-sm
                        w-100
                    "
                >

                    <i
                        class="
                            bi
                            bi-search
                        "
                    ></i>

                    ค้นหา

                </button>

            </div>

        </form>

    </div>


    <!-- =================================================
         จำนวนโปรเจกต์
    ================================================== -->

    <div
        class="
            mb-3
            text-secondary
        "
    >

        <small>

            พบโปรเจกต์

            <strong>

                <?php
                echo mysqli_num_rows($result);
                ?>

            </strong>

            รายการ


            <?php if ($keyword !== ''): ?>

                สำหรับคำค้นหา

                <strong>

                    "<?php echo e($keyword); ?>"

                </strong>

            <?php endif; ?>

        </small>

    </div>


    <!-- =================================================
         PROJECT LIST
    ================================================== -->

    <?php if (
        mysqli_num_rows($result) > 0
    ): ?>


        <?php while (
            $project =
            mysqli_fetch_assoc($result)
        ): ?>


            <div class="project-item">


                <!-- =================================================
                     ชื่อโปรเจกต์
                ================================================== -->

                <a
                    href="project-detail.php?id=<?php echo (int)$project['id']; ?>"
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
                                $project['project_name']
                                ?? ''
                            );
                    }


                    if (
                        $display_title === ''
                    ) {

                        $display_title =
                            'ไม่ระบุชื่อโปรเจกต์';
                    }


                    echo e(
                        $display_title
                    );

                    ?>

                </a>


                <!-- =================================================
                     ระดับการศึกษา
                ================================================== -->

                <?php if (
                    !empty(
                        $project['degree']
                    )
                ): ?>

                    <span
                        class="
                            badge
                            bg-light
                            text-dark
                            ms-2
                            border
                        "
                    >

                        <?php
                        echo e(
                            $project['degree']
                        );
                        ?>

                    </span>

                <?php endif; ?>


                <!-- =================================================
                     สาขา
                ================================================== -->

                <?php if (
                    !empty(
                        $project['department']
                    )
                ): ?>

                    <span
                        class="
                            badge
                            bg-info
                            text-dark
                            ms-1
                        "
                    >

                        <?php
                        echo e(
                            $project['department']
                        );
                        ?>

                    </span>

                <?php endif; ?>


                <!-- =================================================
                     เจ้าของโปรเจกต์
                ================================================== -->

                <?php

                $owner_name =
                    trim(
                        $project['student_name'] ?? ''
                    );


                /*
                 * ถ้า student_name มีหลายบรรทัด
                 * ใช้เฉพาะบรรทัดแรกเป็นเจ้าของ
                 */
                if ($owner_name !== '') {

                    $owner_lines =
                        preg_split(
                            '/\r\n|\r|\n/',
                            $owner_name
                        );

                    $owner_name =
                        trim(
                            $owner_lines[0] ?? ''
                        );
                }

                ?>


                <?php if (
                    $owner_name !== ''
                ): ?>

                    <p
                        class="
                            info-text
                            mt-2
                            mb-1
                        "
                    >

                        <span
                            class="info-label"
                        >

                            เจ้าของโปรเจกต์:

                        </span>

                        <?php
                        echo e(
                            $owner_name
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     ข้อมูลสมาชิก
                     แสดงเฉพาะผู้ Login
                ================================================== -->

                <?php if ($logged_in): ?>


                    <?php

                    /*
                     * authors:
                     *
                     * Fiw
                     * Mario
                     * Aomsin
                     * Icedusit
                     *
                     * จะตัด Fiw ซึ่งเป็นเจ้าของออก
                     */

                    $authors_text =
                        trim(
                            $project['authors'] ?? ''
                        );

                    $member_list = [];


                    if (
                        $authors_text !== ''
                    ) {

                        $author_list =
                            preg_split(
                                '/\r\n|\r|\n/',
                                $authors_text
                            );


                        foreach (
                            $author_list
                            as $author
                        ) {

                            $author =
                                trim($author);


                            if (
                                $author === ''
                            ) {

                                continue;
                            }


                            /*
                             * ไม่เอาเจ้าของมาแสดงซ้ำ
                             */
                            if (
                                $owner_name !== '' &&
                                $author === $owner_name
                            ) {

                                continue;
                            }


                            /*
                             * ป้องกันสมาชิกซ้ำ
                             */
                            if (
                                !in_array(
                                    $author,
                                    $member_list,
                                    true
                                )
                            ) {

                                $member_list[] =
                                    $author;
                            }
                        }
                    }

                    ?>


                    <!-- =================================================
                         สมาชิกกลุ่ม
                    ================================================== -->

                    <?php if (
                        !empty($member_list)
                    ): ?>

                        <div
                            class="
                                info-text
                                mb-1
                            "
                        >

                            <span
                                class="info-label"
                            >

                                สมาชิกกลุ่ม:

                            </span>


                            <div
                                class="author-list"
                            >

                                <?php foreach (
                                    $member_list
                                    as $member
                                ): ?>

                                    <div>

                                        •

                                        <?php
                                        echo e(
                                            $member
                                        );
                                        ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                <?php endif; ?>


                <!-- =================================================
                     อาจารย์ที่ปรึกษา
                     
                     สำคัญ:
                     แสดงทั้งคน Login และบุคคลทั่วไป
                ================================================== -->

                <?php if (
                    !empty(
                        trim(
                            $project['advisor'] ?? ''
                        )
                    )
                ): ?>

                    <p
                        class="
                            info-text
                            mb-1
                        "
                    >

                        <span
                            class="info-label"
                        >

                            อาจารย์ที่ปรึกษา:

                        </span>

                        <?php

                        echo e(
                            trim(
                                $project['advisor']
                            )
                        );

                        ?>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     วันที่ลงโปรเจกต์
                     
                     แสดงทั้งคน Login และบุคคลทั่วไป
                ================================================== -->

                <?php if (
                    !empty(
                        $project['created_at']
                    )
                ): ?>

                    <p
                        class="
                            info-text
                            mb-1
                        "
                    >

                        <span
                            class="info-label"
                        >

                            วันที่ลงโปรเจกต์:

                        </span>

                        <?php

                        $created_timestamp =
                            strtotime(
                                $project['created_at']
                            );


                        if (
                            $created_timestamp !== false
                        ) {

                            echo e(
                                date(
                                    'd/m/Y',
                                    $created_timestamp
                                )
                            );

                        } else {

                            echo e(
                                $project['created_at']
                            );
                        }

                        ?>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     คำอธิบาย
                ================================================== -->

                <?php if (
                    !empty(
                        $project['description']
                    )
                ): ?>

                    <p
                        class="
                            description-text
                        "
                    >

                        <span
                            class="
                                description-label
                            "
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
                            e($description)
                        );

                        ?>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     จำนวนหน้า
                ================================================== -->

                <?php if (
                    !empty(
                        $project['pages']
                    )
                ): ?>

                    <p
                        class="page-text"
                    >

                        <?php
                        echo e(
                            $project['pages']
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     PDF
                ================================================== -->

                <?php if (
                    !empty(
                        $project['pdf_file']
                    )
                ): ?>

                    <a
                        href="view-pdf.php?id=<?php echo (int)$project['id']; ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="
                            btn
                            btn-pdf
                            mt-1
                        "
                    >

                        <i
                            class="
                                bi
                                bi-file-earmark-pdf
                            "
                        ></i>

                        PDF

                    </a>

                <?php endif; ?>


                <!-- =================================================
                     GitHub
                ================================================== -->

                <?php if (
                    !empty(
                        $project['github_url']
                    )
                ): ?>

                    <a
                        href="<?php echo e($project['github_url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn-github"
                    >

                        <i
                            class="
                                bi
                                bi-github
                            "
                        ></i>

                        GitHub

                    </a>

                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <!-- =================================================
             ไม่พบโปรเจกต์
        ================================================== -->

        <div class="no-project">

            <i
                class="
                    bi
                    bi-folder-x
                "
            ></i>


            <h5>

                ไม่พบโปรเจกต์

            </h5>


            <p>

                ยังไม่มีโปรเจกต์
                ที่ตรงกับข้อมูลที่ค้นหา

            </p>

        </div>


    <?php endif; ?>


</div>


<!-- =====================================================
     Bootstrap JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>