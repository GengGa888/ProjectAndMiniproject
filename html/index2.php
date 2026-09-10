<?php
include 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>หน้าแรก - คลังโปรเจกต์ SDU</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: "Sarabun", Arial, sans-serif;
            background: #f5f8fb;
        }

        .header {
            background: linear-gradient(135deg, #4da4d9, #2b7bb3);
            color: white;
            padding: 15px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            text-decoration: none;
        }

        .logo-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            color: #2b7bb3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-image {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }

        .custom-profile-menu {
            min-width: 200px;
            border-radius: 12px;
            padding: 8px;
        }

        .custom-profile-menu .dropdown-item {
            padding: 10px 12px;
            border-radius: 8px;
        }

        .custom-profile-menu .dropdown-item:hover {
            background: #eaf5fc;
        }

        .custom-profile-menu i {
            margin-right: 8px;
        }

        .logout-btn {
            color: #dc3545;
        }

        .search-section {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .search-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .project-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        .project-title {
            color: #2b7bb3;
            font-size: 20px;
            font-weight: bold;
        }

        .add-btn {
            background: white;
            color: #2b7bb3;
            border: none;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            font-size: 22px;
        }

        .add-btn:hover {
            background: #eaf5fc;
        }
    </style>
</head>

<body>

<!-- ================= HEADER ================= -->
<header class="header">

    <!-- กลับหน้าแรก -->
    <a href="index2.php" class="header-left">
        <div class="logo-placeholder">SDU</div>
        <div class="header-title">หน้าแรก</div>
    </a>

    <div class="header-right">

        <!-- ปุ่มเพิ่มโปรเจกต์ -->
        <a href="create.php" class="add-btn d-flex align-items-center justify-content-center">
            <i class="bi bi-plus-lg"></i>
        </a>

        <!-- PROFILE -->
        <div class="dropdown">

            <a href="#" 
               role="button" 
               id="profileDropdown"
               data-bs-toggle="dropdown"
               aria-expanded="false">

                <img
                    src="https://cdn-icons-png.flaticon.com/512/149/149071.png"
                    alt="โปรไฟล์"
                    class="profile-image"
                >

            </a>

            <ul class="dropdown-menu dropdown-menu-end custom-profile-menu mt-2"
                aria-labelledby="profileDropdown">

                <!-- แก้แล้ว -->
                <li>
                    <a class="dropdown-item" href="profile.php">
                        <i class="bi bi-person-fill"></i>
                        <span>ข้อมูลส่วนตัว</span>
                    </a>
                </li>

                <li>
                    <hr class="dropdown-divider">
                </li>

                <li>
                    <a class="dropdown-item logout-btn" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </li>

            </ul>

        </div>

    </div>
</header>


<!-- ================= SEARCH ================= -->
<section class="search-section">

    <div class="search-box">

        <!-- แก้ action ให้กลับมาที่ index2.php -->
        <form action="index2.php" method="GET" class="row g-3 align-items-end">

            <div class="col-md-5">
                <label class="form-label">ค้นหาโปรเจกต์</label>

                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="ชื่อโปรเจกต์ / ผู้จัดทำ"
                    value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                >
            </div>

            <div class="col-md-3">

                <label class="form-label">ระดับการศึกษา</label>

                <select name="degree" class="form-select">

                    <option value="">ทั้งหมด</option>

                    <option value="ปริญญาตรี"
                        <?php echo (($_GET['degree'] ?? '') == 'ปริญญาตรี') ? 'selected' : ''; ?>>
                        ปริญญาตรี
                    </option>

                    <option value="ปริญญาโท"
                        <?php echo (($_GET['degree'] ?? '') == 'ปริญญาโท') ? 'selected' : ''; ?>>
                        ปริญญาโท
                    </option>

                    <option value="ปริญญาเอก"
                        <?php echo (($_GET['degree'] ?? '') == 'ปริญญาเอก') ? 'selected' : ''; ?>>
                        ปริญญาเอก
                    </option>

                </select>

            </div>

            <div class="col-md-3">

                <label class="form-label">สาขา</label>

                <select name="major" class="form-select">

                    <option value="">ทั้งหมด</option>

                    <option value="IT"
                        <?php echo (($_GET['major'] ?? '') == 'IT') ? 'selected' : ''; ?>>
                        เทคโนโลยีสารสนเทศ
                    </option>

                    <option value="วิทยาศาสตร์สิ่งแวดล้อม"
                        <?php echo (($_GET['major'] ?? '') == 'วิทยาศาสตร์สิ่งแวดล้อม') ? 'selected' : ''; ?>>
                        วิทยาศาสตร์สิ่งแวดล้อม
                    </option>

                </select>

            </div>

            <div class="col-md-1">

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>

            </div>

        </form>

    </div>


    <!-- ================= PROJECTS ================= -->

    <div class="mt-4">

        <div class="project-card">

            <div class="project-title">
                การเพิ่มประสิทธิภาพในการตรวจจับไฟป่าโดยใช้ Google’s Teachable Machine
            </div>

            <p class="mt-2 mb-1">
                <strong>ระดับ:</strong> ปริญญาตรี
            </p>

            <p class="mb-1">
                <strong>สาขา:</strong> IT
            </p>

            <p class="mb-0">
                <strong>ผู้จัดทำ:</strong> นักศึกษา
            </p>

        </div>


        <div class="project-card">

            <div class="project-title">
                ศึกษาทางเลือกการผลิตพลังงานทดแทนจากผักตบชวา
            </div>

            <p class="mt-2 mb-1">
                <strong>ระดับ:</strong> ปริญญาโท
            </p>

            <p class="mb-1">
                <strong>สาขา:</strong> วิทยาศาสตร์สิ่งแวดล้อม
            </p>

            <p class="mb-0">
                <strong>ผู้จัดทำ:</strong> นักศึกษา
            </p>

        </div>


        <div class="project-card">

            <div class="project-title">
                การรับรู้ผลกระทบด้านสุขภาพของสมาชิกโครงการธนาคารขยะ
            </div>

            <p class="mt-2 mb-1">
                <strong>ระดับ:</strong> ปริญญาเอก
            </p>

            <p class="mb-1">
                <strong>สาขา:</strong> วิทยาศาสตร์สิ่งแวดล้อม
            </p>

            <p class="mb-0">
                <strong>ผู้จัดทำ:</strong> นักศึกษา
            </p>

        </div>

    </div>

</section>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>