<?php
session_start();
include 'db_connect.php';

/* ================= CHECK LOGIN ================= */

if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('กรุณาเข้าสู่ระบบก่อน!');
            window.location.href='login.php';
          </script>";
    exit();
}

$user_id = intval($_SESSION['user_id']);

/* ================= GET USER ================= */

$user_query = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE id = $user_id LIMIT 1"
);

$user_data = null;

if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
}

/* ================= USER DATA ================= */

$firstname  = $user_data['firstname'] ?? '';
$lastname   = $user_data['lastname'] ?? '';
$username   = $user_data['username'] ?? '-';
$email      = $user_data['email'] ?? '-';
$role       = $user_data['role'] ?? '';
$department = $user_data['department'] ?? '-';

/* ================= ROLE ================= */

if ($role === 'teacher') {

    $role_text = 'อาจารย์';
    $role_icon = 'bi-person-workspace';

} else {

    $role_text = 'นักศึกษา';
    $role_icon = 'bi-mortarboard-fill';

}


/* ================= PROJECTS ================= */

$projects_query = mysqli_query(
    $conn,
    "SELECT * FROM projects ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>โปรไฟล์ - คลังโปรเจกต์ SDU</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                "Sarabun",
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f4f8fb;

            color: #333;

        }


        /* =========================================
           HEADER
        ========================================= */

        .header {

            height: 80px;

            background:
                linear-gradient(
                    135deg,
                    #4da4d9,
                    #2b7bb3
                );

            color: white;

            padding: 0 40px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.12);

        }


        .header-left {

            display: flex;

            align-items: center;

            gap: 15px;

            color: white;

            text-decoration: none;

        }


        .logo-placeholder {

            width: 48px;

            height: 48px;

            background: white;

            color: #2b7bb3;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 17px;

            font-weight: bold;

        }


        .header-title {

            font-size: 21px;

            font-weight: bold;

        }


        .back-home {

            color: white;

            text-decoration: none;

            font-size: 16px;

            padding: 9px 16px;

            border-radius: 8px;

            transition: 0.2s;

        }


        .back-home:hover {

            background: rgba(255,255,255,0.15);

            color: white;

        }


        /* =========================================
           MAIN
        ========================================= */

        .container-main {

            max-width: 1100px;

            margin: 40px auto;

            padding: 0 20px;

        }


        /* =========================================
           PROFILE CARD
        ========================================= */

        .profile-card {

            background: white;

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);

            margin-bottom: 30px;

        }


        .profile-cover {

            height: 150px;

            background:
                linear-gradient(
                    135deg,
                    #4da4d9,
                    #2b7bb3
                );

            position: relative;

        }


        .profile-avatar {

            width: 125px;

            height: 125px;

            border-radius: 50%;

            background: white;

            border: 6px solid white;

            position: absolute;

            left: 45px;

            bottom: -62px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #2b7bb3;

            font-size: 60px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.15);

        }


        .profile-body {

            padding: 80px 45px 35px;

        }


        .profile-name {

            font-size: 30px;

            font-weight: bold;

            margin-bottom: 8px;

            color: #222;

        }


        .profile-username {

            color: #777;

            margin-bottom: 15px;

        }


        /* =========================================
           ROLE BADGE
        ========================================= */

        .role-badge {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 8px 18px;

            border-radius: 30px;

            background: #e8f4fc;

            color: #2b7bb3;

            font-weight: bold;

            margin-bottom: 25px;

        }


        /* =========================================
           INFORMATION
        ========================================= */

        .info-title {

            font-size: 21px;

            font-weight: bold;

            color: #2b7bb3;

            margin-bottom: 15px;

        }


        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;

        }


        .info-box {

            background: #f7fafc;

            border: 1px solid #e6edf2;

            border-radius: 12px;

            padding: 18px;

        }


        .info-label {

            font-size: 14px;

            color: #777;

            margin-bottom: 5px;

        }


        .info-value {

            font-size: 16px;

            font-weight: 600;

            color: #333;

        }


        /* =========================================
           PROJECT SECTION
        ========================================= */

        .section-title {

            font-size: 24px;

            font-weight: bold;

            color: #2b7bb3;

            margin-bottom: 20px;

        }


        .project-card {

            background: white;

            border-radius: 16px;

            padding: 25px;

            margin-bottom: 18px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.07);

            border-left: 5px solid #4da4d9;

        }


        .project-title {

            font-size: 19px;

            font-weight: bold;

            color: #2b7bb3;

            margin-bottom: 15px;

        }


        .project-info {

            margin-bottom: 7px;

            color: #555;

        }


        .pdf-btn {

            margin-top: 12px;

        }


        .no-project {

            background: white;

            padding: 40px;

            text-align: center;

            border-radius: 16px;

            color: #777;

        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 700px) {

            .header {

                padding: 0 20px;

            }


            .header-title {

                font-size: 17px;

            }


            .profile-avatar {

                left: 25px;

            }


            .profile-body {

                padding:
                    80px 25px 30px;

            }


            .profile-name {

                font-size: 25px;

            }


            .info-grid {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header class="header">


    <!-- กดแล้วกลับหน้า index2.php -->

    <a href="index2.php" class="header-left">

        <div class="logo-placeholder">
            SDU
        </div>

        <div class="header-title">
            คลังโปรเจกต์ SDU
        </div>

    </a>


    <a href="index2.php" class="back-home">

        <i class="bi bi-house-fill"></i>

        หน้าแรก

    </a>


</header>



<!-- =========================================
     MAIN
========================================= -->

<div class="container-main">


    <!-- =====================================
         PROFILE
    ====================================== -->

    <div class="profile-card">


        <!-- COVER -->

        <div class="profile-cover">


            <div class="profile-avatar">

                <i class="bi bi-person-fill"></i>

            </div>


        </div>


        <!-- BODY -->

        <div class="profile-body">


            <!-- NAME -->

            <div class="profile-name">

                <?php

                echo htmlspecialchars(
                    trim($firstname . ' ' . $lastname)
                    ?: $username
                );

                ?>

            </div>


            <!-- USERNAME -->

            <div class="profile-username">

                @<?php echo htmlspecialchars($username); ?>

            </div>


            <!-- ROLE -->

            <div class="role-badge">

                <i class="bi <?php echo $role_icon; ?>"></i>

                <?php echo $role_text; ?>

            </div>



            <!-- =================================
                 INFORMATION
            ================================== -->

            <div class="info-title">

                <i class="bi bi-person-vcard-fill"></i>

                ข้อมูลบัญชี

            </div>


            <div class="info-grid">


                <!-- EMAIL -->

                <div class="info-box">

                    <div class="info-label">
                        อีเมล
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars($email);
                        ?>

                    </div>

                </div>


                <!-- ROLE -->

                <div class="info-box">

                    <div class="info-label">
                        ประเภทผู้ใช้งาน
                    </div>

                    <div class="info-value">

                        <i class="bi <?php echo $role_icon; ?>"></i>

                        <?php echo $role_text; ?>

                    </div>

                </div>


                <!-- DEPARTMENT -->

                <div class="info-box">

                    <div class="info-label">
                        สาขา / ภาควิชา
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars($department);
                        ?>

                    </div>

                </div>


                <!-- USERNAME -->

                <div class="info-box">

                    <div class="info-label">
                        ชื่อผู้ใช้งาน
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars($username);
                        ?>

                    </div>

                </div>


            </div>

        </div>

    </div>



    <!-- =====================================
         PROJECTS
    ====================================== -->

    <div class="section-title">

        <i class="bi bi-folder-fill"></i>

        โปรเจกต์ของผู้ใช้งาน

    </div>


    <?php if ($projects_query && mysqli_num_rows($projects_query) > 0): ?>


        <?php while ($row = mysqli_fetch_assoc($projects_query)): ?>


            <div class="project-card">


                <!-- TITLE -->

                <div class="project-title">

                    <i class="bi bi-file-earmark-text"></i>

                    <?php

                    echo htmlspecialchars(
                        $row['title']
                        ?? 'ไม่มีชื่อโปรเจกต์'
                    );

                    ?>

                </div>


                <!-- DEGREE -->

                <div class="project-info">

                    <strong>
                        <i class="bi bi-mortarboard"></i>
                        ระดับการศึกษา:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $row['degree']
                        ?? '-'
                    );

                    ?>

                </div>


                <!-- DEPARTMENT -->

                <div class="project-info">

                    <strong>
                        <i class="bi bi-building"></i>
                        สาขา:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $row['department']
                        ?? '-'
                    );

                    ?>

                </div>


                <!-- AUTHORS -->

                <div class="project-info">

                    <strong>
                        <i class="bi bi-people"></i>
                        ผู้จัดทำ:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $row['authors']
                        ?? '-'
                    );

                    ?>

                </div>


                <!-- PDF -->

                <?php if (!empty($row['pdf_file'])): ?>

                    <a
                        href="<?php echo htmlspecialchars($row['pdf_file']); ?>"
                        target="_blank"
                        class="btn btn-primary pdf-btn">

                        <i class="bi bi-file-earmark-pdf"></i>

                        เปิดไฟล์ PDF

                    </a>

                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <div class="no-project">

            <i
                class="bi bi-folder-x"
                style="font-size: 45px;">
            </i>

            <p class="mt-3 mb-0">
                ยังไม่มีโปรเจกต์
            </p>

        </div>


    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>