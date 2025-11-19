<!-- includes/sidebar.php -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

<style>
  .sidebar {
    position: fixed;
    top: 56px;
    left: 0;
    width: 240px;
    height: calc(100vh - 56px);
    background-color: #fff;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    z-index: 998;
    transition: transform 0.3s ease;
    overflow-y: auto;
    /* ✅ Thêm dòng này để sidebar cuộn được */
  }


  .sidebar.hidden {
    transform: translateX(-100%);
  }

  .sidebar .menu {
    display: flex;
    flex-direction: column;
    padding-top: 10px;
  }

  .sidebar .link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    font-size: 15px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s ease;
    gap: 14px;
    border-radius: 6px;
  }

  .sidebar .link:hover {
    background-color: #f0f0f0;
  }

  .sidebar .link i {
    width: 34px;
    height: 34px;
    background: #f2f2f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0066cc;
    font-size: 16px;
    flex-shrink: 0;
  }

  .sidebar .footer {
    text-align: center;
    padding: 16px;
    font-size: 13px;
    color: #888;
    border-top: 1px solid #eee;
  }

  .sidebar::-webkit-scrollbar {
    width: 6px;
  }

  .sidebar::-webkit-scrollbar-thumb {
    background-color: #ccc;
    border-radius: 4px;
  }
</style>
<div class="sidebar <?= isset($forceSidebarOpen) && $forceSidebarOpen ? '' : 'hidden' ?>" id="sidebar">
  <div class="menu">
    <a href="trangchu.php" class="link"><i class="fas fa-home"></i><span>Trang chủ</span></a>
    <a href="../main/history.php" class="link"><i class="fas fa-clock-rotate-left"></i><span>Đã xem</span></a>
    <a href="../main/channel.php" class="link"><i class="fas fa-user-circle"></i><span>Kênh của bạn</span></a>
    <a href="../main/watch_later.php" class="link"><i class="fas fa-clock"></i><span>Xem sau</span></a>
    <a href="../main/favorites.php" class="link"><i class="fas fa-heart"></i><span>Video thích</span></a>
    <a href="../main/upload.php" class="link"><i class="fas fa-upload"></i><span>Tải lên</span></a>
    <a href="../loginphp/account_settings.php" class="link"><i class="fas fa-cog"></i><span>Cài đặt</span></a>
    <a href="../fiml/upload_episode.php" class="link"><i class="fas fa-circle-question"></i><span>Trợ giúp</span></a>
    <a href="../fiml/add_series.php" class="link"><i class="fas fa-comment-dots"></i><span>Gửi phản hồi</span></a>
    <a href="../loginphp/logout.php" class="link"><i class="fas fa-comment-dots"></i><span>Đăng Xuất</span></a>
    <hr>
    <hr>
    <a href="../index.php" class="link"><i class="fa-solid fa-house-user"></i><span>Giao Diện Chính </span></a>
  </div>
  <div class="footer">
    © 2025 MMG Global
  </div>
</div>

</div>