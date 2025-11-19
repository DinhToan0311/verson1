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
    <title>Dragon Ball Super</title>
    <link rel="stylesheet" href="../css/style.css" />

    <!-- Font Awesome 6.6.0 Free CDN -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
      integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
        <style>
      .naruto-image {
  width: 50%;
  height: 100%;
  background: url(../img/dragonball/3.jpg) no-repeat center;
  background-size: cover;
  z-index: 9999;
}
    </style>
  </head>
  <body>
    <div class="container">
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
            <a href="../loginphp/login.php" style="
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
      <div class="naruto-image">
        <div class="logo">
          <img src="../img/logo.png" alt="" />
        </div>
      </div>

      <div class="content">
        <img src="../img/dragonball/3.jpg" alt="" class="cover" />

         <nav class="navigation">
          <ul class="navbar">
            <li class="nav-link active"><a href="../index.php">Trang Chủ</a></li>
            <li class="nav-link"><a href="../series.php">Nổi Bật</a></li>
            <li class="nav-link"><a href="../categoria.php">Phân Loại</a></li>
            <li class="nav-link"><a href="../main/trangchu.php">MMG TuBe</a></li>
            <li class="nav-link"><a href="../about.php">Khác</a></li>
          </ul>
        </nav>

        <div class="about">
          <div class="logo">MMG Global</div>
          <div class="title">Action Movies</div>
          <div class="topic">Dragon Ball Super</div>
          <div class="des">
            Dragon Ball là bộ truyện nổi tiếng và phổ biến rộng rãi bậc nhất trên toàn thế giới, là một trong 
            những bộ manga được tiêu thụ nhiều nhất mọi thời đại. Nó được bán ở hơn 40 quốc gia và phiên bản anime
             cũng được phát sóng ở hơn 80 quốc gia.
          </div>
          <div class="imdb">
            <i class="fa-solid fa-star"></i>
            <span>10</span>/10
          </div>

          <div class="buttons">
            <?php if ($loggedIn): ?>
              <a href="../fiml/watch_series.php?id=27" class="action-btn">Xem ngay</a>
            <?php else: ?>
              <a href="#" class="action-btn" onclick="Swal.fire('Thông báo', 'Bạn cần đăng nhập để xem!', 'warning'); return false;">Xem ngay</a>
            <?php endif; ?>
            <a href="about.php" class="action-btn">More</a>
          </div>
        </div>

        <div class="cards">
          <a href="narutogt.php" class="card">
            <div class="image">
              <img src="../img/naruto/1.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Naruto</div>
              </div>
            </div>
          </a>
          <a href="onepigt.php" class="card">
            <div class="image">
              <img src="../img/onepi/2.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Onepi</div>
              </div>
            </div>
          </a>
          <a href="doraemongt.php" class="card">
            <div class="image">
              <img src="../img/doraemon/2.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Doraemon</div>
              </div>
            </div>
          </a>
          <a href="seriesgt.php" class="card">
            <div class="image">
              <img src="../img/SLIME/3.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Chuyển Sinh Thành Slime</div>
              </div>
            </div>
          </a>

          <a href="atackontitangt.php" class="card">
            <div class="image">
              <img src="../img/attackontitan/1.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Attack on Titan</div>
              </div>
            </div>
          </a>

          <a href="pokemongt.php" class="card">
            <div class="image">
              <img src="../img/pokemon/1.jpg" alt="" />
              <div class="epi-content">
                <div class="title">PoKeMon</div>
              </div>
            </div>
          </a>

          <a href="thanhguomdietquygt.php" class="card">
            <div class="image">
              <img src="../img/thanhguomdietquy/1.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Thanh Gươm Diệt Quỷ</div>
              </div>
            </div>
          </a>

          <a href="marukogt.php" class="card">
            <div class="image">
              <img src="../img/maruko/3.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Cô bé Maruko</div>
              </div>
            </div>
          </a>
           <a href="dragonballgt.php" class="card">
            <div class="image">
              <img src="../img/dragonball/3.jpg" alt="" />
              <div class="epi-content">
                <div class="title">Dragonball Super</div>
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
</html>
