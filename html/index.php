<?php 
session_start();
include 'db_connect.php'; 

// 1. รับค่าตัวกรองและการค้นหาผ่าน Query String (GET)
$search = isset($_GET['searchKeyword']) ? trim($_GET['searchKeyword']) : '';
$degree = isset($_GET['degreeSelect']) ? $_GET['degreeSelect'] : 'all';
$major  = isset($_GET['majorSelect']) ? $_GET['majorSelect'] : 'all';

// 2. สร้าง SQL Dynamic Query
$sql = "SELECT * FROM projects WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR authors LIKE ? OR abstract LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if ($degree !== 'all' && !empty($degree)) {
    $sql .= " AND degree = ?";
    $params[] = $degree;
    $types .= "s";
}

if ($major !== 'all' && !empty($major)) {
    $sql .= " AND department = ?";
    $params[] = $major;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// 3. ประมวลผลคำสั่ง SQL ร่วมกับ Prepared Statement
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
            --sdu-primary: #2b7bb3;
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

        .top-links {
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        .top-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .top-links a:hover {
            text-decoration: underline;
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

        /* Profile Dropdown */
        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            cursor: pointer;
        }

        .custom-profile-menu {
            background-color: #1a1b26;
            border: 1px solid #2f334d;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            min-width: 200px;
            padding: 8px;
        }

        .custom-profile-menu .dropdown-item {
            color: #a9b1d6;
            font-size: 0.95rem;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-profile-menu .dropdown-item:hover {
            background-color: #24283b;
            color: #ffffff;
        }

        .custom-profile-menu .dropdown-item.logout-btn {
            color: #f7768e;
        }

        /* Filter Box */
        .filter-container {
            background-color: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
        }

        /* Project Cards */
        .project-title { 
            font-size: 1.15rem; 
            color: #2a7cbd; 
            text-decoration: none; 
            font-weight: 600; 
        }
        .project-title:hover { 
            text-decoration: underline; 
            color: #1c5b8e; 
        }
        .author-text { 
            font-size: 0.9rem; 
            color: #6c757d; 
            margin-bottom: 0.25rem; 
        }
        .btn-pdf { 
            background-color: var(--sdu-pdf-blue); 
            border: none; 
            color: white; 
            border-radius: 4px; 
            padding: 5px 16px; 
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
            <div class="d-flex justify-content-end top-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-3"><i class="bi bi-person-circle me-1"></i> ยินดีต้อนรับ</span>
                <?php else: ?>
                    <a href="signup.php">สมัครสมาชิก</a>
                    <a href="login.php">ล็อกอิน</a>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap mt-2">
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php">
                        <img src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png" alt="SDU Logo" class="sdu-logo">
                    </a>
                    
                    <ul class="nav main-menu">
                        <li class="nav-item">
                            <!-- แก้จาก index2.php เป็น index.php แล้ว -->
                            <a class="nav-link" href="index2.php">หน้าแรก</a>
                        </li>
                    </ul>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- โปรไฟล์ Dropdown -->
                <div class="dropdown">
                    <a href="#" role="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="โปรไฟล์" class="profile-image">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end custom-profile-menu mt-2" aria-labelledby="profileDropdown">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person-fill"></i> ข้อมูลส่วนตัว
                            </a>
                        </li>
                        <li><hr class="dropdown-divider border-secondary"></li>
                        <li>
                            <a class="dropdown-item logout-btn" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </header>

    <!-- เนื้อหาหลัก -->
    <div class="container mt-4 mb-5">
        
        <!-- ส่วนกรองและค้นหาข้อมูล -->
        <div class="filter-container mb-4">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                
                <!-- 1. ค้นหาชื่อโปรเจกต์ / ผู้จัดทำ -->
                <div class="col-md-4">
                    <label for="searchKeyword" class="form-label fw-bold text-secondary mb-1">ค้นหาชื่อโปรเจกต์ / ผู้จัดทำ:</label>
                    <input type="text" class="form-control" name="searchKeyword" id="searchKeyword" 
                           placeholder="พิมพ์คำค้นหา..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- 2. เลือกหลักสูตร -->
                <div class="col-md-3">
                    <label for="degreeSelect" class="form-label fw-bold text-secondary mb-1">ระดับหลักสูตร:</label>
                    <select class="form-select" name="degreeSelect" id="degreeSelect">
                        <option value="all" <?php echo ($degree === 'all') ? 'selected' : ''; ?>>ทุกระดับการศึกษา</option>
                        <option value="ปริญญาตรี" <?php echo ($degree === 'ปริญญาตรี') ? 'selected' : ''; ?>>ปริญญาตรี</option>
                        <option value="ปริญญาโท" <?php echo ($degree === 'ปริญญาโท') ? 'selected' : ''; ?>>ปริญญาโท</option>
                        <option value="ปริญญาเอก" <?php echo ($degree === 'ปริญญาเอก') ? 'selected' : ''; ?>>ปริญญาเอก</option>
                    </select>
                </div>

                <!-- 3. เลือกสาขาวิชา -->
                <div class="col-md-3">
                    <label for="majorSelect" class="form-label fw-bold text-secondary mb-1">สาขาวิชา:</label>
                    <select class="form-select" name="majorSelect" id="majorSelect">
                        <option value="all" <?php echo ($major === 'all') ? 'selected' : ''; ?>>ทุกสาขาวิชา</option>
                        <option value="เทคโนโลยีสารสนเทศ" <?php echo ($major === 'เทคโนโลยีสารสนเทศ') ? 'selected' : ''; ?>>เทคโนโลยีสารสนเทศ</option>
                        <option value="วิทยาการคอมพิวเตอร์" <?php echo ($major === 'วิทยาการคอมพิวเตอร์') ? 'selected' : ''; ?>>วิทยาการคอมพิวเตอร์</option>
                        <option value="วิทยาศาสตร์สิ่งแวดล้อม" <?php echo ($major === 'วิทยาศาสตร์สิ่งแวดล้อม') ? 'selected' : ''; ?>>วิทยาศาสตร์สิ่งแวดล้อม</option>
                        <option value="เทคโนโลยีการประกอบอาหาร" <?php echo ($major === 'เทคโนโลยีการประกอบอาหาร') ? 'selected' : ''; ?>>เทคโนโลยีการประกอบอาหาร</option>
                    </select>
                </div>

                <!-- ปุ่มส่งฟอร์ม -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i> กรองข้อมูล</button>
                </div>

            </form>
        </div>

        <!-- รายการโปรเจกต์จากฐานข้อมูล -->
        <div class="project-list">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="project-item border-bottom pb-4 mb-4">
                        <a href="project-detail.php?id=<?php echo $row['id']; ?>" class="project-title">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                        
                        <?php if (!empty($row['degree'])): ?>
                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($row['degree']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($row['department'])): ?>
                            <span class="badge bg-info text-dark ms-1"><?php echo htmlspecialchars($row['department']); ?></span>
                        <?php endif; ?>

                        <p class="author-text mt-2 mb-1">
                            <strong>ผู้จัดทำ:</strong> <?php echo htmlspecialchars($row['authors']); ?>
                        </p>
                        
                        <?php if (!empty($row['advisor_name'])): ?>
                            <p class="author-text mb-1">
                                <strong>อาจารย์ที่ปรึกษา:</strong> <?php echo htmlspecialchars($row['advisor_name']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($row['abstract'])): ?>
                            <p class="text-muted small mb-2 text-truncate" style="max-width: 900px;">
                                <?php echo htmlspecialchars($row['abstract']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($row['pdf_file'])): ?>
                            <a href="uploads/<?php echo htmlspecialchars($row['pdf_file']); ?>" target="_blank" class="btn btn-pdf mt-2">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> ดาวน์โหลด PDF
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($row['github_url'])): ?>
                            <a href="<?php echo htmlspecialchars($row['github_url']); ?>" target="_blank" class="btn btn-outline-dark btn-sm mt-2 ms-2">
                                <i class="bi bi-github me-1"></i> GitHub
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted display-4"></i>
                    <p class="text-muted mt-3 fs-5">ไม่พบข้อมูลโปรเจกต์ที่ตรงตามเงื่อนไขการค้นหา</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>