<?php if (isset($_GET['error'])): ?>
  <div class="error">
    <?php
    switch ($_GET['error']) {
      case 'missing_otp':
        echo "Vui lòng nhập mã OTP.";
        break;
      case 'expired':
        echo "Mã OTP đã hết hạn. Vui lòng thử lại.";
        break;
      case 'invalid':
        echo "Mã OTP không đúng. Vui lòng kiểm tra lại.";
        break;
      default:
        echo "Lỗi không xác định.";
    }
    ?>
  </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Xác minh OTP</title>
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

    input[type="text"] {
      width: 100%;
      padding: 12px 15px;
      margin: 10px 0 20px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
      transition: 0.3s;
    }

    input[type="text"]:focus {
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

    .error {
      background-color: #fdecea;
      color: #d32f2f;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
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

      input[type="text"],
      button {
        font-size: 15px;
        padding: 10px;
      }

      .error {
        font-size: 14px;
        padding: 8px;
      }
    }
  </style>
</head>

<body>

  <div class="container">
    <h2>Xác minh mã OTP</h2>
    <form action="check_otp.php" method="POST">
      <input type="text" name="otp_input" placeholder="Nhập mã OTP" required>
      <button type="submit">Xác minh</button>
    </form>
  </div>

</body>

</html>