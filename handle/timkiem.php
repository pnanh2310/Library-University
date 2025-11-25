<?php
include '../functions/db_connect.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 9;
$offset = ($page - 1) * $limit;

$like = '%' . $keyword . '%';

// Đếm tổng
$count_sql = "SELECT COUNT(*) AS total FROM sach WHERE ten_sach LIKE ? OR tac_gia LIKE ? OR the_loai LIKE ?";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param('sss', $like, $like, $like);
$stmt_count->execute();
$total_result = $stmt_count->get_result()->fetch_assoc();
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu trang hiện tại
$sql = "SELECT * FROM sach WHERE ten_sach LIKE ? OR tac_gia LIKE ? OR the_loai LIKE ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssii', $like, $like, $like, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($total_records == 0) {
    echo "<div class='alert alert-warning'>Không tìm thấy kết quả cho <b>$keyword</b>.</div>";
    exit;
}

echo "<div class='row mt-3 g-4'>";
while ($row = $result->fetch_assoc()) {
    // Truyền id và tên sách qua GET khi bấm vào
    $url = "../btl/view/muon_sach.php?id={$row['id']}&ten=" . urlencode($row['ten_sach']);

    echo "
    <div class='col-md-4'>
      <a href='$url' style='text-decoration: none;'>
        <div class='card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center' 
            style='border-radius:12px; background:#fff; transition:transform .2s, box-shadow .2s;'>
          <img src='{$row['hinh_anh']}' alt='Ảnh sách' 
              style='width:110px; height:150px; object-fit:contain; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.15);'>
          <div class='ms-3 flex-grow-1'>
            <h6 class='fw-bold text-danger mb-2'>{$row['ten_sach']}</h6>
            <p class='text-muted mb-1'><b>Tác giả:</b> {$row['tac_gia']}</p>
            <p class='text-muted mb-1'><b>Thể loại:</b> {$row['the_loai']}</p>
          </div>
        </div>
      </a>
    </div>";
}
echo "</div>";

// Phân trang
echo "<nav class='mt-4'>
        <ul class='pagination justify-content-center'>";
for ($i = 1; $i <= $total_pages; $i++) {
    $active = ($i == $page) ? "active" :"" ;
    echo "<li class='page-item $active' >
            <a class=' page-link  text-black fw-bold' href='#' data-page='$i'>$i</a>
          </li>";
}
echo "</ul></nav>";
