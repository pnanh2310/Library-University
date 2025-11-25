<?php
$servername = "localhost";
$username = "root"; // user mặc định của XAMPP
$password = "anhcus123";     // mật khẩu mặc định thường để trống
$database = "thu_vien";  // tên database bạn vừa tạo

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
