<?php
session_start();
require '../loginphp/db.php';

$playlistId = $_GET['id'] ?? null;
$videoId = $_GET['video'] ?? null;

if (!$playlistId) {
  die("Danh sách phát không tồn tại.");
}
mysqli_set_charset($conn, "utf8mb4");

// Lấy thông tin danh sách phát
$stmt = $conn->prepare("SELECT playlists.name, users.name AS owner 
  FROM playlists 
  JOIN users ON playlists.user_id = users.id 
  WHERE playlists.id = ?");
$stmt->bind_param("i", $playlistId);
$stmt->execute();
$playlist = $stmt->get_result()->fetch_assoc();
if (!$playlist) die("❌ Không tìm thấy danh sách phát.");

// Lấy danh sách video trong playlist
$stmt = $conn->prepare("
  SELECT videos.*, channels.name AS channel_name 
  FROM playlist_videos 
  JOIN videos ON playlist_videos.video_id = videos.id 
  JOIN channels ON videos.uploaded_by = channels.user_id
  WHERE playlist_videos.playlist_id = ?
");

$stmt->bind_param("i", $playlistId);
$stmt->execute();
$videos = $stmt->get_result();

$videoList = [];
while ($v = $videos->fetch_assoc()) {
  $videoList[] = $v;
}

if (count($videoList) === 0) die("⚠️ Danh sách phát này chưa có video.");

// Tìm video hiện tại
$currentVideo = $videoList[0];
if ($videoId) {
  foreach ($videoList as $v) {
    if ($v['id'] == $videoId) {
      $currentVideo = $v;
      break;
    }
  }
}

$currentVideoId = $currentVideo['id'];

// Lấy thông tin người đăng
$stmt = $conn->prepare("SELECT users.name AS uploader_name FROM users JOIN videos ON users.id = videos.uploaded_by WHERE videos.id = ?");
$stmt->bind_param("i", $currentVideoId);
$stmt->execute();
$uploader = $stmt->get_result()->fetch_assoc();
$currentVideo['uploader_name'] = $uploader['uploader_name'] ?? 'Không rõ';

// Kiểm tra đã yêu thích chưa
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND video_id = ?");
  $stmt->bind_param("ii", $_SESSION['user_id'], $currentVideoId);
  $stmt->execute();
  $isFavorite = $stmt->get_result()->num_rows > 0;
}

// Xử lý bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
  $content = trim($_POST['comment']);
  if ($content !== '' && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, video_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $_SESSION['user_id'], $currentVideoId, $content);
    $stmt->execute();
    header("Location: ?id=$playlistId&video=$currentVideoId");
    exit;
  }
}

