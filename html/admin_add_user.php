<?php
session_start();
require_once "db_connect.php";

/* =========================
   ตรวจสอบ Admin
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

$error = "";
$success = "";


/* =========================
   เพิ่มผู้ใช้
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username   = trim($_POST["username"] ?? "");
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $role       = trim($_POST["role"] ?? "student");
    $department = trim($_POST["department"] ?? "");
    $password   = $_POST["password"] ?? "";


    /* ตรวจสอบข้อมูล */

    if (
        $username === "" ||
        $first_name === "" ||
        $last_name === "" ||
        $email === "" ||
        $password === ""
    ) {

        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";

    } elseif (!in_array($role, ["student", "teacher", "admin"], true)) {

        $error = "Role ไม่ถูกต้อง";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "รูปแบบ Email ไม่ถูกต้อง";

    } elseif (strlen($password) < 4) {

        $error = "Password ต้องมีอย่างน้อย 4 ตัวอักษร";

    } else {

        /* =========================
           เช็ก Username ซ้ำ
        ========================= */

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "Username นี้มีอยู่แล้ว";

        }

        $stmt->close();


        /* =========================
           เช็ก Email ซ้ำ
        ========================= */

        if ($error === "") {

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "Email นี้มีอยู่แล้ว";

            }

            $stmt->close();
        }


        /* =========================
           เพิ่มข้อมูล
        ========================= */

        if ($error === "") {

            /* Hash Password */

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    username,
                    first_name,
                    last_name,
                    email,
                    role,
                    department,
                    password
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssssss",
                $username,
                $first_name,
                $last_name,
                $email,
                $role,
                $department,
                $hashed_password
            );


            if ($stmt->execute()) {

                $stmt->close();

                echo "<script>
                    alert('เพิ่มผู้ใช้สำเร็จ');
                    window.location.href='admin.php';
                </script>";

                exit;

            } else {

                $error =
                    "เกิดข้อผิดพลาด: " .
                    $stmt->error;

                $stmt->close();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>เพิ่มผู้ใช้ - Admin</title>

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

            background: #f4f8fb;
        }


        /* HEADER */

        .header {

            background:
                linear-gradient(
                    90deg,
                    #4aa4d6,
                    #3287BB
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
        }

        .header a {

            color: white;

            text-decoration: none;

            background:
                rgba(255,255,255,.18);

            padding:
                10px 15px;

            border-radius: 8px;
        }


        /* CONTAINER */

        .container {

            max-width: 700px;

            margin: 40px auto;

            padding:
                0 20px;
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

            color: #287cab;

            margin-bottom: 25px;
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


        /* BUTTONS */

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


        /* RESPONSIVE */

        @media (max-width: 600px) {

            .grid {

                grid-template-columns:
                    1fr;
            }

            .header {

                padding:
                    15px 20px;

                gap: 10px;
            }

        }

    </style>

</head>

<body>


<!-- HEADER -->

<div class="header">

    <h2>

        <i class="bi bi-person-plus-fill"></i>

        เพิ่มผู้ใช้งาน

    </h2>


    <a href="admin.php">

        <i class="bi bi-arrow-left"></i>

        กลับ Admin

    </a>

</div>


<div class="container">

    <div class="box">

        <h2>

            <i class="bi bi-person-plus-fill"></i>

            สร้างบัญชีผู้ใช้งาน

        </h2>


        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="bi bi-exclamation-circle"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- USERNAME -->

            <div class="form-group">

                <label>

                    Username

                </label>

                <input
                    type="text"
                    name="username"
                    placeholder="เช่น student01"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required>

            </div>


            <!-- NAME -->

            <div class="grid">

                <div class="form-group">

                    <label>

                        ชื่อ

                    </label>

                    <input
                        type="text"
                        name="first_name"
                        placeholder="ชื่อ"
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                        required>

                </div>


                <div class="form-group">

                    <label>

                        นามสกุล

                    </label>

                    <input
                        type="text"
                        name="last_name"
                        placeholder="นามสกุล"
                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
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
                    placeholder="example@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>

            </div>


            <!-- ROLE -->

            <div class="form-group">

                <label>

                    Role

                </label>

                <select name="role" required>

                    <option value="student"
                        <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>

                        Student

                    </option>

                    <option value="teacher"
                        <?= (($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>

                        Teacher

                    </option>

                    <option value="admin"
                        <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>

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
                    placeholder="เช่น เทคโนโลยีสารสนเทศ"
                    value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label>

                    Password

                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="กำหนดรหัสผ่าน"
                    required>

            </div>


            <!-- BUTTON -->

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

                    เพิ่มผู้ใช้

                </button>

            </div>


        </form>

    </div>

</div>

</body>

</html>