<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id'])) {
    die("Không hợp lệ.");
}

$userId = $_SESSION['user_id'];
$videoId = intval($_POST['video_id']);

$stmt = $conn->prepare("DELETE FROM watch_history WHERE user_id = ? AND video_id = ?");
$stmt->bind_param("ii", $userId, $videoId);
$stmt->execute();

header("Location: history.php");
exit;
