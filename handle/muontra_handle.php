<?php
include __DIR__ . '/../functions/db_connect.php';
include __DIR__ . '/../functions/log_helper.php'; // THÊM DÒNG NÀY
session_start();

// Chỉ cho phép admin hoặc thủ thư
if (!isset($_SESSION['user']) || !in_array(strtolower(trim($_SESSION['user']['vai_tro'])), ['admin', 'thuthu'])) {
    http_response_code(403);
    echo "403 - Bạn không có quyền truy cập trang này.";
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$errorMsg = '';
$today = date('Y-m-d');

// ======================== XỬ LÝ FORM THÊM / SỬA ========================
if ($action==='addForm' || ($action==='editForm' && $id)) {

    // Lấy danh sách người dùng và sách
    $nguoidungs = $conn->query("SELECT id, ho_ten, msv, ngay_toi_da_muon, loi_vi_pham FROM nguoidung ORDER BY ho_ten");
    $saches = $conn->query("SELECT id, ten_sach FROM sach ORDER BY ten_sach");

    // Nếu edit
    $mt = null;
    if($action==='editForm'){
        $stmt = $conn->prepare("
            SELECT mt.*, nd.ho_ten, nd.msv, s.ten_sach, nd.id AS id_nguoidung
            FROM muon_tra mt
            JOIN nguoidung nd ON mt.id_nguoidung = nd.id
            JOIN sach s ON mt.id_sach = s.id
            WHERE mt.id=?
        ");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $mt = $stmt->get_result()->fetch_assoc();
        if(!$mt) exit("Không tìm thấy phiếu mượn!");
    }

    // ======================== XỬ LÝ POST ========================
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $idNguoiDung = $_POST['id_nguoidung'] ?? 0;
        $idSach = $_POST['id_sach'] ?? 0;
        $ngayMuon = $_POST['ngay_muon'] ?? $today;
        $ngayTra = $_POST['ngay_tra'] ?? $today;
        $trangThai = $action==='addForm' ? 'Đang mượn' : ($_POST['trang_thai'] ?? 'Đang mượn');

        // Lấy thông tin vi phạm
        $user = $conn->query("SELECT ho_ten, loi_vi_pham FROM nguoidung WHERE id=$idNguoiDung")->fetch_assoc();

        // Kiểm tra vi phạm nếu thêm mới
        if($action==='addForm' && $user['loi_vi_pham'] >=3){
            $errorMsg = "<b>{$user['ho_ten']}</b> đã vi phạm 3 lần, không thể mượn thêm!";
        } else {
            if($action==='addForm'){
                // ĐÃ XÓA MSV KHỎI INSERT
                $stmt = $conn->prepare("INSERT INTO muon_tra (id_nguoidung, id_sach, ngay_muon, ngay_tra, trang_thai) VALUES (?,?,?,?,?)");
                $stmt->bind_param("iisss",$idNguoiDung,$idSach,$ngayMuon,$ngayTra,$trangThai);
                if($stmt->execute()){
                    // THÊM GHI LỊCH SỬ
                    $sach_info = $conn->query("SELECT ten_sach FROM sach WHERE id=$idSach")->fetch_assoc();
                    ghi_lich_su("Thêm phiếu mượn sách: {$sach_info['ten_sach']}", "muon_tra", $conn->insert_id);
                }
            } else if($action==='editForm'){
                $stmt = $conn->prepare("UPDATE muon_tra SET trang_thai=? WHERE id=?");
                $stmt->bind_param("si",$trangThai,$id);
                if($stmt->execute()){
                    // THÊM GHI LỊCH SỬ
                    ghi_lich_su("Cập nhật trạng thái phiếu mượn #$id thành: $trangThai", "muon_tra", $id);

                    // Nếu trạng thái đổi sang Quá hạn, tăng số vi phạm
                    if($trangThai==='Quá hạn'){
                        $conn->query("UPDATE nguoidung SET loi_vi_pham=loi_vi_pham+1 WHERE id=$idNguoiDung");
                        ghi_lich_su("Tăng lỗi vi phạm cho người dùng ID: $idNguoiDung", "nguoidung", $idNguoiDung);
                    }
                }
            }
            // Chuyển về trang quản lý sau khi thành công
            header("Location: ../admin/qlmuontra.php");
            exit();
        }
    }

    // ======================== HIỂN THỊ FORM ========================
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title><?= $action==='addForm'?'Thêm':'Sửa' ?> phiếu mượn</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>input[readonly]{background:#e9ecef;}</style>
    </head>
    <body class="p-5 bg-light">
    <div class="container">
        <h3 class="mb-4"><?= $action==='addForm'?'📘 Thêm':'✏️ Sửa' ?> Phiếu Mượn Sách</h3>

        <?php if($errorMsg): ?>
            <div class="alert alert-danger"><?= $errorMsg ?></div>
        <?php endif; ?>

        <form method="POST">

        <?php if($action==='addForm'): ?>
            <!-- Người mượn -->
            <div class="mb-3">
                <label class="form-label">Người mượn</label>
                <input list="nguoidung_list" id="nguoidung_input" class="form-control" placeholder="Gõ tên để chọn..." required
                       value="<?= $_POST['nguoidung_input'] ?? '' ?>">
                <datalist id="nguoidung_list">
                    <?php while($u=$nguoidungs->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($u['ho_ten']) ?>" data-id="<?= $u['id'] ?>" data-msv="<?= htmlspecialchars($u['msv']) ?>" data-max="<?= $u['ngay_toi_da_muon'] ?>"></option>
                    <?php endwhile; ?>
                </datalist>
                <input type="hidden" name="id_nguoidung" id="id_nguoidung" value="<?= $_POST['id_nguoidung'] ?? '' ?>">
            </div>

            <!-- ĐÃ XÓA TRƯỜNG MSV TRONG FORM -->

            <!-- Sách -->
            <div class="mb-3">
                <label class="form-label">Sách</label>
                <input list="sach_list" id="sach_input" class="form-control" placeholder="Gõ tên sách..." required
                       value="<?= $_POST['sach_input'] ?? '' ?>">
                <datalist id="sach_list">
                    <?php while($s=$saches->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($s['ten_sach']) ?>" data-id="<?= $s['id'] ?>"></option>
                    <?php endwhile; ?>
                </datalist>
                <input type="hidden" name="id_sach" id="id_sach" value="<?= $_POST['id_sach'] ?? '' ?>">
            </div>

            <!-- Ngày mượn -->
            <div class="mb-3">
                <label class="form-label">Ngày mượn</label>
                <input type="date" name="ngay_muon" id="ngay_muon" class="form-control" value="<?= $_POST['ngay_muon'] ?? $today ?>" required>
            </div>

            <!-- Ngày trả -->
            <div class="mb-3">
                <label class="form-label">Ngày trả (dự kiến)</label>
                <input type="date" name="ngay_tra" id="ngay_tra" class="form-control" readonly value="<?= $_POST['ngay_tra'] ?? $today ?>" required>
            </div>

        <?php else: ?>
            <!-- FORM SỬA -->
            <input type="hidden" name="id_nguoidung" value="<?= $mt['id_nguoidung'] ?>">
            <div class="mb-3"><label>Người mượn</label><input class="form-control" value="<?= htmlspecialchars($mt['ho_ten']) ?>" readonly></div>
            <div class="mb-3"><label>Mã sinh viên</label><input class="form-control" value="<?= htmlspecialchars($mt['msv']) ?>" readonly></div>
            <div class="mb-3"><label>Sách</label><input class="form-control" value="<?= htmlspecialchars($mt['ten_sach']) ?>" readonly></div>
            <div class="mb-3"><label>Ngày mượn</label><input class="form-control" value="<?= $mt['ngay_muon'] ?>" readonly></div>
            <div class="mb-3"><label>Ngày trả</label><input class="form-control" value="<?= $mt['ngay_tra'] ?>" readonly></div>
            <div class="mb-3">
                <label>Trạng thái</label>
                <select name="trang_thai" class="form-select" required>
                    <option value="Đang mượn" <?= ($mt['trang_thai']=='Đang mượn')?'selected':'' ?>>Đang mượn</option>
                    <option value="Đã trả" <?= ($mt['trang_thai']=='Đã trả')?'selected':'' ?>>Đã trả</option>
                    <option value="Quá hạn" <?= ($mt['trang_thai']=='Quá hạn')?'selected':'' ?>>Quá hạn</option>
                </select>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary"><?= $action==='addForm'?'Lưu':'Cập nhật' ?></button>
        <a href="../admin/qlmuontra.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>

    <script>
        const nguoidungInput = document.getElementById('nguoidung_input');
        const idNguoidung = document.getElementById('id_nguoidung');
        const borrowDate = document.getElementById('ngay_muon');
        const returnDate = document.getElementById('ngay_tra');
        const sachInput = document.getElementById('sach_input');
        const idSach = document.getElementById('id_sach');

        function updateUser(){
            const opts = document.querySelectorAll('#nguoidung_list option');
            let found=false;
            opts.forEach(opt=>{
                if(opt.value===nguoidungInput.value){
                    idNguoidung.value=opt.dataset.id;
                    const max=parseInt(opt.dataset.max||7);
                    const borrow=new Date(borrowDate.value);
                    borrow.setDate(borrow.getDate()+max);
                    returnDate.value=borrow.toISOString().slice(0,10);
                    found=true;
                }
            });
            if(!found){idNguoidung.value='';returnDate.value='';}
        }

        function updateReturnDate(){updateUser();}
        nguoidungInput.addEventListener('input',updateUser);
        borrowDate.addEventListener('change',updateReturnDate);

        sachInput.addEventListener('input',function(){
            const opts=document.querySelectorAll('#sach_list option');
            let found=false;
            opts.forEach(opt=>{ if(opt.value===sachInput.value){idSach.value=opt.dataset.id;found=true;}});
            if(!found) idSach.value='';
        });
    </script>
    </body>
    </html>
    <?php
    exit();
}
// ======================== DUYỆT PHIẾU ========================
if ($action==='approve' && $id){
    // Lấy thông tin phiếu mượn
    $mt = $conn->query("SELECT id_nguoidung, ngay_muon FROM muon_tra WHERE id=$id")->fetch_assoc();
    if($mt){
        // Lấy số ngày tối đa mượn của người dùng
        $user = $conn->query("SELECT ngay_toi_da_muon FROM nguoidung WHERE id={$mt['id_nguoidung']}")->fetch_assoc();
        $maxDays = intval($user['ngay_toi_da_muon'] ?? 7);

        // Tính ngày trả dự kiến
        $borrowDate = new DateTime($mt['ngay_muon']);
        $borrowDate->modify("+{$maxDays} days");
        $ngayTra = $borrowDate->format('Y-m-d');

        // Cập nhật trạng thái phiếu mượn
        $stmt = $conn->prepare("UPDATE muon_tra SET duyet_don='Đã duyệt', trang_thai='Đang mượn', ngay_tra=? WHERE id=?");
        $stmt->bind_param("si",$ngayTra,$id);
        if($stmt->execute()){
            // THÊM GHI LỊCH SỬ
            ghi_lich_su("Duyệt phiếu mượn sách #$id", "muon_tra", $id);
        }
    }
    header("Location: ../admin/qlmuontra.php");
    exit();
}

// ======================== TỪ CHỐI PHIẾU ========================
if ($action==='reject' && $id){
    $stmt = $conn->prepare("UPDATE muon_tra SET duyet_don='Từ chối', trang_thai='Từ chối' WHERE id=?");
    $stmt->bind_param("i",$id);
    if($stmt->execute()){
        // THÊM GHI LỊCH SỬ
        ghi_lich_su("Từ chối phiếu mượn sách #$id", "muon_tra", $id);
    }
    header("Location: ../admin/qlmuontra.php");
    exit();
}

// ======================== XÓA ========================
if ($action==='delete' && $id){
    $stmt = $conn->prepare("DELETE FROM muon_tra WHERE id=?");
    $stmt->bind_param("i",$id);
    if($stmt->execute()){
        // THÊM GHI LỊCH SỬ
        ghi_lich_su("Xóa phiếu mượn sách #$id", "muon_tra", $id);
    }
    header("Location: ../admin/qlmuontra.php");
    exit();
}

echo "Hành động không hợp lệ!";
?>