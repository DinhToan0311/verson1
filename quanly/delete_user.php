<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once realpath(__DIR__ . '/../loginphp/db.php');

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền thực hiện thao tác này.");
}

$success = '';
$error = '';
$targetUserId = $_GET['user_id'] ?? null;

// Xử lý khi xác nhận xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $id = (int)$_POST['user_id'];

    // Không cho phép xóa chính mình
    if ($id == $_SESSION['user']['id']) {
        $error = "❌ Không thể xóa chính bạn.";
    } else {
        // Xóa dữ liệu liên quan trước (watch_history)
        $conn->prepare("DELETE FROM watch_history WHERE user_id = ?")->bind_param("i", $id)->execute();

        // Xóa người dùng
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "✅ Xóa tài khoản thành công.";
        } else {
            $error = "❌ Lỗi khi xóa người dùng.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Xóa tài khoản</title>
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
            max-width: 420px;
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
            text-decoration: none;
            display: inline-block;
        }

        .confirm {
            background-color: #e74c3c;
            color: white;
        }

        .error {
            color: red;
            margin-top: 10px;
        }

        .success {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="delete-box">
        <h2>Xác nhận xóa tài khoản</h2>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
            <a href="all_users.php" class="cancel">← Quay lại danh sách</a>
        <?php elseif ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($targetUserId): ?>
            <p>Bạn có chắc chắn muốn xóa tài khoản <strong>ID <?= htmlspecialchars($targetUserId) ?></strong>?<br>
                Hành động này <b>không thể hoàn tác</b>.</p>

            <form method="POST">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($targetUserId) ?>">
                <button type="submit" class="confirm">Xác nhận xóa</button>
                <a href="all_users.php" class="cancel">Hủy bỏ</a>
            </form>
        <?php else: ?>
            <div class="error">❌ Không xác định được người dùng cần xóa.</div>
        <?php endif; ?>
    </div>

</body>

</html>