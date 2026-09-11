<?php

session_start();
require_once "db_connect.php";


// =====================================================
// ตรวจสอบ Login
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;

}


// =====================================================
// ตรวจสอบสิทธิ์ Admin
// =====================================================

if (($_SESSION['role'] ?? '') !== 'admin') {

    echo "<script>
        alert('หน้านี้สำหรับ Admin เท่านั้น');
        window.location.href='index2.php';
    </script>";

    exit;
}


// =====================================================
// Helper สำหรับแสดงข้อมูลอย่างปลอดภัย
// =====================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =====================================================
// ข้อมูล Admin ปัจจุบัน
// =====================================================

$current_user_id = (int)($_SESSION['user_id'] ?? 0);

$current_first_name =
    $_SESSION['first_name'] ?? 'Admin';

$current_last_name =
    $_SESSION['last_name'] ?? '';

$admin_name = trim(
    $current_first_name . ' ' . $current_last_name
);

if ($admin_name === '') {
    $admin_name = 'Admin';
}


// =====================================================
// ตัวแปร Dashboard
// =====================================================

$total_users    = 0;
$total_students = 0;
$total_teachers = 0;
$total_admins   = 0;
$total_projects = 0;


// =====================================================
// จำนวน Users ทั้งหมด
// =====================================================

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

if (!$result) {
    die("SQL Error: " . e(mysqli_error($conn)));
}

$row = mysqli_fetch_assoc($result);

$total_users = (int)($row['total'] ?? 0);


// =====================================================
// จำนวน Student
// =====================================================

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

if (!$result) {
    die("SQL Error: " . e(mysqli_error($conn)));
}

$row = mysqli_fetch_assoc($result);

$total_students = (int)($row['total'] ?? 0);


// =====================================================
// จำนวน Teacher
// =====================================================

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'teacher'"
);

if (!$result) {
    die("SQL Error: " . e(mysqli_error($conn)));
}

$row = mysqli_fetch_assoc($result);

$total_teachers = (int)($row['total'] ?? 0);


// =====================================================
// จำนวน Admin
// =====================================================

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'admin'"
);

if (!$result) {
    die("SQL Error: " . e(mysqli_error($conn)));
}

$row = mysqli_fetch_assoc($result);

$total_admins = (int)($row['total'] ?? 0);


// =====================================================
// จำนวน Projects
// =====================================================

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM projects"
);

if (!$result) {
    die("SQL Error: " . e(mysqli_error($conn)));
}

$row = mysqli_fetch_assoc($result);

$total_projects = (int)($row['total'] ?? 0);


// =====================================================
// ดึง Users
// =====================================================

$users = [];

$result = mysqli_query(
    $conn,
    "
    SELECT
        id,
        username,
        first_name,
        last_name,
        email,
        role,
        department
    FROM users
    ORDER BY id DESC
    "
);

if (!$result) {
    die("SQL Error (Users): " . e(mysqli_error($conn)));
}

while ($row = mysqli_fetch_assoc($result)) {

    $users[] = $row;

}


// =====================================================
// ดึง Projects
// =====================================================

$projects = [];

$result = mysqli_query(
    $conn,
    "
    SELECT
        id,
        title,
        project_name,
        authors,
        student_name,
        degree,
        project_type,
        department,
        advisor,
        pdf_file,
        github_url,
        status,
        created_at
    FROM projects
    ORDER BY id DESC
    "
);

if (!$result) {
    die("SQL Error (Projects): " . e(mysqli_error($conn)));
}

while ($row = mysqli_fetch_assoc($result)) {

    $projects[] = $row;

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
    Admin Dashboard - คลังโปรเจกต์ SDU
</title>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<style>

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    margin: 0;

    font-family:
        Arial,
        "Sarabun",
        sans-serif;

    background: #f4f8fb;

    color: #333;
}


/* =====================================================
   HEADER
===================================================== */

