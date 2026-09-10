<?php

session_start();

include 'db_connect.php';


// ================= CHECK LOGIN =================

if (!isset($_SESSION['user_id'])) {

    echo "<script>
            alert('กรุณาเข้าสู่ระบบก่อน!');
            window.location.href='login.php';
          </script>";

    exit();
}


// ================= USER =================

$user_id = intval($_SESSION['user_id']);

$user_query = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE id = $user_id LIMIT 1"
);

$user_data = null;

if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
}


// ================= PROJECTS =================

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

    <title>ข้อมูลส่วนตัว - ระบบสืบค้นโปรเจกต์</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family: "Sarabun",
                         "Segoe UI",
                         Arial,
                         sans-serif;

            background: #f5f8fb;

            color: #333;
        }


        /* ================= HEADER ================= */

        .header {

            background: linear-gradient(
                135deg,
                #4da4d9,
                #2b7bb3
            );

            color: white;

            padding: 15px 40px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        /* สำคัญ: กลับหน้าแรก */

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


        /* ================= CONTENT ================= */

        .container-main {

            max-width: 1100px;

            margin: 40px auto;

            padding: 0 20px;
        }


        .profile-card {

            background: white;

            border-radius: 15px;

            padding: 30px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.08);

            margin-bottom: 30px;
        }


        .profile-title {

            color: #2b7bb3;

            font-size: 26px;

            font-weight: bold;

            margin-bottom: 25px;
        }


        .profile-row {

            display: flex;

            border-bottom: 1px solid #eee;

            padding: 12px 0;
        }


        .profile-label {

            width: 180px;

            font-weight: bold;

            color: #555;
        }


        .profile-value {

            flex: 1;

            color: #333;
        }


        /* ================= PROJECT ================= */

        .section-title {

            color: #2b7bb3;

            font-size: 24px;

            font-weight: bold;

            margin-bottom: 20px;
        }


        .project-card {

            background: white;

            border-radius: 15px;

            padding: 25px;

            margin-bottom: 20px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.07);
        }


        .project-title {

            color: #2b7bb3;

            font-size: 20px;

            font-weight: bold;

            margin-bottom: 15px;
        }


        .project-info {

            margin-bottom: 7px;
        }


        .pdf-btn {

            margin-top: 15px;
        }


        .no-project {

            background: white;

            padding: 30px;

            text-align: center;

            border-radius: 15px;

            color: #777;
        }


        @media (max-width: 600px) {

            .header {

                padding: 12px 20px;
            }

            .container-main {

                margin-top: 25px;
            }

            .profile-row {

                display: block;
            }

            .profile-label {

                width: auto;

                margin-bottom: 5px;
            }

        }

    </style>

</head>


<body>


<!-- ================= HEADER ================= -->

<header class="header">


    <!-- แก้จาก profile.php เป็น index2.php -->

    <a href="index2.php" class="header-left">

        <div class="logo-placeholder">
            SDU
        </div>

        <div class="header-title">
            หน้าแรก
        </div>

    </a>


    <a
        href="logout.php"
        class="btn btn-light">

        <i class="bi bi-box-arrow-right"></i>

        ออกจากระบบ

    </a>


</header>


<!-- ================= MAIN ================= -->

<div class="container-main">


    <!-- ================= USER INFORMATION ================= -->

    <div class="profile-card">


        <div class="profile-title">

            <i class="bi bi-person-circle"></i>

            ข้อมูลส่วนตัว

        </div>


        <div class="profile-row">

            <div class="profile-label">
                ชื่อ
            </div>

            <div class="profile-value">

                <?php

                echo htmlspecialchars(
                    $user_data['firstname']
                    ?? $user_data['username']
                    ?? 'ไม่มีข้อมูล'
                );

                ?>

            </div>

        </div>


        <div class="profile-row">

            <div class="profile-label">
                นามสกุล
            </div>

            <div class="profile-value">

                <?php

                echo htmlspecialchars(
                    $user_data['lastname']
                    ?? '-'
                );

                ?>

            </div>

        </div>


        <div class="profile-row">

            <div class="profile-label">
                Username
            </div>

            <div class="profile-value">

                <?php

                echo htmlspecialchars(
                    $user_data['username']
                    ?? '-'
                );

                ?>

            </div>

        </div>


        <div class="profile-row">

            <div class="profile-label">
                Email
            </div>

            <div class="profile-value">

                <?php

                echo htmlspecialchars(
                    $user_data['email']
                    ?? '-'
                );

                ?>

            </div>

        </div>


        <div class="profile-row">

            <div class="profile-label">
                บทบาท
            </div>

            <div class="profile-value">

                <?php

                echo htmlspecialchars(
                    $user_data['role']
                    ?? '-'
                );

                ?>

            </div>

        </div>


        <div class="profile-row">

            <div class="profile-label">
                สาขา / ภาควิชา
            </div>

            <div class="profile-value">

                <?php

                echo htmlspecialchars(
                    $user_data['department']
                    ?? '-'
                );

                ?>

            </div>

        </div>


    </div>


    <!-- ================= PROJECTS ================= -->

    <div class="section-title">

        <i class="bi bi-folder-fill"></i>

        โปรเจกต์

    </div>


    <?php if ($projects_query && mysqli_num_rows($projects_query) > 0): ?>


        <?php while ($row = mysqli_fetch_assoc($projects_query)): ?>


            <div class="project-card">


                <div class="project-title">

                    <?php

                    echo htmlspecialchars(
                        $row['title']
                        ?? 'ไม่มีชื่อโปรเจกต์'
                    );

                    ?>

                </div>


                <div class="project-info">

                    <strong>ระดับการศึกษา:</strong>

                    <?php

                    echo htmlspecialchars(
                        $row['degree']
                        ?? '-'
                    );

                    ?>

                </div>


                <div class="project-info">

                    <strong>สาขา:</strong>

                    <?php

                    echo htmlspecialchars(
                        $row['department']
                        ?? '-'
                    );

                    ?>

                </div>


                <div class="project-info">

                    <strong>ผู้จัดทำ:</strong>

                    <?php

                    echo htmlspecialchars(
                        $row['authors']
                        ?? '-'
                    );

                    ?>

                </div>


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

            <i class="bi bi-folder-x"
               style="font-size: 40px;"></i>

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