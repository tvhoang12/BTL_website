<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin sinh viên
$stmt = $conn->prepare("SELECT full_name, class FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $class);
$stmt->fetch();
$stmt->close();

// Lấy toàn bộ điểm và tín chỉ của sinh viên
$stmt = $conn->prepare("
    SELECT g.subject_id, s.subject_name, g.score, s.credit
    FROM grades g
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
$total_score = 0;
$total_credit = 0;
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
    $total_score += $row['score'] * $row['credit'];
    $total_credit += $row['credit'];
}
$stmt->close();

$cpa = $total_credit > 0 ? round($total_score / $total_credit, 2) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
     <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Bảng điểm</title>
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
                <h2>Bảng điểm cá nhân - <?= htmlspecialchars($full_name) ?> (<?= htmlspecialchars($class) ?>)</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mã môn</th>
                            <th>Tên môn</th>
                            <th>Điểm</th>
                            <th>Số tín chỉ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($grades)): ?>
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><?= htmlspecialchars($grade['subject_id']) ?></td>
                                    <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($grade['score']) ?></td>
                                    <td><?= htmlspecialchars($grade['credit']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng tín chỉ:</strong></td>
                                <td><?= htmlspecialchars($total_credit) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng điểm:</strong></td>
                                <td><?= htmlspecialchars($total_score) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>CPA:</strong></td>
                                <td><?= htmlspecialchars($cpa) ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Chưa có dữ liệu điểm.</td>
                            </tr>
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
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
</body>
</html>