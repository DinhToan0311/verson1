<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Bạn cần đăng nhập để xem lịch sử.");
}

$userId = $_SESSION['user_id'];

// Xoá từng video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id'])) {
  $videoId = intval($_POST['video_id']);
  $stmt = $conn->prepare("DELETE FROM watch_history WHERE user_id = ? AND video_id = ?");
  $stmt->bind_param("ii", $userId, $videoId);
  $stmt->execute();
}

// Xoá tất cả
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
  $stmt = $conn->prepare("DELETE FROM watch_history WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
}

// Lấy lịch sử xem
$sql = "SELECT 
    v.id, v.title, v.filename, v.thumbnail, v.views, v.duration, v.description,
    h.watched_at,
    c.name AS channel_name, c.description AS channel_description, c.avatar
FROM watch_history h
JOIN videos v ON h.video_id = v.id
JOIN channels c ON v.uploaded_by = c.user_id
WHERE h.user_id = ?
ORDER BY h.watched_at DESC";



function formatDuration($seconds)
{
  if (!is_numeric($seconds)) return '00:00';
  $seconds = (int)$seconds;
  $h = floor($seconds / 3600);
  $m = floor(($seconds % 3600) / 60);
  $s = $seconds % 60;
  return $h > 0
    ? sprintf("%02d:%02d:%02d", $h, $m, $s)
    : sprintf("%02d:%02d", $m, $s);
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
// nhóm ngày hôm qua hôm kia
$currentDate = null;

function groupDateLabel($date)
{
  $today = date('Y-m-d');
  $yesterday = date('Y-m-d', strtotime('-1 day'));
  $watchedDate = date('Y-m-d', strtotime($date));

  if ($watchedDate == $today) return "📅 Hôm nay";
  if ($watchedDate == $yesterday) return "📆 Hôm qua";
  setlocale(LC_TIME, 'vi_VN.UTF-8'); // Đặt ngôn ngữ nếu cần
  return "🗓️ " . (new IntlDateFormatter(
    'vi_VN',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'Asia/Ho_Chi_Minh',
    IntlDateFormatter::GREGORIAN,
    "EEEE, dd/MM/yyyy"
  ))->format(strtotime($date));
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Lịch sử xem</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      background: #f5f5f5;
    }

    .main {
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .history-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 24px;
      gap: 12px;
    }

    .history-header h2 {
      font-size: 24px;
      color: #333;
    }

    .delete-all-form button {
      background: #ff4d4d;
      border: none;
      color: white;
      padding: 10px 16px;
      font-size: 14px;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .delete-all-form button:hover {
      background: #cc0000;
    }

    .video-history-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .video-item {
      display: flex;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
      transition: box-shadow 0.3s;
      position: relative;
    }

    .video-item:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .video-item a {
      display: block;
      position: relative;
      width: 200px;
      height: 120px;
      flex-shrink: 0;
    }

    .video-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .video-duration {
      position: absolute;
      bottom: 6px;
      right: 8px;
      background: rgba(0, 0, 0, 0.75);
      color: #fff;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 4px;
    }

    .video-info {
      padding: 12px 16px;
      flex-grow: 1;
    }

    .video-info h3 {
      margin: 0;
      font-size: 18px;
      color: #0066cc;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .channel-name {
      font-size: 14px;
      color: #555;
      margin-top: 4px;
    }

    .video-subtitle {
      font-size: 13px;
      color: #777;
      margin-top: 6px;
      line-height: 1.4;
      max-height: 2.8em;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .video-actions {
      display: flex;
      align-items: center;
      padding: 0 12px;
    }

    .video-actions button {
      background: none;
      border: none;
      font-size: 20px;
      color: #888;
      cursor: pointer;
      transition: color 0.2s;
    }

    .video-actions button:hover {
      color: red;
    }

    h4 {
      margin: 30px 0 10px;
      color: #333;
      font-size: 16px;
    }

    /* FOOTER */
    footer {
      margin-top: 50px;
      background: #f0f0f0;
      text-align: center;
      padding: 20px;
      color: #555;
      font-size: 14px;
      border-top: 1px solid #ddd;
    }

    /* MOBILE OPTIMIZATION */
    @media (max-width: 768px) {
      .main {
        padding: 12px;
      }

      .history-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .history-header h2 {
        font-size: 18px;
      }

      .delete-all-form button {
        font-size: 13px;
        padding: 10px 14px;
        width: 100%;
      }

      .video-item {
        flex-direction: column;
        width: 100%;
      }

      .video-item a {
        width: 100%;
        height: 200px;
      }

      .video-info {
        padding: 12px;
      }

      .video-info h3 {
        font-size: 16px;
        white-space: normal;
      }

      .channel-name {
        font-size: 13px;
      }

      .video-subtitle {
        font-size: 13px;
        max-height: 3.2em;
        line-height: 1.6;
      }

      .video-actions {
        padding: 10px 12px;
        justify-content: flex-end;
      }

      .video-actions button {
        font-size: 18px;
      }

      h4 {
        font-size: 14px;
        margin-top: 20px;
      }

      footer {
        font-size: 13px;
        padding: 15px 10px;
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

  <div class="main">
    <div class="history-header">
      <h2>📜 Nhật ký xem gần đây</h2>
      <?php if ($result->num_rows > 0): ?>
        <form method="post" class="delete-all-form" onsubmit="return confirm('Bạn có chắc muốn xoá tất cả lịch sử?')">
          <button type="submit" name="delete_all">🧹 Xoá tất cả</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="video-history-list">

        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
          $watchedDate = date('Y-m-d', strtotime($row['watched_at']));
          if ($watchedDate !== $currentDate):
            $currentDate = $watchedDate;
          ?>
            <h4 style="margin: 30px 0 10px; color: #333;">
              <?= groupDateLabel($row['watched_at']) ?>
            </h4>
          <?php endif; ?>

          <div class="video-item">
            <a href="watch.php?id=<?= $row['id'] ?>" class="video-thumb-wrapper">
              <img src="<?= htmlspecialchars($row['thumbnail']) ?>" class="video-thumb" alt="Thumbnail">

              <div class="video-overlay">

                <?php if (!empty($row['duration'])): ?>
                  <div class="video-duration"><?= formatDuration($row['duration']) ?></div>
                <?php endif; ?>
              </div>
            </a>


            <div class="video-info">
              <h3 class="video-title"><?= htmlspecialchars($row['title']) ?></h3>

              <p class="channel-name">
                <?= htmlspecialchars($row['channel_name']) ?> • <?= number_format($row['subscribers'] ?? 0) ?> đăng ký
              </p>
              <p class="video-subtitle">
                <?= htmlspecialchars(mb_strimwidth($row['description'], 0, 120, '...')) ?>
              </p>

            </div>

            <div class="video-actions">
              <form method="post" onsubmit="return confirm('Xoá video này khỏi lịch sử?')">
                <input type="hidden" name="video_id" value="<?= $row['id'] ?>">
                <button type="submit" title="Xoá">🗑</button>
              </form>
            </div>
          </div>

        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p>Chưa có video nào trong lịch sử xem.</p>
    <?php endif; ?>
  </div>
  <footer>
    © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
  </footer>
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