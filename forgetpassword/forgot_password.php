<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Quên mật khẩu</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(120deg, #f6f9fc, #e3f2fd);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .container {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 400px;
    }

    h2 {
      text-align: center;
      color: #1976d2;
      margin-bottom: 20px;
    }

    input[type="email"] {
      width: 100%;
      padding: 12px 15px;
      margin: 10px 0 20px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
      transition: 0.3s;
    }

    input[type="email"]:focus {
      border-color: #1976d2;
      box-shadow: 0 0 5px rgba(25, 118, 210, 0.5);
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #1976d2;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }

    button:hover {
      background-color: #125ca1;
    }

    .error-message {
      background-color: #f8d7da;
      color: #842029;
      border: 1px solid #f5c2c7;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
      text-align: center;
    }

    @media (max-width: 480px) {
      .container {
        padding: 20px;
        border-radius: 10px;
      }

      h2 {
        font-size: 20px;
      }

      input[type="email"],
      button {
        font-size: 15px;
        padding: 10px;
      }

      .error-message {
        font-size: 14px;
        padding: 8px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Quên mật khẩu</h2>
    <?php if (!empty($error)): ?>
      <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="send_otp.php">
      <input type="email" name="email" placeholder="Nhập email đã đăng ký" required>
      <button type="submit">Gửi mã xác nhận</button>
    </form>
  </div>
</body>

</html>