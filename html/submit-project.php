<?php

session_start();
require_once 'db_connect.php';

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   ALLOW STUDENT / ADMIN
========================================================= */

if ($user_role !== 'student' && $user_role !== 'admin') {
    echo "
    <script>
        alert('ไม่มีสิทธิ์ส่งโปรเจกต์');
        window.location.href='index2.php';
    </script>
    ";
    exit();
}

/* =========================================================
   ONLY POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create.php");
    exit();
}

/* =========================================================
   GET FORM DATA
========================================================= */

$title = trim($_POST['title'] ?? '');

$description = trim($_POST['description'] ?? '');

$degree = trim($_POST['degree'] ?? '');

$department_select = trim($_POST['department_select'] ?? '');

$other_department = trim($_POST['other_department'] ?? '');

$authors = trim($_POST['authors'] ?? '');

$advisor = trim($_POST['advisor'] ?? '');

$github_url = trim($_POST['github_url'] ?? '');


/* =========================================================
   DEPARTMENT
========================================================= */

if ($department_select === 'อื่นๆ') {

    $department = $other_department;

} else {

    $department = $department_select;
}


/* =========================================================
   VALIDATE REQUIRED
========================================================= */

if ($title === '') {

    echo "
    <script>
        alert('กรุณากรอกชื่อโปรเจกต์');
        history.back();
    </script>
    ";

    exit();
}


if ($description === '') {

    echo "
    <script>
        alert('กรุณากรอกคำอธิบายหรือบทคัดย่อ');
        history.back();
    </script>
    ";

    exit();
}


if ($degree === '') {

    echo "
    <script>
        alert('กรุณาเลือกระดับการศึกษา');
        history.back();
    </script>
    ";

    exit();
}


if ($department === '') {

    echo "
    <script>
        alert('กรุณาเลือกสาขา / ภาควิชา');
        history.back();
    </script>
    ";

    exit();
}


if ($authors === '') {

    echo "
    <script>
        alert('กรุณากรอกสมาชิกกลุ่ม');
        history.back();
    </script>
    ";

    exit();
}


if ($advisor === '') {

    echo "
    <script>
        alert('กรุณาเลือกอาจารย์ที่ปรึกษา');
        history.back();
    </script>
    ";

    exit();
}


/* =========================================================
   CHECK ADVISOR
   ต้องเป็นอาจารย์ที่มีอยู่ใน users
========================================================= */

$advisor_stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        prefix,
        first_name,
        last_name
    FROM users
    WHERE role = 'teacher'
    "
);

$valid_advisor = false;

if ($advisor_stmt) {

    mysqli_stmt_execute($advisor_stmt);

    $advisor_result = mysqli_stmt_get_result($advisor_stmt);

    if ($advisor_result) {

        while ($teacher = mysqli_fetch_assoc($advisor_result)) {

            $prefix = trim($teacher['prefix'] ?? '');

            $first_name = trim($teacher['first_name'] ?? '');

            $last_name = trim($teacher['last_name'] ?? '');

            $teacher_fullname = trim(
                $prefix . ' ' .
                $first_name . ' ' .
                $last_name
            );

            $teacher_without_prefix = trim(
                $first_name . ' ' .
                $last_name
            );

            $advisor_normalized = preg_replace(
                '/\s+/u',
                ' ',
                trim($advisor)
            );

            $fullname_normalized = preg_replace(
                '/\s+/u',
                ' ',
                trim($teacher_fullname)
            );

            $without_prefix_normalized = preg_replace(
                '/\s+/u',
                ' ',
                trim($teacher_without_prefix)
            );

            if (
                mb_strtolower(
                    $advisor_normalized,
                    'UTF-8'
                )
                ===
                mb_strtolower(
                    $fullname_normalized,
                    'UTF-8'
                )
                ||
                mb_strtolower(
                    $advisor_normalized,
                    'UTF-8'
                )
                ===
                mb_strtolower(
                    $without_prefix_normalized,
                    'UTF-8'
                )
            ) {

                /*
                    บันทึกชื่ออาจารย์ตามข้อมูลปัจจุบัน
                    เช่น

                    อ. สมชาย ใจดี
                    ผศ.ดร. สมหญิง ใจดี
                */

                $advisor = $teacher_fullname;

                $valid_advisor = true;

                break;
            }
        }
    }

    mysqli_stmt_close($advisor_stmt);
}


