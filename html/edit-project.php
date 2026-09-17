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


/*
 * ทำชื่อให้เป็นรูปแบบเดียวกัน
 * ป้องกันกรณีมีช่องว่างเกิน
 */
function normalizeName($value)
{
    $value = trim((string)$value);

    $value = preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    return trim($value);
}


/* =========================================================
   LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo "<script>
        alert('กรุณาเข้าสู่ระบบก่อนใช้งาน');
        window.location.href='login.php';
    </script>";

    exit();
}


$user_id = intval(
    $_SESSION['user_id']
);


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

    mysqli_stmt_execute(
        $stmt
    );

    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    if ($result) {

        $user_data =
            mysqli_fetch_assoc(
                $result
            ) ?: [];
    }


    mysqli_stmt_close(
        $stmt
    );
}


/* =========================================================
   CURRENT USER NAME
   ผู้จัดทำ = เจ้าของโปรเจกต์
========================================================= */

$prefix =
    trim(
        $user_data['prefix'] ?? ''
    );


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
        $prefix . ' ' .
        $first_name . ' ' .
        $last_name
    );


/*
 * ถ้าไม่มีชื่อ
 * ใช้ username
 */

if ($student_name === '') {

    $student_name =
        trim(
            $user_data['username']
            ?? 'นักศึกษา'
        );
}


/*
 * ชื่อเจ้าของแบบ normalize
 * ใช้สำหรับเปรียบเทียบชื่อ
 */

$normalized_owner =
    normalizeName(
        $student_name
    );


/* =========================================================
   USER DEPARTMENT
========================================================= */

$user_department =
    trim(
        $user_data['department']
        ?? ''
    );


/* =========================================================
   GET PROJECT ID
========================================================= */

$project_id =
    isset($_GET['id'])
        ? intval($_GET['id'])
        : 0;


if ($project_id <= 0) {

    echo "<script>
        alert('ไม่พบโปรเจกต์ที่ต้องการแก้ไข');
        window.location.href='index2.php';
    </script>";

    exit();
}


/* =========================================================
   GET PROJECT COLUMNS
========================================================= */

$project_columns = [];


$columns_result =
    mysqli_query(
        $conn,
        "SHOW COLUMNS FROM projects"
    );


