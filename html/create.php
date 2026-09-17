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
    LOGIN
    ========================================================= */

    if (!isset($_SESSION['user_id'])) {

        echo "<script>
            alert('กรุณาเข้าสู่ระบบก่อนใช้งาน');
            window.location.href='login.php';
        </script>";

        exit();
    }

    $user_id = intval($_SESSION['user_id']);


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
            $user_data = mysqli_fetch_assoc($result) ?: [];
        }

        mysqli_stmt_close($stmt);
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


    $student_name = trim(
        $prefix . ' ' .
        $first_name . ' ' .
        $last_name
    );


    /*
    * ถ้าไม่มีชื่อ-นามสกุล
    * ใช้ username แทน
    */

    if ($student_name === '') {

        $student_name =
            trim(
                $user_data['username']
                ?? 'นักศึกษา'
            );
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
            $column =
            mysqli_fetch_assoc($columns_result)
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
            $column =
            mysqli_fetch_assoc($user_columns_result)
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
                ORDER BY first_name ASC, last_name ASC
            ";

        } else {

            $teacher_sql = "
                SELECT
                    id,
                    first_name,
                    last_name
                FROM users
                WHERE role = 'teacher'
                ORDER BY first_name ASC, last_name ASC
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

    $advisor = '';

    $github_url = '';

    $pdf_file = '';

    $destination = '';

    /*
    * สมาชิกกลุ่มเท่านั้น
    *
    * ไม่รวมผู้จัดทำ
    */
    $members = '';

    /*
    * authors จะเก็บสมาชิกกลุ่มเท่านั้น
    */
    $authors = '';


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
                $_POST['title'] ?? ''
            );


        /* =====================================================
        DEGREE
        ===================================================== */

        $degree =
            trim(
                $_POST['degree'] ?? ''
            );


        /* =====================================================
        DEPARTMENT
        ===================================================== */

        $department_select =
            trim(
                $_POST['department'] ?? ''
            );


        $other_department =
            trim(
                $_POST['other_department'] ?? ''
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
        GROUP MEMBERS
        สมาชิกกลุ่มเท่านั้น
        ===================================================== */

        $members =
            trim(
                $_POST['members'] ?? ''
            );


        /*
        * authors = สมาชิกกลุ่ม
        *
        * ผู้จัดทำไม่ถูกเก็บซ้ำใน authors
        */

        if (
            $members !== ''
        ) {

            /*
            * แยกสมาชิกทีละบรรทัด
            */

            $member_lines =
                preg_split(
                    '/\r\n|\r|\n/',
                    $members
                );


            $clean_members = [];


            foreach (
                $member_lines
                as $member
            ) {

                $member =
                    trim(
                        $member
                    );


                /*
                * ข้ามบรรทัดว่าง
                */

                if (
                    $member === ''
                ) {

                    continue;
                }


                /*
                * ไม่ให้กรอกชื่อผู้จัดทำซ้ำ
                */

                if (
                    $member ===
                    $student_name
                ) {

                    continue;
                }


                /*
                * เพิ่มสมาชิก
                */

                $clean_members[] =
                    $member;
            }


            /*
            * ป้องกันชื่อซ้ำ
            */

            $clean_members =
                array_values(
                    array_unique(
                        $clean_members
                    )
                );


            /*
            * เก็บสมาชิกลง authors
            */

            if (
                !empty(
                    $clean_members
                )
            ) {

                $authors =
                    implode(
                        "\n",
                        $clean_members
                    );
            }
        }


        /* =====================================================
        ADVISOR
        ===================================================== */

        $advisor =
            trim(
                $_POST['advisor'] ?? ''
            );


        /* =====================================================
        GITHUB
        ===================================================== */

        $github_url =
            trim(
                $_POST['github_url'] ?? ''
            );


        /* =====================================================
        VALIDATE BASIC DATA
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
        CHECK GITHUB
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
        CHECK ADVISOR
        ===================================================== */

        if (
            $error === '' &&
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
                        $teacher_prefix .
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
        PDF UPLOAD
        ===================================================== */

        if (
            $error === ''
        ) {

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


                        if (
                            move_uploaded_file(
                                $file_tmp,
                                $destination
                            )
                        ) {

                            $pdf_file =
                                $new_file_name;

                        } else {

                            $error =
                                'ไม่สามารถบันทึกไฟล์ลงโฟลเดอร์ uploads ได้';
                        }
                    }
                }
            }
        }


        /* =====================================================
        INSERT PROJECT
        ===================================================== */

        if (
            $error === '' &&
            $pdf_file !== ''
        ) {

            $insert_columns = [];

            $insert_values = [];


            /* =================================================
            TITLE
            ================================================= */

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


            /* =================================================
            PROJECT NAME
            ================================================= */

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


            /* =================================================
            DEGREE
            ================================================= */

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


            /* =================================================
            PROJECT TYPE
            ================================================= */

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


            /* =================================================
            DEPARTMENT
            ================================================= */

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


            /* =================================================
            AUTHORS
            สมาชิกกลุ่มเท่านั้น
            ================================================= */

            if (
                in_array(
                    'authors',
                    $project_columns
                )
            ) {

                $insert_columns[] =
                    'authors';

                /*
                * ถ้าไม่มีสมาชิก
                * จะบันทึกเป็นค่าว่าง
                */

                $insert_values[] =
                    $authors;
            }


            /* =================================================
            STUDENT NAME
            ผู้จัดทำ / เจ้าของโปรเจกต์
            ================================================= */

            if (
                in_array(
                    'student_name',
                    $project_columns
                )
            ) {

                $insert_columns[] =
                    'student_name';

                /*
                * สำคัญมาก
                *
                * student_name =
                * คนที่ล็อกอินและสร้างโปรเจกต์
                */

                $insert_values[] =
                    $student_name;
            }


            /* =================================================
            ADVISOR
            ================================================= */

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


            /* =================================================
            PDF
            ================================================= */

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


            /* =================================================
            GITHUB
            ================================================= */

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


            /* =================================================
            STATUS
            ================================================= */

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


            /* =================================================
            STUDENT ID
            ================================================= */

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


            /* =================================================
            AUTHOR ID
            ================================================= */

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


            /* =================================================
            CREATED BY
            ================================================= */

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
            CHECK INSERT COLUMNS
            ================================================= */

            if (
                empty(
                    $insert_columns
                )
            ) {

                if (
                    $destination !== '' &&
                    file_exists(
                        $destination
                    )
                ) {

                    unlink(
                        $destination
                    );
                }


                $error =
                    'ไม่พบคอลัมน์ที่สามารถบันทึกข้อมูลโปรเจกต์ได้';

            } else {


                /* =================================================
                BUILD SQL
                ================================================= */

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


                if (
                    $stmt
                ) {

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


                    mysqli_stmt_bind_param(
                        $stmt,
                        $types,
                        ...$insert_values
                    );


                    /* =================================================
                    EXECUTE
                    ================================================= */

                    if (
                        mysqli_stmt_execute(
                            $stmt
                        )
                    ) {

                        mysqli_stmt_close(
                            $stmt
                        );


                        /* =================================================
                        SUCCESS
                        ================================================= */

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


                        /* =================================================
                        DELETE PDF IF INSERT FAIL
                        ================================================= */

                        if (
                            $destination !== '' &&
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
                        $destination !== '' &&
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

            .main-container {

                max-width: 850px;

                margin: 40px auto;

                padding: 0 20px;
            }

            .form-card {

                background: white;

                border-radius: 14px;

                border: 1px solid #e2e8f0;

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
            OWNER / AUTHOR
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
            }

            /* =====================================================
            GROUP MEMBERS
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
            }

            .btn-save:hover {

                color: white;

                transform: translateY(-2px);
            }

            .btn-cancel {

                border: 1px solid #cbd5e1;

                color: #475569;

                background: white;

                padding: 11px 25px;

                border-radius: 8px;

                text-decoration: none;

                font-weight: 600;
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

            #otherDepartmentBox {

                display: none;

                margin-top: 10px;
            }

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


    <div class="main-container">

        <div class="form-card">

            <div class="form-header">

                <h1>

                    <i class="bi bi-cloud-arrow-up me-2"></i>

                    ส่งโปรเจกต์ใหม่

                </h1>

                <p>
                    กรอกข้อมูลโครงงานและอัปโหลดไฟล์ PDF
                </p>

            </div>


            <div class="form-body">


                <?php if (!empty($error)): ?>

                    <div
                        class="alert alert-danger"
                        role="alert"
                    >

                        <i class="bi bi-exclamation-circle me-2"></i>

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
                            value="<?php echo e($title); ?>"
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
                                    echo $degree === 'ปริญญาตรี'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    ปริญญาตรี
                                </option>


                                <option
                                    value="ปริญญาโท"
                                    <?php
                                    echo $degree === 'ปริญญาโท'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    ปริญญาโท
                                </option>


                                <option
                                    value="ปริญญาเอก"
                                    <?php
                                    echo $degree === 'ปริญญาเอก'
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
                                    echo $project_department === 'เทคโนโลยีสารสนเทศ'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    เทคโนโลยีสารสนเทศ
                                </option>


                                <option
                                    value="วิทยาการคอมพิวเตอร์"
                                    <?php
                                    echo $project_department === 'วิทยาการคอมพิวเตอร์'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    วิทยาการคอมพิวเตอร์
                                </option>


                                <option
                                    value="วิทยาศาสตร์สิ่งแวดล้อม"
                                    <?php
                                    echo $project_department === 'วิทยาศาสตร์สิ่งแวดล้อม'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    วิทยาศาสตร์สิ่งแวดล้อม
                                </option>


                                <option
                                    value="เทคโนโลยีการประกอบอาหาร"
                                    <?php
                                    echo $project_department === 'เทคโนโลยีการประกอบอาหาร'
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
                                            ]
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
                                                ]
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
                        AUTHOR / OWNER
                    ================================================== -->

                    <div class="owner-box mb-4">

                        <label class="form-label mb-2">

                            <i
                                class="bi bi-person-fill owner-icon me-1"
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

                            <i class="bi bi-info-circle me-1"></i>

                            ผู้จัดทำคือเจ้าของโปรเจกต์
                            และใช้ชื่อจากบัญชีที่กำลังเข้าสู่ระบบอัตโนมัติ

                        </div>

                    </div>


                    <!-- =================================================
                        GROUP MEMBERS
                    ================================================== -->

                    <div class="members-box mb-4">

                        <label
                            for="members"
                            class="form-label mb-2"
                        >

                            <i class="bi bi-people-fill me-1"></i>

                            สมาชิกกลุ่ม

                        </label>


                        <textarea
                            name="members"
                            id="members"
                            class="form-control"
                            rows="4"
                            placeholder="กรอกชื่อสมาชิกกลุ่มเพิ่มเติม คนละ 1 บรรทัด"
                        ><?php echo e($members); ?></textarea>


                        <div class="members-help">

                            <i class="bi bi-info-circle me-1"></i>

                            ใส่เฉพาะสมาชิกกลุ่มเพิ่มเติม
                            ไม่ต้องใส่ชื่อผู้จัดทำซ้ำ

                        </div>


                        <div class="members-example">

                            <strong>ตัวอย่าง</strong><br>

                            ผู้จัดทำ: นาย A<br>

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
                                        $teacher_prefix .
                                        ' ' .
                                        $teacher_first .
                                        ' ' .
                                        $teacher_last
                                    );

                                ?>


                                <option
                                    value="<?php
                                        echo e(
                                            $teacher_fullname
                                        );
                                    ?>"
                                    <?php
                                    echo $advisor ===
                                        $teacher_fullname
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

                            <i class="bi bi-github"></i>

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
                        PDF
                    ================================================== -->

                    <div class="mb-4">

                        <label
                            for="pdf_file"
                            class="form-label"
                        >

                            ไฟล์โปรเจกต์ PDF

                            <span class="required">*</span>

                        </label>


                        <div class="file-box">

                            <div class="file-icon">

                                <i class="bi bi-file-earmark-pdf"></i>

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

                            <i class="bi bi-arrow-left me-1"></i>

                            ยกเลิก

                        </a>


                        <button
                            type="submit"
                            class="btn-save"
                        >

                            <i class="bi bi-cloud-arrow-up me-1"></i>

                            ส่งโปรเจกต์

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


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