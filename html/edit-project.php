<?php
session_start();
require_once "db_connect.php";

/* =========================================================
   ตรวจสอบ Login
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   ตรวจสอบว่าเป็น Student
========================================================= */

if (($_SESSION['role'] ?? '') !== 'student') {

    echo "<script>
        alert('หน้านี้สำหรับนักศึกษาเท่านั้น');
        window.location.href='index2.php';
    </script>";

    exit;
}


/* =========================================================
   รับ Project ID
========================================================= */

$project_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($project_id <= 0) {

    echo "<script>
        alert('ไม่พบ ID โปรเจกต์');
        window.location.href='profile.php';
    </script>";

    exit;
}


/* =========================================================
   Helper
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   ฟังก์ชันจัดรูปแบบผู้จัดทำ
========================================================= */

function formatAuthors($authors)
{
    $authors = trim((string)$authors);

    if ($authors === '') {
        return '';
    }

    $authors = str_replace(
        ["\r\n", "\r"],
        "\n",
        $authors
    );

    $parts = preg_split(
        '/\s*,\s*|\n+/',
        $authors
    );

    $parts = array_map(
        'trim',
        $parts
    );

    $parts = array_filter(
        $parts,
        function ($value) {
            return $value !== '';
        }
    );

    return implode(
        "\n",
        $parts
    );
}


/* =========================================================
   ตัวเลือกระดับการศึกษา
========================================================= */

$degree_options = [
    'ปริญญาตรี',
    'ปริญญาโท',
    'ปริญญาเอก'
];


/* =========================================================
   ตัวเลือกสาขา
========================================================= */

$department_options = [
    'เทคโนโลยีสารสนเทศ',
    'วิทยาการคอมพิวเตอร์',
    'เทคโนโลยีดิจิทัล',
    'คอมพิวเตอร์ธุรกิจ',
    'มัลติมีเดีย',
    'วิทยาศาสตร์สิ่งแวดล้อม',
    'เทคโนโลยีการประกอบอาหาร'
];


/* =========================================================
   ดึงรายชื่ออาจารย์
========================================================= */

$teachers = [];

