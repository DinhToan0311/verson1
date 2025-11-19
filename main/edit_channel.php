<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../loginphp/db.php';
require_once 'cloudinary_config.php';

if (!isset($_SESSION['user_id'])) die("Bạn chưa đăng nhập.");
$userId = $_SESSION['user_id'];

// Cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $desc = trim($_POST['description']);
  $avatar_url = $_POST['avatar_url'] ?? null;
  $banner_url = $_POST['banner_url'] ?? null;

  $sql = "UPDATE channels SET name=?, description=?, avatar=?, banner=? WHERE user_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssssi", $name, $desc, $avatar_url, $banner_url, $userId);
  $stmt->execute();

  header("Location: channel.php");
  exit;
}

// Lấy dữ liệu
$stmt = $conn->prepare("SELECT * FROM channels WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$channel = $stmt->get_result()->fetch_assoc();
?>
<!-- HTML phía dưới giữ nguyên -->

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Chỉnh sửa kênh</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: #f4f6f8;
      padding: 40px;
      margin: 0;
    }

    .container {
      max-width: 700px;
      margin: auto;
      background: white;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    }

    h2 {
      margin-bottom: 25px;
      color: #333;
      text-align: center;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: 500;
      color: #333;
    }

    input[type="text"],
    textarea,
    input[type="file"] {
      width: 100%;
      padding: 10px;
      margin-top: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }

    textarea {
      resize: vertical;
    }

    .preview {
      margin-top: 15px;
    }

    .preview img {
      max-width: 100%;
      max-height: 180px;
      border-radius: 6px;
      border: 1px solid #ddd;
      margin-top: 8px;
    }

    button {
      margin-top: 25px;
      padding: 12px 20px;
      background: #0073ff;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #005dd1;
    }

    /* 👉 Responsive cho điện thoại */
    @media (max-width: 768px) {
      body {
        padding: 20px 10px;
        font-size: 15px;
      }

      .container {
        padding: 20px;
        width: 100%;
        box-sizing: border-box;
      }

      input[type="text"],
      textarea,
      input[type="file"] {
        font-size: 14px;
        padding: 8px;
      }

      button {
        font-size: 15px;
        padding: 10px;
      }

      h2 {
        font-size: 20px;
      }
    }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>
  <?php $forceSidebarOpen = true; ?>
  <?php include '../includes/sidebar.php'; ?>

  <div class="container">
    <h2>🛠️ Chỉnh sửa kênh</h2>
    <form method="POST">
      <label for="name">Tên kênh:</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($channel['name']) ?>" required>

      <label for="description">Mô tả kênh:</label>
      <textarea id="description" name="description" rows="4"><?= htmlspecialchars($channel['description']) ?></textarea>

      <label for="avatar">Ảnh đại diện:</label>
      <input type="file" id="avatar" onchange="uploadImage(this, 'avatar')">
      <input type="hidden" name="avatar_url" id="avatar_url" value="<?= htmlspecialchars($channel['avatar']) ?>">
      <div class="preview">
        <img id="avatar-preview" src="<?= htmlspecialchars($channel['avatar']) ?>" alt="Avatar">
      </div>

      <label for="banner">Ảnh bìa:</label>
      <input type="file" id="banner" onchange="uploadImage(this, 'banner')">
      <input type="hidden" name="banner_url" id="banner_url" value="<?= htmlspecialchars($channel['banner']) ?>">
      <div class="preview">
        <img id="banner-preview" src="<?= htmlspecialchars($channel['banner']) ?>" alt="Banner">
      </div>

      <button type="submit">📂 Lưu thay đổi</button>
    </form>
  </div>

  <script>
    async function uploadImage(input, type) {
      const file = input.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("image", file);
      formData.append("type", type);

      const previewId = type + "-preview";
      const hiddenInputId = type + "_url";

      try {
        const res = await fetch("upload-avatar-banner.php", {
          method: "POST",
          body: formData
        });
        const data = await res.json();
        if (data.url) {
          document.getElementById(previewId).src = data.url;
          document.getElementById(hiddenInputId).value = data.url;
        } else {
          alert("Lỗi khi upload: " + (data.error || "Không xác định"));
        }
      } catch (err) {
        alert("Lỗi kết nối khi upload ảnh");
      }
    }
  </script>
</body>

</html>