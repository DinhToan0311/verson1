<?php
require '../loginphp/db.php';
session_start();

$keyword = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$duration = $_GET['duration'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if ($keyword === '') {
  die('Vui lòng nhập từ khóa tìm kiếm.');
}

function fetchVideos($conn, $keyword, $filter, $duration, $userId)
{
  $sql = "SELECT v.*, c.name AS channel_name
            FROM videos v
            LEFT JOIN channels c ON v.uploaded_by = c.user_id
            WHERE (v.title LIKE CONCAT('%', ?, '%') OR v.description LIKE CONCAT('%', ?, '%'))";

  $params = [$keyword, $keyword];
  $types = "ss";

  if ($filter === 'watched') {
    $sql .= " AND v.id IN (SELECT video_id FROM watch_history WHERE user_id = ?)";
    $params[] = $userId;
    $types .= "i";
  } elseif ($filter === 'unwatched') {
    $sql .= " AND v.id NOT IN (SELECT video_id FROM watch_history WHERE user_id = ?)";
    $params[] = $userId;
    $types .= "i";
  }

  if ($duration === 'short') {
    $sql .= " AND v.duration < 240";
  } elseif ($duration === 'medium') {
    $sql .= " AND v.duration BETWEEN 240 AND 1200";
  } elseif ($duration === 'long') {
    $sql .= " AND v.duration > 1200";
  }

  $sql .= " ORDER BY v.upload_date DESC";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  return $stmt->get_result();
}

// Tìm kênh
$stmtChannels = $conn->prepare("SELECT * FROM channels WHERE name LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%')");
$stmtChannels->bind_param("ss", $keyword, $keyword);
$stmtChannels->execute();
$channels = $stmtChannels->get_result();

// Tìm playlist
$stmtPlaylists = $conn->prepare("SELECT * FROM playlists WHERE name LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%')");
$stmtPlaylists->bind_param("ss", $keyword, $keyword);
$stmtPlaylists->execute();
$playlists = $stmtPlaylists->get_result();

// Video
$videos = fetchVideos($conn, $keyword, $filter, $duration, $userId);

?>


<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Search | MMG TUBE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    body {
      margin: 0;
      padding-top: 80px;
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      overflow-x: hidden;
      /* 🔒 Ngăn cuộn ngang */
    }

    .container {
      margin-left: 250px;
      /* chiều rộng sidebar */
      padding: 20px;
      max-width: calc(100% - 250px);
      /* tránh tràn ngang */
      box-sizing: border-box;
    }


    .filters {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      margin-bottom: 15px;
      margin-right: 20%;
      gap: 10px;
    }

    .filters a {
      padding: 8px 16px;
      background-color: #eee;
      border-radius: 20px;
      text-decoration: none;
      color: #333;
      font-size: 14px;
    }

    .filters a.active {
      background-color: #0066cc;
      color: white;
    }

    .video-item {
      display: flex;
      position: relative;
      margin-bottom: 20px;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.05);
    }

    .video-thumb img {
      width: 240px;
      height: 135px;
      object-fit: cover;
      display: block;
    }

    .video-info {
      padding: 12px 16px;
      flex: 1;
    }

    .video-title {
      font-size: 18px;
      font-weight: bold;
      color: #0066cc;
      text-decoration: none;
      display: block;
      margin-bottom: 6px;
    }

    .channel-name {
      font-size: 14px;
      color: #222;
      margin-bottom: 6px;
    }

    .video-meta {
      font-size: 13px;
      color: #666;
    }

    .video-meta span {
      margin-right: 16px;
    }

    .channel-item {
      display: flex;
      align-items: flex-start;
      background: #fff;
      padding: 12px 16px;
      margin-bottom: 16px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .channel-avatar img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 50%;
    }

    .channel-info {
      margin-left: 16px;
      flex: 1;
    }

    .channel-name {
      font-size: 20px;
      font-weight: bold;
      color: #0066cc;
      text-decoration: none;
    }

    .channel-desc {
      margin: 8px 0;
      color: #555;
    }

    .channel-meta {
      font-size: 13px;
      color: #999;
    }

    .playlist-item-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .playlist-item {
      display: flex;
      align-items: center;
      background: #fff;
      padding: 12px 16px;
      margin-bottom: 16px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      transition: background 0.2s;
    }

    .playlist-item:hover {
      background: #f0f8ff;
    }

    .playlist-thumb img {
      width: 160px;
      height: 90px;
      object-fit: cover;
      border-radius: 4px;
    }

    .playlist-info {
      margin-left: 16px;
    }

    .playlist-name {
      font-size: 18px;
      font-weight: bold;
      color: #0066cc;
    }

    .playlist-meta {
      font-size: 14px;
      color: #777;
      margin-top: 0px;
    }

    h3 {
      margin-bottom: 5px;
    }

    .video-options {
      position: absolute;
      top: 8px;
      right: 8px;
    }

    .video-options form button {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: #999;
    }

    .video-options form button:hover {
      color: #333;
    }

    .video-item {
      position: relative;
      /* ⚠️ cần thiết để .video-options định vị đúng */
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

    @media (max-width: 768px) {
      body {
        padding-top: 60px;
        font-size: 15px;
      }

      .container {
        margin-left: 0;
        max-width: 100%;
        padding: 12px;
      }

      .filters {
        flex-direction: column;
        align-items: flex-start;
        margin-right: 0;
        gap: 8px;
      }

      .filters a {
        font-size: 13px;
        padding: 6px 12px;
      }

      .video-item {
        flex-direction: column;
      }

      .video-thumb img {
        width: 100%;
        height: auto;
        max-height: 200px;
      }

      .video-info {
        padding: 10px;
      }

      .video-title {
        font-size: 16px;
      }

      .channel-name {
        font-size: 13px;
      }

      .video-meta {
        font-size: 12px;
      }

      .channel-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .channel-avatar img {
        width: 80px;
        height: 80px;
      }

      .channel-info {
        margin-left: 0;
        margin-top: 12px;
      }

      .channel-name {
        font-size: 18px;
      }

      .playlist-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .playlist-thumb img {
        width: 100%;
        height: auto;
      }

      .playlist-info {
        margin-left: 0;
        margin-top: 12px;
      }

      .playlist-name {
        font-size: 16px;
      }

      .playlist-meta {
        font-size: 13px;
      }

      .video-options {
        top: 6px;
        right: 6px;
      }
    }
  </style>
</head>

<body>
  <!-- Header -->
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
  <div class="container">
    <h2>Kết quả tìm kiếm cho: "<?= htmlspecialchars($keyword) ?>"</h2>

    <div class="filters">
      <div>
        <a href="?q=<?= urlencode($keyword) ?>&filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">Tất cả</a>
        <a href="?q=<?= urlencode($keyword) ?>&filter=watched" class="<?= $filter === 'watched' ? 'active' : '' ?>">Đã xem</a>
        <a href="?q=<?= urlencode($keyword) ?>&filter=unwatched" class="<?= $filter === 'unwatched' ? 'active' : '' ?>">Chưa xem</a>
      </div>
      <div>
        <a href="?q=<?= urlencode($keyword) ?>&duration=short" class="<?= $duration === 'short' ? 'active' : '' ?>">Dưới 4 phút</a>
        <a href="?q=<?= urlencode($keyword) ?>&duration=medium" class="<?= $duration === 'medium' ? 'active' : '' ?>">4 - 20 phút</a>
        <a href="?q=<?= urlencode($keyword) ?>&duration=long" class="<?= $duration === 'long' ? 'active' : '' ?>">Trên 20 phút</a>
      </div>
    </div>

    <?php while ($video = $videos->fetch_assoc()): ?>
      <div class="video-item">
        <div class="video-thumb" style="position: relative;">
          <a href="watch.php?id=<?= $video['id'] ?>">
            <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="<?= htmlspecialchars($video['title']) ?>">
            <div style="position: absolute; bottom: 6px; right: 6px; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; font-size: 12px; border-radius: 4px;">
              <?= gmdate("i:s", $video['duration']) ?>
            </div>
          </a>
        </div>
        <div class="video-info">
          <a href="watch.php?id=<?= $video['id'] ?>" class="video-title"><?= htmlspecialchars($video['title']) ?></a>
          <div class="channel-name"><?= htmlspecialchars($video['channel_name'] ?? 'Không rõ') ?></div>
          <div class="video-meta">
            <span>Ngày đăng: <?= date('d/m/Y', strtotime($video['upload_date'])) ?></span>
            <span><?= number_format($video['views']) ?> lượt xem</span>
          </div>
        </div>
        <div class="video-options">
          <button onclick="addToWatchLater(<?= $video['id'] ?>)" title="Thêm vào xem sau">
            <i class="fas fa-ellipsis-v"></i>
          </button>
        </div>
      </div>
    <?php endwhile; ?>

  </div>
  <div class="container">
    <!-- Danh sách kênh -->
    <?php if ($channels->num_rows > 0): ?>
      <div class="channel-list">
        <?php while ($channel = $channels->fetch_assoc()): ?>
          <div class="channel-item">
            <div class="channel-avatar">
              <img src="<?= htmlspecialchars($channel['avatar'] ?? 'default-avatar.png') ?>" width="100" height="100" style="border-radius: 50%;">
            </div>
            <div class="channel-info">
              <a href="public_channel.php?id=<?= $channel['id'] ?>" class="channel-name playlist-channel-name">

                <?= htmlspecialchars($channel['name']) ?>
              </a>
              <p class="channel-desc"><?= htmlspecialchars($channel['description']) ?></p>
              <span class="channel-meta">Ngày tạo: <?= date('d/m/Y', strtotime($channel['created_at'])) ?></span>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
    <!-- Danh sách phát -->
    <?php if ($playlists->num_rows > 0): ?>
      <div class="playlist-list">
        <?php while ($pl = $playlists->fetch_assoc()): ?>
          <?php
          // Đếm số video trong playlist
          $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM playlist_videos WHERE playlist_id = ?");
          $stmt->bind_param("i", $pl['id']);
          $stmt->execute();
          $countResult = $stmt->get_result()->fetch_assoc();
          $videoCount = $countResult['total'] ?? 0;

          // Lấy thumbnail đầu tiên của playlist này
          $stmtThumb = $conn->prepare("SELECT v.thumbnail 
                                 FROM playlist_videos pv
                                 JOIN videos v ON pv.video_id = v.id
                                 WHERE pv.playlist_id = ?
                                 ORDER BY pv.video_id ASC
                                 LIMIT 1");
          $stmtThumb->bind_param("i", $pl['id']);
          $stmtThumb->execute();
          $thumbResult = $stmtThumb->get_result();
          $thumb = $thumbResult->fetch_assoc();
          $thumbnail = $thumb['thumbnail'] ?? 'default.jpg';
          ?>

          <a href="playlist.php?id=<?= $pl['id'] ?>" class="playlist-item-link">
            <div class="playlist-item">
              <div class="playlist-thumb">
                <img src="<?= htmlspecialchars($thumbnail) ?>" alt="<?= htmlspecialchars($pl['name']) ?>">
              </div>
              <div class="playlist-info">
                <h3>Danh Sách Phát</h3>
                <div class="playlist-name"><?= htmlspecialchars($pl['name']) ?></div>
                <div class="playlist-meta"><?= $videoCount ?> video</div>
              </div>
            </div>
          </a>

        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
  <footer>
    © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
  </footer>
  <script>
    function addToWatchLater(videoId) {
      fetch('../loginphp/add_to_watch_later.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `video_id=${videoId}`
        })
        .then(res => res.json())
        .then(data => {
          alert(data.message);
        })
        .catch(err => {
          console.error('Lỗi:', err);
          alert('Không thể thêm vào xem sau.');
        });
    }
  </script>

</body>