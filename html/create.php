<?php
session_start();
include 'db_connect.php';

// 1. ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนอัปโหลดผลงาน'); window.location.href='login.php';</script>";
    exit();
}

$message = '';
$error = '';

// 2. จัดการเมื่อมีการกดปุ่มส่งฟอร์ม (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = $_SESSION['user_id'];
    $project_type  = $_POST['projectType'] ?? 'miniproject';
    $title        = trim($_POST['projectTitle'] ?? '');
    $degree       = $_POST['degreeSelect'] ?? '';
    $major        = $_POST['majorSelect'] ?? '';
    $authors      = trim($_POST['authorNames'] ?? '');
    $advisor      = trim($_POST['advisorName'] ?? '');
    $github_url    = trim($_POST['githubUrl'] ?? '');
    $abstract     = trim($_POST['projectAbstract'] ?? '');

    if (empty($title) || empty($degree) || empty($major) || empty($authors) || !isset($_FILES['filePdf'])) {
        $error = "กรุณากรอกข้อมูลสำคัญที่มีเครื่องหมาย (*) ให้ครบถ้วน";
    } else {
        $file = $_FILES['filePdf'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt === 'pdf') {
            if ($fileError === 0) {
                if ($fileSize <= 25 * 1024 * 1024) { // 25MB
                    
                    $newFileName = uniqid('proj_', true) . "." . $fileExt;
                    $uploadDir = 'uploads/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileDestination = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        
                        $sql = "INSERT INTO projects (user_id, project_type, title, degree, department, authors, advisor_name, github_url, abstract, pdf_file, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                        if ($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "isssssssss", $user_id, $project_type, $title, $degree, $major, $authors, $advisor, $github_url, $abstract, $newFileName);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                echo "<script>
                                        alert('อัปโหลดและส่งเอกสารเรียบร้อยแล้ว!');
                                        window.location.href='index2.php';
                                     </script>";
                                exit();
                            } else {
                                $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmt);
                        }
                    } else {
                        $error = "เกิดข้อผิดพลาดในการย้ายไฟล์ไปยังเซิร์ฟเวอร์";
                    }
                } else {
                    $error = "ขนาดไฟล์เกินกำหนด (ต้องไม่เกิน 25 MB)";
                }
            } else {
                $error = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            }
        } else {
            $error = "รองรับเฉพาะไฟล์เอกสารประเภท PDF เท่านั้น";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งไฟล์งาน - คลังโปรเจกต์ SDU</title>

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
            background-color: #f8f9fa; 
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

        /* Profile Menu Dropdown */
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

        /* Upload Form Style */
        .upload-card {
            background-color: #ffffff;
            border: 1px solid #e3e8ee;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 30px;
        }

        .type-selector .btn-outline-primary {
            border-color: #2b7bb3;
            color: #2b7bb3;
        }

        .type-selector .btn-check:checked + .btn-outline-primary {
            background-color: #2b7bb3;
            border-color: #2b7bb3;
            color: white;
        }

        .btn-submit {
            background-color: var(--sdu-primary);
            color: white;
            font-weight: 500;
            padding: 10px 24px;
            border-radius: 6px;
            border: none;
        }

        .btn-submit:hover {
            background-color: #1c5b8e;
            color: white;
        }
    </style>
</head>

<body>

    <!-- Header / Navbar -->
    <header class="custom-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-2">
                
                <div class="d-flex align-items-center gap-2">
                    <!-- ใส่แท็ก a ครอบโลโก้และข้อความเพื่อให้คลิกกลับหน้าแรกได้ -->
                    <a href="index2.php" class="d-flex align-items-center gap-2 text-decoration-none text-white">
                        <img 
                            src="https://it-btech.dusit.ac.th/wp-content/uploads/2022/05/SDU2016.png"
                            alt="SDU Logo"
                            class="sdu-logo"
                        >
                        <span class="fs-5 fw-bold">หน้าแรก</span>
                    </a>
                </div>

                <!-- Profile Dropdown -->
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
                            <a class="dropdown-item" href="profile.php">
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
    </header>

    <!-- Upload Form -->
    <div class="container mt-4 mb-5" style="max-width: 800px;">
        <div class="upload-card">
            <h4 class="mb-4 text-primary fw-bold">
                <i class="bi bi-cloud-upload-fill me-2"></i>ส่งไฟล์งาน / โปรเจกต์
            </h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="create.php" method="POST" enctype="multipart/form-data">
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary">ประเภทงานที่ต้องการส่ง <span class="text-danger">*</span></label>
                    <div class="row g-3 type-selector">
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="projectType" id="typeMiniProject" value="miniproject" checked>
                            <label class="btn btn-outline-primary w-100 p-3 text-start" for="typeMiniProject">
                                <div class="fw-bold fs-6"><i class="bi bi-journal-code me-2"></i>Mini Project</div>
                                <small class="text-muted d-block mt-1">มินิโปรเจกต์ประจำรายวิชา</small>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="projectType" id="typeProject" value="project">
                            <label class="btn btn-outline-primary w-100 p-3 text-start" for="typeProject">
                                <div class="fw-bold fs-6"><i class="bi bi-mortarboard-fill me-2"></i>Project (ปริญญานิพนธ์)</div>
                                <small class="text-muted d-block mt-1">โครงงานจบการศึกษา / วิทยานิพนธ์</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="projectTitle" class="form-label fw-bold text-secondary">ชื่อหัวข้อ / ชื่อโปรเจกต์ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="projectTitle" id="projectTitle" placeholder="ระบุชื่อผลงานภาษาไทย หรือภาษาอังกฤษ" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="degreeSelect" class="form-label fw-bold text-secondary">ระดับหลักสูตร <span class="text-danger">*</span></label>
                        <select class="form-select" name="degreeSelect" id="degreeSelect" required>
                            <option value="" selected disabled>-- เลือกระดับการศึกษา --</option>
                            <option value="ปริญญาตรี">ปริญญาตรี</option>
                            <option value="ปริญญาโท">ปริญญาโท</option>
                            <option value="ปริญญาเอก">ปริญญาเอก</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="majorSelect" class="form-label fw-bold text-secondary">สาขาวิชา <span class="text-danger">*</span></label>
                        <select class="form-select" name="majorSelect" id="majorSelect" required>
                            <option value="" selected disabled>-- เลือกสาขาวิชา --</option>
                            <option value="เทคโนโลยีสารสนเทศ">เทคโนโลยีสารสนเทศ</option>
                            <option value="วิทยาการคอมพิวเตอร์">วิทยาการคอมพิวเตอร์</option>
                            <option value="วิทยาศาสตร์สิ่งแวดล้อม">วิทยาศาสตร์สิ่งแวดล้อม</option>
                            <option value="เทคโนโลยีการประกอบอาหาร">เทคโนโลยีการประกอบอาหาร</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="authorNames" class="form-label fw-bold text-secondary">ชื่อผู้จัดทำ / ผู้แต่ง <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="authorNames" id="authorNames" placeholder="เช่น นายสมชาย ใจดี, นางสาวสมหญิง รักดี" required>
                    <div class="form-text">หากมีหลายคน ให้ใช้เครื่องหมายจุลภาค (,) คั่น</div>
                </div>

                <div class="mb-3">
                    <label for="advisorName" class="form-label fw-bold text-secondary">อาจารย์ที่ปรึกษา / อาจารย์ประจำวิชา</label>
                    <input type="text" class="form-control" name="advisorName" id="advisorName" placeholder="ระบุชื่อ-นามสกุล อาจารย์ที่ปรึกษา">
                </div>

                <div class="mb-3">
                    <label for="githubUrl" class="form-label fw-bold text-secondary">GitHub Repository:</label>
                    <input type="url" class="form-control" name="githubUrl" id="githubUrl" placeholder="https://github.com/username/repository">
                </div>

                <div class="mb-3">
                    <label for="projectAbstract" class="form-label fw-bold text-secondary">บทคัดย่อ / รายละเอียดสังเขป</label>
                    <textarea class="form-control" name="projectAbstract" id="projectAbstract" rows="4" placeholder="กรอกเนื้อหาบทคัดย่อหรือรายละเอียดภาพรวมของโปรเจกต์..."></textarea>
                </div>

                <div class="mb-4">
                    <label for="filePdf" class="form-label fw-bold text-secondary">แนบไฟล์เอกสาร (PDF) <span class="text-danger">*</span></label>
                    <input class="form-control" type="file" name="filePdf" id="filePdf" accept=".pdf" required>
                    <div class="form-text">รองรับเฉพาะไฟล์ .pdf ขนาดไม่เกิน 25 MB</div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="index2.php" class="btn btn-light border">ยกเลิก</a>
                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> ส่งเอกสาร
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>