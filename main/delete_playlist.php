<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Bạn cần đăng nhập.");
}

$userId = $_SESSION['user_id'];
$playlistId = $_POST['playlist_id'] ?? null;

if ($playlistId) {
  // Chỉ xóa nếu playlist thuộc về user
  $stmt = $conn->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $playlistId, $userId);
  $stmt->execute();
}

header("Location: channel.php");
exit;
