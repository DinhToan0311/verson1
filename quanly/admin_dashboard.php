<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập.");
}

// Thống kê tổng quan
$stats = [];

// Tổng số người dùng
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Tổng số admin
$stats['total_admins'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];

// Tổng số phim bộ
$stats['total_series'] = $conn->query("SELECT COUNT(*) as count FROM series")->fetch_assoc()['count'];

// Tổng số tập phim
$stats['total_episodes'] = $conn->query("SELECT COUNT(*) as count FROM episodes")->fetch_assoc()['count'];

// Tổng số video
$stats['total_videos'] = $conn->query("SELECT COUNT(*) as count FROM videos")->fetch_assoc()['count'];

// Tổng số channels
$stats['total_channels'] = $conn->query("SELECT COUNT(*) as count FROM channels")->fetch_assoc()['count'];

// Tổng lượt xem
$stats['total_views'] = $conn->query("SELECT SUM(views) as total FROM videos")->fetch_assoc()['total'] ?? 0;
$stats['total_episode_views'] = $conn->query("SELECT SUM(views) as total FROM episodes")->fetch_assoc()['total'] ?? 0;
$stats['total_all_views'] = $stats['total_views'] + $stats['total_episode_views'];

// Tổng số bình luận
$stats['total_comments'] = $conn->query("SELECT COUNT(*) as count FROM comments")->fetch_assoc()['count'];

// Tổng số playlist
$stats['total_playlists'] = $conn->query("SELECT COUNT(*) as count FROM playlists")->fetch_assoc()['count'];

// Tổng số subscription
$stats['total_subscriptions'] = $conn->query("SELECT COUNT(*) as count FROM subscriptions")->fetch_assoc()['count'];

// Tổng số favorites
$stats['total_favorites'] = $conn->query("SELECT COUNT(*) as count FROM favorites")->fetch_assoc()['count'];

// Thống kê theo thời gian (7 ngày qua)
$week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

