<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo "<script>
        alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้!');
        window.location.href='login.php';
    </script>";
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

/* ข้อมูล Admin */
$stmt = mysqli_prepare($conn,
    "SELECT username, first_name, last_name, email, role
     FROM users WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);

$admin_data = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

/* จำนวนผู้ใช้ */
$user_count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$user_count = mysqli_fetch_assoc(
    $user_count_result
)['total'];

/* จำนวนโปรเจกต์ */
$project_count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM projects"
);

$project_count = mysqli_fetch_assoc(
    $project_count_result
)['total'];

/* โปรเจกต์ทั้งหมด */
$projects_result = mysqli_query(
    $conn,
    "SELECT * FROM projects ORDER BY id DESC"
);

/* ผู้ใช้ทั้งหมด */
$users_result = mysqli_query(
    $conn,
    "SELECT id, username, first_name, last_name,
            email, role, department, created_at
     FROM users
     ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Admin Console - คลังโปรเจกต์ SDU</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
}

body {
    background: #f4f7fb;
    color: #172033;
}

/* HEADER */

.header {
    height: 80px;
    background: linear-gradient(
        135deg,
        #172033,
        #263653
    );

    color: white;

    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 0 40px;

    box-shadow:
        0 3px 15px rgba(0,0,0,0.15);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 14px;

    color: white;
    text-decoration: none;
}

.logo {
    width: 48px;
    height: 48px;

    border-radius: 12px;

    background: #4aa4d6;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 800;
}

.header-title {
    font-size: 21px;
    font-weight: 700;
}

.admin-badge {
    background: #ef4444;

    font-size: 12px;

    padding: 4px 9px;

    border-radius: 5px;

    margin-left: 8px;
}

.logout {
    color: white;
    text-decoration: none;

    background: #ef4444;

    padding: 9px 15px;

    border-radius: 8px;

    font-weight: 600;
}

.logout:hover {
    background: #dc2626;
}

/* CONTAINER */

.container {
    max-width: 1250px;

    margin: 35px auto;

    padding: 0 20px;
}

.welcome {
    margin-bottom: 25px;
}

.welcome h1 {
    font-size: 30px;
}

.welcome p {
    margin-top: 6px;
    color: #64748b;
}

/* STAT */

.stats {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}

.stat-card {
    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 16px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.05);
}

.stat-icon {
    font-size: 32px;
    color: #3287bb;
}

.stat-number {
    font-size: 30px;
    font-weight: 700;

    margin-top: 7px;
}

.stat-title {
    color: #64748b;
}

/* CARD */

.card {
    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 16px;

    overflow: hidden;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.05);
}

.card-header {
    padding: 20px 24px;

    border-bottom:
        1px solid #e2e8f0;

    display: flex;

    align-items: center;
    justify-content: space-between;
}

.card-header h2 {
    font-size: 20px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f8fafc;

    color: #475569;

    font-size: 14px;

    text-align: left;

    padding: 14px;
}

td {
    padding: 14px;

    border-top:
        1px solid #edf2f7;

    font-size: 14px;

    white-space: nowrap;
}

.status {
    background: #e0f2fe;

    color: #0369a1;

    padding: 5px 9px;

    border-radius: 6px;

    font-size: 12px;
}

.actions {
    display: flex;
    gap: 7px;
}

.btn {
    text-decoration: none;

    border: none;

    padding: 7px 11px;

    border-radius: 7px;

    font-size: 13px;

    cursor: pointer;
}

