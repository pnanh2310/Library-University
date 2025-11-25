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
$limit = 10; // Số bản ghi mỗi trang
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total 
              FROM muon_tra mt
              JOIN nguoidung nd ON mt.id_nguoidung = nd.id
              JOIN sach s ON mt.id_sach = s.id
              WHERE (nd.ho_ten LIKE ? 
                  OR nd.msv LIKE ? 
                  OR s.ten_sach LIKE ? 
                  OR mt.trang_thai LIKE ?)";

$count_stmt = $conn->prepare($count_sql);
$searchTerm = "%" . $keyword . "%";
$count_stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$count_stmt->execute();
$total_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu cho trang hiện tại
$sql = "SELECT mt.*, nd.ho_ten, nd.msv, s.ten_sach 
        FROM muon_tra mt
        JOIN nguoidung nd ON mt.id_nguoidung = nd.id
        JOIN sach s ON mt.id_sach = s.id
        WHERE (nd.ho_ten LIKE ? 
            OR nd.msv LIKE ? 
            OR s.ten_sach LIKE ? 
            OR mt.trang_thai LIKE ?)
        ORDER BY mt.id DESC
        LIMIT ? OFFSET ?";

$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("ssssii", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
$stmt2->execute();
$result = $stmt2->get_result();
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý Mượn Trả</title>
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
    <a href="#" class="active">Quản lý Mượn Trả</a>
    <a href="qllichsu.php">Lịch Sử Thao Tác</a>
    <a href="../index.php">Trang Thư Viện</a>
    <a href="../handle/dangxuat.php" class="text-danger">Đăng xuất</a>
  </div>

  <div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold">📚 Quản lý Mượn - Trả</h3>
      <a href="../handle/muontra_handle.php?action=addForm" class="btn btn-primary">+ Thêm phiếu mượn</a>
    </div>

    <!-- Thanh tìm kiếm -->
    <form method="GET" class="search-bar mb-4">
      <input type="hidden" name="page" value="1">
      <input type="text" name="keyword" class="form-control" placeholder="Tìm theo tên, MSV, tên sách hoặc trạng thái..." 
             value="<?= htmlspecialchars($keyword) ?>">
      <button type="submit" class="btn btn-success">Tìm kiếm</button>
      <?php if ($keyword !== ''): ?>
        <a href="qlmuontra.php" class="btn btn-secondary">Xóa lọc</a>
      <?php endif; ?>
    </form>

    <!-- Thông tin phân trang -->
    <div class="page-info">
      Hiển thị <?= min($limit, $result->num_rows) ?> trong tổng số <?= $total_records ?> bản ghi
    </div>

    <table class="table table-bordered table-hover bg-white shadow-sm">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên người mượn</th>
          <th>Mã SV</th>
          <th>Tên sách</th>
          <th>Ngày mượn</th>
          <th>Ngày trả</th>
          <th>Trạng thái</th>
          <th>Duyệt đơn</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
              // Xử lý trạng thái hiển thị
              $status = $row['trang_thai'];
              if ($status === 'Đang mượn' && $row['ngay_tra'] < $today) {
                  $status = 'Quá hạn';
              }
              // Duyệt đơn
              $duyet = $row['trang_thai'] === 'Chờ duyệt' ? 'Chờ duyệt' : 'Đã duyệt';
            ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['ho_ten']) ?></td>
              <td><?= htmlspecialchars($row['msv']) ?></td>
              <td><?= htmlspecialchars($row['ten_sach']) ?></td>
              <td><?= htmlspecialchars($row['ngay_muon']) ?></td>
              <td><?= htmlspecialchars($row['ngay_tra']) ?></td>
              <td>
                <?php if ($status === 'Đang mượn'): ?>
                  <span class="badge bg-warning text-dark"><?= $status ?></span>
                <?php elseif ($status === 'Đã trả'): ?>
                  <span class="badge bg-success"><?= $status ?></span>
                <?php elseif ($status === 'Quá hạn'): ?>
                  <span class="badge bg-danger"><?= $status ?></span>
                <?php elseif ($status === 'Chờ duyệt'): ?>
                  <span class="badge bg-info text-dark"><?= $status ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($duyet === 'Chờ duyệt'): ?>
                  <a href="../handle/muontra_handle.php?action=approve&id=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Duyệt phiếu mượn này?')">Duyệt</a>
                  <a href="../handle/muontra_handle.php?action=reject&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Từ chối phiếu mượn này?')">Từ chối</a>
                <?php else: ?>
                  <span class="badge bg-success"><?= $duyet ?></span>
                <?php endif; ?>
              </td>
              <td>
                <a href="../handle/muontra_handle.php?action=editForm&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                <a href="../handle/muontra_handle.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa phiếu mượn này?');">Xóa</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="9" class="text-center text-muted">Không có dữ liệu mượn trả.</td></tr>
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