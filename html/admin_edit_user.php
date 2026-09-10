<?php
session_start();
require_once "db_connect.php";

/* =========================
   ตรวจสอบสิทธิ์ Admin
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    echo "<script>
        alert('ไม่มีสิทธิ์เข้าหน้านี้');
        window.location.href='index2.php';
    </script>";
    exit;
}


/* =========================
   รับ ID ผู้ใช้
========================= */

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: admin.php");
    exit;
}


/* =========================
   ดึงข้อมูลผู้ใช้
========================= */

$stmt = $conn->prepare("
    SELECT id, username, first_name, last_name,
           email, role, department
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {
    echo "<script>
        alert('ไม่พบผู้ใช้งาน');
        window.location.href='admin.php';
    </script>";
    exit;
}


$error = "";


/* =========================
   เมื่อกดบันทึก
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $role       = trim($_POST["role"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $password   = $_POST["password"] ?? "";


    /* ตรวจสอบข้อมูล */

    if (
        $first_name === "" ||
        $last_name === "" ||
        $email === ""
    ) {

        $error = "กรุณากรอกข้อมูลให้ครบ";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "รูปแบบ Email ไม่ถูกต้อง";

    } elseif (!in_array($role, ["student", "teacher", "admin"], true)) {

        $error = "Role ไม่ถูกต้อง";

    }


    /* =========================
       เช็ก Email ซ้ำ
    ========================= */

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "si",
            $email,
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email นี้ถูกใช้งานแล้ว";
        }

        $stmt->close();
    }


    /* =========================
       บันทึกข้อมูล
    ========================= */

    if ($error === "") {

        if ($password !== "") {

            /* ถ้าใส่ Password ใหม่ */

            if (strlen($password) < 4) {

                $error =
                    "Password ต้องมีอย่างน้อย 4 ตัวอักษร";

            } else {

                $hashed_password =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        first_name = ?,
                        last_name = ?,
                        email = ?,
                        role = ?,
                        department = ?,
                        password = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ssssssi",
                    $first_name,
                    $last_name,
                    $email,
                    $role,
                    $department,
                    $hashed_password,
                    $user_id
                );

                $saved = $stmt->execute();

                $stmt->close();
            }

        } else {

            /* ถ้าไม่ใส่ Password
               จะใช้ Password เดิม */

            $stmt = $conn->prepare("
                UPDATE users
                SET
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    role = ?,
                    department = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssssi",
                $first_name,
                $last_name,
                $email,
                $role,
                $department,
                $user_id
            );

            $saved = $stmt->execute();

            $stmt->close();
        }


        /* =========================
           อัปเดต Session ถ้าแก้บัญชีตัวเอง
        ========================= */

        if ($error === "" && $saved) {

            if (
                isset($_SESSION['user_id']) &&
                (int)$_SESSION['user_id'] === $user_id
            ) {

                $_SESSION['role'] = $role;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['department'] = $department;
            }


            echo "<script>
                alert('แก้ไขผู้ใช้สำเร็จ');
                window.location.href='admin.php';
            </script>";

            exit;

        } elseif ($error === "") {

            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }


    /* เอาข้อมูลที่กรอกกลับมาแสดง */

    $user['first_name'] = $first_name;
    $user['last_name'] = $last_name;
    $user['email'] = $email;
    $user['role'] = $role;
    $user['department'] = $department;
}

?>


