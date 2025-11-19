<?php
session_start();
require 'db.php'; // kết nối cơ sở dữ liệu

// Lấy token được gửi từ Google
$token = $_POST['credential'] ?? null;
if (!$token) {
    die('Không nhận được token từ Google.');
}

// Xác minh token
$clientID = '1013664563912-33cpk9gqu78956rj0pte2c8l33pq86cs.apps.googleusercontent.com';
$payload = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $token), true);

if (!$payload || $payload['aud'] !== $clientID) {
    die('Đăng nhập thất bại: Token không hợp lệ.');
}

$email = $payload['email'];
$name = $payload['name'] ?? 'Người dùng';
$picture = $payload['picture'] ?? null;

// Kiểm tra người dùng đã tồn tại chưa
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Người dùng đã tồn tại
    $userId = $user['id'];

    // Lấy thêm thông tin name, avatar
    $stmt = $conn->prepare("SELECT name, avatar, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userDetails = $stmt->get_result()->fetch_assoc();

    $_SESSION['user_id'] = $userId;
    $_SESSION['user'] = [
        'id'     => $userId,
        'name'   => $userDetails['name'],
        'email'  => $email,
        'avatar' => $userDetails['avatar'],
        'role'   => $userDetails['role']
    ];
} else {
    // Người dùng mới → tạo mới
    $role = 'user';
    $stmt = $conn->prepare("INSERT INTO users (name, email, avatar, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $picture, $role);
    $stmt->execute();
    $newId = $conn->insert_id;

    // Tạo kênh mặc định cho user
    $channelName = $name . "'s Channel";
    $insertChannel = $conn->prepare("INSERT INTO channels (user_id, name) VALUES (?, ?)");
    $insertChannel->bind_param("is", $newId, $channelName);
    $insertChannel->execute();

    // Gán session
    $_SESSION['user_id'] = $newId;
    $_SESSION['user'] = [
        'id'     => $newId,
        'name'   => $name,
        'email'  => $email,
        'avatar' => $picture,
        'role'   => $role
    ];
}

// ✅ Chuyển hướng về trang chính (nhớ sửa lại nếu web bạn không nằm ở /login/)
header("Location: https://rainbow-z.42web.io/index.php");
exit;
?>
