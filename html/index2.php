<?php
session_start();
include 'db_connect.php';

$user_role = $_SESSION['role'] ?? '';


// =====================================================
// รับค่าจากช่องค้นหา
// =====================================================

$keyword = trim($_GET['keyword'] ?? '');
$degree  = $_GET['degree'] ?? 'all';
$major   = $_GET['major'] ?? 'all';


// =====================================================
// สร้าง SQL
// =====================================================

$sql = "SELECT * FROM projects WHERE 1=1";

$params = [];
$types = "";


// =====================================================
// ค้นหา
// =====================================================
// สำคัญ:
// ช่องชื่อโปรเจกต์ใน create.php ใช้ title
// ดังนั้น title ต้องอยู่ในเงื่อนไขค้นหา
// =====================================================

if ($keyword !== '') {

    $search = "%" . $keyword . "%";

    $sql .= "
        AND (
            title LIKE ?
            OR project_name LIKE ?
            OR authors LIKE ?
            OR description LIKE ?
            OR department LIKE ?
            OR degree LIKE ?
            OR advisor LIKE ?
            OR student_name LIKE ?
        )
    ";

    $params = [
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search
    ];

    $types = "ssssssss";
}


// =====================================================
// กรองระดับการศึกษา
// =====================================================

if ($degree === 'bachelor') {

    $sql .= "
        AND (
            degree = 'ปริญญาตรี'
            OR degree = 'bachelor'
            OR degree = 'Bachelor'
        )
    ";

} elseif ($degree === 'master') {

    $sql .= "
        AND (
            degree = 'ปริญญาโท'
            OR degree = 'master'
            OR degree = 'Master'
        )
    ";

} elseif ($degree === 'doctorate') {

    $sql .= "
        AND (
            degree = 'ปริญญาเอก'
            OR degree = 'doctorate'
            OR degree = 'Doctorate'
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
            OR department LIKE '%Information Technology%'
            OR department = 'IT'
        )
    ";

} elseif ($major === 'cs') {

    $sql .= "
        AND (
            department LIKE '%วิทยาการคอมพิวเตอร์%'
            OR department LIKE '%Computer Science%'
            OR department = 'CS'
        )
    ";

} elseif ($major === 'env') {

    $sql .= "
        AND (
            department LIKE '%วิทยาศาสตร์สิ่งแวดล้อม%'
            OR department LIKE '%Environmental Science%'
        )
    ";

} elseif ($major === 'food') {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีการประกอบอาหาร%'
            OR department LIKE '%Food Technology%'
        )
    ";
}


// =====================================================
// เรียงจากล่าสุด
// =====================================================

$sql .= " ORDER BY id DESC";


// =====================================================
// เตรียม SQL
// =====================================================

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        htmlspecialchars(mysqli_error($conn))
    );
}


