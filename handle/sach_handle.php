<?php
session_start();
include __DIR__ . '/../functions/db_connect.php';
include __DIR__ . '/../functions/log_helper.php'; // THÊM DÒNG NÀY

// 🧱 Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../view/dangnhap.php");
    exit();
}

$user_id = $_SESSION['user']['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 🧱 Kiểm tra quyền
if (!$user || !in_array(strtolower(trim($user['vai_tro'])), ['admin', 'thuthu'])) {
    http_response_code(403);
    echo "403 - Bạn không có quyền truy cập trang này.";
    exit();
}

// ========================= CÁC BIẾN CHÍNH =========================
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$uploadDir = "../img/";

// ========================= HÀM HỖ TRỢ =========================
function uploadImage($file, $uploadDir) {
    if (!isset($file['tmp_name']) || !$file['tmp_name']) return null;

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid("sach_") . "." . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return "img/" . $filename; // lưu đường dẫn tương đối
    }
    return null;
}

// ========================= FORM THÊM / SỬA =========================
if ($action === 'addForm' || ($action === 'editForm' && $id)) {
    $isEdit = $action === 'editForm';
    $book = null;

    if ($isEdit) {
        $stmt = $conn->prepare("SELECT * FROM sach WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        if (!$book) {
            echo "Không tìm thấy sách!";
            exit();
        }
    }

    // Nếu submit form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ten_sach = trim($_POST['ten_sach'] ?? '');
        $tac_gia = trim($_POST['tac_gia'] ?? '');
        $nam_xuat_ban = trim($_POST['nam_xuat_ban'] ?? '');
        $the_loai = trim($_POST['the_loai'] ?? '');
        $so_luong = (int)($_POST['so_luong'] ?? 0);
        $mo_ta = trim($_POST['mo_ta'] ?? '');

        if (empty($ten_sach) || empty($tac_gia) || $so_luong < 0) {
            echo "<script>alert('Vui lòng nhập đủ thông tin hợp lệ!'); history.back();</script>";
            exit();
        }

        // Upload ảnh (nếu có)
        $hinh_anh = uploadImage($_FILES['hinh_anh'] ?? null, $uploadDir);
        if ($isEdit && !$hinh_anh) {
            $hinh_anh = $book['hinh_anh'] ?? null;
        }

        // ================== XỬ LÝ SQL ==================
        if ($isEdit) {
            $stmt = $conn->prepare("
                UPDATE sach 
                SET ten_sach=?, tac_gia=?, nam_xuat_ban=?, the_loai=?, so_luong=?, hinh_anh=?, mo_ta=? 
                WHERE id=?
            ");
            // 7 giá trị + id
            $stmt->bind_param("ssssisis", $ten_sach, $tac_gia, $nam_xuat_ban, $the_loai, $so_luong, $hinh_anh, $mo_ta, $id);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO sach (ten_sach, tac_gia, nam_xuat_ban, the_loai, so_luong, hinh_anh, mo_ta, so_lan_muon)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->bind_param("ssssssis", $ten_sach, $tac_gia, $nam_xuat_ban, $the_loai, $so_luong, $hinh_anh, $mo_ta);
        }

        if ($stmt->execute()) {
            // THÊM GHI LỊCH SỬ
            if($isEdit){
                ghi_lich_su("Cập nhật thông tin sách: $ten_sach", "sach", $id);
            } else {
                ghi_lich_su("Thêm sách mới: $ten_sach", "sach", $conn->insert_id);
            }
            header("Location: ../admin/qlsach.php");
            exit();
        } else {
            echo "❌ Lỗi khi lưu dữ liệu: " . $stmt->error;
            exit();
        }
    }

    // ========================= GIAO DIỆN FORM =========================
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title><?= $isEdit ? 'Sửa Sách' : 'Thêm Sách' ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background:#f4f7fa; font-family:'Segoe UI'; }
            .container { background:#fff; padding:30px; border-radius:10px; margin-top:50px; max-width:700px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
            img.preview { width:100px; height:140px; object-fit:cover; border-radius:6px; margin-top:10px; }
        </style>
    </head>
    <body>
    <div class="container">
        <h3 class="mb-4 text-center"><?= $isEdit ? '✏️ Sửa Thông Tin Sách' : '📘 Thêm Sách Mới' ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Tên Sách</label>
                <input type="text" name="ten_sach" class="form-control" required value="<?= htmlspecialchars($book['ten_sach'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Tác Giả</label>
                <input type="text" name="tac_gia" class="form-control" required value="<?= htmlspecialchars($book['tac_gia'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Năm Xuất Bản</label>
                <input type="text" name="nam_xuat_ban" class="form-control" value="<?= htmlspecialchars($book['nam_xuat_ban'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Thể Loại</label>
                <input type="text" name="the_loai" class="form-control" value="<?= htmlspecialchars($book['the_loai'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Số Lượng</label>
                <input type="number" name="so_luong" class="form-control" min="0" required value="<?= htmlspecialchars($book['so_luong'] ?? 0) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Mô Tả Sách</label>
                <textarea name="mo_ta" class="form-control" rows="4"><?= htmlspecialchars($book['mo_ta'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Ảnh Bìa</label>
                <input type="file" name="hinh_anh" class="form-control" accept="image/*">
                <?php if ($isEdit && !empty($book['hinh_anh'])): ?>
                    <img src="../<?= htmlspecialchars($book['hinh_anh']) ?>" class="preview" alt="Ảnh hiện tại">
                <?php endif; ?>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Cập nhật' : 'Thêm mới' ?></button>
                <a href="../admin/qlsach.php" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// ========================= XÓA SÁCH =========================
if ($action === 'delete' && $id) {
    $stmt = $conn->prepare("DELETE FROM sach WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        // THÊM GHI LỊCH SỬ
        ghi_lich_su("Xóa sách ID: $id", "sach", $id);
    }
    header("Location: ../admin/qlsach.php");
    exit();
}

echo "Hành động không hợp lệ!";
?>