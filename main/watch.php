<?php
require '../loginphp/db.php';
session_start();

// Lấy videoId
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("Video không tồn tại.");
}
$videoId = intval($_GET['id']);

// Lưu lịch sử xem nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
  $userId = $_SESSION['user_id'];

  $check = $conn->prepare("SELECT id FROM watch_history WHERE user_id = ? AND video_id = ?");
  $check->bind_param("ii", $userId, $videoId);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    $update = $conn->prepare("UPDATE watch_history SET watched_at = NOW() WHERE user_id = ? AND video_id = ?");
    $update->bind_param("ii", $userId, $videoId);
    $update->execute();
  } else {
    $insert = $conn->prepare("INSERT INTO watch_history (user_id, video_id, watched_at) VALUES (?, ?, NOW())");
    $insert->bind_param("ii", $userId, $videoId);
    $insert->execute();
  }
}

// Lấy thông tin video
// Thay thế toàn bộ khối chuẩn bị $stmt cũ bằng khối này
$stmt = $conn->prepare("
  SELECT 
    v.*,
    u.name       AS uploader_name,
    ch.id        AS channel_id,
    ch.name      AS channel_name,
    ch.avatar    AS channel_avatar
  FROM videos v
  JOIN users u     ON v.uploaded_by = u.id
  JOIN channels ch ON ch.user_id   = u.id
  WHERE v.id = ?
");


$stmt->bind_param("i", $videoId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  die("Không tìm thấy video.");
}
$video = $result->fetch_assoc();

// Tăng lượt xem
$conn->query("UPDATE videos SET views = views + 1 WHERE id = $videoId");

// Kiểm tra đã yêu thích hay chưa
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
  $fav_check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND video_id = ?");
  $fav_check->bind_param("ii", $_SESSION['user_id'], $videoId);
  $fav_check->execute();
  $fav_check->store_result();
  $isFavorite = $fav_check->num_rows > 0;
} // kiểm xem sau
$isWatchLater = false;
if (isset($_SESSION['user_id'])) {
  $wl_check = $conn->prepare("SELECT id FROM watch_later WHERE user_id = ? AND video_id = ?");
  $wl_check->bind_param("ii", $_SESSION['user_id'], $videoId);
  $wl_check->execute();
  $wl_check->store_result();
  $isWatchLater = $wl_check->num_rows > 0;
}

// Lấy bình luận
$cmt_stmt = $conn->prepare("SELECT c.content, c.created_at, u.name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_id = ? ORDER BY c.created_at DESC");
$cmt_stmt->bind_param("i", $videoId);
$cmt_stmt->execute();
$comments = $cmt_stmt->get_result();

// Gửi bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_SESSION['user_id'])) {
  $comment = trim($_POST['comment']);
  if ($comment !== '') {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, video_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $_SESSION['user_id'], $videoId, $comment);
    $stmt->execute();
    header("Location: watch.php?id=$videoId");
    exit;
  }
}
$isSubscribed = false;
if (isset($_SESSION['user_id'])) {
  $checkSub = $conn->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
  $checkSub->bind_param("ii", $_SESSION['user_id'], $video['channel_id']);
  $checkSub->execute();
  $checkSub->store_result();
  $isSubscribed = $checkSub->num_rows > 0;
}

?>


