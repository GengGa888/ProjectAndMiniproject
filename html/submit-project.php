<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   ฟังก์ชัน Escape
========================= */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   ดึงข้อมูลผู้ใช้ปัจจุบัน
========================= */
$user_stmt = mysqli_prepare($conn, "
    SELECT id, prefix, first_name, last_name, username, email, role, department
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$user_stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
}

mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);

$user_result = mysqli_stmt_get_result($user_stmt);
$current_user = mysqli_fetch_assoc($user_result);

mysqli_stmt_close($user_stmt);

if (!$current_user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* =========================
   ชื่อผู้ส่ง
========================= */
$submitter_name = trim(
    ($current_user['prefix'] ?? '') . ' ' .
    ($current_user['first_name'] ?? '') . ' ' .
    ($current_user['last_name'] ?? '')
);

$submitter_name = preg_replace('/\s+/', ' ', $submitter_name);

/* =========================
   ดึงรายชื่อนักศึกษา
========================= */
$students = [];

$student_stmt = mysqli_prepare($conn, "
    SELECT id, prefix, first_name, last_name, username
    FROM users
    WHERE role = 'student'
    ORDER BY first_name ASC, last_name ASC
");

if ($student_stmt) {
    mysqli_stmt_execute($student_stmt);
    $student_result = mysqli_stmt_get_result($student_stmt);

    while ($student = mysqli_fetch_assoc($student_result)) {
        $students[] = $student;
    }

    mysqli_stmt_close($student_stmt);
}

/* =========================
   ดึงรายชื่ออาจารย์
========================= */
$teachers = [];

$teacher_stmt = mysqli_prepare($conn, "
    SELECT id, prefix, first_name, last_name, username
    FROM users
    WHERE role = 'teacher'
    ORDER BY first_name ASC, last_name ASC
");

if ($teacher_stmt) {
    mysqli_stmt_execute($teacher_stmt);
    $teacher_result = mysqli_stmt_get_result($teacher_stmt);

    while ($teacher = mysqli_fetch_assoc($teacher_result)) {
        $teachers[] = $teacher;
    }

    mysqli_stmt_close($teacher_stmt);
}

/* =========================
   ตัวแปรฟอร์ม
========================= */
$title = '';
$description = '';
$degree = '';
$department = '';
$authors = '';
$advisor = '';
$github_url = '';
$custom_department = '';

$error = '';
$success = '';

/* =========================
   เมื่อกดส่ง
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $custom_department = trim($_POST['custom_department'] ?? '');
    $advisor = trim($_POST['advisor'] ?? '');
    $github_url = trim($_POST['github_url'] ?? '');

    /* สมาชิก */
    $selected_members = $_POST['members'] ?? [];

    if (!is_array($selected_members)) {
        $selected_members = [$selected_members];
    }

    $selected_members = array_values(
        array_unique(
            array_filter(
                array_map('intval', $selected_members),
                function ($id) {
                    return $id > 0;
                }
            )
        )
    );

    /* ต้องมีผู้ส่งเป็นสมาชิกด้วย */
    if (!in_array($user_id, $selected_members, true)) {
        $selected_members[] = $user_id;
    }

    /* =========================
       ตรวจสอบข้อมูล
    ========================= */
    if ($title === '') {
        $error = 'กรุณากรอกชื่อโปรเจกต์';
    } elseif ($degree === '') {
        $error = 'กรุณาเลือกระดับการศึกษา';
    } elseif ($department === '') {
        $error = 'กรุณาเลือกสาขา / ภาควิชา';
    } elseif ($department === 'อื่นๆ' && $custom_department === '') {
        $error = 'กรุณาระบุสาขา / ภาควิชา';
    } elseif (count($selected_members) === 0) {
        $error = 'กรุณาเลือกสมาชิกในกลุ่มอย่างน้อย 1 คน';
    }

    /* =========================
       ถ้าเลือกอื่นๆ
    ========================= */
    if ($error === '') {
        if ($department === 'อื่นๆ') {
            $department = $custom_department;
        }
    }

    /* =========================
       ตรวจสอบสมาชิก
    ========================= */
    $valid_member_ids = [];

    if ($error === '') {

        if (count($selected_members) > 0) {

            $placeholders = implode(',', array_fill(0, count($selected_members), '?'));

            $sql = "
                SELECT id
                FROM users
                WHERE role = 'student'
                AND id IN ($placeholders)
            ";

            $member_stmt = mysqli_prepare($conn, $sql);

            if (!$member_stmt) {
                $error = 'ไม่สามารถตรวจสอบสมาชิกได้';
            } else {

                $types = str_repeat('i', count($selected_members));

                mysqli_stmt_bind_param(
                    $member_stmt,
                    $types,
                    ...$selected_members
                );

                mysqli_stmt_execute($member_stmt);

                $member_result = mysqli_stmt_get_result($member_stmt);

                while ($member_row = mysqli_fetch_assoc($member_result)) {
                    $valid_member_ids[] = (int)$member_row['id'];
                }

                mysqli_stmt_close($member_stmt);

                /* ป้องกันกรณี current user ไม่อยู่ในผลเพราะ role ไม่ใช่ student */
                if ($current_user['role'] === 'student') {
                    if (!in_array($user_id, $valid_member_ids, true)) {
                        $valid_member_ids[] = $user_id;
                    }
                }

                if (count($valid_member_ids) === 0) {
                    $error = 'ไม่พบสมาชิกที่เลือก';
                }
            }
        }
    }

    /* =========================
       สร้างชื่อสมาชิก
    ========================= */
    if ($error === '') {

        $member_names = [];

        foreach ($students as $student) {

            if (in_array((int)$student['id'], $valid_member_ids, true)) {

                $name = trim(
                    ($student['prefix'] ?? '') . ' ' .
                    ($student['first_name'] ?? '') . ' ' .
                    ($student['last_name'] ?? '')
                );

                $name = preg_replace('/\s+/', ' ', $name);

                if ($name !== '') {
                    $member_names[] = $name;
                }
            }
        }

        /* กรณีผู้ส่งเป็นคนที่ไม่ใช่นักศึกษา */
        if (
            $current_user['role'] === 'student' &&
            !in_array($submitter_name, $member_names, true)
        ) {
            $member_names[] = $submitter_name;
        }

        $authors = implode("\n", $member_names);
    }

    /* =========================
       ตรวจสอบ GitHub
    ========================= */
    if ($error === '' && $github_url !== '') {

        if (!filter_var($github_url, FILTER_VALIDATE_URL)) {
            $error = 'URL GitHub ไม่ถูกต้อง';
        } elseif (
            stripos($github_url, 'github.com') === false
        ) {
            $error = 'กรุณาใส่ลิงก์ GitHub ที่ถูกต้อง';
        }
    }

    /* =========================
       PDF
    ========================= */
    $pdf_file_name = '';

    if ($error === '' && isset($_FILES['pdf_file'])) {

        if ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'เกิดข้อผิดพลาดในการอัปโหลด PDF';
            } else {

                $max_size = 20 * 1024 * 1024;

                if ($_FILES['pdf_file']['size'] > $max_size) {
                    $error = 'ไฟล์ PDF ต้องมีขนาดไม่เกิน 20MB';
                } else {

                    $tmp_name = $_FILES['pdf_file']['tmp_name'];
                    $original_name = $_FILES['pdf_file']['name'];

                    $extension = strtolower(
                        pathinfo($original_name, PATHINFO_EXTENSION)
                    );

                    if ($extension !== 'pdf') {
                        $error = 'กรุณาอัปโหลดเฉพาะไฟล์ PDF';
                    } else {

                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);

                        if ($mime !== 'application/pdf') {
                            $error = 'ไฟล์ที่อัปโหลดไม่ใช่ PDF';
                        } else {

                            $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }

                            $pdf_file_name =
                                'project_' .
                                time() . '_' .
                                bin2hex(random_bytes(5)) .
                                '.pdf';

                            $target_path =
                                $upload_dir .
                                DIRECTORY_SEPARATOR .
                                $pdf_file_name;

                            if (!move_uploaded_file($tmp_name, $target_path)) {
                                $error = 'ไม่สามารถบันทึกไฟล์ PDF ได้';
                                $pdf_file_name = '';
                            }
                        }
                    }
                }
            }
        }
    }

    /* =========================
       บันทึกโปรเจกต์
    ========================= */
    if ($error === '') {

        $project_name = $title;
        $project_type = $degree;
        $student_name = $authors !== ''
            ? $authors
            : $submitter_name;

        $status = 'ส่งแล้ว';

        $sql = "
            INSERT INTO projects
            (
                project_name,
                project_type,
                student_name,
                title,
                description,
                degree,
                department,
                authors,
                advisor,
                pdf_file,
                github_url,
                status,
                student_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            if ($pdf_file_name !== '') {
                $uploaded_path =
                    __DIR__ .
                    DIRECTORY_SEPARATOR .
                    'uploads' .
                    DIRECTORY_SEPARATOR .
                    $pdf_file_name;

                if (is_file($uploaded_path)) {
                    unlink($uploaded_path);
                }
            }

            $error = 'เกิดข้อผิดพลาดในการบันทึกโปรเจกต์';
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssssssi",
                $project_name,
                $project_type,
                $student_name,
                $title,
                $description,
                $degree,
                $department,
                $authors,
                $advisor,
                $pdf_file_name,
                $github_url,
                $status,
                $user_id
            );

            if (mysqli_stmt_execute($stmt)) {

                $project_id = mysqli_insert_id($conn);

                mysqli_stmt_close($stmt);

                /* =========================
                   บันทึกสมาชิกโปรเจกต์
                ========================= */
                $member_insert = mysqli_prepare(
                    $conn,
                    "
                    INSERT IGNORE INTO project_members
                    (
                        project_id,
                        user_id
                    )
                    VALUES (?, ?)
                    "
                );

                if ($member_insert) {

                    foreach ($valid_member_ids as $member_id) {

                        mysqli_stmt_bind_param(
                            $member_insert,
                            "ii",
                            $project_id,
                            $member_id
                        );

                        mysqli_stmt_execute($member_insert);
                    }

                    mysqli_stmt_close($member_insert);
                }

                header("Location: project-detail.php?id=" . $project_id);
                exit();

            } else {

                mysqli_stmt_close($stmt);

                if ($pdf_file_name !== '') {
                    $uploaded_path =
                        __DIR__ .
                        DIRECTORY_SEPARATOR .
                        'uploads' .
                        DIRECTORY_SEPARATOR .
                        $pdf_file_name;

                    if (is_file($uploaded_path)) {
                        unlink($uploaded_path);
                    }
                }

                $error = 'ไม่สามารถบันทึกโปรเจกต์ได้';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ส่งโปรเจกต์ - ระบบสืบค้นโปรเจกต์</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f4f8fb;
            font-family: "Sarabun", Arial, sans-serif;
        }

        .page-wrapper {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card-box {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
        }

        .page-title {
            color: #2384bb;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
        }

        .member-box {
            border: 1px solid #dcebf4;
            border-radius: 15px;
            padding: 15px;
            max-height: 350px;
            overflow-y: auto;
            background: #f8fcff;
        }

        .member-item {
            background: white;
            border: 1px solid #e4eef4;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .member-item:hover {
            background: #eef8ff;
        }

        .member-item label {
            cursor: pointer;
            width: 100%;
        }

        .btn-submit {
            background: #238bc2;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
        }

        .btn-submit:hover {
            background: #176f9e;
            color: white;
        }

        .current-user {
            color: #1683bd;
            font-size: 13px;
        }

    </style>

</head>

<body>

<div class="page-wrapper">

    <div class="card-box">

        <h2 class="page-title">
            <i class="bi bi-folder-plus"></i>
            ส่งโปรเจกต์
        </h2>

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo e($error); ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <!-- ชื่อโปรเจกต์ -->
            <div class="mb-3">

                <label class="form-label">
                    ชื่อโปรเจกต์
                </label>

                <input
                    type="text"
                    name="title"
                    class="form-control"
                    value="<?php echo e($title); ?>"
                    required
                >

            </div>

            <!-- รายละเอียด -->
            <div class="mb-3">

                <label class="form-label">
                    รายละเอียดโปรเจกต์
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="5"
                ><?php echo e($description); ?></textarea>

            </div>

            <!-- ระดับ -->
            <div class="mb-3">

                <label class="form-label">
                    ระดับการศึกษา
                </label>

                <select
                    name="degree"
                    class="form-select"
                    required
                >

                    <option value="">
                        -- เลือกระดับการศึกษา --
                    </option>

                    <option
                        value="ปริญญาตรี"
                        <?php echo $degree === 'ปริญญาตรี' ? 'selected' : ''; ?>
                    >
                        ปริญญาตรี
                    </option>

                    <option
                        value="ปริญญาโท"
                        <?php echo $degree === 'ปริญญาโท' ? 'selected' : ''; ?>
                    >
                        ปริญญาโท
                    </option>

                    <option
                        value="ปริญญาเอก"
                        <?php echo $degree === 'ปริญญาเอก' ? 'selected' : ''; ?>
                    >
                        ปริญญาเอก
                    </option>

                </select>

            </div>

            <!-- สาขา -->
            <div class="mb-3">

                <label class="form-label">
                    สาขา / ภาควิชา
                </label>

                <select
                    name="department"
                    id="department"
                    class="form-select"
                    required
                >

                    <option value="">
                        -- เลือกสาขา --
                    </option>

                    <option value="เทคโนโลยีสารสนเทศ">
                        เทคโนโลยีสารสนเทศ
                    </option>

                    <option value="วิทยาการคอมพิวเตอร์">
                        วิทยาการคอมพิวเตอร์
                    </option>

                    <option value="เทคโนโลยีดิจิทัล">
                        เทคโนโลยีดิจิทัล
                    </option>

                    <option value="คอมพิวเตอร์ธุรกิจ">
                        คอมพิวเตอร์ธุรกิจ
                    </option>

                    <option value="มัลติมีเดีย">
                        มัลติมีเดีย
                    </option>

                    <option value="วิทยาศาสตร์สิ่งแวดล้อม">
                        วิทยาศาสตร์สิ่งแวดล้อม
                    </option>

                    <option value="เทคโนโลยีการประกอบอาหาร">
                        เทคโนโลยีการประกอบอาหาร
                    </option>

                    <option value="อื่นๆ">
                        อื่นๆ
                    </option>

                </select>

            </div>

            <div
                class="mb-3"
                id="customDepartmentBox"
                style="display:none;"
            >

                <label class="form-label">
                    ระบุสาขา / ภาควิชา
                </label>

                <input
                    type="text"
                    name="custom_department"
                    class="form-control"
                    value="<?php echo e($custom_department); ?>"
                >

            </div>

            <!-- สมาชิก -->
            <div class="mb-3">

                <label class="form-label">
                    <i class="bi bi-people"></i>
                    สมาชิกในกลุ่ม
                </label>

                <div class="current-user mb-2">

                    <i class="bi bi-info-circle"></i>

                    ผู้ส่งโปรเจกต์:
                    <?php echo e($submitter_name); ?>

                    <br>

                    ผู้ส่งจะถูกเพิ่มเป็นสมาชิกโดยอัตโนมัติ

                </div>

                <div class="member-box">

                    <?php if (count($students) > 0): ?>

                        <?php foreach ($students as $student): ?>

                            <?php

                            $student_id = (int)$student['id'];

                            $student_name = trim(
                                ($student['prefix'] ?? '') . ' ' .
                                ($student['first_name'] ?? '') . ' ' .
                                ($student['last_name'] ?? '')
                            );

                            $student_name =
                                preg_replace('/\s+/', ' ', $student_name);

                            $checked =
                                in_array(
                                    $student_id,
                                    $selected_members,
                                    true
                                );

                            ?>

                            <div class="member-item">

                                <label>

                                    <input
                                        type="checkbox"
                                        name="members[]"
                                        value="<?php echo $student_id; ?>"
                                        <?php echo $checked ? 'checked' : ''; ?>
                                        class="form-check-input me-2"
                                    >

                                    <?php echo e($student_name); ?>

                                    <?php if ($student_id === $user_id): ?>

                                        <span class="text-primary">
                                            (คุณ)
                                        </span>

                                    <?php endif; ?>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="text-muted">
                            ยังไม่มีรายชื่อนักศึกษา
                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <!-- อาจารย์ที่ปรึกษา -->
            <div class="mb-3">

                <label class="form-label">
                    อาจารย์ที่ปรึกษา
                </label>

                <select
                    name="advisor"
                    class="form-select"
                >

                    <option value="">
                        -- เลือกอาจารย์ที่ปรึกษา --
                    </option>

                    <?php foreach ($teachers as $teacher): ?>

                        <?php

                        $teacher_name = trim(
                            ($teacher['prefix'] ?? '') . ' ' .
                            ($teacher['first_name'] ?? '') . ' ' .
                            ($teacher['last_name'] ?? '')
                        );

                        $teacher_name =
                            preg_replace('/\s+/', ' ', $teacher_name);

                        ?>

                        <option
                            value="<?php echo e($teacher_name); ?>"
                            <?php echo $advisor === $teacher_name ? 'selected' : ''; ?>
                        >
                            <?php echo e($teacher_name); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <!-- GitHub -->
            <div class="mb-3">

                <label class="form-label">
                    GitHub
                </label>

                <input
                    type="url"
                    name="github_url"
                    class="form-control"
                    placeholder="https://github.com/username/project"
                    value="<?php echo e($github_url); ?>"
                >

            </div>

            <!-- PDF -->
            <div class="mb-4">

                <label class="form-label">
                    ไฟล์ PDF
                </label>

                <input
                    type="file"
                    name="pdf_file"
                    class="form-control"
                    accept=".pdf,application/pdf"
                >

                <small class="text-muted">
                    ขนาดไฟล์ไม่เกิน 20MB
                </small>

            </div>

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-submit"
                >
                    <i class="bi bi-cloud-upload"></i>
                    ส่งโปรเจกต์
                </button>

                <a
                    href="index2.php"
                    class="btn btn-light"
                >
                    ยกเลิก
                </a>

            </div>

        </form>

    </div>

</div>

<script>

const department =
    document.getElementById('department');

const customDepartmentBox =
    document.getElementById('customDepartmentBox');

function checkDepartment() {

    if (department.value === 'อื่นๆ') {

        customDepartmentBox.style.display = 'block';

    } else {

        customDepartmentBox.style.display = 'none';

    }
}

department.addEventListener(
    'change',
    checkDepartment
);

checkDepartment();

</script>

</body>
</html>