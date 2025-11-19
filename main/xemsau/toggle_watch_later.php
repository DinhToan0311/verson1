<?php
session_start();
require '../../loginphp/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id'])) {
    http_response_code(400);
    echo "Thiếu thông tin.";
    exit;
}

$userId = $_SESSION['user_id'];
$videoId = intval($_POST['video_id']);

// Kiểm tra đã có trong danh sách chưa
$check = $conn->prepare("SELECT id FROM watch_later WHERE user_id = ? AND video_id = ?");
$check->bind_param("ii", $userId, $videoId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Xoá khỏi danh sách xem sau
    $delete = $conn->prepare("DELETE FROM watch_later WHERE user_id = ? AND video_id = ?");
    $delete->bind_param("ii", $userId, $videoId);
    $delete->execute();
    echo "removed";
} else {
    // Thêm vào danh sách xem sau
    $insert = $conn->prepare("INSERT INTO watch_later (user_id, video_id, added_at) VALUES (?, ?, NOW())");
    $insert->bind_param("ii", $userId, $videoId);
    $insert->execute();
    echo "added";
}
?>
