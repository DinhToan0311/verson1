<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Bạn cần đăng nhập để xem danh sách xem sau.");
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
  SELECT videos.*, watch_later.id AS watch_later_id FROM watch_later 
  JOIN videos ON watch_later.video_id = videos.id 
  WHERE watch_later.user_id = ?
  ORDER BY watch_later.added_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$videos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Danh sách Xem Sau</title>
  <link rel="icon" href="logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    .watch-later-container {
      padding: 40px 5%;

    }

    .watch-later-container h2 {
      font-size: 24px;
      margin-bottom: 24px;
      color: #222;
    }

    .video-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 20px;
      text-decoration: none;
      color: inherit;
      gap: 16px;
      background: #fff;
      padding: 14px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      position: relative;
    }

    .video-item img {
      width: 180px;
      height: 100px;
      object-fit: cover;
      border-radius: 6px;
      flex-shrink: 0;
    }

    .video-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .video-info h3 {
      font-size: 16px;
      margin: 0 0 8px;
      color: #0066cc;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .video-info p {
      font-size: 13px;
      color: #666;
      margin: 2px 0;
    }

    .remove-btn {
      position: absolute;
      top: 10px;
      right: 12px;
      background: none;
      border: none;
      font-size: 16px;
      color: #888;
      cursor: pointer;
    }

    .remove-btn:hover {
      color: red;
    }

    .video-duration {
      position: absolute;
      bottom: 8px;
      right: 12px;
      background: rgba(0, 0, 0, 0.7);
      color: #fff;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 4px;
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

    /* MOBILE RESPONSIVE */
    @media (max-width: 768px) {
      body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
      }

      .watch-later-container {
        padding: 12px;
        margin: 0;
        width: 100%;
        max-width: 100%;
      }

      .video-item {
        flex-direction: row;
        align-items: center;
        padding: 12px;
        gap: 12px;
        width: 100%;
      }

      .video-item img {
        width: 120px;
        height: 70px;
        border-radius: 6px;
        flex-shrink: 0;
      }

      .video-info {
        width: 100%;
        overflow: hidden;
      }

      .video-info h3 {
        font-size: 14px;
        margin: 0;
        color: #0066cc;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
      }

      .video-info p {
        font-size: 12px;
        color: #555;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
      }

      .remove-btn {
        position: absolute;
        top: 6px;
        right: 6px;
        font-size: 18px;
      }

      .video-duration {
        bottom: 10px;
        right: 10px;
        padding: 3px 6px;
        font-size: 11px;
      }

      footer {
        font-size: 13px;
        padding: 15px;
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


  <div class="watch-later-container">
    <h2>🕓 Danh sách Xem Sau</h2>

    <div id="watchLaterList">
      <?php if ($videos->num_rows === 0): ?>
        <p>Danh sách của bạn đang trống.</p>
      <?php endif; ?>
      <?php
      function formatDuration($seconds)
      {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
      }
      ?>

      <?php while ($video = $videos->fetch_assoc()):
        $thumb = $video['thumbnail'] ?: 'default.jpg';

        // Hàm định dạng thời gian (bạn có thể đưa ra ngoài vòng lặp nếu muốn tái sử dụng)
      ?>
        <div class="video-item" id="item-<?= $video['watch_later_id'] ?>">
          <a href="watch.php?id=<?= $video['id'] ?>" style="position: relative; display: inline-block;">
            <img src="<?= htmlspecialchars($thumb) ?>" alt="Thumbnail">


            <?php if (!empty($video['duration'])): ?>
              <div class="video-duration"><?= formatDuration($video['duration']) ?></div>
            <?php endif; ?>
          </a>
          <div class="video-info">
            <h3><?= htmlspecialchars($video['title']) ?></h3>
            <p><?= number_format($video['views']) ?> lượt xem</p>
            <p>Đăng ngày: <?= date('d/m/Y', strtotime($video['upload_date'])) ?></p>
          </div>
          <button class="remove-btn" onclick="removeWatchLater(<?= $video['watch_later_id'] ?>)">
            <i class="fas fa-times"></i>
          </button>
        </div>
      <?php endwhile; ?>

    </div>
  </div>

  <script>
    function removeWatchLater(watchLaterId) {
      if (!confirm("Bạn có chắc muốn xóa video này khỏi Xem Sau?")) return;

      fetch("../main/xemsau/remove_watch_later.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "id=" + watchLaterId
        })
        .then(res => res.text())
        .then(data => {
          if (data.trim() === "OK") {
            const item = document.getElementById("item-" + watchLaterId);
            if (item) item.remove();
          } else {
            alert("Lỗi khi xóa video.");
          }
        });
    }
  </script>
</body>
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
    } else if (e.data.type === 'notify') {
      alert(e.data.message);
    }
  };
</script>

</html>
<footer>
  © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
</footer>