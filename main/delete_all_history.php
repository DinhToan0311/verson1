<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Không hợp lệ.");
}

$userId = $_SESSION['user_id'];
$conn->query("DELETE FROM watch_history WHERE user_id = $userId");

header("Location: history.php");
exit;
