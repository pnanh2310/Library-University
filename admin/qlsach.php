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

// Kiểm tra quyền admin hoặc thủ thư
if (!$user || !in_array(strtolower(trim($user['vai_tro'])), ['admin', 'thuthu'])) {
    http_response_code(403);
    echo "403 - Bạn không có quyền truy cập trang này.";
    exit();
}

// --- Xử lý tìm kiếm và phân trang ---
$keyword = trim($_GET['keyword'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM sach WHERE 1=1";
if ($keyword !== '') {
    $count_sql .= " AND (ten_sach LIKE ? OR tac_gia LIKE ?)";
    $count_stmt = $conn->prepare($count_sql);
    $searchTerm = "%" . $keyword . "%";
    $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
} else {
    $count_stmt = $conn->prepare($count_sql);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu cho trang hiện tại
$sql = "SELECT * FROM sach WHERE 1=1";
if ($keyword !== '') {
    $sql .= " AND (ten_sach LIKE ? OR tac_gia LIKE ?)";
}
$sql .= " ORDER BY id ASC LIMIT ? OFFSET ?";

$stmt2 = $conn->prepare($sql);
if ($keyword !== '') {
    $searchTerm = "%" . $keyword . "%";
    $stmt2->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
} else {
    $stmt2->bind_param("ii", $limit, $offset);
}
$stmt2->execute();
$result = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý Sách</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background:#f4f7fa; display:flex; }
    .sidebar { width:250px; background:#fff; height:100vh; padding:30px 20px; box-shadow:2px 0 5px rgba(0,0,0,0.05); position:fixed; }
    .sidebar a { display:block; text-decoration:none; color:#555; padding:10px 15px; border-radius:8px; margin-bottom:10px; transition:0.3s; }
    .sidebar a:hover, .sidebar a.active { background:#6c63ff; color:#fff; }
    .main { margin-left:270px; padding:30px; width:calc(100% - 270px); }
    .table th { background:#6c63ff; color:#fff; }
    .search-bar { display:flex; gap:10px; }
    .search-bar input { flex:1; }
    img.thumb { width:60px; height:80px; object-fit:cover; border-radius:4px; }
    .pagination { justify-content: center; margin-top: 20px; }
    .page-info { text-align: center; margin: 10px 0; color: #6c757d; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h4 class="text-center mb-4">Hệ Thống</h4>
    <a href="admin.php">Trang Chủ</a>
    <a href="#" class="active">Quản lý Sách</a>
    <a href="qlnguoidung.php">Quản lý Người dùng</a>
    <a href="qlmuontra.php">Quản lý Mượn Trả</a>
    <a href="qllichsu.php">Lịch Sử Thao Tác</a>
    <a href="../index.php">Trang Thư Viện</a>
    <a href="../handle/dangxuat.php" class="text-danger">Đăng xuất</a>
  </div>

  <div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold">📖 Danh sách Sách</h3>
      <a href="../handle/sach_handle.php?action=addForm" class="btn btn-primary">+ Thêm Sách</a>
    </div>

    <!-- Thanh tìm kiếm -->
    <form method="GET" class="search-bar mb-4">
      <input type="hidden" name="page" value="1">
      <input type="text" name="keyword" class="form-control" placeholder="Tìm theo tên sách hoặc tác giả..." 
             value="<?= htmlspecialchars($keyword) ?>">
      <button type="submit" class="btn btn-success">Tìm kiếm</button>
      <?php if ($keyword !== ''): ?>
        <a href="qlsach.php" class="btn btn-secondary">Xóa lọc</a>
      <?php endif; ?>
    </form>

    <!-- Thông tin phân trang -->
    <div class="page-info">
      Hiển thị <?= min($limit, $result->num_rows) ?> trong tổng số <?= $total_records ?> sách
    </div>

    <table class="table table-bordered table-hover bg-white shadow-sm">
      <thead>
        <tr>
          <th>ID</th>
          <th>Ảnh</th>
          <th>Tên sách</th>
          <th>Tác giả</th>
          <th>Năm XB</th>
          <th>Thể loại</th>
          <th>Số lượng</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td>
                <?php if (!empty($row['hinh_anh'])): ?>
                  <img src="<?= htmlspecialchars('../' . $row['hinh_anh']) ?>" class="thumb" alt="thumb">
                <?php else: ?>
                  <span class="text-muted">Không có</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['ten_sach']) ?></td>
              <td><?= htmlspecialchars($row['tac_gia']) ?></td>
              <td><?= htmlspecialchars($row['nam_xuat_ban'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['the_loai']) ?></td>
              <td><?= (int)$row['so_luong'] ?></td>
              <td>
                <a href="../handle/sach_handle.php?action=editForm&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                <a href="../handle/sach_handle.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa sách này?');">Xóa</a>
              </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
          <tr><td colspan="8" class="text-center text-muted">Không tìm thấy kết quả nào.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
      <ul class="pagination">
        <!-- Nút Previous -->
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>

        <!-- Các trang -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <!-- Nút Next -->
        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</body>
</html>