// =====================================================
// Bind ค่าค้นหา
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
            mysqli_stmt_error($stmt)
        )
    );
}


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

        .custom-header {
            background:
                linear-gradient(
                    to right,
                    #4aa4d6,
                    #3287BB
                );
            padding: 8px 0 15px 0;
            border-bottom: 2px solid #287cab;
        }

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

        .btn-upload-project {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
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

        .sdu-logo {
            width: 45px;
            height: auto;
            background-color: white;
            border-radius: 50%;
            padding: 2px;
        }

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

        .custom-profile-menu {
            background-color: #173f5f;
            border: 1px solid #285776;
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

        .custom-profile-menu .dropdown-item:hover {
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

        .filter-section {
            background-color: #f4f8fb;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px 20px;
        }

        .project-item {
            padding-bottom: 25px;
            margin-bottom: 25px;
            border-bottom: 1px solid #dee2e6;
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

        .btn-pdf {
            background-color: var(--sdu-pdf-blue);
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

    </style>

</head>


<body>


<header class="custom-header">

    <div class="container">

        <div
            class="d-flex justify-content-between align-items-center flex-wrap mt-2"
        >

            <!-- ซ้าย -->

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


                    <?php if ($user_role === 'admin'): ?>

                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="admin.php"
                            >

                                <i class="bi bi-shield-lock-fill"></i>

                                Admin

                            </a>

                        </li>

                    <?php endif; ?>

                </ul>

            </div>


            <!-- ขวา -->

            <div class="d-flex align-items-center gap-3">


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

                                <i class="bi bi-person-fill"></i>

                                <span>
                                    ข้อมูลส่วนตัว
                                </span>

                            </a>

                        </li>


                        <?php if ($user_role === 'admin'): ?>

                            <li>

                                <a
                                    class="dropdown-item"
                                    href="admin.php"
                                >

                                    <i class="bi bi-shield-lock-fill"></i>

                                    <span>
                                        จัดการระบบ
                                    </span>

                                </a>

                            </li>

                        <?php endif; ?>


                        <li>

                            <hr class="dropdown-divider">

                        </li>


                        <li>

                            <a
                                class="dropdown-item logout-btn"
                                href="logout.php"
                            >

                                <i class="bi bi-box-arrow-right"></i>

                                <span>
                                    ออกจากระบบ
                                </span>

                            </a>

                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</header>


<div class="container mt-4 mb-5">


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
                        <?= $degree === 'all' ? 'selected' : '' ?>
                    >
                        ทุกระดับการศึกษา
                    </option>

                    <option
                        value="bachelor"
                        <?= $degree === 'bachelor' ? 'selected' : '' ?>
                    >
                        ปริญญาตรี
                    </option>

                    <option
                        value="master"
                        <?= $degree === 'master' ? 'selected' : '' ?>
                    >
                        ปริญญาโท
                    </option>

                    <option
                        value="doctorate"
                        <?= $degree === 'doctorate' ? 'selected' : '' ?>
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
                        <?= $major === 'all' ? 'selected' : '' ?>
                    >
                        ทุกสาขาวิชา
                    </option>

                    <option
                        value="it"
                        <?= $major === 'it' ? 'selected' : '' ?>
                    >
                        เทคโนโลยีสารสนเทศ
                    </option>

                    <option
                        value="cs"
                        <?= $major === 'cs' ? 'selected' : '' ?>
                    >
                        วิทยาการคอมพิวเตอร์
                    </option>

                    <option
                        value="env"
                        <?= $major === 'env' ? 'selected' : '' ?>
                    >
                        วิทยาศาสตร์สิ่งแวดล้อม
                    </option>

                    <option
                        value="food"
                        <?= $major === 'food' ? 'selected' : '' ?>
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

    <?php if (mysqli_num_rows($result) > 0): ?>


        <?php while ($project = mysqli_fetch_assoc($result)): ?>


            <div class="project-item">


                <!-- ชื่อโปรเจกต์ -->

                <a
                    href="project-detail.php?id=<?= (int)$project['id'] ?>"
                    class="project-title"
                >

                    <?php

                    /*
                     * ใช้ title เป็นชื่อหลัก
                     * เพราะ create.php บันทึกชื่อไว้ที่ title
                     */

                    $display_title =
                        trim($project['title'] ?? '');

                    if ($display_title === '') {

                        $display_title =
                            trim(
                                $project['project_name'] ?? ''
                            );
                    }

                    if ($display_title === '') {

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


                <!-- ระดับ -->

                <?php if (!empty($project['degree'])): ?>

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


                <!-- สาขา -->

                <?php if (!empty($project['department'])): ?>

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


                <!-- สมาชิก -->

                <?php if (!empty($project['authors'])): ?>

                    <p class="author-text mt-2">

                        <?= htmlspecialchars(
                            $project['authors'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                <?php endif; ?>


                <!-- อาจารย์ -->

                <?php if (!empty($project['advisor'])): ?>

                    <p class="author-text">

                        <?= htmlspecialchars(
                            $project['advisor'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                <?php endif; ?>


                <!-- คำอธิบาย -->

                <?php if (!empty($project['description'])): ?>

                    <p class="description-text">

                        <span class="description-label">
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


                <!-- จำนวนหน้า -->

                <?php if (!empty($project['pages'])): ?>

                    <p class="page-text">

                        <?= htmlspecialchars(
                            $project['pages'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                <?php endif; ?>


                <!-- PDF -->

                <?php if (!empty($project['pdf_file'])): ?>

                    <a
                        href="uploads/<?= rawurlencode(
                            $project['pdf_file']
                        ) ?>"
                        target="_blank"
                        class="btn btn-pdf mt-1"
                    >

                        <i class="bi bi-file-earmark-pdf"></i>

                        PDF

                    </a>

                <?php endif; ?>


                <!-- GitHub -->

                <?php if (!empty($project['github_url'])): ?>

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