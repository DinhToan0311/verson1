<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require '../loginphp/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "❌ Phương thức không hợp lệ."]);
    exit;
}

$series_id   = (int)($data['series_id'] ?? 0);
$title       = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$video_url   = trim($data['video_url'] ?? '');
$public_id   = trim($data['public_id'] ?? '');

if ($series_id <= 0 || !$title || !$video_url) {
    echo json_encode(["status" => "error", "message" => "❌ Thiếu dữ liệu cần thiết."]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO episodes (series_id, title, description, video_path, public_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $series_id, $title, $description, $video_url, $public_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
