<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Cài đặt tài khoản</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 50px;
      background-color: #f4f4f4;
    }
    .settings-box {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    h2 {
      margin-bottom: 20px;
    }
    .delete-btn {
      background-color: #e74c3c;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
    }
    .delete-btn:hover {
      background-color: #c0392b;
    }
  </style>
</head>
<body>
  <div class="settings-box">
    <h2>Xin chào, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</h2>
    <p>Nếu bạn không muốn sử dụng tài khoản này nữa, bạn có thể xóa nó.</p>
    <form action="delete_account.php" method="post">
      <button type="submit" name="confirm_delete" class="delete-btn">Xóa tài khoản</button>
    </form>
  </div>
</body>
</html>
