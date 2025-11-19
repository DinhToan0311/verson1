<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

$series = [];
$result = mysqli_query($conn, "SELECT id, title FROM series");
$series = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Lấy thông tin tập hiện tại cho mỗi series
$seriesWithEpisodes = [];
foreach ($series as $s) {
    $seriesId = (int)$s['id'];
    $episodeResult = mysqli_query($conn, "SELECT COUNT(*) as total_episodes FROM episodes WHERE series_id = $seriesId");
    
    if ($episodeResult) {
        $episodeData = mysqli_fetch_assoc($episodeResult);
        $totalEpisodes = (int)($episodeData['total_episodes'] ?? 0);
    } else {
        $totalEpisodes = 0;
    }
    
    $seriesWithEpisodes[] = [
        'id' => $s['id'],
        'title' => $s['title'],
        'total_episodes' => $totalEpisodes,
        'next_episode' => $totalEpisodes + 1
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Upload Tập Mới - Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="../logo.png" type="image/png">
  <script src="https://widget.cloudinary.com/v2.0/global/all.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #0F0F23;
      min-height: 100vh;
      color: #FFFFFF;
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* Animated background */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
      animation: backgroundShift 20s ease-in-out infinite;
      z-index: -1;
    }

    @keyframes backgroundShift {
      0%, 100% { transform: translateX(0) translateY(0); }
      33% { transform: translateX(-20px) translateY(-10px); }
      66% { transform: translateX(20px) translateY(10px); }
    }

    .main-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      position: relative;
    }

    .floating-nav {
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1000;
    }

    .nav-btn {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #FFFFFF;
      padding: 12px 20px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .nav-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
      color: #FFFFFF;
    }

    .upload-workspace {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 30px;
      margin-top: 40px;
    }

    .upload-panel {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 30px;
      position: relative;
      overflow: hidden;
    }

    .upload-panel::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    }

    .panel-header {
      margin-bottom: 30px;
    }

    .panel-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #FFFFFF;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .panel-subtitle {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.9rem;
      font-weight: 400;
    }

    .input-group {
      margin-bottom: 25px;
      position: relative;
    }

    .input-label {
      display: block;
      color: rgba(255, 255, 255, 0.8);
      font-weight: 500;
      margin-bottom: 8px;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .input-field {
      width: 100%;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 14px 16px;
      color: #FFFFFF;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .input-field:focus {
      outline: none;
      border-color: rgba(120, 219, 255, 0.5);
      background: rgba(255, 255, 255, 0.08);
      box-shadow: 0 0 0 3px rgba(120, 219, 255, 0.1);
    }

    .input-field::placeholder {
      color: rgba(255, 255, 255, 0.4);
    }

    .upload-zone {
      border: 2px dashed rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 40px 20px;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .upload-zone:hover {
      border-color: rgba(120, 219, 255, 0.4);
      background: rgba(120, 219, 255, 0.05);
    }

    .upload-zone::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.5s ease;
    }

    .upload-zone:hover::before {
      left: 100%;
    }

    .upload-icon {
      font-size: 3rem;
      color: rgba(255, 255, 255, 0.6);
      margin-bottom: 16px;
    }

    .upload-text {
      color: rgba(255, 255, 255, 0.8);
      font-weight: 500;
      margin-bottom: 8px;
    }

    .upload-hint {
      color: rgba(255, 255, 255, 0.4);
      font-size: 0.85rem;
    }

    .action-btn {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 16px 24px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      position: relative;
      overflow: hidden;
    }

    .action-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s ease;
    }

    .action-btn:hover::before {
      left: 100%;
    }

    .action-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .action-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .info-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 20px;
    }

    .info-card h3 {
      color: #FFFFFF;
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .info-item:last-child {
      border-bottom: none;
    }

    .info-label {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.85rem;
    }

    .info-value {
      color: #FFFFFF;
      font-weight: 500;
      font-size: 0.9rem;
    }

    .progress-ring {
      width: 60px;
      height: 60px;
      margin: 0 auto 15px;
    }

    .progress-ring circle {
      fill: none;
      stroke-width: 4;
      stroke-linecap: round;
    }

    .progress-ring .bg {
      stroke: rgba(255, 255, 255, 0.1);
    }

    .progress-ring .progress {
      stroke: #4ECDC4;
      stroke-dasharray: 157;
      stroke-dashoffset: 157;
      transition: stroke-dashoffset 0.3s ease;
    }

    .series-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 10px;
      max-height: 200px;
      overflow-y: auto;
      padding: 5px;
    }

    .series-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 12px 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 180px;
      flex-shrink: 0;
    }

    .series-card:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(120, 219, 255, 0.3);
      transform: translateY(-2px);
    }

    .series-card.selected {
      background: rgba(120, 219, 255, 0.1);
      border-color: rgba(120, 219, 255, 0.5);
      box-shadow: 0 0 20px rgba(120, 219, 255, 0.2);
    }

    .series-icon {
      font-size: 1.5rem;
      color: rgba(255, 255, 255, 0.6);
    }

    .series-card.selected .series-icon {
      color: #78DBFF;
    }

    .series-info h4 {
      color: #FFFFFF;
      font-size: 0.85rem;
      font-weight: 600;
      margin: 0 0 2px 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 120px;
    }

    .series-info p {
      color: rgba(255, 255, 255, 0.5);
      font-size: 0.75rem;
      margin: 0 0 4px 0;
    }

    .episode-info {
      margin-top: 4px;
    }

    .next-episode {
      background: rgba(120, 219, 255, 0.2);
      color: #78DBFF;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.7rem;
      font-weight: 600;
    }

    .episode-title-container {
      position: relative;
    }

    .episode-suggestion {
      background: rgba(120, 219, 255, 0.1);
      border: 1px solid rgba(120, 219, 255, 0.3);
      border-radius: 8px;
      padding: 8px 12px;
      margin-top: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: slideInDown 0.3s ease;
    }

    .suggestion-text {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.85rem;
    }

    .suggestion-value {
      color: #78DBFF;
      font-weight: 600;
      font-size: 0.9rem;
      flex: 1;
    }

    .use-suggestion-btn {
      background: rgba(120, 219, 255, 0.2);
      border: 1px solid rgba(120, 219, 255, 0.3);
      color: #78DBFF;
      padding: 4px 8px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.8rem;
    }

    .use-suggestion-btn:hover {
      background: rgba(120, 219, 255, 0.3);
      transform: scale(1.05);
    }

    .search-container {
      margin-bottom: 20px;
    }

    .search-box {
      position: relative;
      display: flex;
      align-items: center;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 10px;
    }

    .search-box i {
      color: rgba(255, 255, 255, 0.6);
      margin-right: 12px;
    }

    .search-input {
      flex: 1;
      background: transparent;
      border: none;
      color: #FFFFFF;
      font-size: 1rem;
      outline: none;
    }

    .search-input::placeholder {
      color: rgba(255, 255, 255, 0.4);
    }

    .clear-btn {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.6);
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: all 0.3s ease;
    }

    .clear-btn:hover {
      color: #FFFFFF;
      background: rgba(255, 255, 255, 0.1);
    }

    .search-stats {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.85rem;
      text-align: right;
    }

    .series-actions {
      margin-left: auto;
    }

    .select-btn {
      background: rgba(120, 219, 255, 0.2);
      border: 1px solid rgba(120, 219, 255, 0.3);
      color: #78DBFF;
      padding: 8px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      opacity: 0;
    }

    .series-card:hover .select-btn {
      opacity: 1;
    }

    .select-btn:hover {
      background: rgba(120, 219, 255, 0.3);
      transform: scale(1.1);
    }

    .selected-series {
      background: rgba(120, 219, 255, 0.1);
      border: 1px solid rgba(120, 219, 255, 0.3);
      border-radius: 12px;
      padding: 16px;
      margin-top: 15px;
    }

    .selected-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .selected-info i {
      color: #78DBFF;
      font-size: 1.2rem;
    }

    .selected-info h4 {
      color: #FFFFFF;
      margin: 0 0 4px 0;
      font-size: 1rem;
    }

    .selected-info p {
      color: rgba(255, 255, 255, 0.6);
      margin: 0;
      font-size: 0.85rem;
    }

    .change-btn {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: rgba(255, 255, 255, 0.8);
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-left: auto;
      font-size: 0.85rem;
    }

    .change-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #FFFFFF;
    }

    .series-card.hidden {
      display: none;
    }

    .progress-container {
      margin-top: 20px;
      display: none;
    }

    .progress {
      height: 8px;
      border-radius: 10px;
      background: rgba(70, 130, 180, 0.1);
      overflow: hidden;
    }

    .progress-bar {
      background: linear-gradient(135deg, #4682B4, #5F9EA0);
      height: 100%;
      border-radius: 10px;
      transition: width 0.3s ease;
    }

    .progress-text {
      text-align: center;
      margin-top: 10px;
      color: #4682B4;
      font-weight: 500;
    }

    .notification {
      margin-top: 20px;
      padding: 16px 20px;
      border-radius: 12px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideInDown 0.3s ease;
    }

    .notification-success {
      background: rgba(46, 204, 113, 0.1);
      color: #27ae60;
      border: 1px solid rgba(46, 204, 113, 0.2);
    }

    .notification-error {
      background: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
      border: 1px solid rgba(231, 76, 60, 0.2);
    }

    .notification-warning {
      background: rgba(243, 156, 18, 0.1);
      color: #f39c12;
      border: 1px solid rgba(243, 156, 18, 0.2);
    }

    .notification-info {
      background: rgba(70, 130, 180, 0.1);
      color: #4682B4;
      border: 1px solid rgba(70, 130, 180, 0.2);
    }

    .upload-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 15px;
      margin-top: 30px;
    }

    .stat-item {
      background: rgba(255, 255, 255, 0.6);
      padding: 20px;
      border-radius: 15px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .stat-item i {
      font-size: 1.8rem;
      color: #4682B4;
      margin-bottom: 8px;
    }

    .stat-item h4 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #2C3E50;
      margin-bottom: 4px;
    }

    .stat-item p {
      color: #666;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .fade-in {
      animation: fadeIn 0.6s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideInDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      .main-container {
        padding: 15px;
      }

      .upload-container {
        padding: 25px;
      }

      .upload-header h1 {
        font-size: 1.8rem;
      }

      .back-btn {
        top: 20px;
        left: 20px;
        padding: 12px 16px;
      }
    }
  </style>
