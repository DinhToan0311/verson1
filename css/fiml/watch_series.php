<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
require '../loginphp/db.php';

// Lấy series ID từ URL
$series_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($series_id <= 0) {
  die("ID phim không hợp lệ.");
}

// Lấy thông tin phim
$stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
$stmt->bind_param("i", $series_id);
$stmt->execute();
$series = $stmt->get_result()->fetch_assoc();

if (!$series) {
  die("Không tìm thấy phim.");
}

// Lấy danh sách tập
$stmt = $conn->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $series_id);
$stmt->execute();
$episodes = $stmt->get_result();

// Lấy tập đầu tiên
$firstEpisode = $episodes->fetch_assoc();
$episodeId = $firstEpisode ? (int)$firstEpisode['id'] : 0;
$comments = false;

// Xử lý gửi bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && $episodeId > 0) {
  $comment = trim($_POST['comment']);
  $userId = $_SESSION['user']['id'];

  if ($comment !== '') {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, episode_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $episodeId, $comment);
    $stmt->execute();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  }
}

// Lấy bình luận của tập hiện tại
if ($episodeId > 0) {
  $stmt = $conn->prepare("
        SELECT c.content, c.created_at, u.name
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.episode_id = ?
        ORDER BY c.created_at DESC
    ");
  $stmt->bind_param("i", $episodeId);
  $stmt->execute();
  $comments = $stmt->get_result();
}
// Không redirect nữa:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && $episodeId > 0) {
  $comment = trim($_POST['comment']);
  $userId = $_SESSION['user']['id'];

  if ($comment !== '') {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, episode_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $episodeId, $comment);
    $stmt->execute();
    exit; // thoát ngay
  }
}
$sameGenre = [];

if (!empty($series['genre'])) {
  $genre = $series['genre'];
  $stmt = $conn->prepare("SELECT * FROM series WHERE genre = ? AND id != ? LIMIT 6");
  $stmt->bind_param("si", $genre, $series_id);
  $stmt->execute();
  $sameGenre = $stmt->get_result();
}

if ($sameGenre->num_rows === 0) {
  // Fallback nếu không có phim cùng thể loại
  $stmt = $conn->prepare("SELECT * FROM series WHERE id != ? ORDER BY rating DESC LIMIT 6");
  $stmt->bind_param("i", $series_id);
  $stmt->execute();
  $sameGenre = $stmt->get_result();
}

$id_series = intval($_GET['id']);
mysqli_query($conn, "INSERT INTO views_log (series_id) VALUES ($id_series)");


?>


