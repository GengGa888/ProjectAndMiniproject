<?php 
include 'db_connect.php'; 

// ตัวอย่างการดึงข้อมูลจากฐานข้อมูล (ปรับชื่อตารางและ column ตามของคุณ)
// $sql = "SELECT * FROM projects ORDER BY id DESC";
// $result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - คลังโปรเจกต์ SDU</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sdu-pdf-blue: #5ab1d8; 
        }
        
        body { 
            background-color: #ffffff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        /* Navbar */
        .custom-header {
            background: linear-gradient(to right, #4da4d9, #2b7bb3); 
            padding: 8px 0 15px 0;
            border-bottom: 2px solid #1a5a8a;
        }

        .profile-image {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            cursor: pointer;
            transition: 0.2s;
        }

        .profile-image:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }

        /* ปุ่มบวกเพิ่มโปรเจกต์ */
        .btn-upload-project {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.4rem;
            transition: all 0.2s ease;
        }

        .btn-upload-project:hover {
            background-color: white;
            color: #2b7bb3;
            transform: scale(1.05);
        }

        .sdu-logo {
            width: 45px;
            height: auto;
            background-color: white;
            border-radius: 50%;
            padding: 2px;
        }

        .main-menu .nav-link {
            color: white !important;
            font-size: 1.05rem;
            padding-left: 0;
            margin-right: 20px;
            font-weight: 500;
        }

        .main-menu .nav-link:hover {
            color: #e2f0fb !important;
            text-decoration: underline;
        }

        /* Modern Dark Dropdown Profile */
        .custom-profile-menu {
            background-color: #1a1b26;
            border: 1px solid #2f334d;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            min-width: 220px;
            padding: 8px;
        }

        .custom-profile-menu .dropdown-item {
            color: #a9b1d6;
            font-size: 0.95rem;
            padding: 10px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .custom-profile-menu .dropdown-item i {
            font-size: 1.1rem;
        }

        .custom-profile-menu .dropdown-item:hover {
            background-color: #24283b;
            color: #ffffff;
        }

        .custom-profile-menu .dropdown-item.logout-btn {
            color: #f7768e;
        }

        .custom-profile-menu .dropdown-item.logout-btn:hover {
            background-color: rgba(247, 118, 142, 0.15);
            color: #ff6c6b;
        }

        .custom-profile-menu .dropdown-divider {
            border-color: #2f334d;
            margin: 6px 0;
        }

        /* Filter Section Style */
        .filter-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px 20px;
        }

        /* Project Items */
        .project-title {
            font-size: 1.1rem;
            color: #2a7cbd;
            text-decoration: none;
            font-weight: 500;
        }

        .project-title:hover {
            text-decoration: underline;
            color: #1c5b8e;
        }

        .author-text {
            font-size: 0.9rem;
            color: #737373;
            margin-bottom: 0.15rem;
        }

        .page-text {
            font-size: 0.85rem;
            color: #737373;
        }

        .btn-pdf {
            background-color: var(--sdu-pdf-blue);
            border: none;
            color: white;
            border-radius: 4px;
            padding: 4px 16px;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-pdf:hover {
            background-color: #459cbf;
            color: white;
        }
    </style>
</head>

<body>

    <!-- แถบเมนูด้านบน -->
    <header class="custom-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-2">
                        <img 
                            src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                            alt="SDU Logo"
                            class="sdu-logo"
                        >
                    </a>
                    <ul class="nav main-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">หน้าแรก</a>
                        </li>
                    </ul>
                </div>

                <!-- ฝั่งขวา: ปุ่มเพิ่มโปรเจกต์ + โปรไฟล์ Dropdown -->
                <div class="d-flex align-items-center gap-3">

                    <!-- ปุ่มเพิ่มโปรเจกต์ (รูปบวก) -->
                    <a href="create.php" class="btn-upload-project" title="ส่งโปรเจกต์">
                        <i class="bi bi-plus-lg"></i>
                    </a>

                    <!-- โปรไฟล์ Dropdown -->
                    <div class="dropdown">
                        <a href="#" role="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img 
                                src="https://cdn-icons-png.flaticon.com/512/149/149071.png"
                                alt="โปรไฟล์"
                                class="profile-image"
                            >
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end custom-profile-menu mt-2" aria-labelledby="profileDropdown">
                            <li>
                                <a class="dropdown-item" href="Personal Information.php">
                                    <i class="bi bi-person-fill"></i>
                                    <span>ข้อมูลส่วนตัว</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item logout-btn" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>ออกจากระบบ</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>

            </div>
        </div>
    </header>

    <!-- เนื้อหาหลัก -->
    <div class="container mt-4 mb-5">
        
        <!-- ส่วนกรองและค้นหาโปรเจกต์ -->
        <div class="filter-section mb-4">
            <form action="index.php" method="GET" class="row g-3 align-items-end">
                
                <!-- ช่องพิมพ์ค้นหา -->
                <div class="col-md-4">
                    <label for="searchKeyword" class="form-label fw-bold text-secondary mb-1">
                        ค้นหาคำขวัญ/โปรเจกต์:
                    </label>
                    <input type="text" name="keyword" class="form-control form-control-sm" id="searchKeyword" placeholder="พิมพ์ชื่อโปรเจกต์ หรือผู้แต่ง...">
                </div>

                <!-- เลือกระดับหลักสูตร -->
                <div class="col-md-3">
                    <label for="degreeSelect" class="form-label fw-bold text-secondary mb-1">
                        ระดับหลักสูตร:
                    </label>
                    <select name="degree" class="form-select form-select-sm" id="degreeSelect">
                        <option value="all" selected>ทุกระดับการศึกษา</option>
                        <option value="bachelor">ปริญญาตรี</option>
                        <option value="master">ปริญญาโท</option>
                        <option value="doctorate">ปริญญาเอก</option>
                    </select>
                </div>

                <!-- เลือกสาขาวิชา -->
                <div class="col-md-3">
                    <label for="majorSelect" class="form-label fw-bold text-secondary mb-1">
                        สาขาวิชา:
                    </label>
                    <select name="major" class="form-select form-select-sm" id="majorSelect">
                        <option value="all" selected>ทุกสาขาวิชา</option>
                        <option value="it">เทคโนโลยีสารสนเทศ</option>
                        <option value="cs">วิทยาการคอมพิวเตอร์</option>
                        <option value="env">วิทยาศาสตร์สิ่งแวดล้อม</option>
                        <option value="food">เทคโนโลยีการประกอบอาหาร</option>
                    </select>
                </div>

                <!-- ปุ่มค้นหา -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">ค้นหา</button>
                </div>

            </form>
        </div>

        <!-- รายการโปรเจกต์ 1 -->
        <div class="project-item border-bottom pb-4 mb-4">
            <a href="project-detail.php?id=1" class="project-title">
                การเพิ่มประสิทธิภาพในการตรวจจับไฟป่าโดยใช้ Google’s Teachable Machine
            </a>
            <span class="badge bg-light text-dark ms-2 border">ปริญญาตรี</span>
            <span class="badge bg-info text-dark ms-1">เทคโนโลยีสารสนเทศ</span>
            
            <p class="author-text mt-2">
                ศุภาพิชญ์ ขวัญอยู่<sup>1</sup>
                สืบสกุล ครุรัตน์<sup>1,*</sup>
            </p>
            <p class="author-text">ศุภาพิชญ์ ขวัญอยู่</p>
            <p class="page-text">1-18</p>
            <a href="uploads/project1.pdf" target="_blank" class="btn btn-pdf mt-1">PDF</a>
        </div>

        <!-- รายการโปรเจกต์ 2 -->
        <div class="project-item border-bottom pb-4 mb-4">
            <a href="project-detail.php?id=2" class="project-title">
                ศึกษาทางเลือกการผลิตพลังงานทดแทนจากผักตบชวา กรณีศึกษา บริเวณลุ่มแม่น้ำท่าจีน
            </a>
            <span class="badge bg-light text-dark ms-2 border">ปริญญาโท</span>
            <span class="badge bg-info text-dark ms-1">วิทยาศาสตร์สิ่งแวดล้อม</span>

            <p class="author-text mt-2">
                นนทนันท์ เกื้อชาติ<sup>1,*</sup>
                และอรทัย ชวาลภาฤทธิ์<sup>2</sup>
            </p>
            <p class="author-text">Nontanan Kuerchart</p>
            <p class="page-text">19-32</p>
            <a href="uploads/project2.pdf" target="_blank" class="btn btn-pdf mt-1">PDF</a>
        </div>

        <!-- รายการโปรเจกต์ 3 -->
        <div class="project-item border-bottom pb-4 mb-4">
            <a href="project-detail.php?id=3" class="project-title">
                การรับรู้ผลกระทบด้านสุขภาพของสมาชิกโครงการธนาคารขยะในพื้นที่ชุมชนสวนอ้อยและมหาวิทยาลัยสวนดุสิต ประเทศไทย
            </a>
            <span class="badge bg-light text-dark ms-2 border">ปริญญาเอก</span>
            <span class="badge bg-info text-dark ms-1">วิทยาศาสตร์สิ่งแวดล้อม</span>

            <p class="author-text mt-2">
                ภูริพจน์ แก้วย่อง<sup>1</sup>
                แทนทัศน เพียกขุนทด<sup>1</sup>
                ทิพย์วรรณ บุณยาภรณ์<sup>3,*</sup>
                ชุติวรรณ บุญอาชาทอง<sup>1</sup>
                และ สายสุดา ปั้นตระกูล<sup>2</sup>
            </p>
            <p class="author-text">ภูริพจน์ แก้วย่อง</p>
            <p class="page-text">33-43</p>
            <a href="uploads/project3.pdf" target="_blank" class="btn btn-pdf mt-1">PDF</a>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>