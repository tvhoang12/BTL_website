<?php
session_start();
require 'db.php';

// Lấy danh sách môn học (mã và tên) để dùng cho dropdown
$subjects = [];
$result = $conn->query("SELECT subject_id, subject_name FROM subjects");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Xử lý thêm công ty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $company_name = trim($_POST['company_name']);
    $cpa_needed = floatval($_POST['cpa_needed']);

    // Xử lý required_scores từ form động
    $required_scores = [];
    if (isset($_POST['subject_id']) && isset($_POST['min_score_letter'])) {
        foreach ($_POST['subject_id'] as $idx => $subject_id) {
            $score = $_POST['min_score_letter'][$idx];
            if ($subject_id && $score) {
                $required_scores[$subject_id] = $score;
            }
        }
    }
    $required_scores_json = json_encode($required_scores, JSON_UNESCAPED_UNICODE);

    if ($company_name && $cpa_needed && $required_scores_json) {
        $stmt = $conn->prepare("INSERT INTO companies (company_name, cpa_needed, required_scores, registed_student) VALUES (?, ?, ?, '[]')");
        $stmt->bind_param("sds", $company_name, $cpa_needed, $required_scores_json);
        $stmt->execute();
        $stmt->close();
        header("Location: company-list.php");
        exit;
    }
}

// Xử lý xóa công ty
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM companies WHERE company_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: company-list.php");
    exit;
}

// Xử lý sửa công ty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_company'])) {
    $company_id = intval($_POST['company_id']);
    $company_name = trim($_POST['company_name']);
    $cpa_needed = floatval($_POST['cpa_needed']);

    // Xử lý required_scores từ form động
    $required_scores = [];
    if (isset($_POST['edit_subject_id']) && isset($_POST['edit_min_score_letter'])) {
        foreach ($_POST['edit_subject_id'] as $idx => $subject_id) {
            $score = $_POST['edit_min_score_letter'][$idx];
            if ($subject_id && $score) {
                $required_scores[$subject_id] = $score;
            }
        }
    }
    $required_scores_json = json_encode($required_scores, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("UPDATE companies SET company_name = ?, cpa_needed = ?, required_scores = ? WHERE company_id = ?");
    $stmt->bind_param("sdsi", $company_name, $cpa_needed, $required_scores_json, $company_id);
    $stmt->execute();
    $stmt->close();
    header("Location: company-list.php");
    exit;
}

// Lấy danh sách công ty
$companies = [];
$result = $conn->query("SELECT company_id, company_name, cpa_needed, required_scores FROM companies");
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

