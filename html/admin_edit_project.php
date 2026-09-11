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
   ตรวจสอบ Admin
========================================================= */

if (($_SESSION['role'] ?? '') !== 'admin') {

    echo "<script>
        alert('หน้านี้สำหรับ Admin เท่านั้น');
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
        window.location.href='admin.php';
    </script>";

    exit;
}


/* =========================================================
   ดึงข้อมูล Project
========================================================= */

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
        pages,
        pdf_file,
        status,
        github_url
    FROM projects
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();

$project = $result->fetch_assoc();

$stmt->close();


if (!$project) {

    echo "<script>
        alert('ไม่พบโปรเจกต์');
        window.location.href='admin.php';
    </script>";

    exit;
}


$error = "";


/* =========================================================
   บันทึกการแก้ไข
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $degree =
        trim($_POST["degree"] ?? "");

    $department =
        trim($_POST["department"] ?? "");

    $authors =
        trim($_POST["authors"] ?? "");

    $advisor =
        trim($_POST["advisor"] ?? "");

    $pages =
        trim($_POST["pages"] ?? "");

    $github_url =
        trim($_POST["github_url"] ?? "");

    $status =
        trim($_POST["status"] ?? "ส่งแล้ว");


    /* =====================================================
       ตรวจสอบ
    ===================================================== */

    if ($title === "") {

        $error = "กรุณากรอกชื่อโปรเจกต์";

    } elseif ($pages !== "" && !ctype_digit($pages)) {

        $error = "จำนวนหน้าต้องเป็นตัวเลข";

    } elseif (
        $github_url !== "" &&
        !filter_var($github_url, FILTER_VALIDATE_URL)
    ) {

        $error = "รูปแบบ GitHub URL ไม่ถูกต้อง";
    }


    /* =====================================================
       ถ้าผ่านการตรวจสอบ
    ===================================================== */

    if ($error === "") {

        /*
         * ตาราง projects มีทั้งชุดข้อมูลใหม่และเก่า
         * จึงเก็บข้อมูลหลักลงทั้งสองชุด
         */

        $project_name =
            $title;

        $project_type =
            $degree;

        $student_name =
            $authors !== ""
            ? $authors
            : ($project['student_name'] ?? "");


        /* pages */

        if ($pages === "") {
            $pages_value = null;
        } else {
            $pages_value = (int)$pages;
        }


        /* =================================================
           UPDATE
        ================================================= */

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
                pages = ?,
                github_url = ?,
                status = ?
            WHERE id = ?
        ");


        $stmt->bind_param(
            "sssssssssiss",
            $project_name,
            $project_type,
            $student_name,
            $title,
            $description,
            $degree,
            $department,
            $authors,
            $advisor,
            $pages_value,
            $github_url,
            $status,
            $project_id
        );


        if ($stmt->execute()) {

            $stmt->close();

            echo "<script>
                alert('แก้ไขโปรเจกต์สำเร็จ');
                window.location.href='admin.php';
            </script>";

            exit;

        } else {

            $error =
                "ไม่สามารถแก้ไขโปรเจกต์ได้: "
                . $stmt->error;

            $stmt->close();
        }


        /* =================================================
           แสดงข้อมูลที่เพิ่งกรอก
        ================================================= */

        $project['title'] =
            $title;

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

        $project['pages'] =
            $pages_value;

        $project['github_url'] =
            $github_url;

        $project['status'] =
            $status;
    }
}

?>


<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>แก้ไขโปรเจกต์ - Admin</title>


<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


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


/* =========================================================
   HEADER
========================================================= */

.header {

    background:
        linear-gradient(
            90deg,
            #4aa4d6,
            #4297cd,
            #3287BB
        );

    color: white;

    padding:
        18px 40px;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

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
}


.header a:hover {

    background:
        rgba(255,255,255,.28);
}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    max-width: 850px;

    margin:
        40px auto;

    padding:
        0 20px;
}


/* =========================================================
   BOX
========================================================= */

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


/* =========================================================
   PROJECT ID
========================================================= */

.project-id {

    background: #eef8fd;

    color: #287cab;

    padding:
        12px 15px;

    border-radius: 8px;

    margin-bottom: 25px;

    font-size: 14px;
}


/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-bottom: 20px;
}


label {

    display: block;

    margin-bottom: 7px;

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
        1px solid #ddd;

    border-radius: 8px;

    font-size: 15px;

    font-family: inherit;

    outline: none;
}


textarea {

    min-height: 150px;

    resize: vertical;
}


input:focus,
textarea:focus,
select:focus {

    border-color: #4297cd;

    box-shadow:
        0 0 0 3px
        rgba(66,151,205,.12);
}


/* =========================================================
   GRID
========================================================= */

.grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 18px;
}


/* =========================================================
   ERROR
========================================================= */

.error {

    background: #ffe6e6;

    color: #b00000;

    padding:
        12px 15px;

    border-radius: 8px;

    margin-bottom: 20px;
}


/* =========================================================
   HELP TEXT
========================================================= */

.help {

    color: #777;

    font-size: 13px;

    margin-top: 6px;
}


/* =========================================================
   BUTTONS
========================================================= */

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
}


/* =========================================================
   PDF
========================================================= */

.current-pdf {

    margin-top: 8px;

    background: #f4f8fb;

    padding:
        10px 12px;

    border-radius: 7px;

    font-size: 13px;
}


