<?php
session_start();
require '../loginphp/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = $_POST['title'];
  $description = $_POST['description'] ?? '';
  $status = $_POST['status'] ?? '';
  $season = $_POST['season'] ?? '';
  $total_episodes = $_POST['total_episodes'] ?? 0;
  $genre = $_POST['genre'] ?? '';
  $duration = $_POST['duration'] ?? '';
  $rating = $_POST['rating'] ?? 0;

  // Xử lý ảnh poster
  $poster_path = '';
  if (isset($_FILES['poster']) && $_FILES['poster']['error'] === 0) {
    $uploadDir = '../img/series/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }
    $filename = time() . '_' . basename($_FILES['poster']['name']);
    $targetFile = $uploadDir . $filename;
    if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetFile)) {
      $poster_path = 'img/series/' . $filename;
    }
  }

  // Thêm vào CSDL
  $stmt = $conn->prepare("INSERT INTO series 
        (title, description, poster_url, status, season, total_episodes, genre, duration, rating) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssisdi", $title, $description, $poster_path, $status, $season, $total_episodes, $genre, $duration, $rating);

  if ($stmt->execute()) {
    header("Location: upload_episode.php");
    exit;
  } else {
    $error = "Thêm phim bộ thất bại: " . $conn->error;
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thêm Phim Bộ - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="../logo.png" type="image/png">
  <style>
    :root {
      --bg-a: #eaf9ff;
      /* pale aqua */
      --a-mid: #8edcf6;
      /* mid aqua */
      --a-strong: #57bde8;
      /* strong aqua */
      --accent: #ffd2c2;
      /* soft peach accent */
      --white: #ffffff;
      --text: #16324a;
      --muted: #587089;
      --radius: 14px;
      --shadow: 0 12px 30px rgba(20, 40, 60, 0.06);
      --glass: rgba(255, 255, 255, 0.75);
      --transition: all 0.25s ease;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial;
      min-height: 100vh;
      background: linear-gradient(180deg, var(--bg-a) 0%, #f7feff 60%);
      color: var(--text);
    }

    /* layout includes left sidebar space like other admin pages */
    .main-container {
      margin-left: 240px;
      padding: 2rem;
      min-height: 100vh;
      transition: var(--transition)
    }

    body.sidebar-collapsed .main-container {
      margin-left: 0
    }

    .back-btn {
      position: fixed;
      top: 22px;
      left: 22px;
      background: var(--glass);
      backdrop-filter: blur(6px);
      padding: 10px 14px;
      border-radius: 12px;
      color: var(--a-strong);
      box-shadow: var(--shadow);
      z-index: 1200;
      border: 1px solid rgba(87, 189, 232, 0.12)
    }

    .back-btn a {
      color: inherit;
      text-decoration: none;
      font-weight: 700
    }

    .form-container {
      position: relative;
      background: linear-gradient(180deg, var(--white), rgba(255, 255, 255, 0.95));
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: var(--shadow);
      overflow: hidden;
      border: 1px solid rgba(20, 40, 60, 0.04)
    }

    /* decorative organic blob using pseudo-element */
    .form-container::after {
      content: '';
      position: absolute;
      right: -120px;
      top: -80px;
      width: 360px;
      height: 360px;
      background: radial-gradient(circle at 30% 30%, var(--a-mid), var(--a-strong));
      opacity: 0.08;
      transform: rotate(25deg);
      filter: blur(30px);
      pointer-events: none
    }

    .form-header {
      text-align: center;
      margin-bottom: 1.5rem
    }

    .form-header h1 {
      font-size: 2rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px
    }

    .form-header h1 i {
      color: var(--a-strong);
      font-size: 1.6rem
    }

    .form-header p {
      color: var(--muted);
      margin-top: 6px
    }

    .form-group {
      margin-bottom: 20px
    }

    .form-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px
    }

    .form-control,
    .form-select {
      width: 100%;
      padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid rgba(20, 40, 60, 0.06);
      background: var(--glass);
      transition: var(--transition);
      color: var(--text)
    }

    .form-control:focus {
      outline: none;
      box-shadow: 0 6px 20px rgba(87, 189, 232, 0.12);
      border-color: var(--a-strong);
      background: #fff
    }

    /* bold upload area with interactive preview */
    .image-upload-container {
      border-radius: 14px;
      padding: 22px;
      text-align: center;
      border: 2px dashed rgba(87, 189, 232, 0.25);
      background: linear-gradient(180deg, transparent, rgba(87, 189, 232, 0.02));
      cursor: pointer;
      position: relative;
      transition: all .28s ease
    }

    .image-upload-container.dragover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(87, 189, 232, 0.08);
      border-color: var(--a-strong)
    }

    .upload-icon {
      font-size: 2.8rem;
      color: var(--a-strong);
      margin-bottom: 10px
    }

    .upload-text {
      font-weight: 800;
      color: var(--text);
      margin-bottom: 6px
    }

    .upload-hint {
      color: var(--muted);
      font-size: 0.9rem
    }

    .image-preview {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      margin-top: 12px
    }

    .preview-image {
      width: 220px;
      height: 140px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(20, 40, 60, 0.06)
    }

    .preview-meta {
      display: flex;
      gap: 12px;
      align-items: center;
      color: var(--muted);
      font-size: 0.9rem
    }

    .preview-remove {
      background: transparent;
      border: 1px solid rgba(20, 40, 60, 0.06);
      padding: 6px 10px;
      border-radius: 8px;
      cursor: pointer
    }

    /* animated submit button — playful gradient */
    .submit-btn {
      margin-top: 18px;
      padding: 12px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(90deg, var(--a-strong), #9be8ff);
      color: var(--white);
      font-weight: 800;
      letter-spacing: .02em;
      cursor: pointer;
      box-shadow: 0 12px 30px rgba(87, 189, 232, 0.12);
      transition: transform .18s ease
    }

    .submit-btn:hover {
      transform: translateY(-3px)
    }

    @media(max-width:900px) {
      .main-container {
        margin-left: 0;
        padding: 16px
      }

      .preview-image {
        width: 180px;
        height: 120px
      }

      .form-header h1 {
        font-size: 1.6rem
      }
    }
  </style>
</head>

<body>
  <?php include '../includes/admin_navbar.php'; ?>
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="back-btn">
    <a href="javascript:history.back()">
      <i class="fas fa-arrow-left"></i>
    </a>
  </div>

  <div class="main-container fade-in">
    <div class="form-container">
      <div class="form-header">
        <h1>
          <i class="fas fa-plus-circle"></i>
          Thêm Phim Bộ
        </h1>
        <p>Tạo phim bộ mới với thông tin chi tiết</p>
      </div>

      <?php if (isset($error)): ?>
        <div class="notification notification-error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?= $error ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="seriesForm">
        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-film"></i>
            Tên Phim
          </label>
          <input type="text" name="title" class="form-control" placeholder="Nhập tên phim bộ..." required>
        </div>

        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-align-left"></i>
            Mô Tả
          </label>
          <textarea name="description" class="form-control" rows="4" placeholder="Mô tả ngắn về nội dung phim..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-image"></i>
            Ảnh Poster
          </label>
          <div class="image-upload-container" id="uploadContainer" onclick="document.getElementById('posterInput').click()" role="button" tabindex="0">
            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="upload-text">Kéo thả poster hoặc click để chọn ảnh</div>
            <div class="upload-hint">JPG, PNG, GIF • Tối thiểu 800×450</div>

            <div class="image-preview" id="imagePreview" aria-hidden="true">
              <img class="preview-image" id="previewImage" alt="Preview">
              <div class="preview-meta">
                <span id="previewInfo">&nbsp;</span>
                <button type="button" class="preview-remove" id="removePreview">Xóa</button>
              </div>
            </div>
          </div>
          <input type="file" name="poster" id="posterInput" accept="image/*" style="display: none;">
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-play-circle"></i>
                Tình Trạng
              </label>
              <select name="status" class="form-select">
                <option value="Hoàn Thành">Hoàn Thành</option>
                <option value="Đang Chiếu">Đang Chiếu</option>
                <option value="Tạm Ngưng">Tạm Ngưng</option>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-calendar"></i>
                Mùa
              </label>
              <input type="text" name="season" class="form-control" placeholder="VD: 2025">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-list-ol"></i>
                Số Tập Dự Kiến
              </label>
              <input type="number" name="total_episodes" class="form-control" min="0" value="0" placeholder="0">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-star"></i>
                Đánh Giá (0–5)
              </label>
              <input type="number" name="rating" class="form-control" min="0" max="5" step="0.1" placeholder="0.0">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-tags"></i>
            Thể Loại
          </label>
          <input type="text" name="genre" class="form-control" placeholder="VD: Hành Động, Hài Hước, Lãng Mạn">
        </div>

        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-clock"></i>
            Thời Lượng Trung Bình
          </label>
          <input type="text" name="duration" class="form-control" placeholder="VD: 20-25 phút">
        </div>

        <button type="submit" class="submit-btn" id="submitBtn">
          <i class="fas fa-plus"></i>
          Thêm Phim Bộ
        </button>
      </form>
    </div>
  </div>

  <script>
    // Image preview functionality
    document.getElementById('posterInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const preview = document.getElementById('imagePreview');
          const previewImage = document.getElementById('previewImage');

          previewImage.src = e.target.result;
          preview.style.display = 'block';

          // Update upload text
          const uploadText = document.querySelector('.upload-text');
          uploadText.textContent = file.name;
        };
        reader.readAsDataURL(file);
      }
    });

    // Form submission with loading state
    document.getElementById('seriesForm').addEventListener('submit', function() {
      const submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm phim bộ...';
    });

    // Drag and drop functionality
    const uploadContainer = document.querySelector('.image-upload-container');

    uploadContainer.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.style.borderColor = '#4682B4';
      this.style.background = 'rgba(70, 130, 180, 0.1)';
    });

    uploadContainer.addEventListener('dragleave', function(e) {
      e.preventDefault();
      this.style.borderColor = '#E8F4FD';
      this.style.background = 'transparent';
    });

    uploadContainer.addEventListener('drop', function(e) {
      e.preventDefault();
      this.style.borderColor = '#E8F4FD';
      this.style.background = 'transparent';

      const files = e.dataTransfer.files;
      if (files.length > 0) {
        document.getElementById('posterInput').files = files;
        document.getElementById('posterInput').dispatchEvent(new Event('change'));
      }
    });
  </script>
</body>

</html>