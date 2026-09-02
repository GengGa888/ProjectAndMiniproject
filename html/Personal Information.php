<?php
session_start();
include 'db_connect.php';

// ตรวจสอบว่าผู้ใช้ Login เข้ามาหรือยัง หากยังให้ส่งกลับไปหน้า Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. ดึงข้อมูลผู้ใช้งานที่กำลัง Login
$stmt_user = mysqli_prepare($conn, "SELECT first_name, last_name FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$res_user = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($res_user);

// กำหนดค่าชื่อ-นามสกุล (หากไม่มีใน DB ให้ใช้ค่าจาก Session หรือแสดงค่าเริ่มต้น)
$first_name = $user_data['first_name'] ?? $_SESSION['username'] ?? 'ไม่ระบุ';
$last_name = $user_data['last_name'] ?? '';

// 2. ดึงรายการโปรเจกต์ของผู้ใช้นี้
$stmt_proj = mysqli_prepare($conn, "SELECT * FROM projects WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_proj, "i", $user_id);
mysqli_stmt_execute($stmt_proj);
$projects_result = mysqli_stmt_get_result($stmt_proj);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โครงการ - โปรไฟล์ผู้ใช้</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: #333;
        }

        /* --- Header --- */
        .header {
            background-color: #3e88c7;
            color: white;
            padding: 12px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .header-left:hover {
            opacity: 0.9;
        }

        .logo-placeholder {
            width: 45px;
            height: 45px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3e88c7;
            font-weight: bold;
            font-size: 12px;
            border: 2px solid #fff;
        }

        .header-title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            background-color: #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        /* --- Container --- */
        .container {
            max-width: 950px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .project-badge-container {
            display: flex;
            justify-content: center;
            padding-top: 25px;
            padding-bottom: 10px;
        }

        .project-badge {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #64b5f6;
            color: #1565c0;
            padding: 10px 60px;
            border-radius: 30px;
            font-size: 1.8rem;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .user-info-section {
            padding: 20px 50px 30px 50px;
            display: flex;
            align-items: baseline;
            gap: 20px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }

        .label-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            min-width: 90px;
        }

        .user-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1a73e8;
            margin-right: 30px;
        }

        .project-list-section {
            padding: 30px 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .project-box {
            width: 70%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .project-item {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }

        .project-title {
            color: #1a73e8;
            font-size: 1.15rem;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 8px;
        }

        .project-title:hover {
            text-decoration: underline;
        }

        .tag-container {
            display: inline-flex;
            gap: 8px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-degree {
            background-color: #e8f0fe;
            color: #1a73e8;
            border: 1px solid #c2e7ff;
        }

        .badge-subject {
            background-color: #00bcd4;
            color: white;
        }

        .author-text {
            font-size: 0.95rem;
            color: #555;
            margin-top: 6px;
        }

        .page-text {
            font-size: 0.9rem;
            color: #777;
            margin-top: 2px;
            margin-bottom: 12px;
        }

        .btn-pdf {
            background-color: #64b5f6;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-pdf:hover {
            background-color: #42a5f5;
        }

        .no-project {
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- แถบ Header -->
    <header class="header">
        <a href="index2.php" class="header-left">
            <div class="logo-placeholder">SDU</div>
            <div class="header-title">หน้าแรก</div>
        </a>
        <div class="header-right">
            <a href="logout.php" style="color: white; text-decoration: none; margin-right: 15px; font-weight: bold;">ออกจากระบบ</a>
            <div class="user-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
        </div>
    </header>

    <!-- ส่วนเนื้อหาโปรไฟล์ -->
    <div class="container">
        <div class="profile-card">
            
            <div class="project-badge-container">
                <div class="project-badge">ข้อมูลส่วนตัว</div>
            </div>

            <!-- ดึงชื่อผู้ใช้งานจริงมาแสดง -->
            <div class="user-info-section">
                <div class="label-title">ชื่อ</div>
                <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                <div class="label-title">นามสกุล</div>
                <div class="user-name"><?php echo htmlspecialchars($last_name); ?></div>
            </div>

            <!-- แสดงรายการโปรเจกต์แบบ Dynamic -->
            <div class="project-list-section">
                <div class="label-title">โปรเจกต์ที่ทำ</div>
                
                <div class="project-box">
                    <?php if (mysqli_num_rows($projects_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($projects_result)): ?>
                            <div class="project-item">
                                <a href="project-detail.php?id=<?php echo $row['id']; ?>" class="project-title">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </a>
                                <div class="tag-container">
                                    <span class="badge badge-degree"><?php echo htmlspecialchars($row['degree_level'] ?? 'ปริญญาตรี'); ?></span>
                                    <span class="badge badge-subject"><?php echo htmlspecialchars($row['subject'] ?? 'เทคโนโลยีสารสนเทศ'); ?></span>
                                </div>
                                
                                <p class="author-text"><?php echo htmlspecialchars($row['authors'] ?? ''); ?></p>
                                <p class="page-text"><?php echo htmlspecialchars($row['page_range'] ?? ''); ?></p>
                                
                                <?php if (!empty($row['pdf_file'])): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($row['pdf_file']); ?>" target="_blank" class="btn-pdf">PDF</a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- กรณีผู้ใช้ยังไม่มีโปรเจกต์ -->
                        <p class="no-project">ยังไม่มีข้อมูลโปรเจกต์ที่ทำ</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>