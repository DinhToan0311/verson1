<?php
require '../loginphp/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

$userId = $_SESSION['user_id'];
$videoId = (int)($_POST['video_id'] ?? 0);
$playlistId = (int)($_POST['playlist_id'] ?? 0);

if ($videoId <= 0 || $playlistId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("INSERT IGNORE INTO playlist_videos (playlist_id, video_id) VALUES (?, ?)");
$stmt->bind_param("ii", $playlistId, $videoId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể thêm']);
}