.current-pdf a {

    color: #287cab;

    text-decoration: none;

    font-weight: bold;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 650px) {

    .grid {

        grid-template-columns: 1fr;
    }

    .header {

        padding:
            15px 20px;
    }

    .box {

        padding: 20px;
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

        แก้ไขโปรเจกต์

    </h2>


    <a href="admin.php">

        <i class="bi bi-arrow-left"></i>

        กลับ Admin

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

            <i class="bi bi-hash"></i>

            Project ID:

            <strong>
                <?= (int)$project['id'] ?>
            </strong>

        </div>



        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="bi bi-exclamation-circle"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>



        <form method="POST">


            <!-- =================================================
                 TITLE
            ================================================= -->

            <div class="form-group">

                <label>

                    ชื่อโปรเจกต์ *

                </label>

                <input
                    type="text"
                    name="title"
                    value="<?= htmlspecialchars(
                        $project['title']
                        ?: $project['project_name']
                    ) ?>"
                    required>

            </div>



            <!-- =================================================
                 DESCRIPTION
            ================================================= -->

            <div class="form-group">

                <label>

                    รายละเอียดโปรเจกต์

                </label>

                <textarea
                    name="description"
                    placeholder="รายละเอียดของโปรเจกต์"><?= htmlspecialchars(
                        $project['description'] ?? ''
                    ) ?></textarea>

            </div>



            <!-- =================================================
                 DEGREE + DEPARTMENT
            ================================================= -->

            <div class="grid">


                <div class="form-group">

                    <label>

                        ระดับการศึกษา

                    </label>

                    <input
                        type="text"
                        name="degree"
                        value="<?= htmlspecialchars(
                            $project['degree']
                            ?: $project['project_type']
                        ) ?>"
                        placeholder="เช่น ปริญญาตรี">

                </div>


                <div class="form-group">

                    <label>

                        สาขา

                    </label>

                    <input
                        type="text"
                        name="department"
                        value="<?= htmlspecialchars(
                            $project['department'] ?? ''
                        ) ?>"
                        placeholder="เช่น เทคโนโลยีสารสนเทศ">

                </div>


            </div>



            <!-- =================================================
                 AUTHORS
            ================================================= -->

            <div class="form-group">

                <label>

                    ผู้จัดทำ

                </label>

                <input
                    type="text"
                    name="authors"
                    value="<?= htmlspecialchars(
                        $project['authors']
                        ?: $project['student_name']
                    ) ?>"
                    placeholder="เช่น นายสมชาย ใจดี, นางสาวสมหญิง ใจดี">

            </div>



            <!-- =================================================
                 ADVISOR
            ================================================= -->

            <div class="form-group">

                <label>

                    อาจารย์ที่ปรึกษา

                </label>

                <input
                    type="text"
                    name="advisor"
                    value="<?= htmlspecialchars(
                        $project['advisor'] ?? ''
                    ) ?>"
                    placeholder="ชื่ออาจารย์ที่ปรึกษา">

            </div>



            <!-- =================================================
                 PAGES
            ================================================= -->

            <div class="form-group">

                <label>

                    จำนวนหน้า

                </label>

                <input
                    type="number"
                    name="pages"
                    min="1"
                    value="<?= htmlspecialchars(
                        $project['pages'] ?? ''
                    ) ?>"
                    placeholder="เช่น 50">

            </div>



            <!-- =================================================
                 GITHUB
            ================================================= -->

            <div class="form-group">

                <label>

                    GitHub URL

                </label>

                <input
                    type="url"
                    name="github_url"
                    value="<?= htmlspecialchars(
                        $project['github_url'] ?? ''
                    ) ?>"
                    placeholder="https://github.com/username/project">

                <div class="help">

                    ถ้าไม่มี GitHub สามารถเว้นว่างได้

                </div>

            </div>



            <!-- =================================================
                 STATUS
            ================================================= -->

            <div class="form-group">

                <label>

                    สถานะ

                </label>

                <select name="status">

                    <option
                        value="ส่งแล้ว"
                        <?= ($project['status'] === 'ส่งแล้ว')
                            ? 'selected'
                            : '' ?>>

                        ส่งแล้ว

                    </option>


                    <option
                        value="กำลังตรวจสอบ"
                        <?= ($project['status'] === 'กำลังตรวจสอบ')
                            ? 'selected'
                            : '' ?>>

                        กำลังตรวจสอบ

                    </option>


                    <option
                        value="ผ่าน"
                        <?= ($project['status'] === 'ผ่าน')
                            ? 'selected'
                            : '' ?>>

                        ผ่าน

                    </option>


                    <option
                        value="ไม่ผ่าน"
                        <?= ($project['status'] === 'ไม่ผ่าน')
                            ? 'selected'
                            : '' ?>>

                        ไม่ผ่าน

                    </option>

                </select>

            </div>



            <!-- =================================================
                 CURRENT PDF
            ================================================= -->

            <?php if (!empty($project['pdf_file'])): ?>

                <div class="form-group">

                    <label>

                        PDF ปัจจุบัน

                    </label>


                    <div class="current-pdf">

                        <i class="bi bi-file-earmark-pdf"></i>

                        มีไฟล์ PDF อยู่แล้ว

                        <a
                            href="uploads/<?= urlencode(
                                basename($project['pdf_file'])
                            ) ?>"
                            target="_blank">

                            เปิดดู PDF

                        </a>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 BUTTON
            ================================================= -->

            <div class="buttons">


                <a
                    href="admin.php"
                    class="btn btn-cancel">

                    <i class="bi bi-x-circle"></i>

                    ยกเลิก

                </a>


                <button
                    type="submit"
                    class="btn btn-save">

                    <i class="bi bi-check-circle"></i>

                    บันทึกการแก้ไข

                </button>


            </div>


        </form>

    </div>

</div>


</body>

</html>