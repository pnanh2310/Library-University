<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập hệ thống</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
  }

  body {
    background: url('../img/anh1.png') no-repeat center center/cover;
    background-attachment: fixed;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
    overflow: hidden;
  }

  .login-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 40px 35px;
    width: 400px;
    color: #fff;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    animation: fadeIn 0.8s ease;
  }

  .login-card h3 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
  }

  .form-control {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    border-radius: 10px;
    height: 45px;
  }

  .form-control::placeholder {
    color: #ddd;
  }

  .form-control:focus {
    box-shadow: none;
    border: 1px solid #ffc0a1;
    background: rgba(255, 255, 255, 0.25);
  }

  .btn-login {
    width: 100%;
    background: #ffb599;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-weight: 600;
    color: #333;
    transition: all 0.3s ease;
  }

  .btn-login:hover {
    background: #ff8f66;
    color: white;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  </style>
</head>
<body>
  <div class="login-card">
    <h3>Đăng nhập hệ thống</h3>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger text-center">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <form action="../handle/dangnhap_xuly.php" method="POST">
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
      </div>
      <div class="mb-3">
        <input type="password" name="mat_khau" class="form-control" placeholder="Mật khẩu" required>
      </div>
      <button type="submit" class="btn-login mt-3">ĐĂNG NHẬP</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
