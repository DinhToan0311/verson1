<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trang Giới Thiệu</title>



  <!-- Font Awesome 6.6.0 Free CDN -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap");

    :root {
      --primary-color: #48a2d6;
      --secondary-color: #e0f3fb;
      --text-color: #222;
      --bg-color: #f9fbfc;
      --white: #ffffff;
      --hover-color: #2e8ccf;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Outfit", sans-serif;
      scroll-behavior: smooth;
    }

    body {
      background-color: var(--bg-color);
      color: var(--text-color);
      padding: 20px;
      min-height: 100vh;
    }

    /* ======================== Logo và Header ======================== */
    .top-header {
      background: linear-gradient(to right, var(--primary-color), #1b6ca8);
      text-align: center;
      padding: 40px 20px;
      border-radius: 20px;
      margin-bottom: 40px;
    }

    .top-logo {
      width: 120px;
      height: 120px;
      margin: 0 auto 20px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid white;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      margin-top: 10px;
    }

    .top-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }

    .top-logo img:hover {
      transform: scale(1.05);
    }

    .company-name {
      color: var(--primary-color);
      font-size: 2rem;
      font-weight: 700;
    }

    .slogan {
      font-size: 1.05rem;
      color: var(--primary-color);
      margin-top: 10px;
      font-style: italic;
    }

    /* ======================== Container chính ======================== */
    .container {
      max-width: 900px;
      margin: 0 auto;
      background-color: var(--white);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
      transition: box-shadow 0.3s ease;
    }

    .container:hover {
      box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
    }

    /* Header người dùng */
    .user-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
    }

    .user-avatar {
      background-color: var(--primary-color);
      color: var(--white);
      font-weight: 700;
      font-size: 24px;
      width: 56px;
      height: 56px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: transform 0.3s ease;
    }

    .user-avatar:hover {
      transform: scale(1.1) rotate(5deg);
    }

    .username {
      font-size: 20px;
      font-weight: 600;
      margin-left: 15px;
      color: var(--primary-color);
    }

    /* Navigation */
    .navigation {
      background-color: var(--primary-color);
      position: sticky;
      top: 0;
      z-index: 1000;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .navbar {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      list-style: none;
      padding: 10px 0;
      margin: 0;
    }

    .nav-link a {
      color: white;
      text-decoration: none;
      padding: 10px 16px;
      transition: all 0.3s ease;
      border-radius: 8px;
    }

    .nav-link a:hover,
    .nav-link.active a {
      background-color: white;
      color: var(--primary-color);
      transform: translateY(-2px);
    }

    /* Logout Button */
    .logout-btn {
      display: inline-block;
      margin-top: 30px;
      background-color: #ff4d4f;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .logout-btn:hover {
      background-color: #d9363e;
    }

    /* ======================== About Section ======================== */
    .about-wrapper {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 30px;
    }

    .about-left {
      flex: 1;
      min-width: 300px;
    }

    .about-right {
      flex: 1;
      min-width: 300px;
    }

    .about-right img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      transition: transform 0.4s ease;
    }

    .about-right img:hover {
      transform: scale(1.03);
    }

    .about-text h2 {
      font-size: 2rem;
      color: var(--primary-color);
      margin-bottom: 10px;
    }

    .about-text p {
      font-size: 1rem;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    .about-columns {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .about-box {
      background-color: var(--secondary-color);
      padding: 20px;
      border-radius: 12px;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .about-box:hover {
      background-color: var(--primary-color);
      color: white;
      transform: translateY(-5px);
    }

    .about-box h3 {
      margin-bottom: 10px;
    }

    /* ======================== Gallery ======================== */
    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .gallery img {
      width: 100%;
      border-radius: 12px;
      transition: transform 0.4s ease, box-shadow 0.3s ease;
    }

    .gallery img:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    /* ======================== Social links ======================== */
    .social-links {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 30px;
    }

    .social-item a {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--secondary-color);
      padding: 12px 20px;
      border-radius: 10px;
      text-decoration: none;
      color: var(--text-color);
      transition: all 0.3s ease;
    }

    .social-item a:hover {
      background: var(--primary-color);
      color: white;
      transform: scale(1.03);
    }

    .social-item i {
      font-size: 1.2rem;
    }

    /* ======================== Footer ======================== */
    footer {
      margin-top: 60px;
      background-color: #f1f1f1;
      padding: 40px 20px 20px;
      border-radius: 20px 20px 0 0;
    }

    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 30px;
    }

    .footer-box h3 {
      margin-bottom: 12px;
      color: var(--primary-color);
    }

    .footer-box p {
      margin-bottom: 6px;
      font-size: 14px;
    }

    .footer-bottom {
      margin-top: 30px;
      text-align: center;
      font-size: 14px;
      color: #888;
    }

    /* ======================== Responsive ======================== */
    @media (max-width: 768px) {
      .about-wrapper {
        flex-direction: column;
      }

      .footer-grid {
        grid-template-columns: 1fr;
      }

      .top-logo {
        width: 90px;
        height: 90px;
      }

      .company-name {
        font-size: 1.4rem;
      }

      .slogan {
        font-size: 0.9rem;
      }
    }

    html {
      scroll-behavior: smooth;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <header>
    <!-- Avatar hoặc nút đăng nhập -->


    <nav class="navigation episode-nav">
      <ul class="navbar">
        <li class="nav-link"><a href="index.php">Trang Chủ</a></li>
        <li class="nav-link"><a href="series.php">Nổi Bật</a></li>
        <li class="nav-link"><a href="categoria.php">Phân Loại</a></li>
        <li class="nav-link"><a href="../main/trangchu.php">MMG TuBe</a></li>
        <li class="nav-link"><a href="about.php">Khác</a></li>
      </ul>
    </nav>

    <div class="top-logo">
      <img src="img/pc/logo.png" alt="Logo Doanh Nghiệp" onerror="this.style.border='2px solid red'; alert('Ảnh không hiển thị!');" />
    </div>

    <h1 style="color: var(--primary-color); font-size: 1.8rem ;  align-items: center;">
      CÔNG TY MMG MEDIA INTERNATIONAL
    </h1>
    <p class="slogan" style=" align-items: center;">Chuyên nghiệp trong từng chi tiết – Tận tâm trong từng bước</p>
  </header>

  <!-- Thông tin chính -->
  <section class="info-section">
    <div class="social-links">
      <div class="social-item">
        <a href="https://www.facebook.com/mmgvi/?locale=vi_VN" target="_blank">
          <i class="fab fa-facebook"></i>
          <span>Facebook</span>
        </a>
      </div>
      <div class="social-item">
        <a href="http://www.youtube.com/@rainbowzmultiverse6975" target="_blank">
          <i class="fab fa-youtube"></i>
          <span>YouTube</span>
        </a>
      </div>
      <div class="social-item">
        <a href="https://www.tiktok.com" target="_blank">
          <i class="fab fa-tiktok"></i>
          <span>TikTok</span>
        </a>
      </div>
      <div class="social-item">
        <a href="https://www.google.com/maps?sca_esv=e3dd5e57b94262db&rlz=1C1ONGR_enVN1079VN1079&sxsrf=AE3TifPSJhA5TjqONuLT6mc3ppAE6KvF8g:1751019880122&si=AMgyJEvWrqMtbdpM6zU9DoVHqM7BZVYVJqG6zLTeueLph2SDZVYvit82iWA7lmLUN6ViRK7APaXmeC1xic3aBScFpvgFz5KC7BSw1rFZlKwp_FOo1R_UxEaioy1iKp549pNf9TDpukFdw-NEzq85wfQiSKrhuYjvi5o0iz3aDk4II-5LOHvZ_LfBw0pyr_pj6IoigLkSVb06&biw=1707&bih=811&dpr=1.13&um=1&ie=UTF-8&fb=1&gl=vn&sa=X&geocode=KVVVFQhleEoxMUYPM_6ksUDD&daddr=T%C3%B2a+Nh%C3%A0+MMG+%C4%90%C6%B0%E1%BB%9Dng+T%C3%ACnh+Th%E1%BB%A7y,+Ng%E1%BB%8D+D%C6%B0%C6%A1ng,+An+D%C6%B0%C6%A1ng,+H%E1%BA%A3i+Ph%C3%B2ng" target="_blank">
          <i class="fas fa-map-marker-alt"></i>
          <span>Bản đồ</span>
        </a>
      </div>
    </div>
    <section class="about-section">
      <div class="about-wrapper">
        <!-- CỘT TRÁI: Giới thiệu + 3 box -->
        <div class="about-left">
          <div class="about-text">
            <h2>Về Chúng Tôi</h2>
            <p>
              Chúng tôi là một doanh nghiệp trẻ trung, năng động với khát vọng trở
              thành đơn vị tiên phong trong lĩnh vực công nghệ và truyền thông media. Với
              nền tảng nội tại vững chắc, đội ngũ tâm huyết và sáng tạo, chúng tôi
              luôn nỗ lực mang đến các sản phẩm – dịch vụ tốt nhất cho cộng đồng.
            </p>
          </div>

          <div class="about-columns">
            <div class="about-box">
              <h3>Mục tiêu</h3>
              <p>Phát triển bền vững, trở thành sự lựa chọn hàng đầu của khách hàng.</p>
            </div>
            <div class="about-box">
              <h3>Sứ mệnh</h3>
              <p>Đem đến giải pháp sáng tạo và giá trị thực tế, lấy khách hàng làm trung tâm.</p>
            </div>
            <div class="about-box">
              <h3>Tầm nhìn</h3>
              <p>Vươn tầm khu vực và thế giới, dẫn đầu xu thế đổi mới trong chuyển đổi số.</p>
            </div>
          </div>
        </div>

        <!-- CỘT PHẢI: Ảnh chiếm toàn bộ chiều cao -->
        <div class="about-right">
          <img src="img/pc/pc7.jpg" alt="Ảnh minh họa" />
        </div>
      </div>
    </section>




    <section class="gallery-section">
      <h2>Hình ảnh</h2>
      <div class="gallery">
        <img src="img/pc/pc8.png" alt="Ảnh 1" />
        <img src="img/pc/pc6.jpg" alt="Ảnh 2" />
        <img src="img/pc/pc5.jpg" alt="Ảnh 3" />
        <img src="img/pc/pc4.jpg" alt="Ảnh 4" />
        <img src="img/pc/pc2.jpg" alt="Ảnh 4" />

      </div>

      <!-- Footer dạng hình chữ nhật mở rộng -->
      <footer>
        <div class="footer-grid">
          <div class="footer-box">
            <h3>Thông tin doanh nghiệp</h3>
            <p>Tên: CÔNG TY TNHH ABC VIỆT NAM</p>
            <p>Mã số thuế: 0201713610</p>
            <p>Người đại diện: Trần Quang Toản</p>
          </div>

          <div class="footer-box">
            <h3>Liên hệ</h3>
            <p>Hotline: 0876 666 777</p>
            <p>Email CSKH: hoanglananh@example.com</p>
            <p>Giờ làm việc: 8h - 17h (Thứ 2 - Thứ 7)</p>
          </div>

          <div class="footer-box">
            <h3>Chi nhánh / Trụ sở</h3>
            <p>Thôn Ngọ Dương 5, Xã An Hòa, Huyện An Dương, Thành phố Hải Phòng</p>
            <p>509 Võ Nguyên Giáp, Vĩnh Niệm, Lê Chân, Hải Phòng</p>
            <p>Trụ sở chính: Tòa Nhà MMG Đường Tình Thủy, Ngọ Dương, An Dương, Hải Phòng</p>
          </div>

          <div class="footer-box">
            <h3>Thông tin pháp lý</h3>
            <p>Giấy phép kinh doanh số: 0201713610</p>
            <p>Ngày cấp: 01/01/2020</p>
            <p>Nơi cấp: Sở KHĐT TP.Hải Phòng</p>
          </div>

          <div class="footer-box">
            <h3>Chính sách & Hướng dẫn</h3>
            <p>Chính sách bảo mật</p>
            <p>Hướng dẫn thanh toán</p>
            <p>khác</p>
          </div>
        </div>

        <div class="footer-bottom">
          &copy; @2025 MMG GLOBAL COMPANY LIMITED - THANKS FOR WATCHING !
        </div>
      </footer>
</body>

</html>