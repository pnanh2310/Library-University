<?php
session_start();
include("../functions/db_connect.php");

$email = $_POST['email'] ?? '';
$mat_khau = $_POST['mat_khau'] ?? '';

// Lấy người dùng theo email
$sql = "SELECT * FROM nguoidung WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();

    // Kiểm tra mật khẩu (plain text hiện tại)
    if ($user['mat_khau'] === $mat_khau) {

        // Chuẩn hóa vai trò: lowercase + trim
        $vai_tro = strtolower(trim($user['vai_tro']));

        // Lưu thông tin người dùng vào session
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'ho_ten'   => $user['ho_ten'],
            'email'    => $user['email'],
            'msv'      => $user['msv'] ?? null,
            'vai_tro'  => $vai_tro
        ];

        // Redirect theo vai trò
        if (in_array($vai_tro, ['admin', 'thuthu'])) {
            header("Location: ../admin/admin.php");
        } else {
            header("Location: ../index.php");
        }
        exit();

    } else {
        $_SESSION['error'] = "Sai mật khẩu!";
    }
} else {
    $_SESSION['error'] = "Không tìm thấy tài khoản!";
}

// Quay lại trang đăng nhập nếu có lỗi
header("Location: ../view/dangnhap.php");
exit();
?>
