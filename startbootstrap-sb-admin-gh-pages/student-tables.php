<?php
session_start();
require 'db.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Lấy danh sách sinh viên trong lớp
$students = [];

if (isset($_SESSION['role_in_class']) and $_SESSION['role_in_class']=='Admin') {
    $stmt = $conn->prepare("SELECT user_id, full_name, dob, gender, class, email, phone FROM users WHERE role_in_class='Student' or role_in_class='Lecturer'");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
} else {
    // Lấy lớp của giảng viên
    $stmt = $conn->prepare("SELECT class FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($lecturer_class);
    $stmt->fetch();
    $stmt->close();
    // Lấy danh sách sinh viên trong lớp
    $stmt = $conn->prepare("SELECT user_id, full_name, dob, gender, class, email, phone FROM users WHERE role_in_class='Student' AND class = ?");
    $stmt->bind_param("s", $lecturer_class);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Tables - SB Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            .dataTable-input {
                display: none !important;
            }
        </style>
    </head>
    <body class="sb-nav-fixed">

        <div id="layoutSidenav_nav">
            <?php include 'navigation.php'; ?>
        </div>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <?php include 'layout-sidenav.php' ?>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Bảng danh sách sinh viên</h1>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Danh sách sinh viên

                                <button class="btn btn-primary float-end" href="add-student.php">Thêm sinh viên</button>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-auto">
                                        <select id="columnSelect" class="form-select">
                                            <option value="0">Mã SV</option>
                                            <option value="1">Họ tên</option>
                                            <option value="2">Ngày sinh</option>
                                            <option value="3">Giới tính</option>
                                            <option value="5">Email</option>
                                            <option value="6">Điện thoại</option>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <input type="text" id="columnSearch" class="form-control" placeholder="Tìm kiếm...">
                                    </div>
                                </div>
                                <table id="studentTable" class="display">
                                    <thead>
                                        <tr>
                                            <th>Mã SV</th>
                                            <th>Họ tên</th>
                                            <th>Ngày sinh</th>
                                            <th>Lớp</th>
                                            <th>Giới tính</th>
                                            <th>Email</th>
                                            <th>Điện thoại</th>
                                            <th>Sửa</th>
                                            <th>Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $g): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($g['user_id']) ?></td>
                                                <td><?= htmlspecialchars($g['full_name']) ?></td>
                                                <td><?= htmlspecialchars($g['dob']) ?></td>
                                                <td><?= htmlspecialchars($g['class']) ?></td>
                                                <td><?= htmlspecialchars($g['gender']) ?></td>
                                                <td><?= htmlspecialchars($g['email']) ?></td>
                                                <td><?= htmlspecialchars($g['phone']) ?></td>
                                                <td>
                                                    <a href="edit-student.php?user_id=<?= urlencode($g['user_id']) ?>" class="btn btn-sm btn-warning">Sửa</a>
                                                </td>
                                                <td>
                                                    <a href="delete-student.php?user_id=<?= urlencode($g['user_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa sinh viên này?');">Xóa</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; Your Website 2023</div>
                            <div>
                                <a href="#">Privacy Policy</a>
                                &middot;
                                <a href="#">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        <script>
            $(document).ready(function() {
                var table = $('#studentTable').DataTable({
                    dom: 'lrtip' // Ẩn search mặc định
                });

                $('#columnSearch').on('keyup', function() {
                    var colIdx = $('#columnSelect').val();
                    table.column(colIdx).search(this.value).draw();
                });

                $('#columnSelect').on('change', function() {
                    $('#columnSearch').val('');
                    table.columns().search('');
                    table.column(this.value).search($('#columnSearch').val()).draw();
                });
            });
        </script>
    </body>
</html>
