<?php
session_start();
require '../loginphp/db.php';

header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']);
    exit;
}

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Tên không được để trống.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO playlists (name, description, user_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $desc, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $stmt->error]);
}
?>
