<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "LOGIN_REQUIRED"]);
    exit;
}

$userId = $_SESSION['user_id'];

// Trường hợp 1: Toggle yêu thích theo video_id
if (isset($_POST['video_id'])) {
    $videoId = intval($_POST['video_id']);

    $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND video_id = ?");
    $check->bind_param("ii", $userId, $videoId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND video_id = ?");
        $del->bind_param("ii", $userId, $videoId);
        $del->execute();
        echo json_encode(["status" => "REMOVED"]);
    } else {
        $add = $conn->prepare("INSERT INTO favorites (user_id, video_id) VALUES (?, ?)");
        $add->bind_param("ii", $userId, $videoId);
        $add->execute();
        echo json_encode(["status" => "ADDED"]);
    }
    exit;
}

// Trường hợp 2: Xóa yêu thích theo fav_id
if (isset($_POST['id'])) {
    $favId = intval($_POST['id']);

    $del = $conn->prepare("DELETE FROM favorites WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $favId, $userId);
    if ($del->execute()) {
        echo json_encode(["status" => "OK"]);
    } else {
        echo json_encode(["status" => "FAIL"]);
    }
    exit;
}

// Trường hợp không hợp lệ
http_response_code(400);
echo json_encode(["status" => "INVALID_REQUEST"]);
