<?php
session_start();
$loggedIn = isset($_SESSION['user']) && isset($_SESSION['user']['name']);
$firstChar = $loggedIn ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Series</title>
  <link rel="stylesheet" href="css/mobilegt.css" media="only screen and (max-width: 768px)">
  <link rel="stylesheet" href="css/destopgt.css" media="only screen and (min-width: 769px)">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><!-- cấm xuay màn hình

   Font Awesome 6.6.0 Free CDN -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .swal2-container {
      z-index: 99999 !important;
    }

    .login-alert {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background-color: #e74c3c;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: bold;
      z-index: 9999;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      animation: fadeInOut 2.5s ease forwards;
    }

    @keyframes fadeInOut {
      0% {
        opacity: 0;
        top: 10px;
      }

      10% {
        opacity: 1;
        top: 20px;
      }

      90% {
        opacity: 1;
        top: 20px;
      }

      100% {
        opacity: 0;
        top: 10px;
      }
    }

    /* thông báo login*/
  </style>
</head>

<body>
  <div class="container">
    <!-- Avatar hoặc nút đăng nhập -->
    <div style="position: fixed; top: 20px; right: 30px; z-index: 999;">
      <?php if ($loggedIn): ?>
        <!-- Đã đăng nhập: hiện avatar -->
        <div style="
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
              cursor: pointer;
            " title="Xin chào <?= $_SESSION['user'] ?>">
          <?= $firstChar ?>
        </div>
      <?php else: ?>
        <!-- Chưa đăng nhập: hiện nút login -->
        <a href="loginphp/login.php" style="
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
        " title="Đăng nhập">
          <i class="fa-solid fa-user"></i>
        </a>

      <?php endif; ?>
    </div>
    <div class="image">
      <div class="logo">
        <a href="../quanly/admin_dashboard.php">
          <img src="img/logo.png" alt="Trang chủ" />
        </a>
      </div>
    </div>
    <div class="content">
      <img src="img/SLIME/1.jpg" alt="" class="cover" />

      <nav class="navigation">
        <ul class="navbar">
          <li class="nav-link"><a href="index.php">Trang Chủ</a></li>
          <li class="nav-link active"><a href="series.php">Nổi Bật</a></li>
          <li class="nav-link"><a href="categoria.php">Phân Loại</a></li>
          <li class="nav-link"><a href="../main/trangchu.php">MMG TuBe</a></li>
          <li class="nav-link"><a href="about.php">Khác</a></li>
        </ul>
      </nav>

      <div class="about">
        <div class="logo">MMG Global</div>
        <div class="title">Series</div>
        <div class="topic">Chuyển Sinh Thành Slime</div>
        <div class="des">
          Câu chuyện bắt đầu với anh chàng Mikami Satoru, một nhân viên 37
          tuổi sống cuộc sống chán chường và không vui vẻ gì. Trong một lần
          gặp cướp, anh đã bị mất mạng. Tưởng chừng cuộc sống chán ngắt ấy đã
          kết thúc...
        </div>
        <div class="imdb">
          <i class="fa-solid fa-star"></i>
          <span>10</span>/10
        </div>

        <div class="buttons">
          <?php if ($loggedIn): ?>
            <a href="../fiml/watch_series.php?id=14" class="action-btn">Xem ngay</a>
          <?php else: ?>
            <a href="#" class="action-btn" onclick="Swal.fire('Thông báo', 'Bạn cần đăng nhập để xem!', 'warning'); return false;">Xem ngay</a>
          <?php endif; ?>

          <a href="about.php" class="action-btn">More</a>
        </div>
      </div>

      <div class="cards">
        <?php
        $episodes = [
          ['1.mp4', '1.jpg', 'Es 1'],
          ['2.mp4', '7.jpg', 'Es 2'],
          ['3.mp4', '8.jpg', 'Es 3'],
          ['4.mp4', '9.jpg', 'Es 4'],
          ['5.mp4', '2.jpg', 'Es 5'],
          ['6.mp4', '3.jpg', 'Es 6'],
          ['7.mp4', '4.jpg', 'Es 7'],
          ['8.mp4', '5.jpg', 'Es 8'],
          ['9.mp4', '6.jpg', 'Es 9'],
          ['1.mp4', '10.jpg', 'Es 10'],
          ['2.mp4', '11.jpg', 'Es 11'],
          ['3.mp4', '12.jpg', 'Es 12'],
          ['4.mp4', '1.jpg', 'Es 13'],
        ];

        foreach ($episodes as $ep) {
          [$video, $img, $title] = $ep;
          $href = $loggedIn ? "video/slime/$video" : "#";
          $onclick = $loggedIn ? "" : "onclick='showLoginAlert(); return false;'";
          echo "
        <a href='$href' class='card' $onclick>
          <div class='image'>
            <img src='img/SLIME/$img' alt='' />
            <div class='epi-content'>
              <div class='title'>$title</div>
            </div>
          </div>
        </a>
      ";
        }
        ?>
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

  // chặn ko cho ấn vào video
  function showLoginAlert() {
    const oldAlert = document.querySelector('.login-alert');
    if (oldAlert) oldAlert.remove();

    const alertBox = document.createElement('div');
    alertBox.className = 'login-alert';
    alertBox.innerText = '⚠️ Bạn cần đăng nhập để xem video!';
    document.body.appendChild(alertBox);

    setTimeout(() => alertBox.remove(), 2500);
  }
</script>

</html>