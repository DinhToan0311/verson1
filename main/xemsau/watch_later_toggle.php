<?php
session_start();
require '../../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Bạn cần đăng nhập.");
}

$userId = $_SESSION['user_id'];
$videoId = $_POST['video_id'] ?? null;

if (!$videoId) {
  die("Thiếu video.");
}

// Kiểm tra đã tồn tại chưa
$stmt = $conn->prepare("SELECT id FROM watch_later WHERE user_id = ? AND video_id = ?");
$stmt->bind_param("ii", $userId, $videoId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  // Nếu đã có → Xoá
  $stmt = $conn->prepare("DELETE FROM watch_later WHERE user_id = ? AND video_id = ?");
  $stmt->bind_param("ii", $userId, $videoId);
  $stmt->execute();
} else {
  // Nếu chưa có → Thêm
  $stmt = $conn->prepare("INSERT INTO watch_later (user_id, video_id) VALUES (?, ?)");
  $stmt->bind_param("ii", $userId, $videoId);
  $stmt->execute();
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