<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($series['title']) ?> | Xem Phim</title>

  <link rel="stylesheet" href="../css/ranbow.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="icon" href="../logo.png" type="image/png">
  <style>
    .episode-btn {
      margin: 5px;
      padding: 6px 12px;
      cursor: pointer;
      border-radius: 6px;
    }

    .episode-btn:hover {
      background: #eee;
    }

    .episode-box {
      padding: 6px 12px;
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      border: 1px solid #ccc;
      cursor: pointer;
      min-width: 60px;
      text-align: center;
      transition: 0.2s;
      white-space: nowrap;
    }

    .episode-box:hover {
      background-color: #f1f1f1;
      transform: translateY(-1px);
    }

    .episode-box.active {
      border: 2px solid #007bff;
      font-weight: bold;
      color: #007bff;
    }

    .video-player video {
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .comment-section textarea {
      width: 100%;
      height: 100px;
      padding: 8px;
      margin-top: 10px;
    }

    .poster-image {
      max-width: 100%;
      height: auto;
      object-fit: contain;
      /* hoặc 'cover' nếu bạn muốn vừa khít */
      image-rendering: auto;
      /* quan trọng: tránh ảnh bị vỡ */
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      display: block;
    }

    .suggest-section .suggest-item {
      width: 180px;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
    }

    .suggest-section .suggest-item:hover {
      transform: translateY(-6px) scale(1.03);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    }

    .suggest-section .suggest-item img {
      border-radius: 8px;
      width: 100%;
      transition: transform 0.3s ease;
    }

    .suggest-section .suggest-item:hover img {
      transform: scale(1.05);
    }

    .suggest-section .suggest-title {
      margin-top: 8px;
      font-weight: 500;
      color: #333;
      font-size: 15px;
      transition: color 0.3s ease;
    }

    .suggest-section .suggest-item:hover .suggest-title {
      color: #0077ff;
    }
  </style>
  <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
  <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/videojs-contrib-hls@latest/dist/videojs-contrib-hls.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/videojs-http-source-selector@1.1.6/dist/videojs-http-source-selector.min.js"></script>

</head>

<body>
  <nav class="navigation episode-nav">
    <div class="logo">
      <img src="../img/logo.png" alt="Logo">
    </div>
    <ul class="navbar">
      <li class="nav-link"><a href="../index.php">Trang Chủ</a></li>
      <li class="nav-link"><a href="../series.php">Nổi Bật</a></li>
      <li class="nav-link"><a href="../categoria.php">Phân Loại</a></li>
      <li class="nav-link"><a href="../main/trangchu.php">MMG TuBe</a></li>
      <li class="nav-link"><a href="../about.php">Khác</a></li>
    </ul>
  </nav>

  <main class="anime-container">
    <!-- THÔNG TIN PHIM -->
    <section class="top-section">
      <div class="poster-container">
        <img src="../<?= htmlspecialchars($series['poster_url']) ?>" alt="Poster" class="poster-image">


      </div>
      <div class="info">
        <h1 class="title"><?= htmlspecialchars($series['title']) ?></h1>
        <p class="description"><?= nl2br(htmlspecialchars($series['description'])) ?></p>
        <div class="metadata">
          <p><strong>Tình Trạng:</strong> <?= htmlspecialchars($series['status']) ?></p>
          <p><strong>Mùa:</strong> <?= htmlspecialchars($series['season']) ?></p>
          <p><strong>Số Tập:</strong> <?= htmlspecialchars($series['total_episodes']) ?></p>
          <p><strong>Thể loại:</strong> <?= htmlspecialchars($series['genre']) ?></p>
          <p><strong>Thời lượng:</strong> <?= htmlspecialchars($series['duration']) ?></p>
          <p><strong>Đánh Giá: </strong> <?= htmlspecialchars($series['rating']) ?><strong>⭐️</strong></p>
        </div>
      </div>
    </section>

    <!-- VIDEO PLAYER -->
    <section class="video-player" style="margin-bottom: 20px;">
      <?php if ($firstEpisode):
        // Cập nhật lượt xem
        $video_id = $firstEpisode['id'];
        $conn->query("UPDATE episodes SET views = views + 1 WHERE id = $video_id");

        // Lưu vào lịch sử xem
        $userId = $_SESSION['user']['id'];
        $stmt = $conn->prepare("INSERT INTO watch_history (user_id, video_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $video_id);
        $stmt->execute();

        // Lấy lượt xem
        $viewCount = 0;
        $stmt = $conn->prepare("SELECT views FROM episodes WHERE id = ?");
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (is_array($row) && isset($row['views'])) {
          $viewCount = $row['views'];
        }
      ?>
        <!-- VIDEO -->
        <video controls
          autoplay
          muted
          playsinline style="max-width: 100%;" src="<?= $firstEpisode['video_path'] ?>"></video>

        <!-- LƯỢT XEM GÓC TRÁI -->
        <div style="text-align: left; padding-left: 5px; color: #333; font-size: 14px;margin-left: 22%">
          👁️ <strong><?= $viewCount ?></strong> lượt xem
        </div>
      <?php else: ?>
        <p>⚠️ Chưa có tập phim nào.</p>
      <?php endif; ?>
    </section>

    <!-- DS Tập -->
    <h3>Danh sách tập</h3>
    <ul id="episodeList" style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 8px;">
      <?php foreach ($episodes as $index => $episode): ?>
        <li
          class="episode-box <?= $index === 0 ? 'active' : '' ?>"
          onclick="changeEpisode('<?= $episode['video_path'] ?>', event)">
          <?= htmlspecialchars($episode['title']) ?>
        </li>
      <?php endforeach; ?>
    </ul>


    <!-- BÌNH LUẬN -->
    <section class="comments comment-section">
      <h2>Bình luận</h2>
      <form id="commentForm">
        <textarea name="comment" placeholder="Viết bình luận..." required></textarea>
        <button type="submit">Gửi</button>
      </form>


      <div id="commentList">
        <?php if ($comments instanceof mysqli_result): ?>
          <?php while ($row = $comments->fetch_assoc()): ?>
            <?php if (is_array($row)): ?>
              <div style="margin-top: 10px; border-bottom: 1px solid #ccc;">
                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                <small><?= htmlspecialchars($row['created_at']) ?></small>
                <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
              </div>
            <?php endif; ?>
          <?php endwhile; ?>
        <?php else: ?>
          <p>Chưa có bình luận nào.</p>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($sameGenre && $sameGenre->num_rows > 0): ?>
      <section class="suggest-section" style="margin-top: 40px;">
        <h2>🎬 Gợi ý cho bạn</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
          <?php while ($s = $sameGenre->fetch_assoc()): ?>
            <div class="suggest-item">
              <a href="../fiml/watch_series.php?id=<?= $s['id'] ?>" style="text-decoration: none; color: inherit;">
                <img src="../<?= htmlspecialchars($s['poster_url']) ?>" alt="<?= htmlspecialchars($s['title']) ?>">
                <p class="suggest-title"><?= htmlspecialchars($s['title']) ?></p>
              </a>
            </div>
          <?php endwhile; ?>

        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="footer">
    <div class="footer-container">
      <div class="footer-logo">MMG Global</div>
      <p>&copy; 2025 MMG Global. Thanks For Watching.</p>
    </div>
  </footer>

  <script>
    const video = document.querySelector("video");

    // Đăng ký sự kiện gửi bình luận 1 lần duy nhất
    document.getElementById('commentForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const form = e.target;
      const comment = form.comment.value.trim();
      if (comment === '') return;

      fetch('', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            comment
          })
        })
        .then(res => res.text())
        .then(() => {
          const now = new Date().toLocaleString();
          const commentHtml = `
        <div style="margin-top: 10px; border-bottom: 1px solid #ccc;">
          <strong>Bạn</strong><br>
          <small>${now}</small>
          <p>${comment.replace(/\n/g, '<br>')}</p>
        </div>
      `;
          document.getElementById('commentList').insertAdjacentHTML('afterbegin', commentHtml);
          form.reset();
        });
    });

    function changeEpisode(src) {
      video.src = src;
      video.load();
      video.play();

      // Đổi trạng thái active
      document.querySelectorAll('#episodeList li').forEach(li => li.classList.remove('active'));
      event.target.classList.add('active');
    }


    function changeEpisode(src, event) {
      video.src = src;
      video.load();
      video.play();

      document.querySelectorAll('#episodeList li').forEach(li => li.classList.remove('active'));
      event.target.classList.add('active');
    }
  </script>


</body>

</html>