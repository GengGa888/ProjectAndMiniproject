<?php

session_start();

include 'db_connect.php';


/* =========================================================
   HELPER
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
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo "
    <script>
        alert('กรุณาเข้าสู่ระบบก่อนใช้งาน');
        window.location.href='login.php';
    </script>
    ";

    exit();
}


$user_id = (int)$_SESSION['user_id'];


/* =========================================================
   GET CURRENT USER
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

        $user_data =
            mysqli_fetch_assoc($result) ?: [];
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   CURRENT USER NAME
========================================================= */

$first_name =
    trim(
        $user_data['first_name']
        ?? $user_data['firstname']
        ?? ''
    );


$last_name =
    trim(
        $user_data['last_name']
        ?? $user_data['lastname']
        ?? ''
    );


$student_name =
    trim(
        $first_name . ' ' . $last_name
    );


if ($student_name === '') {

    $student_name =
        $user_data['username']
        ?? 'นักศึกษา';
}


/* =========================================================
   USER DEPARTMENT
========================================================= */

$user_department =
    trim(
        $user_data['department']
        ?? ''
    );


/* =========================================================
   GET PROJECT COLUMNS
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
   GET USER COLUMNS
========================================================= */

$user_columns = [];


$user_columns_result = mysqli_query(
    $conn,
    "SHOW COLUMNS FROM users"
);


if ($user_columns_result) {

    while (
        $column = mysqli_fetch_assoc(
            $user_columns_result
        )
    ) {

        $user_columns[] =
            $column['Field'];
    }
}


/* =========================================================
   GET TEACHERS
========================================================= */

$teachers = [];


if (
    in_array('role', $user_columns) &&
    in_array('first_name', $user_columns) &&
    in_array('last_name', $user_columns)
) {

    if (
        in_array(
            'prefix',
            $user_columns
        )
    ) {

        $teacher_sql = "
            SELECT
                id,
                prefix,
                first_name,
                last_name
            FROM users
            WHERE role = 'teacher'
            ORDER BY
                first_name ASC,
                last_name ASC
        ";

    } else {

        $teacher_sql = "
            SELECT
                id,
                first_name,
                last_name
            FROM users
            WHERE role = 'teacher'
            ORDER BY
                first_name ASC,
                last_name ASC
        ";
    }


    $teacher_result =
        mysqli_query(
            $conn,
            $teacher_sql
        );


    if ($teacher_result) {

        while (
            $teacher =
            mysqli_fetch_assoc(
                $teacher_result
            )
        ) {

            $teachers[] =
                $teacher;
        }
    }
}


/* =========================================================
   VARIABLES
========================================================= */

$error = '';

$title = '';

$degree = '';

$project_department = '';

$authors = '';

$advisor = '';

$github_url = '';