.custom-header {

    background:
        linear-gradient(
            90deg,
            #4aa4d6,
            #4297cd,
            #3287bb
        );

    color: white;

    min-height: 90px;

    display: flex;

    align-items: center;

    padding: 15px 35px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,.12);
}


.header-inner {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.header-left {

    display: flex;

    align-items: center;

    gap: 15px;
}


.logo {

    width: 58px;

    height: 58px;

    object-fit: contain;

    display: block;
}


.header-title {

    font-size: 23px;

    font-weight: bold;
}


.header-right {

    display: flex;

    align-items: center;

    gap: 10px;
}


.admin-name {

    background:
        rgba(255,255,255,.15);

    padding:
        9px 14px;

    border-radius: 8px;
}


.logout-btn {

    color: white;

    text-decoration: none;

    background:
        rgba(220,53,69,.9);

    padding:
        9px 14px;

    border-radius: 8px;

    transition: .2s;
}


.logout-btn:hover {

    background: #dc3545;

    transform:
        translateY(-1px);
}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    max-width: 1400px;

    margin:
        35px auto;

    padding:
        0 20px;
}


/* =====================================================
   PAGE TITLE
===================================================== */

.page-title {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 25px;
}


.page-title h1 {

    margin: 0;

    color: #287cab;

    font-size: 30px;
}


.page-title p {

    margin:
        6px 0 0;

    color: #777;
}


/* =====================================================
   BUTTON
===================================================== */

.btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    border: none;

    text-decoration: none;

    cursor: pointer;

    border-radius: 8px;

    padding:
        9px 14px;

    font-size: 14px;

    transition: .2s;

    white-space: nowrap;
}


.btn:hover {

    opacity: .9;

    transform:
        translateY(-1px);
}


.btn-primary {

    background: #3287bb;

    color: white;
}


.btn-success {

    background: #198754;

    color: white;
}


.btn-warning {

    background: #f0ad4e;

    color: white;
}


.btn-danger {

    background: #dc3545;

    color: white;
}


.btn-info {

    background: #0dcaf0;

    color: #111;
}


.btn-secondary {

    background: #6c757d;

    color: white;
}


/* =====================================================
   DASHBOARD
===================================================== */

.dashboard {

    display: grid;

    grid-template-columns:
        repeat(5, 1fr);

    gap: 18px;

    margin-bottom: 35px;
}


.card {

    background: white;

    border-radius: 14px;

    padding: 22px;

    box-shadow:
        0 4px 14px
        rgba(0,0,0,.07);

    display: flex;

    align-items: center;

    gap: 15px;
}


.card-icon {

    width: 55px;

    height: 55px;

    min-width: 55px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    background: #e8f4fb;

    color: #3287bb;
}


.card h3 {

    margin: 0;

    font-size: 27px;

    color: #333;
}


.card p {

    margin:
        3px 0 0;

    color: #777;

    font-size: 14px;
}


/* =====================================================
   SECTION
===================================================== */

.section {

    background: white;

    border-radius: 14px;

    padding: 25px;

    margin-bottom: 30px;

    box-shadow:
        0 4px 14px
        rgba(0,0,0,.07);
}


.section-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

    gap: 15px;
}


.section-header h2 {

    margin: 0;

    color: #287cab;

    font-size: 22px;
}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

    width: 100%;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 950px;
}


th {

    background: #eef8fd;

    color: #287cab;

    padding:
        13px 10px;

    text-align: left;

    font-size: 14px;

    white-space: nowrap;
}


td {

    padding:
        12px 10px;

    border-bottom:
        1px solid #eee;

    font-size: 14px;

    vertical-align: middle;
}


tbody tr:hover td {

    background: #fafafa;
}


/* =====================================================
   ROLE
===================================================== */

.role {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding:
        5px 9px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

    white-space: nowrap;
}


.role-student {

    background: #e7f5ff;

    color: #0877b9;
}


.role-teacher {

    background: #fff3cd;

    color: #997404;
}


