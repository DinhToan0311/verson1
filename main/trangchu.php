<?php
session_start();
require '../loginphp/db.php';
require_once '../includes/auth_check.php';

function formatDuration($seconds)
{
  $minutes = floor($seconds / 60);
  $secs = $seconds % 60;
  return sprintf("%02d:%02d", $minutes, $secs);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="logo.png" type="image/png">
  <title>MMGTube - Trang chủ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap&subset=vietnamese" rel="stylesheet">
  <style>
    body {
      overflow-x: auto;
      margin: 0;
      padding: 0;
      margin-left: 14%;
      font-family: 'Roboto', sans-serif;
    }

    .main {
      margin-left: 250px;
      padding: 20px;
      box-sizing: border-box;
    }

    .content {
      padding: 20px;
    }

    .video-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
    }

    .video {
      background: white;
      border-radius: 10px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s;
      overflow: hidden;
    }

    .video:hover {
      transform: translateY(-4px);
    }

    .thumbnail {
      width: 100%;
      height: 140px;
      object-fit: cover;
      border-radius: 8px;
    }

    .duration-label {
      position: absolute;
      bottom: 6px;
      right: 6px;
      background: rgba(0, 0, 0, 0.75);
      color: #fff;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 4px;
    }

    .thumbnail-wrapper {
      position: relative;
    }

    .video-content {
      display: flex;
      padding: 10px;
    }

    .channel-icon-small {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
    }

    .info h4 {
      font-size: 15px;
      margin: 0;
    }

    .info p {
      font-size: 13px;
      color: #666;
      margin: 2px 0;
    }

    .video-options {
      margin-left: auto;
      position: relative;
      display: flex;
      align-items: center;
    }

    .more-btn {
      cursor: pointer;
      font-size: 18px;
      color: #555;
    }

    .options-menu {
      position: absolute;
      top: -45px;
      right: 0;
      background: white;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 5px 10px;
      display: none;
      z-index: 1000;
    }

    .options-menu button {
      background: none;
      border: none;
      color: #333;
      font-size: 14px;
      padding: 6px 0;
      width: 100%;
      text-align: left;
      cursor: pointer;
    }

    .options-menu button:hover {
      color: #0a84ff;
    }

    .search-filter-bar {
      margin-bottom: 20px;
      position: sticky;
      top: 70px;
      background: white;
      z-index: 100;
      padding: 10px 0;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    .categories {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    .category-btn {
      background-color: #0073e6;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
    }

    .category-btn.active {
      background-color: #004ea0;
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
      .main {
        margin-left: 0;
      }

      .sidebar {
        display: none;
      }
    }
  </style>
</head>

<body>

  <!-- Header -->
  <?php include '../includes/header.php'; ?>

  <div class="main">
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



    <!-- Content -->
    <div class="content">
      <div class="search-filter-bar">
        <div class="categories">
          <button class="category-btn active" data-category="Tất cả">Tất cả</button>
          <button class="category-btn" data-category="Hành động">Hành động</button>
          <button class="category-btn" data-category="Tình cảm">Tình cảm</button>
          <button class="category-btn" data-category="Hài">Hài</button>
          <button class="category-btn" data-category="Tâm lý">Tâm lý</button>
          <button class="category-btn" data-category="Khoa học viễn tưởng">Khoa học viễn tưởng</button>
          <button class="category-btn" data-category="Học đường">Học đường</button>

        </div>
      </div>

      <div class="video-container">
        <?php
        $sql = "SELECT v.*, c.name AS channel_name, c.avatar 
                FROM videos v 
                JOIN channels c ON v.uploaded_by = c.user_id
                ORDER BY RAND()";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()):
          $thumbnail = $row['thumbnail'] ?? 'default.jpg';
          $thumbUrl = $thumbnail;
          $avatarUrl = (!empty($row['avatar']) && strpos($row['avatar'], 'http') === 0)
            ? $row['avatar']
            : '../images/default-avatar.png';


        ?>
          <div class="video" data-category="<?= htmlspecialchars($row['category']) ?>">
            <a href="watch.php?id=<?= $row['id'] ?>">
              <div class="thumbnail-wrapper">
                <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="Thumbnail" class="thumbnail">
                <span class="duration-label"><?= formatDuration((int)$row['duration']) ?></span>
              </div>
            </a>

            <div class="video-content">
              <img src="<?= $avatarUrl ?>" alt="avatar" class="channel-icon-small">
              <div class="info">
                <h4><?= htmlspecialchars($row['title']) ?></h4>
                <p><strong><?= htmlspecialchars($row['channel_name']) ?></strong></p>
                <p><?= number_format($row['views']) ?> lượt xem • <?= date('d/m/Y', strtotime($row['upload_date'])) ?></p>
              </div>

              <div class="video-options">
                <i class="fas fa-ellipsis-v more-btn" data-id="<?= $row['id'] ?>"></i>
                <div class="options-menu" id="menu-<?= $row['id'] ?>">
                  <button onclick="addToWatchLater(<?= $row['id'] ?>); event.stopPropagation();">Xem sau</button>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
  <footer>© <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!</footer>
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
          alert('Lỗi kết nối server.');
        });
    }
    // Xử lý hiển thị menu khi ấn dấu ba chấm
    document.querySelectorAll('.more-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Ngăn sự kiện click lan ra ngoài

        const menuId = "menu-" + this.dataset.id;
        const menu = document.getElementById(menuId);

        // Ẩn tất cả menu khác
        document.querySelectorAll('.options-menu').forEach(m => {
          if (m !== menu) m.style.display = 'none';
        });

        // Toggle menu hiện tại
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
      });
    });

    // Ẩn menu nếu click ra ngoài
    document.addEventListener('click', function(e) {
      document.querySelectorAll('.options-menu').forEach(m => {
        m.style.display = 'none';
      });
    });
    // --- Lọc video theo thể loại & từ khóa ---
    const categoryButtons = document.querySelectorAll(".category-btn");
    const videos = document.querySelectorAll(".video");

    let currentCategory = "Tất cả";

    categoryButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        categoryButtons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        currentCategory = btn.dataset.category;
        filterVideos();
      });
    });

    function filterVideos() {
      videos.forEach(video => {
        const category = video.dataset.category || '';
        const match = currentCategory === "Tất cả" || category === currentCategory;
        video.style.display = match ? "block" : "none";
      });
    }

    // Tự động lọc ban đầu
    window.addEventListener("DOMContentLoaded", filterVideos);
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
        percentText.textContent = '✅ Đã tải thành công!';
        setTimeout(() => {
          bar.style.display = 'none';
        }, 3000); // Ẩn sau 3 giây
      }
    };
  </script>

</body>

</html>