<?php
session_start();
require_once "db_connect.php";

/* =====================================================
   ฟังก์ชันป้องกัน XSS
===================================================== */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


/* =====================================================
   Google Forms
===================================================== */
$feedback_form_url =
    'https://docs.google.com/forms/d/e/1FAIpQLSdQ-ZUl1xnAAQ_tUqrl6iwZKvx7U9mhXEVvRcJGWsPUPcsegw/viewform';

/* สร้าง QR Code จาก Google Forms อัตโนมัติ */
$feedback_qr_url =
    'https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=10&data='
    . urlencode($feedback_form_url);


/* =====================================================
   ระบบเปลี่ยนภาษา
===================================================== */
$allowed_languages = ['th', 'en'];

if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], $allowed_languages, true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'th';


/* =====================================================
   ข้อความภาษา
===================================================== */
$translations = [

    'th' => [

        'page_title' => 'หน้าแรก - คลังโปรเจกต์ SDU',

        'home' => 'หน้าแรก',
        'admin' => 'Admin',
        'upload_project' => 'ส่งโปรเจกต์',
        'profile' => 'ข้อมูลส่วนตัว',
        'manage_system' => 'จัดการระบบ',
        'logout' => 'ออกจากระบบ',
        'login' => 'เข้าสู่ระบบ',

        'project_archive' => 'โปรเจกต์ SDU',

        'hero_description' =>
            'แหล่งรวบรวมและค้นหาโปรเจกต์ของนักศึกษา เพื่อให้สามารถเข้าถึงผลงานทางวิชาการ ค้นหาโครงงานตามระดับการศึกษาและสาขาวิชา รวมถึงดูข้อมูลอาจารย์ที่ปรึกษาและเอกสารโปรเจกต์ได้อย่างสะดวก',

        'about_title' =>
            'เว็บไซต์นี้ทำหน้าที่อะไร?',

        'about_description' =>
            'เว็บไซต์นี้จัดทำขึ้นเพื่อเป็นศูนย์กลางสำหรับ จัดเก็บ ค้นหา และเผยแพร่โปรเจกต์ของนักศึกษา ภายในมหาวิทยาลัยสวนดุสิต ช่วยให้ผู้ใช้งานสามารถค้นหาโครงงานที่สนใจ และดูรายละเอียดของแต่ละโปรเจกต์ได้ง่ายขึ้น',

        'search_project' => 'ค้นหาโปรเจกต์',
        'search_placeholder' => 'พิมพ์ชื่อโปรเจกต์...',

        'degree' => 'ระดับหลักสูตร',
        'all_degree' => 'ทุกระดับการศึกษา',
        'bachelor' => 'ปริญญาตรี',
        'master' => 'ปริญญาโท',
        'doctorate' => 'ปริญญาเอก',

        'major' => 'สาขาวิชา',
        'all_major' => 'ทุกสาขาวิชา',
        'it' => 'เทคโนโลยีสารสนเทศ',
        'cs' => 'วิทยาการคอมพิวเตอร์',
        'env' => 'วิทยาศาสตร์สิ่งแวดล้อม',
        'food' => 'เทคโนโลยีการประกอบอาหาร',

        'search_filter' => 'ค้นหาและกรองโปรเจกต์',
        'search' => 'ค้นหา',

        'found' => 'พบโปรเจกต์',
        'items' => 'รายการ',
        'for_keyword' => 'สำหรับคำค้นหา',

        'owner' => 'เจ้าของโปรเจกต์',
        'members' => 'สมาชิกกลุ่ม',
        'advisor' => 'อาจารย์ที่ปรึกษา',
        'date' => 'วันที่ลงโปรเจกต์',
        'description' => 'คำอธิบาย',

        'guest' =>
            'กำลังเข้าชมในฐานะบุคคลทั่วไป สามารถค้นหาและดูข้อมูลโปรเจกต์ได้',

        'no_project' => 'ไม่พบโปรเจกต์',

        'no_project_description' =>
            'ยังไม่มีโปรเจกต์ที่ตรงกับข้อมูลที่ค้นหา',

        'footer_description' =>
            'ระบบจัดเก็บและค้นหาโปรเจกต์ของนักศึกษา มหาวิทยาลัยสวนดุสิต',

        'language' => 'ภาษา',
        'thai' => 'ไทย',
        'english' => 'English',

        'not_specified' => 'ไม่ระบุชื่อโปรเจกต์',

        'pdf' => 'PDF',
        'github' => 'GitHub',

        'feedback_title' =>
            'สอบถาม ร้องเรียน ข้อคิดเห็น ข้อเสนอแนะ',

        'scan_qr' =>
            'สแกน QR Code',

        'feedback_description' =>
            'เพื่อสอบถาม ร้องเรียน แสดงความคิดเห็น หรือส่งข้อเสนอแนะ'
    ],


    'en' => [

        'page_title' => 'Home - SDU Project Archive',

        'home' => 'Home',
        'admin' => 'Admin',
        'upload_project' => 'Submit Project',
        'profile' => 'Profile',
        'manage_system' => 'Manage System',
        'logout' => 'Logout',
        'login' => 'Login',

        'project_archive' => 'SDU Project Archive',

        'hero_description' =>
            'A central platform for collecting and searching student projects, allowing users to access academic works, search projects by education level and department, and view advisor information and project documents easily.',

        'about_title' =>
            'What does this website do?',

        'about_description' =>
            'This website serves as a central platform for storing, searching, and publishing student projects at Suan Dusit University. Users can easily search for projects and view project details.',

        'search_project' => 'Search Projects',
        'search_placeholder' => 'Enter project name...',

        'degree' => 'Education Level',
        'all_degree' => 'All Education Levels',
        'bachelor' => "Bachelor's Degree",
        'master' => "Master's Degree",
        'doctorate' => 'Doctorate',

        'major' => 'Department',
        'all_major' => 'All Departments',
        'it' => 'Information Technology',
        'cs' => 'Computer Science',
        'env' => 'Environmental Science',
        'food' => 'Food Technology',

        'search_filter' => 'Search and Filter Projects',
        'search' => 'Search',

        'found' => 'Found',
        'items' => 'projects',
        'for_keyword' => 'for keyword',

        'owner' => 'Project Owner',
        'members' => 'Group Members',
        'advisor' => 'Advisor',
        'date' => 'Submission Date',
        'description' => 'Description',

        'guest' =>
            'You are browsing as a guest. You can search and view project information.',

        'no_project' => 'No Projects Found',

        'no_project_description' =>
            'There are no projects matching your search.',

        'footer_description' =>
            'Student project storage and search system, Suan Dusit University',

        'language' => 'Language',
        'thai' => 'ไทย',
        'english' => 'English',

        'not_specified' => 'Untitled Project',

        'pdf' => 'PDF',
        'github' => 'GitHub',

        'feedback_title' =>
            'Questions, Complaints, Comments & Suggestions',

        'scan_qr' =>
            'Scan QR Code',

        'feedback_description' =>
            'For questions, complaints, comments, or suggestions'
    ]

];