</head>

<body>
  <div class="floating-nav">
    <a href="javascript:history.back()" class="nav-btn">
      <i class="fas fa-arrow-left"></i>
      Quay lại
    </a>
  </div>

  <div class="main-container">
    <div class="upload-workspace">
      <!-- Main Upload Panel -->
      <div class="upload-panel">
        <div class="panel-header">
          <h1 class="panel-title">
            <i class="fas fa-cloud-upload-alt"></i>
            Upload Episode
          </h1>
          <p class="panel-subtitle">Tải lên tập phim mới với giao diện hiện đại</p>
        </div>

        <form id="uploadForm">
          <div class="input-group">
            <label class="input-label">
              <i class="fas fa-film"></i>
              Chọn Phim Bộ
            </label>
            
            <!-- Search Box -->
            <div class="search-container">
              <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="seriesSearch" placeholder="Tìm kiếm phim theo tên hoặc ID..." class="search-input">
                <button type="button" id="clearSearch" class="clear-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div class="search-stats">
                <span id="searchResults">Hiển thị <?= count($series) ?> phim</span>
              </div>
            </div>

            <!-- Series Grid -->
            <div class="series-grid" id="seriesGrid">
              <?php foreach ($seriesWithEpisodes as $s): ?>
                <div class="series-card" data-id="<?= $s['id'] ?>" data-title="<?= strtolower(htmlspecialchars($s['title'])) ?>" data-next-episode="<?= $s['next_episode'] ?>">
                  <div class="series-icon">
                    <i class="fas fa-play-circle"></i>
                  </div>
                  <div class="series-info">
                    <h4><?= htmlspecialchars($s['title']) ?></h4>
                    <p>ID: <?= $s['id'] ?> • Tập: <?= $s['total_episodes'] ?></p>
                    <div class="episode-info">
                      <span class="next-episode">Tập tiếp: <?= $s['next_episode'] ?></span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <!-- Selected Series Display -->
            <div class="selected-series" id="selectedSeriesDisplay" style="display: none;">
              <div class="selected-info">
                <i class="fas fa-check-circle"></i>
                <div>
                  <h4 id="selectedSeriesTitle"></h4>
                  <p>ID: <span id="selectedSeriesId"></span></p>
                </div>
                <button type="button" class="change-btn" onclick="clearSelection()">
                  <i class="fas fa-edit"></i> Thay đổi
                </button>
              </div>
            </div>
            
            <input type="hidden" name="series_id" id="selected_series_id" required>
          </div>

          <div class="input-group">
            <label class="input-label">
              <i class="fas fa-heading"></i>
              Tên Tập
            </label>
            <div class="episode-title-container">
              <input type="text" name="title" class="input-field" id="episodeTitleInput" placeholder="Nhập tên tập phim..." required>
              <div class="episode-suggestion" id="episodeSuggestion" style="display: none;">
                <span class="suggestion-text">Gợi ý: </span>
                <span class="suggestion-value" id="suggestedTitle"></span>
                <button type="button" class="use-suggestion-btn" onclick="useSuggestion()">
                  <i class="fas fa-check"></i> Dùng
                </button>
              </div>
            </div>
          </div>

          <div class="input-group">
            <label class="input-label">
              <i class="fas fa-align-left"></i>
              Mô Tả
            </label>
            <textarea name="description" class="input-field" rows="3" placeholder="Mô tả ngắn về tập phim..."></textarea>
          </div>

          <div class="input-group">
            <label class="input-label">
              <i class="fas fa-video"></i>
              Video File
            </label>
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
              <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
              </div>
              <div class="upload-text">Kéo thả video vào đây</div>
              <div class="upload-hint">hoặc click để chọn file</div>
            </div>
            <input type="file" id="fileInput" accept="video/*" style="display: none;">
          </div>

          <button type="button" class="action-btn" id="uploadAndSave">
            <i class="fas fa-rocket"></i>
            Upload & Save Episode
          </button>

          <input type="hidden" name="video_url" id="video_url">
          <input type="hidden" name="public_id" id="public_id">
        </form>

        <div id="message"></div>
      </div>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Upload Progress -->
        <div class="info-card">
          <h3>
            <i class="fas fa-chart-line"></i>
            Upload Progress
          </h3>
          <div class="progress-ring">
            <svg viewBox="0 0 60 60">
              <circle class="bg" cx="30" cy="30" r="25"></circle>
              <circle class="progress" cx="30" cy="30" r="25" id="progressCircle"></circle>
            </svg>
          </div>
          <div class="info-item">
            <span class="info-label">Status</span>
            <span class="info-value" id="uploadStatus">Ready</span>
          </div>
          <div class="info-item">
            <span class="info-label">Progress</span>
            <span class="info-value" id="uploadProgress">0%</span>
          </div>
        </div>

        <!-- Episode Info -->
        <div class="info-card">
          <h3>
            <i class="fas fa-info-circle"></i>
            Episode Info
          </h3>
          <div class="info-item">
            <span class="info-label">Series</span>
            <span class="info-value" id="selectedSeries">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Title</span>
            <span class="info-value" id="episodeTitle">-</span>
          </div>
          <div class="info-item">
            <span class="info-label">Duration</span>
            <span class="info-value" id="videoDuration">-</span>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="info-card">
          <h3>
            <i class="fas fa-chart-bar"></i>
            Quick Stats
          </h3>
          <div class="info-item">
            <span class="info-label">Total Series</span>
            <span class="info-value"><?= count($series) ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Today's Uploads</span>
            <span class="info-value">0</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const cloudName = 'dz5rz7doo';
    const uploadPreset = 'ml_default';
    let isUploading = false;

    // Search functionality
    document.getElementById('seriesSearch').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const cards = document.querySelectorAll('.series-card');
      let visibleCount = 0;
      
      cards.forEach(card => {
        const title = card.getAttribute('data-title');
        const id = card.getAttribute('data-id');
        
        if (title.includes(searchTerm) || id.includes(searchTerm)) {
          card.classList.remove('hidden');
          visibleCount++;
        } else {
          card.classList.add('hidden');
        }
      });
      
      document.getElementById('searchResults').textContent = `Hiển thị ${visibleCount} phim`;
    });

    // Clear search
    document.getElementById('clearSearch').addEventListener('click', function() {
      document.getElementById('seriesSearch').value = '';
      document.querySelectorAll('.series-card').forEach(card => {
        card.classList.remove('hidden');
      });
      document.getElementById('searchResults').textContent = `Hiển thị ${document.querySelectorAll('.series-card').length} phim`;
    });

    // Series selection functions
    function selectSeries(id, title, nextEpisode) {
      // Remove selected class from all cards
      document.querySelectorAll('.series-card').forEach(c => c.classList.remove('selected'));
      
      // Add selected class to clicked card
      const selectedCard = document.querySelector(`[data-id="${id}"]`);
      if (selectedCard) {
        selectedCard.classList.add('selected');
      }
      
      // Set hidden input value
      document.getElementById('selected_series_id').value = id;
      
      // Show selected series display
      document.getElementById('selectedSeriesTitle').textContent = title;
      document.getElementById('selectedSeriesId').textContent = id;
      document.getElementById('selectedSeriesDisplay').style.display = 'block';
      document.getElementById('seriesGrid').style.display = 'none';
      
      // Show episode suggestion
      if (nextEpisode) {
        showEpisodeSuggestion(title, nextEpisode);
      }
      
      // Update sidebar
      updateSidebarInfo();
    }

    function showEpisodeSuggestion(seriesTitle, nextEpisode) {
      const suggestion = `${seriesTitle} - Tập ${nextEpisode}`;
      document.getElementById('suggestedTitle').textContent = suggestion;
      document.getElementById('episodeSuggestion').style.display = 'flex';
    }

    function useSuggestion() {
      const suggestedTitle = document.getElementById('suggestedTitle').textContent;
      document.getElementById('episodeTitleInput').value = suggestedTitle;
      document.getElementById('episodeSuggestion').style.display = 'none';
      updateSidebarInfo();
    }

    function clearSelection() {
      // Clear selection
      document.querySelectorAll('.series-card').forEach(c => c.classList.remove('selected'));
      document.getElementById('selected_series_id').value = '';
      
      // Hide selected display and show grid
      document.getElementById('selectedSeriesDisplay').style.display = 'none';
      document.getElementById('seriesGrid').style.display = 'flex';
      
      // Update sidebar
      updateSidebarInfo();
    }

    // Series card click handlers
    document.querySelectorAll('.series-card').forEach(card => {
      card.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.querySelector('h4').textContent;
        const nextEpisode = this.getAttribute('data-next-episode');
        selectSeries(id, title, nextEpisode);
      });
    });

    // File input handling
    document.getElementById('fileInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const uploadZone = document.getElementById('uploadZone');
        uploadZone.innerHTML = `
          <div class="upload-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="upload-text">${file.name}</div>
          <div class="upload-hint">Click để chọn file khác</div>
        `;
      }
    });

    // Update sidebar info
    function updateSidebarInfo() {
      const form = document.getElementById("uploadForm");
      const selectedCard = document.querySelector('.series-card.selected');
      const selectedSeries = selectedCard ? selectedCard.querySelector('h4').textContent : '-';
      const episodeTitle = form.title.value || '-';
      
      document.getElementById('selectedSeries').textContent = selectedSeries;
      document.getElementById('episodeTitle').textContent = episodeTitle;
    }

    // Update progress ring
    function updateProgress(percent) {
      const circle = document.getElementById('progressCircle');
      const circumference = 2 * Math.PI * 25;
      const offset = circumference - (percent / 100) * circumference;
      circle.style.strokeDashoffset = offset;
      document.getElementById('uploadProgress').textContent = percent + '%';
    }

    // Show notification
    function showNotification(message, type = 'info') {
      const notification = $(`
        <div class="notification notification-${type}">
          <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
          <span>${message}</span>
        </div>
      `);
      
      $('#message').html(notification);
      
      setTimeout(() => {
        notification.addClass('show');
      }, 100);
    }

    // Form change listeners
    document.getElementById('uploadForm').addEventListener('input', updateSidebarInfo);
    document.getElementById('uploadForm').addEventListener('change', updateSidebarInfo);

    // Chống thoát trang khi đang upload
    window.onbeforeunload = function() {
      if (isUploading) {
        return "⚠️ Video đang được tải lên. Nếu bạn rời trang, quá trình upload sẽ bị hủy.";
      }
    };

    document.getElementById("uploadAndSave").addEventListener("click", function() {
      const form = document.getElementById("uploadForm");
      const title = form.title.value.trim();
      const seriesId = form.series_id.value;
      
      if (!title || !seriesId) {
        showNotification('⚠️ Vui lòng điền đầy đủ thông tin trước khi upload.', 'warning');
        return;
      }

      isUploading = true;
      document.getElementById('uploadStatus').textContent = 'Uploading...';
      updateProgress(0);
      showNotification('🚀 Đang tải video lên Cloudinary. Vui lòng không rời khỏi trang...', 'info');

      const myWidget = cloudinary.createUploadWidget({
        cloudName: cloudName,
        uploadPreset: uploadPreset,
        resourceType: 'video',
        folder: 'episodes'
      }, (error, result) => {
        if (!error && result && result.event === "success") {
          updateProgress(100);
          document.getElementById('uploadStatus').textContent = 'Processing...';
          
          const videoURL = result.info.secure_url;
          const publicID = result.info.public_id;

          const postData = {
            series_id: parseInt(seriesId),
            title: title,
            description: form.description.value,
            video_url: videoURL,
            public_id: publicID
          };

          $.ajax({
            url: "save_episode.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(postData),
            success: function(res) {
              isUploading = false;
              document.getElementById('uploadStatus').textContent = 'Completed';

              if (res.status === "success") {
                showNotification('✅ Tập mới đã được lưu thành công! Bạn có thể tiếp tục tải tập khác.', 'success');
                form.reset();
                updateSidebarInfo();
                updateProgress(0);
                document.getElementById('uploadStatus').textContent = 'Ready';
              } else {
                showNotification('❌ Lỗi: ' + res.message, 'error');
                document.getElementById('uploadStatus').textContent = 'Error';
              }
            },
            error: function() {
              isUploading = false;
              showNotification('❌ Không thể kết nối tới máy chủ.', 'error');
              document.getElementById('uploadStatus').textContent = 'Error';
            }
          });

        } else if (error) {
          isUploading = false;
          showNotification('❌ Upload lỗi: ' + error.message, 'error');
          document.getElementById('uploadStatus').textContent = 'Error';
        }
      });

      myWidget.open();
    });

    // Initialize
    updateSidebarInfo();
  </script>
</body>

</html>