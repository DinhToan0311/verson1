<?php
session_start();
require '../../loginphp/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    http_response_code(400);
    echo "Thiếu thông tin";
    exit;
}

$userId = $_SESSION['user_id'];
$watchLaterId = intval($_POST['id']);

// Kiểm tra xem video có thuộc user không
$check = $conn->prepare("SELECT id FROM watch_later WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $watchLaterId, $userId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo "Không tìm thấy bản ghi";
    exit;
}

// Xóa khỏi danh sách
$delete = $conn->prepare("DELETE FROM watch_later WHERE id = ? AND user_id = ?");
$delete->bind_param("ii", $watchLaterId, $userId);
if ($delete->execute()) {
    echo "OK";
} else {
    echo "Lỗi khi xóa";
}
?>
