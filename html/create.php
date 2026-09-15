<?php

session_start();
include 'db_connect.php';

/* =========================================================
   ตรวจสอบ Login
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo "<script>
        alert('กรุณาเข้าสู่ระบบก่อนใช้งาน');
        window.location.href='login.php';
    </script>";

    exit();
}

$user_id = (int)$_SESSION['user_id'];

$user_role = $_SESSION['role'] ?? '';

/* =========================================================
   ดึงข้อมูลผู้ใช้ปัจจุบัน
========================================================= */

$user_data = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM users WHERE id = ? LIMIT 1"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $user_data = mysqli_fetch_assoc($result) ?: [];
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   ชื่อผู้ส่ง
========================================================= */

$first_name =
    $user_data['first_name']
    ?? $user_data['firstname']
    ?? '';

$last_name =
    $user_data['last_name']
    ?? $user_data['lastname']
    ?? '';

$student_name = trim(
    $first_name . ' ' . $last_name
);

if ($student_name === '') {

    $student_name =
        $user_data['username']
        ?? 'นักศึกษา';
}


/* =========================================================
   สาขาวิชาของผู้ใช้
========================================================= */

$user_department =
    trim(
        $user_data['department'] ?? ''
    );


/* =========================================================
   ดึงรายชื่ออาจารย์
========================================================= */

$teachers = [];

$teacher_sql = "
    SELECT
        id,
        prefix,
        first_name,
        last_name
    FROM users
    WHERE role = 'teacher'
    ORDER BY first_name ASC, last_name ASC
";

$teacher_result = mysqli_query(
    $conn,
    $teacher_sql
);

if ($teacher_result) {

    while (
        $teacher = mysqli_fetch_assoc(
            $teacher_result
        )
    ) {

        $teachers[] = $teacher;
    }
}


/* =========================================================
   ตรวจสอบ Columns ใน projects
========================================================= */

$project_columns = [];

$columns_result = mysqli_query(
    $conn,
    "SHOW COLUMNS FROM projects"
);

if ($columns_result) {

    while (
        $column = mysqli_fetch_assoc(
            $columns_result
        )
    ) {

        $project_columns[] =
            $column['Field'];
    }
}


/* =========================================================
   ตัวแปร
========================================================= */

$error = '';

$title = '';
$degree = '';
$project_department = '';
$other_department = '';
$advisor = '';
$github_url = '';

$selected_department = '';


