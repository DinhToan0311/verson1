<?php
session_start();        // Bắt đầu session
session_destroy();      // Hủy toàn bộ session (đăng xuất)
header("Location: login.php"); // Chuyển hướng đến trang đăng nhập
exit;
