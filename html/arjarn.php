<?php
session_start();
include 'db_connect.php';

// 1. ตรวจสอบสิทธิ์การเข้าใช้งาน (ต้องล็อกอินและเป็น advisor หรือ admin)
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนใช้งาน'); window.location.href='login.php';</script>";
    exit();
}

$advisor_id = $_SESSION['user_id'];

// 2. บันทึกข้อเสนอแนะใหม่ (เมื่อมีการกดปุ่มส่งคอมเมนต์)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $project_id = intval($_POST['project_id']);
    $comment_text = trim($_POST['comment_text']);

    if (!empty($comment_text) && $project_id > 0) {
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO project_comments (project_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_insert, "iis", $project_id, $advisor_id, $comment_text);
        mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);

        // รีเฟรชหน้าเพื่อแสดงคอมเมนต์ใหม่
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 3. ดึงข้อมูลอาจารย์ที่ปรึกษาที่ล็อกอินอยู่
$advisor_stmt = mysqli_prepare($conn, "SELECT id, firstname, lastname, email, department, academic_rank FROM users WHERE id = ?");
mysqli_stmt_bind_param($advisor_stmt, "i", $advisor_id);
mysqli_stmt_execute($advisor_stmt);
$advisor_result = mysqli_stmt_get_result($advisor_stmt);
$advisor_data = mysqli_fetch_assoc($advisor_result);