if (!$valid_advisor) {

    echo "
    <script>
        alert('ไม่พบอาจารย์ที่ปรึกษาที่เลือก');
        history.back();
    </script>
    ";

    exit();
}


/* =========================================================
   GITHUB URL
========================================================= */

if ($github_url !== '') {

    if (
        !filter_var(
            $github_url,
            FILTER_VALIDATE_URL
        )
    ) {

        echo "
        <script>
            alert('ลิงก์ GitHub ไม่ถูกต้อง');
            history.back();
        </script>
        ";

        exit();
    }
}


/* =========================================================
   PDF UPLOAD
========================================================= */

$pdf_file_name = '';

if (
    isset($_FILES['pdf_file']) &&
    $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if (
        $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK
    ) {

        echo "
        <script>
            alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์ PDF');
            history.back();
        </script>
        ";

        exit();
    }


    $pdf_tmp = $_FILES['pdf_file']['tmp_name'];

    $original_name = $_FILES['pdf_file']['name'];

    $file_size = (int)$_FILES['pdf_file']['size'];


    /* จำกัด 10MB */

    if ($file_size > 10 * 1024 * 1024) {

        echo "
        <script>
            alert('ไฟล์ PDF ต้องมีขนาดไม่เกิน 10 MB');
            history.back();
        </script>
        ";

        exit();
    }


    /* ตรวจนามสกุล */

    $extension = strtolower(
        pathinfo(
            $original_name,
            PATHINFO_EXTENSION
        )
    );


    if ($extension !== 'pdf') {

        echo "
        <script>
            alert('อนุญาตเฉพาะไฟล์ PDF เท่านั้น');
            history.back();
        </script>
        ";

        exit();
    }


    /* ตรวจ MIME */

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime = finfo_file(
        $finfo,
        $pdf_tmp
    );

    finfo_close($finfo);


    if (
        $mime !== 'application/pdf' &&
        $mime !== 'application/octet-stream'
    ) {

        echo "
        <script>
            alert('ไฟล์ที่อัปโหลดไม่ใช่ PDF');
            history.back();
        </script>
        ";

        exit();
    }


    /* สร้างโฟลเดอร์ uploads */

    $upload_dir = __DIR__ . '/uploads/';

    if (!is_dir($upload_dir)) {

        if (!mkdir($upload_dir, 0777, true)) {

            echo "
            <script>
                alert('ไม่สามารถสร้างโฟลเดอร์ uploads ได้');
                history.back();
            </script>
            ";

            exit();
        }
    }


    /* สร้างชื่อไฟล์ใหม่ */

    $pdf_file_name =
        'project_' .
        $user_id .
        '_' .
        time() .
        '_' .
        bin2hex(random_bytes(4)) .
        '.pdf';


    $destination =
        $upload_dir .
        $pdf_file_name;


    if (
        !move_uploaded_file(
            $pdf_tmp,
            $destination
        )
    ) {

        echo "
        <script>
            alert('ไม่สามารถบันทึกไฟล์ PDF ได้');
            history.back();
        </script>
        ";

        exit();
    }
}


/* =========================================================
   GET USER NAME
========================================================= */

$student_name = '';

$user_stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        first_name,
        last_name
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

