<?php

session_start();
require_once 'db_connect.php';


/* =========================================================
   LANGUAGE SYSTEM
========================================================= */

$supported_languages = ['th', 'en'];

if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], $supported_languages, true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'th';

if (!in_array($lang, $supported_languages, true)) {
    $lang = 'th';
    $_SESSION['lang'] = 'th';
}


/* =========================================================
   TRANSLATION
========================================================= */

$translations = [

    'th' => [

        'home' => 'หน้าแรก',
        'project_archive' => 'คลังโปรเจกต์ SDU',

        'project_information' => 'ข้อมูลโปรเจกต์',

        'view_pdf' => 'ดูไฟล์ PDF',
        'download_pdf' => 'ดาวน์โหลด PDF',
        'no_pdf' => 'ไม่มีไฟล์ PDF',

        'published' => 'วันที่เผยแพร่',
        'status' => 'สถานะ',
        'pdf_views' => 'ยอดเข้าชม PDF',
        'pdf_downloads' => 'ยอดดาวน์โหลด PDF',
        'times' => 'ครั้ง',

        'education_level' => 'ระดับการศึกษา',
        'department' => 'สาขา / ภาควิชา',
        'advisor' => 'อาจารย์ที่ปรึกษา',

        'members' => 'สมาชิกกลุ่ม',
        'person' => 'คน',

        'github_repository' => 'GitHub Repository',
        'github_project' => 'เปิดโปรเจกต์บน GitHub',
        'no_github' => 'ยังไม่ได้เพิ่มลิงก์ GitHub',

        'student_project' => 'โปรเจกต์นักศึกษา',
        'university' => 'มหาวิทยาลัยสวนดุสิต',

        'description' => 'คำอธิบาย / บทคัดย่อ',

        'advisor_comments' => 'ความคิดเห็นจากอาจารย์ที่ปรึกษา',
        'advisor_label' => 'อาจารย์ที่ปรึกษา:',
        'write_comment' => 'แสดงความคิดเห็นต่อโปรเจกต์นี้',
        'comment_placeholder' => 'พิมพ์ความคิดเห็นหรือคำแนะนำ...',
        'send_comment' => 'ส่งความคิดเห็น',

        'teacher_only' =>
            'โปรเจกต์นี้อยู่ภายใต้การดูแลของ',

        'teacher_only_2' =>
            'จึงมีเฉพาะอาจารย์ที่ปรึกษาเท่านั้นที่สามารถแสดงความคิดเห็นได้',

        'no_comments' =>
            'ยังไม่มีความคิดเห็นจากอาจารย์ที่ปรึกษา',

        'delete_comment' => 'ลบความคิดเห็น',

        'edit_project' => 'แก้ไขโปรเจกต์',
        'delete_project' => 'ลบโปรเจกต์',

        'guest_notice' =>
            'บุคคลทั่วไปสามารถดูไฟล์ PDF วันเผยแพร่ อาจารย์ที่ปรึกษา สาขา / ภาควิชา และคำอธิบายโปรเจกต์ได้',

        'not_found_project' =>
            'ไม่พบโปรเจกต์ที่ต้องการ',

        'no_description' =>
            'ไม่มีคำอธิบายหรือบทคัดย่อ',

        'no_author' =>
            'ไม่ระบุผู้แต่ง',

        'no_member' =>
            'ไม่ระบุสมาชิก',

        'not_specified' =>
            'ไม่ระบุ',

        'not_available' =>
            'ไม่พบชื่อโปรเจกต์',

        'admin_confirm_delete' =>
            "ต้องการลบโปรเจกต์นี้ใช่หรือไม่?\n\nเมื่อลบแล้วจะไม่สามารถกู้คืนได้",

        'comment_confirm_delete' =>
            'ต้องการลบความคิดเห็นนี้ใช่หรือไม่?',

        'profile' =>
            'ดูโปรไฟล์สมาชิก',

        'advisor_word' =>
            'อาจารย์',

        'error_sql' =>
            'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL',

        'project_detail' =>
            'รายละเอียดโปรเจกต์',

        'submitted' =>
            'ส่งโปรเจกต์แล้ว',

        'open_github' =>
            'เปิด GitHub',

        'back_home' =>
            'กลับหน้าแรก',

        'pdf_document' =>
            'เอกสารโครงงาน',

        'project_stats' =>
            'สถิติการเข้าถึง',

        'login_required' =>
            'เข้าสู่ระบบเพื่อดูข้อมูลเพิ่มเติม',

    ],

    'en' => [

        'home' => 'Home',
        'project_archive' => 'SDU Project Archive',

        'project_information' => 'Project Information',

        'view_pdf' => 'View PDF',
        'download_pdf' => 'Download PDF',
        'no_pdf' => 'No PDF file',

        'published' => 'Published',
        'status' => 'Status',
        'pdf_views' => 'PDF Views',
        'pdf_downloads' => 'PDF Downloads',
        'times' => 'times',

        'education_level' => 'Education Level',
        'department' => 'Department',
        'advisor' => 'Project Advisor',

        'members' => 'Group Members',
        'person' => 'members',

        'github_repository' => 'GitHub Repository',
        'github_project' => 'Open Project on GitHub',
        'no_github' => 'GitHub link has not been added',

        'student_project' => 'Student Project',
        'university' => 'Suan Dusit University',

        'description' => 'Description / Abstract',

        'advisor_comments' => 'Advisor Comments',
        'advisor_label' => 'Project Advisor:',
        'write_comment' => 'Comment on this project',
        'comment_placeholder' =>
            'Write a comment or suggestion...',
        'send_comment' => 'Send Comment',

        'teacher_only' =>
            'This project is supervised by',

        'teacher_only_2' =>
            'Therefore, only the project advisor can comment on this project.',

        'no_comments' =>
            'There are no comments from the project advisor yet.',

        'delete_comment' => 'Delete Comment',

        'edit_project' => 'Edit Project',
        'delete_project' => 'Delete Project',

        'guest_notice' =>
            'Guests can view the PDF file, publication date, project advisor, department, and project description.',

        'not_found_project' =>
            'Project not found',

        'no_description' =>
            'No description or abstract available',

        'no_author' =>
            'No author specified',

        'no_member' =>
            'No members specified',

        'not_specified' =>
            'Not specified',

        'not_available' =>
            'Project name not found',

        'admin_confirm_delete' =>
            "Are you sure you want to delete this project?\n\nThis action cannot be undone.",

        'comment_confirm_delete' =>
            'Are you sure you want to delete this comment?',

        'profile' =>
            'View member profile',

        'advisor_word' =>
            'Advisor',

        'error_sql' =>
            'An error occurred while preparing the SQL statement.',

        'project_detail' =>
            'Project Details',

        'submitted' =>
            'Project Submitted',

        'open_github' =>
            'Open GitHub',

        'back_home' =>
            'Back to Home',

        'pdf_document' =>
            'Project Document',

        'project_stats' =>
            'Access Statistics',

        'login_required' =>
            'Log in to view more information',

    ]

];


