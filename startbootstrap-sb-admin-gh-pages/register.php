<?php
require 'db.php'; // Kết nối CSDL

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $class = trim($_POST['class']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_in_class = $_POST['role_in_class'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra dữ liệu
    if (empty($user_id)) {
        $error = "Vui lòng nhập mã người dùng.";
    } elseif (empty($full_name)) {
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
    } elseif (empty($password)) {
        $error = "Vui lòng nhập mật khẩu.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Kiểm tra email hoặc user_id đã tồn tại chưa
        $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE email = ? OR user_id = ?");
        $stmt->bind_param("ss", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['email'] === $email) {
                $error = "Email đã tồn tại.";
            } elseif ($row['user_id'] === $user_id) {
                $error = "Mã người dùng đã tồn tại.";
            } else {
                $error = "Email hoặc mã người dùng đã tồn tại.";
            }
        } else {
            // Hash password và lưu vào DB
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (user_id, full_name, dob, gender, class, email, phone, role_in_class, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("sssssssss", $user_id, $full_name, $dob, $gender, $class, $email, $phone, $role_in_class, $hash);
            
            if ($insert->execute()) {
                if ($isAjax) {
                    $response['success'] = true;
                    $response['message'] = "Đăng ký thành công!";
                    echo json_encode($response);
                    exit;
                } else {
                    header("Location: login.php");
                    exit;
                }
            } else {
                $error = "Lỗi đăng ký: " . $conn->error;
            }
            $insert->close();
        }
        $stmt->close();
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
    <title>Đăng ký</title>
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
<body class="registerBody sb-nav-fixed">
    <div id="layoutSidenav_nav">
        <?php include 'navigation.php'; ?>
    </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'layout-sidenav.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <!-- Thông báo lỗi/thành công -->
                            <div id="alertContainer"></div>
                            
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Đăng ký tài khoản</h3></div>
                                <div class="card-body">
                                    <?php if (!empty($error) && !$isAjax): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    
                                    <form id="registerForm" method="POST" action="" novalidate>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputUserId" name="user_id" type="text" placeholder="Mã người dùng" required />
                                                    <label for="inputUserId">Mã người dùng</label>
                                                    <div class="error-feedback" id="userIdFeedback">Vui lòng nhập mã người dùng</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputClass" name="class" type="text" placeholder="Lớp" required />
                                                    <label for="inputClass">Lớp</label>
                                                    <div class="error-feedback" id="classFeedback">Vui lòng nhập lớp</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputFullName" name="full_name" type="text" placeholder="Họ và tên" required />
                                                    <label for="inputFullName">Họ và tên</label>
                                                    <div class="error-feedback" id="fullNameFeedback">Vui lòng nhập họ và tên</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputDob" name="dob" type="date" required />
                                                    <label for="inputDob">Ngày sinh</label>
                                                    <div class="error-feedback" id="dobFeedback">Vui lòng nhập ngày sinh</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" id="inputGender" name="gender" required>
                                                        <option value="">Chọn giới tính</option>
                                                        <option value="Nam">Nam</option>
                                                        <option value="Nữ">Nữ</option>
                                                    </select>
                                                    <label for="inputGender">Giới tính</label>
                                                    <div class="error-feedback" id="genderFeedback">Vui lòng chọn giới tính</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputEmail" name="email" type="email" placeholder="Email" required />
                                                    <label for="inputEmail">Email</label>
                                                    <div class="error-feedback" id="emailFeedback">Vui lòng nhập email hợp lệ</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputPhone" name="phone" type="text" placeholder="Điện thoại" required />
                                                    <label for="inputPhone">Điện thoại</label>
                                                    <div class="error-feedback" id="phoneFeedback">Vui lòng nhập số điện thoại</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <select class="form-select" id="inputRole" name="role_in_class" required>
                                                        <option value="">Chọn vai trò</option>
                                                        <option value="Student">Sinh viên</option>
                                                        <option value="Lecturer">Giảng viên</option>
                                                    </select>
                                                    <label for="inputRole">Vai trò</label>
                                                    <div class="error-feedback" id="roleFeedback">Vui lòng chọn vai trò</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Mật khẩu" required />
                                                    <label for="inputPassword">Mật khẩu</label>
                                                    <div class="error-feedback" id="passwordFeedback">Vui lòng nhập mật khẩu</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPasswordConfirm" name="confirm_password" type="password" placeholder="Xác nhận mật khẩu" required />
                                                    <label for="inputPasswordConfirm">Xác nhận mật khẩu</label>
                                                    <div class="error-feedback" id="confirmPasswordFeedback">Mật khẩu xác nhận không khớp</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 mb-0">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-block" type="submit" id="submitBtn">
                                                    <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status" aria-hidden="true"></span>
                                                    <span id="submitText">Tạo tài khoản</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small"><a href="login.php">Đã có tài khoản? Đăng nhập</a></div>
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
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const submitText = document.getElementById('submitText');
            const alertContainer = document.getElementById('alertContainer');
            
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
            
            // Hàm xác thực form phía client
            function validateForm() {
                let isValid = true;
                
                // Reset tất cả trạng thái lỗi
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
                
                // Kiểm tra từng trường
                if (!form.user_id.value.trim()) {
                    form.user_id.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.full_name.value.trim()) {
                    form.full_name.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.dob.value) {
                    form.dob.classList.add('is-invalid');
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
                }
                
                if (!form.phone.value.trim()) {
                    form.phone.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.role_in_class.value) {
                    form.role_in_class.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!form.password.value) {
                    form.password.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (form.password.value !== form.confirm_password.value) {
                    form.confirm_password.classList.add('is-invalid');
                    document.getElementById('confirmPasswordFeedback').textContent = 'Mật khẩu xác nhận không khớp';
                    isValid = false;
                }
                
                return isValid;
            }
            
            // Xử lý sự kiện submit form
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Xác thực form phía client
                if (!validateForm()) {
                    showAlert('Vui lòng kiểm tra lại thông tin đăng ký.');
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
                    submitText.textContent = 'Tạo tài khoản';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Đăng ký thành công
                                showAlert(response.message || 'Đăng ký thành công!', 'success');
                                
                                // Chuyển hướng sau 2 giây
                                setTimeout(() => {
                                    window.location.href = 'login.php';
                                }, 2000);
                            } else {
                                // Hiển thị lỗi từ server
                                showAlert(response.error || 'Đăng ký thất bại!');
                                
                                // Đánh dấu trường lỗi nếu có thông tin
                                if (response.error.includes('Email')) {
                                    form.email.classList.add('is-invalid');
                                    document.getElementById('emailFeedback').textContent = response.error;
                                }
                                
                                if (response.error.includes('người dùng')) {
                                    form.user_id.classList.add('is-invalid');
                                    document.getElementById('userIdFeedback').textContent = response.error;
                                }
                                
                                if (response.error.includes('Mật khẩu')) {
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
                    submitText.textContent = 'Tạo tài khoản';
                    
                    // Hiển thị lỗi kết nối
                    showAlert('Lỗi kết nối! Vui lòng kiểm tra kết nối internet và thử lại.');
                };
                
                xhr.send(formData);
            });
        });
    </script>
</body>
</html>
