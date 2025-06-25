<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Lấy danh sách công ty thực tập
$companies = [];
$result = $conn->query("SELECT company_id, company_name, cpa_needed FROM companies");
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

// Lấy toàn bộ điểm và tín chỉ của sinh viên từ bảng grades và subjects (luôn thực hiện)
$stmt = $conn->prepare("
    SELECT g.subject_id, g.score, s.credit
    FROM grades g
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}
$stmt->close();

// Tính CPA và tổng điểm, tổng tín chỉ (luôn thực hiện)
$total_score = 0;
$total_credit = 0;
foreach ($grades as $grade) {
    $total_score += $grade['score'] * $grade['credit'];
    $total_credit += $grade['credit'];
}
$cpa = $total_credit > 0 ? round($total_score / $total_credit, 2) : 0;

// Xử lý đăng ký thực tập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company_id'];
    // Bước 3: Lấy yêu cầu công ty và kiểm tra
    $stmt = $conn->prepare("SELECT cpa_needed, required_scores, registered_students FROM companies WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $stmt->bind_result($cpa_needed, $required_scores_json, $registed_user_json);
    $stmt->fetch();
    $stmt->close();

    $required_scores = json_decode($required_scores_json, true);
    $registed_users = $registed_user_json ? json_decode($registed_user_json, true) : [];

    // Tạo bảng điểm của sinh viên theo subject_id
    $user_scores = [];
    foreach ($grades as $grade) {
        $user_scores[$grade['subject_id']] = $grade['score'];
    }

    $pass = true;
    foreach ($required_scores as $subject_id => $min_score) {
        if (!isset($user_scores[$subject_id]) || $user_scores[$subject_id] < $min_score) {
            $pass = false;
            $error = "Bạn chưa đạt yêu cầu môn $subject_id (yêu cầu: $min_score, bạn có: " . ($user_scores[$subject_id] ?? 'chưa học') . ")";
            break;
        }
    }
    if ($pass && $cpa < $cpa_needed) {
        $pass = false;
        $error = "CPA của bạn ($cpa) không đủ điều kiện (yêu cầu: $cpa_needed)";
    }

    // Bước 4: Nếu đạt, thêm vào JSON
    if ($pass) {
        if (!in_array($user_id, $registed_users)) {
            $registed_users[] = $user_id;
            $new_json = json_encode($registed_users);
            $stmt = $conn->prepare("UPDATE companies SET registered_students = ? WHERE company_id = ?");
            $stmt->bind_param("si", $new_json, $company_id);
            if ($stmt->execute()) {
                $success = "Đăng ký thực tập thành công!";
            } else {
                $error = "Lỗi khi lưu đăng ký!";
            }
            $stmt->close();
        } else {
            $error = "Bạn đã đăng ký công ty này!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
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
            <div class="container mt-4">
                <h2>Đăng ký thực tập</h2>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="company_id" class="form-label">Chọn công ty thực tập</label>
                        <select class="form-control" id="company_id" name="company_id" required>
                            <option value="">-- Chọn công ty --</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= $company['company_id'] ?>">
                                    <?= htmlspecialchars($company['company_name']) ?> (CPA yêu cầu: <?= $company['cpa_needed'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Đăng ký</button>
                </form>

                <!-- Hiển thị bảng điểm sinh viên -->
                <h4 class="mt-5">Bảng điểm của bạn</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mã môn</th>
                            <th>Điểm</th>
                            <th>Số tín chỉ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($grades)): ?>
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><?= htmlspecialchars($grade['subject_id']) ?></td>
                                    <td><?= htmlspecialchars($grade['score']) ?></td>
                                    <td><?= htmlspecialchars($grade['credit']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Chưa có dữ liệu điểm.</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($total_credit > 0): ?>
                                <tr>
                                    <td colspan="2" class="text-end"><strong>Tổng tín chỉ:</strong></td>
                                    <td><?= htmlspecialchars($total_credit) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end"><strong>Tổng điểm:</strong></td>
                                    <td><?= htmlspecialchars($total_score) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end"><strong>CPA:</strong></td>
                                    <td><?= htmlspecialchars($cpa) ?></td>
                                </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Chưa có học môn nào cả.</td>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>