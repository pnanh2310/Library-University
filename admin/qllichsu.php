<?php
session_start();
include __DIR__ . '/../functions/db_connect.php';

// Kiểm tra đăng nhập và quyền admin/thuthu
if (!isset($_SESSION['user']) || !in_array(strtolower(trim($_SESSION['user']['vai_tro'])), ['admin', 'thuthu'])) {
    header("Location: ../view/dangnhap.php");
    exit();
}

// --- Xử lý tìm kiếm và phân trang ---
$keyword = trim($_GET['keyword'] ?? '');
$table_filter = trim($_GET['table'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Mảng ánh xạ tên bảng đẹp
$tableNames = [
    'sach' => 'Sách',
    'nguoidung' => 'Người dùng', 
    'muon_tra' => 'Mượn trả'
];

// Đếm tổng số bản ghi
$count_sql = "
    SELECT COUNT(*) as total 
    FROM lich_su ls 
    LEFT JOIN nguoidung nd ON ls.id_nguoidung = nd.id 
    WHERE 1=1
";

$count_params = [];
$count_types = "";

if ($keyword !== '') {
    $count_sql .= " AND (ls.hanh_dong LIKE ? OR nd.ho_ten LIKE ? OR nd.msv LIKE ?)";
    $searchTerm = "%" . $keyword . "%";
    $count_params[] = $searchTerm;
    $count_params[] = $searchTerm;
    $count_params[] = $searchTerm;
    $count_types .= "sss";
}

if ($table_filter !== '' && in_array($table_filter, ['sach', 'nguoidung', 'muon_tra'])) {
    $count_sql .= " AND ls.bang_lien_quan = ?";
    $count_params[] = $table_filter;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu cho trang hiện tại
$sql = "
    SELECT ls.*, nd.ho_ten, nd.msv 
    FROM lich_su ls 
    LEFT JOIN nguoidung nd ON ls.id_nguoidung = nd.id 
    WHERE 1=1
";

$params = [];
$types = "";

if ($keyword !== '') {
    $sql .= " AND (ls.hanh_dong LIKE ? OR nd.ho_ten LIKE ? OR nd.msv LIKE ?)";
    $searchTerm = "%" . $keyword . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if ($table_filter !== '' && in_array($table_filter, ['sach', 'nguoidung', 'muon_tra'])) {
    $sql .= " AND ls.bang_lien_quan = ?";
    $params[] = $table_filter;
    $types .= "s";
}

$sql .= " ORDER BY ls.thoi_gian DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Thực thi query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$lich_su = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Lịch sử thao tác</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background:#f4f7fa; display:flex; }
    .sidebar { width:250px; background:#fff; height:100vh; padding:30px 20px; box-shadow:2px 0 5px rgba(0,0,0,0.05); position:fixed; }
    .sidebar a { display:block; text-decoration:none; color:#555; padding:10px 15px; border-radius:8px; margin-bottom:10px; transition:0.3s; }
    .sidebar a:hover, .sidebar a.active { background:#6c63ff; color:#fff; }
    .main { margin-left:270px; padding:30px; width:calc(100% - 270px); }
    .table th { background:#6c63ff; color:#fff; }
    .badge-custom { font-size: 0.8em; padding: 5px 10px; }
    .pagination { justify-content: center; margin-top: 20px; }
    .page-info { text-align: center; margin: 10px 0; color: #6c757d; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h4 class="text-center mb-4">Hệ Thống</h4>
    <a href="admin.php">Trang Chủ</a>
    <a href="qlsach.php">Quản lý Sách</a>
    <a href="qlnguoidung.php">Quản lý Người dùng</a>
    <a href="qlmuontra.php">Quản lý Mượn Trả</a>
    <a href="#" class="active">Lịch sử thao tác</a>
    <a href="../index.php">Trang Thư Viện</a>
    <a href="../handle/dangxuat.php" class="text-danger">Đăng xuất</a>
  </div>

  <div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold">📋 Lịch sử thao tác hệ thống</h3>
      <button class="btn btn-outline-primary" onclick="window.location.reload()">🔄 Làm mới</button>
    </div>

    <!-- Thanh tìm kiếm -->
    <form method="GET" class="d-flex align-items-center mb-4" style="gap:10px; flex-wrap: nowrap;">
        <input type="hidden" name="page" value="1">
        <input type="text" name="keyword" class="form-control" placeholder="Tìm theo hành động, tên người dùng hoặc MSV..."
            value="<?= htmlspecialchars($keyword) ?>" style="flex:0 0 65%;">
        <select name="table" class="form-control" style="flex:0 0 20%;">
            <option value="">Tất cả bảng</option>
            <option value="sach" <?= $table_filter=='sach' ? 'selected' : '' ?>>Sách</option>
            <option value="nguoidung" <?= $table_filter=='nguoidung' ? 'selected' : '' ?>>Người dùng</option>
            <option value="muon_tra" <?= $table_filter=='muon_tra' ? 'selected' : '' ?>>Mượn trả</option>
        </select>
        <button type="submit" class="btn btn-success" style="flex:0 0 6.5%;">🔍</button>
        <?php if ($keyword !== '' || $table_filter !== ''): ?>
            <a href="qllichsu.php" class="btn btn-secondary" style="flex:0 0 6.5%;">✖</a>
        <?php endif; ?>
    </form>

    <!-- Thông tin phân trang -->
    <div class="page-info">
      Hiển thị <?= min($limit, $lich_su->num_rows) ?> trong tổng số <?= $total_records ?> bản ghi
    </div>

    <?php if ($lich_su->num_rows > 0): ?>
      <table class="table table-bordered table-hover bg-white shadow-sm">
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Người thực hiện</th>
            <th>Hành động</th>
            <th>Bảng liên quan</th>
            <th>ID bản ghi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($log = $lich_su->fetch_assoc()): ?>
            <tr>
              <td>
                <small class="text-muted"><?= date('H:i', strtotime($log['thoi_gian'])) ?></small><br>
                <small><?= date('d/m/Y', strtotime($log['thoi_gian'])) ?></small>
              </td>
              <td>
                <?php if ($log['ho_ten']): ?>
                  <strong><?= htmlspecialchars($log['ho_ten']) ?></strong>
                  <?php if ($log['msv']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars($log['msv']) ?></small>
                  <?php endif; ?>
                <?php else: ?>
                  <em class="text-muted">Hệ thống</em>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($log['hanh_dong']) ?></td>
              <td>
                <?php if ($log['bang_lien_quan']): ?>
                  <?php $displayTable = $tableNames[$log['bang_lien_quan']] ?? htmlspecialchars($log['bang_lien_quan']); ?>
                  <span class="badge bg-info badge-custom"><?= $displayTable ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary badge-custom">N/A</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($log['id_ban_ghi']): ?>
                  <span class="badge bg-dark badge-custom">#<?= $log['id_ban_ghi'] ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary badge-custom">N/A</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Phân trang -->
      <?php if ($total_pages > 1): ?>
      <nav aria-label="Page navigation">
        <ul class="pagination">
          <!-- Nút Previous -->
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&table=<?= urlencode($table_filter) ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>

          <!-- Các trang -->
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&table=<?= urlencode($table_filter) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <!-- Nút Next -->
          <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&table=<?= urlencode($table_filter) ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>

    <?php else: ?>
      <div class="alert alert-info text-center">
        <h5>📝 <?= ($keyword !== '' || $table_filter !== '') ? 'Không tìm thấy kết quả phù hợp' : 'Chưa có lịch sử thao tác nào' ?></h5>
        <p class="mb-0"><?= ($keyword !== '' || $table_filter !== '') ? 'Hãy thử từ khóa hoặc bộ lọc khác' : 'Các thao tác trong hệ thống sẽ được ghi lại tại đây' ?></p>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>