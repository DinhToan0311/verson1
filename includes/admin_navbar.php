<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Navbar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <style>
    :root {
      --navbar-height: 56px;
      --primary-color: #1a5da0;
      --primary-dark: #154b85;
      --primary-light: #2169b5;
      --text-light: #fff;
      --spacing: 20px;
      --border-radius: 8px;
    }

    body {
      padding-top: var(--navbar-height);
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .admin-navbar {
      position: fixed;
      top: 0;
      left: 0;
      height: var(--navbar-height);
      width: 100%;
      background-color: var(--primary-color);
      color: var(--text-light);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 var(--spacing);
      z-index: 999;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .admin-navbar .logo {
      display: flex;
      align-items: center;
      font-size: 20px;
      font-weight: 600;
      color: var(--text-light);
      text-decoration: none;
      margin-left: 50px;
      transition: all 0.3s ease;
    }

    .admin-navbar .logo i {
      margin-right: 10px;
      font-size: 24px;
    }

    .admin-navbar .logo:hover {
      transform: translateY(-1px);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .admin-navbar .actions {
      display: flex;
      align-items: center;
      gap: var(--spacing);
      margin-right: 50px;
    }

    .admin-navbar .actions a {
      display: flex;
      align-items: center;
      color: var(--text-light);
      text-decoration: none;
      font-size: 15px;
      padding: 8px 16px;
      border-radius: var(--border-radius);
      transition: all 0.3s ease;
      background-color: rgba(255, 255, 255, 0.1);
    }

    .admin-navbar .actions a i {
      margin-right: 8px;
      font-size: 16px;
    }

    .admin-navbar .actions a:hover {
      background-color: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .admin-navbar .actions a:active {
      transform: translateY(0);
      box-shadow: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .admin-navbar {
        padding: 0 10px;
      }

      .admin-navbar .logo {
        margin-left: 20px;
        font-size: 18px;
      }

      .admin-navbar .actions {
        margin-right: 20px;
      }

      .admin-navbar .actions a {
        padding: 6px 12px;
      }

      .admin-navbar .actions a span {
        display: none;
      }

      .admin-navbar .actions a i {
        margin-right: 0;
        font-size: 18px;
      }
    }
  </style>
</head>

<body>
  <nav class="admin-navbar">
    <a href="#" class="logo">
      <i class="fas fa-shield-alt"></i>
      <span>Quản Lý Admin</span>
    </a>
    <div class="actions">
      <a href="../index.php">
        <i class="fas fa-home"></i>
        <span>Trang chủ</span>
      </a>
      <a href="../loginphp/logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span>Đăng xuất</span>
      </a>
    </div>
  </nav>
</body>

</html>