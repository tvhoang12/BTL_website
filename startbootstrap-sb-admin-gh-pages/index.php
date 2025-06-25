<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

$user = null;
$isBirthday = false; // Biến kiểm tra có phải ngày sinh nhật không

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT full_name, role_in_class, class, dob FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($full_name, $role_in_class, $class, $dob);
    if ($stmt->fetch()) {
        $user = [
            'fullname' => $full_name,
            'role' => $role_in_class,
            'class' => $class,
            'dob' => $dob
        ];
        
        // Kiểm tra xem hôm nay có phải là sinh nhật không
        if (!empty($dob)) {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            
            // So sánh ngày và tháng (không quan tâm năm)
            if ($birthDate->format('m-d') === $today->format('m-d')) {
                $isBirthday = true;
                $age = $today->format('Y') - $birthDate->format('Y');
            }
        }
    }
    $stmt->close();
    

echo "DOB: " . $user['dob'] . "<br>";
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
        <title>Quản lý sinh viên</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <?php include 'navigation.php'; ?>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <?php include 'layout-sidenav.php'; ?>
            </div>
            <div id="layoutSidenav_content">
                <main>
                <div class="container-fluid px-4">
                    <h2 class="mt-4">Chào mừng đến với Ứng dụng Quản lý Sinh viên</h2>
                    
                    <?php if ($isBirthday && $user): ?>
                    <!-- Banner chúc mừng sinh nhật -->
                    <div class="birthday-banner" id="birthdayBanner">
                        <button type="button" class="birthday-close" onclick="document.getElementById('birthdayBanner').style.display='none';">×</button>
                        <div class="d-flex align-items-center">
                            <span class="birthday-icon">🎂</span>
                            <div>
                                <h3>Chúc mừng sinh nhật <?php echo htmlspecialchars($user['fullname']); ?>! 🎉</h3>
                                <p>Chúc bạn có một ngày sinh nhật thật vui vẻ và hạnh phúc. Chúc bạn tuổi <?php echo $age; ?> thật nhiều sức khỏe, niềm vui và thành công!</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <section class="py-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Thông tin cá nhân</h4>
                                <?php if ($user): ?>
                                    <p><strong>Họ và tên:</strong> <?= htmlspecialchars($user['fullname']) ?></p>
                                    <p><strong>Vai trò:</strong> <?= htmlspecialchars($user['role']) ?></p>
                                    <p><strong>Lớp hiện tại:</strong> <?= htmlspecialchars($user['class']) ?></p>
                                <?php else: ?>
                                    <p>Bạn chưa đăng nhập.</p>
                                <?php endif; ?>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <button class="btn btn-primary w-100" onclick="window.location.href='edit-profile.php'">Đổi thông tin</button>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-danger w-100" onclick="window.location.href='logout.php'">Đăng xuất</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h4 class="card-title mt-4">Tiện ích theo dõi & thông kê</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="dashboard.php" class="text-decoration-none">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-tachometer-alt fa-2x text-success"></i>
                                            <h5 class="card-title">Bảng theo dõi</h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="grades.php" class="text-decoration-none">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-table fa-2x text-success"></i>
                                            <h5 class="card-title">Bảng điểm</h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="student-tables.php" class="text-decoration-none">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-database fa-2x text-success"></i>
                                            <h5 class="card-title">Quản lý CSDL</h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </section>
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
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        
        <?php if ($isBirthday): ?>
        <script>
            // Script để tạo hiệu ứng confetti
            document.addEventListener('DOMContentLoaded', function() {
                const confettiContainer = document.getElementById('confettiContainer');
                const colors = ['#f2d74e', '#95c3de', '#ff7e5f', '#80ff72', '#f08080', '#95a5a6'];
                
                // Tạo 50 confetti
                for (let i = 0; i < 50; i++) {
                    createConfetti();
                }
                
                function createConfetti() {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    
                    // Vị trí ngẫu nhiên
                    const left = Math.random() * 100;
                    
                    // Màu ngẫu nhiên
                    const color = colors[Math.floor(Math.random() * colors.length)];
                    
                    // Kích thước ngẫu nhiên
                    const size = Math.random() * 10 + 5;
                    
                    // Tốc độ rơi ngẫu nhiên
                    const duration = Math.random() * 3 + 2;
                    
                    // Độ trễ ngẫu nhiên
                    const delay = Math.random() * 5;
                    
                    confetti.style.left = left + '%';
                    confetti.style.backgroundColor = color;
                    confetti.style.width = size + 'px';
                    confetti.style.height = size + 'px';
                    confetti.style.animationDuration = duration + 's';
                    confetti.style.animationDelay = delay + 's';
                    
                    confettiContainer.appendChild(confetti);
                    
                    // Xóa confetti sau khi animation kết thúc
                    setTimeout(() => {
                        confetti.remove();
                        // Tạo confetti mới để duy trì hiệu ứng
                        createConfetti();
                    }, (duration + delay) * 1000);
                }
                
                // Lưu trạng thái đã hiển thị banner vào localStorage
                const today = new Date().toISOString().split('T')[0];
                localStorage.setItem('birthdayBannerShown', today);
            });
        </script>
        <?php endif; ?>
    </body>
</html>
