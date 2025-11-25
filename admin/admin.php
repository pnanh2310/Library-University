<?php
session_start();
include __DIR__ . '/../functions/db_connect.php';

// Kiểm tra người dùng đã login chưa
if (!isset($_SESSION['user'])) {
    header("Location: ../view/dangnhap.php");
    exit();
}

// Lấy thông tin user từ session
$user_id = $_SESSION['user']['id'] ?? 0;

// Lấy thông tin user từ database
$stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Kiểm tra quyền admin hoặc thủ thư
if (!$user || !in_array(strtolower(trim($user['vai_tro'])), ['admin', 'thuthu'])) {
    http_response_code(403);
    echo "403 - Bạn không có quyền truy cập trang này.";
    exit();
}

// --- Lấy số liệu thống kê ---
// Tổng số sách
$totalBooks = $conn->query("SELECT COUNT(*) AS cnt FROM sach")->fetch_assoc()['cnt'];

// Tổng số người dùng
$totalUsers = $conn->query("SELECT COUNT(*) AS cnt FROM nguoidung")->fetch_assoc()['cnt'];

// Sách đang mượn (trạng thái 'Đang mượn')
$booksBorrowed = $conn->query("SELECT COUNT(*) AS cnt FROM muon_tra WHERE trang_thai = 'Đang mượn'")->fetch_assoc()['cnt'];

// Sách trễ hạn (Đang mượn nhưng ngày trả < hôm nay)
$booksOverdue = $conn->query("SELECT COUNT(*) AS cnt FROM muon_tra WHERE trang_thai = 'Đang mượn' AND ngay_tra < CURDATE()")->fetch_assoc()['cnt'];

// --- Lấy dữ liệu cho biểu đồ 7 ngày ---
$borrowData = [];
$dateLabels = [];

// Tạo dữ liệu cho 7 ngày gần nhất
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateLabels[] = date('d/m', strtotime("-$i days"));
    
    $query = $conn->prepare("SELECT COUNT(*) AS cnt FROM muon_tra WHERE DATE(ngay_muon) = ?");
    $query->bind_param("s", $date);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    
    $borrowData[] = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7fa; display: flex; }
    .sidebar { width: 250px; height: 100vh; background: #fff; box-shadow: 2px 0 5px rgba(0,0,0,0.05); padding: 30px 20px; position: fixed; top:0; left:0; }
    .sidebar h4 { color: #6c63ff; font-weight:700; text-align:center; margin-bottom:30px; }
    .sidebar a { display:block; text-decoration:none; color:#555; padding:10px 15px; border-radius:8px; margin-bottom:10px; transition:0.3s; }
    .sidebar a:hover, .sidebar a.active { background:#6c63ff; color:#fff; }
    .main { margin-left:270px; padding:30px; width: calc(100% - 270px); }
    .card-box { border-radius:15px; color:#fff; padding:25px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    .bg-blue { background-color:#4e73df; }
    .bg-cyan { background-color:#36b9cc; }
    .bg-purple { background-color:#6f42c1; }
    .bg-green { background-color:#1cc88a; }
    .chart-container { background:#fff; border-radius:10px; padding:20px; margin-bottom:20px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
    canvas { max-height: 300px; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h4>Hệ Thống</h4>
    <a href="#" class="active">Trang Chủ</a>
    <a href="qlsach.php">Quản lý Sách</a>
    <a href="qlnguoidung.php">Quản lý Người dùng</a>
    <a href="qlmuontra.php">Quản lý Mượn Trả</a>
    <a href="qllichsu.php">Lịch Sử Thao Tác</a>
    <a href="../index.php">Trang Thư Viện</a>
    <a href="../handle/dangxuat.php" class="text-danger">Đăng xuất</a>
  </div>

  <div class="main">
    <h3 class="fw-bold mb-4">Tổng quan hệ thống</h3>

    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card-box bg-blue">
          <h5>Tổng số sách</h5>
          <h2><?= $totalBooks ?></h2>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-box bg-cyan">
          <h5>Người dùng</h5>
          <h2><?= $totalUsers ?></h2>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-box bg-purple">
          <h5>Sách đang mượn</h5>
          <h2><?= $booksBorrowed ?></h2>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-box bg-green">
          <h5>Trễ hạn</h5>
          <h2><?= $booksOverdue ?></h2>
        </div>
      </div>
    </div>

    <!-- Biểu đồ xu hướng mượn sách 7 ngày -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="chart-container">
          <h5 class="mb-3">📊 Xu Hướng Mượn Sách (7 Ngày Qua)</h5>
          <canvas id="trendChart"></canvas>
        </div>
      </div>
    </div>


  <script>
    // Biểu đồ xu hướng 7 ngày
    const trendCtx = document.getElementById('trendChart');
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($dateLabels) ?>,
        datasets: [{
          label: 'Số lượt mượn',
          data: <?= json_encode($borrowData) ?>,
          borderColor: '#6c63ff',
          backgroundColor: 'rgba(108, 99, 255, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#6c63ff',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          },
          tooltip: {
            mode: 'index',
            intersect: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Số lượt mượn'
            },
            ticks: {
              stepSize: 1
            }
          },
          x: {
            title: {
              display: true,
              text: 'Ngày'
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'nearest'
        }
      }
    });

    
  </script>
</body>
</html>