/* =====================================================
   ฟังก์ชันแปลภาษา
===================================================== */
function t($key)
{
    global $translations, $lang;

    return $translations[$lang][$key]
        ?? $translations['th'][$key]
        ?? $key;
}


/* =====================================================
   URL สำหรับเปลี่ยนภาษา
===================================================== */
function language_url($new_lang)
{
    $query = $_GET;

    $query['lang'] = $new_lang;

    return 'index2.php?' . http_build_query($query);
}


/* =====================================================
   บันทึกจำนวนผู้เข้าชมเว็บไซต์
===================================================== */
mysqli_query(
    $conn,
    "INSERT INTO site_visits (visited_at)
     VALUES (NOW())"
);


/* =====================================================
   ตรวจสอบการเข้าสู่ระบบ
===================================================== */
$logged_in = isset($_SESSION['user_id']);

$user_id =
    $logged_in
        ? (int)$_SESSION['user_id']
        : 0;

$user_role =
    $_SESSION['role'] ?? '';


/* =====================================================
   รูปโปรไฟล์
===================================================== */
$profile_image_url = '';

if (
    $logged_in &&
    $user_id > 0
) {

    $profile_stmt = mysqli_prepare(
        $conn,
        "SELECT profile_image
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    if ($profile_stmt) {

        mysqli_stmt_bind_param(
            $profile_stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute(
            $profile_stmt
        );

        $profile_result =
            mysqli_stmt_get_result(
                $profile_stmt
            );

        if ($profile_result) {

            $profile_row =
                mysqli_fetch_assoc(
                    $profile_result
                );

            $profile_image =
                trim(
                    $profile_row['profile_image']
                    ?? ''
                );

            if (
                $profile_image !== ''
            ) {

                $safe_profile_image =
                    basename(
                        $profile_image
                    );

                $profile_file_path =
                    __DIR__ .
                    DIRECTORY_SEPARATOR .
                    "profile_uploads" .
                    DIRECTORY_SEPARATOR .
                    $safe_profile_image;

                if (
                    is_file(
                        $profile_file_path
                    )
                ) {

                    $profile_image_url =
                        "profile_uploads/" .
                        rawurlencode(
                            $safe_profile_image
                        );
                }
            }
        }

        mysqli_stmt_close(
            $profile_stmt
        );
    }
}


/* =====================================================
   รูปโปรไฟล์เริ่มต้น
===================================================== */
if (
    $profile_image_url === ''
) {

    $profile_image_url =
        "https://cdn-icons-png.flaticon.com/512/149/149071.png";
}


/* =====================================================
   รับค่าค้นหา
===================================================== */
$keyword =
    trim(
        $_GET['keyword'] ?? ''
    );

$degree =
    $_GET['degree'] ?? 'all';

$major =
    $_GET['major'] ?? 'all';


/* =====================================================
   SQL
===================================================== */
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
        pages,
        pdf_file,
        status,
        student_id,
        github_url
    FROM projects
    WHERE 1=1
";


$params = [];

$types = "";


/* =====================================================
   ค้นหาโปรเจกต์
===================================================== */
if (
    $keyword !== ''
) {

    $search =
        "%" .
        $keyword .
        "%";

    $sql .= "
        AND (
            LOWER(project_name) LIKE LOWER(?)
            OR LOWER(title) LIKE LOWER(?)
            OR LOWER(authors) LIKE LOWER(?)
            OR LOWER(description) LIKE LOWER(?)
            OR LOWER(department) LIKE LOWER(?)
            OR LOWER(degree) LIKE LOWER(?)
            OR LOWER(advisor) LIKE LOWER(?)
            OR LOWER(student_name) LIKE LOWER(?)
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


    $types =
        "ssssssss";
}


/* =====================================================
   กรองระดับการศึกษา
===================================================== */
if (
    $degree === 'bachelor'
) {

    $sql .= "
        AND (
            degree = 'ปริญญาตรี'
            OR LOWER(degree) = 'bachelor'
        )
    ";

} elseif (
    $degree === 'master'
) {

    $sql .= "
        AND (
            degree = 'ปริญญาโท'
            OR LOWER(degree) = 'master'
        )
    ";

} elseif (
    $degree === 'doctorate'
) {

    $sql .= "
        AND (
            degree = 'ปริญญาเอก'
            OR LOWER(degree) = 'doctorate'
        )
    ";
}


/* =====================================================
   กรองสาขาวิชา
===================================================== */
if (
    $major === 'it'
) {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีสารสนเทศ%'
            OR LOWER(department) LIKE '%information technology%'
            OR department = 'IT'
        )
    ";

} elseif (
    $major === 'cs'
) {

    $sql .= "
        AND (
            department LIKE '%วิทยาการคอมพิวเตอร์%'
            OR LOWER(department) LIKE '%computer science%'
            OR department = 'CS'
        )
    ";

} elseif (
    $major === 'env'
) {

    $sql .= "
        AND (
            department LIKE '%วิทยาศาสตร์สิ่งแวดล้อม%'
            OR LOWER(department) LIKE '%environmental science%'
        )
    ";

} elseif (
    $major === 'food'
) {

    $sql .= "
        AND (
            department LIKE '%เทคโนโลยีการประกอบอาหาร%'
            OR LOWER(department) LIKE '%food technology%'
        )
    ";
}


/* =====================================================
   เรียงโปรเจกต์ล่าสุด
===================================================== */
$sql .= "
    ORDER BY id DESC
";


/* =====================================================
   Prepare
===================================================== */
$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (
    !$stmt
) {

    die(
        "SQL Error: " .
        e(
            mysqli_error(
                $conn
            )
        )
    );
}


/* =====================================================
   Bind Parameter
===================================================== */
if (
    !empty($params)
) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


/* =====================================================
   Execute
===================================================== */
if (
    !mysqli_stmt_execute(
        $stmt
    )
) {

    die(
        "Execute Error: " .
        e(
            mysqli_stmt_error(
                $stmt
            )
        )
    );
}


/* =====================================================
   Result
===================================================== */
$result =
    mysqli_stmt_get_result(
        $stmt
    );

?>

<!DOCTYPE html>

<html
    lang="<?php echo e($lang); ?>"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo e(t('page_title')); ?>
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
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
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
                    #f4f9fd 0%,
                    #ffffff 45%
                );

            font-family:
                'Sarabun',
                'Segoe UI',
                Tahoma,
                sans-serif;

            color: #333;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .custom-header {

            background:
                linear-gradient(
                    135deg,
                    #4da4d9,
                    #2474aa
                );

            padding:
                12px 0;

            box-shadow:
                0 4px 18px
                rgba(36, 116, 170, 0.20);

            position:
                sticky;

            top: 0;

            z-index: 1000;
        }


        .header-inner {

            min-height:
                55px;
        }


        .sdu-logo {

            width: 48px;
            height: 48px;

            object-fit: contain;

            background: #fff;

            border-radius: 50%;

            padding: 2px;

            box-shadow:
                0 3px 10px
                rgba(0,0,0,.15);
        }


        .main-menu {

            margin: 0;

            padding: 0;

            list-style: none;
        }


        .main-menu .nav-link {

            color: #fff !important;

            font-size: 1.05rem;

            font-weight: 600;

            padding: 8px 12px;

            border-radius: 10px;

            transition: .2s;
        }


        .main-menu .nav-link:hover {

            background:
                rgba(255,255,255,.15);
        }


        .header-right {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .btn-login {

            min-height: 43px;

            padding: 0 18px;

            border-radius: 25px;

            border:
                2px solid rgba(255,255,255,.9);

            color: #fff;

            text-decoration: none;

            display: flex;

            align-items: center;

            gap: 7px;

            font-weight: 600;

            transition: .2s;
        }


        .btn-login:hover {

            background: #fff;

            color: #287cab;
        }


        .profile-image {

            width: 45px;
            height: 45px;

            border-radius: 50%;

            object-fit: cover;

            border: 2px solid #fff;

            background: #fff;

            transition: .2s;
        }


        .profile-image:hover {

            transform:
                scale(1.05);
        }


        .btn-upload-project {

            width: 44px;
            height: 44px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #fff;

            border:
                2px solid rgba(255,255,255,.9);

            background:
                rgba(255,255,255,.15);

            text-decoration: none;

            font-size: 1.3rem;

            transition: .2s;
        }


        .btn-upload-project:hover {

            background: #fff;

            color: #287cab;

            transform:
                scale(1.05);
        }


        /* =====================================================
           LANGUAGE
        ===================================================== */

        .language-button {

            min-height: 38px;

            padding: 0 12px;

            border-radius: 20px;

            border:
                1px solid rgba(255,255,255,.85);

            background:
                rgba(255,255,255,.12);

            color: #fff;

            font-weight: 600;

            cursor: pointer;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            transition: .2s;
        }


        .language-button:hover {

            background: #fff;

            color: #287cab;
        }


        /* รูปธง */

        .language-flag {

            width: 24px;

            height: 16px;

            object-fit: cover;

            display: inline-block;

            border-radius: 2px;

            box-shadow:
                0 1px 3px
                rgba(0,0,0,.18);
        }


        .language-button .language-flag {

            width: 25px;

            height: 17px;
        }


        .language-menu {

            min-width: 165px;

            border-radius: 12px;

            border: 1px solid #e0ebf1;

            box-shadow:
                0 10px 25px
                rgba(0,0,0,.15);

            padding: 6px;
        }


        .language-menu .dropdown-item {

            border-radius: 8px;

            padding: 10px 12px;

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .language-menu .dropdown-item:hover {

            background: #eef8fd;

            color: #287cab;
        }


        .language-name {

            flex: 1;

            font-weight: 500;
        }


        /* =====================================================
           PROFILE MENU
        ===================================================== */

        .custom-profile-menu {

            background: #173f5f;

            border: 1px solid #285776;

            border-radius: 14px;

            box-shadow:
                0 12px 30px
                rgba(0,0,0,.25);

            min-width: 230px;

            padding: 8px;
        }


        .custom-profile-menu .dropdown-item {

            color: #d4ebf7;

            padding: 11px 14px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .custom-profile-menu
        .dropdown-item:hover {

            background: #204b6d;

            color: #fff;
        }


        .custom-profile-menu
        .logout-btn {

            color: #ff8499;
        }


        .custom-profile-menu
        .dropdown-divider {

            border-color: #285776;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {

            margin-top: 28px;

            padding: 45px 35px;

            border-radius: 24px;

            background:
                linear-gradient(
                    135deg,
                    #eaf7ff,
                    #ffffff
                );

            border:
                1px solid #d7edf8;

            box-shadow:
                0 10px 35px
                rgba(45, 137, 185, .08);

            position: relative;

            overflow: hidden;
        }


        .hero::after {

            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            border-radius: 50%;

            background:
                rgba(77,164,217,.10);

            right: -80px;

            top: -100px;
        }


        .hero-icon {

            width: 65px;
            height: 65px;

            border-radius: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #4da4d9,
                    #287cab
                );

            color: #fff;

            font-size: 1.9rem;

            margin-bottom: 18px;

            box-shadow:
                0 8px 20px
                rgba(45, 137, 185, .25);
        }


        .hero h1 {

            font-size:
                clamp(
                    1.8rem,
                    4vw,
                    2.6rem
                );

            font-weight: 700;

            color: #245d80;

            margin-bottom: 10px;
        }


        .hero p {

            color: #607d8b;

            font-size: 1.05rem;

            line-height: 1.8;

            max-width: 850px;

            margin: 0;
        }


        /* =====================================================
           ABOUT
        ===================================================== */

        .about-section {

            margin-top: 25px;

            padding: 28px;

            background: #fff;

            border-radius: 20px;

            border:
                1px solid #e2edf3;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.05);
        }


        .section-title {

            color: #285f7f;

            font-size: 1.35rem;

            font-weight: 700;

            margin-bottom: 10px;
        }


        .section-description {

            color: #6d7d86;

            line-height: 1.8;

            margin-bottom: 25px;
        }


        .feature-card {

            height: 100%;

            padding: 20px;

            border-radius: 16px;

            background:
                #f8fcff;

            border:
                1px solid #e1f0f7;

            transition: .25s;
        }


        .feature-card:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 10px 25px
                rgba(45,137,185,.10);
        }


        .feature-icon {

            width: 48px;
            height: 48px;

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e4f5fd;

            color: #318bb8;

            font-size: 1.3rem;

            margin-bottom: 12px;
        }


        .feature-card h5 {

            font-size: 1.05rem;

            font-weight: 700;

            color: #315c73;

            margin-bottom: 7px;
        }


        .feature-card p {

            font-size: .9rem;

            line-height: 1.7;

            color: #71818a;

            margin: 0;
        }


        /* =====================================================
           GUEST
        ===================================================== */

        .guest-notice {

            margin-top: 20px;

            padding: 12px 16px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #eef9ff,
                    #f7fcff
                );

            border:
                1px solid #d7edf7;

            color: #53768a;

            font-size: .92rem;
        }


        /* =====================================================
           SEARCH
        ===================================================== */

        .filter-section {

            margin-top: 25px;

            padding: 22px;

            background: #fff;

            border-radius: 18px;

            border:
                1px solid #e1ebf1;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.045);
        }


        .filter-title {

            font-weight: 700;

            color: #315f78;

            margin-bottom: 15px;

            font-size: 1.1rem;
        }


        .form-label {

            color: #566d7b;

            font-weight: 600;

            font-size: .9rem;
        }


        .form-control,
        .form-select {

            border-radius: 10px;

            border-color: #d7e4eb;

            min-height: 40px;
        }


        .form-control:focus,
        .form-select:focus {

            border-color: #5ab1d8;

            box-shadow:
                0 0 0 .2rem
                rgba(90,177,216,.15);
        }


        .btn-search {

            min-height: 40px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #4da4d9,
                    #287cab
                );

            font-weight: 600;

            transition: .2s;
        }


        .btn-search:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 5px 15px
                rgba(40,124,171,.22);
        }


        /* =====================================================
           RESULT COUNT
        ===================================================== */

        .result-count {

            margin-top: 28px;

            margin-bottom: 18px;

            color: #71818a;
        }


        .result-count strong {

            color: #287cab;
        }


        /* =====================================================
           PROJECT CARD
        ===================================================== */

        .project-item {

            position: relative;

            padding: 23px 25px;

            margin-bottom: 18px;

            background: #fff;

            border:
                1px solid #e3edf2;

            border-radius: 17px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.035);

            transition: .25s;
        }


        .project-item:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 10px 30px
                rgba(45,137,185,.10);

            border-color:
                #cfe7f3;
        }


        .project-title {

            color: #287cab;

            font-size: 1.2rem;

            font-weight: 700;

            text-decoration: none;

            line-height: 1.5;
        }


        .project-title:hover {

            color: #155a82;
        }


        .badge {

            border-radius: 7px;

            font-weight: 500;

            padding: 6px 9px;
        }


        .info-text {

            font-size: .92rem;

            color: #71818a;

            margin-bottom: 5px;

            line-height: 1.7;
        }


        .info-label {

            color: #4c626e;

            font-weight: 700;
        }


        .author-list {

            margin-top: 3px;

            margin-left: 5px;

            padding-left: 15px;

            color: #71818a;

            font-size: .9rem;

            line-height: 1.8;
        }


        .description-text {

            font-size: .92rem;

            color: #687a84;

            line-height: 1.7;

            margin-top: 10px;

            margin-bottom: 8px;

            max-width: 950px;
        }


        .description-label {

            color: #4c626e;

            font-weight: 700;
        }


        .page-text {

            font-size: .85rem;

            color: #87969d;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .project-actions {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: wrap;

            margin-top: 12px;
        }


        .btn-pdf {

            background:
                linear-gradient(
                    135deg,
                    #5ab1d8,
                    #4297cd
                );

            border: none;

            color: #fff;

            border-radius: 8px;

            padding: 7px 15px;

            font-size: .9rem;

            text-decoration: none;

            transition: .2s;
        }


        .btn-pdf:hover {

            color: #fff;

            transform:
                translateY(-1px);

            box-shadow:
                0 5px 12px
                rgba(66,151,205,.25);
        }


        .btn-github {

            background: #24292f;

            color: #fff;

            border-radius: 8px;

            padding: 7px 14px;

            font-size: .9rem;

            text-decoration: none;

            transition: .2s;
        }


        .btn-github:hover {

            background: #000;

            color: #fff;
        }


        /* =====================================================
           NO PROJECT
        ===================================================== */

        .no-project {

            text-align: center;

            padding: 70px 20px;

            background: #fff;

            border-radius: 18px;

            border:
                1px solid #e3edf2;

            color: #89979e;
        }


        .no-project i {

            font-size: 55px;

            color: #b8dcea;

            display: block;

            margin-bottom: 12px;
        }


        .no-project h5 {

            color: #536d7b;

            font-weight: 700;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .site-footer {

            margin-top: 60px;

            padding: 35px 0 25px;

            background:
                linear-gradient(
                    180deg,
                    #edf8fd 0%,
                    #e5f3fa 100%
                );

            border-top:
                1px solid #d5eaf4;

            color: #78909c;

            text-align: center;

            font-size: .9rem;
        }


        .footer-title {

            color: #287cab;

            font-size: 1rem;

            font-weight: 700;
        }


        .footer-description {

            margin-top: 5px;

            color: #78909c;

            font-size: .88rem;
        }


        /* =====================================================
           QR CODE FEEDBACK CARD
        ===================================================== */

        .qr-feedback-card {

            width: 100%;

            max-width: 520px;

            margin: 24px auto 0;

            padding: 20px;

            background:
                rgba(255,255,255,.96);

            border:
                1px solid #d5e9f2;

            border-radius: 20px;

            box-shadow:
                0 10px 30px
                rgba(36, 116, 170, .10);

            text-align: left;

            transition:
                .25s ease;
        }


        .qr-feedback-card:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 14px 35px
                rgba(36, 116, 170, .15);
        }


        /* =====================================================
           QR TITLE
        ===================================================== */

        .qr-feedback-title {

            display: flex;

            align-items: center;

            gap: 10px;

            color: #245d80;

            font-size: 1.02rem;

            font-weight: 700;

            line-height: 1.5;

            margin-bottom: 16px;
        }


        .qr-feedback-title i {

            width: 40px;

            height: 40px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #e5f5fc,
                    #d7eef8
                );

            color: #287cab;

            font-size: 1.3rem;
        }


        /* =====================================================
           QR CONTENT
        ===================================================== */

        .qr-feedback-content {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 20px;
        }


        /* =====================================================
           QR IMAGE
        ===================================================== */

        .qr-image-box {

            width: 165px;

            height: 165px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 9px;

            background: #fff;

            border:
                1px solid #dcebf2;

            border-radius: 14px;

            box-shadow:
                0 5px 15px
                rgba(0,0,0,.06);
        }


        .qr-feedback-image {

            width: 100%;

            height: 100%;

            object-fit: contain;

            display: block;
        }


        /* =====================================================
           QR TEXT
        ===================================================== */

        .qr-feedback-text {

            display: flex;

            flex-direction: column;

            gap: 7px;

            color: #71818a;

            font-size: .88rem;

            line-height: 1.7;

            flex: 1;
        }


        .qr-feedback-text strong {

            color: #287cab;

            font-size: 1rem;

            font-weight: 700;
        }


        .qr-feedback-text span {

            color: #71818a;
        }


        /* =====================================================
           FOOTER BOTTOM
        ===================================================== */

        .footer-bottom {

            margin-top: 24px;

            padding-top: 15px;

            border-top:
                1px solid #d5e7ef;

            color: #8a9ca5;

            font-size: .82rem;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .hero {

                padding: 30px 22px;

                border-radius: 18px;
            }


            .about-section {

                padding: 22px 17px;
            }


            .filter-section {

                padding: 18px;
            }


            .project-item {

                padding: 18px;
            }


            .main-menu .nav-link {

                font-size: .9rem;

                padding:
                    7px 8px;
            }


            .qr-feedback-card {

                max-width: 400px;
            }

        }


        @media (max-width: 576px) {

            .sdu-logo {

                width: 40px;
                height: 40px;
            }


            .header-inner {

                min-height: 45px;
            }


            .btn-login {

                min-height: 39px;

                padding:
                    0 12px;

                font-size: .85rem;
            }


            .btn-upload-project {

                width: 39px;
                height: 39px;
            }


            .profile-image {

                width: 39px;
                height: 39px;
            }


            .language-button {

                min-height: 36px;

                padding:
                    0 9px;

                gap: 5px;
            }


            .language-button .language-flag {

                width: 23px;

                height: 15px;
            }


            .hero h1 {

                font-size: 1.7rem;
            }


            .hero p {

                font-size: .95rem;
            }


            .site-footer {

                margin-top: 45px;

                padding:
                    30px 15px 20px;
            }


            .qr-feedback-card {

                max-width: 340px;

                padding: 16px;

                border-radius: 17px;
            }


            .qr-feedback-title {

                font-size: .92rem;

                margin-bottom: 13px;
            }


            .qr-feedback-content {

                flex-direction: column;

                gap: 14px;

                text-align: center;
            }


            .qr-image-box {

                width: 155px;

                height: 155px;
            }


            .qr-feedback-text {

                align-items: center;

                font-size: .82rem;
            }


            .qr-feedback-text strong {

                font-size: .92rem;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="custom-header">

    <div class="container">

        <div
            class="
                header-inner
                d-flex
                justify-content-between
                align-items-center
            "
        >


            <!-- LEFT -->

            <div
                class="
                    d-flex
                    align-items-center
                    gap-2
                "
            >

                <a
                    href="index2.php?lang=<?php echo urlencode($lang); ?>"
                >

                    <img
                        src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                        alt="SDU Logo"
                        class="sdu-logo"
                    >

                </a>


                <ul class="main-menu">

                    <li>

                        <a
                            class="nav-link"
                            href="index2.php?lang=<?php echo urlencode($lang); ?>"
                        >

                            <i class="bi bi-house-fill"></i>

                            <?php echo t('home'); ?>

                        </a>

                    </li>

                </ul>


                <?php if (
                    $logged_in &&
                    $user_role === 'admin'
                ): ?>

                    <ul class="main-menu">

                        <li>

                            <a
                                class="nav-link"
                                href="admin.php"
                            >

                                <i class="bi bi-shield-lock-fill"></i>

                                <?php echo t('admin'); ?>

                            </a>

                        </li>

                    </ul>

                <?php endif; ?>

            </div>


            <!-- RIGHT -->

            <div class="header-right">


                <!-- LANGUAGE -->

                <div class="dropdown">

                    <button
                        type="button"
                        class="language-button dropdown-toggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <?php if ($lang === 'th'): ?>

                            <img
                                src="images/thailand-flag.png"
                                alt="ไทย"
                                class="language-flag"
                            >

                            <span>TH</span>

                        <?php else: ?>

                            <img
                                src="images/england-flag.png"
                                alt="English"
                                class="language-flag"
                            >

                            <span>EN</span>

                        <?php endif; ?>

                    </button>


                    <ul
                        class="
                            dropdown-menu
                            dropdown-menu-end
                            language-menu
                            mt-2
                        "
                    >

                        <li>

                            <a
                                class="dropdown-item"
                                href="<?php echo e(language_url('th')); ?>"
                            >

                                <img
                                    src="images/thailand-flag.png"
                                    alt="ไทย"
                                    class="language-flag"
                                >

                                <span class="language-name">
                                    ไทย
                                </span>

                                <?php if ($lang === 'th'): ?>

                                    <i
                                        class="bi bi-check-lg text-primary"
                                    ></i>

                                <?php endif; ?>

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="<?php echo e(language_url('en')); ?>"
                            >

                                <img
                                    src="images/england-flag.png"
                                    alt="English"
                                    class="language-flag"
                                >

                                <span class="language-name">
                                    English
                                </span>

                                <?php if ($lang === 'en'): ?>

                                    <i
                                        class="bi bi-check-lg text-primary"
                                    ></i>

                                <?php endif; ?>

                            </a>

                        </li>

                    </ul>

                </div>


                <?php if ($logged_in): ?>


                    <!-- UPLOAD -->

                    <a
                        href="create.php"
                        class="btn-upload-project"
                        title="<?php echo e(t('upload_project')); ?>"
                    >

                        <i class="bi bi-plus-lg"></i>

                    </a>


                    <!-- PROFILE -->

                    <div class="dropdown">

                        <a
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <img
                                src="<?php echo e($profile_image_url); ?>"
                                alt="<?php echo e(t('profile')); ?>"
                                class="profile-image"
                            >

                        </a>


                        <ul
                            class="
                                dropdown-menu
                                dropdown-menu-end
                                custom-profile-menu
                                mt-2
                            "
                        >

                            <li>

                                <a
                                    class="dropdown-item"
                                    href="profile.php"
                                >

                                    <i class="bi bi-person-fill"></i>

                                    <?php echo t('profile'); ?>

                                </a>

                            </li>


                            <?php if (
                                $user_role === 'admin'
                            ): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="admin.php"
                                    >

                                        <i class="bi bi-shield-lock-fill"></i>

                                        <?php echo t('manage_system'); ?>

                                    </a>

                                </li>

                            <?php endif; ?>


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
                                        class="bi bi-box-arrow-right"
                                    ></i>

                                    <?php echo t('logout'); ?>

                                </a>

                            </li>

                        </ul>

                    </div>


                <?php else: ?>


                    <!-- LOGIN -->

                    <a
                        href="login.php"
                        class="btn-login"
                    >

                        <i
                            class="bi bi-box-arrow-in-right"
                        ></i>

                        <?php echo t('login'); ?>

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container pb-5">


    <!-- HERO -->

    <section class="hero">

        <div class="hero-icon">

            <i class="bi bi-folder2-open"></i>

        </div>


        <h1>

            <?php echo t('project_archive'); ?>

        </h1>


        <p>

            <?php echo t('hero_description'); ?>

        </p>

    </section>


    <!-- ABOUT -->

    <section class="about-section">

        <div class="section-title">

            <i
                class="bi bi-info-circle-fill me-2"
            ></i>

            <?php echo t('about_title'); ?>

        </div>


        <p class="section-description">

            <?php echo t('about_description'); ?>

        </p>


        <div class="row g-3">


            <!-- SEARCH -->

            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-search"></i>

                    </div>


                    <h5>

                        <?php echo t('search_project'); ?>

                    </h5>


                    <p>

                        <?php

                        if (
                            $lang === 'th'
                        ) {

                            echo
                                'ค้นหาโปรเจกต์จากชื่อผลงาน
                                ระดับการศึกษา และสาขาวิชา
                                เพื่อค้นหาข้อมูลที่ต้องการได้รวดเร็ว';

                        } else {

                            echo
                                'Search projects by title,
                                education level, and department
                                to quickly find the information you need.';

                        }

                        ?>

                    </p>

                </div>

            </div>


            <!-- STORE -->

            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i
                            class="bi bi-archive-fill"
                        ></i>

                    </div>


                    <h5>

                        <?php

                        echo $lang === 'th'
                            ? 'จัดเก็บผลงาน'
                            : 'Store Projects';

                        ?>

                    </h5>


                    <p>

                        <?php

                        if (
                            $lang === 'th'
                        ) {

                            echo
                                'รวบรวมโปรเจกต์ของนักศึกษาไว้ในระบบเดียว
                                พร้อมข้อมูลผู้จัดทำ อาจารย์ที่ปรึกษา
                                และเอกสารประกอบโครงงาน';

                        } else {

                            echo
                                'Collect student projects in one system
                                with author information, advisors,
                                and supporting project documents.';

                        }

                        ?>

                    </p>

                </div>

            </div>


            <!-- DOCUMENT -->

            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i
                            class="bi bi-file-earmark-pdf-fill"
                        ></i>

                    </div>


                    <h5>

                        <?php

                        echo $lang === 'th'
                            ? 'เข้าถึงเอกสาร'
                            : 'Access Documents';

                        ?>

                    </h5>


                    <p>

                        <?php

                        if (
                            $lang === 'th'
                        ) {

                            echo
                                'ผู้ใช้งานสามารถดูข้อมูลโปรเจกต์
                                และเปิดดูเอกสาร PDF
                                หรือ GitHub ของโครงงานที่เผยแพร่ไว้ได้';

                        } else {

                            echo
                                'Users can view project information
                                and access published PDF documents
                                or GitHub repositories.';

                        }

                        ?>

                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- GUEST -->

    <?php if (!$logged_in): ?>

        <div class="guest-notice">

            <i
                class="bi bi-eye-fill me-1"
            ></i>

            <?php echo t('guest'); ?>

        </div>

    <?php endif; ?>


    <!-- SEARCH -->

    <section class="filter-section">

        <div class="filter-title">

            <i
                class="bi bi-funnel-fill me-2"
            ></i>

            <?php echo t('search_filter'); ?>

        </div>


        <form
            action="index2.php"
            method="GET"
            class="row g-3 align-items-end"
        >

            <input
                type="hidden"
                name="lang"
                value="<?php echo e($lang); ?>"
            >


            <!-- KEYWORD -->

            <div class="col-md-4">

                <label
                    for="searchKeyword"
                    class="form-label"
                >

                    <?php echo t('search_project'); ?>

                </label>


                <input
                    type="text"
                    name="keyword"
                    id="searchKeyword"
                    class="form-control"
                    placeholder="<?php echo e(t('search_placeholder')); ?>"
                    value="<?php echo e($keyword); ?>"
                >

            </div>


            <!-- DEGREE -->

            <div class="col-md-3">

                <label
                    for="degreeSelect"
                    class="form-label"
                >

                    <?php echo t('degree'); ?>

                </label>


                <select
                    name="degree"
                    id="degreeSelect"
                    class="form-select"
                >

                    <option
                        value="all"
                        <?php echo
                            $degree === 'all'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('all_degree'); ?>

                    </option>


                    <option
                        value="bachelor"
                        <?php echo
                            $degree === 'bachelor'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('bachelor'); ?>

                    </option>


                    <option
                        value="master"
                        <?php echo
                            $degree === 'master'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('master'); ?>

                    </option>


                    <option
                        value="doctorate"
                        <?php echo
                            $degree === 'doctorate'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('doctorate'); ?>

                    </option>

                </select>

            </div>


            <!-- MAJOR -->

            <div class="col-md-3">

                <label
                    for="majorSelect"
                    class="form-label"
                >

                    <?php echo t('major'); ?>

                </label>


                <select
                    name="major"
                    id="majorSelect"
                    class="form-select"
                >

                    <option
                        value="all"
                        <?php echo
                            $major === 'all'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('all_major'); ?>

                    </option>


                    <option
                        value="it"
                        <?php echo
                            $major === 'it'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('it'); ?>

                    </option>


                    <option
                        value="cs"
                        <?php echo
                            $major === 'cs'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('cs'); ?>

                    </option>


                    <option
                        value="env"
                        <?php echo
                            $major === 'env'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('env'); ?>

                    </option>


                    <option
                        value="food"
                        <?php echo
                            $major === 'food'
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php echo t('food'); ?>

                    </option>

                </select>

            </div>


            <!-- BUTTON -->

            <div class="col-md-2">

                <button
                    type="submit"
                    class="
                        btn
                        btn-search
                        text-white
                        w-100
                    "
                >

                    <i
                        class="bi bi-search me-1"
                    ></i>

                    <?php echo t('search'); ?>

                </button>

            </div>

        </form>

    </section>


    <!-- RESULT COUNT -->

    <div class="result-count">

        <small>

            <i
                class="bi bi-collection-fill me-1"
            ></i>

            <?php echo t('found'); ?>

            <strong>

                <?php
                echo mysqli_num_rows(
                    $result
                );
                ?>

            </strong>

            <?php echo t('items'); ?>


            <?php if (
                $keyword !== ''
            ): ?>

                <?php echo t('for_keyword'); ?>

                <strong>

                    "<?php echo e($keyword); ?>"

                </strong>

            <?php endif; ?>

        </small>

    </div>


    <!-- PROJECT LIST -->

    <?php if (
        mysqli_num_rows($result) > 0
    ): ?>


        <?php while (
            $project =
            mysqli_fetch_assoc($result)
        ): ?>


            <article
                class="project-item"
            >


                <!-- TITLE -->

                <div class="mb-2">

                    <a
                        href="project-detail.php?id=<?php echo (int)$project['id']; ?>&lang=<?php echo urlencode($lang); ?>"
                        class="project-title"
                    >

                        <?php

                        $display_title =
                            trim(
                                $project['title']
                                ?? ''
                            );


                        if (
                            $display_title === ''
                        ) {

                            $display_title =
                                trim(
                                    $project['project_name']
                                    ?? ''
                                );
                        }


                        if (
                            $display_title === ''
                        ) {

                            $display_title =
                                t(
                                    'not_specified'
                                );
                        }


                        echo e(
                            $display_title
                        );

                        ?>

                    </a>


                    <?php if (
                        !empty(
                            $project['degree']
                        )
                    ): ?>

                        <span
                            class="
                                badge
                                bg-light
                                text-dark
                                border
                                ms-1
                            "
                        >

                            <?php

                            echo e(
                                $project['degree']
                            );

                            ?>

                        </span>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $project['department']
                        )
                    ): ?>

                        <span
                            class="
                                badge
                                bg-info
                                text-dark
                                ms-1
                            "
                        >

                            <?php

                            echo e(
                                $project['department']
                            );

                            ?>

                        </span>

                    <?php endif; ?>

                </div>


                <!-- OWNER -->

                <?php

                $owner_name =
                    trim(
                        $project['student_name']
                        ?? ''
                    );


                if (
                    $owner_name !== ''
                ) {

                    $owner_lines =
                        preg_split(
                            '/\r\n|\r|\n/',
                            $owner_name
                        );

                    $owner_name =
                        trim(
                            $owner_lines[0]
                            ?? ''
                        );
                }

                ?>


                <?php if (
                    $owner_name !== ''
                ): ?>

                    <div class="info-text">

                        <span class="info-label">

                            <i
                                class="bi bi-person-fill"
                            ></i>

                            <?php echo t('owner'); ?>:

                        </span>

                        <?php

                        echo e(
                            $owner_name
                        );

                        ?>

                    </div>

                <?php endif; ?>


                <!-- MEMBERS -->

                <?php if (
                    $logged_in
                ): ?>


                    <?php

                    $authors_text =
                        trim(
                            $project['authors']
                            ?? ''
                        );

                    $member_list = [];


                    if (
                        $authors_text !== ''
                    ) {

                        $author_list =
                            preg_split(
                                '/\r\n|\r|\n/',
                                $authors_text
                            );


                        foreach (
                            $author_list
                            as $author
                        ) {

                            $author =
                                trim(
                                    $author
                                );


                            if (
                                $author === ''
                            ) {

                                continue;
                            }


                            if (
                                $owner_name !== ''
                                &&
                                $author ===
                                $owner_name
                            ) {

                                continue;
                            }


                            if (
                                !in_array(
                                    $author,
                                    $member_list,
                                    true
                                )
                            ) {

                                $member_list[] =
                                    $author;
                            }

                        }
                    }

                    ?>


                    <?php if (
                        !empty(
                            $member_list
                        )
                    ): ?>

                        <div class="info-text">

                            <span
                                class="info-label"
                            >

                                <i
                                    class="bi bi-people-fill"
                                ></i>

                                <?php echo t('members'); ?>:

                            </span>


                            <div
                                class="author-list"
                            >

                                <?php foreach (
                                    $member_list
                                    as $member
                                ): ?>

                                    <div>

                                        <i
                                            class="bi bi-person"
                                        ></i>

                                        <?php

                                        echo e(
                                            $member
                                        );

                                        ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                <?php endif; ?>


                <!-- ADVISOR -->

                <?php if (
                    !empty(
                        trim(
                            $project['advisor']
                            ?? ''
                        )
                    )
                ): ?>

                    <div class="info-text">

                        <span
                            class="info-label"
                        >

                            <i
                                class="bi bi-person-workspace"
                            ></i>

                            <?php echo t('advisor'); ?>:

                        </span>

                        <?php

                        echo e(
                            trim(
                                $project['advisor']
                            )
                        );

                        ?>

                    </div>

                <?php endif; ?>


                <!-- DATE -->

                <?php if (
                    !empty(
                        $project['created_at']
                    )
                ): ?>

                    <div class="info-text">

                        <span
                            class="info-label"
                        >

                            <i
                                class="bi bi-calendar3"
                            ></i>

                            <?php echo t('date'); ?>:

                        </span>

                        <?php

                        $created_timestamp =
                            strtotime(
                                $project['created_at']
                            );


                        if (
                            $created_timestamp !==
                            false
                        ) {

                            echo e(
                                date(
                                    'd/m/Y',
                                    $created_timestamp
                                )
                            );

                        } else {

                            echo e(
                                $project['created_at']
                            );
                        }

                        ?>

                    </div>

                <?php endif; ?>


                <!-- DESCRIPTION -->

                <?php if (
                    !empty(
                        trim(
                            $project['description']
                            ?? ''
                        )
                    )
                ): ?>

                    <div
                        class="description-text"
                    >

                        <span
                            class="description-label"
                        >

                            <?php
                            echo t(
                                'description'
                            );
                            ?>:

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
                                ) .
                                '...';
                        }


                        echo nl2br(
                            e(
                                $description
                            )
                        );

                        ?>

                    </div>

                <?php endif; ?>


                <!-- PAGES -->

                <?php if (
                    !empty(
                        $project['pages']
                    )
                ): ?>

                    <div class="page-text">

                        <i
                            class="bi bi-file-text"
                        ></i>

                        <?php

                        echo e(
                            $project['pages']
                        );

                        ?>

                    </div>

                <?php endif; ?>


                <!-- ACTIONS -->

                <div
                    class="project-actions"
                >


                    <?php if (
                        !empty(
                            $project['pdf_file']
                        )
                    ): ?>

                        <a
                            href="view-pdf.php?id=<?php echo (int)$project['id']; ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-pdf"
                        >

                            <i
                                class="bi bi-file-earmark-pdf"
                            ></i>

                            <?php echo t('pdf'); ?>

                        </a>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $project['github_url']
                        )
                    ): ?>

                        <a
                            href="<?php echo e($project['github_url']); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-github"
                        >

                            <i
                                class="bi bi-github"
                            ></i>

                            <?php echo t('github'); ?>

                        </a>

                    <?php endif; ?>


                </div>

            </article>


        <?php endwhile; ?>


    <?php else: ?>


        <div class="no-project">

            <i
                class="bi bi-folder-x"
            ></i>


            <h5>

                <?php echo t('no_project'); ?>

            </h5>


            <p>

                <?php
                echo t(
                    'no_project_description'
                );
                ?>

            </p>

        </div>

    <?php endif; ?>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="site-footer">

    <div class="container">


        <!-- ชื่อระบบ -->

        <div class="footer-title">

            <i
                class="bi bi-folder2-open me-1"
            ></i>

            <?php
            echo t(
                'project_archive'
            );
            ?>

        </div>


        <!-- คำอธิบาย -->

        <div class="footer-description">

            <?php
            echo t(
                'footer_description'
            );
            ?>

        </div>


        <!-- =================================================
             GOOGLE FORMS / QR CODE
        ================================================== -->

        <div class="qr-feedback-card">


            <!-- TITLE -->

            <div class="qr-feedback-title">

                <i
                    class="bi bi-chat-square-text-fill"
                ></i>

                <span>

                    <?php
                    echo e(
                        t(
                            'feedback_title'
                        )
                    );
                    ?>

                </span>

            </div>


            <!-- CONTENT -->

            <div class="qr-feedback-content">


                <!-- QR CODE -->

                <div class="qr-image-box">

                    <img
                        src="<?php echo e($feedback_qr_url); ?>"
                        alt="QR Code Google Forms"
                        class="qr-feedback-image"
                    >

                </div>


                <!-- TEXT -->

                <div class="qr-feedback-text">

                    <strong>

                        <?php
                        echo e(
                            t(
                                'scan_qr'
                            )
                        );
                        ?>

                    </strong>


                    <span>

                        <?php
                        echo e(
                            t(
                                'feedback_description'
                            )
                        );
                        ?>

                    </span>

                </div>

            </div>

        </div>


        <!-- COPYRIGHT -->

        <div class="footer-bottom">

            © <?php echo date('Y'); ?>

            Suan Dusit University

        </div>

    </div>

</footer>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>


<?php

mysqli_stmt_close(
    $stmt
);

?>