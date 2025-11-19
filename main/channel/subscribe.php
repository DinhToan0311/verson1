<?php
session_start();
require '../../loginphp/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'LOGIN_REQUIRED']);
    exit;
}

$subscriber_id = $_SESSION['user_id'];
$channel_id = $_POST['channel_id'] ?? null;

if (!$channel_id || !is_numeric($channel_id)) {
    echo json_encode(['status' => 'INVALID']);
    exit;
}

// ❌ Ngăn người dùng đăng ký kênh của chính mình
if ($subscriber_id == $channel_id) {
    echo json_encode(['status' => 'CANNOT_SUBSCRIBE_TO_SELF']);
    exit;
}

// Kiểm tra trùng (đã đăng ký)
$stmt = $conn->prepare("SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
$stmt->bind_param("ii", $subscriber_id, $channel_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Đã đăng ký -> Hủy đăng ký
    $delete = $conn->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
    $delete->bind_param("ii", $subscriber_id, $channel_id);
    $delete->execute();
    $status = 'UNSUBSCRIBED';
} else {
    // Chưa đăng ký -> Thêm mới
    $insert = $conn->prepare("INSERT INTO subscriptions (subscriber_id, channel_id) VALUES (?, ?)");
    $insert->bind_param("ii", $subscriber_id, $channel_id);
    $insert->execute();
    $status = 'SUBSCRIBED';
}

// Đếm lại tổng số người đăng ký
$count = $conn->prepare("SELECT COUNT(*) AS total FROM subscriptions WHERE channel_id = ?");
$count->bind_param("i", $channel_id);
$count->execute();
$result = $count->get_result();
$total = $result->fetch_assoc()['total'] ?? 0;

echo json_encode([
    'status' => $status,
    'total' => $total
]);
exit;
