<?php
session_start();
include 'db_connect.php';

// 1. ตรวจสอบว่าได้เข้าสู่ระบบและมีสิทธิ์เป็น Admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้!');
            window.location.href='login.php';
          </script>";
    exit();
}

// 2. ดึงข้อมูล Admin ที่กำลังใช้งานอยู่
$admin_id = $_SESSION['user_id'];
$admin_stmt = mysqli_prepare($conn, "SELECT username, firstname, lastname, email, role FROM users WHERE id = ?");

if (!$admin_stmt) {
    die("SQL Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($admin_stmt, "i", $admin_id);
mysqli_stmt_execute($admin_stmt);
$admin_result = mysqli_stmt_get_result($admin_stmt); // เพิ่มบรรทัดนี้เพื่อดึงผลลัพธ์
$admin_data = mysqli_fetch_assoc($admin_result);

// 3. ดึงรายการโครงงานทั้งหมดจากฐานข้อมูล
$projects_query = "SELECT * FROM projects ORDER BY id DESC";
$projects_result = mysqli_query($conn, $projects_query);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบผู้ดูแลระบบ (Admin Console)</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        /* --- Header สำหรับ Admin --- */
        .header {
            background-color: #0f172a;
            color: white;
            padding: 14px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
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
            background-color: #38bdf8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            font-weight: bold;
            font-size: 13px;
            border: 2px solid #fff;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .admin-badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 8px;
            letter-spacing: 0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            background-color: #334155;
            color: #f87171;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #475569;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background-color: #ef4444;
            color: white;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            background-color: #334155;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #38bdf8;
        }

        /* --- Main Container --- */
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* แผงควบคุม Admin Quick Actions */
        .admin-control-bar {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .control-title {
            font-weight: 700;
            color: #334155;
            font-size: 1.1rem;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .btn-admin {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s;
        }

        .btn-add {
            background-color: #10b981;
            color: white;
        }

        .btn-add:hover {
            background-color: #059669;
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        /* การ์ดข้อมูลหลัก */
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .project-badge-container {
            display: flex;
            justify-content: center;
            padding-top: 25px;
            padding-bottom: 15px;
        }

        .project-badge {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 2px solid #475569;
            color: white;
            padding: 8px 40px;
            border-radius: 30px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* ส่วนข้อมูลผู้ใช้งาน / ระบบ */
        .user-info-section {
            padding: 25px 40px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .label-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #475569;
            min-width: 140px;
        }

        .user-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
        }

        .user-meta {
            font-size: 1.05rem;
            color: #334155;
            font-weight: 500;
        }

        /* ส่วนรายการโครงงานและเมนูจัดการ */
        .project-list-section {
            padding: 30px 40px;
            display: flex;
            gap: 20px;
        }

        .section-label {
            min-width: 140px;
        }

        .project-box {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .project-item {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            position: relative;
        }

        .project-header {
            margin-bottom: 8px;
        }

        .project-title {
            color: #0284c7;
            font-size: 1.15rem;
            font-weight: 700;
            text-decoration: none;
            line-height: 1.5;
        }

        .project-title:hover {
            text-decoration: underline;
        }

        .tag-container {
            display: inline-flex;
            gap: 6px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-degree {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .badge-subject {
            background-color: #06b6d4;
            color: white;
        }

        .author-text {
            font-size: 0.95rem;
            color: #475569;
            margin-top: 6px;
        }

        .page-text {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 2px;
            margin-bottom: 12px;
        }

        /* ปุ่มการทำงานใต้โครงงานสำหรับ Admin */
        .action-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-top: 10px;
        }

        .btn-pdf {
            background-color: #0284c7;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-edit {
            background-color: #f59e0b;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-edit:hover {
            background-color: #d97706;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-delete:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>

    <!-- แถบ Header (Admin Theme) -->
    <header class="header">
        <a href="index.php" class="header-left">
            <div class="logo-placeholder">SDU</div>
            <div class="header-title">
                หน้าแรกระบบ 
                <span class="admin-badge">ADMIN</span>
            </div>
        </a>
        <div class="header-right">
            <a href="logout.php" class="btn-logout">ออกจากระบบ</a>
            <div class="user-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#38bdf8"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- แถบเครื่องมือผู้ดูแลระบบ (Admin Control Bar) -->
        <div class="admin-control-bar">
            <div class="control-title">เครื่องมือผู้ดูแลระบบ (Admin Panel)</div>
            <div class="button-group">
                <a href="add_project.php" class="btn-admin btn-add">+ เพิ่มโครงงานใหม่</a>
                <a href="manage_users.php" class="btn-admin btn-secondary">จัดการผู้ใช้งาน</a>
                <a href="settings.php" class="btn-admin btn-secondary">ตั้งค่าระบบ</a>
            </div>
        </div>

        <!-- การ์ดจัดการข้อมูล -->
        <div class="profile-card">
            
            <div class="project-badge-container">
                <div class="project-badge">โหมดผู้ดูแลระบบ (Full Admin Access)</div>
            </div>

            <!-- ข้อมูลผู้ใช้งานที่กำลังจัดการ -->
            <div class="user-info-section">
                <div class="info-row">
                    <div class="label-title">ผู้ดูแลระบบ</div>
                    <div class="user-name">
                        <?php echo htmlspecialchars(($admin_data['firstname'] ?? '') . ' ' . ($admin_data['lastname'] ?? $admin_data['username'] ?? 'Admin')); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="label-title">ระดับสิทธิ์</div>
                    <div class="user-meta" style="color: #ef4444; font-weight: bold;">
                        <?php echo htmlspecialchars(strtoupper($admin_data['role'] ?? 'ADMIN')); ?> (สิทธิ์จัดการสูงสุด)
                    </div>
                </div>
                <div class="info-row">
                    <div class="label-title">อีเมลระบบ</div>
                    <div class="user-meta"><?php echo htmlspecialchars($admin_data['email'] ?? '-'); ?></div>
                </div>
            </div>

            <!-- ส่วนจัดการรายการโครงงานทั้งหมดในระบบ -->
            <div class="project-list-section">
                <div class="label-title section-label">จัดการโครงงาน</div>
                
                <div class="project-box">
                    <?php if ($projects_result && mysqli_num_rows($projects_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($projects_result)): ?>
                            <div class="project-item">
                                <div class="project-header">
                                    <a href="project-detail.php?id=<?php echo $row['id']; ?>" class="project-title">
                                        <?php echo htmlspecialchars($row['title'] ?? 'ไม่มีชื่อโครงงาน'); ?>
                                    </a>
                                    <div class="tag-container">
                                        <span class="badge badge-degree"><?php echo htmlspecialchars($row['degree'] ?? 'ปริญญาตรี'); ?></span>
                                        <span class="badge badge-subject"><?php echo htmlspecialchars($row['department'] ?? 'เทคโนโลยีสารสนเทศ'); ?></span>
                                    </div>
                                </div>
                                
                                <p class="author-text">ผู้แต่ง: <?php echo htmlspecialchars($row['authors'] ?? '-'); ?></p>
                                <p class="page-text">ตีพิมพ์หน้า: <?php echo htmlspecialchars($row['pages'] ?? '-'); ?></p>

                                <!-- แถบปุ่มจัดการสำหรับ Admin -->
                                <div class="action-bar">
                                    <?php if (!empty($row['pdf_file'])): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($row['pdf_file']); ?>" target="_blank" class="btn-pdf">ดู PDF</a>
                                    <?php endif; ?>
                                    <a href="edit_project.php?id=<?php echo $row['id']; ?>" class="btn-edit">แก้ไขโครงงาน</a>
                                    <a href="delete_project.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('คุณต้องการลบโครงงานนี้ออกจากระบบใช่หรือไม่?');">ลบโครงงาน</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="project-item">
                            <p style="text-align: center; color: #64748b;">ยังไม่มีรายการโครงงานในระบบ</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>