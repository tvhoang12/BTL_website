<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Lấy danh sách công ty
$companies = [];
$result = $conn->query("SELECT company_id, company_name FROM companies");
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

// Xác định công ty đang chọn
$selected_company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : ($companies[0]['company_id'] ?? null);

// Lấy role và class của tài khoản hiện tại
$current_role = $_SESSION['role_in_class'] ?? '';
$current_user_id = $_SESSION['user_id'];
$current_class = '';
if ($current_role === 'Lecturer') {
    $stmt = $conn->prepare("SELECT class FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $stmt->bind_result($current_class);
    $stmt->fetch();
    $stmt->close();
}

// Lấy danh sách sinh viên đã đăng ký thực tập tại công ty này
$students = [];
if ($selected_company_id) {
    $stmt = $conn->prepare("SELECT registered_students FROM companies WHERE company_id = ?");
    $stmt->bind_param("i", $selected_company_id);
    $stmt->execute();
    $stmt->bind_result($registed_json);
    $stmt->fetch();
    $stmt->close();

    $registed_users = $registed_json ? json_decode($registed_json, true) : [];
    if ($registed_users && is_array($registed_users) && count($registed_users) > 0) {
        // Lấy thông tin sinh viên theo quyền
        $in_query = implode(',', array_fill(0, count($registed_users), '?'));
        $types = str_repeat('s', count($registed_users));
        if ($current_role === 'Admin') {
            // Admin: lấy toàn bộ sinh viên đăng ký thực tập
            $sql = "SELECT user_id, full_name FROM users WHERE user_id IN ($in_query)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$registed_users);
        } elseif ($current_role === 'Lecturer') {
            // Lecturer: chỉ lấy sinh viên cùng lớp
            $sql = "SELECT user_id, full_name FROM users WHERE user_id IN ($in_query) AND class = ?";
            $stmt = $conn->prepare($sql);
            $bind_params = array_merge($registed_users, [$current_class]);
            $bind_types = $types . 's';
            $stmt->bind_param($bind_types, ...$bind_params);
        } else {
            // Các role khác: không hiển thị
            $stmt = null;
        }

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
        }
    }
}

// Hàm tính CPA cho từng sinh viên
function calculate_cpa($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT g.score, s.credit
        FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        WHERE g.user_id = ?
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_score = 0;
    $total_credit = 0;
    while ($row = $result->fetch_assoc()) {
        $total_score += $row['score'] * $row['credit'];
        $total_credit += $row['credit'];
    }
    $stmt->close();
    return $total_credit > 0 ? round($total_score / $total_credit, 2) : 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- giữ nguyên phần head như yêu cầu -->
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Kết quả thực tập</title>
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
                <h2>Kết quả thực tập</h2>
                <form method="get" class="mb-4">
                    <label for="company_id" class="form-label">Chọn công ty:</label>
                    <select class="form-select w-auto d-inline-block" id="company_id" name="company_id" onchange="this.form.submit()">
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['company_id'] ?>" <?= $company['company_id'] == $selected_company_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($company['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mã sinh viên</th>
                            <th>Họ tên</th>
                            <th>CPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['user_id']) ?></td>
                                    <td><?= htmlspecialchars($student['full_name']) ?></td>
                                    <td><?= htmlspecialchars(calculate_cpa($conn, $student['user_id'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Chưa có sinh viên đăng ký thực tập tại công ty này.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<!-- giữ nguyên phần script ở cuối body -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>