<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="logo.png" type="image/png">
  <title><?= htmlspecialchars($video['title']) ?> - BlueTube</title>
  <link rel="stylesheet" href="../css/style.css" />
  <!-- Font Awesome CDN -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <!-- Thêm vào trước <style> -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap&subset=vietnamese" rel="stylesheet">

  <style>
    .body {
      font-family: 'Roboto', sans-serif;
    }

    .main {
      display: flex;
      margin-top: 60px;
      padding: 20px;
      gap: 20px;
    }

    .video-section {
      flex: 3;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .video-section video {
      width: 100%;
      border-radius: 6px;
      margin-bottom: 16px;
    }

    .video-section h2 {
      font-size: 20px;
      margin-bottom: 10px;
      color: #222;
    }

    .video-section p {
      font-size: 14px;
      color: #555;
      margin: 5px 0;
    }

    .video-section textarea {
      width: 100%;
      border-radius: 6px;
      border: 1px solid #ccc;
      padding: 10px;
      font-size: 14px;
      resize: vertical;
    }

    .video-section button {
      margin-top: 8px;
      padding: 8px 16px;
      background: #0066cc;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .video-section button:hover {
      background: #004b99;
    }

    .comment-box {
      margin-top: 16px;
      padding: 10px;
      border-bottom: 1px solid #eee;
      font-size: 14px;
      color: #333;
    }

    .comment-box strong {
      color: #0066cc;
    }

    .suggestions {
      flex: 1.3;
    }

    .suggestions h3 {
      margin-bottom: 12px;
    }

    .suggestions a {
      display: flex;
      margin-bottom: 12px;
      text-decoration: none;
      color: #000;
    }

    .suggestions img {
      width: 120px;
      height: 70px;
      object-fit: cover;
      border-radius: 4px;
    }

    .suggestions .info {
      margin-left: 10px;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      overflow-wrap: break-word;
    }

    .suggestion-item {
      position: relative;
      align-items: center;
    }

    .more-menu {
      font-size: 18px;
      color: #888;
      margin-left: auto;
      cursor: pointer;
      padding: 4px;
      transition: color 0.2s ease;
    }

    .more-menu:hover {
      color: #222;
    }

    .suggestions .info div:first-child {
      font-weight: bold;
      font-size: 14px;
      margin-bottom: 4px;
    }

    .suggestions .info div:last-child {
      font-size: 12px;
      color: #555;
    }

    .video-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      font-size: 14px;
      color: #555;
    }

    .video-meta .left-info {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .main .video-section .goku {
      padding-left: 20px;
    }


    .video-section video {
      width: 100%;
      height: auto;
      aspect-ratio: 16 / 9;
      object-fit: contain;
      border-radius: 6px;
      background-color: #000;
      max-height: 80vh;
    }


    /* đề xuất */
    .suggestions a {
      display: flex;
      align-items: flex-start;
      margin-bottom: 20px;
      text-decoration: none;
      color: #000;
      gap: 12px;
      width: 100%;
    }

    .suggestions img {
      width: 140px;
      height: 80px;
      object-fit: cover;
      border-radius: 6px;
      flex-shrink: 0;
    }

    .suggestions .info {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      overflow-wrap: break-word;
    }

    .suggestions .info div:first-child {
      font-weight: bold;
      font-size: 14px;
      line-height: 1.4;
      margin-bottom: 4px;
    }

    .suggestions .info div:last-child {
      font-size: 12px;
      color: #666;
      line-height: 1.4;
      word-break: break-word;
    }

    .video-meta .right-actions i {
      transition: color 0.2s ease;
    }

    .video-meta .right-actions i:hover {
      color: #333;
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

    /* ẩn hiện mota */
    #video-description {
      overflow: hidden;
      position: relative;
      transition: max-height 0.3s ease;
    }

    #video-description.collapsed {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      max-height: 4.5em;
      /* tương ứng với 3 dòng */
    }

    #video-description.expanded {
      -webkit-line-clamp: unset;
      max-height: none;
    }

    @keyframes shake-bell {
      0% {
        transform: rotate(0deg);
      }

      25% {
        transform: rotate(15deg);
      }

      50% {
        transform: rotate(-15deg);
      }

      75% {
        transform: rotate(10deg);
      }

      100% {
        transform: rotate(0deg);
      }
    }

    .shake {
      animation: shake-bell 0.5s ease;
    }

    @media only screen and (max-width: 768px) {
      body {
        font-size: 15px;
        background-color: #f9f9f9;
        margin: 0;
        padding: 0;
      }

      .main {
        flex-direction: column;
        padding: 8px;
        gap: 16px;
      }

      .video-section {
        padding: 0;
        box-shadow: none;
        border-radius: 0;
        background: transparent;
      }

      .video-section video {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 9;
        background: #000;
        border-radius: 0;
      }

      .goku h2 {
        font-size: 18px;
        line-height: 1.4;
        margin-top: 8px;
      }

      .video-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        font-size: 14px;
      }

      .video-meta .left-info,
      .video-meta .right-actions {
        flex-wrap: wrap;
        gap: 12px;
        width: 100%;
        justify-content: space-between;
      }

      #subscribe-btn {
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 6px;
      }

      .comment-box {
        font-size: 14px;
        line-height: 1.5;
        padding: 8px 0;
      }

      .suggestions {
        margin-top: 20px;
      }

      .suggestions h3 {
        font-size: 16px;
        margin-bottom: 12px;
      }

      .suggestions a {
        gap: 8px;
        margin-bottom: 14px;
      }

      .suggestions img {
        width: 110px;
        height: 62px;
        border-radius: 4px;
      }

      .suggestions .info div:first-child {
        font-size: 13px;
      }

      .suggestions .info div:last-child {
        font-size: 11px;
      }

    }
  </style>
</head>