/* =========================================================
   SUBMIT PROJECT
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /* =====================================================
       TITLE
    ===================================================== */

    $title =
        trim(
            $_POST['title']
            ?? ''
        );


    /* =====================================================
       DEGREE
    ===================================================== */

    $degree =
        trim(
            $_POST['degree']
            ?? ''
        );


    /* =====================================================
       DEPARTMENT
    ===================================================== */

    $department_select =
        trim(
            $_POST['department']
            ?? ''
        );


    $other_department =
        trim(
            $_POST['other_department']
            ?? ''
        );


    if (
        $department_select === 'อื่นๆ'
    ) {

        $project_department =
            $other_department;

    } else {

        $project_department =
            $department_select;
    }


    /* =====================================================
       AUTHORS
    ===================================================== */

    $authors =
        trim(
            $_POST['authors']
            ?? ''
        );


    /*
     * ถ้าไม่ได้กรอกสมาชิก
     * ใช้ชื่อผู้ส่งเป็นสมาชิกหลัก
     */

    if ($authors === '') {

        $authors =
            $student_name;
    }


    /* =====================================================
       ADVISOR
    ===================================================== */

    $advisor =
        trim(
            $_POST['advisor']
            ?? ''
        );


    /* =====================================================
       GITHUB
    ===================================================== */

    $github_url =
        trim(
            $_POST['github_url']
            ?? ''
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($title === '') {

        $error =
            'กรุณากรอกชื่อโปรเจกต์';

    } elseif ($degree === '') {

        $error =
            'กรุณาเลือกระดับการศึกษา';

    } elseif (
        $project_department === ''
    ) {

        $error =
            'กรุณาเลือกสาขาวิชา';

    } elseif ($authors === '') {

        $error =
            'กรุณากรอกสมาชิกกลุ่ม';

    } elseif (
        !isset(
            $_FILES['pdf_file']
        )
    ) {

        $error =
            'กรุณาเลือกไฟล์ PDF';

    } elseif (
        $_FILES['pdf_file']['error']
        !== UPLOAD_ERR_OK
    ) {

        $error =
            'ไม่สามารถอัปโหลดไฟล์ได้';
    }


    /* =====================================================
       CHECK ADVISOR
    ===================================================== */

    if (
        $error === '' &&
        $advisor !== ''
    ) {

        $advisor_allowed =
            false;


        foreach (
            $teachers as $teacher
        ) {

            $prefix =
                trim(
                    $teacher['prefix']
                    ?? ''
                );


            $teacher_first_name =
                trim(
                    $teacher['first_name']
                    ?? ''
                );


            $teacher_last_name =
                trim(
                    $teacher['last_name']
                    ?? ''
                );


            $teacher_fullname =
                trim(
                    $prefix .
                    ' ' .
                    $teacher_first_name .
                    ' ' .
                    $teacher_last_name
                );


            $teacher_without_prefix =
                trim(
                    $teacher_first_name .
                    ' ' .
                    $teacher_last_name
                );


            if (
                $advisor ===
                $teacher_fullname
                ||
                $advisor ===
                $teacher_without_prefix
            ) {

                $advisor_allowed =
                    true;

                break;
            }
        }


        if (!$advisor_allowed) {

            $error =
                'กรุณาเลือกอาจารย์ที่ปรึกษาจากรายการ';
        }
    }


    /* =====================================================
       CHECK GITHUB URL
    ===================================================== */

    if (
        $error === '' &&
        $github_url !== ''
    ) {

        if (
            !filter_var(
                $github_url,
                FILTER_VALIDATE_URL
            )
        ) {

            $error =
                'กรุณากรอกลิงก์ GitHub ให้ถูกต้อง';
        }
    }


    /* =====================================================
       PDF UPLOAD
    ===================================================== */

    if ($error === '') {

        $file =
            $_FILES['pdf_file'];


        $file_name =
            $file['name'];


        $file_tmp =
            $file['tmp_name'];


        $file_size =
            (int)$file['size'];


        /* =================================================
           FILE EXTENSION
        ================================================= */

        $extension =
            strtolower(
                pathinfo(
                    $file_name,
                    PATHINFO_EXTENSION
                )
            );


        if (
            $extension !== 'pdf'
        ) {

            $error =
                'อนุญาตเฉพาะไฟล์ PDF เท่านั้น';

        } elseif (
            $file_size >
            (20 * 1024 * 1024)
        ) {

            $error =
                'ไฟล์ต้องมีขนาดไม่เกิน 20 MB';

        } else {


            /* =============================================
               CHECK MIME
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


            finfo_close(
                $finfo
            );


            if (
                $mime !==
                'application/pdf'
            ) {

                $error =
                    'ไฟล์ที่เลือกไม่ใช่ PDF ที่ถูกต้อง';

            } else {


                /* =========================================
                   UPLOAD DIRECTORY
                ========================================= */

                $upload_dir =
                    __DIR__ .
                    '/uploads/';


                if (
                    !is_dir(
                        $upload_dir
                    )
                ) {

                    if (
                        !mkdir(
                            $upload_dir,
                            0777,
                            true
                        )
                    ) {

                        $error =
                            'ไม่สามารถสร้างโฟลเดอร์ uploads ได้';
                    }
                }


                /* =========================================
                   NEW FILE NAME
                ========================================= */

                if ($error === '') {

                    try {

                        $random_name =
                            bin2hex(
                                random_bytes(4)
                            );

                    } catch (
                        Exception $exception
                    ) {

                        $random_name =
                            uniqid();
                    }


                    $new_file_name =
                        'project_' .
                        $user_id .
                        '_' .
                        time() .
                        '_' .
                        $random_name .
                        '.pdf';


                    $destination =
                        $upload_dir .
                        $new_file_name;


                    /* =====================================
                       MOVE FILE
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


                        /* =================================
                           TITLE
                        ================================= */

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


                        /* =================================
                           PROJECT NAME
                        ================================= */

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


                        /* =================================
                           DEGREE
                        ================================= */

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


                        /* =================================
                           PROJECT TYPE
                        ================================= */

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


                        /* =================================
                           DEPARTMENT
                        ================================= */

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


                        /* =================================
                           AUTHORS
                        ================================= */

                        if (
                            in_array(
                                'authors',
                                $project_columns
                            )
                        ) {

                            $insert_columns[] =
                                'authors';

                            $insert_values[] =
                                $authors;
                        }


                        /* =================================
                           STUDENT NAME
                        ================================= */

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


                        /* =================================
                           ADVISOR
                        ================================= */

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


                        /* =================================
                           PDF
                        ================================= */

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


                        /* =================================
                           GITHUB
                        ================================= */

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


                        /* =================================
                           STATUS
                        ================================= */

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


                        /* =================================
                           STUDENT ID
                        ================================= */

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


                        /* =================================
                           AUTHOR ID
                        ================================= */

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


                        /* =================================
                           CREATED BY
                        ================================= */

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


                        /* =================================
                           CHECK COLUMNS
                        ================================= */

                        if (
                            count(
                                $insert_columns
                            ) === 0
                        ) {

                            $error =
                                'ไม่พบคอลัมน์สำหรับบันทึกข้อมูลโปรเจกต์';

                            if (
                                file_exists(
                                    $destination
                                )
                            ) {

                                unlink(
                                    $destination
                                );
                            }

                        } else {


                            /* =============================
                               BUILD SQL
                            ============================= */

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


                            /* =============================
                               PREPARE
                            ============================= */

                            $stmt =
                                mysqli_prepare(
                                    $conn,
                                    $sql
                                );


                            if ($stmt) {


                                /* =========================
                                   BIND TYPES
                                ========================= */

                                $types = '';


                                foreach (
                                    $insert_columns
                                    as $column
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


                                /* =========================
                                   BIND PARAM
                                ========================= */

                                mysqli_stmt_bind_param(
                                    $stmt,
                                    $types,
                                    ...$insert_values
                                );


                                /* =========================
                                   EXECUTE
                                ========================= */

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
                                        window.location.href='index2.php';
                                    </script>
                                    ";

                                    exit();


                                } else {

                                    $error =
                                        'ไม่สามารถบันทึกข้อมูลได้: ' .
                                        mysqli_stmt_error(
                                            $stmt
                                        );


                                    mysqli_stmt_close(
                                        $stmt
                                    );


                                    if (
                                        file_exists(
                                            $destination
                                        )
                                    ) {

                                        unlink(
                                            $destination
                                        );
                                    }
                                }

                            } else {

                                $error =
                                    'ไม่สามารถเตรียมคำสั่ง SQL ได้: ' .
                                    mysqli_error(
                                        $conn
                                    );


                                if (
                                    file_exists(
                                        $destination
                                    )
                                ) {

                                    unlink(
                                        $destination
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

            background:
                linear-gradient(
                    180deg,
                    #f4f8fb 0%,
                    #ffffff 45%
                );

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
                rgba(0, 0, 0, 0.16);

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


        /* ========================================
           LOGO
        ======================================== */

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

            transition:
                transform 0.3s ease;
        }


        .logo-link:hover
        .sdu-logo {

            transform:
                scale(1.06);
        }


        /* ========================================
           HOME
        ======================================== */

        .home-link {

            display: flex;

            align-items: center;

            gap: 8px;

            color: white !important;

            text-decoration: none;

            font-size: 17px;

            font-weight: 600;

            padding: 10px 16px;

            border-radius: 10px;

            transition:
                background 0.25s ease,
                transform 0.25s ease;
        }


        .home-link:hover {

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.16
                );

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

            border:
                2px solid white;

            cursor: pointer;

            background: white;

            transition:
                all 0.25s ease;
        }


        .profile-image:hover {

            transform:
                scale(1.06);

            box-shadow:
                0 4px 12px
                rgba(
                    0,
                    0,
                    0,
                    0.18
                );
        }


        /* ========================================
           PROFILE DROPDOWN
        ======================================== */

        .custom-profile-menu {

            background-color:
                #1a1b26;

            border:
                1px solid #2f334d;

            border-radius: 12px;

            box-shadow:
                0 10px 25px
                rgba(
                    0,
                    0,
                    0,
                    0.3
                );

            min-width: 220px;

            padding: 8px;
        }


        .custom-profile-menu
        .dropdown-item {

            color:
                #a9b1d6;

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

            background-color:
                #24283b;

            color:
                #ffffff;
        }


        .custom-profile-menu
        .logout-btn {

            color:
                #f7768e;
        }


        /* ========================================
           MAIN
        ======================================== */

        .main-container {

            max-width:
                850px;

            margin:
                40px auto;

            padding:
                0 20px;
        }


        .form-card {

            background:
                white;

            border-radius:
                14px;

            border:
                1px solid #e2e8f0;

            box-shadow:
                0 5px 25px
                rgba(
                    0,
                    0,
                    0,
                    0.06
                );

            overflow:
                hidden;
        }


        /* ========================================
           FORM HEADER
        ======================================== */

        .form-header {

            background:
                linear-gradient(
                    135deg,
                    #4297CD,
                    #55A7D7
                );

            color:
                white;

            padding:
                28px 35px;
        }


        .form-header h1 {

            font-size:
                1.5rem;

            font-weight:
                700;

            margin-bottom:
                5px;
        }


        .form-header p {

            margin:
                0;

            opacity:
                0.9;
        }


        /* ========================================
           FORM BODY
        ======================================== */

        .form-body {

            padding:
                30px 35px;
        }


        .form-label {

            font-weight:
                600;

            color:
                #334155;
        }


        .required {

            color:
                #dc3545;
        }


        .form-control,
        .form-select {

            border-color:
                #d8dee4;

            border-radius:
                8px;

            padding:
                10px 12px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                #4297CD;

            box-shadow:
                0 0 0 0.2rem
                rgba(
                    66,
                    151,
                    205,
                    0.15
                );
        }


        /* ========================================
           AUTHORS
        ======================================== */

        .authors-box {

            background:
                #f7fbfe;

            border:
                1px solid #dceef8;

            border-radius:
                10px;

            padding:
                16px;
        }


        .authors-icon {

            color:
                #4297CD;

            font-size:
                18px;

            margin-right:
                5px;
        }


        .authors-help {

            font-size:
                13px;

            color:
                #64748b;

            margin-top:
                6px;
        }


        .authors-box textarea {

            min-height:
                130px;

            resize:
                vertical;
        }


        /* ========================================
           OTHER DEPARTMENT
        ======================================== */

        #otherDepartmentBox {

            display:
                none;

            margin-top:
                10px;
        }


        /* ========================================
           GITHUB
        ======================================== */

        .github-box {

            background:
                #f8fafc;

            border:
                1px solid #e1e7ec;

            border-radius:
                10px;

            padding:
                16px;
        }


        .github-label {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

            font-weight:
                600;

            color:
                #24292f;

            margin-bottom:
                8px;
        }


        .github-label i {

            font-size:
                20px;
        }


        .github-help {

            color:
                #64748b;

            font-size:
                13px;

            margin-top:
                6px;
        }


        /* ========================================
           FILE
        ======================================== */

        .file-box {

            border:
                2px dashed #b8d8ea;

            background:
                #f7fbfe;

            border-radius:
                10px;

            padding:
                25px;

            text-align:
                center;

            transition:
                all 0.25s ease;
        }


        .file-box:hover {

            border-color:
                #4297CD;

            background:
                #f0f9ff;
        }


        .file-icon {

            font-size:
                42px;

            color:
                #4297CD;

            margin-bottom:
                8px;
        }


        .file-text {

            color:
                #64748b;

            font-size:
                0.9rem;

            margin-bottom:
                15px;
        }


        .file-input {

            max-width:
                500px;

            margin:
                0 auto;
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

            border:
                none;

            color:
                white;

            padding:
                11px 25px;

            border-radius:
                8px;

            font-weight:
                600;

            transition:
                all 0.25s ease;

            box-shadow:
                0 4px 10px
                rgba(
                    66,
                    151,
                    205,
                    0.25
                );
        }


        .btn-save:hover {

            color:
                white;

            transform:
                translateY(-2px);

            box-shadow:
                0 6px 15px
                rgba(
                    66,
                    151,
                    205,
                    0.30
                );
        }


        .btn-cancel {

            border:
                1px solid #cbd5e1;

            color:
                #475569;

            background:
                white;

            padding:
                11px 25px;

            border-radius:
                8px;

            text-decoration:
                none;

            font-weight:
                600;
        }


        .btn-cancel:hover {

            background:
                #f1f5f9;

            color:
                #334155;
        }


        .help-text {

            font-size:
                0.82rem;

            color:
                #64748b;

            margin-top:
                5px;
        }


        /* ========================================
           MOBILE
        ======================================== */

        @media (
            max-width: 768px
        ) {

            .custom-header {

                height:
                    78px;
            }


            .header-left-area {

                gap:
                    8px;
            }


            .sdu-logo {

                width:
                    48px;

                height:
                    48px;
            }


            .home-link {

                font-size:
                    15px;

                padding:
                    9px 12px;
            }


            .home-icon {

                font-size:
                    17px;
            }


            .profile-image {

                width:
                    42px;

                height:
                    42px;
            }


            .main-container {

                margin-top:
                    25px;

                padding:
                    0 15px;
            }


            .form-header {

                padding:
                    22px;
            }


            .form-body {

                padding:
                    22px;
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

                <i
                    class="
                        bi
                        bi-house-fill
                        home-icon
                    "
                ></i>

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
                        ?? ''
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
                        !empty(
                            $profile_image
                        )
                        &&
                        file_exists(
                            $profile_file
                        )
                    ): ?>

                        <img
                            src="<?php
                                echo e(
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

        </div>

    </div>

</header>


<!-- ========================================
     MAIN
======================================== -->

<div class="main-container">


    <div class="form-card">


        <!-- FORM HEADER -->

        <div class="form-header">

            <h1>

                <i
                    class="
                        bi
                        bi-cloud-arrow-up
                        me-2
                    "
                ></i>

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
                    class="
                        alert
                        alert-danger
                    "
                    role="alert"
                >

                    <i
                        class="
                            bi
                            bi-exclamation-circle
                            me-2
                        "
                    ></i>

                    <?php
                    echo e($error);
                    ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

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
                            echo e($title);
                        ?>"
                        required
                    >

                </div>


                <!-- =================================
                     DEGREE + DEPARTMENT
                ================================== -->

                <div class="row">


                    <!-- DEGREE -->

                    <div
                        class="
                            col-md-6
                            mb-4
                        "
                    >

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
                                echo (
                                    $degree ===
                                    'ปริญญาตรี'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >
                                ปริญญาตรี
                            </option>


                            <option
                                value="ปริญญาโท"
                                <?php
                                echo (
                                    $degree ===
                                    'ปริญญาโท'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >
                                ปริญญาโท
                            </option>


                            <option
                                value="ปริญญาเอก"
                                <?php
                                echo (
                                    $degree ===
                                    'ปริญญาเอก'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >
                                ปริญญาเอก
                            </option>

                        </select>

                    </div>


                    <!-- DEPARTMENT -->

                    <div
                        class="
                            col-md-6
                            mb-4
                        "
                    >

                        <label
                            for="department"
                            class="form-label"
                        >

                            สาขาวิชา

                            <span class="required">
                                *
                            </span>

                        </label>


                        <?php

                        $department_list = [

                            'เทคโนโลยีสารสนเทศ',

                            'วิทยาการคอมพิวเตอร์',

                            'วิทยาศาสตร์สิ่งแวดล้อม',

                            'เทคโนโลยีการประกอบอาหาร'

                        ];


                        $is_other_department =
                            (
                                $project_department !== ''
                                &&
                                !in_array(
                                    $project_department,
                                    $department_list
                                )
                            );

                        ?>


                        <select
                            name="department"
                            id="department"
                            class="form-select"
                            required
                            onchange="toggleOtherDepartment()"
                        >

                            <option value="">
                                -- เลือกสาขาวิชา --
                            </option>


                            <?php foreach (
                                $department_list
                                as $department
                            ): ?>

                                <option
                                    value="<?php
                                        echo e(
                                            $department
                                        );
                                    ?>"
                                    <?php
                                    echo (
                                        $project_department ===
                                        $department
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    <?php
                                    echo e(
                                        $department
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>


                            <option
                                value="อื่นๆ"
                                <?php
                                echo $is_other_department
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                อื่นๆ

                            </option>

                        </select>


                        <!-- OTHER DEPARTMENT -->

                        <div
                            id="otherDepartmentBox"
                        >

                            <input
                                type="text"
                                name="other_department"
                                id="other_department"
                                class="form-control"
                                placeholder="กรอกชื่อสาขาวิชา"
                                value="<?php
                                    echo $is_other_department
                                        ? e(
                                            $project_department
                                        )
                                        : '';
                                ?>"
                            >

                        </div>

                    </div>

                </div>


                <!-- =================================
                     AUTHORS
                ================================== -->

                <div
                    class="
                        authors-box
                        mb-4
                    "
                >

                    <label
                        for="authors"
                        class="form-label"
                    >

                        <i
                            class="
                                bi
                                bi-people-fill
                                authors-icon
                            "
                        ></i>

                        สมาชิกกลุ่ม

                        <span class="required">
                            *
                        </span>

                    </label>


                    <textarea
                        name="authors"
                        id="authors"
                        class="form-control"
                        placeholder="กรอกชื่อสมาชิกกลุ่มทีละคน"
                        required
                    ><?php
                        echo e($authors);
                    ?></textarea>


                    <div
                        class="authors-help"
                    >

                        <i
                            class="
                                bi
                                bi-info-circle
                            "
                        ></i>

                        ใส่สมาชิกได้หลายคน
                        โดยขึ้นบรรทัดใหม่สำหรับสมาชิกแต่ละคน

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
                                    $teacher['prefix']
                                    ?? ''
                                );


                            $teacher_first_name =
                                trim(
                                    $teacher[
                                        'first_name'
                                    ]
                                    ?? ''
                                );


                            $teacher_last_name =
                                trim(
                                    $teacher[
                                        'last_name'
                                    ]
                                    ?? ''
                                );


                            $teacher_fullname =
                                trim(
                                    $prefix .
                                    ' ' .
                                    $teacher_first_name .
                                    ' ' .
                                    $teacher_last_name
                                );


                            $teacher_without_prefix =
                                trim(
                                    $teacher_first_name .
                                    ' ' .
                                    $teacher_last_name
                                );


                            $selected =
                                (
                                    $advisor ===
                                    $teacher_fullname
                                )
                                ||
                                (
                                    $advisor ===
                                    $teacher_without_prefix
                                );

                            ?>


                            <option
                                value="<?php
                                    echo e(
                                        $teacher_fullname
                                    );
                                ?>"
                                <?php
                                echo $selected
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo e(
                                    $teacher_fullname
                                );
                                ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                    <div
                        class="help-text"
                    >

                        <i
                            class="
                                bi
                                bi-info-circle
                                me-1
                            "
                        ></i>

                        เลือกอาจารย์จากรายชื่อที่มีในระบบ

                    </div>

                </div>


                <!-- =================================
                     CURRENT USER
                ================================== -->

                <div class="mb-4">

                    <label
                        class="form-label"
                    >

                        ผู้ส่งโปรเจกต์

                    </label>


                    <input
                        type="text"
                        class="form-control"
                        value="<?php
                            echo e(
                                $student_name
                            );
                        ?>"
                        readonly
                    >


                    <div
                        class="help-text"
                    >

                        ระบบจะบันทึกชื่อจากบัญชีที่กำลังเข้าสู่ระบบอัตโนมัติ

                    </div>

                </div>


                <!-- =================================
                     GITHUB
                ================================== -->

                <div
                    class="
                        github-box
                        mb-4
                    "
                >

                    <label
                        for="github_url"
                        class="github-label"
                    >

                        <i
                            class="
                                bi
                                bi-github
                            "
                        ></i>

                        GitHub Repository

                    </label>


                    <input
                        type="url"
                        name="github_url"
                        id="github_url"
                        class="form-control"
                        placeholder="https://github.com/username/project"
                        value="<?php
                            echo e(
                                $github_url
                            );
                        ?>"
                    >


                    <div
                        class="github-help"
                    >

                        <i
                            class="
                                bi
                                bi-info-circle
                            "
                        ></i>

                        ใส่ลิงก์ GitHub ของโปรเจกต์
                        ถ้าไม่มีสามารถเว้นว่างได้

                    </div>

                </div>


                <!-- =================================
                     PDF
                ================================== -->

                <div class="mb-4">

                    <label
                        for="pdf_file"
                        class="form-label"
                    >

                        ไฟล์โปรเจกต์ PDF

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="file-box">


                        <div class="file-icon">

                            <i
                                class="
                                    bi
                                    bi-file-earmark-pdf
                                "
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
                            class="
                                form-control
                                file-input
                            "
                            accept=".pdf,application/pdf"
                            required
                        >


                    </div>

                </div>


                <!-- =================================
                     BUTTONS
                ================================== -->

                <div
                    class="
                        d-flex
                        justify-content-end
                        gap-2
                        mt-4
                    "
                >

                    <a
                        href="index2.php"
                        class="btn-cancel"
                    >

                        <i
                            class="
                                bi
                                bi-arrow-left
                                me-1
                            "
                        ></i>

                        ยกเลิก

                    </a>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <i
                            class="
                                bi
                                bi-cloud-arrow-up
                                me-1
                            "
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

function toggleOtherDepartment()
{
    const department =
        document.getElementById(
            'department'
        );


    const otherBox =
        document.getElementById(
            'otherDepartmentBox'
        );


    const otherInput =
        document.getElementById(
            'other_department'
        );


    if (
        !department ||
        !otherBox ||
        !otherInput
    ) {

        return;
    }


    if (
        department.value === 'อื่นๆ'
    ) {

        otherBox.style.display =
            'block';

        otherInput.required =
            true;

    } else {

        otherBox.style.display =
            'none';

        otherInput.required =
            false;

        otherInput.value =
            '';
    }
}


/* ========================================
   RUN WHEN PAGE LOADS
======================================== */

document.addEventListener(
    'DOMContentLoaded',
    function()
    {
        toggleOtherDepartment();
    }
);

</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>