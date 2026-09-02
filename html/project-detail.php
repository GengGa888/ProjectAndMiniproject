<?php 
include 'db_connect.php'; 

// 1. ตรวจสอบการรับค่า ID จาก URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: index.php");
    exit();
}

// 2. ดึงข้อมูลโปรเจกต์จากฐานข้อมูล
$sql = "SELECT * FROM projects WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $title = $row['title'] ?? 'ไม่พบชื่อโปรเจกต์';
    $description = $row['description'] ?? 'ไม่มีคำอธิบาย';
    $keywords = !empty($row['keywords']) ? explode(',', $row['keywords']) : [];
    $authors = $row['authors'] ?? 'ไม่ระบุผู้แต่ง';
    $advisor = $row['advisor'] ?? 'ไม่ระบุ';
    $created_at = !empty($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : '-';
    $academic_year = $row['academic_year'] ?? '-';
    $github_url = $row['github_url'] ?? '#';
    $pdf_file = $row['pdf_file'] ?? '';
    $cover_image = !empty($row['cover_image']) ? 'uploads/' . $row['cover_image'] : 'https://ph01.tci-thaijo.org/public/journals/706/cover_issue_17385_th_TH.png';
    $category_name = $row['category_name'] ?? 'เทคโนโลยีสารสนเทศ';
} else {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ไม่พบข้อมูลโปรเจกต์ที่ต้องการ</div></div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - คลังโปรเจกต์ SDU</title>
    <!-- เรียกใช้งาน Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sdu-pdf-blue: #5ab1d8; 
            --sdu-text-dark: #333333;
        }
        
        body { 
            background-color: #ffffff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: var(--sdu-text-dark);
        }
        
        /* แถบเมนูด้านบน */
        .custom-header {
            background: linear-gradient(to right, #4da4d9, #2b7bb3); 
            padding: 8px 0 15px 0;
            border-bottom: 2px solid #1a5a8a;
        }
        .top-links { font-size: 0.9rem; margin-bottom: 8px; }
        .top-links a { color: white; text-decoration: none; margin-left: 20px; }
        .top-links a:hover { text-decoration: underline; }
        .sdu-logo { width: 45px; height: auto; background-color: white; border-radius: 50%; padding: 2px; }
        .main-menu .nav-link { color: white !important; font-size: 1.05rem; padding-left: 0; margin-right: 20px; font-weight: 500; }
        .main-menu .nav-link:hover { color: #e2f0fb !important; text-decoration: underline; }
        
        .search-box { display: flex; gap: 5px; }
        .search-box input { border-radius: 2px; border: none; padding: 5px 10px; width: 250px; }
        .search-box button { border-radius: 2px; background-color: white; color: #333; border: none; padding: 5px 15px; font-weight: 500; }

        /* ตกแต่งเนื้อหาหน้า Detail */
        .breadcrumb-text { font-size: 0.9rem; color: #6c757d; margin-bottom: 20px; }
        .breadcrumb-text a { color: var(--sdu-pdf-blue); text-decoration: none; }
        .breadcrumb-text a:hover { text-decoration: underline; }

        .detail-title { color: #2a7cbd; font-size: 1.8rem; font-weight: 500; line-height: 1.4; margin-bottom: 15px; }
        
        /* ฝั่งซ้าย (Sidebar) */
        .cover-image { width: 100%; border: 1px solid #e0e0e0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 15px; }
        .btn-pdf-large { background-color: var(--sdu-pdf-blue); color: white; font-weight: bold; border: none; border-radius: 4px; padding: 10px; width: 100%; text-decoration: none; display: inline-block; text-align: center; }
        .btn-pdf-large:hover { background-color: #459cbf; color: white; }
        
        .sidebar-meta { border-top: 1px solid #eeeeee; padding-top: 12px; margin-top: 12px; font-size: 0.95rem; }
        .sidebar-meta-title { font-weight: 600; color: #555; margin-bottom: 5px; }
        
        /* ฝั่งขวา (เนื้อหา) */
        .section-heading { font-size: 1.5rem; font-weight: bold; color: #333; margin-top: 30px; margin-bottom: 15px; }
        .description-text { line-height: 1.8; color: #444; text-align: justify; white-space: pre-line; }
        
        /* Keyword Badges */
        .keyword-badge { font-weight: normal; font-size: 0.85rem; background-color: #f8f9fa; color: #555; border: 1px solid #dee2e6; }
    </style>
</head>
<body>

    <!-- แถบเมนูด้านบน -->
    <header class="custom-header">
        <div class="container">
            <div class="d-flex justify-content-end top-links">
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-2">
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php">
                        <img src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png" alt="SDU Logo" class="sdu-logo">
                    </a>
                    <ul class="nav main-menu">
                        <li class="nav-item"><a class="nav-link" href="index.php">หน้าแรก</a></li>
                    </ul>
                </div>
                <form class="search-box" action="search.php" method="GET">
                    <input type="text" name="query" placeholder="ค้นหาโปรเจกต์...">
                    <button type="submit">ค้นหา</button>
                </form>
            </div>
        </div>
    </header>

    <!-- ส่วนเนื้อหาหลัก -->
    <div class="container mt-4 mb-5">
        
        <div class="breadcrumb-text">
            <a href="index.php">หน้าแรก</a> / <a href="#"><?php echo htmlspecialchars($category_name); ?></a> / รายละเอียดโปรเจกต์
        </div>

        <div class="row">
            
            <!-- คอลัมน์ซ้าย (Sidebar) -->
            <div class="col-md-3 mb-4">
                <img src="<?php echo htmlspecialchars($cover_image); ?>" alt="รูปปกโปรเจกต์" class="cover-image">
                
                <?php if (!empty($pdf_file)): ?>
                    <a href="uploads/<?php echo htmlspecialchars($pdf_file); ?>" target="_blank" class="btn btn-pdf-large mb-3">ดาวน์โหลด PDF</a>
                <?php else: ?>
                    <button class="btn btn-secondary w-100 mb-3" disabled>ไม่มีไฟล์ PDF</button>
                <?php endif; ?>

                <div class="sidebar-meta border-top-0">
                    <p class="sidebar-meta-title">เผยแพร่เมื่อ:</p>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($created_at); ?></p>
                </div>
                
                <div class="sidebar-meta">
                    <p class="sidebar-meta-title">ปีการศึกษา:</p>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($academic_year); ?></p>
                </div>

                <div class="sidebar-meta">
                    <p class="sidebar-meta-title">อาจารย์ที่ปรึกษา:</p>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($advisor); ?></p>
                </div>

                <div class="sidebar-meta">
                    <p class="sidebar-meta-title">สมาชิกกลุ่ม:</p>
                    <p class="text-muted mb-0" style="line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($authors)); ?>
                    </p>
                </div>

                <?php if (!empty($github_url) && $github_url !== '#'): ?>
                <div class="sidebar-meta">
                    <p class="sidebar-meta-title">GitHub Repository:</p>
                    <a href="<?php echo htmlspecialchars($github_url); ?>" target="_blank" class="text-break" style="color: var(--sdu-pdf-blue); font-size: 0.9rem;">
                        <?php echo htmlspecialchars($github_url); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- คอลัมน์ขวา (เนื้อหาหลัก) -->
            <div class="col-md-9 px-md-4">
                
                <h1 class="detail-title"><?php echo htmlspecialchars($title); ?></h1>
                
                <!-- แสดงคำสำคัญ (Keywords) -->
                <?php if (!empty($keywords)): ?>
                <div class="mb-4 d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-bold" style="color: #666; font-size: 0.95rem;">คำสำคัญ:</span>
                    <?php foreach ($keywords as $kw): ?>
                        <span class="badge keyword-badge"><?php echo htmlspecialchars(trim($kw)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <p class="fs-5 text-muted mb-1"><?php echo htmlspecialchars($authors); ?></p>
                <p class="text-muted mb-4">มหาวิทยาลัยสวนดุสิต</p>

                <h3 class="section-heading">คำอธิบาย / บทคัดย่อ</h3>
                <div class="description-text">
                    <?php echo htmlspecialchars($description); ?>
                </div>
            </div>

        </div>
    </div>

    <!-- เรียกใช้งาน Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>