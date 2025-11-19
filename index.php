<?php
ini_set('session.cookie_lifetime', 0);
session_start();

// Kiểm tra đăng nhập
$loggedIn = isset($_SESSION['user']) && isset($_SESSION['user']['name']);
$userName = $loggedIn ? $_SESSION['user']['name'] : '';
$firstChar = $loggedIn ? strtoupper(mb_substr($userName, 0, 1)) : '';

// Thông báo xóa tài khoản
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
  echo "<script>alert('Tài khoản đã được xóa thành công!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trang chủ</title>
  <link rel="icon" href="logo.png" type="image/png">
  <link rel="stylesheet" href="css/mobilegt.css" media="only screen and (max-width: 768px)">
  <link rel="stylesheet" href="css/destopgt.css" media="only screen and (min-width: 769px)">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"> <!-- chỉ hiện theo hướng dọc --

  Font Awesome 6.6.0 Free CDN -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <style>
    .swal2-container {
      z-index: 99999 !important;
    }
  </style>
</head>

<body>
  <div style="position: fixed; top: 20px; right: 30px; z-index: 999;">
    <?php if ($loggedIn): ?>
      <div title="Xin chào <?= htmlspecialchars($userName) ?>" style="
      width: 40px;
      height: 40px;
      background-color: #3498db;
      color: white;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      font-size: 18px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      cursor: default;
    ">
        <?= $firstChar ?>
      </div>
    <?php else: ?>
      <a href="loginphp/login.php" title="Đăng nhập" style="
      width: 40px;
      height: 40px;
      background-color: #e74c3c;
      color: white;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      font-size: 16px;
      text-decoration: none;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    ">
        <i class="fa-solid fa-user"></i>
      </a>
    <?php endif; ?>
  </div>

  <div class="container">
    <!-- Avatar hoặc nút đăng nhập -->

    <div class="image">
      <div class="logo">
        <a href="../quanly/admin_dashboard.php">
          <img src="img/logo.png" alt="Trang chủ" />
        </a>
      </div>
    </div>

    <div class="content">
      <img src="img/rainbow/main.jpg" alt="" class="cover" />

      <nav class="navigation">
        <ul class="navbar">
          <li class="nav-link active"><a href="index.php">Trang Chủ</a></li>
          <li class="nav-link"><a href="series.php">Nổi Bật</a></li>
          <li class="nav-link"><a href="categoria.php">Phân Loại</a></li>
          <li class="nav-link"><a href="main/trangchu.php">MMG TuBe</a></li>
          <li class="nav-link"><a href="about.php">Khác</a></li>
        </ul>
      </nav>

      <div class="about">
        <div class="logo">MMG Global</div>
        <div class="title">Cartoon Movies</div>
        <div class="topic">RanBow-Z</div>
        <div class="des">
          Rainbow‑Z là kênh hoạt hình Gacha kể lại những câu chuyện học đường,
          tình bạn và cảm xúc tuổi mới lớn. Mỗi video đều mang màu sắc nhẹ
          nhàng, dễ thương và gần gũi với lứa tuổi thanh thiếu niên.
        </div>
        <div class="imdb">
          <i class="fa-solid fa-star"></i>
          <span>8.4</span>/10
        </div>
        <div class="buttons">
          <?php if ($loggedIn): ?>
            <a href="../fiml/watch_series.php?id=20" class="action-btn">Xem ngay</a>
          <?php else: ?>
            <a href="#" class="action-btn" onclick="Swal.fire('Thông báo', 'Bạn cần đăng nhập để xem!', 'warning'); return false;">Xem ngay</a>
          <?php endif; ?>
          <a href="about.php" class="action-btn">More</a>
        </div>
      </div>

      <div class="cards">
        <a href="../giaodien/dragonballgt.php" class="card">
          <div class="image">
            <img src="img/dragonball/3.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Dragonball Super</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/narutogt.php" class="card">
          <div class="image">
            <img src="img/naruto/1.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Naruto</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/onepigt.php" class="card">
          <div class="image">
            <img src="img/onepi/2.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Onepi</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/doraemongt.php" class="card">
          <div class="image">
            <img src="img/doraemon/2.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Doraemon</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/series.php" class="card">
          <div class="image">
            <img src="img/SLIME/3.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Chuyển Sinh Thành Slime</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/atackontitangt.php" class="card">
          <div class="image">
            <img src="../login/img/attackontitan/1.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Attack on Titan</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/pokemongt.php" class="card">
          <div class="image">
            <img src="../login/img/pokemon/1.jpg" alt="" />
            <div class="epi-content">
              <div class="title">PoKeMon</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/thanhguomdietquygt.php" class="card">
          <div class="image">
            <img src="../login/img/thanhguomdietquy/1.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Thanh Gươm Diệt Quỷ</div>
            </div>
          </div>
        </a>

        <a href="../giaodien/marukogt.php" class="card">
          <div class="image">
            <img src="../img/maruko/3.jpg" alt="" />
            <div class="epi-content">
              <div class="title">Cô bé Maruko</div>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</body>
<script>
  const slider = document.querySelector(".cards");
  let isDown = false;
  let startX;
  let scrollLeft;

  slider.addEventListener("mousedown", (e) => {
    isDown = true;
    slider.classList.add("active");
    startX = e.pageX - slider.offsetLeft;
    scrollLeft = slider.scrollLeft;
  });

  slider.addEventListener("mouseleave", () => {
    isDown = false;
    slider.classList.remove("active");
  });

  slider.addEventListener("mouseup", () => {
    isDown = false;
    slider.classList.remove("active");
  });

  slider.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - slider.offsetLeft;
    const walk = (x - startX) * 2; // tốc độ kéo
    slider.scrollLeft = scrollLeft - walk;
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
  // chặn video và gửi thông báo
</script>

</html>