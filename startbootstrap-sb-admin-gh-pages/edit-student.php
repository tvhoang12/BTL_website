<?php
require 'db.php';

if (!isset($_GET['user_id'])) {
    header("Location: student-tables.php");
    exit;
}

$user_id = $_GET['user_id'];
$error = '';
$success = '';

// Lấy thông tin sinh viên
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    if (!$student) {
        $error = "Không tìm thấy sinh viên!";
    }
    $stmt->close();
} else {
    // Cập nhật thông tin sinh viên
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $class = $_POST['class'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, dob=?, gender=?, class=?, email=?, phone=? WHERE user_id=?");
    $stmt->bind_param("sssssss", $full_name, $dob, $gender, $class, $email, $phone, $user_id);
    if ($stmt->execute()) {
        $success = "Cập nhật thành công!";
        // Lấy lại thông tin mới
        $student = [
            'user_id' => $user_id,
            'full_name' => $full_name,
            'dob' => $dob,
            'gender' => $gender,
            'class' => $class,
            'email' => $email,
            'phone' => $phone
        ];
    } else {
        $error = "Cập nhật thất bại!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thông tin sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2>Sửa thông tin sinh viên</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($student)): ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Mã sinh viên</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student['user_id']) ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Họ tên</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($student['dob']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-control" required>
                <option value="Nam" <?= $student['gender']=='Nam'?'selected':'' ?>>Nam</option>
                <option value="Nữ" <?= $student['gender']=='Nữ'?'selected':'' ?>>Nữ</option>
                <option value="Khác" <?= $student['gender']=='Khác'?'selected':'' ?>>Khác</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Lớp</label>
            <input type="text" name="class" class="form-control" value="<?= htmlspecialchars($student['class']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Điện thoại</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        <a href="student-tables.php" class="btn btn-secondary">Quay lại</a>
    </form>
    <?php endif; ?>
</div>
</body>
</html>