// Người dùng mới (7 ngày qua)
$stats['new_users_week'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE id IN (SELECT DISTINCT user_id FROM channels WHERE created_at >= '$week_ago')")->fetch_assoc()['count'];

// Video mới (7 ngày qua)
$stats['new_videos_week'] = $conn->query("SELECT COUNT(*) as count FROM videos WHERE upload_date >= '$week_ago'")->fetch_assoc()['count'];

// Tập phim mới (7 ngày qua)
$stats['new_episodes_week'] = $conn->query("SELECT COUNT(*) as count FROM episodes WHERE created_at >= '$week_ago'")->fetch_assoc()['count'];

// Lượt xem (7 ngày qua)
$stats['views_week'] = $conn->query("SELECT COUNT(*) as count FROM views_log WHERE viewed_at >= '$week_ago'")->fetch_assoc()['count'];

// Top series phổ biến
$top_series = $conn->query("
    SELECT s.id, s.title, s.views, COUNT(e.id) as episode_count, 
           COALESCE(SUM(e.views), 0) as total_episode_views
    FROM series s 
    LEFT JOIN episodes e ON s.id = e.series_id 
    GROUP BY s.id 
    ORDER BY (s.views + COALESCE(SUM(e.views), 0)) DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Top video phổ biến
$top_videos = $conn->query("
    SELECT v.id, v.title, v.views, v.upload_date, u.name as uploader_name
    FROM videos v 
    LEFT JOIN users u ON v.uploaded_by = u.id 
    ORDER BY v.views DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Hoạt động gần đây
$recent_activities = $conn->query("
    SELECT 'video' as type, v.title as content, v.upload_date as created_at, u.name as user_name
    FROM videos v 
    LEFT JOIN users u ON v.uploaded_by = u.id 
    WHERE v.upload_date >= '$week_ago'
    UNION ALL
    SELECT 'episode' as type, CONCAT('Tập: ', e.title) as content, e.created_at, 'System' as user_name
    FROM episodes e 
    WHERE e.created_at >= '$week_ago'
    ORDER BY created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #E8F4FD 0%, #F0F8FF 50%, #E6F3FF 100%);
            min-height: 100vh;
            color: #2c3e50;
            line-height: 1.6;
        }

        .main-container {
            margin-left: 15%;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3498db, #2980b9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2980b9);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(31, 38, 135, 0.5);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-icon.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .stat-icon.info {
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
        }

        .stat-icon.danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .stat-change.positive {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .stat-change.negative {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            height: 400px;
            position: relative;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-activities {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .activity-icon.video {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .activity-icon.episode {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .top-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .top-list {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .top-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .top-item:last-child {
            border-bottom: none;
        }

        .top-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: white;
        }

        .top-rank.rank-1 {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .top-rank.rank-2 {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .top-rank.rank-3 {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        .top-rank.rank-other {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .top-content-info {
            flex: 1;
        }

        .top-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .top-meta {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .top-views {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .quick-actions {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f4e79);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .top-content {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/admin_navbar.php'; ?>
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <p class="page-subtitle">Tổng quan toàn bộ hệ thống streaming video</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-label">Tổng số người dùng</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +<?= $stats['new_users_week'] ?> tuần này
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-tv"></i>
                    </div>
                </div>
                <div class="stat-number"><?= number_format($stats['total_series']) ?></div>
                <div class="stat-label">Tổng số phim bộ</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +<?= $stats['new_episodes_week'] ?> tập mới
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-play-circle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= number_format($stats['total_videos'] + $stats['total_episodes']) ?></div>
                <div class="stat-label">Tổng số video</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +<?= $stats['new_videos_week'] + $stats['new_episodes_week'] ?> tuần này
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon info">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <div class="stat-number"><?= number_format($stats['total_all_views']) ?></div>
                <div class="stat-label">Tổng lượt xem</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +<?= number_format($stats['views_week']) ?> tuần này
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="stat-number"><?= number_format($stats['total_comments']) ?></div>
                <div class="stat-label">Tổng số bình luận</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> Hoạt động
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                </div>
                <div class="stat-number"><?= number_format($stats['total_channels']) ?></div>
                <div class="stat-label">Tổng số kênh</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> <?= $stats['total_subscriptions'] ?> đăng ký
                </div>
            </div>
        </div>

        <!-- Top Content -->
        <div class="top-content">
            <!-- Top Series -->
            <div class="top-list">
                <h3 class="chart-title">
                    <i class="fas fa-trophy"></i>
                    Top Phim Bộ Phổ Biến
                </h3>
                <?php foreach ($top_series as $index => $series): ?>
                    <div class="top-item">
                        <div class="top-rank rank-<?= $index < 3 ? $index + 1 : 'other' ?>">
                            <?= $index + 1 ?>
                        </div>
                        <div class="top-content-info">
                            <div class="top-title"><?= htmlspecialchars($series['title']) ?></div>
                            <div class="top-meta"><?= $series['episode_count'] ?> tập • ID: <?= $series['id'] ?></div>
                        </div>
                        <div class="top-views">
                            <?= number_format($series['views'] + $series['total_episode_views']) ?> lượt xem
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Top Videos -->
            <div class="top-list">
                <h3 class="chart-title">
                    <i class="fas fa-fire"></i>
                    Top Video Phổ Biến
                </h3>
                <?php foreach ($top_videos as $index => $video): ?>
                    <div class="top-item">
                        <div class="top-rank rank-<?= $index < 3 ? $index + 1 : 'other' ?>">
                            <?= $index + 1 ?>
                        </div>
                        <div class="top-content-info">
                            <div class="top-title"><?= htmlspecialchars($video['title']) ?></div>
                            <div class="top-meta"><?= $video['uploader_name'] ?> • <?= date('d/m/Y', strtotime($video['upload_date'])) ?></div>
                        </div>
                        <div class="top-views">
                            <?= number_format($video['views']) ?> lượt xem
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Chart Container -->
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Thống Kê Nội Dung
                </h3>
                <div class="chart-wrapper">
                    <canvas id="contentChart"></canvas>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="recent-activities">
                <h3 class="chart-title">
                    <i class="fas fa-clock"></i>
                    Hoạt Động Gần Đây
                </h3>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $activity['type'] ?>">
                            <i class="fas fa-<?= $activity['type'] === 'video' ? 'video' : 'play' ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?= htmlspecialchars($activity['content']) ?></div>
                            <div class="activity-meta">
                                <?= $activity['user_name'] ?> • <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3 class="chart-title">
                <i class="fas fa-bolt"></i>
                Hành Động Nhanh
            </h3>
            <div class="action-grid">
                <a href="manage_users.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div>Quản lý người dùng</div>
                        <small>Quản lý tài khoản</small>
                    </div>
                </a>
                <a href="manage_video.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div>
                        <div>Quản lý video</div>
                        <small>Quản lý nội dung</small>
                    </div>
                </a>
                <a href="manage_series.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-tv"></i>
                    </div>
                    <div>
                        <div>Quản lý phim bộ</div>
                        <small>Quản lý series</small>
                    </div>
                </a>
                <a href="add_series.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <div>Thêm phim bộ</div>
                        <small>Tạo series mới</small>
                    </div>
                </a>
                <a href="upload_episode.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div>
                        <div>Upload tập phim</div>
                        <small>Thêm tập mới</small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Content Chart
        const ctx = document.getElementById('contentChart').getContext('2d');
        const contentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Video', 'Tập phim', 'Phim bộ', 'Kênh', 'Playlist'],
                datasets: [{
                    data: [
                        <?= $stats['total_videos'] ?>,
                        <?= $stats['total_episodes'] ?>,
                        <?= $stats['total_series'] ?>,
                        <?= $stats['total_channels'] ?>,
                        <?= $stats['total_playlists'] ?>
                    ],
                    backgroundColor: [
                        '#3498db',
                        '#27ae60',
                        '#f39c12',
                        '#8e44ad',
                        '#e74c3c'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 1,
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>

</html>
