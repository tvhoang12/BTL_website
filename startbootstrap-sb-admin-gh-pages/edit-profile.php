<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kiểm tra xem request có phải là AJAX không
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['success' => false, 'error' => '', 'message' => ''];

// Lấy thông tin người dùng hiện tại
$user = null;
$stmt = $conn->prepare("SELECT user_id, full_name, dob, gender, class, email, phone, role_in_class FROM users WHERE user_id = ?");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Chuyển đổi định dạng ngày sinh từ YYYY-MM-DD sang DD-MM-YYYY cho hiển thị
    if (!empty($user['dob'])) {
        $dob_date = new DateTime($user['dob']);
        $user['dob_formatted'] = $dob_date->format('d-m-Y');
    } else {
        $user['dob_formatted'] = '';
    }
} else {
    // Nếu không tìm thấy thông tin người dùng, chuyển hướng về trang chủ
    header("Location: index.php");
    exit;
}
$stmt->close();

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob']; // Nhận dạng DD-MM-YYYY từ form
    $gender = $_POST['gender'];
    $class = trim($_POST['class']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_in_class = $_POST['role_in_class'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Chuyển đổi định dạng ngày sinh từ DD-MM-YYYY sang YYYY-MM-DD để lưu vào DB
    if (!empty($dob)) {
        $dob_parts = explode('-', $dob);
        if (count($dob_parts) === 3) {
            $dob = $dob_parts[2] . '-' . $dob_parts[1] . '-' . $dob_parts[0]; // YYYY-MM-DD
        }
    }

    // Kiểm tra dữ liệu
    if (empty($full_name)) {
        $error = "Vui lòng nhập họ và tên.";
    } elseif (empty($dob)) {
        $error = "Vui lòng nhập ngày sinh.";
    } elseif (empty($gender)) {
        $error = "Vui lòng chọn giới tính.";
    } elseif (empty($class)) {
        $error = "Vui lòng nhập lớp.";
    } elseif (empty($email)) {
        $error = "Vui lòng nhập email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif (empty($phone)) {
        $error = "Vui lòng nhập số điện thoại.";
    } elseif (empty($role_in_class)) {
        $error = "Vui lòng chọn vai trò.";
    } else {
        // Kiểm tra email đã tồn tại chưa (nếu thay đổi email)
        if ($email !== $user['email']) {
            $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_email->bind_param("ss", $email, $_SESSION['user_id']);
            $check_email->execute();
            $check_email->store_result();
            
            if ($check_email->num_rows > 0) {
                $error = "Email đã được sử dụng bởi tài khoản khác.";
            }
            $check_email->close();
        }
        
        // Kiểm tra mật khẩu nếu người dùng muốn thay đổi
        if (!empty($new_password) || !empty($confirm_password)) {
            // Kiểm tra mật khẩu hiện tại
            if (empty($current_password)) {
                $error = "Vui lòng nhập mật khẩu hiện tại.";
            } else {
                $check_password = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                $check_password->bind_param("s", $_SESSION['user_id']);
                $check_password->execute();
                $check_password->bind_result($password_hash);
                $check_password->fetch();
                $check_password->close();
                
                if (!password_verify($current_password, $password_hash)) {
                    $error = "Mật khẩu hiện tại không đúng.";
                } elseif (empty($new_password)) {
                    $error = "Vui lòng nhập mật khẩu mới.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "Mật khẩu xác nhận không khớp.";
                }
            }
        }
        
        // Nếu không có lỗi, tiến hành cập nhật
        if (empty($error)) {
            // Cập nhật thông tin cơ bản
            $update = $conn->prepare("UPDATE users SET full_name = ?, dob = ?, gender = ?, class = ?, email = ?, phone = ?, role_in_class = ? WHERE user_id = ?");
            $update->bind_param("ssssssss", $full_name, $dob, $gender, $class, $email, $phone, $role_in_class, $_SESSION['user_id']);
            $update_success = $update->execute();
            $update->close();
            
            // Cập nhật mật khẩu nếu có
            if (!empty($new_password) && $update_success) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $update_password->bind_param("ss", $hash, $_SESSION['user_id']);
                $update_password->execute();
                $update_password->close();
            }
            
            if ($update_success) {
                // Cập nhật session nếu cần
                $_SESSION['full_name'] = $full_name;
                
                if ($isAjax) {
                    $response['success'] = true;
                    $response['message'] = "Cập nhật thông tin thành công!";
                    echo json_encode($response);
                    exit;
                } else {
                    header("Location: index.php?updated=1");
                    exit;
                }
            } else {
                $error = "Lỗi cập nhật thông tin: " . $conn->error;
            }
        }
    }
    
    // Xử lý lỗi
    if (!empty($error)) {
        if ($isAjax) {
            $response['error'] = $error;
            echo json_encode($response);
            exit;
        }
    }
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
    <title>Sửa thông tin cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .error-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        .is-invalid ~ .error-feedback {
            display: block;
        }
        .alert-float {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
    </style>
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
                    <h1 class="mt-4">Sửa thông tin cá nhân</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active">Sửa thông tin</li>
                    </ol>
                    
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <!-- Thông báo lỗi/thành công -->
                            <div id="alertContainer"></div>
                            
                            <div class="card shadow-lg border-0 rounded-lg mb-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">Thông tin cá nhân</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($error) && !$isAjax): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    
                                    <form id="editProfileForm" method="POST" action="" novalidate>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputUserId" name="user_id" type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly />
                                                    <label for="inputUserId">Mã người dùng</label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputFullName" name="full_name" type="text" placeholder="Họ và tên" value="<?= htmlspecialchars($user['full_name']) ?>" required />
                                                    <label for="inputFullName">Họ và tên</label>
                                                    <div class="error-feedback" id="fullNameFeedback">Vui lòng nhập họ và tên</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputDob" name="dob" type="text" placeholder="DD-MM-YYYY" value="<?= htmlspecialchars($user['dob_formatted']) ?>" required />
                                                    <label for="inputDob">Ngày sinh</label>
                                                    <div class="error-feedback" id="dobFeedback">Vui lòng nhập ngày sinh đúng định dạng DD-MM-YYYY</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" id="inputGender" name="gender" required>
                                                        <option value="">Chọn giới tính</option>
                                                        <option value="Nam" <?= $user['gender'] === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                                        <option value="Nữ" <?= $user['gender'] === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                                        <option value="Khác" <?= $user['gender'] === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                                    </select>
                                                    <label for="inputGender">Giới tính</label>
                                                    <div class="error-feedback" id="genderFeedback">Vui lòng chọn giới tính</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputEmail" name="email" type="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required />
                                                    <label for="inputEmail">Email</label>
                                                    <div class="error-feedback" id="emailFeedback">Vui lòng nhập email hợp lệ</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputPhone" name="phone" type="text" placeholder="Điện thoại" value="<?= htmlspecialchars($user['phone']) ?>" required />
                                                    <label for="inputPhone">Điện thoại</label>
                                                    <div class="error-feedback" id="phoneFeedback">Vui lòng nhập số điện thoại</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputClass" name="class" type="text" placeholder="Lớp" value="<?= htmlspecialchars($user['class']) ?>" required />
                                                    <label for="inputClass">Lớp</label>
                                                    <div class="error-feedback" id="classFeedback">Vui lòng nhập lớp</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" id="inputRole" name="role_in_class" required>
                                                        <option value="">Chọn vai trò</option>
                                                        <option value="Student" <?= $user['role_in_class'] === 'Student' ? 'selected' : '' ?>>Sinh viên</option>
                                                        <option value="Lecturer" <?= $user['role_in_class'] === 'Lecturer' ? 'selected' : '' ?>>Giảng viên</option>
                                                    </select>
                                                    <label for="inputRole">Vai trò</label>
                                                    <div class="error-feedback" id="roleFeedback">Vui lòng chọn vai trò</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h4 class="mt-4 mb-3">Đổi mật khẩu <small class="text-muted">(để trống nếu không muốn đổi)</small></h4>
                                        
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-floating mb-3">
                                                    <input class="form-control" id="inputCurrentPassword" name="current_password" type="password" placeholder="Mật khẩu hiện tại" />
                                                    <label for="inputCurrentPassword">Mật khẩu hiện tại</label>
                                                    <div class="error-feedback" id="currentPasswordFeedback">Vui lòng nhập mật khẩu hiện tại</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputNewPassword" name="new_password" type="password" placeholder="Mật khẩu mới" />
                                                    <label for="inputNewPassword">Mật khẩu mới</label>
                                                    <div class="error-feedback" id="newPasswordFeedback">Vui lòng nhập mật khẩu mới</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputConfirmPassword" name="confirm_password" type="password" placeholder="Xác nhận mật khẩu mới" />
                                                    <label for="inputConfirmPassword">Xác nhận mật khẩu mới</label>
                                                    <div class="error-feedback" id="confirmPasswordFeedback">Mật khẩu xác nhận không khớp</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 mb-0">
                                            <div class="d-flex justify-content-between">
                                                <a href="index.php" class="btn btn-secondary">Hủy</a>
                                                <button class="btn btn-primary" type="submit" id="submitBtn">
                                                    <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status" aria-hidden="true"></span>
                                                    <span id="submitText">Cập nhật thông tin</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
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
    <script src="js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editProfileForm');
            const submitBtn = document.getElementById('submitBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const submitText = document.getElementById('submitText');
            const alertContainer = document.getElementById('alertContainer');
            const dobInput = document.getElementById('inputDob');
            
            // Đảm bảo trường mật khẩu hiện tại trống
            document.getElementById('inputCurrentPassword').value = '';
            
            // Hàm hiển thị thông báo
            function showAlert(message, type = 'danger') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible alert-float fade show`;
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertContainer.appendChild(alert);
                
                // Tự động ẩn sau 5 giây
                setTimeout(() => {
                    alert.classList.remove('show');
                    setTimeout(() => {
                        alertContainer.removeChild(alert);
                    }, 150);
                }, 5000);
            }
            
            // Hàm kiểm tra định dạng ngày tháng DD-MM-YYYY
            function isValidDateFormat(dateStr) {
                // Kiểm tra định dạng DD-MM-YYYY
                const regex = /^(\d{2})-(\d{2})-(\d{4})$/;
                if (!regex.test(dateStr)) return false;
                
                const parts = dateStr.split('-');
                const day = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10);
                const year = parseInt(parts[2], 10);
                
                // Kiểm tra ngày, tháng hợp lệ
                if (month < 1 || month > 12) return false;
                if (day < 1 || day > 31) return false;
                
                // Kiểm tra số ngày trong tháng
                if (month === 2) {
                    // Tháng 2
                    const isLeapYear = (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
                    if (day > (isLeapYear ? 29 : 28)) return false;
                } else if ([4, 6, 9, 11].includes(month)) {
                    // Tháng 4, 6, 9, 11 có 30 ngày
                    if (day > 30) return false;
                }
                
                return true;
            }
            
            // Hàm xác thực form phía client
            function validateForm() {
                let isValid = true;
                
                // Reset tất cả trạng thái lỗi
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
                
                // Kiểm tra các trường bắt buộc
                if (!form.full_name.value.trim()) {
                    form.full_name.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Kiểm tra ngày sinh
                if (!form.dob.value.trim()) {
                    form.dob.classList.add('is-invalid');
                    document.getElementById('dobFeedback').textContent = 'Vui lòng nhập ngày sinh';
                    isValid = false;
                } else if (!isValidDateFormat(form.dob.value.trim())) {
                    form.dob.classList.add('is-invalid');
                    document.getElementById('dobFeedback').textContent = 'Ngày sinh không đúng định dạng DD-MM-YYYY';
                    isValid = false;
                }
                
                if (!form.gender.value) {
                    form.gender.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.class.value.trim()) {
                    form.class.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.email.value.trim()) {
                    form.email.classList.add('is-invalid');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.value.trim())) {
                    form.email.classList.add('is-invalid');
                    document.getElementById('emailFeedback').textContent = 'Email không hợp lệ';
                    isValid = false;
                }
                
                if (!form.phone.value.trim()) {
                    form.phone.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.role_in_class.value) {
                    form.role_in_class.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Kiểm tra mật khẩu nếu người dùng muốn thay đổi
                const newPassword = form.new_password.value;
                const confirmPassword = form.confirm_password.value;
                
                if (newPassword || confirmPassword) {
                    if (!form.current_password.value) {
                        form.current_password.classList.add('is-invalid');
                        document.getElementById('currentPasswordFeedback').textContent = 'Vui lòng nhập mật khẩu hiện tại';
                        isValid = false;
                    }
                    
                    if (!newPassword) {
                        form.new_password.classList.add('is-invalid');
                        document.getElementById('newPasswordFeedback').textContent = 'Vui lòng nhập mật khẩu mới';
                        isValid = false;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        form.confirm_password.classList.add('is-invalid');
                        document.getElementById('confirmPasswordFeedback').textContent = 'Mật khẩu xác nhận không khớp';
                        isValid = false;
                    }
                }
                
                return isValid;
            }
            
            // Xử lý sự kiện submit form
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Xác thực form phía client
                if (!validateForm()) {
                    showAlert('Vui lòng kiểm tra lại thông tin.');
                    return;
                }
                
                // Hiển thị trạng thái loading
                submitBtn.disabled = true;
                loadingSpinner.classList.remove('d-none');
                submitText.textContent = 'Đang xử lý...';
                
                // Gửi dữ liệu form qua AJAX
                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();
                
                xhr.open('POST', '', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onload = function() {
                    // Khôi phục trạng thái nút submit
                    submitBtn.disabled = false;
                    loadingSpinner.classList.add('d-none');
                    submitText.textContent = 'Cập nhật thông tin';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Cập nhật thành công
                                showAlert(response.message || 'Cập nhật thông tin thành công!', 'success');
                                
                                // Xóa các trường mật khẩu
                                form.current_password.value = '';
                                form.new_password.value = '';
                                form.confirm_password.value = '';
                            } else {
                                // Hiển thị lỗi từ server
                                showAlert(response.error || 'Cập nhật thất bại!');
                                
                                // Đánh dấu trường lỗi nếu có thông tin
                                if (response.error.includes('Email')) {
                                    form.email.classList.add('is-invalid');
                                    document.getElementById('emailFeedback').textContent = response.error;
                                }
                                
                                if (response.error.includes('Mật khẩu hiện tại')) {
                                    form.current_password.classList.add('is-invalid');
                                    document.getElementById('currentPasswordFeedback').textContent = response.error;
                                }
                                
                                if (response.error.includes('Mật khẩu xác nhận')) {
                                    form.confirm_password.classList.add('is-invalid');
                                    document.getElementById('confirmPasswordFeedback').textContent = response.error;
                                }
                            }
                        } catch (e) {
                            // Lỗi khi parse JSON
                            showAlert('Lỗi hệ thống! Vui lòng thử lại sau.');
                            console.error('Error parsing JSON:', e);
                        }
                    } else {
                        // Lỗi HTTP
                        showAlert('Lỗi kết nối! Vui lòng thử lại sau.');
                    }
                };
                
                xhr.onerror = function() {
                    // Khôi phục trạng thái nút submit
                    submitBtn.disabled = false;
                    loadingSpinner.classList.add('d-none');
                    submitText.textContent = 'Cập nhật thông tin';
                    
                    // Hiển thị lỗi kết nối
                    showAlert('Lỗi kết nối! Vui lòng kiểm tra kết nối internet và thử lại.');
                };
                
                xhr.send(formData);
            });
        });
    </script>
</body>
</html>
