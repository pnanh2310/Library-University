<?php
include '../functions/db_connect.php';

$id = (int)($_GET['id'] ?? 0);
if($id <= 0) exit();

$stmt = $conn->prepare("SELECT * FROM sach WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$sach = $result->fetch_assoc();

if(!$sach) {
    echo '<div class="text-danger text-center">Không tìm thấy sách!</div>';
    exit();
}

echo '<div class="card p-3 shadow-sm">';
echo '<img src="../'.htmlspecialchars($sach['hinh_anh']).'" alt="Ảnh sách" class="img-fluid mb-3" style="max-height:250px; object-fit:contain;">';
echo '<h5 class="fw-bold text-primary">'.htmlspecialchars($sach['ten_sach']).'</h5>';
echo '<p><b>Tác giả:</b> '.htmlspecialchars($sach['tac_gia']).'</p>';
echo '<p><b>Thể loại:</b> '.htmlspecialchars($sach['the_loai']).'</p>';
echo '<p><b>Năm xuất bản:</b> '.htmlspecialchars($sach['nam_xuat_ban']).'</p>';
echo '<p><b>Số lượng còn:</b> '.htmlspecialchars($sach['so_luong']).'</p>';
echo '<p class="text-muted">'.htmlspecialchars($sach['mo_ta']).'</p>';
echo '</div>';
?>
