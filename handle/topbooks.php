<?php
include '../functions/db_connect.php';

$limit = 6;
$sql_top = "SELECT * FROM sach ORDER BY so_lan_muon DESC LIMIT ?";
$stmt = $conn->prepare($sql_top);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    echo "<div class='row g-4'>";
    while($book = $result->fetch_assoc()){
        $url = '../btl/view/muon_sach.php?id='.$book['id'].'&ten='.urlencode($book['ten_sach']);
        echo "
        <div class='col-md-4'>
            <a href='{$url}' style='text-decoration: none;'>
            <div class='card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center' 
                style='border-radius:12px; background:#fff; transition:transform .2s, box-shadow .2s;'>
                <img src='{$book['hinh_anh']}' alt='Ảnh sách' 
                    style='width:110px; height:150px; object-fit:contain; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.15);'>
                <div class='ms-3 flex-grow-1'>
                <h6 class='fw-bold text-danger mb-2'>{$book['ten_sach']}</h6>
                <p class='text-muted mb-1'><b>Tác giả:</b> {$book['tac_gia']}</p>
                <p class='text-muted mb-1'><b>Thể loại:</b> {$book['the_loai']}</p>
                <p class='text-muted mb-1'><b>Số lần mượn:</b> {$book['so_lan_muon']}</p>
                </div>
            </div>
          </a>
        </div>";
    }
    echo "</div>";
} else {
    echo "<div class='text-center text-secondary'>Chưa có sách nổi bật</div>";
}
?>
