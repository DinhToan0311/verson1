<?php
session_start();
$loggedIn = isset($_SESSION['user']) && isset($_SESSION['user']['name']);
$firstChar = $loggedIn ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : '';
require 'loginphp/db.php';

$currentMonth = date('Y-m');

$resultPopularMonth = mysqli_query($conn, "
  SELECT s.*, COUNT(v.id) AS month_views
  FROM series s
  JOIN views_log v ON s.id = v.series_id
  WHERE DATE_FORMAT(v.viewed_at, '%Y-%m') = '$currentMonth'
  GROUP BY s.id
  ORDER BY month_views DESC
  LIMIT 10
");


if (!$resultPopularMonth) {
  echo "❌ SQL Error: " . mysqli_error($conn);
}


$monthlyPopular = mysqli_fetch_all($resultPopularMonth, MYSQLI_ASSOC);

$result1 = mysqli_query($conn, "SELECT * FROM series");
$seriesList = mysqli_fetch_all($result1, MYSQLI_ASSOC);


$result2 = mysqli_query($conn, "SELECT * FROM series WHERE title LIKE '%Shin%' ORDER BY id DESC LIMIT 10");
$shinList = mysqli_fetch_all($result2, MYSQLI_ASSOC);

$result3 = mysqli_query($conn, "SELECT * FROM series WHERE title LIKE '%dragon%' OR title LIKE '%ball%' ORDER BY id DESC LIMIT 12");
$kidsList = mysqli_fetch_all($result3, MYSQLI_ASSOC);

$result4 = mysqli_query($conn, "SELECT * FROM series ORDER BY views DESC LIMIT 10");
$trendingList = mysqli_fetch_all($result4, MYSQLI_ASSOC);

$result5 = mysqli_query($conn, "SELECT * FROM series WHERE genre LIKE '%adventure%' ORDER BY RAND() LIMIT 10");
$recommendList = mysqli_fetch_all($result5, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Categoria | Danh sách phim</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    :root {
      --primary: #0d47a1;
      --accent: #1976d2;
      --light-bg: #f5f7fa;
      --card-bg: #ffffff;
      --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      --text-dark: #1a1a1a;
      --text-gray: #666;
      --border-radius: 12px;
      --transition: all 0.3s ease;
    }

    body {
      margin: 0;
      font-family: "Segoe UI", Roboto, sans-serif;
      background-color: var(--light-bg);
      color: var(--text-dark);
    }

    header {
      background: var(--primary);
      padding: 14px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--shadow);
      color: #fff;
    }

    .logo {
      font-size: 26px;
      font-weight: bold;
      letter-spacing: 1px;
    }

    nav a {
      color: #fff;
      margin-left: 24px;
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
    }

    nav a:hover {
      text-decoration: underline;
    }

    .search-bar {
      margin: 30px 40px 0;
      text-align: center;
    }

    #searchInput {
      width: 100%;
      max-width: 450px;
      padding: 12px 18px;
      border-radius: var(--border-radius);
      border: 1px solid #ccc;
      font-size: 16px;
      transition: var(--transition);
      outline: none;
    }

    #searchInput:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .section-title {
      font-size: 22px;
      font-weight: 600;
      margin: 30px 40px 10px;
      color: var(--primary);
    }

    .swiper {
      padding: 20px 40px;
    }

    .swiper-slide {
      width: 190px;
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      position: relative;
      transition: var(--transition);
    }

    .swiper-slide:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .swiper-slide img {
      width: 100%;
      height: 260px;
      object-fit: cover;
      display: block;
      transition: 0.3s ease-in-out;
    }

    .swiper-slide:hover img {
      filter: brightness(0.4);
    }

    .play-overlay {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 42px;
      color: white;
      display: none;
      z-index: 10;
    }

    .swiper-slide:hover .play-overlay {
      display: block;
    }

    .title {
      font-size: 15px;
      font-weight: 600;
      padding: 8px 10px 0;
      color: var(--primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .meta {
      font-size: 13px;
      padding: 0 10px 10px;
      color: var(--text-gray);
    }

    .tag {
      position: absolute;
      top: 10px;
      left: 10px;
      background: crimson;
      padding: 4px 8px;
      font-size: 12px;
      border-radius: 8px;
      font-weight: bold;
      color: white;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }

    .swiper-button-next,
    .swiper-button-prev {
      color: var(--accent);
      transition: var(--transition);
    }

    .swiper-button-next:hover,
    .swiper-button-prev:hover {
      color: #0d47a1;
    }

    /* Chatbot button */
    #chatbot-button {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 999;
      cursor: pointer;
    }

    #chatbot-button img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      box-shadow: var(--shadow);
      transition: transform 0.2s;
    }

    #chatbot-button img:hover {
      transform: scale(1.05);
    }

    /* Chatbox */
    #chatbox-container {
      position: fixed;
      bottom: 90px;
      right: 20px;
      width: 350px;
      height: 500px;
      border: 1px solid #ddd;
      background: #fff;
      z-index: 1000;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow);
      display: none;
    }

    #chatbox-container iframe {
      width: 100%;
      height: 100%;
      border: none;
    }

    /* Smooth scroll toàn trang */
    html {
      scroll-behavior: smooth;
    }

    /* Thanh cuộn tùy chỉnh (Chrome/Edge) */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-thumb {
      background: #90caf9;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #64b5f6;
    }

    ::-webkit-scrollbar-track {
      background: #e3f2fd;
    }

    /* Hover mượt hơn cho thẻ phim */
    .swiper-slide:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .play-overlay {
      opacity: 0;
      transition: all 0.3s ease-in-out;
    }

    .swiper-slide:hover .play-overlay {
      opacity: 1;
      transform: translate(-50%, -50%) scale(1.1);
    }

    /* Responsive Mobile */
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        align-items: flex-start;
        padding: 12px 20px;
      }

      nav {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
      }

      .section-title {
        font-size: 20px;
        margin: 20px 20px 10px;
      }

      .search-bar {
        margin: 20px;
      }

      .swiper {
        padding: 10px 20px;
      }

      .swiper-slide {
        width: 150px;
      }

      .swiper-slide img {
        height: 200px;
      }

      #chatbot-button {
        bottom: 15px;
        right: 15px;
      }

      #chatbot-button img {
        width: 50px;
        height: 50px;
      }

      #chatbox-container {
        width: 90%;
        height: 400px;
        right: 5%;
        bottom: 80px;
      }

      #searchInput {
        max-width: 100%;
      }
    }

    nav a {
      position: relative;
    }

    nav a::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -4px;
      width: 0;
      height: 2px;
      background-color: #fff;
      transition: width 0.3s;
    }

    nav a:hover::after {
      width: 100%;
    }
  </style>
