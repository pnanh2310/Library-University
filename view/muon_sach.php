<?php
include '../functions/db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: dangnhap.php");
    exit();
}

$user = $_SESSION['user'];

// Lấy ID và tên sách từ GET nếu có
$id_sach_prefill = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ten_sach_prefill = isset($_GET['ten']) ? $_GET['ten'] : '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Mượn sách</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <style>
        #sach_suggestions {
            position: absolute;
            z-index: 1000;
            width: 100%;
        }
        .sach-item {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5 p-4 bg-white rounded shadow-sm">
            <a href="../index.php" class="btn btn-secondary mt-3">← Quay lại Trang chủ</a>

    <h3 class="text-center mb-4 text-primary fw-bold">📚 Mượn Sách</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success text-center fw-bold">✅ Mượn sách thành công!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger text-center fw-bold">❌ Mượn sách thất bại, vui lòng thử lại!</div>
    <?php endif; ?>

    <div class="row">
        <!-- Form mượn sách -->
        <div class="col-md-6 position-relative">
            <form id="muonForm" action="../functions/xu_ly_muon_sach.php" method="POST">
                <input type="hidden" name="msv" value="<?= htmlspecialchars($user['msv'] ?? '') ?>">
                <input type="hidden" name="ho_ten" value="<?= htmlspecialchars($user['ho_ten'] ?? '') ?>">
                <input type="hidden" id="id_sach" name="id_sach" value="<?= $id_sach_prefill ?>">
                <input type="hidden" name="ten_sach" id="ten_sach_hidden" value="<?= htmlspecialchars($ten_sach_prefill) ?>">

                <div class="mb-3">
                    <label class="form-label">Mã sinh viên:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['msv']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tên người mượn:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['ho_ten'] ?? '') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tên sách:</label>
                    <input type="text" id="ten_sach_input" class="form-control" placeholder="Nhập tên sách..." autocomplete="off" required
                           value="<?= htmlspecialchars($ten_sach_prefill) ?>">
                    <div id="sach_suggestions" class="list-group"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ngày mượn:</label>
                    <input type="date" name="ngay_muon" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold">Xác nhận mượn</button>
            </form>
        </div>

        <!-- Thông tin sách -->
        <div class="col-md-6" id="info_sach">
            <div class="text-center text-muted">Chọn sách để xem thông tin chi tiết</div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Nếu có sẵn id sách từ URL, load thông tin bằng get_sach.php
    let prefillId = <?= $id_sach_prefill ?: 'null' ?>;
    if(prefillId){
        $.ajax({
            url: '../functions/get_sach.php',
            type: 'GET',
            data: { id: prefillId },
            success: function(data){
                $('#info_sach').html(data);
            }
        });
    }

    // Gợi ý sách khi gõ
    $('#ten_sach_input').on('input', function(){
        const keyword = $(this).val().trim();
        if(keyword === '') {
            $('#sach_suggestions').empty();
            $('#info_sach').html('<div class="text-center text-muted">Chọn sách để xem thông tin chi tiết</div>');
            $('#id_sach').val('');
            return;
        }

        $.ajax({
            url: '../functions/search_sach.php',
            type: 'GET',
            data: { keyword: keyword },
            success: function(data){
                $('#sach_suggestions').html(data);
            }
        });
    });

    // Khi chọn sách từ gợi ý
    $(document).on('click', '.sach-item', function(){
        const id = $(this).data('id');
        const ten = $(this).text();

        $('#id_sach').val(id);
        $('#ten_sach_input').val(ten);
        $('#ten_sach_hidden').val(ten);
        $('#sach_suggestions').empty();

        // Lấy thông tin sách
        $.ajax({
            url: '../functions/get_sach.php',
            type: 'GET',
            data: { id: id },
            success: function(data){
                $('#info_sach').html(data);
            }
        });
    });

    // Submit form
    $('#muonForm').on('submit', function(e){
        const tenSach = $('#ten_sach_input').val().trim();
        if(!tenSach) {
            e.preventDefault();
            alert('Vui lòng nhập hoặc chọn sách hợp lệ!');
            return;
        }
        $('#ten_sach_hidden').val(tenSach);
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
