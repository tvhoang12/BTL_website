<?php
session_start();
require 'db.php';

// Kiểm tra xem request có phải là AJAX không
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['success' => false, 'error' => '', 'redirect' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ email và mật khẩu.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $full_name, $password_hash);
            $stmt->fetch();
            if (password_verify($password, $password_hash)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['full_name'] = $full_name;
                
                if ($isAjax) {
                    $response['success'] = true;
                    $response['redirect'] = 'index.php';
                    echo json_encode($response);
                    exit;
                } else {
                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = "Mật khẩu không đúng.";
            }
        } else {
            $error = "Email không tồn tại.";
        }
        $stmt->close();
    }
    
    // Xử lý lỗi cho AJAX request
    if ($isAjax && !empty($error)) {
        $response['error'] = $error;
        echo json_encode($response);
        exit;
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
        <title>Đăng nhập</title>
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
            .login-form-container {
                max-width: 450px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body class="sb-nav-fixed bg-primary">
        <?php include 'navigation.php'; ?>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <?php include 'layout-sidenav.php'; ?>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-5">
                                <!-- Thông báo lỗi/thành công -->
                                <div id="alertContainer"></div>
                                
                                <div class="card shadow-lg border-0 rounded-lg mt-5 login-form-container">
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Đăng nhập</h3></div>
                                    <div class="card-body">
                                        <?php if (!empty($error) && !$isAjax): ?>
                                            <div class="alert alert-danger"><?php echo $error; ?></div>
                                        <?php endif; ?>
                                        
                                        <form id="loginForm" method="POST" action="" novalidate>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputEmail" name="email" type="email" placeholder="Email" required />
                                                <label for="inputEmail">Email</label>
                                                <div class="error-feedback" id="emailFeedback">Vui lòng nhập email hợp lệ</div>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Mật khẩu" required />
                                                <label for="inputPassword">Mật khẩu</label>
                                                <div class="error-feedback" id="passwordFeedback">Vui lòng nhập mật khẩu</div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                                <a class="small" href="#">Quên mật khẩu?</a>
                                                <button class="btn btn-primary" type="submit" id="submitBtn">
                                                    <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status" aria-hidden="true"></span>
                                                    <span id="submitText">Đăng nhập</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="card-footer text-center py-3">
                                        <div class="small"><a href="register.php">Chưa có tài khoản? Đăng ký!</a></div>
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
                const form = document.getElementById('loginForm');
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
                    const inputs = form.querySelectorAll('input');
                    inputs.forEach(input => {
                        input.classList.remove('is-invalid');
                    });
                    
                    // Kiểm tra email
                    const email = form.email.value.trim();
                    if (!email) {
                        form.email.classList.add('is-invalid');
                        document.getElementById('emailFeedback').textContent = 'Vui lòng nhập email';
                        isValid = false;
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        form.email.classList.add('is-invalid');
                        document.getElementById('emailFeedback').textContent = 'Email không hợp lệ';
                        isValid = false;
                    }
                    
                    // Kiểm tra mật khẩu
                    if (!form.password.value) {
                        form.password.classList.add('is-invalid');
                        document.getElementById('passwordFeedback').textContent = 'Vui lòng nhập mật khẩu';
                        isValid = false;
                    }
                    
                    return isValid;
                }
                
                // Xử lý sự kiện submit form
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Xác thực form phía client
                    if (!validateForm()) {
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
                        submitText.textContent = 'Đăng nhập';
                        
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                
                                if (response.success) {
                                    // Đăng nhập thành công
                                    showAlert('Đăng nhập thành công! Đang chuyển hướng...', 'success');
                                    
                                    // Chuyển hướng sau 1 giây
                                    setTimeout(() => {
                                        window.location.href = response.redirect || 'index.php';
                                    }, 1000);
                                } else {
                                    // Hiển thị lỗi từ server
                                    showAlert(response.error || 'Đăng nhập thất bại!');
                                    
                                    // Đánh dấu trường lỗi nếu có thông tin
                                    if (response.error.includes('Email')) {
                                        form.email.classList.add('is-invalid');
                                        document.getElementById('emailFeedback').textContent = response.error;
                                    }
                                    
                                    if (response.error.includes('Mật khẩu')) {
                                        form.password.classList.add('is-invalid');
                                        document.getElementById('passwordFeedback').textContent = response.error;
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
                        submitText.textContent = 'Đăng nhập';
                        
                        // Hiển thị lỗi kết nối
                        showAlert('Lỗi kết nối! Vui lòng kiểm tra kết nối internet và thử lại.');
                    };
                    
                    xhr.send(formData);
                });
            });
        </script>
    </body>
</html>