</head>

<body>
  <header>
    <div class="logo">Rainbow-Z</div>
    <nav>
      <a href="index.php">Trang Chủ</a>
      <a href="series.php">Nổi Bật</a>
      <a href="categoria.php">Phân Loại</a>
      <a href="../main/trangchu.php">MMG TuBe</a>
      <a href="about.php">Khác</a>
    </nav>
  </header>
  <?php include 'ai-widget.php'; ?>
  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Tìm kiếm phim...">
  </div>
  <?php
  $sections = [
    ["title" => "Top Trending", "data" => $trendingList, "swiper" => "trending"],
    ["title" => "Xem nhiều nhất tháng này", "data" => $monthlyPopular, "swiper" => "popular"],
    ["title" => "Tuyển tập Shin", "data" => $shinList, "swiper" => "shin"],
    ["title" => "Dragon Ball", "data" => $kidsList, "swiper" => "dragon"],
    ["title" => "Gợi ý cho bạn", "data" => $recommendList, "swiper" => "suggest"],
  ];
  foreach ($sections as $sec): ?>
    <h2 class="section-title"><?= $sec['title'] ?></h2>
    <div class="swiper mySwiper<?= $sec['swiper'] ?>">
      <div class="swiper-wrapper">
        <?php foreach ($sec['data'] as $s): ?>
          <div class="swiper-slide" data-title="<?= strtolower($s['title']) ?>">
            <a href="fiml/watch_series.php?id=<?= $s['id'] ?>">
              <img src="<?= htmlspecialchars($s['poster_url']) ?>" alt="<?= htmlspecialchars($s['title']) ?>">
              <div class="play-overlay"><i class="fa fa-play-circle"></i></div>
              <div class="tag">Mới</div>
              <div class="title"><?= htmlspecialchars($s['title']) ?></div>
              <div class="meta"><?= $s['genre'] ?? 'Chưa rõ' ?> | <?= $s['duration'] ?? '?? phút' ?></div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
    </div>
  <?php endforeach; ?>

  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <script>
    const swipers = ["trending", "popular", "shin", "dragon", "suggest"];
    swipers.forEach(cls => {
      new Swiper(`.mySwiper${cls}`, {
        slidesPerView: 'auto',
        spaceBetween: 20,
        navigation: {
          nextEl: `.mySwiper${cls} .swiper-button-next`,
          prevEl: `.mySwiper${cls} .swiper-button-prev`
        },
        freeMode: true,
        grabCursor: true
      });
    });
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', () => {
      const keyword = searchInput.value.toLowerCase();
      document.querySelectorAll('.swiper-slide').forEach(slide => {
        const title = slide.dataset.title;
        slide.style.display = title.includes(keyword) ? 'block' : 'none';
      });
    });
    // Chat is handled in floating dialog below
  </script>

  <!-- Nút bật chatbot -->
  <div id="chatbot-button" onclick="toggleChatbox()">
    <img src="chatbot-icon.png" alt="Chatbot" />
  </div>

  <!-- Khung chat -->
  <div id="chatbox-container">
    <iframe src="chatbot.php" frameborder="0"></iframe>
  </div>

  <script>
    function toggleChatbox() {
      const chatbox = document.getElementById("chatbox-container");
      chatbox.style.display = (chatbox.style.display === "none" || chatbox.style.display === "") ? "block" : "none";
    }

    // Nhận yêu cầu mở URL từ chatbot (iframe)
    window.addEventListener('message', function(e) {
      try {
        const data = e.data || {};
        if (data.action === 'open_url' && data.url) {
          window.location.href = data.url;
        }
      } catch (_) {}
    });
  </script>

</body>

</html>