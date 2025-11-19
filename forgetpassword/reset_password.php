<?php
session_start();
require __DIR__ . '/../loginphp/db.php';

if (!isset($_SESSION['verified_email'])) {
  die("Truy cập không hợp lệ.");
}

$email = $_SESSION['verified_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (empty($new) || empty($confirm)) {
    $error = "Vui lòng nhập đầy đủ mật khẩu.";
  } elseif ($new !== $confirm) {
    $error = "Mật khẩu xác nhận không khớp.";
  } else {
    $newPassword = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $newPassword, $email);
    $stmt->execute();
    session_destroy();
    echo "
              <script>
                localStorage.setItem('resetSuccess', '1');
                window.location.href = '../loginphp/login.php';
              </script>
              ";
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đổi mật khẩu</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .reset-box {
      background: white;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      width: 350px;
      text-align: center;
    }

    .reset-box h2 {
      margin-bottom: 20px;
      color: #333;
    }

    .reset-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
    }

    .reset-box button {
      background-color: #3498db;
      color: white;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .reset-box button:hover {
      background-color: #2980b9;
    }

    .error {
      color: red;
      margin-bottom: 10px;
    }

    @media (max-width: 480px) {
      .reset-box {
        width: 90%;
        padding: 20px;
      }

      .reset-box h2 {
        font-size: 20px;
      }

      .reset-box input[type="password"],
      .reset-box button {
        font-size: 16px;
        padding: 10px;
      }
    }
  </style>
</head>

<body>
  <div class="reset-box">
    <h2>Đổi mật khẩu</h2>
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="password" name="new_password" placeholder="Mật khẩu mới" required>
      <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
      <button type="submit">Cập nhật</button>
    </form>
  </div>
</body>

</html>