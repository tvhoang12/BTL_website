<?php
require 'db.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $class = trim($_POST['class']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Kiểm tra trùng mã sinh viên
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id=?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = "Mã sinh viên đã tồn tại!";
    } else {
        $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, dob, gender, class, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $student_id, $full_name, $dob, $gender, $class, $email, $phone);
        if ($stmt->execute()) {
            $success = "Thêm sinh viên thành công!";
            header("Location: student-tables.php");
            exit;
        } else {
            $error = "Lỗi khi thêm sinh viên!";
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2>Thêm sinh viên</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Mã sinh viên</label>
            <input type="text" name="student_id" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Họ tên</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-control" required>
                <option value="Nam">Nam</option>
                <option value="Nữ">Nữ</option>
                <option value="Khác">Khác</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Lớp</label>
            <input type="text" name="class" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Điện thoại</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Thêm sinh viên</button>
        <a href="student-tables.php" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
</body>
</html>