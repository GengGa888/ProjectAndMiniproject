<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| เปลี่ยนภาษา
|--------------------------------------------------------------------------
*/

if (isset($_GET['lang'])) {

    $new_lang = $_GET['lang'];

    if ($new_lang === 'th' || $new_lang === 'en') {
        $_SESSION['lang'] = $new_lang;
    }

    /*
     * กลับไปหน้าก่อนหน้า
     * เพื่อให้กดภาษาแล้วอยู่หน้าเดิม
     */
    $back_url = $_SERVER['HTTP_REFERER'] ?? 'index2.php';

    header("Location: " . $back_url);
    exit;
}


/*
|--------------------------------------------------------------------------
| ภาษาปัจจุบัน
|--------------------------------------------------------------------------
*/

$lang = $_SESSION['lang'] ?? 'th';


/*
|--------------------------------------------------------------------------
| ข้อความระบบ
|--------------------------------------------------------------------------
*/

$text = [

    'th' => [

        'home' => 'หน้าแรก',
        'admin' => 'Admin',
        'login' => 'เข้าสู่ระบบ',
        'logout' => 'ออกจากระบบ',
        'profile' => 'ข้อมูลส่วนตัว',
        'manage_system' => 'จัดการระบบ',

        'submit_project' => 'ส่งโปรเจกต์',

        'search_project' => 'ค้นหาโปรเจกต์:',
        'search_placeholder' => 'พิมพ์ชื่อโปรเจกต์...',

        'education_level' => 'ระดับหลักสูตร:',
        'all_education' => 'ทุกระดับการศึกษา',
        'bachelor' => 'ปริญญาตรี',
        'master' => 'ปริญญาโท',
        'doctorate' => 'ปริญญาเอก',

        'major' => 'สาขาวิชา:',
        'all_major' => 'ทุกสาขาวิชา',
        'it' => 'เทคโนโลยีสารสนเทศ',
        'cs' => 'วิทยาการคอมพิวเตอร์',
        'env' => 'วิทยาศาสตร์สิ่งแวดล้อม',
        'food' => 'เทคโนโลยีการประกอบอาหาร',

        'search' => 'ค้นหา',

        'guest' => 'กำลังเข้าชมในฐานะบุคคลทั่วไป',
        'guest_detail' => 'สามารถค้นหาและดูโปรเจกต์ได้',

        'found_project' => 'พบโปรเจกต์',
        'items' => 'รายการ',
        'for_keyword' => 'สำหรับคำค้นหา',

        'project_owner' => 'เจ้าของโปรเจกต์:',
        'members' => 'สมาชิกกลุ่ม:',
        'advisor' => 'อาจารย์ที่ปรึกษา:',
        'project_date' => 'วันที่ลงโปรเจกต์:',
        'description' => 'คำอธิบาย:',

        'pdf' => 'PDF',
        'github' => 'GitHub',

        'no_project' => 'ไม่พบโปรเจกต์',
        'no_project_detail' => 'ยังไม่มีโปรเจกต์ที่ตรงกับข้อมูลที่ค้นหา',

        'project_detail' => 'รายละเอียดโปรเจกต์',
        'back' => 'ย้อนกลับ',
        'project_information' => 'ข้อมูลโปรเจกต์',

        'pages' => 'จำนวนหน้า',
        'language' => 'ภาษา',
        'thai' => 'ไทย',
        'english' => 'English',

    ],

    'en' => [

        'home' => 'Home',
        'admin' => 'Admin',
        'login' => 'Login',
        'logout' => 'Logout',
        'profile' => 'Profile',
        'manage_system' => 'System Management',

        'submit_project' => 'Submit Project',

        'search_project' => 'Search Project:',
        'search_placeholder' => 'Enter project name...',

        'education_level' => 'Education Level:',
        'all_education' => 'All Education Levels',
        'bachelor' => "Bachelor's Degree",
        'master' => "Master's Degree",
        'doctorate' => 'Doctoral Degree',

        'major' => 'Major:',
        'all_major' => 'All Majors',
        'it' => 'Information Technology',
        'cs' => 'Computer Science',
        'env' => 'Environmental Science',
        'food' => 'Food Technology',

        'search' => 'Search',

        'guest' => 'You are browsing as a guest',
        'guest_detail' => 'You can search and view projects.',

        'found_project' => 'Found',
        'items' => 'projects',
        'for_keyword' => 'for keyword',

        'project_owner' => 'Project Owner:',
        'members' => 'Group Members:',
        'advisor' => 'Advisor:',
        'project_date' => 'Project Date:',
        'description' => 'Description:',

        'pdf' => 'PDF',
        'github' => 'GitHub',

        'no_project' => 'No Projects Found',
        'no_project_detail' => 'No projects match your search criteria.',

        'project_detail' => 'Project Details',
        'back' => 'Back',
        'project_information' => 'Project Information',

        'pages' => 'Pages',
        'language' => 'Language',
        'thai' => 'ไทย',
        'english' => 'English',

    ]

];


/*
|--------------------------------------------------------------------------
| ฟังก์ชันเรียกข้อความ
|--------------------------------------------------------------------------
*/

function t($key)
{
    global $text, $lang;

    return $text[$lang][$key]
        ?? $text['th'][$key]
        ?? $key;
}