<body>
  <?php include '../includes/header.php';  ?>
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

    <div class="video-section">
      <video
        controls
        autoplay
        muted
        playsinline
        poster="<?= htmlspecialchars($video['thumbnail']) ?>">
        <source src="<?= htmlspecialchars($video['filename']) ?>" type="video/mp4">
      </video>

      <div class="goku">
        <h2><?= htmlspecialchars($video['title']) ?></h2>
        <div class="video-meta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">

          <!-- Bên trái: Tên kênh + nút đăng ký -->
          <div style="display: flex; align-items: center; gap: 12px;">
            <!-- Avatar kênh -->
            <a href="public_channel.php?id=<?= $video['channel_id'] ?>" style="display: inline-block; width: 42px; height: 42px;">
              <img src="<?= htmlspecialchars($video['channel_avatar'] ?: '../images/default-avatar.png') ?>"

                alt="Avatar"
                style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
            </a>
            <div>
              <a href="public_channel.php?id=<?= $video['channel_id'] ?>" style="text-decoration: none; color: #222; font-weight: bold; font-size: 16px;">
                <?= htmlspecialchars($video['channel_name']) ?>
              </a>

              <button type="button" onclick="toggleSubscribe(<?= $video['channel_id'] ?>, event)"
                id="subscribe-btn"
                style="margin-left: 12px;
               padding: 6px 12px;
               font-size: 14px;
               border: none;
               border-radius: 4px;
               cursor: pointer;
               background-color: <?= $isSubscribed ? '#ccc' : '#ff0000' ?>;
               color: white;"
                data-sub="<?= $isSubscribed ? '1' : '0' ?>">
                <span id="subscribe-text"><?= $isSubscribed ? 'Đã đăng ký' : 'Đăng ký' ?></span>
                <i id="bell-icon" class="fa-solid fa-bell" style="margin-left: 6px; <?= $isSubscribed ? '' : 'display:none;' ?>"></i>
              </button>


            </div>
          </div>


          <!-- Bên phải: nút thích và xem sau -->
          <div class="right-actions" style="display: flex; gap: 16px; align-items: center;">
            <span onclick="toggleFavorite(<?= $videoId ?>)" style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
              <i id="heart-icon"
                class="fa-solid fa-heart"
                style="font-size: 20px; color: <?= $isFavorite ? 'red' : '#aaa' ?>;"
                data-fav="<?= $isFavorite ? '1' : '0' ?>"></i>
              <span style="font-size: 14px; color: #333;">Thích</span>
            </span>

            <span onclick="toggleWatchLater(<?= $videoId ?>)" style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
              <i id="watch-later-icon"
                class="fa-regular fa-clock"
                style="font-size: 20px; color: <?= $isWatchLater ? '#007bff' : '#aaa' ?>;"
                data-watchlater="<?= $isWatchLater ? '1' : '0' ?>"></i>
              <span style="font-size: 14px; color: #333;">Xem sau</span>
            </span>
          </div>
        </div>

        <!-- Dòng 2: Lượt xem và ngày đăng -->
        <div style="font-size: 14px; color: #555; margin-bottom: 10px;">
          <?= number_format($video['views'] + 1) ?> lượt xem • <?= date('d/m/Y', strtotime($video['upload_date'])) ?>
        </div>
      </div>
      <!-- Mô tả rút gọn -->
      <div id="video-description" class="collapsed">
        <?= nl2br(htmlspecialchars($video['description'] ?? '')) ?>
      </div>

      <!-- Nút mở rộng -->
      <button onclick="toggleDescription()" id="desc-toggle-btn" style="margin-top: 6px; background: none; border: none; color: #0066cc; cursor: pointer; font-weight: bold;">
        Hiển thêm
      </button>

      <hr>
      <h3>💬 Bình luận</h3>

      <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post">
          <textarea name="comment" rows="3" placeholder="Nhập bình luận..." required></textarea><br>
          <input type="hidden" name="video_id" value="<?= $videoId ?>">
          <button type="submit">Gửi</button>
        </form>
      <?php else: ?>
        <p><a href="../loginphp/login.php">Đăng nhập</a> để bình luận.</p>
      <?php endif; ?>

      <?php while ($cmt = $comments->fetch_assoc()): ?>
        <div class="comment-box">
          <strong><?= htmlspecialchars($cmt['name']) ?></strong> <em><?= date('H:i d/m/Y', strtotime($cmt['created_at'])) ?></em><br>
          <?= nl2br(htmlspecialchars($cmt['content'])) ?>
        </div>
      <?php endwhile; ?>
    </div>
    <div class="suggestions">
      <h3 style="display: flex; align-items: center; gap: 8px; font-size: 18px;">
        <i class="fas fa-film" style="color: #0066cc;"></i> Đề Xuất
      </h3>

      <?php
      $suggest = $conn->prepare("
  SELECT 
    v.id, v.title, v.thumbnail, v.views, v.upload_date,
    ch.name AS channel_name,
    ch.avatar AS channel_avatar,
    ch.id AS channel_id
  FROM videos v
  JOIN users u ON v.uploaded_by = u.id
  JOIN channels ch ON ch.user_id = u.id
  WHERE v.id != ?
  ORDER BY RAND()
  LIMIT 19
");

      $suggest->bind_param("i", $videoId);
      $suggest->execute();
      $suggestResult = $suggest->get_result();

      while ($row = $suggestResult->fetch_assoc()):
        $thumb = $row['thumbnail'] ?: 'default.jpg';
      ?>
        <a href="../main/watch.php?id=<?= $row['id'] ?>" class="suggestion-item">
          <img src="<?= htmlspecialchars($row['thumbnail'] ?? 'https://via.placeholder.com/160x90?text=No+Thumb') ?>" alt="Thumbnail">
          <div class="info">
            <div><?= htmlspecialchars($row['title']) ?></div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 12px;">
              <img src="<?= htmlspecialchars($row['channel_avatar'] ?: 'default-avatar.png') ?>"
                alt="avatar"
                style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover;">
              <?= htmlspecialchars($row['channel_name']) ?>
            </div>
            <div><?= number_format($row['views']) ?> lượt xem • <?= date('d/m/Y', strtotime($row['upload_date'])) ?></div>
          </div>
          <div class="more-menu">⋮</div>
        </a>
      <?php endwhile; ?>
    </div>
  </div>


  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('hidden');
    }

    function toggleWatchLater(videoId) {
      const clock = document.getElementById("watch-later-icon");
      const isWL = clock.dataset.watchlater === "1";

      fetch("../main/xemsau/toggle_watch_later.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: `video_id=${videoId}`
        })
        .then(res => res.text())
        .then(() => {
          clock.style.color = isWL ? "#aaa" : "#007bff";
          clock.dataset.watchlater = isWL ? "0" : "1";
        })
        .catch(err => console.error("Lỗi xem sau:", err));
    }

    function toggleFavorite(videoId) {
      const heart = document.getElementById("heart-icon");
      const isFav = heart.dataset.fav === "1";

      fetch("toggle_favorite.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: `video_id=${videoId}`
        })
        .then(res => res.json())
        .then(data => {
          heart.style.color = (data.status === "ADDED") ? "red" : "#aaa";
          heart.dataset.fav = (data.status === "ADDED") ? "1" : "0";
        })
        .catch(err => console.error("Lỗi yêu thích:", err));
    }
    // subscribeChannel
    function toggleSubscribe(channelId, event) {
      if (event) event.preventDefault();

      const btn = document.getElementById("subscribe-btn");
      const textSpan = document.getElementById("subscribe-text");
      const bell = document.getElementById("bell-icon");

      fetch("../main/channel/subscribe.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "channel_id=" + channelId
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === "SUBSCRIBED") {
            btn.dataset.sub = "1";
            btn.style.backgroundColor = "#ccc";
            textSpan.innerText = "Đã đăng ký";
            bell.style.display = "inline";
            bell.classList.add("shake");
            setTimeout(() => bell.classList.remove("shake"), 500);
          } else if (data.status === "UNSUBSCRIBED") {
            btn.dataset.sub = "0";
            btn.style.backgroundColor = "#ff0000";
            textSpan.innerText = "Đăng ký";
            bell.style.display = "none";
          } else if (data.status === "LOGIN_REQUIRED") {
            alert("Vui lòng đăng nhập để đăng ký kênh.");
            window.location.href = "../loginphp/login.php";
          }
        })
        .catch(err => console.error("Lỗi đăng ký:", err));
    }
    // ẩn hiện mô tả
    function toggleDescription() {
      const desc = document.getElementById('video-description');
      const btn = document.getElementById('desc-toggle-btn');

      if (desc.classList.contains('collapsed')) {
        desc.classList.remove('collapsed');
        desc.classList.add('expanded');
        btn.textContent = 'Ẩn bớt';
      } else {
        desc.classList.add('collapsed');
        desc.classList.remove('expanded');
        btn.textContent = 'Hiển thị thêm';
      }
    }
  </script>
  <!-- Thanh tiến trình toàn trang -->
  <div id="uploadProgressBar" style="display:none; position:fixed; bottom:20px; right:20px; background:#fff; border:1px solid #ccc; border-radius:8px; padding:10px 16px; z-index:9999; font-weight:bold;">
    🚀 Đang tải video: <span id="progressPercent">0%</span>
  </div>

  <script>
    const bar = document.getElementById('uploadProgressBar');
    const percentText = document.getElementById('progressPercent');
    const channel = new BroadcastChannel('upload-progress');

    channel.onmessage = (e) => {
      const data = e.data;
      if (!data || !data.type) return;

      if (data.type === 'upload-progress' && typeof data.progress === 'number') {
        bar.style.display = 'block';
        percentText.textContent = data.progress + '%';
      } else if (data.type === 'upload-finished') {
        percentText.textContent = '100% ✅';
        setTimeout(() => {
          bar.style.display = 'none';
          percentText.textContent = '0%';
        }, 1000);
      }
    };
  </script>
</body>
<footer>
  © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
</footer>

</html>