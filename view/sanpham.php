<?php
include '../functions/db_connect.php';
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: view/dangnhap.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tất cả Sách | Thư viện Đại học</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>

<!-- Thanh điều hướng -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">TRANG CHỦ</a>
    <a class="navbar-brand fw-bold" href="muon_sach.php">Mượn Sách</a>
    <a class="navbar-brand fw-bold" href="sanpham.php">Tất Cả Sách</a>
    <a class="navbar-brand fw-bold" href="view/don_muon_sach.php" >Đơn Của Bạn</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="../handle/dangxuat.php">Đăng xuất</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Tiêu đề -->
<div class="container my-5">
  <div class="text-center">
    <h2 class="fw-bold text-danger mb-4">TẤT CẢ SÁCH TRONG THƯ VIỆN</h2>

    <!-- Thanh tìm kiếm -->
    <form id="searchForm" class="d-flex justify-content-center mb-4">
      <input type="text" id="keyword" class="form-control rounded-pill me-2"
             placeholder="Nhập tên sách, tác giả hoặc thể loại..."
             style="max-width: 500px; padding: 10px 20px;">
      <button type="submit" class="btn btn-danger rounded-circle px-3 py-2">🔍</button>
    </form>
  </div>

  <!-- Kết quả tìm kiếm / danh sách sách -->
  <div id="bookList"></div>
</div>

</body>
</html>

<script>
$(document).ready(function(){
  // Load danh sách sách ban đầu
  loadBooks(1);

  // Khi tìm kiếm
  $('#searchForm').on('submit', function(e){
    e.preventDefault();
    loadBooks(1);
  });

  // Hàm tải dữ liệu (có phân trang)
  function loadBooks(page){
    const keyword = $('#keyword').val().trim();
    $.ajax({
      url: '../handle/load_sach.php',
      type: 'GET',
      data: { keyword: keyword, page: page },
      beforeSend: function(){
        $('#bookList').html("<div class='text-center text-secondary'>Đang tải dữ liệu...</div>");
      },
      success: function(data){
        $('#bookList').html(data);
      }
    });
  }

  // Bắt sự kiện phân trang
  $(document).on('click', '.pagination a', function(e){
    e.preventDefault();
    const page = $(this).data('page');
    loadBooks(page);
  });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
