<?php
function ghi_lich_su($hanh_dong, $bang_lien_quan = null, $id_ban_ghi = null) {
    include 'db_connect.php';
    
    $id_nguoidung = $_SESSION['user']['id'] ?? null;
    
    $stmt = $conn->prepare("
        INSERT INTO lich_su (id_nguoidung, hanh_dong, bang_lien_quan, id_ban_ghi) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("issi", $id_nguoidung, $hanh_dong, $bang_lien_quan, $id_ban_ghi);
    return $stmt->execute();
}
?>