/* =========================================================
   TRANSLATION HELPER
========================================================= */

function t($key)
{
    global $translations, $lang;

    return $translations[$lang][$key]
        ?? $translations['th'][$key]
        ?? $key;
}


/* =========================================================
   ESCAPE
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
   PROJECT ID
========================================================= */

$project_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


/* =========================================================
   LOGIN
========================================================= */

$logged_in = isset($_SESSION['user_id']);

$user_id = $logged_in
    ? (int)$_SESSION['user_id']
    : 0;

$user_role = $_SESSION['role'] ?? '';


/* =========================================================
   INVALID PROJECT
========================================================= */

if ($project_id <= 0) {

    header(
        "Location: index2.php?lang=" .
        urlencode($lang)
    );

    exit();
}


/* =========================================================
   NORMALIZE NAME
========================================================= */

function normalize_teacher_name_for_comment($name)
{
    $name = trim((string)$name);

    $name = preg_replace(
        '/[\s\.]+/u',
        '',
        $name
    );

    return mb_strtolower(
        $name,
        'UTF-8'
    );
}


/* =========================================================
   GET PROJECT
========================================================= */

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
        pdf_file,
        status,
        student_id,
        github_url,
        views,
        downloads
    FROM projects
    WHERE id = ?
    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    die(
        e(t('error_sql'))
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close($stmt);

    echo "
    <script>
        alert(" .
        json_encode(
            t('not_found_project'),
            JSON_UNESCAPED_UNICODE
        ) .
        ");
        window.location.href='index2.php?lang=" .
        e($lang) .
        "';
    </script>
    ";

    exit();
}


$row = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   PROJECT TITLE
========================================================= */

$title = trim(
    $row['title'] ?? ''
);


if ($title === '') {

    $title = trim(
        $row['project_name'] ?? ''
    );
}


if ($title === '') {

    $title = t('not_available');
}


/* =========================================================
   DESCRIPTION
========================================================= */

$description = trim(
    $row['description'] ?? ''
);


if ($description === '') {

    $description =
        t('no_description');
}


/* =========================================================
   MEMBERS
========================================================= */

$owner_name = trim(
    $row['student_name'] ?? ''
);


$authors = trim(
    $row['authors'] ?? ''
);


$member_list = [];

if ($authors !== '') {

    $member_list = preg_split(
        '/\r\n|\r|\n/',
        $authors
    );
}


/*
   ถ้า authors ไม่มีข้อมูล
   ให้ถือว่าไม่มีสมาชิกเพิ่มเติม
*/

$members = [];


/*
   เอาชื่อเจ้าของออกจากสมาชิก
   เพื่อไม่ให้เจ้าของแสดงซ้ำ
*/

$owner_removed = false;


foreach ($member_list as $member) {

    $member = trim($member);

    if ($member === '') {
        continue;
    }


    /*
       ลบเจ้าของออกเพียง 1 ครั้ง
    */

    if (
        !$owner_removed &&
        $owner_name !== '' &&
        normalize_teacher_name_for_comment($member)
        ===
        normalize_teacher_name_for_comment($owner_name)
    ) {

        $owner_removed = true;

        continue;
    }


    $members[] = $member;
}


$member_count = count($members);


/* =========================================================
   USER MAP
========================================================= */

$user_map = [];


if ($logged_in) {

    $user_map_sql = "
        SELECT
            id,
            prefix,
            first_name,
            last_name,
            role
        FROM users
        WHERE role IN (
            'student',
            'teacher',
            'admin'
        )
    ";


    $user_map_result = mysqli_query(
        $conn,
        $user_map_sql
    );


    if ($user_map_result) {

        while (
            $user_data =
            mysqli_fetch_assoc(
                $user_map_result
            )
        ) {

            $db_prefix = trim(
                $user_data['prefix'] ?? ''
            );

            $db_first_name = trim(
                $user_data['first_name'] ?? ''
            );

            $db_last_name = trim(
                $user_data['last_name'] ?? ''
            );


            $full_name = trim(
                $db_prefix . ' ' .
                $db_first_name . ' ' .
                $db_last_name
            );


            $name_without_prefix = trim(
                $db_first_name . ' ' .
                $db_last_name
            );


            $full_name_key =
                normalize_teacher_name_for_comment(
                    $full_name
                );


            $name_without_prefix_key =
                normalize_teacher_name_for_comment(
                    $name_without_prefix
                );


            if ($full_name_key !== '') {

                $user_map[
                    $full_name_key
                ] = $user_data;
            }


            if ($name_without_prefix_key !== '') {

                $user_map[
                    $name_without_prefix_key
                ] = $user_data;
            }
        }
    }
}


/* =========================================================
   ADVISOR
========================================================= */

$advisor = trim(
    $row['advisor'] ?? ''
);


if ($advisor === '') {

    $advisor = t('not_specified');
}


/* =========================================================
   DEGREE
========================================================= */

$degree = trim(
    $row['degree'] ?? ''
);


if ($degree === '') {

    $degree = trim(
        $row['project_type'] ?? ''
    );
}


if ($degree === '') {

    $degree = '-';
}


/* =========================================================
   DEPARTMENT
========================================================= */

$department = trim(
    $row['department'] ?? ''
);


if ($department === '') {

    $department = '-';
}


/* =========================================================
   STATUS
========================================================= */

$status = trim(
    $row['status'] ?? ''
);


if ($status === '') {

    $status = $lang === 'en'
        ? 'Submitted'
        : 'ส่งแล้ว';
}


/* =========================================================
   GITHUB
========================================================= */

$github_url = trim(
    $row['github_url'] ?? ''
);


/* =========================================================
   PDF
========================================================= */

$pdf_file = trim(
    $row['pdf_file'] ?? ''
);


$has_pdf = false;

$safe_pdf = '';


if ($pdf_file !== '') {

    $safe_pdf = basename($pdf_file);

    if ($safe_pdf !== '') {

        $has_pdf = true;
    }
}


/* =========================================================
   COUNTERS
========================================================= */

$view_count = (int)(
    $row['views'] ?? 0
);


$download_count = (int)(
    $row['downloads'] ?? 0
);


/* =========================================================
   DATE
========================================================= */

$created_at = '-';


if (!empty($row['created_at'])) {

    $timestamp = strtotime(
        $row['created_at']
    );


    if ($timestamp !== false) {

        $created_at = date(
            'd/m/Y',
            $timestamp
        );
    }
}


/* =========================================================
   OWNER
========================================================= */

$student_id = isset(
    $row['student_id']
)
    ? (int)$row['student_id']
    : 0;


$is_owner = (
    $logged_in &&
    $user_id > 0 &&
    $student_id > 0 &&
    $student_id === $user_id
);


/* =========================================================
   EDIT / DELETE
========================================================= */

$can_edit = false;

$can_delete = false;


if ($logged_in) {

    if ($user_role === 'admin') {

        $can_edit = true;
        $can_delete = true;

    } elseif (
        $user_role === 'student' &&
        $is_owner
    ) {

        $can_delete = true;
    }
}


/* =========================================================
   TEACHER COMMENT PERMISSION
========================================================= */

$can_comment = false;


if (
    $logged_in &&
    $user_role === 'teacher'
) {

    $teacher_stmt = mysqli_prepare(
        $conn,
        "
        SELECT
            prefix,
            first_name,
            last_name
        FROM users
        WHERE id = ?
        AND role = 'teacher'
        LIMIT 1
        "
    );


    $teacher_prefix = '';

    $teacher_first_name = '';

    $teacher_last_name = '';


    if ($teacher_stmt) {

        mysqli_stmt_bind_param(
            $teacher_stmt,
            "i",
            $user_id
        );


        mysqli_stmt_execute(
            $teacher_stmt
        );


        $teacher_result =
            mysqli_stmt_get_result(
                $teacher_stmt
            );


        if ($teacher_result) {

            $teacher_data =
                mysqli_fetch_assoc(
                    $teacher_result
                );


            if ($teacher_data) {

                $teacher_prefix =
                    trim(
                        $teacher_data['prefix']
                        ?? ''
                    );


                $teacher_first_name =
                    trim(
                        $teacher_data['first_name']
                        ?? ''
                    );


                $teacher_last_name =
                    trim(
                        $teacher_data['last_name']
                        ?? ''
                    );
            }
        }


        mysqli_stmt_close(
            $teacher_stmt
        );
    }


    $teacher_with_prefix = trim(
        $teacher_prefix . ' ' .
        $teacher_first_name . ' ' .
        $teacher_last_name
    );


    $teacher_without_prefix = trim(
        $teacher_first_name . ' ' .
        $teacher_last_name
    );


    $advisor_normalized =
        normalize_teacher_name_for_comment(
            $advisor
        );


    $teacher_with_prefix_normalized =
        normalize_teacher_name_for_comment(
            $teacher_with_prefix
        );


    $teacher_without_prefix_normalized =
        normalize_teacher_name_for_comment(
            $teacher_without_prefix
        );


    if (
        $advisor_normalized !== '' &&
        (
            $advisor_normalized ===
            $teacher_with_prefix_normalized

            ||

            $advisor_normalized ===
            $teacher_without_prefix_normalized
        )
    ) {

        $can_comment = true;
    }
}


/* =========================================================
   PROJECT TYPE
========================================================= */

$project_type_label = trim(
    $row['project_type'] ?? ''
);


if ($project_type_label === '') {

    $project_type_label =
        t('student_project');
}


/* =========================================================
   COMMENTS
========================================================= */

$comments = [];


$comment_sql = "
    SELECT
        pc.id,
        pc.project_id,
        pc.teacher_id,
        pc.comment,
        pc.created_at,
        u.prefix,
        u.first_name,
        u.last_name
    FROM project_comments pc
    LEFT JOIN users u
        ON pc.teacher_id = u.id
    WHERE pc.project_id = ?
    ORDER BY pc.id DESC
";


$comment_stmt = mysqli_prepare(
    $conn,
    $comment_sql
);


if ($comment_stmt) {

    mysqli_stmt_bind_param(
        $comment_stmt,
        "i",
        $project_id
    );


    mysqli_stmt_execute(
        $comment_stmt
    );


    $comment_result =
        mysqli_stmt_get_result(
            $comment_stmt
        );


    if ($comment_result) {

        while (
            $comment_row =
            mysqli_fetch_assoc(
                $comment_result
            )
        ) {

            $comments[] =
                $comment_row;
        }
    }


    mysqli_stmt_close(
        $comment_stmt
    );
}

?>

<!DOCTYPE html>

<html
    lang="<?php echo $lang === 'en' ? 'en' : 'th'; ?>"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo e($title); ?>
        -
        <?php echo e(t('project_archive')); ?>
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
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        :root {

            --primary: #3287bb;
            --primary-light: #58b4df;
            --primary-dark: #174f70;

            --text: #243746;
            --muted: #71828d;

            --border: #e3edf3;

            --soft-blue: #eef8fd;

            --green: #2e9b68;

            --danger: #dc3545;

            --shadow:
                0 12px 35px
                rgba(42, 104, 139, 0.10);
        }


        body {

            margin: 0;

            min-height: 100vh;

            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(88,180,223,.13),
                    transparent 25%
                ),

                radial-gradient(
                    circle at 90% 15%,
                    rgba(50,135,187,.10),
                    transparent 25%
                ),

                linear-gradient(
                    180deg,
                    #f4f9fc 0%,
                    #ffffff 45%,
                    #f7fbfd 100%
                );

            color: var(--text);

            font-family:
                'Sarabun',
                'Segoe UI',
                Tahoma,
                Arial,
                sans-serif;
        }


        a {
            transition: .2s ease;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .custom-header {

            position: sticky;

            top: 0;

            z-index: 1000;

            min-height: 82px;

            background:
                linear-gradient(
                    135deg,
                    rgba(74,164,214,.97),
                    rgba(50,135,187,.98)
                );

            border-bottom:
                1px solid
                rgba(255,255,255,.20);

            box-shadow:
                0 8px 28px
                rgba(35,100,136,.20);

            backdrop-filter: blur(14px);
        }


        .header-inner {

            max-width: 1280px;

            min-height: 82px;

            margin: auto;

            padding: 0 25px;

            display: flex;

            align-items: center;

            justify-content: flex-start;

            gap: 20px;
        }


        .header-left-area {

            display: flex;

            align-items: center;

            gap: 17px;
        }


        .logo-link {

            display: flex;

            align-items: center;

            text-decoration: none;
        }


        .sdu-logo {

            width: 54px;

            height: 54px;

            object-fit: contain;

            background: white;

            border-radius: 50%;

            padding: 3px;

            box-shadow:
                0 5px 15px
                rgba(0,0,0,.18);

            transition: .25s;
        }


        .logo-link:hover .sdu-logo {

            transform:
                translateY(-2px)
                rotate(-2deg);

            box-shadow:
                0 8px 20px
                rgba(0,0,0,.22);
        }


        .home-link {

            display: flex;

            align-items: center;

            gap: 9px;

            color: white;

            text-decoration: none;

            font-size: 17px;

            font-weight: 700;

            padding: 10px 15px;

            border-radius: 12px;
        }


        .home-link:hover {

            color: white;

            background:
                rgba(255,255,255,.12);

            transform:
                translateY(-1px);
        }


        .home-icon {

            font-size: 20px;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .detail-container {

            max-width: 1240px;

            margin: 42px auto 70px;

            padding: 0 20px;
        }


        .project-card {

            position: relative;

            overflow: hidden;

            background: rgba(255,255,255,.94);

            border:
                1px solid
                rgba(221,233,240,.95);

            border-radius: 24px;

            box-shadow: var(--shadow);

            backdrop-filter: blur(8px);
        }


        .project-card::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 5px;

            background:
                linear-gradient(
                    90deg,
                    #58b4df,
                    #3287bb,
                    #174f70
                );
        }


        .project-card-body {

            padding: 36px;
        }


        /* =====================================================
           SIDE PANEL
        ===================================================== */

        .side-panel {

            height: 100%;

            padding: 22px;

            background:
                linear-gradient(
                    180deg,
                    #f8fcfe 0%,
                    #ffffff 100%
                );

            border:
                1px solid
                var(--border);

            border-radius: 18px;

            box-shadow:
                0 5px 20px
                rgba(50,135,187,.05);
        }


        .side-title {

            display: flex;

            align-items: center;

            gap: 9px;

            color: var(--primary-dark);

            font-size: 18px;

            font-weight: 800;

            padding-bottom: 17px;

            margin-bottom: 4px;

            border-bottom:
                1px solid
                var(--border);
        }


        .side-title i {

            color: var(--primary);

            font-size: 19px;
        }


        /* =====================================================
           PDF
        ===================================================== */

        .pdf-button {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            padding: 13px 15px;

            border-radius: 11px;

            color: white;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 9px;

            box-shadow:
                0 5px 14px
                rgba(50,135,187,.13);
        }


        .pdf-button:hover {

            color: white;

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 18px
                rgba(50,135,187,.20);
        }


        .pdf-button i {

            font-size: 19px;
        }


        .pdf-view-button {

            background:
                linear-gradient(
                    135deg,
                    #58b4df,
                    #3287bb
                );
        }


        .pdf-download-button {

            background:
                linear-gradient(
                    135deg,
                    #51b982,
                    #27895d
                );
        }


        .pdf-document-label {

            display: flex;

            align-items: center;

            gap: 7px;

            color: var(--muted);

            font-size: 12px;

            margin:
                7px 0 12px;
        }


        /* =====================================================
           INFO
        ===================================================== */

        .info-item {

            padding: 15px 0;

            border-top:
                1px solid
                #e7eef2;
        }


        .info-label {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #607985;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 7px;

            text-transform: uppercase;

            letter-spacing: .15px;
        }


        .info-label i {

            color: var(--primary);

            font-size: 16px;
        }


        .info-value {

            color: var(--text);

            font-size: 14px;

            line-height: 1.65;

            word-break: break-word;
        }


        .advisor-value {

            color: var(--primary-dark);

            font-weight: 700;

            line-height: 1.7;
        }


        .status-pill {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 11px;

            background: #eaf8f1;

            border:
                1px solid
                #ccebdc;

            color: #277b55;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 700;
        }


        .status-pill i {

            font-size: 13px;
        }


        /* =====================================================
           STAT
        ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 8px;
        }


        .stat-box {

            padding: 12px 10px;

            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 10px;

            text-align: center;
        }


        .stat-icon {

            font-size: 18px;

            color: var(--primary);
        }


        .stat-number {

            display: block;

            color: var(--primary-dark);

            font-size: 17px;

            font-weight: 800;

            margin-top: 3px;
        }


        .stat-label {

            display: block;

            color: var(--muted);

            font-size: 10px;

            margin-top: 2px;
        }


        /* =====================================================
           MEMBERS
        ===================================================== */

        .member-list {

            display: flex;

            flex-direction: column;

            gap: 8px;

            margin-top: 5px;
        }


        .member-item {

            display: flex;

            align-items: flex-start;

            gap: 8px;

            padding: 10px 11px;

            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 9px;

            color: var(--text);

            font-size: 14px;

            line-height: 1.55;

            transition: .2s;
        }


        .member-item:hover {

            border-color:
                #c9e5f3;

            transform:
                translateX(2px);

            box-shadow:
                0 4px 12px
                rgba(50,135,187,.07);
        }


        .member-number {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 22px;

            height: 22px;

            flex-shrink: 0;

            border-radius: 50%;

            background: var(--soft-blue);

            color: var(--primary);

            font-size: 11px;

            font-weight: 800;
        }


        .member-name {

            word-break: break-word;
        }


        .member-profile-link {

            color: var(--primary-dark);

            text-decoration: none;

            font-weight: 700;
        }


        .member-profile-link:hover {

            color: var(--primary);

            text-decoration: underline;
        }


        .member-profile-link i {

            font-size: 11px;

            margin-left: 4px;

            opacity: .65;
        }


        /* =====================================================
           GITHUB
        ===================================================== */

        .github-card {

            margin-top: 18px;

            padding: 17px;

            background:
                linear-gradient(
                    135deg,
                    #f7f9fb,
                    #ffffff
                );

            border:
                1px solid
                #e0e7ec;

            border-radius: 13px;
        }


        .github-title {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #24292f;

            font-size: 14px;

            font-weight: 800;

            margin-bottom: 10px;
        }


        .github-title i {

            font-size: 19px;
        }


        .github-url {

            display: block;

            padding: 10px;

            margin-bottom: 10px;

            background: white;

            border:
                1px solid
                #dce4e9;

            border-radius: 8px;

            color: var(--primary);

            font-size: 12px;

            word-break: break-all;

            text-decoration: none;
        }


        .github-url:hover {

            border-color:
                #afd6e9;

            background:
                #f9fdff;
        }


        .github-button {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 10px;

            background: #24292f;

            color: white;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;
        }


        .github-button:hover {

            background: #111;

            color: white;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           PROJECT CONTENT
        ===================================================== */

        .project-content {

            padding-left: 15px;
        }


        .project-category {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            background:
                linear-gradient(
                    135deg,
                    #eaf7fd,
                    #f2faff
                );

            color: var(--primary);

            border:
                1px solid
                #cbe7f5;

            padding: 7px 13px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 800;

            margin-bottom: 17px;
        }


        .project-title {

            font-size: clamp(
                28px,
                4vw,
                40px
            );

            line-height: 1.28;

            font-weight: 800;

            color: var(--primary-dark);

            margin: 0 0 13px;

            letter-spacing: -.4px;
        }


        .university-text {

            display: flex;

            align-items: center;

            gap: 8px;

            color: var(--muted);

            font-size: 14px;

            margin-bottom: 25px;
        }


        .university-text i {

            color: var(--primary);
        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .description-box {

            position: relative;

            background:
                linear-gradient(
                    135deg,
                    #fbfdfe,
                    #f7fbfd
                );

            border:
                1px solid
                var(--border);

            border-radius: 15px;

            padding: 23px;

            overflow: hidden;
        }


        .description-box::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            bottom: 0;

            width: 4px;

            background:
                linear-gradient(
                    180deg,
                    #58b4df,
                    #3287bb
                );
        }


        .description-title {

            display: flex;

            align-items: center;

            gap: 9px;

            color: var(--primary-dark);

            font-size: 19px;

            font-weight: 800;

            margin-bottom: 13px;
        }


        .description-title i {

            color: var(--primary);
        }


        .description-text {

            color: #526570;

            font-size: 15px;

            line-height: 1.9;

            white-space: pre-line;

            margin: 0;
        }


        /* =====================================================
           COMMENTS
        ===================================================== */

        .comments-box {

            margin-top: 24px;

            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 15px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(48,105,139,.06);
        }


        .comments-title {

            display: flex;

            align-items: center;

            gap: 9px;

            color: var(--primary-dark);

            font-size: 19px;

            font-weight: 800;

            margin-bottom: 10px;
        }


        .comments-title i {

            color: var(--primary);
        }


        .comment-advisor {

            display: flex;

            align-items: center;

            gap: 7px;

            color: var(--primary);

            font-size: 13px;

            padding-bottom: 14px;

            border-bottom:
                1px solid
                #e7eef2;

            margin-bottom: 15px;

            flex-wrap: wrap;
        }


        .comment-advisor strong {

            color: var(--primary-dark);
        }


        .teacher-comment-form {

            background:
                #f4faff;

            border:
                1px solid
                #d8edf8;

            border-radius: 11px;

            padding: 16px;

            margin-bottom: 20px;
        }


        .teacher-comment-form textarea {

            width: 100%;

            min-height: 120px;

            resize: vertical;

            border:
                1px solid
                #cedee8;

            border-radius: 9px;

            padding: 12px;

            font-family: inherit;

            font-size: 14px;

            outline: none;

            transition: .2s;
        }


        .teacher-comment-form textarea:focus {

            border-color:
                var(--primary);

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,.12);
        }


        .comment-submit {

            margin-top: 10px;

            background:
                linear-gradient(
                    135deg,
                    #58b4df,
                    #3287bb
                );

            border: none;

            color: white;

            padding: 10px 18px;

            border-radius: 8px;

            font-family: inherit;

            font-weight: 700;

            cursor: pointer;

            transition: .2s;
        }


        .comment-submit:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 5px 13px
                rgba(50,135,187,.18);
        }


        .comment-item {

            padding: 16px 0;

            border-top:
                1px solid
                #e7eef2;

            position: relative;
        }


        .comment-author {

            display: flex;

            align-items: center;

            gap: 8px;

            color: var(--primary-dark);

            font-weight: 800;

            margin-bottom: 6px;

            flex-wrap: wrap;
        }


        .comment-author i {

            color: var(--primary);
        }


        .comment-date {

            color: #8999a2;

            font-size: 11px;

            margin-left: 4px;

            font-weight: 500;
        }


        .comment-text {

            color: #53636c;

            font-size: 14px;

            line-height: 1.8;

            white-space: pre-line;

            margin: 10px 0 0;
        }


        .comment-delete-form {

            margin-top: 12px;
        }


        .comment-delete-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            border: 1px solid
                #f3c6ca;

            background: #fff0f1;

            color: var(--danger);

            padding: 6px 11px;

            border-radius: 7px;

            font-family: inherit;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            transition: .2s;
        }


        .comment-delete-button:hover {

            background: var(--danger);

            color: white;
        }


        .no-comments {

            color: #8999a2;

            text-align: center;

            padding: 22px 10px;

            font-size: 14px;
        }


        .teacher-only-notice {

            background: #fff8e8;

            border:
                1px solid
                #f3dfaa;

            color: #80651e;

            border-radius: 9px;

            padding: 11px 13px;

            font-size: 13px;

            margin-bottom: 18px;

            line-height: 1.6;
        }


        /* =====================================================
           ADMIN
        ===================================================== */

        .admin-actions {

            display: flex;

            gap: 9px;

            margin-top: 20px;

            flex-wrap: wrap;
        }


        .edit-button,
        .delete-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 10px 16px;

            color: white;

            border-radius: 9px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: .2s;
        }


        .edit-button {

            background:
                linear-gradient(
                    135deg,
                    #58b4df,
                    #3287bb
                );
        }


        .delete-button {

            background:
                linear-gradient(
                    135deg,
                    #ef5969,
                    #dc3545
                );
        }


        .edit-button:hover,
        .delete-button:hover {

            color: white;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           GUEST NOTICE
        ===================================================== */

        .guest-notice {

            margin-top: 20px;

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 14px 16px;

            background:
                linear-gradient(
                    135deg,
                    #eef8fd,
                    #f7fcff
                );

            border:
                1px solid
                #cfeaf7;

            border-radius: 11px;

            color: #35677f;

            font-size: 13px;

            line-height: 1.7;
        }


        .guest-notice i {

            color: var(--primary);

            font-size: 17px;

            margin-top: 1px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .custom-header {

                min-height: 72px;
            }


            .header-inner {

                min-height: 72px;

                padding: 0 13px;
            }


            .header-left-area {

                gap: 7px;
            }


            .sdu-logo {

                width: 45px;

                height: 45px;
            }


            .home-link {

                padding: 8px;

                font-size: 14px;
            }


            .home-link span {

                display: none;
            }


            .detail-container {

                margin: 22px auto 45px;

                padding: 0 11px;
            }


            .project-card {

                border-radius: 17px;
            }


            .project-card-body {

                padding: 17px;
            }


            .side-panel {

                padding: 16px;
            }


            .project-content {

                padding-left: 0;

                margin-top: 5px;
            }


            .project-title {

                font-size: 28px;

                line-height: 1.35;
            }


            .description-box {

                padding: 19px;
            }


            .description-text {

                font-size: 14px;

                line-height: 1.8;
            }


            .stats-grid {

                gap: 7px;
            }

        }


        @media (max-width: 420px) {

            .project-title {

                font-size: 25px;
            }


            .pdf-button {

                font-size: 13px;
            }


            .side-title {

                font-size: 16px;
            }


            .comments-box {

                padding: 16px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="custom-header">

    <div class="header-inner">

        <div class="header-left-area">

            <a
                href="index2.php?lang=<?php echo e($lang); ?>"
                class="logo-link"
                title="<?php echo e(t('back_home')); ?>"
            >

                <img
                    src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                    alt="SDU Logo"
                    class="sdu-logo"
                >

            </a>


            <a
                href="index2.php?lang=<?php echo e($lang); ?>"
                class="home-link"
            >

                <i
                    class="bi bi-house-fill home-icon"
                ></i>

                <span>
                    <?php echo e(t('home')); ?>
                </span>

            </a>

        </div>

    </div>

</header>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="detail-container">


    <div class="project-card">


        <div class="project-card-body">


            <div class="row g-4">


                <!-- =================================================
                     LEFT INFORMATION
                ================================================== -->

                <div class="col-lg-4">


                    <div class="side-panel">


                        <div class="side-title">

                            <i class="bi bi-info-circle-fill"></i>

                            <?php
                            echo e(
                                t('project_information')
                            );
                            ?>

                        </div>


                        <!-- PDF -->

                        <?php if ($has_pdf): ?>


                            <div class="pdf-document-label">

                                <i class="bi bi-file-earmark-pdf"></i>

                                <?php
                                echo e(
                                    t('pdf_document')
                                );
                                ?>

                            </div>


                            <a
                                href="view-pdf.php?id=<?php echo $project_id; ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pdf-button pdf-view-button"
                            >

                                <i class="bi bi-eye-fill"></i>

                                <?php
                                echo e(
                                    t('view_pdf')
                                );
                                ?>

                            </a>


                            <a
                                href="download-pdf.php?id=<?php echo $project_id; ?>"
                                class="pdf-button pdf-download-button"
                            >

                                <i class="bi bi-download"></i>

                                <?php
                                echo e(
                                    t('download_pdf')
                                );
                                ?>

                            </a>


                        <?php else: ?>


                            <div class="pdf-document-label">

                                <i class="bi bi-file-earmark-x"></i>

                                <?php
                                echo e(
                                    t('pdf_document')
                                );
                                ?>

                            </div>


                            <button
                                type="button"
                                class="btn btn-secondary w-100 mb-3"
                                disabled
                            >

                                <i class="bi bi-file-earmark-x"></i>

                                <?php
                                echo e(
                                    t('no_pdf')
                                );
                                ?>

                            </button>


                        <?php endif; ?>


                        <!-- DATE -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-calendar3"></i>

                                <?php
                                echo e(
                                    t('published')
                                );
                                ?>

                            </div>


                            <div class="info-value">

                                <?php
                                echo e($created_at);
                                ?>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <?php if ($logged_in): ?>


                            <div class="info-item">

                                <div class="info-label">

                                    <i class="bi bi-check-circle"></i>

                                    <?php
                                    echo e(
                                        t('status')
                                    );
                                    ?>

                                </div>


                                <div class="info-value">

                                    <span class="status-pill">

                                        <i
                                            class="bi bi-check-circle-fill"
                                        ></i>

                                        <?php
                                        echo e($status);
                                        ?>

                                    </span>

                                </div>

                            </div>


                        <?php endif; ?>


                        <!-- ADMIN STATS -->

                        <?php if (
                            $logged_in &&
                            $user_role === 'admin'
                        ): ?>


                            <div class="info-item">

                                <div class="info-label">

                                    <i class="bi bi-bar-chart-fill"></i>

                                    <?php
                                    echo e(
                                        t('project_stats')
                                    );
                                    ?>

                                </div>


                                <div class="stats-grid">


                                    <div class="stat-box">

                                        <i
                                            class="bi bi-eye stat-icon"
                                        ></i>

                                        <span
                                            class="stat-number"
                                        >

                                            <?php
                                            echo number_format(
                                                $view_count
                                            );
                                            ?>

                                        </span>

                                        <span
                                            class="stat-label"
                                        >

                                            <?php
                                            echo e(
                                                t('pdf_views')
                                            );
                                            ?>

                                        </span>

                                    </div>


                                    <div class="stat-box">

                                        <i
                                            class="bi bi-download stat-icon"
                                        ></i>

                                        <span
                                            class="stat-number"
                                        >

                                            <?php
                                            echo number_format(
                                                $download_count
                                            );
                                            ?>

                                        </span>

                                        <span
                                            class="stat-label"
                                        >

                                            <?php
                                            echo e(
                                                t('pdf_downloads')
                                            );
                                            ?>

                                        </span>

                                    </div>


                                </div>

                            </div>


                        <?php endif; ?>


                        <!-- DEGREE -->

                        <?php if ($logged_in): ?>


                            <div class="info-item">

                                <div class="info-label">

                                    <i class="bi bi-mortarboard-fill"></i>

                                    <?php
                                    echo e(
                                        t('education_level')
                                    );
                                    ?>

                                </div>


                                <div class="info-value">

                                    <?php
                                    echo e($degree);
                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>


                        <!-- DEPARTMENT -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-building"></i>

                                <?php
                                echo e(
                                    t('department')
                                );
                                ?>

                            </div>


                            <div class="info-value">

                                <?php
                                echo e($department);
                                ?>

                            </div>

                        </div>


                        <!-- ADVISOR -->

                        <div class="info-item">

                            <div class="info-label">

                                <i class="bi bi-person-workspace"></i>

                                <?php
                                echo e(
                                    t('advisor')
                                );
                                ?>

                            </div>


                            <div class="info-value advisor-value">

                                <?php
                                echo e($advisor);
                                ?>

                            </div>

                        </div>


                        <!-- MEMBERS -->

                        <?php if ($logged_in): ?>


                            <div class="info-item">

                                <div class="info-label">

                                    <i class="bi bi-people-fill"></i>

                                    <?php
                                    echo e(
                                        t('members')
                                    );
                                    ?>

                                    (
                                    <?php
                                    echo $member_count;
                                    ?>

                                    <?php
                                    echo e(
                                        t('person')
                                    );
                                    ?>

                                    )

                                </div>


                                <div class="member-list">


                                    <?php if (
                                        $member_count === 0
                                    ): ?>


                                        <div class="member-item">

                                            <span
                                                class="member-number"
                                            >
                                                -
                                            </span>

                                            <span
                                                class="member-name"
                                            >

                                                <?php
                                                echo e(
                                                    t('no_member')
                                                );
                                                ?>

                                            </span>

                                        </div>


                                    <?php else: ?>


                                        <?php foreach (
                                            $members
                                            as $index => $member
                                        ): ?>


                                            <?php

                                            $member_key =
                                                normalize_teacher_name_for_comment(
                                                    $member
                                                );


                                            $member_user =
                                                $user_map[
                                                    $member_key
                                                ] ?? null;


                                            $can_view_member_profile =
                                                false;


                                            if ($member_user) {

                                                $member_role =
                                                    $member_user['role']
                                                    ?? '';


                                                $can_view_member_profile =
                                                    in_array(
                                                        $member_role,
                                                        [
                                                            'student',
                                                            'teacher',
                                                            'admin'
                                                        ],
                                                        true
                                                    );
                                            }

                                            ?>


                                            <div class="member-item">


                                                <span
                                                    class="member-number"
                                                >

                                                    <?php
                                                    echo $index + 1;
                                                    ?>

                                                </span>


                                                <?php if (
                                                    $member_user &&
                                                    $can_view_member_profile
                                                ): ?>


                                                    <a
                                                        href="profile.php?id=<?php
                                                            echo (int)
                                                                $member_user['id'];
                                                        ?>"
                                                        class="member-name member-profile-link"
                                                        title="<?php
                                                            echo e(
                                                                t('profile')
                                                            );
                                                        ?>"
                                                    >

                                                        <?php
                                                        echo e($member);
                                                        ?>


                                                        <i
                                                            class="bi bi-box-arrow-up-right"
                                                        ></i>

                                                    </a>


                                                <?php else: ?>


                                                    <span
                                                        class="member-name"
                                                    >

                                                        <?php
                                                        echo e($member);
                                                        ?>

                                                    </span>


                                                <?php endif; ?>


                                            </div>


                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                </div>

                            </div>


                        <?php endif; ?>


                        <!-- GITHUB -->

                        <?php if ($logged_in): ?>


                            <div class="github-card">


                                <div class="github-title">

                                    <i class="bi bi-github"></i>

                                    <?php
                                    echo e(
                                        t('github_repository')
                                    );
                                    ?>

                                </div>


                                <?php if (
                                    $github_url !== ''
                                ): ?>


                                    <a
                                        href="<?php
                                            echo e($github_url);
                                        ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="github-url"
                                    >

                                        <?php
                                        echo e($github_url);
                                        ?>

                                    </a>


                                    <a
                                        href="<?php
                                            echo e($github_url);
                                        ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="github-button"
                                    >

                                        <i class="bi bi-github"></i>

                                        <?php
                                        echo e(
                                            t('github_project')
                                        );
                                        ?>

                                    </a>


                                <?php else: ?>


                                    <div
                                        class="text-muted"
                                        style="font-size:13px;"
                                    >

                                        <?php
                                        echo e(
                                            t('no_github')
                                        );
                                        ?>

                                    </div>


                                <?php endif; ?>


                            </div>


                        <?php endif; ?>


                    </div>

                </div>


                <!-- =================================================
                     RIGHT CONTENT
                ================================================== -->

                <div class="col-lg-8">


                    <div class="project-content">


                        <!-- PROJECT TYPE -->

                        <?php if ($logged_in): ?>


                            <div class="project-category">

                                <i class="bi bi-folder2-open"></i>

                                <?php
                                echo e(
                                    $project_type_label
                                );
                                ?>

                            </div>


                        <?php endif; ?>


                        <!-- TITLE -->

                        <h1 class="project-title">

                            <?php
                            echo e($title);
                            ?>

                        </h1>


                        <!-- UNIVERSITY -->

                        <div class="university-text">

                            <i class="bi bi-mortarboard-fill"></i>

                            <?php
                            echo e(
                                t('university')
                            );
                            ?>

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="description-box">


                            <div class="description-title">

                                <i class="bi bi-file-text-fill"></i>

                                <?php
                                echo e(
                                    t('description')
                                );
                                ?>

                            </div>


                            <p class="description-text">

                                <?php
                                echo nl2br(
                                    e($description)
                                );
                                ?>

                            </p>


                        </div>


                        <!-- COMMENTS -->

                        <?php if ($logged_in): ?>


                            <div class="comments-box">


                                <div class="comments-title">

                                    <i
                                        class="bi bi-chat-left-text-fill"
                                    ></i>

                                    <?php
                                    echo e(
                                        t('advisor_comments')
                                    );
                                    ?>

                                </div>


                                <div class="comment-advisor">

                                    <i
                                        class="bi bi-person-workspace"
                                    ></i>

                                    <span>

                                        <?php
                                        echo e(
                                            t('advisor_label')
                                        );
                                        ?>

                                    </span>

                                    <strong>

                                        <?php
                                        echo e($advisor);
                                        ?>

                                    </strong>

                                </div>


                                <!-- TEACHER FORM -->

                                <?php if ($can_comment): ?>


                                    <div
                                        class="teacher-comment-form"
                                    >


                                        <div
                                            class="mb-2"
                                            style="
                                                font-size:14px;
                                                font-weight:700;
                                                color:#245c7d;
                                            "
                                        >

                                            <i
                                                class="bi bi-pencil-square"
                                            ></i>

                                            <?php
                                            echo e(
                                                t('write_comment')
                                            );
                                            ?>

                                        </div>


                                        <form
                                            action="add-comment.php"
                                            method="POST"
                                        >


                                            <input
                                                type="hidden"
                                                name="project_id"
                                                value="<?php
                                                    echo $project_id;
                                                ?>"
                                            >


                                            <textarea
                                                name="comment"
                                                placeholder="<?php
                                                    echo e(
                                                        t(
                                                            'comment_placeholder'
                                                        )
                                                    );
                                                ?>"
                                                required
                                            ></textarea>


                                            <button
                                                type="submit"
                                                class="comment-submit"
                                            >

                                                <i
                                                    class="bi bi-send-fill"
                                                ></i>

                                                <?php
                                                echo e(
                                                    t('send_comment')
                                                );
                                                ?>

                                            </button>


                                        </form>


                                    </div>


                                <?php elseif (
                                    $user_role === 'teacher'
                                ): ?>


                                    <div
                                        class="teacher-only-notice"
                                    >

                                        <i
                                            class="bi bi-info-circle"
                                        ></i>

                                        <?php
                                        echo e(
                                            t('teacher_only')
                                        );
                                        ?>

                                        <strong>

                                            <?php
                                            echo e($advisor);
                                            ?>

                                        </strong>

                                        <?php
                                        echo e(
                                            t('teacher_only_2')
                                        );
                                        ?>

                                    </div>


                                <?php endif; ?>


                                <!-- SHOW COMMENTS -->

                                <?php if (
                                    count($comments) > 0
                                ): ?>


                                    <?php foreach (
                                        $comments
                                        as $comment
                                    ): ?>


                                        <?php

                                        $comment_prefix =
                                            trim(
                                                $comment['prefix']
                                                ?? ''
                                            );


                                        $comment_first_name =
                                            trim(
                                                $comment['first_name']
                                                ?? ''
                                            );


                                        $comment_last_name =
                                            trim(
                                                $comment['last_name']
                                                ?? ''
                                            );


                                        $comment_teacher =
                                            trim(
                                                $comment_prefix .
                                                ' ' .
                                                $comment_first_name .
                                                ' ' .
                                                $comment_last_name
                                            );


                                        if (
                                            $comment_teacher === ''
                                        ) {

                                            $comment_teacher =
                                                t(
                                                    'advisor_word'
                                                );
                                        }


                                        $comment_date = '-';


                                        if (
                                            !empty(
                                                $comment['created_at']
                                            )
                                        ) {

                                            $comment_timestamp =
                                                strtotime(
                                                    $comment['created_at']
                                                );


                                            if (
                                                $comment_timestamp !== false
                                            ) {

                                                $comment_date =
                                                    date(
                                                        'd/m/Y H:i',
                                                        $comment_timestamp
                                                    );
                                            }
                                        }


                                        $comment_teacher_id =
                                            isset(
                                                $comment['teacher_id']
                                            )
                                            ? (int)
                                                $comment['teacher_id']
                                            : 0;


                                        $can_delete_comment = (

                                            $user_role === 'admin'

                                            ||

                                            (
                                                $user_role === 'teacher' &&
                                                $comment_teacher_id === $user_id
                                            )

                                        );

                                        ?>


                                        <div
                                            class="comment-item"
                                        >


                                            <div
                                                class="comment-author"
                                            >

                                                <i
                                                    class="bi bi-person-workspace"
                                                ></i>


                                                <?php
                                                echo e(
                                                    $comment_teacher
                                                );
                                                ?>


                                                <span
                                                    class="comment-date"
                                                >

                                                    <?php
                                                    echo e(
                                                        $comment_date
                                                    );
                                                    ?>

                                                </span>


                                            </div>


                                            <p
                                                class="comment-text"
                                            >

                                                <?php
                                                echo nl2br(
                                                    e(
                                                        $comment['comment']
                                                    )
                                                );
                                                ?>

                                            </p>


                                            <?php if (
                                                $can_delete_comment
                                            ): ?>


                                                <form
                                                    action="delete-comment.php"
                                                    method="POST"
                                                    class="comment-delete-form"
                                                    onsubmit="
                                                        return confirm(
                                                            <?php
                                                            echo json_encode(
                                                                t(
                                                                    'comment_confirm_delete'
                                                                ),
                                                                JSON_UNESCAPED_UNICODE
                                                            );
                                                            ?>
                                                        );
                                                    "
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="comment_id"
                                                        value="<?php
                                                            echo (int)
                                                                $comment['id'];
                                                        ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="project_id"
                                                        value="<?php
                                                            echo $project_id;
                                                        ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="comment-delete-button"
                                                    >

                                                        <i
                                                            class="bi bi-trash3"
                                                        ></i>

                                                        <?php
                                                        echo e(
                                                            t(
                                                                'delete_comment'
                                                            )
                                                        );
                                                        ?>

                                                    </button>


                                                </form>


                                            <?php endif; ?>


                                        </div>


                                    <?php endforeach; ?>


                                <?php else: ?>


                                    <div
                                        class="no-comments"
                                    >

                                        <i
                                            class="bi bi-chat-square-text"
                                        ></i>

                                        <?php
                                        echo e(
                                            t('no_comments')
                                        );
                                        ?>

                                    </div>


                                <?php endif; ?>


                            </div>


                        <?php endif; ?>


                        <!-- ADMIN / OWNER -->

                        <?php if (
                            $can_edit ||
                            $can_delete
                        ): ?>


                            <div class="admin-actions">


                                <?php if ($can_edit): ?>


                                    <a
                                        href="admin_edit_project.php?id=<?php
                                            echo $project_id;
                                        ?>"
                                        class="edit-button"
                                    >

                                        <i
                                            class="bi bi-pencil-square"
                                        ></i>

                                        <?php
                                        echo e(
                                            t('edit_project')
                                        );
                                        ?>

                                    </a>


                                <?php endif; ?>


                                <?php if ($can_delete): ?>


                                    <a
                                        href="delete-project.php?id=<?php
                                            echo $project_id;
                                        ?>"
                                        class="delete-button"
                                        onclick="
                                            return confirm(
                                                <?php
                                                echo json_encode(
                                                    t(
                                                        'admin_confirm_delete'
                                                    ),
                                                    JSON_UNESCAPED_UNICODE
                                                );
                                                ?>
                                            );
                                        "
                                    >

                                        <i
                                            class="bi bi-trash3"
                                        ></i>

                                        <?php
                                        echo e(
                                            t('delete_project')
                                        );
                                        ?>

                                    </a>


                                <?php endif; ?>


                            </div>


                        <?php endif; ?>


                        <!-- GUEST NOTICE -->

                        <?php if (!$logged_in): ?>


                            <div class="guest-notice">

                                <i class="bi bi-eye-fill"></i>

                                <span>

                                    <?php
                                    echo e(
                                        t('guest_notice')
                                    );
                                    ?>

                                </span>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>