<?php
include '../functions/db_connect.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: dangnhap.php");
    exit();
}

$user = $_SESSION['user'];
$id_nguoidung = $user['id'];

// Lấy danh sách đơn mượn sách của user
$stmt = $conn->prepare("
    SELECT mt.*, s.ten_sach, s.tac_gia, s.hinh_anh 
    FROM muon_tra mt 
    JOIN sach s ON mt.id_sach = s.id 
    WHERE mt.id_nguoidung = ? 
    ORDER BY mt.ngay_muon DESC
");
$stmt->bind_param("i", $id_nguoidung);
$stmt->execute();
$don_muon = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn Mượn Sách Của Tôi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-danger mb-4">📚 Đơn Mượn Sách Của Tôi</h2>
        
        <?php if ($don_muon->num_rows > 0): ?>
            <div class="row">
                <?php while ($don = $don_muon->fetch_assoc()): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($don['ten_sach']) ?></h5>
                                <p class="card-text">
                                    <strong>Tác giả:</strong> <?= htmlspecialchars($don['tac_gia']) ?><br>
                                    <strong>Ngày mượn:</strong> <?= $don['ngay_muon'] ?><br>
                                    <strong>Ngày trả:</strong> <?= $don['ngay_tra'] ?><br>
                                    <strong>Trạng thái:</strong> 
                                    <span class="badge 
                                        <?= $don['trang_thai'] == 'Đã trả' ? 'bg-success' : 
                                           ($don['trang_thai'] == 'Đang mượn' ? 'bg-primary' : 
                                           ($don['trang_thai'] == 'Quá hạn' ? 'bg-danger' : 'bg-secondary')) ?>">
                                        <?= $don['trang_thai'] ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Bạn chưa có đơn mượn sách nào.
            </div>
        <?php endif; ?>
        
        <a href="../index.php" class="btn btn-secondary mt-3">← Quay lại Trang chủ</a>
    </div>
</body>
</html>