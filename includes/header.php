<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$avatar = '../images/default-avatar.png';

if (isset($_SESSION['user_id'])) {
  $userId = $_SESSION['user_id'];
  require_once __DIR__ . '/../loginphp/db.php';

  $stmt = $conn->prepare("SELECT avatar FROM channels WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($avatarResult);
  $stmt->fetch();
  $stmt->close();

  if (!empty($avatarResult)) {
    $avatar = $avatarResult;
  }
}
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

<!-- Responsive header CSS -->
<style>
  .navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    background-color: #fff;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    z-index: 1000;
    width: 100%;
  }

  .navbar .left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .menu-btn {
    font-size: 20px;
    color: #333;
    cursor: pointer;
  }

  .logo-text {
    font-size: 20px;
    font-weight: bold;
    color: #0066cc;
    text-decoration: none;
  }

  .logo-text:hover {
    color: #004999;
  }

  .search-box {
    flex: 1;
    max-width: 500px;
    margin: 0 16px;
    display: flex;
  }

  .search-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 18px 0 0 18px;
    border-right: none;
    font-size: 14px;
    outline: none;
  }

  .search-btn {
    width: 44px;
    border: 1px solid #ccc;
    border-left: none;
    background: #f8f8f8;
    border-radius: 0 18px 18px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .search-btn i {
    color: #444;
  }

  .right-icons {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-right: 50px;
  }

  .avatar-img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    transition: transform 0.2s;
  }

  .avatar-img:hover {
    transform: scale(1.05);
  }

  /* Make sure body always leaves room for navbar */
  body {
    padding-top: 66px !important;
    margin: 0;
  }

  @media (max-width: 768px) {
    .search-box {
      display: grid;
    }

    .navbar {
      padding: 0 12px;
    }
  }
</style>

<!-- Navbar -->
<div class="navbar">
  <div class="left">
    <span class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>
    <a href="#" class="logo-text">MMG Tube</a>
  </div>

  <?php include __DIR__ . '/search-bar.php'; ?>

  <div class="right-icons">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="../main/channel.php?id=<?= $userId ?>" title="Kênh của tôi">
        <img src="<?= htmlspecialchars($avatar) ?>" class="avatar-img" alt="Avatar">
      </a>
    <?php else: ?>
      <a href="../loginphp/login.php" title="Đăng nhập">
        <i class="fas fa-user-circle fa-xl" style="color: #0066cc;"></i>
      </a>
    <?php endif; ?>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
      sidebar.classList.toggle('hidden');
    }
  }
</script>