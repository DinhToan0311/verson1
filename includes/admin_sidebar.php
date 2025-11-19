<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/admin.css">

  <style>
    :root {
      --sidebar-width: 240px;
      --primary-color: #0066cc;
      --hover-bg: #f0f0f0;
      --border-color: #ddd;
      --text-color: #333;
      --icon-bg: #f2f2f2;
    }

    /* Sidebar collapse animation */
    body.sidebar-collapsed .sidebar {
      transform: translateX(-100%);
    }

    /* Toggle button styles */
    .sidebar-toggle-btn {
      position: fixed;
      top: 10px;
      left: 10px;
      z-index: 999;
      background-color: transparent;
      border: none;
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 18px;
      line-height: 1;
      transition: all 0.3s ease;
    }

    .sidebar-toggle-btn:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    /* Main sidebar container */
    .sidebar {
      position: fixed;
      top: 56px;
      left: 0;
      width: var(--sidebar-width);
      height: calc(100vh - 56px);
      background-color: #fff;
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      z-index: 998;
      transition: transform 0.3s ease;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    }

    /* Menu container */
    .sidebar .menu {
      display: flex;
      flex-direction: column;
      padding: 15px 10px;
      gap: 5px;
    }

    /* Menu links */
    .sidebar .link {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      font-size: 15px;
      color: var(--text-color);
      text-decoration: none;
      transition: all 0.2s ease;
      gap: 14px;
      border-radius: 8px;
    }

    .sidebar .link:hover {
      background-color: var(--hover-bg);
      transform: translateX(2px);
    }

    /* Menu icons */
    .sidebar .link i {
      width: 34px;
      height: 34px;
      background: var(--icon-bg);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
      font-size: 16px;
      flex-shrink: 0;
      transition: all 0.2s ease;
    }

    .sidebar .link:hover i {
      background: var(--primary-color);
      color: #fff;
    }

    /* Footer section */
    .sidebar .footer {
      text-align: center;
      padding: 16px;
      font-size: 13px;
      color: #888;
      border-top: 1px solid var(--border-color);
      background-color: #fafafa;
    }
  </style>
</head>

<body>
  <!-- Toggle Button -->
  <button class="sidebar-toggle-btn" id="toggleSidebar">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="menu">
      <a href="../index.php" class="link">
        <i class="fas fa-home"></i>
        <span>Trang người dùng</span>
      </a>
      <a href="admin_dashboard.php" class="link">
        <i class="fas fa-chart-line"></i>
        <span>Trang chủ</span>
      </a>
      <a href="manage_series.php" class="link">
        <i class="fas fa-film"></i>
        <span>Quản lý phim bộ</span>
      </a>
      <a href="manage_video.php" class="link">
        <i class="fas fa-video"></i>
        <span>Quản lý video</span>
      </a>
      <a href="manage_users.php" class="link">
        <i class="fas fa-users"></i>
        <span>Quản lý tài khoản</span>
      </a>
      <a href="add_series.php" class="link">
        <i class="fas fa-film fa-plus"></i>
        <span>Thêm phim bộ</span>
      </a>
      <a href="upload_episode.php" class="link">
        <i class="fas fa-video-plus"></i>
        <span>Thêm tập</span>
      </a>
      <a href="manage_chanel.php" class="link">
        <i class="fas fa-tv"></i>
        <span>Quản lý kênh</span>
      </a>
      <a href="manage_activity.php" class="link">
        <i class="fas fa-chart-bar"></i>
        <span>Quản lý hoạt động</span>
      </a>
      <a href="manage_notification.php" class="link">
        <i class="fas fa-bell"></i>
        <span>Quản lý thông báo</span>
      </a>
      <a href="../loginphp/logout.php" class="link">
        <i class="fas fa-sign-out-alt"></i>
        <span>Đăng xuất</span>
      </a>
    </div>
    <div class="footer">
      © 2025 Admin Panel
    </div>
  </div>

  <script>
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      document.body.classList.toggle('sidebar-collapsed');
    });
  </script>
</body>

</html>