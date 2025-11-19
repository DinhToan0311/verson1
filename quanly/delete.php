<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['type'], $_POST['id'])) {
    echo "invalid_request";
    exit;
}

$type = $_POST['type'];
$id = (int) $_POST['id'];

if ($id <= 0) {
    echo "invalid_id";
    exit;
}

switch ($type) {
    case 'series':
        // Xóa series và các tập liên quan
        $conn->query("DELETE FROM episodes WHERE series_id = $id");
        $conn->query("DELETE FROM series WHERE id = $id");
        echo "success";
        break;

    case 'video':
        $conn->query("DELETE FROM videos WHERE id = $id");
        echo "success";
        break;

    case 'episode':
        $stmt = $conn->prepare("DELETE FROM episodes WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "fail";
        }
        break;

    default:
        echo "invalid_type";
        break;
}
?>
