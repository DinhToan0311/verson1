<?php
session_start();
header('Content-Type: application/json');
require_once 'cloudinary_config.php';

use Cloudinary\Api\Upload\UploadApi;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}

if (!isset($_FILES['image']) || !isset($_POST['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu ảnh hoặc loại ảnh']);
    exit;
}

$type = $_POST['type'];
if (!in_array($type, ['avatar', 'banner'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Loại ảnh không hợp lệ']);
    exit;
}

try {
    $folder = $type === 'avatar' ? 'channels/avatars' : 'channels/banners';
    $upload = new UploadApi();
    $result = $upload->upload($_FILES['image']['tmp_name'], ['folder' => $folder]);

    echo json_encode([
        'success' => true,
        'url' => $result['secure_url'],
        'public_id' => $result['public_id']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Upload thất bại: ' . $e->getMessage()]);
}