.role-admin {

    background: #f8d7da;

    color: #a71d2a;
}


/* =====================================================
   STATUS
===================================================== */

.status {

    display: inline-block;

    padding:
        5px 9px;

    border-radius: 20px;

    background: #e8f5e9;

    color: #198754;

    font-size: 12px;

    white-space: nowrap;
}


/* =====================================================
   ACTION BUTTONS
===================================================== */

.action-buttons {

    display: flex;

    align-items: center;

    gap: 7px;

    flex-wrap: wrap;
}


.action-buttons .btn {

    padding:
        7px 10px;

    font-size: 13px;
}


/* =====================================================
   PROJECT TITLE
===================================================== */

.project-title {

    font-weight: bold;

    color: #287cab;

    max-width: 280px;

    line-height: 1.5;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 40px;

    color: #888;
}


.empty i {

    display: block;

    font-size: 40px;

    margin-bottom: 10px;
}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    color: #888;

    padding:
        20px;

    font-size: 13px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .dashboard {

        grid-template-columns:
            repeat(3, 1fr);
    }
}


@media (max-width: 750px) {

    .custom-header {

        padding:
            15px 20px;
    }


    .header-title {

        font-size: 18px;
    }


    .admin-name {

        display: none;
    }


    .dashboard {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .page-title {

        align-items: flex-start;

        flex-direction: column;
    }

}


@media (max-width: 500px) {

    .dashboard {

        grid-template-columns:
            1fr;
    }


    .section {

        padding: 15px;
    }


    .page-title h1 {

        font-size: 25px;
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


        <div class="header-left">

            <a href="index2.php">

                <img
                    src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                    class="logo"
                    alt="SDU Logo"
                >

            </a>


            <div class="header-title">

                คลังโปรเจกต์ SDU

            </div>

        </div>


        <div class="header-right">

            <div class="admin-name">

                <i class="bi bi-person-circle"></i>

                <?= e($admin_name) ?>

                <strong>(Admin)</strong>

            </div>


            <a
                href="logout.php"
                class="logout-btn"
                onclick="return confirm('ต้องการออกจากระบบหรือไม่?');"
            >

                <i class="bi bi-box-arrow-right"></i>

                ออกจากระบบ

            </a>

        </div>

    </div>

</header>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container">


    <!-- =================================================
         PAGE TITLE
    ================================================== -->

    <div class="page-title">

        <div>

            <h1>

                <i class="bi bi-speedometer2"></i>

                Admin Dashboard

            </h1>

            <p>
                จัดการผู้ใช้งานและโปรเจกต์ทั้งหมดในระบบ
            </p>

        </div>


        <a
            href="index2.php"
            class="btn btn-primary"
        >

            <i class="bi bi-house-fill"></i>

            หน้าแรก

        </a>

    </div>



    <!-- =================================================
         DASHBOARD CARDS
    ================================================== -->

    <div class="dashboard">


        <div class="card">

            <div class="card-icon">

                <i class="bi bi-people-fill"></i>

            </div>

            <div>

                <h3>
                    <?= $total_users ?>
                </h3>

                <p>
                    ผู้ใช้ทั้งหมด
                </p>

            </div>

        </div>


        <div class="card">

            <div class="card-icon">

                <i class="bi bi-person-fill"></i>

            </div>

            <div>

                <h3>
                    <?= $total_students ?>
                </h3>

                <p>
                    Student
                </p>

            </div>

        </div>


        <div class="card">

            <div class="card-icon">

                <i class="bi bi-person-workspace"></i>

            </div>

            <div>

                <h3>
                    <?= $total_teachers ?>
                </h3>

                <p>
                    Teacher
                </p>

            </div>

        </div>


        <div class="card">

            <div class="card-icon">

                <i class="bi bi-shield-lock-fill"></i>

            </div>

            <div>

                <h3>
                    <?= $total_admins ?>
                </h3>

                <p>
                    Admin
                </p>

            </div>

        </div>


        <div class="card">

            <div class="card-icon">

                <i class="bi bi-folder-fill"></i>

            </div>

            <div>

                <h3>
                    <?= $total_projects ?>
                </h3>

                <p>
                    โปรเจกต์ทั้งหมด
                </p>

            </div>

        </div>

    </div>



    <!-- =================================================
         USERS
    ================================================== -->

    <div class="section">


        <div class="section-header">

            <h2>

                <i class="bi bi-people-fill"></i>

                จัดการผู้ใช้งาน

            </h2>


            <a
                href="admin_add_user.php"
                class="btn btn-success"
            >

                <i class="bi bi-person-plus-fill"></i>

                เพิ่มผู้ใช้

            </a>

        </div>


        <?php if (!empty($users)): ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Username</th>

                            <th>ชื่อ - นามสกุล</th>

                            <th>Email</th>

                            <th>Role</th>

                            <th>สาขา</th>

                            <th>จัดการ</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($users as $row): ?>

                        <?php

                        $user_role =
                            $row['role'] ?? 'student';

                        $full_name = trim(
                            ($row['first_name'] ?? '') .
                            ' ' .
                            ($row['last_name'] ?? '')
                        );

                        if ($full_name === '') {
                            $full_name = '-';
                        }

                        ?>


                        <tr>


                            <td>
                                <?= (int)$row['id'] ?>
                            </td>


                            <td>

                                <strong>
                                    <?= e($row['username'] ?? '-') ?>
                                </strong>

                            </td>


                            <td>
                                <?= e($full_name) ?>
                            </td>


                            <td>
                                <?= e($row['email'] ?? '-') ?>
                            </td>


                            <td>


                                <?php if ($user_role === 'admin'): ?>

                                    <span class="role role-admin">

                                        <i class="bi bi-shield-fill"></i>

                                        Admin

                                    </span>


                                <?php elseif ($user_role === 'teacher'): ?>

                                    <span class="role role-teacher">

                                        <i class="bi bi-person-workspace"></i>

                                        Teacher

                                    </span>


                                <?php else: ?>

                                    <span class="role role-student">

                                        <i class="bi bi-person-fill"></i>

                                        Student

                                    </span>

                                <?php endif; ?>


                            </td>


                            <td>
                                <?= e($row['department'] ?? '-') ?>
                            </td>


                            <td>

                                <div class="action-buttons">


                                    <!-- EDIT USER -->

                                    <a
                                        href="admin_edit_user.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-warning"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                        แก้ไข

                                    </a>


                                    <!-- DELETE USER -->

                                    <?php if (
                                        (int)$row['id'] !==
                                        $current_user_id
                                    ): ?>

                                        <a
                                            href="admin_delete_user.php?id=<?= (int)$row['id'] ?>"
                                            class="btn btn-danger"
                                            onclick="return confirm('ต้องการลบผู้ใช้นี้ใช่หรือไม่?\\n\\nข้อมูลผู้ใช้จะถูกลบออกจากระบบ');"
                                        >

                                            <i class="bi bi-trash-fill"></i>

                                            ลบ

                                        </a>

                                    <?php else: ?>

                                        <span class="role role-admin">

                                            <i class="bi bi-person-check-fill"></i>

                                            บัญชีปัจจุบัน

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty">

                <i class="bi bi-people"></i>

                ยังไม่มีผู้ใช้งาน

            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         PROJECTS
    ================================================== -->

    <div class="section">


        <div class="section-header">

            <h2>

                <i class="bi bi-folder-fill"></i>

                จัดการโปรเจกต์ทั้งหมด

            </h2>

        </div>


        <?php if (!empty($projects)): ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>ชื่อโปรเจกต์</th>

                            <th>ผู้จัดทำ</th>

                            <th>ระดับ</th>

                            <th>สาขา</th>

                            <th>อาจารย์ที่ปรึกษา</th>

                            <th>สถานะ</th>

                            <th>วันที่ส่ง</th>

                            <th>จัดการ</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($projects as $row): ?>


                        <?php

                        $display_title =
                            trim($row['title'] ?? '');

                        if ($display_title === '') {

                            $display_title =
                                trim(
                                    $row['project_name'] ?? ''
                                );

                        }

                        if ($display_title === '') {
                            $display_title = '-';
                        }


                        $display_authors =
                            trim($row['authors'] ?? '');

                        if ($display_authors === '') {

                            $display_authors =
                                trim(
                                    $row['student_name'] ?? ''
                                );

                        }

                        if ($display_authors === '') {
                            $display_authors = '-';
                        }


                        $display_degree =
                            trim($row['degree'] ?? '');

                        if ($display_degree === '') {

                            $display_degree =
                                trim(
                                    $row['project_type'] ?? ''
                                );

                        }

                        if ($display_degree === '') {
                            $display_degree = '-';
                        }

                        ?>


                        <tr>


                            <td>
                                <?= (int)$row['id'] ?>
                            </td>


                            <td>

                                <div class="project-title">

                                    <?= e($display_title) ?>

                                </div>

                            </td>


                            <td>
                                <?= e($display_authors) ?>
                            </td>


                            <td>
                                <?= e($display_degree) ?>
                            </td>


                            <td>
                                <?= e(
                                    $row['department'] ?? '-'
                                ) ?>
                            </td>


                            <td>
                                <?= e(
                                    $row['advisor'] ?? '-'
                                ) ?>
                            </td>


                            <td>

                                <span class="status">

                                    <?= e(
                                        !empty($row['status'])
                                            ? $row['status']
                                            : 'ส่งแล้ว'
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if (
                                    !empty($row['created_at'])
                                ): ?>

                                    <?= e(
                                        date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $row['created_at']
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <td>

                                <div class="action-buttons">


                                    <!-- VIEW -->

                                    <a
                                        href="project-detail.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-info"
                                    >

                                        <i class="bi bi-eye-fill"></i>

                                        ดู

                                    </a>


                                    <!-- EDIT -->

                                    <a
                                        href="admin_edit_project.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-warning"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                        แก้ไข

                                    </a>


                                    <!-- PDF -->

                                    <?php if (
                                        !empty($row['pdf_file'])
                                    ): ?>

                                        <a
                                            href="uploads/<?= rawurlencode(
                                                basename(
                                                    $row['pdf_file']
                                                )
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-primary"
                                        >

                                            <i class="bi bi-file-earmark-pdf-fill"></i>

                                            PDF

                                        </a>

                                    <?php endif; ?>


                                    <!-- GITHUB -->

                                    <?php

                                    $github_url =
                                        trim(
                                            $row['github_url'] ?? ''
                                        );

                                    $github_scheme =
                                        strtolower(
                                            parse_url(
                                                $github_url,
                                                PHP_URL_SCHEME
                                            ) ?? ''
                                        );

                                    ?>


                                    <?php if (
                                        $github_url !== '' &&
                                        in_array(
                                            $github_scheme,
                                            ['http', 'https'],
                                            true
                                        )
                                    ): ?>

                                        <a
                                            href="<?= e($github_url) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-secondary"
                                        >

                                            <i class="bi bi-github"></i>

                                            GitHub

                                        </a>

                                    <?php endif; ?>


                                    <!-- DELETE -->

                                    <a
                                        href="admin_delete_project.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('ต้องการลบโปรเจกต์นี้ใช่หรือไม่?\\n\\nไฟล์ PDF และข้อมูลโปรเจกต์จะถูกลบด้วย');"
                                    >

                                        <i class="bi bi-trash-fill"></i>

                                        ลบ

                                    </a>


                                </div>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty">

                <i class="bi bi-folder-x"></i>

                ยังไม่มีโปรเจกต์ในระบบ

            </div>


        <?php endif; ?>


    </div>


</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<div class="footer">

    ระบบสืบค้นโปรเจกต์ SDU

</div>


</body>

</html>