$teacher_stmt = $conn->prepare("
    SELECT
        id,
        prefix,
        first_name,
        last_name
    FROM users
    WHERE role = 'teacher'
    ORDER BY first_name ASC, last_name ASC
");

if ($teacher_stmt) {

    $teacher_stmt->execute();

    $teacher_result = $teacher_stmt->get_result();

    while ($teacher = $teacher_result->fetch_assoc()) {
        $teachers[] = $teacher;
    }

    $teacher_stmt->close();
}


/* =========================================================
   ดึงข้อมูล Project
   นักศึกษาดึงได้เฉพาะ student_id ของตัวเอง
========================================================= */

$current_user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT
        id,
        project_name,
        project_type,
        student_name,
        advisor,
        description,
        title,
        degree,
        department,
        authors,
        pdf_file,
        status,
        github_url,
        student_id
    FROM projects
    WHERE id = ?
      AND student_id = ?
    LIMIT 1
");

if (!$stmt) {

    die(
        "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: "
        . $conn->error
    );
}

$stmt->bind_param(
    "ii",
    $project_id,
    $current_user_id
);

$stmt->execute();

$result = $stmt->get_result();

$project = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   ตรวจสอบสิทธิ์
========================================================= */

if (!$project) {

    echo "<script>
        alert('คุณไม่มีสิทธิ์แก้ไขโปรเจกต์นี้ หรือไม่พบโปรเจกต์');
        window.location.href='profile.php';
    </script>";

    exit;
}


/* =========================================================
   เตรียมผู้จัดทำ
========================================================= */

$authors_source =
    !empty($project['authors'])
        ? $project['authors']
        : ($project['student_name'] ?? '');

$authors_display = formatAuthors($authors_source);


/* =========================================================
   Advisor ปัจจุบัน
========================================================= */

$current_advisor = trim(
    $project['advisor'] ?? ''
);


/* =========================================================
   เตรียมระดับการศึกษา
========================================================= */

$saved_degree = trim(
    $project['degree']
    ?: $project['project_type']
    ?: ''
);

if (
    $saved_degree !== ''
    && in_array(
        $saved_degree,
        $degree_options,
        true
    )
) {

    $degree_select = $saved_degree;
    $other_degree = '';

} else {

    if ($saved_degree !== '') {
        $degree_select = 'อื่นๆ';
        $other_degree = $saved_degree;
    } else {
        $degree_select = '';
        $other_degree = '';
    }
}


/* =========================================================
   เตรียมสาขา
========================================================= */

$saved_department = trim(
    $project['department'] ?? ''
);

if (
    $saved_department !== ''
    && in_array(
        $saved_department,
        $department_options,
        true
    )
) {

    $department_select = $saved_department;
    $other_department = '';

} else {

    if ($saved_department !== '') {
        $department_select = 'อื่นๆ';
        $other_department = $saved_department;
    } else {
        $department_select = '';
        $other_department = '';
    }
}


/* =========================================================
   Error
========================================================= */

$error = "";


/* =========================================================
   POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =====================================================
       รับข้อมูล
    ===================================================== */

    $title = trim(
        $_POST['title'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );


    /* =====================================================
       ระดับการศึกษา
    ===================================================== */

    $degree_select = trim(
        $_POST['degree'] ?? ''
    );

    $other_degree = trim(
        $_POST['other_degree'] ?? ''
    );

    if ($degree_select === 'อื่นๆ') {

        $degree = $other_degree;

    } else {

        $degree = $degree_select;
    }


    /* =====================================================
       สาขา
    ===================================================== */

    $department_select = trim(
        $_POST['department'] ?? ''
    );

    $other_department = trim(
        $_POST['other_department'] ?? ''
    );

    if ($department_select === 'อื่นๆ') {

        $department = $other_department;

    } else {

        $department = $department_select;
    }


    /* =====================================================
       ผู้จัดทำ
    ===================================================== */

    $authors = trim(
        $_POST['authors'] ?? ''
    );

    $authors = formatAuthors($authors);


    /* =====================================================
       อาจารย์ที่ปรึกษา
    ===================================================== */

    $advisor = trim(
        $_POST['advisor'] ?? ''
    );


    /* =====================================================
       GitHub
    ===================================================== */

    $github_url = trim(
        $_POST['github_url'] ?? ''
    );


    /* =====================================================
       ตรวจสอบชื่อโปรเจกต์
    ===================================================== */

    if ($title === '') {

        $error = 'กรุณากรอกชื่อโปรเจกต์';
    }


    /* =====================================================
       ตรวจสอบระดับการศึกษา
    ===================================================== */

    elseif ($degree === '') {

        $error = 'กรุณาเลือกระดับการศึกษา';
    }


    /* =====================================================
       ตรวจสอบสาขา
    ===================================================== */

    elseif ($department === '') {

        $error = 'กรุณาเลือกสาขา';
    }


    /* =====================================================
       ตรวจสอบ GitHub
    ===================================================== */

    elseif (
        $github_url !== ''
        &&
        !filter_var(
            $github_url,
            FILTER_VALIDATE_URL
        )
    ) {

        $error = 'รูปแบบ GitHub URL ไม่ถูกต้อง';
    }


    /* =====================================================
       ตรวจสอบ Advisor
    ===================================================== */

    if (
        $error === ''
        &&
        $advisor !== ''
    ) {

        $advisor_valid = false;

        foreach ($teachers as $teacher) {

            $prefix = trim(
                $teacher['prefix'] ?? ''
            );

            $first_name = trim(
                $teacher['first_name'] ?? ''
            );

            $last_name = trim(
                $teacher['last_name'] ?? ''
            );


            $teacher_fullname = trim(
                $prefix
                . ' '
                . $first_name
                . ' '
                . $last_name
            );


            $teacher_name_without_prefix = trim(
                $first_name
                . ' '
                . $last_name
            );


            if (
                $advisor === $teacher_fullname
                ||
                $advisor === $teacher_name_without_prefix
            ) {

                /* บันทึกเป็นชื่อเต็มพร้อมคำนำหน้า */

                $advisor = $teacher_fullname;

                $advisor_valid = true;

                break;
            }
        }


        if (!$advisor_valid) {

            $error =
                'กรุณาเลือกอาจารย์ที่ปรึกษาจากรายการ';
        }
    }


    /* =====================================================
       เตรียมข้อมูล
    ===================================================== */

    if ($error === '') {

        /* =================================================
           ข้อมูล Legacy
        ================================================= */

        $project_name = $title;

        $project_type = $degree;

        $student_name =
            $authors !== ''
                ? $authors
                : ($project['student_name'] ?? '');


        /* =================================================
           PDF
        ================================================= */

        $old_pdf_file =
            $project['pdf_file'] ?? '';

        $new_pdf_file =
            $old_pdf_file;

        $uploaded_new_pdf = false;


        /* =================================================
           ตรวจสอบ PDF ใหม่
        ================================================= */

        if (
            isset($_FILES['pdf_file'])
            &&
            $_FILES['pdf_file']['error']
            !== UPLOAD_ERR_NO_FILE
        ) {

            $file = $_FILES['pdf_file'];


            /* ---------------------------------------------
               Upload Error
            --------------------------------------------- */

            if (
                $file['error']
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    'อัปโหลดไฟล์ PDF ไม่สำเร็จ';
            }


            /* ---------------------------------------------
               ขนาด
            --------------------------------------------- */

            elseif (
                $file['size']
                > 10 * 1024 * 1024
            ) {

                $error =
                    'ไฟล์ PDF ต้องมีขนาดไม่เกิน 10 MB';
            }


            /* ---------------------------------------------
               MIME
            --------------------------------------------- */

            else {

                $finfo = new finfo(
                    FILEINFO_MIME_TYPE
                );

                $mime_type = $finfo->file(
                    $file['tmp_name']
                );


                if (
                    $mime_type
                    !== 'application/pdf'
                ) {

                    $error =
                        'อนุญาตเฉพาะไฟล์ PDF เท่านั้น';
                }
            }


            /* ---------------------------------------------
               Upload Directory
            --------------------------------------------- */

            if ($error === '') {

                $upload_dir =
                    __DIR__
                    . DIRECTORY_SEPARATOR
                    . 'uploads';


                if (!is_dir($upload_dir)) {

                    if (
                        !mkdir(
                            $upload_dir,
                            0755,
                            true
                        )
                    ) {

                        $error =
                            'ไม่สามารถสร้างโฟลเดอร์ uploads ได้';
                    }
                }
            }


            /* ---------------------------------------------
               สร้างชื่อ PDF
            --------------------------------------------- */

            if ($error === '') {

                $new_pdf_file =
                    'project_'
                    . $project_id
                    . '_'
                    . time()
                    . '_'
                    . bin2hex(
                        random_bytes(4)
                    )
                    . '.pdf';


                $destination =
                    $upload_dir
                    . DIRECTORY_SEPARATOR
                    . $new_pdf_file;


                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    $error =
                        'ไม่สามารถบันทึกไฟล์ PDF ได้';

                    $new_pdf_file =
                        $old_pdf_file;

                } else {

                    $uploaded_new_pdf = true;
                }
            }
        }


        /* =================================================
           UPDATE
           ย้ำ student_id เพื่อป้องกันแก้ของคนอื่น
        ================================================= */

        if ($error === '') {

            $stmt = $conn->prepare("
                UPDATE projects
                SET
                    project_name = ?,
                    project_type = ?,
                    student_name = ?,
                    title = ?,
                    description = ?,
                    degree = ?,
                    department = ?,
                    authors = ?,
                    advisor = ?,
                    pdf_file = ?,
                    github_url = ?
                WHERE id = ?
                  AND student_id = ?
            ");


            if (!$stmt) {

                $error =
                    'ไม่สามารถเตรียมคำสั่ง SQL ได้: '
                    . $conn->error;

            } else {

                $stmt->bind_param(
                    "sssssssssssii",
                    $project_name,
                    $project_type,
                    $student_name,
                    $title,
                    $description,
                    $degree,
                    $department,
                    $authors,
                    $advisor,
                    $new_pdf_file,
                    $github_url,
                    $project_id,
                    $current_user_id
                );


                if ($stmt->execute()) {

                    $stmt->close();


                    /* =====================================
                       ลบ PDF เก่า
                    ===================================== */

                    if (
                        $uploaded_new_pdf
                        &&
                        !empty($old_pdf_file)
                        &&
                        basename($old_pdf_file)
                        !== basename($new_pdf_file)
                    ) {

                        $old_pdf_path =
                            __DIR__
                            . DIRECTORY_SEPARATOR
                            . 'uploads'
                            . DIRECTORY_SEPARATOR
                            . basename(
                                $old_pdf_file
                            );


                        if (
                            is_file(
                                $old_pdf_path
                            )
                        ) {

                            @unlink(
                                $old_pdf_path
                            );
                        }
                    }


                    /* =====================================
                       สำเร็จ
                    ===================================== */

                    echo "<script>
                        alert('แก้ไขโปรเจกต์สำเร็จ');
                        window.location.href='profile.php';
                    </script>";

                    exit;

                } else {

                    $error =
                        'ไม่สามารถแก้ไขโปรเจกต์ได้: '
                        . $stmt->error;

                    $stmt->close();


                    /* =====================================
                       ลบ PDF ใหม่ถ้า UPDATE ไม่สำเร็จ
                    ===================================== */

                    if (
                        $uploaded_new_pdf
                        &&
                        !empty($new_pdf_file)
                    ) {

                        $new_pdf_path =
                            __DIR__
                            . DIRECTORY_SEPARATOR
                            . 'uploads'
                            . DIRECTORY_SEPARATOR
                            . basename(
                                $new_pdf_file
                            );


                        if (
                            is_file(
                                $new_pdf_path
                            )
                        ) {

                            @unlink(
                                $new_pdf_path
                            );
                        }
                    }


                    $new_pdf_file =
                        $old_pdf_file;
                }
            }
        }


        /* =================================================
           แสดงข้อมูลล่าสุดบนหน้า
        ================================================= */

        $project['title'] = $title;

        $project['project_name'] =
            $project_name;

        $project['description'] =
            $description;

        $project['degree'] =
            $degree;

        $project['project_type'] =
            $project_type;

        $project['department'] =
            $department;

        $project['authors'] =
            $authors;

        $project['student_name'] =
            $student_name;

        $project['advisor'] =
            $advisor;

        $project['github_url'] =
            $github_url;

        $project['pdf_file'] =
            $new_pdf_file;


        $authors_display =
            formatAuthors($authors);


        $current_advisor =
            $advisor;
    }
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

    <title>แก้ไขโปรเจกต์</title>

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
                Arial,
                "Sarabun",
                sans-serif;

            background: #f4f8fb;

            color: #333;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .header {

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6,
                    #4297cd,
                    #3287bb
                );

            color: white;

            padding:
                18px 40px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            box-shadow:
                0 3px 10px
                rgba(0,0,0,.12);
        }


        .header h2 {

            margin: 0;

            font-size: 22px;
        }


        .header a {

            color: white;

            text-decoration: none;

            background:
                rgba(255,255,255,.18);

            padding:
                10px 15px;

            border-radius: 8px;

            transition: .2s;
        }


        .header a:hover {

            background:
                rgba(255,255,255,.28);
        }


        /* =====================================================
           CONTAINER
        ===================================================== */

        .container {

            max-width: 850px;

            margin:
                40px auto;

            padding:
                0 20px;
        }


        /* =====================================================
           BOX
        ===================================================== */

        .box {

            background: white;

            border-radius: 15px;

            padding: 30px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.08);
        }


        .box h1 {

            margin-top: 0;

            color: #287cab;

            font-size: 25px;

            margin-bottom: 25px;
        }


        /* =====================================================
           PROJECT ID
        ===================================================== */

        .project-id {

            background: #eef8fd;

            color: #287cab;

            padding:
                12px 15px;

            border-radius: 8px;

            margin-bottom: 25px;

            font-size: 14px;

            border-left:
                4px solid #4297cd;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

            color: #444;
        }


        input,
        textarea,
        select {

            width: 100%;

            padding:
                12px 14px;

            border:
                1px solid #d8e0e5;

            border-radius: 8px;

            font-size: 15px;

            font-family: inherit;

            outline: none;

            background: white;

            transition: .2s;
        }


        input:focus,
        textarea:focus,
        select:focus {

            border-color: #4297cd;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,.12);
        }


        select {

            cursor: pointer;
        }


        textarea {

            min-height: 150px;

            resize: vertical;

            line-height: 1.8;
        }


        /* =====================================================
           AUTHORS
        ===================================================== */

        .authors-input {

            min-height: 150px;

            background: #fbfdff;

            line-height: 1.9;
        }


        .authors-input::placeholder {

            color: #aaa;
        }


        .authors-tip {

            margin-top: 8px;

            padding:
                10px 12px;

            background: #f1f8fc;

            border-radius: 7px;

            color: #6b7c87;

            font-size: 13px;

            line-height: 1.7;
        }


        .authors-tip i {

            color: #4297cd;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;
        }


        /* =====================================================
           HELP
        ===================================================== */

        .help {

            color: #777;

            font-size: 13px;

            margin-top: 6px;

            line-height: 1.5;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .error {

            background: #ffe6e6;

            color: #b00000;

            padding:
                12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;

            border-left:
                4px solid #dc3545;
        }


        /* =====================================================
           PDF
        ===================================================== */

        .current-pdf {

            margin-top: 8px;

            background: #f4f8fb;

            padding:
                12px;

            border-radius: 7px;

            font-size: 13px;

            border:
                1px solid #e4edf2;
        }


        .current-pdf a {

            color: #287cab;

            text-decoration: none;

            font-weight: bold;

            margin-left: 5px;
        }


        .current-pdf a:hover {

            text-decoration: underline;
        }


        /* =====================================================
           FILE
        ===================================================== */

        input[type="file"] {

            padding: 9px;

            background: #fafcfd;

            cursor: pointer;
        }


        input[type="file"]::file-selector-button {

            border: none;

            background: #4297cd;

            color: white;

            padding:
                8px 12px;

            border-radius: 6px;

            margin-right: 10px;

            cursor: pointer;
        }


        /* =====================================================
           OTHER INPUT
        ===================================================== */

        .other-input {

            margin-top: 10px;

            background: #fffdf5;

            border-color: #e5c76b;
        }


        .other-input:focus {

            border-color: #d5a900;

            box-shadow:
                0 0 0 3px
                rgba(213,169,0,.12);
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 30px;
        }


        .btn {

            flex: 1;

            border: none;

            padding:
                13px;

            border-radius: 8px;

            text-decoration: none;

            text-align: center;

            cursor: pointer;

            font-size: 15px;

            font-family: inherit;

            transition: .2s;
        }


        .btn-save {

            background: #198754;

            color: white;
        }


        .btn-cancel {

            background: #6c757d;

            color: white;
        }


        .btn:hover {

            opacity: .88;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 650px) {

            .grid {

                grid-template-columns: 1fr;
            }


            .header {

                padding:
                    15px 20px;
            }


            .header h2 {

                font-size: 18px;
            }


            .header a {

                padding:
                    8px 10px;

                font-size: 13px;
            }


            .box {

                padding: 20px;
            }


            .container {

                margin-top: 25px;
            }


            .buttons {

                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

    <h2>

        <i class="bi bi-pencil-square"></i>

        แก้ไขโปรเจกต์ของฉัน

    </h2>


    <a href="profile.php">

        <i class="bi bi-arrow-left"></i>

        กลับโปรไฟล์

    </a>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="container">

    <div class="box">


        <h1>

            <i class="bi bi-folder2-open"></i>

            แก้ไขข้อมูลโปรเจกต์

        </h1>


        <div class="project-id">

            <i class="bi bi-shield-check"></i>

            คุณกำลังแก้ไขโปรเจกต์ของตัวเอง

            <br>

            <i class="bi bi-hash"></i>

            Project ID:

            <strong>
                <?= (int)$project['id'] ?>
            </strong>

        </div>


        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="bi bi-exclamation-circle"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- =================================================
                 TITLE
            ================================================= -->

            <div class="form-group">

                <label for="title">

                    <i class="bi bi-folder-fill"></i>

                    ชื่อโปรเจกต์

                    <span style="color:#dc3545;">*</span>

                </label>


                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= e(
                        $project['title']
                        ?: $project['project_name']
                    ) ?>"
                    placeholder="กรอกชื่อโปรเจกต์"
                    required
                >

            </div>


            <!-- =================================================
                 DESCRIPTION
            ================================================= -->

            <div class="form-group">

                <label for="description">

                    <i class="bi bi-card-text"></i>

                    รายละเอียดโปรเจกต์

                </label>


                <textarea
                    id="description"
                    name="description"
                    placeholder="รายละเอียดของโปรเจกต์"
                ><?= e(
                    $project['description'] ?? ''
                ) ?></textarea>

            </div>


            <!-- =================================================
                 DEGREE + DEPARTMENT
            ================================================= -->

            <div class="grid">


                <!-- =================================================
                     ระดับการศึกษา
                ================================================= -->

                <div class="form-group">

                    <label for="degree">

                        <i class="bi bi-mortarboard-fill"></i>

                        ระดับการศึกษา

                    </label>


                    <select
                        name="degree"
                        id="degree"
                        onchange="toggleOtherDegree()"
                        required
                    >

                        <option value="">
                            -- เลือกระดับการศึกษา --
                        </option>


                        <?php foreach (
                            $degree_options
                            as $option
                        ): ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $degree_select === $option
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e($option) ?>

                            </option>

                        <?php endforeach; ?>


                        <option
                            value="อื่นๆ"
                            <?= $degree_select === 'อื่นๆ'
                                ? 'selected'
                                : '' ?>
                        >

                            อื่นๆ

                        </option>

                    </select>


                    <!-- ช่องพิมพ์เอง -->

                    <input
                        type="text"
                        name="other_degree"
                        id="other_degree"
                        class="other-input"
                        value="<?= e($other_degree) ?>"
                        placeholder="กรุณาระบุระดับการศึกษา"
                    >

                </div>


                <!-- =================================================
                     สาขา
                ================================================= -->

                <div class="form-group">

                    <label for="department">

                        <i class="bi bi-building"></i>

                        สาขา

                    </label>


                    <select
                        name="department"
                        id="department"
                        onchange="toggleOtherDepartment()"
                        required
                    >

                        <option value="">
                            -- เลือกสาขา --
                        </option>


                        <?php foreach (
                            $department_options
                            as $option
                        ): ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $department_select === $option
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e($option) ?>

                            </option>

                        <?php endforeach; ?>


                        <option
                            value="อื่นๆ"
                            <?= $department_select === 'อื่นๆ'
                                ? 'selected'
                                : '' ?>
                        >

                            อื่นๆ

                        </option>

                    </select>


                    <!-- ช่องพิมพ์เอง -->

                    <input
                        type="text"
                        name="other_department"
                        id="other_department"
                        class="other-input"
                        value="<?= e($other_department) ?>"
                        placeholder="กรุณาระบุสาขา"
                    >

                </div>


            </div>


            <!-- =================================================
                 AUTHORS
            ================================================= -->

            <div class="form-group">

                <label for="authors">

                    <i class="bi bi-people-fill"></i>

                    ผู้จัดทำ

                </label>


                <textarea
                    name="authors"
                    id="authors"
                    class="authors-input"
                    placeholder="กรอกชื่อสมาชิก คนละ 1 บรรทัด"
                ><?= e(
                    $authors_display
                ) ?></textarea>


                <div class="authors-tip">

                    <i class="bi bi-info-circle-fill"></i>

                    กรอกชื่อสมาชิก
                    <strong>คนละ 1 บรรทัด</strong>

                    <br>

                    <i class="bi bi-check-circle-fill"></i>

                    เช่น นายสมชาย ใจดี

                    <br>

                    <i class="bi bi-check-circle-fill"></i>

                    เช่น นางสาวสมหญิง ใจดี

                </div>

            </div>


            <!-- =================================================
                 ADVISOR
            ================================================= -->

            <div class="form-group">

                <label for="advisor">

                    <i class="bi bi-person-workspace"></i>

                    อาจารย์ที่ปรึกษา

                </label>


                <select
                    name="advisor"
                    id="advisor"
                >

                    <option value="">

                        -- เลือกอาจารย์ที่ปรึกษา --

                    </option>


                    <?php foreach (
                        $teachers
                        as $teacher
                    ): ?>

                        <?php

                        $prefix =
                            trim(
                                $teacher['prefix'] ?? ''
                            );

                        $first_name =
                            trim(
                                $teacher['first_name'] ?? ''
                            );

                        $last_name =
                            trim(
                                $teacher['last_name'] ?? ''
                            );


                        $teacher_fullname =
                            trim(
                                $prefix
                                . ' '
                                . $first_name
                                . ' '
                                . $last_name
                            );


                        $teacher_name_without_prefix =
                            trim(
                                $first_name
                                . ' '
                                . $last_name
                            );


                        $selected =
                            (
                                $current_advisor
                                === $teacher_fullname
                                ||
                                $current_advisor
                                === $teacher_name_without_prefix
                            );

                        ?>


                        <option
                            value="<?= e(
                                $teacher_fullname
                            ) ?>"
                            <?= $selected
                                ? 'selected'
                                : '' ?>
                        >

                            <?= e(
                                $teacher_fullname
                            ) ?>

                        </option>


                    <?php endforeach; ?>

                </select>


                <div class="help">

                    <i class="bi bi-info-circle"></i>

                    เลือกอาจารย์ที่ปรึกษาจากรายชื่อในระบบ

                </div>

            </div>


            <!-- =================================================
                 GITHUB
            ================================================= -->

            <div class="form-group">

                <label for="github_url">

                    <i class="bi bi-github"></i>

                    GitHub URL

                </label>


                <input
                    type="url"
                    id="github_url"
                    name="github_url"
                    value="<?= e(
                        $project['github_url'] ?? ''
                    ) ?>"
                    placeholder="https://github.com/username/project"
                >


                <div class="help">

                    <i class="bi bi-info-circle"></i>

                    ถ้าไม่มี GitHub สามารถเว้นว่างได้

                </div>

            </div>


            <!-- =================================================
                 PDF เดิม
            ================================================= -->

            <div class="form-group">

                <label>

                    <i class="bi bi-file-earmark-pdf-fill"></i>

                    PDF โปรเจกต์

                </label>


                <?php if (
                    !empty($project['pdf_file'])
                ): ?>

                    <div class="current-pdf">

                        <i class="bi bi-file-earmark-pdf"></i>

                        มีไฟล์ PDF อยู่แล้ว


                        <a
                            href="uploads/<?= rawurlencode(
                                basename(
                                    $project['pdf_file']
                                )
                            ) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >

                            <i class="bi bi-box-arrow-up-right"></i>

                            เปิดดู PDF

                        </a>

                    </div>

                <?php else: ?>

                    <div class="current-pdf">

                        <i class="bi bi-file-earmark-x"></i>

                        ยังไม่มีไฟล์ PDF

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 PDF ใหม่
            ================================================= -->

            <div class="form-group">

                <label for="pdf_file">

                    <i class="bi bi-upload"></i>

                    เปลี่ยนไฟล์ PDF

                </label>


                <input
                    type="file"
                    id="pdf_file"
                    name="pdf_file"
                    accept=".pdf,application/pdf"
                >


                <div class="help">

                    <i class="bi bi-info-circle"></i>

                    ถ้าไม่เลือกไฟล์ จะใช้ PDF เดิม

                    <br>

                    <i class="bi bi-file-earmark-pdf"></i>

                    รองรับเฉพาะ PDF ขนาดไม่เกิน 10 MB

                </div>

            </div>


            <!-- =================================================
                 BUTTON
            ================================================= -->

            <div class="buttons">


                <a
                    href="profile.php"
                    class="btn btn-cancel"
                >

                    <i class="bi bi-x-circle"></i>

                    ยกเลิก

                </a>


                <button
                    type="submit"
                    class="btn btn-save"
                >

                    <i class="bi bi-check-circle"></i>

                    บันทึกการแก้ไข

                </button>


            </div>


        </form>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

/* =========================================================
   ระดับการศึกษา
========================================================= */

function toggleOtherDegree() {

    const degree =
        document.getElementById('degree');

    const otherDegree =
        document.getElementById('other_degree');


    if (degree.value === 'อื่นๆ') {

        otherDegree.style.display = 'block';

        otherDegree.required = true;

    } else {

        otherDegree.style.display = 'none';

        otherDegree.required = false;

    }
}


/* =========================================================
   สาขา
========================================================= */

function toggleOtherDepartment() {

    const department =
        document.getElementById('department');

    const otherDepartment =
        document.getElementById('other_department');


    if (department.value === 'อื่นๆ') {

        otherDepartment.style.display = 'block';

        otherDepartment.required = true;

    } else {

        otherDepartment.style.display = 'none';

        otherDepartment.required = false;

    }
}


/* =========================================================
   ตอนเปิดหน้า
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        toggleOtherDegree();

        toggleOtherDepartment();

    }
);

</script>


</body>

</html>