if ($columns_result) {

    while (
        $column =
        mysqli_fetch_assoc(
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


$user_columns_result =
    mysqli_query(
        $conn,
        "SHOW COLUMNS FROM users"
    );


if ($user_columns_result) {

    while (
        $column =
        mysqli_fetch_assoc(
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
    in_array(
        'role',
        $user_columns
    )
    &&
    in_array(
        'first_name',
        $user_columns
    )
    &&
    in_array(
        'last_name',
        $user_columns
    )
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
   GET PROJECT
   แก้ได้เฉพาะโปรเจกต์ของตัวเอง
========================================================= */

$project = [];


$project_stmt =
    mysqli_prepare(
        $conn,
        "
        SELECT *
        FROM projects
        WHERE id = ?
          AND student_id = ?
        LIMIT 1
        "
    );


if (!$project_stmt) {

    die(
        'ไม่สามารถเตรียมคำสั่ง SQL ได้: '
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $project_stmt,
    "ii",
    $project_id,
    $user_id
);


mysqli_stmt_execute(
    $project_stmt
);


$project_result =
    mysqli_stmt_get_result(
        $project_stmt
    );


if ($project_result) {

    $project =
        mysqli_fetch_assoc(
            $project_result
        ) ?: [];
}


mysqli_stmt_close(
    $project_stmt
);


/* =========================================================
   CHECK PROJECT
========================================================= */

if (empty($project)) {

    echo "<script>
        alert('ไม่พบโปรเจกต์ หรือคุณไม่มีสิทธิ์แก้ไขโปรเจกต์นี้');
        window.location.href='index2.php';
    </script>";

    exit();
}


/* =========================================================
   VARIABLES
========================================================= */

$error = '';


$title =
    trim(
        $project['title']
        ?? $project['project_name']
        ?? ''
    );


$degree =
    trim(
        $project['degree']
        ?? $project['project_type']
        ?? ''
    );


$project_department =
    trim(
        $project['department']
        ?? ''
    );


$advisor =
    trim(
        $project['advisor']
        ?? ''
    );


$github_url =
    trim(
        $project['github_url']
        ?? ''
    );


$pdf_file =
    trim(
        $project['pdf_file']
        ?? ''
    );


$members = '';


/* =========================================================
   AUTHORS
   =========================================================

   DATABASE:

   authors =
   เจ้าของ
   สมาชิก B
   สมาชิก C
   สมาชิก D

   FORM:

   members =
   สมาชิก B
   สมาชิก C
   สมาชิก D

   สำคัญ:
   เจ้าของจะไม่แสดงในช่องสมาชิก
========================================================= */

$authors_source =
    trim(
        $project['authors']
        ?? ''
    );


if ($authors_source !== '') {

    $author_lines =
        preg_split(
            '/\r\n|\r|\n/',
            $authors_source,
            -1,
            PREG_SPLIT_NO_EMPTY
        );


    $clean_members = [];


    /*
     * ใช้ตัวแปรตรวจว่าเจอเจ้าของแล้วหรือยัง
     */
    $owner_found = false;


    foreach (
        $author_lines
        as $index => $author
    ) {

        $author =
            trim(
                $author
            );


        if ($author === '') {

            continue;
        }


        $normalized_author =
            normalizeName(
                $author
            );


        /* =================================================
           จุดสำคัญ
           
           คนแรกใน authors ถือเป็นเจ้าของ
           ไม่เอาลงช่องสมาชิก
        ================================================= */

        if (
            $index === 0
        ) {

            $owner_found = true;

            continue;
        }


        /* =================================================
           ถ้าชื่อตรงกับเจ้าของ
           ก็ไม่เอาลงสมาชิก
        ================================================= */

        if (
            $normalized_author ===
            $normalized_owner
        ) {

            continue;
        }


        /* =================================================
           ป้องกันชื่อซ้ำ
        ================================================= */

        $duplicate = false;


        foreach (
            $clean_members
            as $existing_member
        ) {

            if (
                normalizeName(
                    $existing_member
                )
                ===
                $normalized_author
            ) {

                $duplicate = true;

                break;
            }
        }


        if (
            !$duplicate
        ) {

            $clean_members[] =
                $author;
        }
    }


    $members =
        implode(
            "\n",
            $clean_members
        );
}


/* =========================================================
   FALLBACK
   =========================================================

   ถ้า authors เก่าไม่มีเจ้าของเป็นคนแรก
   ให้ค้นจากชื่อเจ้าของก่อน
========================================================= */

if (
    $authors_source !== ''
    &&
    $members === ''
) {

    $author_lines =
        preg_split(
            '/\r\n|\r|\n/',
            $authors_source,
            -1,
            PREG_SPLIT_NO_EMPTY
        );


    $fallback_members = [];


    foreach (
        $author_lines
        as $author
    ) {

        $author =
            trim(
                $author
            );


        if ($author === '') {

            continue;
        }


        if (
            normalizeName($author)
            ===
            $normalized_owner
        ) {

            continue;
        }


        if (
            !in_array(
                $author,
                $fallback_members,
                true
            )
        ) {

            $fallback_members[] =
                $author;
        }
    }


    $members =
        implode(
            "\n",
            $fallback_members
        );
}


/* =========================================================
   PDF URL
========================================================= */

$current_pdf_url = '';


if ($pdf_file !== '') {

    $current_pdf_url =
        'uploads/'
        . rawurlencode(
            basename(
                $pdf_file
            )
        );
}


/* =========================================================
   SUBMIT UPDATE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
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
        $department_select
        === 'อื่นๆ'
    ) {

        $project_department =
            $other_department;

    } else {

        $project_department =
            $department_select;
    }


    /* =====================================================
       GROUP MEMBERS
       สมาชิกกลุ่มเพิ่มเติมเท่านั้น
    ===================================================== */

    $members =
        trim(
            $_POST['members']
            ?? ''
        );


    $member_lines = [];


    if ($members !== '') {

        $member_lines =
            preg_split(
                '/\r\n|\r|\n/',
                $members,
                -1,
                PREG_SPLIT_NO_EMPTY
            );
    }


    $clean_members = [];


    foreach (
        $member_lines
        as $member
    ) {

        $member =
            trim(
                $member
            );


        if ($member === '') {

            continue;
        }


        $normalized_member =
            normalizeName(
                $member
            );


        /* =================================================
           ห้ามใส่เจ้าของซ้ำ
        ================================================= */

        if (
            $normalized_member
            ===
            $normalized_owner
        ) {

            continue;
        }


        /* =================================================
           ป้องกันชื่อซ้ำ
        ================================================= */

        $duplicate = false;


        foreach (
            $clean_members
            as $existing_member
        ) {

            if (
                normalizeName(
                    $existing_member
                )
                ===
                $normalized_member
            ) {

                $duplicate = true;

                break;
            }
        }


        if (
            !$duplicate
        ) {

            $clean_members[] =
                $member;
        }
    }


    /* =====================================================
       AUTHORS
       
       เจ้าของต้องอยู่คนแรกเสมอ
    ===================================================== */

    $authors_list = [];


    /*
     * เจ้าของจากบัญชี Login
     * ใส่เป็นคนแรกเสมอ
     */

    if (
        $student_name !== ''
    ) {

        $authors_list[] =
            $student_name;
    }


    /*
     * สมาชิกกลุ่ม
     */

    foreach (
        $clean_members
        as $member
    ) {

        $normalized_member =
            normalizeName(
                $member
            );


        /*
         * ป้องกันเจ้าของซ้ำ
         */

        if (
            $normalized_member
            ===
            $normalized_owner
        ) {

            continue;
        }


        /*
         * ป้องกันชื่อซ้ำกับคนก่อนหน้า
         */

        $duplicate = false;


        foreach (
            $authors_list
            as $existing_author
        ) {

            if (
                normalizeName(
                    $existing_author
                )
                ===
                $normalized_member
            ) {

                $duplicate = true;

                break;
            }
        }


        if (
            !$duplicate
        ) {

            $authors_list[] =
                $member;
        }
    }


    /*
     * แปลงเป็นข้อความ
     */

    $authors =
        implode(
            "\n",
            $authors_list
        );


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
       VALIDATE
    ===================================================== */

    if (
        $title === ''
    ) {

        $error =
            'กรุณากรอกชื่อโปรเจกต์';

    } elseif (
        $degree === ''
    ) {

        $error =
            'กรุณาเลือกระดับการศึกษา';

    } elseif (
        $project_department === ''
    ) {

        $error =
            'กรุณาเลือกสาขาวิชา';
    }


    /* =====================================================
       CHECK GITHUB
    ===================================================== */

    if (
        $error === ''
        &&
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
       CHECK ADVISOR
    ===================================================== */

    if (
        $error === ''
        &&
        $advisor !== ''
    ) {

        $advisor_allowed =
            false;


        foreach (
            $teachers
            as $teacher
        ) {

            $teacher_prefix =
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
                    $teacher_prefix
                    . ' '
                    . $teacher_first
                    . ' '
                    . $teacher_last
                );


            $teacher_without_prefix =
                trim(
                    $teacher_first
                    . ' '
                    . $teacher_last
                );


            if (
                normalizeName($advisor)
                ===
                normalizeName($teacher_fullname)
                ||
                normalizeName($advisor)
                ===
                normalizeName($teacher_without_prefix)
            ) {

                /*
                 * บันทึกชื่อเต็ม
                 */

                $advisor =
                    $teacher_fullname;


                $advisor_allowed =
                    true;


                break;
            }
        }


        if (
            !$advisor_allowed
        ) {

            $error =
                'กรุณาเลือกอาจารย์ที่ปรึกษาจากรายการ';
        }
    }


    /* =====================================================
       PDF
    ===================================================== */

    $new_pdf_file =
        $pdf_file;


    $new_destination =
        '';


    $uploaded_new_pdf =
        false;


    if (
        $error === ''
        &&
        isset(
            $_FILES['pdf_file']
        )
        &&
        $_FILES['pdf_file']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['pdf_file'];


        /* =================================================
           UPLOAD ERROR
        ================================================= */

        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                'ไม่สามารถอัปโหลดไฟล์ได้';

        } else {

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
               CHECK EXTENSION
            ================================================= */

            if (
                $extension !== 'pdf'
            ) {

                $error =
                    'อนุญาตเฉพาะไฟล์ PDF เท่านั้น';

            } elseif (
                $file_size >
                20 * 1024 * 1024
            ) {

                $error =
                    'ไฟล์ต้องมีขนาดไม่เกิน 20 MB';

            } else {


                /* =================================================
                   CHECK MIME
                ================================================= */

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


                    /* =================================================
                       UPLOAD DIRECTORY
                    ================================================= */

                    $upload_dir =
                        __DIR__
                        . '/uploads/';


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


                    /* =================================================
                       CREATE FILE NAME
                    ================================================= */

                    if (
                        $error === ''
                    ) {

                        try {

                            $random_name =
                                bin2hex(
                                    random_bytes(8)
                                );

                        } catch (
                            Exception $e
                        ) {

                            $random_name =
                                uniqid();
                        }


                        $new_file_name =
                            'project_'
                            . $user_id
                            . '_'
                            . $project_id
                            . '_'
                            . time()
                            . '_'
                            . $random_name
                            . '.pdf';


                        $new_destination =
                            $upload_dir
                            . $new_file_name;


                        if (
                            move_uploaded_file(
                                $file_tmp,
                                $new_destination
                            )
                        ) {

                            $new_pdf_file =
                                $new_file_name;


                            $uploaded_new_pdf =
                                true;

                        } else {

                            $error =
                                'ไม่สามารถบันทึกไฟล์ PDF ได้';
                        }
                    }
                }
            }
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if (
        $error === ''
    ) {


        /*
         * รองรับฐานข้อมูลเก่า
         */

        $project_name =
            $title;


        $project_type =
            $degree;


        /*
         * สำคัญมาก
         *
         * student_name
         * เก็บเจ้าของเพียงคนเดียว
         */

        $owner_student_name =
            $student_name;


        /* =================================================
           UPDATE SQL
        ================================================= */

        $update_stmt =
            mysqli_prepare(
                $conn,
                "
                UPDATE projects
                SET
                    title = ?,
                    project_name = ?,
                    degree = ?,
                    project_type = ?,
                    department = ?,
                    student_name = ?,
                    authors = ?,
                    advisor = ?,
                    github_url = ?,
                    pdf_file = ?
                WHERE id = ?
                  AND student_id = ?
                "
            );


        if (!$update_stmt) {

            $error =
                'ไม่สามารถเตรียมคำสั่ง SQL ได้: '
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $update_stmt,
                "ssssssssssii",
                $title,
                $project_name,
                $degree,
                $project_type,
                $project_department,
                $owner_student_name,
                $authors,
                $advisor,
                $github_url,
                $new_pdf_file,
                $project_id,
                $user_id
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {

                mysqli_stmt_close(
                    $update_stmt
                );


                /* =================================================
                   DELETE OLD PDF
                ================================================= */

                if (
                    $uploaded_new_pdf
                    &&
                    $pdf_file !== ''
                    &&
                    basename(
                        $pdf_file
                    )
                    !== basename(
                        $new_pdf_file
                    )
                ) {

                    $old_pdf_path =
                        __DIR__
                        . '/uploads/'
                        . basename(
                            $pdf_file
                        );


                    if (
                        file_exists(
                            $old_pdf_path
                        )
                    ) {

                        @unlink(
                            $old_pdf_path
                        );
                    }
                }


                /* =================================================
                   SUCCESS
                ================================================= */

                echo "
                <script>
                    alert('แก้ไขโปรเจกต์เรียบร้อยแล้ว');
                    window.location.href='index2.php';
                </script>
                ";

                exit();


            } else {

                $error =
                    'ไม่สามารถแก้ไขข้อมูลได้: '
                    . mysqli_stmt_error(
                        $update_stmt
                    );


                mysqli_stmt_close(
                    $update_stmt
                );


                /* =================================================
                   DELETE NEW PDF IF UPDATE FAIL
                ================================================= */

                if (
                    $uploaded_new_pdf
                    &&
                    $new_destination !== ''
                    &&
                    file_exists(
                        $new_destination
                    )
                ) {

                    @unlink(
                        $new_destination
                    );
                }


                $new_pdf_file =
                    $pdf_file;
            }
        }
    }


    /* =====================================================
       UPDATE PAGE VARIABLES
    ===================================================== */

    $project['title'] =
        $title;


    $project['project_name'] =
        $project_name
        ?? $title;


    $project['degree'] =
        $degree;


    $project['project_type'] =
        $project_type
        ?? $degree;


    $project['department'] =
        $project_department;


    /*
     * student_name = เจ้าของ
     */

    $project['student_name'] =
        $student_name;


    /*
     * authors =
     * เจ้าของ + สมาชิก
     */

    $project['authors'] =
        $authors;


    $project['advisor'] =
        $advisor;


    $project['github_url'] =
        $github_url;


    $project['pdf_file'] =
        $new_pdf_file;


    $pdf_file =
        $new_pdf_file;


    $current_pdf_url =
        $pdf_file !== ''
            ? 'uploads/'
              . rawurlencode(
                    basename(
                        $pdf_file
                    )
                )
            : '';
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
        แก้ไขโปรเจกต์ - คลังโปรเจกต์ SDU
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


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


        /* =====================================================
           HEADER
        ===================================================== */

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
        }


        .header-inner {

            width: 100%;

            max-width: 1280px;

            margin: 0 auto;

            padding: 0 20px;

            display: flex;

            align-items: center;
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
        }


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
        }


        .home-link:hover {

            background:
                rgba(255,255,255,0.16);
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main-container {

            max-width: 850px;

            margin: 40px auto;

            padding: 0 20px;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .form-card {

            background: white;

            border-radius: 14px;

            border: 1px solid #e2e8f0;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,0.06);

            overflow: hidden;
        }


        /* =====================================================
           HEADER CARD
        ===================================================== */

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


        /* =====================================================
           BODY
        ===================================================== */

        .form-body {

            padding: 30px 35px;
        }


        .form-label {

            font-weight: 600;

            color: #334155;
        }


        .required {

            color: #dc3545;
        }


        .form-control,
        .form-select {

            border-color: #d8dee4;

            border-radius: 8px;

            padding: 10px 12px;
        }


        .form-control:focus,
        .form-select:focus {

            border-color: #4297CD;

            box-shadow:
                0 0 0 0.2rem
                rgba(66,151,205,0.15);
        }


        /* =====================================================
           OWNER
        ===================================================== */

        .owner-box {

            background: #f7fbfe;

            border: 1px solid #dceef8;

            border-radius: 10px;

            padding: 16px;
        }


        .owner-icon {

            color: #4297CD;

            font-size: 20px;
        }


        .owner-name {

            background: white;

            border: 1px solid #d8dee4;

            border-radius: 8px;

            padding: 11px 12px;

            font-weight: 600;

            color: #334155;
        }


        .owner-help {

            font-size: 13px;

            color: #64748b;

            margin-top: 8px;

            line-height: 1.6;
        }


        /* =====================================================
           MEMBERS
        ===================================================== */

        .members-box {

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 10px;

            padding: 16px;
        }


        .members-help {

            font-size: 13px;

            color: #64748b;

            margin-top: 8px;

            line-height: 1.6;
        }


        .members-example {

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            border-radius: 8px;

            padding: 10px 12px;

            margin-top: 10px;

            font-size: 13px;

            color: #64748b;

            line-height: 1.7;
        }


        .members-example strong {

            color: #334155;
        }


        /* =====================================================
           GITHUB
        ===================================================== */

        .github-box {

            background: #f8fafc;

            border: 1px solid #e1e7ec;

            border-radius: 10px;

            padding: 16px;
        }


        .github-label {

            display: flex;

            align-items: center;

            gap: 7px;

            font-weight: 600;

            color: #24292f;

            margin-bottom: 8px;
        }


        /* =====================================================
           PDF
        ===================================================== */

        .current-pdf {

            background: #f8fafc;

            border: 1px solid #e1e7ec;

            border-radius: 10px;

            padding: 15px;

            margin-bottom: 12px;

            color: #475569;

            font-size: 14px;
        }


        .current-pdf a {

            color: #287cab;

            text-decoration: none;

            font-weight: 600;
        }


        .current-pdf a:hover {

            text-decoration: underline;
        }


        .file-box {

            border: 2px dashed #b8d8ea;

            background: #f7fbfe;

            border-radius: 10px;

            padding: 25px;

            text-align: center;
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


        /* =====================================================
           BUTTON
        ===================================================== */

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

            transition: .2s;
        }


        .btn-save:hover {

            color: white;

            transform:
                translateY(-2px);
        }


        .btn-cancel {

            border: 1px solid #cbd5e1;

            color: #475569;

            background: white;

            padding: 11px 25px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: 600;

            transition: .2s;
        }


        .btn-cancel:hover {

            background: #f1f5f9;

            color: #334155;
        }


        .help-text {

            font-size: 0.82rem;

            color: #64748b;

            margin-top: 5px;
        }


        /* =====================================================
           OTHER DEPARTMENT
        ===================================================== */

        #otherDepartmentBox {

            display: none;

            margin-top: 10px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (
            max-width: 768px
        ) {

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


<!-- =====================================================
     HEADER
===================================================== -->

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

                <i class="bi bi-house-fill"></i>

                <span>
                    หน้าแรก
                </span>

            </a>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">

    <div class="form-card">


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="form-header">

            <h1>

                <i class="bi bi-pencil-square me-2"></i>

                แก้ไขโปรเจกต์

            </h1>


            <p>

                แก้ไขข้อมูลโครงงานและอัปโหลดไฟล์ PDF ใหม่

            </p>

        </div>


        <!-- =================================================
             BODY
        ================================================== -->

        <div class="form-body">


            <?php if (!empty($error)): ?>

                <div
                    class="alert alert-danger"
                    role="alert"
                >

                    <i
                        class="bi bi-exclamation-circle me-2"
                    ></i>

                    <?php
                    echo e($error);
                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =================================================
                     TITLE
                ================================================== -->

                <div class="mb-4">

                    <label
                        for="title"
                        class="form-label"
                    >

                        ชื่อโปรเจกต์

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        name="title"
                        id="title"
                        class="form-control"
                        placeholder="กรอกชื่อโปรเจกต์"
                        value="<?php
                            echo e(
                                $title
                            );
                        ?>"
                        required
                    >

                </div>


                <!-- =================================================
                     DEGREE + DEPARTMENT
                ================================================== -->

                <div class="row">


                    <div class="col-md-6 mb-4">

                        <label
                            for="degree"
                            class="form-label"
                        >

                            ระดับการศึกษา

                            <span class="required">*</span>

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
                                echo $degree
                                    === 'ปริญญาตรี'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                ปริญญาตรี

                            </option>


                            <option
                                value="ปริญญาโท"
                                <?php
                                echo $degree
                                    === 'ปริญญาโท'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                ปริญญาโท

                            </option>


                            <option
                                value="ปริญญาเอก"
                                <?php
                                echo $degree
                                    === 'ปริญญาเอก'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                ปริญญาเอก

                            </option>

                        </select>

                    </div>


                    <div class="col-md-6 mb-4">

                        <label
                            for="department"
                            class="form-label"
                        >

                            สาขาวิชา

                            <span class="required">*</span>

                        </label>


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


                            <option
                                value="เทคโนโลยีสารสนเทศ"
                                <?php
                                echo $project_department
                                    === 'เทคโนโลยีสารสนเทศ'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                เทคโนโลยีสารสนเทศ

                            </option>


                            <option
                                value="วิทยาการคอมพิวเตอร์"
                                <?php
                                echo $project_department
                                    === 'วิทยาการคอมพิวเตอร์'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                วิทยาการคอมพิวเตอร์

                            </option>


                            <option
                                value="วิทยาศาสตร์สิ่งแวดล้อม"
                                <?php
                                echo $project_department
                                    === 'วิทยาศาสตร์สิ่งแวดล้อม'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                วิทยาศาสตร์สิ่งแวดล้อม

                            </option>


                            <option
                                value="เทคโนโลยีการประกอบอาหาร"
                                <?php
                                echo $project_department
                                    === 'เทคโนโลยีการประกอบอาหาร'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                เทคโนโลยีการประกอบอาหาร

                            </option>


                            <option
                                value="อื่นๆ"
                                <?php
                                echo (
                                    $project_department !== ''
                                    &&
                                    !in_array(
                                        $project_department,
                                        [
                                            'เทคโนโลยีสารสนเทศ',
                                            'วิทยาการคอมพิวเตอร์',
                                            'วิทยาศาสตร์สิ่งแวดล้อม',
                                            'เทคโนโลยีการประกอบอาหาร'
                                        ],
                                        true
                                    )
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >

                                อื่นๆ

                            </option>

                        </select>


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
                                    echo e(
                                        !in_array(
                                            $project_department,
                                            [
                                                '',
                                                'เทคโนโลยีสารสนเทศ',
                                                'วิทยาการคอมพิวเตอร์',
                                                'วิทยาศาสตร์สิ่งแวดล้อม',
                                                'เทคโนโลยีการประกอบอาหาร'
                                            ],
                                            true
                                        )
                                        ? $project_department
                                        : ''
                                    );
                                ?>"
                            >

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     OWNER
                ================================================== -->

                <div class="owner-box mb-4">

                    <label
                        class="form-label mb-2"
                    >

                        <i
                            class="
                                bi
                                bi-person-fill
                                owner-icon
                                me-1
                            "
                        ></i>

                        ผู้จัดทำ

                        <span class="required">*</span>

                    </label>


                    <div class="owner-name">

                        <?php

                        echo e(
                            $student_name
                        );

                        ?>

                    </div>


                    <div class="owner-help">

                        <i
                            class="bi bi-info-circle me-1"
                        ></i>

                        ผู้จัดทำคือเจ้าของโปรเจกต์
                        ไม่สามารถเปลี่ยนจากหน้านี้ได้

                        <br>

                        <i
                            class="bi bi-shield-check me-1"
                        ></i>

                        ระบบจะใช้ชื่อจากบัญชีที่กำลังเข้าสู่ระบบอัตโนมัติ

                    </div>

                </div>


                <!-- =================================================
                     MEMBERS
                ================================================== -->

                <div class="members-box mb-4">

                    <label
                        for="members"
                        class="form-label mb-2"
                    >

                        <i
                            class="bi bi-people-fill me-1"
                        ></i>

                        สมาชิกกลุ่ม

                    </label>


                    <textarea
                        name="members"
                        id="members"
                        class="form-control"
                        rows="5"
                        placeholder="กรอกชื่อสมาชิกกลุ่มเพิ่มเติม"
                    ><?php

                    echo e(
                        $members
                    );

                    ?></textarea>


                    <div class="members-help">

                        <i
                            class="bi bi-info-circle me-1"
                        ></i>

                        ใส่เฉพาะสมาชิกกลุ่มเพิ่มเติม
                        ไม่ต้องใส่ชื่อผู้จัดทำ

                        <br>

                        ใส่สมาชิก 1 คนต่อ 1 บรรทัด

                    </div>


                    <div class="members-example">

                        <strong>ตัวอย่าง</strong><br>

                        ผู้จัดทำ:
                        <?php
                        echo e($student_name);
                        ?>

                        <br>

                        สมาชิกกลุ่ม:<br>

                        นาย B<br>

                        นาย C<br>

                        นาย D

                    </div>

                </div>


                <!-- =================================================
                     ADVISOR
                ================================================== -->

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

                            $teacher_prefix =
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
                                    $teacher_prefix
                                    . ' '
                                    . $teacher_first
                                    . ' '
                                    . $teacher_last
                                );

                            ?>


                            <option
                                value="<?php
                                echo e(
                                    $teacher_fullname
                                );
                                ?>"
                                <?php
                                echo normalizeName($advisor)
                                    === normalizeName($teacher_fullname)
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


                    <div class="help-text">

                        เลือกอาจารย์จากรายชื่อในระบบ

                    </div>

                </div>


                <!-- =================================================
                     GITHUB
                ================================================== -->

                <div class="github-box mb-4">

                    <label
                        for="github_url"
                        class="github-label"
                    >

                        <i
                            class="bi bi-github"
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


                    <div class="help-text">

                        ใส่ลิงก์ GitHub ของโปรเจกต์
                        ถ้าไม่มีสามารถเว้นว่างได้

                    </div>

                </div>


                <!-- =================================================
                     CURRENT PDF
                ================================================== -->

                <div class="mb-4">

                    <label
                        class="form-label"
                    >

                        <i
                            class="
                                bi
                                bi-file-earmark-pdf-fill
                                me-1
                            "
                        ></i>

                        ไฟล์ PDF ปัจจุบัน

                    </label>


                    <?php if (
                        $pdf_file !== ''
                    ): ?>


                        <div class="current-pdf">

                            <i
                                class="
                                    bi
                                    bi-file-earmark-pdf
                                    me-1
                                "
                            ></i>


                            ไฟล์ปัจจุบัน:


                            <strong>

                                <?php

                                echo e(
                                    basename(
                                        $pdf_file
                                    )
                                );

                                ?>

                            </strong>


                            <br>


                            <a
                                href="<?php
                                echo e(
                                    $current_pdf_url
                                );
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >

                                <i
                                    class="
                                        bi
                                        bi-box-arrow-up-right
                                        me-1
                                    "
                                ></i>

                                เปิดดู PDF เดิม

                            </a>

                        </div>


                    <?php else: ?>


                        <div class="current-pdf">

                            <i
                                class="
                                    bi
                                    bi-file-earmark-x
                                    me-1
                                "
                            ></i>

                            ยังไม่มีไฟล์ PDF

                        </div>


                    <?php endif; ?>

                </div>


                <!-- =================================================
                     NEW PDF
                ================================================== -->

                <div class="mb-4">

                    <label
                        for="pdf_file"
                        class="form-label"
                    >

                        <i
                            class="bi bi-upload me-1"
                        ></i>

                        เปลี่ยนไฟล์ PDF

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

                            ถ้าต้องการเปลี่ยนไฟล์
                            ให้เลือก PDF ใหม่

                            <br>

                            ถ้าไม่เลือก
                            ระบบจะใช้ไฟล์เดิม

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
                        >

                    </div>

                </div>


                <!-- =================================================
                     BUTTONS
                ================================================== -->

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
                                bi-check-circle
                                me-1
                            "
                        ></i>

                        บันทึกการแก้ไข

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>


/* =========================================================
   OTHER DEPARTMENT
========================================================= */

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
        department.value ===
        'อื่นๆ'
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
    }

}


/* =========================================================
   PAGE LOAD
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function()
    {

        toggleOtherDepartment();

    }
);

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>