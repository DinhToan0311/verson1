<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['message' => 'Bạn cần đăng nhập']);
    exit;
}

$videoId = intval($_POST['video_id']);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM watch_later WHERE user_id = ? AND video_id = ?");
$stmt->bind_param("ii", $userId, $videoId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['message' => 'Video đã có trong danh sách.']);
    exit;
}

$stmt->close();

$stmt = $conn->prepare("INSERT INTO watch_later (user_id, video_id) VALUES (?, ?)");
$stmt->bind_param("ii", $userId, $videoId);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Đã thêm vào danh sách xem sau.']);
} else {
    echo json_encode(['message' => 'Lỗi khi thêm.']);
}