.btn-edit {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-delete {
    background: #fee2e2;
    color: #b91c1c;
}

.btn-edit:hover {
    background: #bfdbfe;
}

.btn-delete:hover {
    background: #fecaca;
}

/* MOBILE */

@media(max-width:700px) {

    .header {
        padding: 0 15px;
    }

    .header-title {
        font-size: 16px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .container {
        padding: 0 12px;
    }

}

</style>

</head>

<body>

<header class="header">

    <a href="index2.php" class="header-left">

        <div class="logo">
            SDU
        </div>

        <div class="header-title">

            Admin Console

            <span class="admin-badge">
                ADMIN
            </span>

        </div>

    </a>

    <a href="logout.php" class="logout">

        <i class="bi bi-box-arrow-right"></i>

        ออกจากระบบ

    </a>

</header>


<div class="container">

    <div class="welcome">

        <h1>
            จัดการระบบ
        </h1>

        <p>
            สวัสดี
            <?php
            echo htmlspecialchars(
                ($admin_data['first_name'] ?? '') .
                ' ' .
                ($admin_data['last_name'] ?? '')
            );
            ?>

            — Admin สามารถจัดการข้อมูลทั้งหมดได้
        </p>

    </div>


    <!-- สถิติ -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                <i class="bi bi-people-fill"></i>
            </div>

            <div class="stat-number">
                <?php echo (int)$user_count; ?>
            </div>

            <div class="stat-title">
                ผู้ใช้ทั้งหมด
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="bi bi-folder-fill"></i>
            </div>

            <div class="stat-number">
                <?php echo (int)$project_count; ?>
            </div>

            <div class="stat-title">
                โปรเจกต์ทั้งหมด
            </div>

        </div>

    </div>


    <!-- =========================
         PROJECTS
    ========================== -->

    <div class="card">

        <div class="card-header">

            <h2>
                <i class="bi bi-folder2-open"></i>

                จัดการโปรเจกต์ทั้งหมด
            </h2>

        </div>


        <div class="table-wrapper">

            <table>

                <tr>

                    <th>ID</th>

                    <th>ชื่อโปรเจกต์</th>

                    <th>ผู้จัดทำ</th>

                    <th>ระดับการศึกษา</th>

                    <th>คณะ / สาขา</th>

                    <th>สถานะ</th>

                    <th>จัดการ</th>

                </tr>


                <?php while ($project = mysqli_fetch_assoc($projects_result)): ?>

                <tr>

                    <td>
                        <?php echo (int)$project['id']; ?>
                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $project['title']
                            ?: $project['project_name']
                        );
                        ?>

                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $project['authors']
                            ?: $project['student_name']
                            ?: '-'
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $project['degree'] ?: '-'
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $project['department'] ?: '-'
                        );
                        ?>
                    </td>

                    <td>

                        <span class="status">

                            <?php
                            echo htmlspecialchars(
                                $project['status'] ?: 'ส่งแล้ว'
                            );
                            ?>

                        </span>

                    </td>

                    <td>

                        <div class="actions">

                            <a
                                href="project-detail.php?id=<?php echo (int)$project['id']; ?>"
                                class="btn btn-edit"
                            >
                                <i class="bi bi-eye"></i>
                                ดู
                            </a>


                            <a
                                href="admin_edit_project.php?id=<?php echo (int)$project['id']; ?>"
                                class="btn btn-edit"
                            >
                                <i class="bi bi-pencil"></i>
                                แก้ไข
                            </a>


                            <a
                                href="admin_delete_project.php?id=<?php echo (int)$project['id']; ?>"
                                class="btn btn-delete"

                                onclick="
                                return confirm(
                                    'ยืนยันการลบโปรเจกต์นี้?'
                                );
                                "
                            >
                                <i class="bi bi-trash"></i>
                                ลบ
                            </a>

                        </div>

                    </td>

                </tr>

                <?php endwhile; ?>

            </table>

        </div>

    </div>


    <!-- =========================
         USERS
    ========================== -->

    <div class="card">

        <div class="card-header">

            <h2>

                <i class="bi bi-people"></i>

                จัดการผู้ใช้ทั้งหมด

            </h2>

        </div>


        <div class="table-wrapper">

            <table>

                <tr>

                    <th>ID</th>

                    <th>Username</th>

                    <th>ชื่อ</th>

                    <th>Email</th>

                    <th>สิทธิ์</th>

                    <th>คณะ / สาขา</th>

                    <th>จัดการ</th>

                </tr>


                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>

                <tr>

                    <td>
                        <?php echo (int)$user['id']; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $user['username']
                        );
                        ?>
                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            ($user['first_name'] ?? '') .
                            ' ' .
                            ($user['last_name'] ?? '')
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $user['email'] ?? '-'
                        );
                        ?>

                    </td>

                    <td>

                        <span class="status">

                            <?php
                            echo htmlspecialchars(
                                $user['role'] ?? '-'
                            );
                            ?>

                        </span>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $user['department'] ?? '-'
                        );
                        ?>

                    </td>

                    <td>

                        <div class="actions">

                            <a
                                href="admin_edit_user.php?id=<?php echo (int)$user['id']; ?>"
                                class="btn btn-edit"
                            >

                                <i class="bi bi-pencil"></i>

                                แก้ไข

                            </a>


                            <?php if ((int)$user['id'] !== $admin_id): ?>

                            <a
                                href="admin_delete_user.php?id=<?php echo (int)$user['id']; ?>"
                                class="btn btn-delete"

                                onclick="
                                return confirm(
                                    'ยืนยันการลบผู้ใช้นี้?'
                                );
                                "
                            >

                                <i class="bi bi-trash"></i>

                                ลบ

                            </a>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

                <?php endwhile; ?>

            </table>

        </div>

    </div>

</div>

</body>
</html>