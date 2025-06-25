<?php
require 'db.php';
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    // Xóa các bản ghi liên quan trong bảng grades trước
    $stmt = $conn->prepare("DELETE FROM grades WHERE student_id=?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->close();

    // Sau đó mới xóa sinh viên
    $stmt2 = $conn->prepare("DELETE FROM students WHERE student_id=?");
    $stmt2->bind_param("s", $student_id);
    $stmt2->execute();
    $stmt2->close();
}
header("Location: student-tables.php");
exit;
?>