<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>แก้ไขผู้ใช้ - Admin</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                Arial,
                "Sarabun",
                sans-serif;

            background: #f5f7fa;
        }


        /* HEADER */

        .header {

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6,
                    #3287bb
                );

            color: white;

            padding: 18px 40px;

            display: flex;

            justify-content:
                space-between;

            align-items: center;
        }

        .header h2 {

            margin: 0;

            font-size: 22px;
        }

        .header a {

            color: white;

            text-decoration: none;

            background:
                rgba(255,255,255,.18);

            padding: 10px 15px;

            border-radius: 8px;
        }


        /* CONTAINER */

        .container {

            max-width: 700px;

            margin: 40px auto;

            padding: 0 20px;
        }


        /* BOX */

        .box {

            background: white;

            border-radius: 15px;

            padding: 30px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.08);
        }

        .box h2 {

            margin-top: 0;

            color: #2879ad;

            margin-bottom: 25px;
        }


        /* USERNAME */

        .username-box {

            background: #f1f5f8;

            padding: 12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;

            color: #555;
        }

        .username-box strong {

            color: #2879ad;
        }


        /* FORM */

        .form-group {

            margin-bottom: 18px;
        }

        label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            color: #444;
        }

        input,
        select {

            width: 100%;

            padding: 12px 14px;

            border:
                1px solid #ddd;

            border-radius: 8px;

            font-size: 15px;

            outline: none;
        }

        input:focus,
        select:focus {

            border-color: #4297cd;

            box-shadow:
                0 0 0 3px
                rgba(66,151,205,.12);
        }


        /* GRID */

        .grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;
        }


        /* ERROR */

        .error {

            background: #ffe6e6;

            color: #b00000;

            padding: 12px;

            border-radius: 8px;

            margin-bottom: 20px;

            text-align: center;
        }


        /* PASSWORD NOTE */

        .password-note {

            font-size: 13px;

            color: #777;

            margin-top: 6px;
        }


        /* BUTTON */

        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 25px;
        }

        .btn {

            flex: 1;

            border: none;

            padding: 12px;

            border-radius: 8px;

            text-decoration: none;

            text-align: center;

            cursor: pointer;

            font-size: 15px;
        }

        .save {

            background: #198754;

            color: white;
        }

        .cancel {

            background: #6c757d;

            color: white;
        }

        .btn:hover {

            opacity: .9;
        }


        @media (max-width: 600px) {

            .grid {

                grid-template-columns: 1fr;
            }

            .header {

                padding:
                    15px 20px;
            }

        }

    </style>

</head>


<body>


<!-- HEADER -->

<div class="header">

    <h2>

        <i class="bi bi-person-gear"></i>

        แก้ไขผู้ใช้งาน

    </h2>


    <a href="admin.php">

        <i class="bi bi-arrow-left"></i>

        กลับ Admin

    </a>

</div>


<div class="container">

    <div class="box">

        <h2>

            <i class="bi bi-person-gear"></i>

            แก้ไขข้อมูลผู้ใช้งาน

        </h2>


        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="bi bi-exclamation-circle"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- USERNAME -->

        <div class="username-box">

            <i class="bi bi-person"></i>

            Username:

            <strong>

                <?= htmlspecialchars($user['username']) ?>

            </strong>

        </div>


        <form method="POST">


            <!-- NAME -->

            <div class="grid">

                <div class="form-group">

                    <label>

                        ชื่อ

                    </label>

                    <input
                        type="text"
                        name="first_name"
                        value="<?= htmlspecialchars($user['first_name']) ?>"
                        required>

                </div>


                <div class="form-group">

                    <label>

                        นามสกุล

                    </label>

                    <input
                        type="text"
                        name="last_name"
                        value="<?= htmlspecialchars($user['last_name']) ?>"
                        required>

                </div>

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label>

                    Email

                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($user['email']) ?>"
                    required>

            </div>


            <!-- ROLE -->

            <div class="form-group">

                <label>

                    Role

                </label>

                <select name="role" required>

                    <option value="student"
                        <?= $user['role'] === 'student' ? 'selected' : '' ?>>

                        Student

                    </option>

                    <option value="teacher"
                        <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>

                        Teacher

                    </option>

                    <option value="admin"
                        <?= $user['role'] === 'admin' ? 'selected' : '' ?>>

                        Admin

                    </option>

                </select>

            </div>


            <!-- DEPARTMENT -->

            <div class="form-group">

                <label>

                    สาขา

                </label>

                <input
                    type="text"
                    name="department"
                    value="<?= htmlspecialchars($user['department'] ?? '') ?>"
                    placeholder="เช่น เทคโนโลยีสารสนเทศ">

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label>

                    Password ใหม่

                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยน">

                <div class="password-note">

                    <i class="bi bi-info-circle"></i>

                    ถ้าไม่กรอก จะใช้ Password เดิม

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <a
                    href="admin.php"
                    class="btn cancel">

                    <i class="bi bi-x-circle"></i>

                    ยกเลิก

                </a>


                <button
                    type="submit"
                    class="btn save">

                    <i class="bi bi-check-circle"></i>

                    บันทึกการแก้ไข

                </button>

            </div>


        </form>

    </div>

</div>


</body>

</html>