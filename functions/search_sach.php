<?php
include '../functions/db_connect.php';

$keyword = $_GET['keyword'] ?? '';
$keyword = "%$keyword%";

$stmt = $conn->prepare("SELECT id, ten_sach FROM sach WHERE ten_sach LIKE ? LIMIT 10");
$stmt->bind_param('s', $keyword);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    echo '<button type="button" class="list-group-item list-group-item-action sach-item" data-id="'.$row['id'].'">'
         .htmlspecialchars($row['ten_sach']).'</button>';
}
?>
