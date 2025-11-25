<?php
session_start();
include __DIR__ . '/../functions/db_connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../view/dangnhap.php");
    exit();
}

$user_id = $_SESSION['user']['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$vai_tro = strtolower(trim($user['vai_tro'] ?? ''));

// --- Xử lý tìm kiếm và phân trang ---
$keyword = trim($_GET['keyword'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Mảng ánh xạ vai trò đẹp
$roleNames = [
    'admin' => 'Admin',
    'sinhvien' => 'Sinh viên',
    'thuthu' => 'Thủ thư'
];

// Nếu là admin thì mới lấy dữ liệu
if ($vai_tro === 'admin') {

    // Đếm tổng số bản ghi
    $count_sql = "SELECT COUNT(*) as total FROM nguoidung WHERE 1=1";
    $count_params = [];
    $count_types = "";

    if ($keyword !== '') {
        $count_sql .= " AND (ho_ten LIKE ? OR email LIKE ? OR msv LIKE ?)";
        $searchTerm = "%" . $keyword . "%";
        $count_params[] = $searchTerm;
        $count_params[] = $searchTerm;
        $count_params[] = $searchTerm;
        $count_types .= "sss";
    }

    if ($role_filter !== '' && in_array($role_filter, ['admin', 'sinhvien', 'thuthu'])) {
        $count_sql .= " AND vai_tro = ?";
        $count_params[] = $role_filter;
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
    $sql = "SELECT * FROM nguoidung WHERE 1=1";
    $params = [];
    $types = "";

    if ($keyword !== '') {
        $sql .= " AND (ho_ten LIKE ? OR email LIKE ? OR msv LIKE ?)";
        $searchTerm = "%" . $keyword . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }

    if ($role_filter !== '' && in_array($role_filter, ['admin', 'sinhvien', 'thuthu'])) {
        $sql .= " AND vai_tro = ?";
        $params[] = $role_filter;
        $types .= "s";
    }

    $sql .= " ORDER BY id ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt2 = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt2->bind_param($types, ...$params);
    }
    $stmt2->execute();
    $result = $stmt2->get_result();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý Người dùng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background:#f4f7fa; display:flex; }
    .sidebar { width:250px; background:#fff; height:100vh; padding:30px 20px; box-shadow:2px 0 5px rgba(0,0,0,0.05); position:fixed; }
    .sidebar a { display:block; text-decoration:none; color:#555; padding:10px 15px; border-radius:8px; margin-bottom:10px; transition:0.3s; }
    .sidebar a:hover, .sidebar a.active { background:#6c63ff; color:#fff; }
    .main { margin-left:270px; padding:30px; width:calc(100% - 270px); }
    .table th { background:#6c63ff; color:#fff; }
    .pagination { justify-content: center; margin-top: 20px; }
    .page-info { text-align: center; margin: 10px 0; color: #6c757d; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h4 class="text-center mb-4">Hệ Thống</h4>
    <a href="admin.php">Trang Chủ</a>
    <a href="qlsach.php">Quản lý Sách</a>
    <a href="#" class="active">Quản lý Người dùng</a>
    <a href="qlmuontra.php">Quản lý Mượn Trả</a>
    <a href="qllichsu.php">Lịch Sử Thao Tác</a>
    <a href="../index.php">Trang Thư Viện</a>
    <a href="../handle/dangxuat.php" class="text-danger">Đăng xuất</a>
  </div>

  <div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold">👤 Danh sách Người dùng</h3>
      <?php if ($vai_tro === 'admin'): ?>
        <a href="../handle/nguoidung_handle.php?action=addForm" class="btn btn-primary">+ Thêm Người dùng</a>
      <?php endif; ?>
    </div>

    <!-- Thanh tìm kiếm -->
    <?php if ($vai_tro === 'admin'): ?>
    <form method="GET" class="d-flex align-items-center mb-4" style="gap:10px; flex-wrap: nowrap;">
        <input type="hidden" name="page" value="1">
        <input type="text" name="keyword" class="form-control" placeholder="Tìm theo tên, email hoặc MSV..."
            value="<?= htmlspecialchars($keyword) ?>" style="flex:0 0 65%;">
        <select name="role" class="form-control" style="flex:0 0 20%;">
            <option value="">Tất cả vai trò</option>
            <option value="admin" <?= $role_filter=='admin' ? 'selected' : '' ?>>Admin</option>
            <option value="sinhvien" <?= $role_filter=='sinhvien' ? 'selected' : '' ?>>Sinh viên</option>
            <option value="thuthu" <?= $role_filter=='thuthu' ? 'selected' : '' ?>>Thủ thư</option>
        </select>
        <button type="submit" class="btn btn-success" style="flex:0 0 6.5%;">🔍</button>
        <?php if ($keyword !== '' || $role_filter !== ''): ?>
            <a href="qlnguoidung.php" class="btn btn-secondary" style="flex:0 0 6.5%;">✖</a>
        <?php endif; ?>
    </form>

    <!-- Thông tin phân trang -->
    <div class="page-info">
      Hiển thị <?= min($limit, $result->num_rows) ?> trong tổng số <?= $total_records ?> người dùng
    </div>

    <table class="table table-bordered table-hover bg-white shadow-sm">
      <thead>
        <tr>
          <th>ID</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>MSV</th>
          <th>Vai trò</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                $displayRole = $roleNames[$row['vai_tro']] ?? htmlspecialchars($row['vai_tro']);
        ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['ho_ten']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['msv'] ?? '-') ?></td>
              <td><?= $displayRole ?></td>
              <td>
                <a href="../handle/nguoidung_handle.php?action=editForm&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                <a href="../handle/nguoidung_handle.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa người dùng này?');">Xóa</a>
              </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
          <tr><td colspan="6" class="text-center text-muted">Không tìm thấy kết quả nào.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
      <ul class="pagination">
        <!-- Nút Previous -->
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&role=<?= urlencode($role_filter) ?>" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>

        <!-- Các trang -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&role=<?= urlencode($role_filter) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <!-- Nút Next -->
        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&role=<?= urlencode($role_filter) ?>" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>

    <?php else: ?>
      <div class="alert alert-danger">Bạn không có quyền truy cập danh sách Người dùng.</div>
    <?php endif; ?>
  </div>
</body>
</html>