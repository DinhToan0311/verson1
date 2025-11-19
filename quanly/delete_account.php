<?php
session_start();
require_once realpath(__DIR__ . '/../loginphp/db.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../loginphp/login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // 1. Xoá bình luận
    $conn->prepare("DELETE FROM comments WHERE user_id = ?")->bind_param("i", $userId)->execute();

    // 2. Xoá lịch sử xem
    $conn->prepare("DELETE FROM watch_history WHERE user_id = ?")->bind_param("i", $userId)->execute();

    // 3. Xoá danh sách yêu thích
    $conn->prepare("DELETE FROM favorites WHERE user_id = ?")->bind_param("i", $userId)->execute();

    // 4. Xoá danh sách xem sau
    $conn->prepare("DELETE FROM watch_later WHERE user_id = ?")->bind_param("i", $userId)->execute();

    // 5. Xoá video đã upload
    $conn->prepare("DELETE FROM videos WHERE uploaded_by = ?")->bind_param("i", $userId)->execute();

    // 6. Xoá tài khoản
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        session_destroy();
        header("Location: ../index.php?deleted=1");
        exit;
    } else {
        $error = "❌ Lỗi khi xóa tài khoản.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Xóa tài khoản</title>
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .delete-box {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.15);
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #e74c3c;
        }

        form {
            margin-top: 20px;
        }

        button {
            padding: 10px 20px;
            border: none;
            margin: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .cancel {
            background-color: #3498db;
            color: white;
        }

        .confirm {
            background-color: #e74c3c;
            color: white;
        }

        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="delete-box">
        <h2>Xác nhận xóa tài khoản</h2>
        <p>Bạn có chắc chắn muốn xóa tài khoản? Hành động này <b>không thể hoàn tác</b>.</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="confirm">Xác nhận xóa</button>
            <a href="../index.php">
                <button type="button" class="cancel">Hủy bỏ</button>
            </a>
        </form>
    </div>

</body>

</html>