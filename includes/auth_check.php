<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Bạn cần đăng nhập để truy cập trang này!');
        window.location.href = '../index.php'; // Đường dẫn về trang index
    </script>";
    exit;
}
?>

