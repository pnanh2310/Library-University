<?php
include __DIR__ . '/../functions/db_connect.php';
include __DIR__ . '/../functions/log_helper.php'; // THÊM DÒNG NÀY
session_start();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user']) || strtolower(trim($_SESSION['user']['vai_tro'])) !== 'admin') {
    header("Location: ../view/dangnhap.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

/* ======================== THÊM MỚI ======================== */
if ($action === 'addForm') {
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm Người dùng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
<div class="container">
  <h3 class="mb-4">➕ Thêm Người dùng Mới</h3>
  <form method="POST" action="nguoidung_handle.php?action=add">
    <div class="mb-3">
      <label class="form-label">Họ tên</label>
      <input type="text" name="ho_ten" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input type="text" name="mat_khau" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mã số (MSV/Mã NV)</label>
      <input type="text" name="msv" class="form-control" required placeholder="Nhập mã sinh viên hoặc mã nhân viên">
    </div>
    <div class="mb-3">
      <label class="form-label">Vai trò</label>
      <select name="vai_tro" class="form-control" required>
        <option value="admin">Admin</option>
        <option value="sinhvien">Sinh viên</option>
        <option value="thuthu">Thủ thư</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Lưu</button>
    <a href="../admin/qlnguoidung.php" class="btn btn-secondary">Hủy</a>
  </form>
</div>
</body>
</html>
<?php
exit();
}

/* ======================== XỬ LÝ THÊM ======================== */
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'];
    $email = $_POST['email'];
    $mat_khau = $_POST['mat_khau'];
    $vai_tro = $_POST['vai_tro'];
    $msv = $_POST['msv']; // MSV bắt buộc cho tất cả user

    // Kiểm tra MSV đã tồn tại chưa
    $check_stmt = $conn->prepare("SELECT id FROM nguoidung WHERE msv = ?");
    $check_stmt->bind_param("s", $msv);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('Mã số đã tồn tại! Vui lòng nhập mã khác.'); history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO nguoidung (ho_ten, email, mat_khau, vai_tro, msv) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $ho_ten, $email, $mat_khau, $vai_tro, $msv);
    
    if ($stmt->execute()) {
        // THÊM GHI LỊCH SỬ
        ghi_lich_su("Thêm người dùng mới: $ho_ten", "nguoidung", $conn->insert_id);
        header("Location: ../admin/qlnguoidung.php");
        exit();
    } else {
        echo "<script>alert('Có lỗi xảy ra!'); history.back();</script>";
        exit();
    }
}

/* ======================== FORM SỬA ======================== */
if ($action === 'editForm' && $id) {
    $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) exit("Không tìm thấy người dùng!");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Sửa Người dùng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
<div class="container">
  <h3 class="mb-4">✏️ Sửa Người dùng</h3>
  <form method="POST" action="nguoidung_handle.php?action=edit&id=<?= $user['id'] ?>">
    <div class="mb-3">
      <label class="form-label">Họ tên</label>
      <input type="text" name="ho_ten" class="form-control" value="<?= htmlspecialchars($user['ho_ten']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mật khẩu (giữ nguyên nếu không đổi)</label>
      <input type="text" name="mat_khau" class="form-control" value="<?= htmlspecialchars($user['mat_khau']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Mã số (MSV/Mã NV)</label>
      <input type="text" name="msv" class="form-control" value="<?= htmlspecialchars($user['msv'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Vai trò</label>
      <select name="vai_tro" class="form-control" required>
        <option value="admin" <?= $user['vai_tro']=='admin' ? 'selected' : '' ?>>Admin</option>
        <option value="sinhvien" <?= $user['vai_tro']=='sinhvien' ? 'selected' : '' ?>>Sinh viên</option>
        <option value="thuthu" <?= $user['vai_tro']=='thuthu' ? 'selected' : '' ?>>Thủ thư</option>
      </select>
    </div>
    <button type="submit" class="btn btn-success">Cập nhật</button>
    <a href="../admin/qlnguoidung.php" class="btn btn-secondary">Hủy</a>
  </form>
</div>
</body>
</html>
<?php
exit();
}

/* ======================== XỬ LÝ SỬA ======================== */
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'];
    $email = $_POST['email'];
    $mat_khau = $_POST['mat_khau'];
    $vai_tro = $_POST['vai_tro'];
    $msv = $_POST['msv']; // MSV bắt buộc cho tất cả user

    // Kiểm tra MSV đã tồn tại cho user khác chưa
    $check_stmt = $conn->prepare("SELECT id FROM nguoidung WHERE msv = ? AND id != ?");
    $check_stmt->bind_param("si", $msv, $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('Mã số đã tồn tại cho người dùng khác!'); history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE nguoidung SET ho_ten=?, email=?, mat_khau=?, vai_tro=?, msv=? WHERE id=?");
    $stmt->bind_param("sssssi", $ho_ten, $email, $mat_khau, $vai_tro, $msv, $id);
    
    if ($stmt->execute()) {
        // THÊM GHI LỊCH SỬ
        ghi_lich_su("Cập nhật thông tin người dùng: $ho_ten", "nguoidung", $id);
        header("Location: ../admin/qlnguoidung.php");
        exit();
    } else {
        echo "<script>alert('Có lỗi xảy ra!'); history.back();</script>";
        exit();
    }
}

/* ======================== XÓA ======================== */
if ($action === 'delete' && $id) {
    $stmt = $conn->prepare("DELETE FROM nguoidung WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        // THÊM GHI LỊCH SỬ
        ghi_lich_su("Xóa người dùng ID: $id", "nguoidung", $id);
    }
    header("Location: ../admin/qlnguoidung.php");
    exit();
}

echo "Hành động không hợp lệ!";
?>