<h2 align="center">
    <a href="https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin">
    🎓 Faculty of Information Technology (DaiNam University)
    </a>
</h2>
<h2 align="center">
    XÂY DỰNG ỨNG DỤNG WEB QUẢN LÝ THƯ VIỆN TRƯỜNG ĐẠI HỌC
</h2>
<div align="center">
    <p align="center">
        <img src="img/logo1.png" alt="Logo 1" width="170"/>
        <img src="img/logo2.png" alt="Logo 2" width="180"/>
        <img src="img/logo3.png" alt="DaiNam University Logo" width="200"/>
    </p>

[![AIoTLab](https://img.shields.io/badge/AIoTLab-green?style=for-the-badge)](https://www.facebook.com/DNUAIoTLab)
[![Faculty of Information Technology](https://img.shields.io/badge/Faculty%20of%20Information%20Technology-blue?style=for-the-badge)](https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin)
[![DaiNam University](https://img.shields.io/badge/DaiNam%20University-orange?style=for-the-badge)](https://dainam.edu.vn/)

</div>

---

# 1. 📘 Giới thiệu

Hệ thống **quản lý thư viện trường đại học** được xây dựng nhằm mô phỏng quy trình hoạt động cơ bản của một thư viện hiện đại:

- Quản lý danh mục sách (thêm/sửa/xóa, phân loại, năm xuất bản, tác giả,…)
- Quản lý người dùng (sinh viên, giảng viên, cán bộ)
- Quản lý mượn – trả sách (thời gian mượn, hạn trả, số lần mượn)
- Theo dõi tình trạng sách (còn / hết / hỏng / mất)
- Thống kê – báo cáo (sách mượn nhiều nhất, số lượt mượn theo thời gian,…)

Ứng dụng gồm 2 nhóm người dùng chính:

- **Admin** – Quản trị hệ thống / Thủ thư  
- **User** – Người dùng thông thường (sinh viên, giảng viên,…)

---

## 🔧 2. Các công nghệ được sử dụng
<div align="center">

### Hệ điều hành
![macOS](https://img.shields.io/badge/macOS-000000?style=for-the-badge&logo=macos&logoColor=F0F0F0)
[![Windows](https://img.shields.io/badge/Windows-0078D6?style=for-the-badge&logo=windows&logoColor=white)](https://www.microsoft.com/en-us/windows/)
[![Ubuntu](https://img.shields.io/badge/Ubuntu-E95420?style=for-the-badge&logo=ubuntu&logoColor=white)](https://ubuntu.com/)

### Công nghệ chính
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](#)
[![CSS](https://img.shields.io/badge/CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)](#)
[![SCSS](https://img.shields.io/badge/SCSS-CC6699?style=for-the-badge&logo=sass&logoColor=white)](#)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](#)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

### Web Server & Database
[![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/) 
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://www.apachefriends.org/)

### Database Management Tools
[![MySQL Workbench](https://img.shields.io/badge/MySQL_Workbench-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://dev.mysql.com/downloads/workbench/)
</div>

---

# 3. 🚀 Hình ảnh các chức năng

## **Trang đăng nhập hệ thống**
<p align="center">
  <img src="img/login_library.png" width="700">
</p>

## **Trang quản lý sách (Dashboard Admin)**
<p align="center">
  <img src="img/admin_library.png" width="700">
</p>

## **Trang thư viện dành cho người dùng (User)**
<p align="center">
  <img src="img/user_library.png" width="700">
</p>

---

# 4. ⚙ Cài đặt

## **4.1 Cài đặt công cụ, môi trường cần thiết**

### ✔ Cài XAMPP
https://www.apachefriends.org/download.html

### ✔ VS Code Extensions
- PHP Intellisense  
- MySQL  
- Prettier  
- PHP Debug  

---

## **4.2 Tải project**

```bash
cd C:\xampp\htdocs
git clone <link-github-project> library-management
```

Truy cập:
```
http://localhost/library-management
```

---

## **4.3 Setup database**

```sql
CREATE DATABASE IF NOT EXISTS library_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

## **4.4 Setup tham số kết nối**

```php
<?php
function getPDO() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = 'localhost';
    $db   = 'library_management';
    $user = 'root';
    $pass = '';           
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        die('Lỗi kết nối database: ' . $e->getMessage());
    }
}
```

---

## **4.5 Chạy hệ thống**

Mở XAMPP → Start Apache + MySQL  
Sau đó truy cập:

```
http://localhost/library-management/
```

---

## **4.6 Đăng nhập lần đầu**

### **Admin**
- Email: `admin@library.com`
- Mật khẩu: `123456`

### **User**
- Email: `user1@library.com`
- Mật khẩu: `123456`

---

# 🗂 Cấu trúc thư mục

```
/BTL
│── /admin
│── /css
│── /docs
│── /functions
│── /handle
│── /img
│── /view
└── index.php
```

---
