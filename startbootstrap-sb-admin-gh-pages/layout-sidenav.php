<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = null;
if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    $stmt = $conn->prepare("SELECT role_in_class FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($role_in_class);
    if ($stmt->fetch()) {
        $role = $role_in_class;
    }
    $stmt->close();
}
?>
<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <?php if ($role === 'Student'): ?>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                            Trang chủ
                        </a>
                        <a class="nav-link" href="personal-grades.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                            Bảng điểm cá nhân
                        </a>
                        <a class="nav-link" href="internship-register.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-briefcase"></i></div>
                            Đăng ký thực tập
                        </a>
                    <?php elseif ($role === 'Lecturer'): ?>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                            Trang chủ
                        </a>
                        <a class="nav-link" href="student-grades.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                            Bảng điểm SV
                        </a>
                        <a class="nav-link" href="student-tables.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                            Quản lý Sinh viên trong lớp
                        </a>
                        <a class="nav-link" href="company-list.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                            Danh sách công ty thực tập
                        </a>
                        <a class="nav-link" href="internship-result.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-user-check"></i></div>
                            DS SV trúng thực tập
                        </a>
                    <?php elseif ($role === 'Admin'): ?>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                            Trang chủ
                        </a>
                        <a class="nav-link" href="student-tables.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                            Quản lý CSDL
                        </a>
                        <a class="nav-link" href="company-list.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                            Danh sách công ty thực tập
                        </a>

                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="logout.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
                            Đăng xuất
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-sign-in-alt"></i></div>
                            Đăng nhập
                        </a>
                        <a class="nav-link" href="register.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-user-plus"></i></div>
                            Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Logged in as:</div>
                Quản lý sinh viên
            </div>
        </nav>
    </div>
</div>