<?php
// Bật lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Kết nối database
$host = "sql307.infinityfree.com";
$username = "if0_39344249";
$password = "GYfJHSjGxKEq";
$database = "if0_39344249_login_movie";
$conn = new mysqli($host, $username, $password, $database);
$conn->set_charset("utf8mb4");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Nhận tin nhắn từ người dùng
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    $msg = $conn->real_escape_string($msg);

    if ($msg === "") {
        echo "Bạn cần nhập nội dung.";
        exit;
    }

    // Trả về danh sách gợi ý theo từ khóa
    $sql = "SELECT title, genre, poster_url FROM series WHERE title LIKE '%$msg%' OR genre LIKE '%$msg%' LIMIT 5";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $poster = $row['poster_url'] ?: 'poster-default.jpg';
            echo "<div><img src='{$poster}' width='50' height='70'><br><b>{$row['title']}</b><br>{$row['genre']}</div><hr>";
        }
    } else {
        echo "Không tìm thấy phim phù hợp.";
    }
} else {
    echo "Không nhận được tin nhắn.";
}
