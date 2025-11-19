<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../loginphp/db.php';
header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    exit("❌ Chưa đăng nhập.");
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) exit("❌ Dữ liệu không hợp lệ.");

$title = trim($data['title'] ?? '');
$category = trim($data['category'] ?? '');
$description = trim($data['description'] ?? '');
$videoUrl = trim($data['videoUrl'] ?? '');
$cloudinaryId = trim($data['publicId'] ?? '');
$duration = (int)($data['duration'] ?? 0);
$thumbnail = trim($data['thumbnail'] ?? '');
$userId = $_SESSION['user_id'];

if (!$title || !$videoUrl) exit("❌ Thiếu dữ liệu quan trọng.");

$stmt = $conn->prepare("INSERT INTO videos (title, filename, category, description, cloudinary_id, duration, thumbnail, uploaded_by, views, upload_date, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), 1)");
$stmt->bind_param("sssssisi", $title, $videoUrl, $category, $description, $cloudinaryId, $duration, $thumbnail, $userId);

if ($stmt->execute()) {
    echo "✅ Video đã lưu!";
} else {
    echo "❌ Lỗi lưu vào CSDL: " . $stmt->error;
}