/* =========================================================
   รับข้อมูลจาก Form
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim(
        $_POST['title'] ?? ''
    );

    $degree = trim(
        $_POST['degree'] ?? ''
    );

    $selected_department = trim(
        $_POST['department'] ?? ''
    );

    $other_department = trim(
        $_POST['other_department'] ?? ''
    );

    $advisor = trim(
        $_POST['advisor'] ?? ''
    );

    $github_url = trim(
        $_POST['github_url'] ?? ''
    );


    /* =====================================================
       สาขาวิชา
    ===================================================== */

    if ($selected_department === 'อื่นๆ') {

        $project_department =
            $other_department;

    } else {

        $project_department =
            $selected_department;
    }


    /* =====================================================
       ตรวจสอบข้อมูล
    ===================================================== */

    if ($title === '') {

        $error =
            'กรุณากรอกชื่อโปรเจกต์';

    } elseif ($degree === '') {

        $error =
            'กรุณาเลือกระดับการศึกษา';

    } elseif ($project_department === '') {

        $error =
            'กรุณาเลือกสาขาวิชา';

    } elseif (
        $selected_department === 'อื่นๆ'
        &&
        $other_department === ''
    ) {

        $error =
            'กรุณากรอกชื่อสาขาวิชา';

    } elseif (
        !empty($github_url)
        &&
        !filter_var(
            $github_url,
            FILTER_VALIDATE_URL
        )
    ) {

        $error =
            'ลิงก์ GitHub ไม่ถูกต้อง';

    } elseif (
        !isset($_FILES['pdf_file'])
    ) {

        $error =
            'กรุณาเลือกไฟล์ PDF';

    } elseif (
        $_FILES['pdf_file']['error']
        !== UPLOAD_ERR_OK
    ) {

        $error =
            'ไม่สามารถอัปโหลดไฟล์ได้';

    } else {


        /* =================================================
           FILE
        ================================================= */

        $file =
            $_FILES['pdf_file'];

        $file_name =
            $file['name'];

        $file_tmp =
            $file['tmp_name'];

        $file_size =
            $file['size'];


        $extension =
            strtolower(
                pathinfo(
                    $file_name,
                    PATHINFO_EXTENSION
                )
            );


        /* =================================================
           ตรวจสอบ PDF
        ================================================= */

        if ($extension !== 'pdf') {

            $error =
                'อนุญาตเฉพาะไฟล์ PDF เท่านั้น';

        } elseif (
            $file_size >
            20 * 1024 * 1024
        ) {

            $error =
                'ไฟล์ต้องมีขนาดไม่เกิน 20 MB';

        } else {


            /* =============================================
               ตรวจสอบ MIME
            ============================================= */

            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );

            $mime =
                finfo_file(
                    $finfo,
                    $file_tmp
                );

            finfo_close($finfo);


            if ($mime !== 'application/pdf') {

                $error =
                    'ไฟล์ที่เลือกไม่ใช่ PDF ที่ถูกต้อง';

            } else {


                /* =========================================
                   ตรวจสอบอาจารย์
                ========================================= */

                $advisor_valid = true;

                if ($advisor !== '') {

                    $advisor_valid = false;

                    foreach (
                        $teachers
                        as $teacher
                    ) {

                        $prefix =
                            trim(
                                $teacher['prefix']
                                ?? ''
                            );

                        $teacher_first =
                            trim(
                                $teacher['first_name']
                                ?? ''
                            );

                        $teacher_last =
                            trim(
                                $teacher['last_name']
                                ?? ''
                            );


                        $teacher_fullname =
                            trim(
                                $prefix .
                                ' ' .
                                $teacher_first .
                                ' ' .
                                $teacher_last
                            );


                        $teacher_without_prefix =
                            trim(
                                $teacher_first .
                                ' ' .
                                $teacher_last
                            );


                        if (
                            $advisor ===
                            $teacher_fullname
                            ||
                            $advisor ===
                            $teacher_without_prefix
                        ) {

                            $advisor_valid = true;

                            break;
                        }
                    }
                }


                if (!$advisor_valid) {

                    $error =
                        'กรุณาเลือกอาจารย์ที่ปรึกษาจากรายการ';

                } else {


                    /* =====================================
                       สร้าง uploads
                    ===================================== */

                    $upload_dir =
                        __DIR__ .
                        '/uploads/';


                    if (
                        !is_dir(
                            $upload_dir
                        )
                    ) {

                        mkdir(
                            $upload_dir,
                            0777,
                            true
                        );
                    }


                    /* =====================================
                       สร้างชื่อไฟล์ใหม่
                    ===================================== */

                    $new_file_name =
                        'project_' .
                        $user_id .
                        '_' .
                        time() .
                        '_' .
                        bin2hex(
                            random_bytes(4)
                        ) .
                        '.pdf';


                    $destination =
                        $upload_dir .
                        $new_file_name;


                    /* =====================================
                       ย้ายไฟล์
                    ===================================== */

                    if (
                        move_uploaded_file(
                            $file_tmp,
                            $destination
                        )
                    ) {


                        $pdf_file =
                            $new_file_name;


                        /* =================================
                           INSERT COLUMNS
                        ================================= */

                        $insert_columns = [];

                        $insert_values = [];


                        /* ---------------------------------
                           ชื่อโปรเจกต์
                        --------------------------------- */

                        if (
                            in_array(
                                'project_name',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'project_name';

                            $insert_values[] =
                                $title;
                        }


                        /* ---------------------------------
                           project_type
                        --------------------------------- */

                        if (
                            in_array(
                                'project_type',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'project_type';

                            $insert_values[] =
                                $degree;
                        }


                        /* ---------------------------------
                           student_name
                        --------------------------------- */

                        if (
                            in_array(
                                'student_name',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'student_name';

                            $insert_values[] =
                                $student_name;
                        }


                        /* ---------------------------------
                           title
                        --------------------------------- */

                        if (
                            in_array(
                                'title',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'title';

                            $insert_values[] =
                                $title;
                        }


                        /* ---------------------------------
                           description
                        --------------------------------- */

                        if (
                            in_array(
                                'description',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'description';

                            $insert_values[] =
                                '';
                        }


                        /* ---------------------------------
                           degree
                        --------------------------------- */

                        if (
                            in_array(
                                'degree',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'degree';

                            $insert_values[] =
                                $degree;
                        }


                        /* ---------------------------------
                           department
                        --------------------------------- */

                        if (
                            in_array(
                                'department',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'department';

                            $insert_values[] =
                                $project_department;
                        }


                        /* ---------------------------------
                           authors
                        --------------------------------- */

                        if (
                            in_array(
                                'authors',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'authors';

                            $insert_values[] =
                                $student_name;
                        }


                        /* ---------------------------------
                           advisor
                        --------------------------------- */

                        if (
                            in_array(
                                'advisor',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'advisor';

                            $insert_values[] =
                                $advisor;
                        }


                        /* ---------------------------------
                           PDF
                        --------------------------------- */

                        if (
                            in_array(
                                'pdf_file',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'pdf_file';

                            $insert_values[] =
                                $pdf_file;
                        }


                        /* ---------------------------------
                           GitHub
                        --------------------------------- */

                        if (
                            in_array(
                                'github_url',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'github_url';

                            $insert_values[] =
                                $github_url;
                        }


                        /* ---------------------------------
                           status
                        --------------------------------- */

                        if (
                            in_array(
                                'status',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'status';

                            $insert_values[] =
                                'ส่งแล้ว';
                        }


                        /* ---------------------------------
                           student_id
                        --------------------------------- */

                        if (
                            in_array(
                                'student_id',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'student_id';

                            $insert_values[] =
                                $user_id;
                        }


                        /* ---------------------------------
                           author_id
                        --------------------------------- */

                        if (
                            in_array(
                                'author_id',
                                $project_columns
                            )
                            &&
                            !in_array(
                                'student_id',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'author_id';

                            $insert_values[] =
                                $user_id;
                        }


                        /* ---------------------------------
                           created_by
                        --------------------------------- */

                        if (
                            in_array(
                                'created_by',
                                $project_columns
                            )
                            &&
                            !in_array(
                                'student_id',
                                $project_columns
                            )
                            &&
                            !in_array(
                                'author_id',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'created_by';

                            $insert_values[] =
                                $user_id;
                        }


                        /* =================================================
                           สร้าง SQL
                        ================================================= */

                        if (
                            count(
                                $insert_columns
                            ) === 0
                        ) {

                            if (
                                file_exists(
                                    $destination
                                )
                            ) {

                                unlink(
                                    $destination
                                );
                            }

                            $error =
                                'ไม่พบคอลัมน์ที่สามารถบันทึกข้อมูลได้';

                        } else {


                            $column_sql =
                                implode(
                                    ', ',
                                    $insert_columns
                                );


                            $placeholders =
                                implode(
                                    ', ',
                                    array_fill(
                                        0,
                                        count(
                                            $insert_values
                                        ),
                                        '?'
                                    )
                                );


                            $sql =
                                "INSERT INTO projects
                                ($column_sql)
                                VALUES
                                ($placeholders)";


                            $stmt =
                                mysqli_prepare(
                                    $conn,
                                    $sql
                                );


                            if (!$stmt) {

                                if (
                                    file_exists(
                                        $destination
                                    )
                                ) {

                                    unlink(
                                        $destination
                                    );
                                }

                                $error =
                                    'ไม่สามารถเตรียมคำสั่ง SQL ได้: ' .
                                    mysqli_error(
                                        $conn
                                    );

                            } else {


                                /* =====================================
                                   bind_param
                                ===================================== */

                                $types = '';

                                foreach (
                                    $insert_columns
                                    as $index => $column
                                ) {

                                    if (
                                        in_array(
                                            $column,
                                            [
                                                'student_id',
                                                'author_id',
                                                'created_by'
                                            ]
                                        )
                                    ) {

                                        $types .= 'i';

                                    } else {

                                        $types .= 's';
                                    }
                                }


                                mysqli_stmt_bind_param(
                                    $stmt,
                                    $types,
                                    ...$insert_values
                                );


                                /* =====================================
                                   Execute
                                ===================================== */

                                if (
                                    mysqli_stmt_execute(
                                        $stmt
                                    )
                                ) {

                                    mysqli_stmt_close(
                                        $stmt
                                    );


                                    echo "
                                    <script>
                                        alert('ส่งโปรเจกต์เรียบร้อยแล้ว');
                                        window.location.href='submit-project.php';
                                    </script>
                                    ";

                                    exit();

                                } else {


                                    if (
                                        file_exists(
                                            $destination
                                        )
                                    ) {

                                        unlink(
                                            $destination
                                        );
                                    }


                                    $error =
                                        'ไม่สามารถบันทึกข้อมูลได้: ' .
                                        mysqli_stmt_error(
                                            $stmt
                                        );


                                    mysqli_stmt_close(
                                        $stmt
                                    );
                                }
                            }
                        }
                    } else {

                        $error =
                            'ไม่สามารถบันทึกไฟล์ลงโฟลเดอร์ uploads ได้';
                    }
                }
            }
        }
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

    <title>
        ส่งโปรเจกต์ - คลังโปรเจกต์ SDU
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


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: #f4f7f9;

            color: #2c3e50;

            font-family:
                'Sarabun',
                'Segoe UI',
                Tahoma,
                sans-serif;

            min-height: 100vh;
        }


        /* ========================================
           HEADER
        ======================================== */

        .custom-header {

            width: 100%;

            height: 90px;

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6 0%,
                    #4297CD 50%,
                    #3287BB 100%
                );

            color: white;

            display: flex;

            align-items: center;

            box-shadow:
                0 2px 8px
                rgba(0,0,0,0.16);

            position: relative;

            z-index: 100;
        }


        .header-inner {

            width: 100%;

            max-width: 1280px;

            margin: 0 auto;

            padding: 0 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .header-left-area {

            display: flex;

            align-items: center;

            gap: 14px;
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

            display: block;

            transition: 0.3s;
        }


        .logo-link:hover .sdu-logo {

            transform: scale(1.06);

            filter:
                drop-shadow(
                    0 4px 7px
                    rgba(0,0,0,0.18)
                );
        }


        /* ========================================
           HOME
        ======================================== */

        .home-link {

            position: relative;

            display: flex;

            align-items: center;

            gap: 8px;

            color: white !important;

            text-decoration: none;

            font-size: 17px;

            font-weight: 600;

            padding: 10px 16px;

            border-radius: 10px;

            transition: 0.25s;
        }


        .home-link:hover {

            background:
                rgba(255,255,255,0.16);

            transform:
                translateY(-2px);
        }


        .home-icon {

            font-size: 19px;
        }


        /* ========================================
           PROFILE
        ======================================== */

        .header-right {

            display: flex;

            align-items: center;
        }


        .profile-image {

            width: 45px;

            height: 45px;

            border-radius: 50%;

            object-fit: cover;

            border: 2px solid white;

            cursor: pointer;

            background: white;

            transition: 0.25s;
        }


        .profile-image:hover {

            opacity: 0.9;

            transform:
                scale(1.06);

            box-shadow:
                0 4px 12px
                rgba(0,0,0,0.18);
        }


        /* ========================================
           PROFILE DROPDOWN
        ======================================== */

        .custom-profile-menu {

            background-color: #1a1b26;

            border:
                1px solid #2f334d;

            border-radius: 12px;

            box-shadow:
                0 10px 25px
                rgba(0,0,0,0.3);

            min-width: 220px;

            padding: 8px;
        }


        .custom-profile-menu
        .dropdown-item {

            color: #a9b1d6;

            font-size: 0.95rem;

            padding: 10px 14px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .custom-profile-menu
        .dropdown-item:hover {

            background-color: #24283b;

            color: white;
        }


        .custom-profile-menu
        .logout-btn {

            color: #f7768e;
        }


        .custom-profile-menu
        .logout-btn:hover {

            background:
                rgba(247,118,142,0.15);

            color: #ff6c6b;
        }


        .custom-profile-menu
        .dropdown-divider {

            border-color: #2f334d;
        }


        /* ========================================
           MAIN
        ======================================== */

        .main-container {

            max-width: 850px;

            margin: 40px auto;

            padding: 0 20px;
        }


        .form-card {

            background: white;

            border-radius: 14px;

            border:
                1px solid #e2e8f0;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,0.06);

            overflow: hidden;
        }


        .form-header {

            background:
                linear-gradient(
                    135deg,
                    #4297CD,
                    #55A7D7
                );

            color: white;

            padding: 28px 35px;
        }


        .form-header h1 {

            font-size: 1.5rem;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .form-header p {

            margin: 0;

            opacity: 0.9;
        }


        .form-body {

            padding: 30px 35px;
        }


        /* ========================================
           FORM
        ======================================== */

        .form-label {

            font-weight: 600;

            color: #334155;
        }


        .form-control,
        .form-select {

            border-color: #d8dee4;

            border-radius: 8px;

            padding: 10px 12px;

            transition: 0.2s;
        }


        .form-control:focus,
        .form-select:focus {

            border-color: #4297CD;

            box-shadow:
                0 0 0 0.2rem
                rgba(66,151,205,0.15);
        }


        .required {

            color: #dc3545;
        }


        .help-text {

            font-size: 0.82rem;

            color: #64748b;

            margin-top: 5px;
        }


        /* ========================================
           GITHUB
        ======================================== */

        .github-box {

            background:
                #f8fafc;

            border:
                1px solid #e1e7ec;

            border-radius: 10px;

            padding: 16px;
        }


        .github-label {

            display: flex;

            align-items: center;

            gap: 7px;

            font-weight: 600;

            color: #334155;

            margin-bottom: 8px;
        }


        .github-label i {

            font-size: 20px;

            color: #24292f;
        }


        /* ========================================
           PDF
        ======================================== */

        .file-box {

            border:
                2px dashed #b8d8ea;

            background: #f7fbfe;

            border-radius: 10px;

            padding: 25px;

            text-align: center;

            transition: 0.25s;
        }


        .file-box:hover {

            border-color: #4297CD;

            background: #f0f9ff;

            transform:
                translateY(-2px);
        }


        .file-icon {

            font-size: 42px;

            color: #4297CD;

            margin-bottom: 8px;
        }


        .file-text {

            color: #64748b;

            font-size: 0.9rem;

            margin-bottom: 15px;
        }


        .file-input {

            max-width: 500px;

            margin: 0 auto;
        }


        /* ========================================
           BUTTON
        ======================================== */

        .btn-save {

            background:
                linear-gradient(
                    135deg,
                    #4297CD,
                    #3586BA
                );

            border: none;

            color: white;

            padding: 11px 25px;

            border-radius: 8px;

            font-weight: 600;

            transition: 0.25s;

            box-shadow:
                0 4px 10px
                rgba(66,151,205,0.25);
        }


        .btn-save:hover {

            color: white;

            transform:
                translateY(-2px);

            box-shadow:
                0 6px 15px
                rgba(66,151,205,0.30);
        }


        .btn-cancel {

            border:
                1px solid #cbd5e1;

            color: #475569;

            background: white;

            padding: 11px 25px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: 600;

            transition: 0.2s;
        }


        .btn-cancel:hover {

            background: #f1f5f9;

            color: #334155;
        }


        /* ========================================
           MOBILE
        ======================================== */

        @media (max-width: 768px) {

            .custom-header {

                height: 78px;
            }


            .sdu-logo {

                width: 48px;

                height: 48px;
            }


            .home-link {

                font-size: 15px;

                padding: 9px 12px;
            }


            .profile-image {

                width: 42px;

                height: 42px;
            }


            .main-container {

                margin-top: 25px;

                padding: 0 15px;
            }


            .form-header {

                padding: 22px;
            }


            .form-body {

                padding: 22px;
            }
        }

    </style>

</head>


<body>


<!-- ========================================
     HEADER
======================================== -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">


            <!-- LOGO -->

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

                <i class="bi bi-house-fill home-icon"></i>

                <span>
                    หน้าแรก
                </span>

            </a>

        </div>


        <!-- PROFILE -->

        <div class="header-right">

            <div class="dropdown">

                <?php

                $profile_image = '';

                if (
                    !empty(
                        $user_data['profile_image']
                    )
                ) {

                    $profile_image =
                        'profile_uploads/' .
                        basename(
                            $user_data[
                                'profile_image'
                            ]
                        );
                }

                $profile_file =
                    __DIR__ .
                    '/profile_uploads/' .
                    basename(
                        $user_data[
                            'profile_image'
                        ] ?? ''
                    );

                ?>


                <a
                    href="#"
                    role="button"
                    id="profileDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >

                    <?php if (
                        !empty($profile_image)
                        &&
                        file_exists(
                            $profile_file
                        )
                    ): ?>

                        <img
                            src="<?php
                                echo htmlspecialchars(
                                    $profile_image
                                );
                            ?>"
                            alt="โปรไฟล์"
                            class="profile-image"
                        >

                    <?php else: ?>

                        <img
                            src="https://cdn-icons-png.flaticon.com/512/149/149071.png"
                            alt="โปรไฟล์"
                            class="profile-image"
                        >

                    <?php endif; ?>

                </a>


                <ul
                    class="dropdown-menu dropdown-menu-end custom-profile-menu mt-2"
                    aria-labelledby="profileDropdown"
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

</header>


<!-- ========================================
     MAIN
======================================== -->

<div class="main-container">

    <div class="form-card">


        <!-- HEADER -->

        <div class="form-header">

            <h1>

                <i class="bi bi-cloud-arrow-up me-2"></i>

                ส่งโปรเจกต์ใหม่

            </h1>


            <p>

                กรอกข้อมูลโครงงานและอัปโหลดไฟล์ PDF

            </p>

        </div>


        <!-- FORM BODY -->

        <div class="form-body">


            <!-- ERROR -->

            <?php if (
                !empty($error)
            ): ?>

                <div
                    class="alert alert-danger"
                    role="alert"
                >

                    <i
                        class="bi bi-exclamation-circle me-2"
                    ></i>

                    <?php
                    echo htmlspecialchars(
                        $error
                    );
                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =================================
                     TITLE
                ================================== -->

                <div class="mb-4">

                    <label
                        for="title"
                        class="form-label"
                    >

                        ชื่อโปรเจกต์

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        name="title"
                        id="title"
                        class="form-control"
                        placeholder="กรอกชื่อโปรเจกต์"
                        value="<?php
                            echo htmlspecialchars(
                                $title
                            );
                        ?>"
                        required
                    >

                </div>


                <!-- =================================
                     DEGREE + DEPARTMENT
                ================================== -->

                <div class="row">


                    <!-- DEGREE -->

                    <div class="col-md-6 mb-4">

                        <label
                            for="degree"
                            class="form-label"
                        >

                            ระดับการศึกษา

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            name="degree"
                            id="degree"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- เลือกระดับการศึกษา --
                            </option>


                            <option
                                value="ปริญญาตรี"
                                <?php
                                echo
                                    $degree ===
                                    'ปริญญาตรี'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ปริญญาตรี
                            </option>


                            <option
                                value="ปริญญาโท"
                                <?php
                                echo
                                    $degree ===
                                    'ปริญญาโท'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ปริญญาโท
                            </option>


                            <option
                                value="ปริญญาเอก"
                                <?php
                                echo
                                    $degree ===
                                    'ปริญญาเอก'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ปริญญาเอก
                            </option>

                        </select>

                    </div>


                    <!-- DEPARTMENT -->

                    <div class="col-md-6 mb-4">

                        <label
                            for="department"
                            class="form-label"
                        >

                            สาขาวิชา

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            name="department"
                            id="department"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- เลือกสาขาวิชา --
                            </option>


                            <option
                                value="เทคโนโลยีสารสนเทศ"
                                <?php
                                echo
                                    $selected_department ===
                                    'เทคโนโลยีสารสนเทศ'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                เทคโนโลยีสารสนเทศ
                            </option>


                            <option
                                value="วิทยาการคอมพิวเตอร์"
                                <?php
                                echo
                                    $selected_department ===
                                    'วิทยาการคอมพิวเตอร์'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                วิทยาการคอมพิวเตอร์
                            </option>


                            <option
                                value="วิทยาศาสตร์สิ่งแวดล้อม"
                                <?php
                                echo
                                    $selected_department ===
                                    'วิทยาศาสตร์สิ่งแวดล้อม'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                วิทยาศาสตร์สิ่งแวดล้อม
                            </option>


                            <option
                                value="เทคโนโลยีการประกอบอาหาร"
                                <?php
                                echo
                                    $selected_department ===
                                    'เทคโนโลยีการประกอบอาหาร'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                เทคโนโลยีการประกอบอาหาร
                            </option>


                            <option
                                value="อื่นๆ"
                                <?php
                                echo
                                    $selected_department ===
                                    'อื่นๆ'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                อื่นๆ
                            </option>

                        </select>

                    </div>

                </div>


                <!-- =================================
                     OTHER DEPARTMENT
                ================================== -->

                <div
                    class="mb-4"
                    id="otherDepartmentBox"
                    style="
                        display:
                        <?php
                        echo
                            $selected_department ===
                            'อื่นๆ'
                            ? 'block'
                            : 'none';
                        ?>;
                    "
                >

                    <label
                        for="other_department"
                        class="form-label"
                    >

                        ระบุชื่อสาขาวิชา

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        name="other_department"
                        id="other_department"
                        class="form-control"
                        placeholder="กรอกชื่อสาขาวิชา"
                        value="<?php
                            echo htmlspecialchars(
                                $other_department
                            );
                        ?>"
                    >

                </div>


                <!-- =================================
                     AUTHORS
                ================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <i class="bi bi-people me-1"></i>

                        สมาชิกกลุ่ม

                        <span class="required">
                            *
                        </span>

                    </label>


                    <textarea
                        name="authors"
                        id="authors"
                        class="form-control"
                        rows="4"
                        placeholder="กรอกชื่อสมาชิกกลุ่ม เช่น&#10;นายสมชาย ใจดี&#10;นางสาวสมหญิง ใจดี"
                        required
                    ><?php
                        echo htmlspecialchars(
                            $_POST['authors']
                            ??
                            $student_name
                        );
                    ?></textarea>


                    <div class="help-text">

                        สามารถใส่สมาชิกหลายคนได้
                        โดยแยกเป็นคนละบรรทัด

                    </div>

                </div>


                <!-- =================================
                     ADVISOR
                ================================== -->

                <div class="mb-4">

                    <label
                        for="advisor"
                        class="form-label"
                    >

                        <i class="bi bi-person-workspace me-1"></i>

                        อาจารย์ที่ปรึกษา

                    </label>


                    <select
                        name="advisor"
                        id="advisor"
                        class="form-select"
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
                                    $teacher[
                                        'prefix'
                                    ] ?? ''
                                );

                            $teacher_first =
                                trim(
                                    $teacher[
                                        'first_name'
                                    ] ?? ''
                                );

                            $teacher_last =
                                trim(
                                    $teacher[
                                        'last_name'
                                    ] ?? ''
                                );


                            $teacher_fullname =
                                trim(
                                    $prefix .
                                    ' ' .
                                    $teacher_first .
                                    ' ' .
                                    $teacher_last
                                );


                            $selected =
                                $advisor ===
                                $teacher_fullname;

                            ?>

                            <option
                                value="<?php
                                    echo htmlspecialchars(
                                        $teacher_fullname
                                    );
                                ?>"
                                <?php
                                echo
                                    $selected
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $teacher_fullname
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <div class="help-text">

                        เลือกอาจารย์จากรายชื่อในระบบ

                    </div>

                </div>


                <!-- =================================
                     GITHUB
                ================================== -->

                <div class="mb-4 github-box">

                    <label
                        for="github_url"
                        class="github-label"
                    >

                        <i class="bi bi-github"></i>

                        <span>
                            ลิงก์ GitHub
                        </span>

                    </label>


                    <input
                        type="url"
                        name="github_url"
                        id="github_url"
                        class="form-control"
                        placeholder="https://github.com/username/project"
                        value="<?php
                            echo htmlspecialchars(
                                $github_url
                            );
                        ?>"
                    >


                    <div class="help-text">

                        ใส่ลิงก์ GitHub ของโปรเจกต์
                        เช่น https://github.com/username/project

                    </div>

                </div>


                <!-- =================================
                     STUDENT
                ================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        ผู้ส่งโปรเจกต์

                    </label>


                    <input
                        type="text"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $student_name
                            );
                        ?>"
                        readonly
                    >


                    <div class="help-text">

                        ระบบจะบันทึกชื่อจากบัญชีที่กำลังเข้าสู่ระบบอัตโนมัติ

                    </div>

                </div>


                <!-- =================================
                     PDF
                ================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        ไฟล์โปรเจกต์ PDF

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="file-box">


                        <div class="file-icon">

                            <i
                                class="bi bi-file-earmark-pdf"
                            ></i>

                        </div>


                        <div class="file-text">

                            เลือกไฟล์โปรเจกต์ที่ต้องการส่ง

                            <br>

                            รองรับเฉพาะ PDF
                            ขนาดไม่เกิน 20 MB

                        </div>


                        <input
                            type="file"
                            name="pdf_file"
                            id="pdf_file"
                            class="form-control file-input"
                            accept=".pdf,application/pdf"
                            required
                        >

                    </div>

                </div>


                <!-- =================================
                     BUTTONS
                ================================== -->

                <div
                    class="d-flex
                           justify-content-end
                           gap-2
                           mt-4"
                >


                    <a
                        href="submit-project.php"
                        class="btn-cancel"
                    >

                        <i
                            class="bi bi-arrow-left me-1"
                        ></i>

                        ยกเลิก

                    </a>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <i
                            class="bi bi-cloud-arrow-up me-1"
                        ></i>

                        ส่งโปรเจกต์

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- ========================================
     JAVASCRIPT
======================================== -->

<script>

    const department =
        document.getElementById(
            'department'
        );

    const otherDepartmentBox =
        document.getElementById(
            'otherDepartmentBox'
        );

    const otherDepartment =
        document.getElementById(
            'other_department'
        );


    department.addEventListener(
        'change',
        function () {

            if (
                this.value === 'อื่นๆ'
            ) {

                otherDepartmentBox.style.display =
                    'block';

                otherDepartment.required =
                    true;

            } else {

                otherDepartmentBox.style.display =
                    'none';

                otherDepartment.required =
                    false;

                otherDepartment.value =
                    '';
            }

        }
    );

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>