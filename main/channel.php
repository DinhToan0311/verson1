<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Bạn cần đăng nhập để xem kênh của mình.");
}
mysqli_set_charset($conn, "utf8mb4"); // 💡 Thiết lập bảng mã
$userId = $_SESSION['user_id'];
function formatDuration($seconds)
{
  if (!is_numeric($seconds)) return '00:00';
  $seconds = (int)$seconds;

  $h = floor($seconds / 3600);
  $m = floor(($seconds % 3600) / 60);
  $s = $seconds % 60;

  if ($h > 0) {
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
  } else {
    return sprintf("%02d:%02d", $m, $s);
  }
}

// Cập nhật thông tin kênh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_channel'])) {
  $newName = trim($_POST['channel_name']);
  $newDesc = trim($_POST['channel_description']);
  if ($newName !== '') {
    $stmt = $conn->prepare("UPDATE channels SET name = ?, description = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $newName, $newDesc, $userId);
    $stmt->execute();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  }
}
// Tạo playlist mới
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
  $newName = trim($_POST['new_playlist']);
  $desc = trim($_POST['new_description']);
  if ($newName !== '') {
    $stmt = $conn->prepare("SELECT id FROM playlists WHERE name = ? AND user_id = ?");
    $stmt->bind_param("si", $newName, $userId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();

    if ($exists) {
      $error = "Tên danh sách phát đã tồn tại.";
    } else {
      $stmt = $conn->prepare("INSERT INTO playlists (name, description, user_id) VALUES (?, ?, ?)");
      $stmt->bind_param("ssi", $newName, $desc, $userId);
      $stmt->execute();
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
}

// Lấy thông tin kênh
$stmt = $conn->prepare("SELECT * FROM channels WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$channel = $stmt->get_result()->fetch_assoc();

// Lấy số người đăng ký kênh
$subStmt = $conn->prepare("SELECT COUNT(*) AS total_subscribers FROM subscriptions WHERE channel_id = ?");
$subStmt->bind_param("i", $userId);
$subStmt->execute();
$subResult = $subStmt->get_result()->fetch_assoc();
$totalSubscribers = $subResult['total_subscribers'] ?? 0;
// Tổng số video và tổng lượt xem của kênh
$videoStatsStmt = $conn->prepare("SELECT COUNT(*) AS total_videos, SUM(views) AS total_views FROM videos WHERE uploaded_by = ?");
$videoStatsStmt->bind_param("i", $userId);
$videoStatsStmt->execute();
$stats = $videoStatsStmt->get_result()->fetch_assoc();


// Playlist
$playlistStmt = $conn->prepare("
  SELECT p.id, p.name, p.description,
    (
      SELECT v.thumbnail 
      FROM playlist_videos pv2 
      JOIN videos v ON pv2.video_id = v.id 
      WHERE pv2.playlist_id = p.id 
      ORDER BY pv2.video_id ASC 
      LIMIT 1
    ) AS thumbnail
  FROM playlists p 
  WHERE p.user_id = ?
");

$playlistStmt->bind_param("i", $userId);
$playlistStmt->execute();
$playlists = $playlistStmt->get_result();
// img

?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kênh của tôi - MMG Tube</title>
  <link rel="icon" href="logo.png" type="image/png">
  <!-- Cho vào <head> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap&subset=vietnamese" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      background: #f8f8f8;
      color: #333;
      font-family: 'Roboto', sans-serif;
      font-size: 16px;
    }

    html,
    body {
      overflow-x: hidden;
    }

    .content-wrapper {
      width: 70%;
      margin: 0 auto;
      padding: 30px 0;
      position: relative;
      z-index: 0;
    }

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
      border-radius: 50%;
      width: 100px;
      height: 100px;
      object-fit: cover;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .channel-name input,
    .channel-description textarea {
      width: 100%;
      font-size: 16px;
    }

    .section {
      background: white;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px;
      margin-top: 20px;
      margin-left: 15%;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .section h3 {
      margin-top: 0;
      font-size: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 8px;
    }

    .playlist-section,
    .video-section {
      overflow-x: auto;
      overflow-y: hidden;
      white-space: nowrap;
      padding-bottom: 10px;
    }

    .playlist-grid,
    .video-grid {
      display: flex;
      flex-wrap: nowrap;
      gap: 16px;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }

    .video,
    .playlist-card {
      flex: 0 0 auto;
      scroll-snap-align: start;
      background: #fdfdfd;
      border-radius: 8px;
      box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.2s ease;
      text-decoration: none;
      color: inherit;
    }

    .video:hover,
    .playlist-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .video {
      width: 220px;
      min-height: 240px;
    }

    .playlist-card {
      width: 200px;
      height: 280px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .video img,
    .playlist-card img,
    .thumbnail,
    .playlist-thumb {
      width: 100%;
      height: 130px;
      object-fit: cover;
    }

    .playlist-info {
      padding: 10px;
      font-size: 14px;
      color: #444;
      flex-grow: 1;
      overflow: hidden;
    }

    .section .video-avatar {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      object-fit: cover;
    }

    .section .video-title {
      margin: 0;
      font-size: 16px;
      font-weight: bold;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      /* Chỉ hiển thị tối đa 2 dòng */
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
    }

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
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      display: none;
      z-index: 9999;
      min-width: 150px;
    }

    .menu-popup.show {
      display: block;
    }

    .playlist-popup-form {
      display: none;
      position: absolute;
      top: -160px;
      right: 0;
      background: white;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      width: 250px;
      animation: fadeIn 0.3s ease;
    }

    .playlist-popup-form input {
      width: 100%;
      margin-bottom: 8px;
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
    }

    .playlist-popup-form .confirm-btn {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 6px 10px;
      border-radius: 5px;
      cursor: pointer;
    }

    .playlist-popup-form .confirm-btn:hover {
      background-color: #218838;
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

    .playlist-form select,
    .playlist-form button {
      width: 100%;
      margin-top: 6px;
      padding: 6px;
      font-size: 14px;
      border-radius: 5px;
    }

    .confirm-btn {
      background: #28a745;
      color: white;
      border: none;
      transition: background 0.2s ease;
    }

    .confirm-btn:hover {
      background: #218838;
    }

    footer {
      margin-top: 50px;
      background: #f0f0f0;
      text-align: center;
      padding: 20px;
      color: #555;
      font-size: 14px;
      border-top: 1px solid #ddd;
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

    @media (max-width: 768px) {
      body {
        font-size: 15px;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
      }

      .content-wrapper {
        width: 100%;
        padding: 16px;
      }

      .channel-header {
        flex-direction: column;
        align-items: flex-start;
        padding: 12px;
        gap: 10px;
      }

      .channel-header img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
      }

      .channel-banner {
        width: 100%;
        max-height: 180px;
        object-fit: cover;
        border-radius: 8px;
      }

      .section {
        margin-left: 0;
        padding: 12px;
      }

      .playlist-grid,
      .video-grid {
        display: flex;
        flex-direction: row;
        gap: 12px;
        overflow-x: auto;
        white-space: nowrap;
      }

      .video {
        width: 160px;
        min-height: 200px;
      }

      .playlist-card {
        width: 160px;
        height: 240px;
      }

      .video-thumbnail {
        width: 100%;
        height: auto;
        border-radius: 6px;
      }

      .section .video-avatar {
        width: 8px;
        height: 8px;
        border-radius: 50%;
      }


      .video h4 {
        font-size: 16px;
        margin: 0;
      }

      .video p {
        font-size: 13px;
        margin: 0;
        color: #666;
      }

      .playlist-card img {
        width: 100px;
        height: 70px;
        object-fit: cover;
        border-radius: 4px;
      }

      .playlist-info {
        flex: 1;
      }

      .playlist-info h4 {
        font-size: 15px;
        margin: 0 0 4px 0;
      }

      .playlist-info p {
        font-size: 13px;
        margin: 0;
        color: #777;
      }

      .menu-popup,
      .playlist-popup-form {
        width: 90vw;
        max-width: 300px;
        right: 10px;
        left: auto;
        padding: 10px;
      }

      .playlist-popup-form {
        position: fixed;
        bottom: 0;
        top: auto;
        border-radius: 12px 12px 0 0;
        background: #fff;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
      }

      .playlist-form select,
      .playlist-form button {
        font-size: 14px;
        width: 100%;
        margin-top: 8px;
      }

      .add-playlist-btn {
        font-size: 16px;
        padding: 6px 12px;
      }

      footer {
        padding: 16px;
        font-size: 13px;
        text-align: center;
      }
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
            <img src="<?= htmlspecialchars($channel['avatar'] ?: '../images/default-avatar.png') ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;" alt="Avatar">

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

            <!-- Nút chỉnh sửa -->
            <a href="edit_channel.php" style="display: inline-block; padding: 6px 14px; background:rgb(15, 47, 81); color: white; border-radius: 6px; text-decoration: none;">
              ✏️ Chỉnh sửa kênh
            </a>

            <!-- Thống kê -->
            <div style="margin-top: 8px; font-size: 14px; color: #555;">
              🎞️ Tổng video: <?= $stats['total_videos'] ?? 0 ?> |
              👁️ Tổng lượt xem: <?= $stats['total_views'] ?? 0 ?> |
              👥 Đăng ký: <?= $totalSubscribers ?>
            </div>

          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="section">
    <div class="video-list">
      <h3>📺 Video của bạn</h3>
      <?php
      $stmt = $conn->prepare("
      SELECT v.*, c.name AS channel_name 
      FROM videos v 
      JOIN channels c ON v.uploaded_by = c.user_id 
      WHERE v.uploaded_by = ? 
      ORDER BY v.upload_date DESC
      ");

      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $videos = $stmt->get_result();
      ?>
      <?php if ($videos->num_rows === 0): ?>
        <p>🙁 Bạn chưa tải video nào.</p>
      <?php else: ?>
        <div class="video-grid">
          <?php while ($row = $videos->fetch_assoc()):
            $thumb = $row['thumbnail'] ?: 'default.jpg';
          ?>
            <div class="video">
              <a href="../main/watch.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: inherit;">
                <div style="position: relative;">
                  <img src="<?= $thumb ?: '../images/default.jpg' ?>" class="thumbnail" alt="Thumbnail">
                  <?php if (!empty($row['duration'])): ?>
                    <div style="
                      position: absolute;
                      bottom: 6px;
                      right: 6px;
                      background: rgba(0, 0, 0, 0.7);
                      color: white;
                      padding: 2px 6px;
                      border-radius: 4px;
                      font-size: 12px;
                    ">
                      <?= formatDuration($row['duration']) ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div style="padding: 10px;">
                  <div style="display: flex; gap: 10px; align-items: center;">
                    <img src="<?= htmlspecialchars($channel['avatar'] ?: '../images/default-avatar.png') ?>"
                      alt="Avatar"
                      class="video-avatar"
                      style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">

                    <h4 class="video-title" style="margin: 0;">
                      <?= htmlspecialchars($row['title']) ?>
                    </h4>
                  </div>
                  <p style="margin: 0 50px 0; font-size: 15px; color: #444; font-weight: bold; font-family: 'Segoe UI', Roboto, sans-serif;">
                    <?= htmlspecialchars($row['channel_name']) ?>
                  </p>
                  <p style="margin: 0 50px 0;">
                    <?= number_format($row['views']) ?> lượt xem • <?= date('d/m/Y', strtotime($row['upload_date'])) ?>
                  </p>
                </div>





              </a>

              <!-- Nút ba chấm + menu vẫn nằm ngoài thẻ <a> -->
              <div style="position: absolute; top: 10px; right: 10px;">
                <button class="menu-toggle" onclick="toggleMenu(this)" style="background: none; border: none; font-size: 18px; cursor: pointer;">⋮</button>
                <div class="menu-popup">
                  <form onsubmit="return deleteVideo(event, <?= $row['id'] ?>);">
                    <button type="submit">🗑️ Xóa video</button>
                  </form>

                  <div class="playlist-container">
                    <button type="button" class="playlist-toggle" onclick="togglePlaylistSelect(this)">📂 Thêm vào danh sách phát</button>
                    <form onsubmit="return addToPlaylist(event);" class="playlist-form">
                      <input type="hidden" name="video_id" value="<?= $row['id'] ?>">
                      <select name="playlist_id" required>
                        <?php
                        // Reset lại kết quả để lấy danh sách
                        $playlistStmt->execute();
                        $result = $playlistStmt->get_result();
                        while ($pl = $result->fetch_assoc()):
                        ?>
                          <option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['name']) ?></option>
                        <?php endwhile; ?>
                      </select>

                      <button type="submit" class="confirm-btn">✅ Xác nhận</button>
                    </form>

                  </div>
                </div>
              </div>
            </div>

          <?php endwhile; ?>
        </div> <!-- video-grid -->
      <?php endif; ?>
    </div>
  </div>

  <div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
      <h3 style="margin: 0;">📂 Danh Sách Phát</h3>
      <div style="position: relative;">
        <button onclick="toggleCreateForm()" class="add-playlist-btn" title="Thêm danh sách phát">➕</button>

        <!-- Form tạo playlist nổi gần nút ➕ -->
        <div id="createPlaylistForm" style="display: none;" class="playlist-popup-form">
          <input type="text" id="playlistName" placeholder="Tên danh sách phát" required>
          <input type="text" id="playlistDesc" placeholder="Mô tả (tuỳ chọn)">
          <button type="button" onclick="createPlaylist()">Lưu</button>
        </div>
      </div>
    </div>

    <!-- Grid hiển thị danh sách phát -->
    <div class="playlist-grid">
      <?php while ($playlist = $playlists->fetch_assoc()):
        $thumb = $playlist['thumbnail'];
      ?>
        <div class="playlist-card">
          <!-- Nút ba chấm nằm dưới cùng bên phải -->
          <div class="menu-container">
            <button class="menu-toggle" onclick="toggleMenu(this)">⋮</button>
            <div class="menu-popup">
              <form method="POST" action="delete_playlist.php" onsubmit="return confirm('Bạn có chắc muốn xóa danh sách phát này không?');">
                <input type="hidden" name="playlist_id" value="<?= $playlist['id'] ?>">
                <button type="submit" style="color: red;">🗑️ Xóa</button>
              </form>
            </div>
          </div>

          <!-- Nội dung danh sách phát -->
          <a href="../main/playlist.php?id=<?= $playlist['id'] ?>" class="playlist-link">
            <img src="<?= $thumb ?: '../images/default.jpg' ?>" alt="Thumbnail" class="thumbnail">

            <div class="playlist-info">
              <strong><?= htmlspecialchars($playlist['name']) ?></strong>
              <p><?= htmlspecialchars($playlist['description'] ?? 'Chưa có mô tả') ?></p>
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
    function togglePlaylistForm() {
      const form = document.getElementById("playlistForm");
      form.style.display = (form.style.display === "none" || form.style.display === '') ? "block" : "none";
    }

    document.addEventListener("click", function(e) {
      const form = document.getElementById("playlistForm");
      const plusBtn = document.getElementById("plusBtn");

      if (
        form && plusBtn &&
        !form.contains(e.target) &&
        !plusBtn.contains(e.target)
      ) {
        form.style.display = "none";
      }
    });


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

    function toggleCreateForm() {
      const form = document.getElementById('createPlaylistForm');
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function createPlaylist() {
      const name = document.getElementById('playlistName').value.trim();
      const desc = document.getElementById('playlistDesc').value.trim();

      if (!name) {
        alert('Vui lòng nhập tên danh sách phát.');
        return;
      }

      fetch('create_playlist.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `name=${encodeURIComponent(name)}&description=${encodeURIComponent(desc)}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Đã tạo danh sách phát!');
            location.reload(); // hoặc bạn có thể append thủ công vào DOM nếu không muốn reload
          } else {
            alert('Lỗi: ' + data.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert('Có lỗi xảy ra khi gửi dữ liệu.');
        });
    }

    function togglePlaylistSelect(button) {
      const form = button.nextElementSibling;
      form.style.display = form.style.display === 'block' ? 'none' : 'block';

      // Đóng các form khác
      document.querySelectorAll('.playlist-form').forEach(f => {
        if (f !== form) f.style.display = 'none';
      });
    }

    // Đóng khi click ra ngoài
    document.addEventListener('click', function(e) {
      document.querySelectorAll('.playlist-form').forEach(form => {
        const toggleBtn = form.previousElementSibling;
        if (!form.contains(e.target) && !toggleBtn.contains(e.target)) {
          form.style.display = 'none';
        }
      });
    });

    function deleteVideo(event, videoId) {
      event.preventDefault();

      if (!confirm('Bạn chắc chắn muốn xóa video này?')) return false;

      fetch('delete_video.php', {

          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'video_id=' + encodeURIComponent(videoId)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Đã xóa video!');
            // Ẩn video khỏi giao diện (DOM)
            const videoEl = event.target.closest('.video');
            if (videoEl) videoEl.remove();
          } else {
            alert('Xóa thất bại: ' + data.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert('Lỗi kết nối khi xóa video.');
        });

      return false;
    }

    function addToPlaylist(event) {
      event.preventDefault();

      const form = event.target;
      const video_id = form.querySelector('input[name="video_id"]').value;
      const playlist_id = form.querySelector('select[name="playlist_id"]').value;

      if (!playlist_id) {
        alert('Vui lòng chọn danh sách phát');
        return false;
      }

      fetch('add_to_playlist.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `video_id=${encodeURIComponent(video_id)}&playlist_id=${encodeURIComponent(playlist_id)}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Đã thêm vào danh sách phát!');
            form.style.display = 'none';
          } else {
            alert('Thêm thất bại: ' + data.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert('Lỗi khi thêm video vào playlist');
        });

      return false;
    }
  </script>

  <?php include '../includes/progress-bar.php'; ?>
  <div id="uploadProgressBar" style="display:none; position:fixed; bottom:20px; right:20px; background:#fff; border:1px solid #ccc; border-radius:8px; padding:10px 16px; z-index:9999; font-weight:bold;">
    🚀 Đang tải video: <span id="progressPercent">0%</span>
  </div>

  <script>
    const bar = document.getElementById('uploadProgressBar');
    const percentText = document.getElementById('progressPercent');
    const channel = new BroadcastChannel('upload-progress');

    channel.onmessage = (e) => {
      if (e.data.type === 'upload-progress') {
        bar.style.display = 'block';
        percentText.textContent = e.data.progress + '%';
      } else if (e.data.type === 'upload-finished') {
        percentText.textContent = '100% ✅';
        setTimeout(() => bar.style.display = 'none', 3000);
      }
    };
  </script>
</body>

</html>