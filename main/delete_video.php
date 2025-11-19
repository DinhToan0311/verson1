<?php
session_start();
require '../loginphp/db.php';
require 'cloudinary_config.php';

use Cloudinary\Api\Upload\UploadApi;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '❌ Chưa đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '❌ Phương thức không hợp lệ']);
    exit;
}

$video_id = $_POST['video_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$video_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '❌ Thiếu video_id']);
    exit;
}

// Lấy video
$stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND uploaded_by = ?");
$stmt->bind_param("ii", $video_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '❌ Không có quyền xóa video hoặc video không tồn tại']);
    exit;
}

$video = $result->fetch_assoc();
$cloudinary_id = $video['cloudinary_id'] ?? null;

// Xoá trên Cloudinary
if ($cloudinary_id) {
    try {
        $uploadApi = new UploadApi();
        $uploadApi->destroy($cloudinary_id, ['resource_type' => 'video']);
    } catch (Exception $e) {
        error_log("Cloudinary delete error: " . $e->getMessage());
    }
}

// Xoá khỏi DB
$stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();

echo json_encode(['success' => true, 'message' => '✅ Đã xoá video']);