// 4. ดึงรายการโครงงานที่อาจารย์ท่านนี้ดูแลอยู่
$projects_stmt = mysqli_prepare($conn, "SELECT * FROM projects WHERE advisor_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($projects_stmt, "i", $advisor_id);
mysqli_stmt_execute($projects_stmt);
$projects_result = mysqli_stmt_get_result($projects_stmt);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์อาจารย์ / ที่ปรึกษาโครงงาน</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            background-color: #f4f7f9;
            color: #2c3e50;
        }

        /* --- Header --- */
        .header {
            background-color: #1e40af;
            color: white;
            padding: 14px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            color: #1e40af;
            font-weight: bold;
            font-size: 13px;
            border: 2px solid #fff;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            background-color: #1e293b;
            color: #f87171;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .btn-logout:hover {
            background-color: #ef4444;
            color: white;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            background-color: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        /* --- Main Container --- */
        .container {
            max-width: 950px;
            margin: 40px auto;
            padding: 0 20px;
        }

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
            padding-top: 30px;
            padding-bottom: 15px;
        }

        .project-badge {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #93c5fd;
            color: #1e40af;
            padding: 8px 50px;
            border-radius: 30px;
            font-size: 1.6rem;
            font-weight: 700;
        }

        /* ส่วนข้อมูลอาจารย์ */
        .user-info-section {
            padding: 25px 50px;
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
            font-size: 1.25rem;
            font-weight: 700;
            color: #475569;
            min-width: 140px;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .user-meta {
            font-size: 1.1rem;
            color: #334155;
            font-weight: 500;
        }

        /* ส่วนรายการงานวิจัย / โครงงานที่ควบคุมดูแล */
        .project-list-section {
            padding: 30px 50px;
            display: flex;
            gap: 30px;
        }

        .section-label {
            min-width: 140px;
        }

        .project-box {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .project-item {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: border-color 0.2s;
        }

        .project-item:hover {
            border-color: #93c5fd;
        }

        .project-header {
            margin-bottom: 8px;
        }

        .project-title {
            color: #1d4ed8;
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
            gap: 8px;
            margin-left: 8px;
            vertical-align: middle;
            flex-wrap: wrap;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-role {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-degree {
            background-color: #eff6ff;
            color: #819ce4;
            border: 1px solid #bfdbfe;
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

        .btn-pdf {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }

        .btn-pdf:hover {
            background-color: #1d4ed8;
        }

        /* --- ส่วน Comments (ข้อเสนอแนะจากอาจารย์) --- */
        .comments-container {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
        }

        .comments-title {
            font-size: 1rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 16px;
        }

        .comment-item {
            background-color: #f8fafc;
            border-left: 3px solid #3b82f6;
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            font-size: 0.9rem;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .comment-author {
            font-weight: 600;
            color: #1e293b;
        }

        .comment-date {
            font-size: 0.78rem;
            color: #94a3b8;
        }

        .comment-text {
            color: #475569;
            line-height: 1.4;
        }

        /* ฟอร์มเพิ่มความเห็น */
        .comment-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #f1f5f9;
            padding: 12px;
            border-radius: 6px;
        }

        .comment-input {
            width: 100%;
            min-height: 60px;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 0.9rem;
            resize: vertical;
            outline: none;
        }

        .comment-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .btn-comment-submit {
            align-self: flex-end;
            background-color: #059669;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-comment-submit:hover {
            background-color: #047857;
        }
    </style>
</head>
<body>

    <!-- แถบ Header -->
    <header class="header">
        <a href="index.php" class="header-left">
            <div class="logo-placeholder">SDU</div>
            <div class="header-title">หน้าแรก</div>
        </a>
        <div class="header-right">
            <a href="logout.php" class="btn-logout">ออกจากระบบ</a>
            <div class="user-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
        </div>
    </header>

    <!-- ส่วนเนื้อหาโปรไฟล์อาจารย์ -->
    <div class="container">
        <div class="profile-card">
            
            <div class="project-badge-container">
                <div class="project-badge">ข้อมูลอาจารย์ที่ปรึกษา</div>
            </div>

            <!-- ข้อมูลส่วนตัวอาจารย์ -->
            <div class="user-info-section">
                <div class="info-row">
                    <div class="label-title">ชื่อ-สกุล</div>
                    <div class="user-name">
                        <?php echo htmlspecialchars(($advisor_data['academic_rank'] ?? '') . ' ' . ($advisor_data['firstname'] ?? '') . ' ' . ($advisor_data['lastname'] ?? '')); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="label-title">สังกัด / สาขา</div>
                    <div class="user-meta">
                        <?php echo htmlspecialchars($advisor_data['department'] ?? 'สาขาวิชาเทคโนโลยีสารสนเทศ'); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="label-title">อีเมลติดต่อ</div>
                    <div class="user-meta"><?php echo htmlspecialchars($advisor_data['email'] ?? '-'); ?></div>
                </div>
            </div>

            <!-- ส่วนรายการงานวิจัย / โครงงานที่ควบคุมดูแล -->
            <div class="project-list-section">
                <div class="label-title section-label">โครงงานที่ดูแล</div>
                
                <div class="project-box">
                    <?php if ($projects_result && mysqli_num_rows($projects_result) > 0): ?>
                        <?php while ($proj = mysqli_fetch_assoc($projects_result)): ?>
                            <?php $project_id = $proj['id']; ?>
                            
                            <div class="project-item">
                                <div class="project-header">
                                    <a href="project-detail.php?id=<?php echo $project_id; ?>" class="project-title">
                                        <?php echo htmlspecialchars($proj['title']); ?>
                                    </a>
                                    <div class="tag-container">
                                        <span class="badge badge-role">อาจารย์ที่ปรึกษาหลัก</span>
                                        <span class="badge badge-degree"><?php echo htmlspecialchars($proj['degree'] ?? 'ปริญญาตรี'); ?></span>
                                        <span class="badge badge-subject"><?php echo htmlspecialchars($proj['department'] ?? 'เทคโนโลยีสารสนเทศ'); ?></span>
                                    </div>
                                </div>
                                
                                <p class="author-text">นักศึกษา: <?php echo htmlspecialchars($proj['authors']); ?></p>
                                <p class="page-text">ตีพิมพ์หน้า: <?php echo htmlspecialchars($proj['pages'] ?? '-'); ?></p>

                                <?php if (!empty($proj['pdf_file'])): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($proj['pdf_file']); ?>" target="_blank" class="btn-pdf">ดาวน์โหลด PDF</a>
                                <?php endif; ?>

                                <!-- ส่วนแสดงข้อเสนอแนะ/ความคิดเห็น -->
                                <div class="comments-container">
                                    <div class="comments-title">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#3b82f6"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                                        ข้อเสนอแนะจากอาจารย์ที่ปรึกษา (Comments)
                                    </div>
                                    
                                    <!-- รายการความเห็นเดิมที่ดึงมาจากฐานข้อมูล -->
                                    <div class="comment-list">
                                        <?php
                                        $cm_stmt = mysqli_prepare($conn, "SELECT c.*, u.firstname, u.lastname, u.academic_rank FROM project_comments c JOIN users u ON c.user_id = u.id WHERE c.project_id = ? ORDER BY c.created_at ASC");
                                        mysqli_stmt_bind_param($cm_stmt, "i", $project_id);
                                        mysqli_stmt_execute($cm_stmt);
                                        $cm_result = mysqli_stmt_get_result($cm_stmt);

                                        if ($cm_result && mysqli_num_rows($cm_result) > 0):
                                            while ($cm = mysqli_fetch_assoc($cm_result)):
                                        ?>
                                                <div class="comment-item">
                                                    <div class="comment-header">
                                                        <span class="comment-author">
                                                            <?php echo htmlspecialchars(($cm['academic_rank'] ?? '') . ' ' . $cm['firstname'] . ' ' . $cm['lastname']); ?>
                                                        </span>
                                                        <span class="comment-date"><?php echo date('d M Y - H:i', strtotime($cm['created_at'])); ?> น.</span>
                                                    </div>
                                                    <div class="comment-text">
                                                        <?php echo nl2br(htmlspecialchars($cm['comment_text'])); ?>
                                                    </div>
                                                </div>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <p style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">ยังไม่มีข้อเสนอแนะในโครงงานนี้</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- ฟอร์มสำหรับพิมพ์ความเห็นใหม่ (ส่งข้อมูลผ่าน POST) -->
                                    <form class="comment-form" method="POST" action="">
                                        <input type="hidden" name="action" value="add_comment">
                                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                        <textarea class="comment-input" name="comment_text" placeholder="พิมพ์ข้อเสนอแนะหรือข้อสั่งการแก้ไขสำหรับนักศึกษา..." required></textarea>
                                        <button type="submit" class="btn-comment-submit">บันทึกข้อเสนอแนะ</button>
                                    </form>
                                </div>

                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="project-item">
                            <p style="text-align: center; color: #64748b;">ยังไม่มีโครงงานที่คุณรับผิดชอบเป็นอาจารย์ที่ปรึกษา</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>