$grade_letters = [
    'A+' => 'A+',
    'A'  => 'A',
    'B+' => 'B+',
    'B'  => 'B',
    'C+' => 'C+',
    'C'  => 'C',
    'D+' => 'D+',
    'D'  => 'D'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <title>Danh sách công ty thực tập</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .score-list { margin-bottom: 0; }
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
                <h1 class="mt-4">Danh sách công ty thực tập</h1>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-building me-1"></i>
                        Danh sách công ty
                        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addCompanyModal">Thêm công ty</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã công ty</th>
                                    <th>Tên công ty</th>
                                    <th>CPA yêu cầu</th>
                                    <th>Điểm môn yêu cầu</th>
                                    <th>Sửa</th>
                                    <th>Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($companies): ?>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($company['company_id']) ?></td>
                                            <td><?= htmlspecialchars($company['company_name']) ?></td>
                                            <td><?= htmlspecialchars($company['cpa_needed']) ?></td>
                                            <td>
                                                <ul class="score-list">
                                                    <?php
                                                    $scores = json_decode($company['required_scores'], true);
                                                    if ($scores && is_array($scores)) {
                                                        foreach ($scores as $subject_id => $min_score) {
                                                            // Tìm tên môn học
                                                            $subject_name = '';
                                                            foreach ($subjects as $subj) {
                                                                if ($subj['subject_id'] == $subject_id) {
                                                                    $subject_name = $subj['subject_name'];
                                                                    break;
                                                                }
                                                            }
                                                            echo "<li><strong>$subject_id</strong> - $subject_name: $min_score</li>";
                                                        }
                                                    } else {
                                                        echo "<li>Không có yêu cầu</li>";
                                                    }
                                                    ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCompanyModal<?= $company['company_id'] ?>">Sửa</button>
                                                <!-- Modal sửa -->
                                                <div class="modal fade" id="editCompanyModal<?= $company['company_id'] ?>" tabindex="-1" aria-labelledby="editCompanyModalLabel<?= $company['company_id'] ?>" aria-hidden="true">
                                                  <div class="modal-dialog">
                                                    <div class="modal-content">
                                                      <form method="post">
                                                        <div class="modal-header">
                                                          <h5 class="modal-title" id="editCompanyModalLabel<?= $company['company_id'] ?>">Sửa công ty</h5>
                                                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                          <input type="hidden" name="company_id" value="<?= $company['company_id'] ?>">
                                                          <div class="mb-3">
                                                            <label class="form-label">Tên công ty</label>
                                                            <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($company['company_name']) ?>" required>
                                                          </div>
                                                          <div class="mb-3">
                                                            <label class="form-label">CPA yêu cầu</label>
                                                            <input type="number" step="0.01" class="form-control" name="cpa_needed" value="<?= htmlspecialchars($company['cpa_needed']) ?>" required>
                                                          </div>
                                                          <div class="mb-3">
                                                            <label class="form-label">Điểm môn yêu cầu</label>
                                                            <div id="edit-required-scores-<?= $company['company_id'] ?>">
                                                                <?php
                                                                $scores = json_decode($company['required_scores'], true);
                                                                $idx = 0;
                                                                if ($scores && is_array($scores)) {
                                                                    foreach ($scores as $subject_id => $min_score) {
                                                                ?>
                                                                    <div class="row mb-2 align-items-center">
                                                                        <div class="col-7">
                                                                            <select class="form-select" name="edit_subject_id[]">
                                                                                <?php foreach ($subjects as $subj): ?>
                                                                                    <option value="<?= $subj['subject_id'] ?>" <?= $subj['subject_id'] == $subject_id ? 'selected' : '' ?>>
                                                                                        <?= $subj['subject_id'] ?> - <?= $subj['subject_name'] ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-4">
                                                                            <input type="number" step="0.01" class="form-control" name="edit_min_score[]" value="<?= htmlspecialchars($min_score) ?>" required>
                                                                        </div>
                                                                        <div class="col-1">
                                                                            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">-</button>
                                                                        </div>
                                                                    </div>
                                                                <?php
                                                                        $idx++;
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                            <button type="button" class="btn btn-success btn-sm" onclick="addEditScoreRow('edit-required-scores-<?= $company['company_id'] ?>')">+</button>
                                                            <div class="form-text">Chọn môn và nhập điểm sàn. Có thể thêm nhiều dòng.</div>
                                                          </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                          <button type="submit" name="edit_company" class="btn btn-primary">Lưu</button>
                                                        </div>
                                                      </form>
                                                    </div>
                                                  </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="company-list.php?delete_id=<?= $company['company_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa công ty này?');">Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal thêm công ty -->
            <div class="modal fade" id="addCompanyModal" tabindex="-1" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header">
                      <h5 class="modal-title" id="addCompanyModalLabel">Thêm công ty</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Tên công ty</label>
                        <input type="text" class="form-control" name="company_name" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">CPA yêu cầu</label>
                        <input type="number" step="0.01" class="form-control" name="cpa_needed" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Điểm môn yêu cầu</label>
                        <div id="required-scores-list">
                            <!-- Các dòng sẽ được thêm bằng JS -->
                        </div>
                        <button type="button" class="btn btn-success btn-sm" onclick="addScoreRow()">+</button>
                        <div class="form-text">Chọn môn và nhập điểm sàn. Có thể thêm nhiều dòng.</div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                      <button type="submit" name="add_company" class="btn btn-primary">Thêm</button>
                    </div>
                  </form>
                </div>
              </div>
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
<script>
    // Thêm dòng nhập điểm sàn cho modal thêm
    function addScoreRow() {
        var subjects = <?= json_encode($subjects, JSON_UNESCAPED_UNICODE) ?>;
        var gradeLetters = ['A+', 'A', 'B+', 'B', 'C+', 'C', 'D+', 'D'];
        var html = '<div class="row mb-2 align-items-center">';
        html += '<div class="col-7"><select class="form-select" name="subject_id[]">';
        for (var i = 0; i < subjects.length; i++) {
            html += '<option value="' + subjects[i].subject_id + '">' + subjects[i].subject_id + ' - ' + subjects[i].subject_name + '</option>';
        }
        html += '</select></div>';
        html += '<div class="col-4"><select class="form-select" name="min_score_letter[]" required>';
        for (var i = 0; i < gradeLetters.length; i++) {
            html += '<option value="' + gradeLetters[i] + '">' + gradeLetters[i] + '</option>';
        }
        html += '</select></div>';
        html += '<div class="col-1"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">-</button></div>';
        html += '</div>';
        document.getElementById('required-scores-list').insertAdjacentHTML('beforeend', html);
    }
    // Thêm dòng nhập điểm sàn cho modal sửa
    function addEditScoreRow(containerId) {
        var subjects = <?= json_encode($subjects, JSON_UNESCAPED_UNICODE) ?>;
        var gradeLetters = ['A+', 'A', 'B+', 'B', 'C+', 'C', 'D+', 'D'];
        var html = '<div class="row mb-2 align-items-center">';
        html += '<div class="col-7"><select class="form-select" name="edit_subject_id[]">';
        for (var i = 0; i < subjects.length; i++) {
            html += '<option value="' + subjects[i].subject_id + '">' + subjects[i].subject_id + ' - ' + subjects[i].subject_name + '</option>';
        }
        html += '</select></div>';
        html += '<div class="col-4"><select class="form-select" name="edit_min_score_letter[]" required>';
        for (var i = 0; i < gradeLetters.length; i++) {
            html += '<option value="' + gradeLetters[i] + '">' + gradeLetters[i] + '</option>';
        }
        html += '</select></div>';
        html += '<div class="col-1"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">-</button></div>';
        html += '</div>';
        document.getElementById(containerId).insertAdjacentHTML('beforeend', html);
    }
</script>
</body>
</html>