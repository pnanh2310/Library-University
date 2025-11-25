<?php
include 'db_connect.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../view/dangnhap.php");
    exit();
}

$user = $_SESSION['user'];
$id_nguoidung = $user['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sach = $_POST['id_sach'] ?? '';
    $ten_sach_input = trim($_POST['ten_sach'] ?? '');
    $ngay_muon = $_POST['ngay_muon'] ?? '';

    // Kiểm tra dữ liệu bắt buộc
    if (empty($id_nguoidung) || empty($ngay_muon) || (empty($id_sach) && empty($ten_sach_input))) {
        header("Location: ../view/muon_sach.php?error=1"); // Thiếu dữ liệu
        exit();
    }

    // Nếu không có id_sach, tìm sách theo tên
    if (empty($id_sach) && !empty($ten_sach_input)) {
        $stmt = $conn->prepare("SELECT id, so_luong FROM sach WHERE ten_sach LIKE ? LIMIT 1");
        $like = "%$ten_sach_input%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res && $res['so_luong'] > 0) {
            $id_sach = $res['id'];
        } else {
            header("Location: ../view/muon_sach.php?error=2"); // sách không tồn tại hoặc hết
            exit();
        }
    }

    // Kiểm tra số lượng sách
    $check = $conn->prepare("SELECT so_luong FROM sach WHERE id = ?");
    $check->bind_param("i", $id_sach);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if (!$result || $result['so_luong'] <= 0) {
        header("Location: ../view/muon_sach.php?error=3"); // sách hết
        exit();
    }

    // Lấy số ngày tối đa được mượn từ bảng nguoidung
    $stmt_user = $conn->prepare("SELECT ngay_toi_da_muon FROM nguoidung WHERE id = ?");
    $stmt_user->bind_param("i", $id_nguoidung);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result()->fetch_assoc();
    $ngay_toi_da_muon = $res_user['ngay_toi_da_muon'] ?? 7; // mặc định 7 ngày nếu không có

    // Tính ngày trả dựa trên ngày mượn và số ngày tối đa
    $ngay_tra = date('Y-m-d', strtotime("+$ngay_toi_da_muon days", strtotime($ngay_muon)));

    // Thêm vào bảng muon_tra (ĐÃ XÓA MSV)
    $stmt = $conn->prepare("
        INSERT INTO muon_tra (id_nguoidung, id_sach, ngay_muon, ngay_tra, trang_thai)
        VALUES (?, ?, ?, ?, 'Chờ duyệt')
    ");
    $stmt->bind_param("iiss", $id_nguoidung, $id_sach, $ngay_muon, $ngay_tra);

    if ($stmt->execute()) {
        // Giảm số lượng sách
        $update = $conn->prepare("UPDATE sach SET so_luong = so_luong - 1 WHERE id = ?");
        $update->bind_param("i", $id_sach);
        $update->execute();
        // Tăng số lần mượn
        $update_lan_muon = $conn->prepare("UPDATE sach SET so_lan_muon = so_lan_muon + 1 WHERE id = ?");
        $update_lan_muon->bind_param("i", $id_sach);
        $update_lan_muon->execute();
        header("Location: ../view/muon_sach.php?success=1");
        exit();
    } else {
        header("Location: ../view/muon_sach.php?error=4"); // lỗi database
        exit();
    }

} else {
    header("Location: ../view/muon_sach.php");
    exit();
}
?>