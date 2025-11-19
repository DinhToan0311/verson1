<?php
if (!isset($currentVideo)) return;

$channel_id = $currentVideo['uploaded_by'];

// Lấy tổng lượt đăng ký
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM subscriptions WHERE channel_id = ?");
$stmt->bind_param("i", $channel_id);
$stmt->execute();
$totalSub = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Kiểm tra người dùng đã đăng ký chưa
$isSubscribed = false;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
    $stmt->bind_param("ii", $userId, $channel_id);
    $stmt->execute();
    $isSubscribed = $stmt->get_result()->num_rows > 0;
}
?>

<button class="subscribe-btn" onclick="subscribeChannel(<?= $channel_id ?>)" id="subscribeBtn">
  <i class="fas fa-bell"></i>
  <span id="subText"><?= $isSubscribed ? 'Đã đăng ký' : 'Đăng ký kênh' ?></span>
  (<span id="subCount"><?= $totalSub ?></span>)
</button>
