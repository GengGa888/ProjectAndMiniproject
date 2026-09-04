<?php
session_start();
include 'db_connect.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('กรุณาเข้าสู่ระบบก่อน!');
            window.location.href='login.php';
          </script>";
    exit();
}

$user_id = intval($_SESSION['user_id']);

// 2. ดึงข้อมูลผู้ใช้จากฐานข้อมูล (ใช้ mysqli_query เพื่อความยืดหยุ่น ป้องกัน Error เรื่องคอลัมน์)
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
$user_data = ($user_query) ? mysqli_fetch_assoc($user_query) : null;

// 3. ดึงรายการโปรเจกต์ของผู้ใช้คนนี้
$projects_query = mysqli_query($conn, "SELECT * FROM projects WHERE user_id = $user_id ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โครงการ - โปรไฟล์ผู้ใช้</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap');
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Sarabun', sans-serif; }
        body { background-color: #f9f9f9; color: #333; }
        .header { background-color: #3e88c7; color: white; padding: 12px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-left { display: flex; align-items: center; gap: 15px; text-decoration: none; color: white; cursor: pointer; }
        .logo-placeholder { width: 45px; height: 45px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #3e88c7; font-weight: bold; font-size: 12px; border: 2px solid #fff; }
        .header-title { font-size: 1.2rem; font-weight: bold; }
        .container { max-width: 950px; margin: 40px auto; padding: 0 20px; }
        .profile-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e0e0e0; overflow: hidden; }
        .project-badge-container { display: flex; justify-content: center; padding: 25px 0 10px 0; }
        .project-badge { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #64b5f6; color: #1565c0; padding: 10px 60px; border-radius: 30px; font-size: 1.8rem; font-weight: bold; }
        .user-info-section { padding: 20px 50px 30px 50px; display: flex; align-items: baseline; gap: 20px; border-bottom: 2px solid #f0f0f0; flex-wrap: wrap; }
        .label-title { font-size: 1.5rem; font-weight: bold; color: #333; min-width: 90px; }
        .user-name { font-size: 1.8rem; font-weight: bold; color: #1a73e8; margin-right: 30px; }
        .project-list-section { padding: 30px 50px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
        .project-box { width: 70%; display: flex; flex-direction: column; gap: 20px; }
        .project-item { background-color: #fff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
        .project-title { color: #1a73e8; font-size: 1.15rem; font-weight: bold; text-decoration: none; display: inline-block; margin-bottom: 8px; }
        .project-title:hover { text-decoration: underline; }
        .tag-container { display: inline-flex; gap: 8px; margin-left: 8px; vertical-align: middle; }
        .badge { padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .badge-degree { background-color: #e8f0fe; color: #1a73e8; border: 1px solid #c2e7ff; }
        .badge-subject { background-color: #00bcd4; color: white; }
        .author-text { font-size: 0.95rem; color: #555; margin-top: 6px; margin-bottom: 15px; }
        .no-project-text { color: #777; font-size: 1rem; padding: 10px 0; }
        .btn-pdf { background-color: #64b5f6; color: white; border: none; padding: 6px 16px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-pdf:hover { background-color: #42a5f5; }
    </style>
</head>
<body>

    <header class="header">
        <a href="index2.php" class="header-left">
            <div class="logo-placeholder">SDU</div>
            <div class="header-title">หน้าแรก</div>
        </a>
    </header>

    <div class="container">
        <div class="profile-card">
            <div class="project-badge-container">
                <div class="project-badge">ข้อมูลส่วนตัว</div>
            </div>

            <div class="user-info-section">
                <div class="label-title">ชื่อ</div>
                <div class="user-name"><?php echo htmlspecialchars($user_data['firstname'] ?? $user_data['username'] ?? 'ไม่มีข้อมูล'); ?></div>
                <div class="label-title">นามสกุล</div>
                <div class="user-name"><?php echo htmlspecialchars($user_data['lastname'] ?? '-'); ?></div>
            </div>

            <div class="project-list-section">
                <div class="label-title">โปรเจกต์ที่ทำ</div>
                <div class="project-box">
                    <?php if ($projects_query && mysqli_num_rows($projects_query) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($projects_query)): ?>
                            <div class="project-item">
                                <a href="project-detail.php?id=<?php echo $row['id']; ?>" class="project-title">
                                    <?php echo htmlspecialchars($row['title'] ?? 'ไม่มีชื่อโปรเจกต์'); ?>
                                </a>
                                <div class="tag-container">
                                    <span class="badge badge-degree"><?php echo htmlspecialchars($row['degree'] ?? 'ปริญญาตรี'); ?></span>
                                    <span class="badge badge-subject"><?php echo htmlspecialchars($row['department'] ?? 'วิทยาการคอมพิวเตอร์'); ?></span>
                                </div>
                                <p class="author-text"><strong>ผู้จัดทำ:</strong> <?php echo htmlspecialchars($row['authors'] ?? '-'); ?></p>
                                
                                <?php if (!empty($row['pdf_file'])): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($row['pdf_file']); ?>" target="_blank" class="btn-pdf">PDF</a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-project-text">ยังไม่มีประวัติโครงงานหรือโปรเจกต์ในระบบ</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>