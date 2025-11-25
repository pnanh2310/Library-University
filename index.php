<?php
include 'functions/db_connect.php';
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
  <title>Thư viện Trường Đại học</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>

<!-- Thanh điều hướng -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">TRANG CHỦ</a>
    <a class="navbar-brand fw-bold" href="view/muon_sach.php" >Mượn Sách</a>
    <a class="navbar-brand fw-bold" href="view/sanpham.php" >Tất Cả Sách</a>
    <a class="navbar-brand fw-bold" href="view/don_muon_sach.php" >Đơn Của Bạn</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="handle/dangxuat.php">Đăng xuất</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Banner -->
<div class="w-100" style="background: url('img/anhmenu.png') center/cover no-repeat; height: 400px;"></div>

<!-- Tìm kiếm -->
<div class="container my-5">
  <div class="text-center">
    <h2 class="fw-bold text-danger mb-4">TÌM KIẾM SÁCH</h2>

    <!-- Form tìm kiếm -->
    <form id="searchForm" class="d-flex justify-content-center mb-4">
      <input type="text" id="keyword" name="keyword" class="form-control rounded-pill me-2"
             placeholder="Nhập tên sách, tác giả hoặc thể loại..." 
             style="max-width: 500px; padding: 10px 20px;">
      <button type="submit" class="btn btn-danger rounded-circle px-3 py-2">🔍</button>
    </form>
  </div>

  <!-- Kết quả sẽ được load tại đây -->
  <div id="searchResults" class="mt-5"></div>
</div>

<!-- Sản phẩm nổi bật -->
<div class="container my-5">
  <h3 class="fw-bold text-danger mb-4 text-center">SÁCH NỔI BẬT NHẤT</h3>
  <div id="topBooks"></div> <!-- Div rỗng, sẽ load AJAX -->
</div>

</body>
</html>

<script>
$(document).ready(function(){
  // Tìm kiếm
  $('#searchForm').on('submit', function(e){
    e.preventDefault();
    loadResults(1);
  });

  function loadResults(page){
    const keyword = $('#keyword').val().trim();
    if(keyword === '') return;
    $.ajax({
      url: 'handle/timkiem.php',
      type: 'GET',
      data: { keyword: keyword, page: page },
      beforeSend: function(){
        $('#searchResults').html("<div class='text-center text-secondary'>Đang tải dữ liệu...</div>");
      },
      success: function(data){
        $('#searchResults').html(data);
      }
    });
  }

  $(document).on('click', '.pagination a', function(e){
    e.preventDefault();
    const page = $(this).data('page');
    loadResults(page);
  });

  // Load top books
  $('#topBooks').load('handle/topbooks.php');
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
