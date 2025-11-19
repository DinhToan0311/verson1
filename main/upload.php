<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id'])) {
  die('🔒 Bạn cần đăng nhập để upload video.');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Upload Video | MMG Tube</title>
  <link rel="icon" href="../logo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    .upload-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      padding: 20px;
      background: #f7f7f7;
      border-radius: 10px;
      max-width: 900px;
      margin: auto;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
      margin-top: 8%;
    }

    .upload-container h2 {
      margin-bottom: 15px;
      font-size: 22px;
      color: #333;
    }

    .upload-container label {
      display: block;
      margin: 10px 0 5px;
      font-weight: bold;
      color: #444;
    }

    .upload-container input[type="text"],
    .upload-container input[type="file"],
    .upload-container textarea {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
      box-sizing: border-box;
    }

    .upload-container textarea {
      resize: vertical;
    }

    .upload-btn {
      margin-top: 20px;
      padding: 12px 20px;
      background-color: #28a745;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.3s ease;
    }

    .upload-btn:hover {
      background-color: #218838;
    }

    #uploadPreviewModal {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      border: 1px solid #ccc;
      padding: 20px 30px;
      z-index: 2000;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      display: none;
      width: 90%;
      max-width: 400px;
      text-align: center;
    }

    #uploadPreviewModal img {
      max-width: 100%;
      border-radius: 6px;
      margin: 10px 0;
    }

    #uploadPreviewModal .progress {
      margin-top: 15px;
      font-weight: bold;
    }

    /* Left and right side */
    .form-left,
    .form-right {
      width: 48%;
      box-sizing: border-box;
    }

    /* Small screen */
    @media (max-width: 768px) {

      .form-left,
      .form-right {
        width: 100%;
        margin-bottom: 20px;
      }

      .upload-btn {
        width: 100%;
      }
    }

    /* Extra small screen (mobile) */
    @media (max-width: 576px) {
      .upload-container {
        padding: 15px;
      }

      .upload-container h2 {
        font-size: 20px;
      }

      .upload-btn {
        font-size: 15px;
        padding: 10px;
      }

    }

    .upload-btn-custom {
      margin-top: 20px;
      padding: 12px 20px;
      background-color: #007bff;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.3s ease, transform 0.1s ease;
      font-weight: bold;
      box-shadow: 0 3px 10px rgba(0, 123, 255, 0.2);
    }

    .upload-btn-custom:hover {
      background-color: #0056b3;
    }

    .upload-btn-custom:active {
      transform: scale(0.97);
      background-color: #004080;
    }

    #customCategoryInput {
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 6px;
      width: 100%;
      box-sizing: border-box;
    }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>
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

  <form class="upload-container" id="uploadForm" enctype="multipart/form-data">
    <div class="form-left">
      <h2>📤 Thông tin video</h2>
      <label>Tiêu đề video:</label>
      <input type="text" name="title" required>
      <label>Thể loại:</label>
      <input type="text" name="category" list="categoryList" placeholder="Nhập hoặc chọn thể loại" required />

      <datalist id="categoryList">
        <option value="Hành động">
        <option value="Tình cảm">
        <option value="Hài">
        <option value="Tâm lý">
        <option value="Khoa học viễn tưởng">
        <option value="Học đường">
      </datalist>

      <label>Mô tả:</label>
      <textarea name="description" rows="5"></textarea>
    </div>

    <div class="form-right">
      <br><br><br>
      <label>Chọn video:</label>
      <input type="file" name="video" id="videoFile" accept="video/*" required>

      <label>Ảnh thumbnail (tùy chọn):</label>
      <input type="file" name="thumb" accept="image/*">
      <br><br><br>
      <form id="uploadForm">
        <button type="submit" class="upload-btn-custom">🚀 Tải video lên</button>

      </form>

      <div style="margin-top: 15px;">
        <div style="height: 20px; background: #ddd; border-radius: 10px;">
          <div id="uploadBar" style="width: 0%; height: 100%; background: #28a745;"></div>
        </div>
        <div id="uploadProgressText" style="margin-top: 5px;">Đang chờ tải lên...</div>
      </div>
    </div>
  </form>
  <script>
    const form = document.getElementById("uploadForm");
    const progressBar = document.getElementById("uploadBar");
    const progressText = document.getElementById("uploadProgressText");

    let homeTab = null;

    form.addEventListener("submit", async function(e) {
      e.preventDefault();

      const video = document.getElementById("videoFile").files[0];
      const thumb = document.querySelector("[name=thumb]").files[0];
      const title = document.querySelector("[name=title]").value.trim();
      const category = document.querySelector("input[name='category']").value.trim();
      const description = document.querySelector("textarea[name=description]").value.trim();

      if (!video) return alert("❌ Bạn cần chọn video.");
      if (video.size > 100 * 1024 * 1024) return alert("❌ File quá lớn (>100MB)");

      // Mở tab mới
      homeTab = window.open("", "_blank");
      if (homeTab) {
        homeTab.document.write(`
        <html>
          <head><title>Đang tải lên...</title></head>
          <body>
            <script>
              alert("📤 Bắt đầu tải lên...\\nBạn có thể thao tác ở tab này.");
              window.location.href = "trangchu.php";
            <\/script>
          </body>
        </html>
      `);
        homeTab.document.close();
      } else {
        return alert("⚠️ Trình duyệt đã chặn tab mới.");
      }

      // Upload video
      const formData = new FormData();
      formData.append("file", video);
      formData.append("upload_preset", "ml_default");

      const xhr = new XMLHttpRequest();
      xhr.open("POST", "https://api.cloudinary.com/v1_1/dz5rz7doo/video/upload", true);

      // Tiến trình upload
      xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
          const percent = Math.round((e.loaded / e.total) * 100);
          progressBar.style.width = percent + "%";
          progressText.textContent = `⬆️ Đang tải lên: ${percent}%`;

          // Gửi tín hiệu tiến trình ra ngoài
          const channel = new BroadcastChannel('upload-progress');
          channel.postMessage({
            type: 'upload-progress',
            progress: percent
          });
        }
      };


      xhr.onload = async function() {
        const res = JSON.parse(xhr.responseText || "{}");
        if (!res.secure_url) {
          alert("❌ Upload thất bại");
          return;
        }

        // Gửi tín hiệu hoàn tất
        const channel = new BroadcastChannel('upload-progress');
        channel.postMessage({
          type: 'upload-finished'
        });

        // Upload thumbnail nếu có
        let thumbUrl = "";
        if (thumb) {
          const thumbData = new FormData();
          thumbData.append("file", thumb);
          thumbData.append("upload_preset", "ml_default");

          const thumbRes = await fetch("https://api.cloudinary.com/v1_1/dz5rz7doo/image/upload", {
            method: "POST",
            body: thumbData
          }).then(r => r.json());

          thumbUrl = thumbRes.secure_url || "";
        }

        // Gửi metadata về server
        const saveRes = await fetch("luu_video.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            title,
            category,
            description,
            videoUrl: res.secure_url,
            publicId: res.public_id,
            duration: Math.floor(res.duration || 0),
            thumbnail: thumbUrl
          })
        });

        const resultText = await saveRes.text();
        console.log(resultText);

        progressBar.style.width = "100%";
        progressText.textContent = "✅ Tải lên hoàn tất!";

        setTimeout(() => {
          window.close();
        }, 1000);

      };

      xhr.onerror = function() {
        alert("❌ Upload thất bại do lỗi mạng.");
      };

      // BẮT ĐẦU upload
      xhr.send(formData);
    });
  </script>



</body>

</html>