if ($user_stmt) {

    mysqli_stmt_bind_param(
        $user_stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($user_stmt);

    $user_result = mysqli_stmt_get_result($user_stmt);

    if ($user_result && mysqli_num_rows($user_result) > 0) {

        $user_row = mysqli_fetch_assoc($user_result);

        $student_name = trim(
            ($user_row['first_name'] ?? '') .
            ' ' .
            ($user_row['last_name'] ?? '')
        );
    }

    mysqli_stmt_close($user_stmt);
}


/* =========================================================
   FALLBACK NAME
========================================================= */

if ($student_name === '') {

    $student_name = $authors;
}


/* =========================================================
   PROJECT TYPE
========================================================= */

$project_type = $degree;


/* =========================================================
   STATUS
========================================================= */

$status = 'ส่งแล้ว';


/* =========================================================
   INSERT PROJECT
========================================================= */

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
    VALUES
    (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    /* ถ้าอัปโหลด PDF ไปแล้วแต่ INSERT ไม่สำเร็จ
       ให้ลบไฟล์ทิ้ง */

    if ($pdf_file_name !== '') {

        $uploaded_file =
            __DIR__ .
            '/uploads/' .
            $pdf_file_name;

        if (file_exists($uploaded_file)) {
            unlink($uploaded_file);
        }
    }


    die(
        "เกิดข้อผิดพลาดในการเตรียม SQL: " .
        e(mysqli_error($conn))
    );
}


/* =========================================================
   BIND
========================================================= */

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssi",
    $title,
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


/* =========================================================
   EXECUTE
========================================================= */

if (mysqli_stmt_execute($stmt)) {

    $new_project_id =
        mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    echo "
    <!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <meta
            name='viewport'
            content='width=device-width, initial-scale=1.0'
        >
        <title>ส่งโปรเจกต์สำเร็จ</title>

        <style>

            body {
                margin: 0;
                min-height: 100vh;

                display: flex;
                align-items: center;
                justify-content: center;

                background:
                    linear-gradient(
                        135deg,
                        #eaf7ff,
                        #ffffff
                    );

                font-family:
                    'Segoe UI',
                    Tahoma,
                    Arial,
                    sans-serif;
            }

            .success-box {
                width: 90%;
                max-width: 500px;

                background: white;

                padding: 40px;

                border-radius: 20px;

                text-align: center;

                box-shadow:
                    0 10px 35px
                    rgba(48,105,139,0.15);
            }

            .success-icon {
                width: 80px;
                height: 80px;

                margin: 0 auto 20px;

                display: flex;
                align-items: center;
                justify-content: center;

                border-radius: 50%;

                background: #e7f8ef;

                color: #198754;

                font-size: 42px;
            }

            h1 {
                color: #174f70;
                margin-bottom: 10px;
            }

            p {
                color: #687780;
                line-height: 1.7;
            }

            .button {
                display: inline-block;

                margin-top: 20px;

                padding: 12px 25px;

                background:
                    linear-gradient(
                        135deg,
                        #58b4df,
                        #358abd
                    );

                color: white;

                text-decoration: none;

                border-radius: 10px;

                font-weight: 600;
            }

        </style>

    </head>

    <body>

        <div class='success-box'>

            <div class='success-icon'>
                ✓
            </div>

            <h1>
                ส่งโปรเจกต์สำเร็จ
            </h1>

            <p>
                โปรเจกต์ของคุณถูกบันทึกเข้าสู่ระบบเรียบร้อยแล้ว
            </p>

            <a
                href='project-detail.php?id=<?php echo $new_project_id; ?>'
                class='button'
            >
                ดูโปรเจกต์
            </a>

            <br>

            <a
                href='index2.php'
                class='button'
                style='background:#6c757d;'
            >
                กลับหน้าหลัก
            </a>

        </div>

    </body>
    </html>
    ";

    exit();

}


/* =========================================================
   INSERT ERROR
========================================================= */

$error = mysqli_error($conn);

mysqli_stmt_close($stmt);


/* ลบ PDF ถ้า INSERT ไม่สำเร็จ */

if ($pdf_file_name !== '') {

    $uploaded_file =
        __DIR__ .
        '/uploads/' .
        $pdf_file_name;

    if (file_exists($uploaded_file)) {

        unlink($uploaded_file);
    }
}


echo "
<script>
    alert('ไม่สามารถบันทึกโปรเจกต์ได้\\n$error');
    history.back();
</script>
";

exit();

?>