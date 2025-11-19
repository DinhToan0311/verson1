<?php
session_start();
require 'db.php';

$msg = '';

if (isset($_POST['signup'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $pass = trim($_POST['password']);
  $hash = password_hash($pass, PASSWORD_DEFAULT);

  // Kiểm tra email đã tồn tại chưa
  $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $check->bind_param("s", $email);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    $msg = "Email đã được đăng ký. Vui lòng dùng email khác.";
  } else {
    $role = 'user';
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hash, $role);

    if ($stmt->execute()) {
      $userId = $conn->insert_id;

      // Tạo kênh mặc định
      $channelName = $name . "'s Channel";
      $insertChannel = $conn->prepare("INSERT INTO channels (user_id, name) VALUES (?, ?)");
      $insertChannel->bind_param("is", $userId, $channelName);
      $insertChannel->execute();

      $msg = "Đăng ký thành công!";
    } else {
      $msg = "Lỗi hệ thống, vui lòng thử lại!";
    }
  }
}

if (isset($_POST['signin'])) {
  $email = trim($_POST['email']);
  $pass = trim($_POST['password']);

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows == 1) {
    $user = $res->fetch_assoc();

    if (password_verify($pass, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
      ];

      echo "<script>
                alert('Đăng nhập thành công! Xin chào bạn!');
                window.location.href = '../index.php';
            </script>";
      exit;
    } else {
      $msg = "Sai mật khẩu!";
    }
  } else {
    $msg = "Email không tồn tại!";
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="google-signin-client_id" content="1013664563912-33cpk9gqu78956rj0pte2c8l33pq86cs.apps.googleusercontent.com">
  <title>Đăng nhập & Đăng ký</title>
  <link rel="stylesheet" href="../css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <link rel="icon" href="../logo.png" type="image/png">
</head>

<body>
  <?php if (!empty($msg)): ?>
    <script>
      alert(<?= json_encode($msg) ?>);
    </script>
  <?php endif; ?>

  <div class="container" id="container">
    <!-- ĐĂNG KÝ -->
    <div class="form-container sign-up">
      <form method="POST" action="">
        <h1>Đăng kí</h1>
        <div class="social-icons">
          <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
          <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
          <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
        </div>
        <span>hoặc sử dụng Email của bạn</span>
        <input type="text" placeholder="Tên của bạn" name="name" required />
        <input type="email" placeholder="Email" name="email" required />
        <input type="password" placeholder="Mật khẩu" name="password" required />
        <button type="submit" name="signup">Đăng kí</button>
      </form>
    </div>

    <!-- ĐĂNG NHẬP -->
    <div class="form-container sign-in">
      <form method="POST" action="">
        <h1>Đăng Nhập</h1>

        <!-- GOOGLE LOGIN -->
        <div id="g_id_onload"
          data-client_id="1013664563912-33cpk9gqu78956rj0pte2c8l33pq86cs.apps.googleusercontent.com"
          data-login_uri="https://rainbow-z.42web.io/loginphp/login_callback.php"
          data-auto_prompt="false">
        </div>

        <div class="g_id_signin"
          data-type="standard"
          data-size="large"
          data-theme="outline"
          data-text="sign_in_with"
          data-shape="rectangular"
          data-logo_alignment="left">
        </div>

        <span>hoặc sử dụng Email của bạn</span>
        <input type="email" placeholder="Email" name="email" required />
        <input type="password" placeholder="Mật khẩu" name="password" required />

        <a href="../forgetpassword/forgot_password.php" style="color: #3498db; font-size: 14px; display: block; margin-top: 8px;">
          Quên mật khẩu?
        </a>

        <button type="submit" name="signin">Đăng Nhập</button>
      </form>
    </div>

    <!-- CHUYỂN FORM -->
    <div class="toggle-container">
      <div class="toggle">
        <div class="toggle-panel toggle-left">
          <h1>Chào mừng bạn!</h1>
          <p>Nhập thông tin để sử dụng mọi tính năng của trang</p>
          <button class="hidden" id="login">Đăng Nhập</button>
        </div>
        <div class="toggle-panel toggle-right">
          <h1>Xin chào!</h1>
          <p>Hãy đăng ký để trải nghiệm các tính năng tuyệt vời</p>
          <button class="hidden" id="register">Đăng ký</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const container = document.getElementById("container");
    const registerBtn = document.getElementById("register");
    const loginBtn = document.getElementById("login");

    registerBtn.addEventListener("click", () => {
      container.classList.add("active");
    });

    loginBtn.addEventListener("click", () => {
      container.classList.remove("active");
    });

    if (localStorage.getItem('resetSuccess') === '1') {
      const message = document.createElement('div');
      message.innerText = 'Đổi mật khẩu thành công! Vui lòng đăng nhập.';
      message.style = `
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        border: 1px solid #c3e6cb;
        border-radius: 6px;
        margin-bottom: 15px;
        text-align: center;
      `;
      document.body.prepend(message);
      localStorage.removeItem('resetSuccess');
    }

    // Phát hiện WebView (Zalo, Messenger, Facebook...)
    function isInWebView() {
      var ua = navigator.userAgent || navigator.vendor || window.opera;
      return (/FBAN|FBAV|FB_IAB|Line|Instagram|Zalo/i.test(ua));
    }

    if (isInWebView()) {
      alert("Bạn đang mở trang bằng trình duyệt tích hợp (Zalo/Facebook). Vui lòng mở lại bằng trình duyệt Chrome hoặc Safari để đăng nhập bằng Google.");
    }
  </script>
</body>

</html>