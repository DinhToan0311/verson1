<?php
session_start();
require '../loginphp/db.php';

$loggedInUserId = $_SESSION['user_id'] ?? null;

if (!isset($_GET['id']) && !$loggedInUserId) {
  die("Không có ID kênh hoặc chưa đăng nhập.");
}

$channelId = $_GET['id'] ?? null;

if (!$channelId || !is_numeric($channelId)) {
  die("Kênh không tồn tại.");
}

// Lấy thông tin kênh theo ID kênh
$stmt = $conn->prepare("SELECT * FROM channels WHERE id = ?");
$stmt->bind_param("i", $channelId);

$stmt->execute();
$channel = $stmt->get_result()->fetch_assoc();

if (!$channel) {
  die("Kênh không tồn tại.");
}
$channelUserId = $channel['user_id'];

// Lấy danh sách video của kênh
$stmt = $conn->prepare("SELECT * FROM videos WHERE uploaded_by = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $channel['user_id']);
$stmt->execute();
$videos = $stmt->get_result();
// Lấy playlist của kênh
$playlistStmt = $conn->prepare("
  SELECT p.id, p.name, p.description,
    (
      SELECT v.thumbnail 
      FROM playlist_videos pv2 
      JOIN videos v ON pv2.video_id = v.id 
      WHERE pv2.playlist_id = p.id 
      ORDER BY pv2.video_id ASC LIMIT 1
    ) AS thumbnail
  FROM playlists p WHERE p.user_id = ?
");
$playlistStmt->bind_param("i", $channel['user_id']);
$playlistStmt->execute();
$playlists = $playlistStmt->get_result();

// Cập nhật thông tin kênh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_channel'])) {
  $newName = trim($_POST['channel_name']);
  $newDesc = trim($_POST['channel_description']);
  if ($newName !== '') {
    $stmt = $conn->prepare("UPDATE channels SET name = ?, description = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $newName, $newDesc, $channelUserId);
    $stmt->execute();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  }
}

// Thống kê kênh
$statsStmt = $conn->prepare("SELECT COUNT(*) AS total_videos, SUM(views) AS total_views FROM videos WHERE uploaded_by = ?");
$statsStmt->bind_param("i", $channelUserId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Tổng người đăng ký
$stmtSub = $conn->prepare("SELECT COUNT(*) FROM subscriptions WHERE channel_id = ?");
$stmtSub->bind_param("i", $channel['id']);
$stmtSub->execute();
$stmtSub->bind_result($subscriberCount);
$stmtSub->fetch();
$stmtSub->close();

// Kiểm tra đã đăng ký chưa
$isSubscribed = false;
$checkSub = $conn->prepare("SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
$checkSub->bind_param("ii", $loggedInUserId, $channel['id']);
$checkSub->execute();
$checkSub->store_result();
$isSubscribed = $checkSub->num_rows > 0;
$checkSub->close();

// Lấy danh sách playlist
$playlistStmt = $conn->prepare("
  SELECT p.id, p.name, p.description,
    (
      SELECT v.thumbnail 
      FROM playlist_videos pv2 
      JOIN videos v ON pv2.video_id = v.id 
      WHERE pv2.playlist_id = p.id 
      ORDER BY pv2.video_id ASC LIMIT 1
    ) AS thumbnail
  FROM playlists p
  WHERE p.user_id = ?
");
$playlistStmt->bind_param("i", $channel['user_id']);
$playlistStmt->execute();
$playlists = $playlistStmt->get_result();

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Kênh của tôi - BlueTube</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" href="logo.png" type="image/png">
  <!-- Cho vào <head> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f8f8f8;
      color: #333;
    }

    /* === WRAPPERS & SECTION === */
    .content-wrapper {
      width: 70%;
      margin: 0 auto;
      padding: 30px 0;
      position: relative;
      z-index: 0;
    }

    .section {
      background: white;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px;
      margin: 20px 0 0 15%;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .section h3 {
      margin-top: 0;
      font-size: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 8px;
    }

    /* === CHANNEL === */
    .channel-banner {
      width: 100%;
      max-height: 300px;
      object-fit: cover;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .channel-header {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 10px;
      background: white;
      padding: 20px;
      border-radius: 10px;
    }

    .channel-header img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .channel-name input,
    .channel-description textarea {
      width: 100%;
      font-size: 16px;
    }

    /* === VIDEO & PLAYLIST GRID === */
    .video-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 16px;
    }

    .video {
      width: calc(20% - 13px);
      /* 100% / 5 = 20%, trừ đi gap để không bị tràn */
      background: #fdfdfd;
      border-radius: 8px;
      box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
      overflow: visible;
      position: relative;
      color: inherit;
    }


    /* === VIDEO & PLAYLIST CARD === */
    .video,
    .playlist-card {
      background: #fdfdfd;
      border-radius: 8px;
      box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
      overflow: visible;
      position: relative;
      color: inherit;
      min-height: 240px;
    }

    .video:hover,
    .playlist-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .thumbnail,
    .playlist-thumb {
      width: 100%;
      height: 130px;
      object-fit: cover;
      border-radius: 8px 8px 0 0;
    }

    .playlist-card {
      width: 200px;
      height: 280px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .playlist-info {
      padding: 10px;
      font-size: 14px;
      color: #444;
      flex-grow: 1;
      overflow: hidden;
    }

    /* === MENU BUTTON & POPUP === */
    .menu-container {
      position: absolute;
      top: 8px;
      right: 8px;
      z-index: 10;
    }

    .menu-toggle {
      background: transparent;
      border: none;
      font-size: 20px;
      color: #444;
      cursor: pointer;
    }

    .menu-popup {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 6px;
      background: white;
      border: 1px solid #ccc;
      border-radius: 6px;
      padding: 8px;
      min-width: 150px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      display: none;
      z-index: 9999;
    }

    .menu-popup.show {
      display: block;
    }

    .menu-popup select,
    .menu-popup button {
      width: 100%;
      margin-bottom: 6px;
    }

    /* === PLAYLIST FORM === */
    #playlistForm,
    .playlist-popup-form {
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      min-width: 250px;
      z-index: 9999;
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .playlist-popup-form input,
    .playlist-form select,
    .playlist-form button {
      width: 100%;
      padding: 6px;
      font-size: 14px;
      border-radius: 5px;
      margin-top: 6px;
      border: 1px solid #ddd;
    }

    .confirm-btn {
      background: #28a745;
      color: white;
      border: none;
      transition: background 0.2s ease;
      padding: 6px 10px;
      border-radius: 5px;
      cursor: pointer;
    }

    .confirm-btn:hover {
      background: #218838;
    }

    .playlist-toggle {
      background: #0f2f51;
      color: white;
      padding: 6px 10px;
      border: none;
      border-radius: 5px;
      width: 100%;
      font-size: 14px;
      cursor: pointer;
      transition: background 0.2s ease;
    }

    .playlist-toggle:hover {
      background: #164d80;
    }

    .playlist-form {
      display: none;
      margin-top: 10px;
      animation: slideDown 0.3s ease;
    }

    .add-playlist-btn {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #0f2f51;
      margin-left: 10px;
      z-index: 100;
    }

    /* === ANIMATIONS === */
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-5px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* === FOOTER === */
    footer {
      margin-top: 50px;
      background: #f0f0f0;
      text-align: center;
      padding: 20px;
      color: #555;
      font-size: 14px;
      border-top: 1px solid #ddd;
    }

    .btn-subscribe {
      background-color: #cc0000;
      color: white;
      padding: 6px 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .btn-subscribed {
      background-color: #cccccc;
      color: #222;
      padding: 6px 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .video-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>
  <!-- Sidebar -->
  <?php
  function isMobileDevice()
  {
    return preg_match('/(android|iphone|ipad|ipod|windows phone|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
  }

  if (isMobileDevice()) {
    include '../includes/sidebar.php';
  } else {
    $forceSidebarOpen = true;
    include '../includes/sidebar.php';
  }
  ?>
  <div class="section">
    <!-- Kênh -->
    <div class="content-wrapper">
      <?php if ($channel): ?>
        <img src="<?= $channel['banner'] ?: '../images/default-banner.jpg' ?>" class="channel-banner">

        <div class="channel-header" style="display: flex; gap: 20px; align-items: center; padding: 20px;">
          <!-- Cột trái: Avatar -->
          <div style="flex-shrink: 0; position: relative; width: 130px; height: 130px;">
            <img src="<?= htmlspecialchars($channel['avatar'] ?: '../images/default-avatar.png') ?>"
              style="width:100px;height:100px;border-radius:50%;object-fit:cover;" alt="Avatar">

            <!-- Dấu tích -->
            <span style="
      position: absolute;
      bottom: 0;
      right: 0;
      background: white;
      border-radius: 50%;
      padding: 3px;
      font-size: 16px;
      color: #0a84ff;
      box-shadow: 0 0 2px rgba(0,0,0,0.2);
    ">
              <i class="fas fa-check-circle"></i>
            </span>
          </div>

          <!-- Cột phải: Thông tin kênh -->
          <div style="flex-grow: 1;">
            <!-- Tên kênh -->
            <h2 style="margin: 0 0 8px; font-size: 26px;">
              <?= htmlspecialchars($channel['name']) ?>
            </h2>

            <!-- Mô tả -->
            <p style="margin: 0 0 10px; font-size: 15px; color: #444; line-height: 1.5;">
              <?= nl2br(htmlspecialchars($channel['description'])) ?>
            </p>

            <!-- Mạng xã hội -->
            <div style="display: flex; gap: 14px; font-size: 18px; margin: 10px 0;">
              <a href="#" target="_blank" title="Facebook" style="color:#3b5998;"><i class="fab fa-facebook-f"></i></a>
              <a href="#" target="_blank" title="TikTok" style="color:#000;"><i class="fab fa-tiktok"></i></a>
              <a href="#" target="_blank" title="Instagram" style="color:#c13584;"><i class="fab fa-instagram"></i></a>
              <a href="#" target="_blank" title="Bản đồ" style="color:#d9534f;"><i class="fas fa-map-marker-alt"></i></a>
            </div>


            <?php if ($loggedInUserId !== $channel['user_id']): ?>
              <button id="btn-subscribe"
                onclick="toggleSubscribe(this, <?= $channel['id'] ?>)"
                class="<?= $isSubscribed ? 'btn-subscribed' : 'btn-subscribe' ?>">
                <i class="fas <?= $isSubscribed ? 'fa-check-circle' : 'fa-bell' ?>"></i>
                <?= $isSubscribed ? 'Đã đăng ký' : 'Đăng ký' ?>
              </button>
            <?php endif; ?>

            <!-- Hiển thị số lượt đăng ký -->
            <div style="margin-top: 6px; font-size: 14px; color: #555;">
              <span id="sub-count">👥 <?= $subscriberCount ?> người đăng ký</span>
            </div>

            <!-- Thống kê -->
            <div style="margin-top: 8px; font-size: 14px; color: #555;">
              🎞️ Tổng video: <?= $stats['total_videos'] ?? 0 ?> |
              👁️ Tổng lượt xem: <?= $stats['total_views'] ?? 0 ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="section">
    <h3>📺 Video của kênh</h3>
    <?php if ($videos->num_rows === 0): ?>
      <p>Kênh chưa đăng video nào.</p>
    <?php else: ?>
      <div class="video-grid">
        <?php while ($v = $videos->fetch_assoc()):
          $thumb = filter_var($v['thumbnail'], FILTER_VALIDATE_URL) ? $v['thumbnail'] : '../images/default.jpg';

        ?>
          <div class="video">
            <a href="../main/watch.php?id=<?= $v['id'] ?>" style="text-decoration: none; color: inherit;">
              <img src="<?= htmlspecialchars($thumb) ?>" class="thumbnail">


              <div style="display: flex; align-items: flex-start; gap: 10px; padding: 10px;">
                <!-- Logo kênh to hơn -->
                <img src="<?= htmlspecialchars($channel['avatar'] ?: '../images/default-avatar.png') ?>"
                  alt="Avatar"
                  class="video-avatar">


                <div style="flex: 1;">
                  <h4 style="margin: 0; font-size: 15px; line-height: 1.3;">
                    <?= htmlspecialchars($v['title']) ?>
                  </h4>
                  <p style="margin: 4px 0 0; font-size: 13px; color: #666;">
                    <?= htmlspecialchars($channel['name']) ?><br>
                    👁️ <?= number_format($v['views']) ?> lượt xem • <?= date('d/m/Y', strtotime($v['upload_date'])) ?>
                  </p>
                </div>
              </div>
            </a>
          </div>

        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>


  <div class="section">
    <h3>📂 Danh sách phát</h3>
    <div class="playlist-grid">
      <?php while ($p = $playlists->fetch_assoc()):
        $thumb = filter_var($p['thumbnail'], FILTER_VALIDATE_URL) ? $p['thumbnail'] : '../images/default.jpg';

      ?>
        <div class="playlist-card">

          <img src="<?= htmlspecialchars($thumb) ?>" alt="Thumbnail" class="thumbnail">

          <div class="playlist-info">
            <strong><?= htmlspecialchars($p['name']) ?></strong>
            <p><?= htmlspecialchars($p['description'] ?? 'Chưa có mô tả') ?></p>
          </div>
          </a>
        </div>
      <?php endwhile; ?>
    </div>
  </div>


  <footer>
    © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
  </footer>
  </div>
  <script>
    function toggleMenu(button) {
      const popup = button.nextElementSibling;
      popup.classList.toggle('show');

      // Đóng menu khác nếu có
      document.querySelectorAll('.menu-popup').forEach(p => {
        if (p !== popup) p.classList.remove('show');
      });
    }

    // Đóng menu khi click ra ngoài
    document.addEventListener('click', function(e) {
      document.querySelectorAll('.menu-popup').forEach(p => {
        if (!p.contains(e.target) && !p.previousElementSibling.contains(e.target)) {
          p.classList.remove('show');
        }
      });
    });

    // Hàm thêm video vào danh sách xem sau
    function addToWatchLater(event, videoId) {
      event.preventDefault();

      fetch('add_to_watch_later.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'video_id=' + encodeURIComponent(videoId)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('📌 Đã thêm vào danh sách xem sau!');
          } else {
            alert('⚠️ ' + data.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert('❌ Có lỗi khi thêm vào danh sách xem sau.');
        });

      return false;
    }

    function toggleSubscribe(button, channelId) {
      const isSubscribed = button.classList.contains('btn-subscribed');

      if (isSubscribed && !confirm("❗ Hủy đăng ký kênh này?")) {
        return;
      }

      fetch('../main/channel/subscribe.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'channel_id=' + encodeURIComponent(channelId)
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'SUBSCRIBED') {
            button.classList.remove('btn-subscribe');
            button.classList.add('btn-subscribed');
            button.innerHTML = '<i class="fas fa-check-circle"></i> Đã đăng ký';
          } else if (data.status === 'UNSUBSCRIBED') {
            button.classList.remove('btn-subscribed');
            button.classList.add('btn-subscribe');
            button.innerHTML = '<i class="fas fa-bell"></i> Đăng ký';
          }


          if (data.total !== undefined) {
            document.getElementById('sub-count').innerText = `👥 ${data.total} người đăng ký`;
          }
        });
    }
  </script>


</body>

</html>