<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = basename($_FILES["project_file"]["name"]);
    $target_file = $target_dir . time() . "_" . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    $uploadOk = 1;
    
    if ($file_type != "pdf") {
        echo "ขออภัย อนุญาตให้อัปโหลดเฉพาะไฟล์ PDF เท่านั้น";
        $uploadOk = 0;
    }
    
    if ($_FILES["project_file"]["size"] > 25 * 1024 * 1024) {
        echo "ขออภัย ไฟล์มีขนาดใหญ่เกิน 25MB";
        $uploadOk = 0;
    }
    
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["project_file"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO projects (project_name, description, file_path) VALUES ('$project_name', '$description', '$target_file')";
            if (mysqli_query($conn, $sql)) {
                echo "อัปโหลดและบันทึกข้อมูลสำเร็จ <a href='index2.php'>กลับหน้าหลัก</a>";
            } else {
                echo "Database Error: " . mysqli_error($conn);
            }
        } else {
            echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        }
    }
}
?>