// Lấy danh sách bình luận
$stmt = $conn->prepare("SELECT comments.*, users.name FROM comments JOIN users ON comments.user_id = users.id WHERE video_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $currentVideoId);
$stmt->execute();
$comments = $stmt->get_result();
?>
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
<link rel="icon" href="logo.png" type="image/png">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" href="logo.png" type="image/png">
<div class="playlist-container">
  <div class="main-video">
    <h2>▶️ Đang phát: <?= htmlspecialchars($currentVideo['title']) ?></h2>
    <video controls autoplay width="100%" poster="<?= htmlspecialchars($currentVideo['thumbnail'] ?: 'default.jpg') ?>">

      <source src="<?= htmlspecialchars($currentVideo['filename']) ?>" type="video/mp4">
      Trình duyệt không hỗ trợ video.
    </video>

    <div style="margin-top: 10px;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <strong><?= htmlspecialchars($currentVideo['channel_name']) ?></strong>

          • <?= number_format($currentVideo['views'] + 1) ?> lượt xem • <?= date('d/m/Y', strtotime($currentVideo['upload_date'])) ?>
        </div>
        <div>
          <?php if (isset($_SESSION['user_id'])): ?>
            <i
              id="heart-icon"
              class="fa-solid fa-heart"
              style="cursor: pointer; font-size: 20px; color: <?= $isFavorite ? 'red' : '#aaa' ?>;"
              onclick="toggleFavorite(<?= $currentVideoId ?>)"></i>
          <?php else: ?>
            <a href="../loginphp/login.php">❤️ Đăng nhập để yêu thích</a>
          <?php endif; ?>
        </div>
      </div>
      <p><?= nl2br(htmlspecialchars($currentVideo['description'] ?? '')) ?></p>
    </div>

    <hr>
    <h3>💬 Bình luận</h3>
    <?php if (isset($_SESSION['user_id'])): ?>
      <form method="POST">
        <textarea name="comment" rows="3" placeholder="Nhập bình luận..." required></textarea><br>
        <button type="submit">Gửi</button>
      </form>
    <?php else: ?>
      <p><a href="../loginphp/login.php">Đăng nhập</a> để bình luận.</p>
    <?php endif; ?>

    <?php while ($cmt = $comments->fetch_assoc()): ?>
      <div class="comment-box">
        <strong><?= htmlspecialchars($cmt['name']) ?></strong>
        <em><?= date('H:i d/m/Y', strtotime($cmt['created_at'])) ?></em><br>
        <?= nl2br(htmlspecialchars($cmt['content'])) ?>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="playlist-sidebar">
    <h3>📂 <?= htmlspecialchars($playlist['name']) ?></h3>
    <p>👤 Tạo bởi: <?= htmlspecialchars($playlist['owner']) ?></p>
    <hr>
    <?php foreach ($videoList as $v): ?>
      <a href="?id=<?= $playlistId ?>&video=<?= $v['id'] ?>" class="suggestion-item">
        <img src="<?= htmlspecialchars($v['thumbnail'] ?: 'default.jpg') ?>" alt="thumb">

        <div class="info">
          <div><?= htmlspecialchars($v['title']) ?></div>
          <div style="font-size: 13px; color: #555;"><?= htmlspecialchars($v['channel_name']) ?></div>
          <div><?= number_format($v['views']) ?> lượt xem</div>
          <div><?= date('d/m/Y', strtotime($v['upload_date'])) ?></div>

        </div>
        <div class="more-menu">⋮</div>
      </a>
    <?php endforeach; ?>


  </div>
</div>
<footer>
  © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
</footer>
<style>
  .suggestion-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 8px;
    text-decoration: none;
    color: inherit;
    border-radius: 8px;
    transition: background 0.2s;
    position: relative;
  }

  .suggestion-item:hover {
    background: #f2f2f2;
  }

  .suggestion-item img {
    width: 120px;
    height: 70px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
  }

  .suggestion-item .info {
    font-size: 14px;
    flex-grow: 1;
    overflow: hidden;
  }

  .suggestion-item .info div:first-child {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .more-menu {
    font-size: 18px;
    color: #999;
    cursor: pointer;
  }

  .playlist-container {
    display: flex;
    padding: 30px 5%;
    gap: 24px;
    align-items: flex-start;
  }

  .main-video {
    flex: 0 0 58%;
    /* 58% chiều ngang */
    max-width: 58%;
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  }

  .playlist-sidebar {
    flex: 1;
    max-width: 40%;
    min-width: 300px;
    max-height: 90vh;
    overflow-y: auto;
    background: #fff;
    padding: 16px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 20px;
  }

  .main-video video {
    width: 100%;
    height: 400px;
    /* ✅ Tăng chiều cao tại đây */
    object-fit: contain;
    /* Hoặc cover tùy bạn */
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
  }

  @media (max-width: 768px) {
    body {
      font-size: 15px;
      margin: 0;
      padding: 0;
    }

    .playlist-container {
      flex-direction: column;
      padding: 10px 10px;
      gap: 20px;
    }

    .main-video,
    .playlist-sidebar {
      max-width: 100%;
      width: 100%;
      box-shadow: none;
      padding: 12px;
      border-radius: 8px;
    }

    .main-video video {
      max-height: 220px;
      height: auto;
    }

    h2,
    h3 {
      font-size: 18px;
    }

    .suggestion-item {
      padding: 8px 6px;
      gap: 10px;
    }

    .suggestion-item img {
      width: 90px;
      height: 55px;
      border-radius: 5px;
    }

    .suggestion-item .info {
      font-size: 13px;
    }

    .suggestion-item .info div:first-child {
      font-size: 14px;
      font-weight: 600;
    }

    .more-menu {
      font-size: 16px;
    }

    form textarea {
      width: 100%;
      font-size: 14px;
      padding: 8px;
      border-radius: 6px;
      resize: vertical;
    }

    form button {
      padding: 6px 14px;
      margin-top: 6px;
      font-size: 14px;
      border-radius: 6px;
      background-color: #007bff;
      color: white;
      border: none;
      cursor: pointer;
    }

    form button:hover {
      background-color: #0056b3;
    }

    .comment-box {
      font-size: 14px;
      margin-top: 12px;
      padding: 8px;
      border-left: 3px solid #ddd;
      background: #fafafa;
      border-radius: 6px;
    }

    footer {
      text-align: center;
      padding: 10px 0;
      font-size: 14px;
    }
  }
</style>