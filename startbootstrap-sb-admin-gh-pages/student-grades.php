<?php

function calculate_cpa($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT g.score, s.credit, g.subject_id, s.subject_name
        FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        WHERE g.user_id = ?
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_score = 0;
    $total_credits = 0;
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        // Chuyển đổi điểm theo thang 4
        if ($row['score'] >= 9) {
            $row['score'] = 4.0;
        } elseif ($row['score'] >= 8.5) {
            $row['score'] = 3.7;
        } elseif ($row['score'] >= 8.0) {
            $row['score'] = 3.5;
        } elseif ($row['score'] >= 6.5) {
            $row['score'] = 3.0;
        } elseif ($row['score'] >= 5.5) {
            $row['score'] = 2.5;
        } elseif ($row['score'] >= 4.0) {
            $row['score'] = 2.0;
        } else {
            $row['score'] = 0;
        }
        $total_score += $row['score'] * $row['credit'];
        $total_credits += $row['credit'];
        $grades[] = $row;
    }
    $stmt->close();
    $cpa = $total_credits > 0 ? round($total_score / $total_credits, 2) : 0;
    // Luôn trả về mảng có 2 phần tử
    return [$cpa, $grades];
}

session_start();
require 'db.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// truy vấn
$stmt = $conn->prepare("SELECT class FROM users WHERE user_id = ?");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($lecturer_class); // Lớp của giảng viên
$stmt->fetch();
$stmt->close();

// Lấy danh sách sinh viên trong lớp cùng lớp với giảng viên
if ($_SESSION['role_in_class'] == 'Admin') {
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE role_in_class='Student' OR role_in_class='Lecturer'");
} else {
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE class = ? AND role_in_class = 'Student'");
    $stmt->bind_param("s", $lecturer_class);
}
$students = [];
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <title>Bảng điểm - <?= htmlspecialchars($lecturer_class) ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
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
                <h2>Bảng điểm - <?= htmlspecialchars($lecturer_class) ?></h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mã sinh viên</th>
                            <th>Họ tên</th>
                            <th>CPA</th>
                            <th>Chi tiết điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $result = calculate_cpa($conn, $student['user_id']);
                            if (!is_array($result) || count($result) < 2) {
                                $cpa = 0;
                                $grades = [];
                            } else {
                                list($cpa, $grades) = $result;
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($student['user_id']) ?></td>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($cpa) ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?= $student['user_id'] ?>">Chi tiết</button>
                                <!-- Modal -->
                                <div class="modal fade" id="detailModal<?= $student['user_id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $student['user_id'] ?>" aria-hidden="true">
                                  <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h5 class="modal-title" id="detailModalLabel<?= $student['user_id'] ?>">Bảng điểm chi tiết - <?= htmlspecialchars($student['full_name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <div class="modal-body">
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
                                                <?php foreach ($grades as $g): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($g['subject_id']) ?></td>
                                                    <td><?= htmlspecialchars($g['subject_name']) ?></td>
                                                    <?php
                                                    // Chuyển đổi điểm sang ký hiệu
                                                    if ($g['score'] == 4.0) {
                                                        $g['score'] = 'A+';
                                                    } elseif ($g['score'] == 3.7) {
                                                        $g['score'] = 'A';
                                                    } elseif ($g['score'] == 3.5) {
                                                        $g['score'] = 'B+';
                                                    } elseif ($g['score'] >= 3.0) {
                                                        $g['score'] = 'B';
                                                    } elseif ($g['score'] >= 2.5) {
                                                        $g['score'] = 'C+';
                                                    } elseif ($g['score'] >= 2.0) {
                                                        $g['score'] = 'C';
                                                    } elseif ($g['score'] >= 1.5) {
                                                        $g['score'] = 'D+';
                                                    } elseif ($g['score'] >= 1.0) {
                                                        $g['score'] = 'D';
                                                    } else {
                                                        $g['score'] = 'F';
                                                    }

                                                    ?>
                                                    <td><?= htmlspecialchars($g['score']) ?></td>
                                                    <td><?= htmlspecialchars($g['credit']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
</html>