<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Thiếu hoặc sai tham số id.');
}

$channel_id = (int)$_GET['id'];

// Lấy thông tin kênh + chủ sở hữu
$channel_query = $conn->prepare(
    "SELECT c.*, u.name AS user_name, u.email AS user_email, u.avatar AS user_avatar
     FROM channels c
     LEFT JOIN users u ON c.user_id = u.id
     WHERE c.id = ?"
);
$channel_query->bind_param('i', $channel_id);
$channel_query->execute();
$channel = $channel_query->get_result()->fetch_assoc();
if (!$channel) {
    die('Không tìm thấy kênh.');
}

// Thống kê: số đăng ký, số video
$stats_stmt = $conn->prepare(
    "SELECT 
        (SELECT COUNT(*) FROM subscriptions s WHERE s.channel_id = ?) AS subscriber_count,
        (SELECT COUNT(*) FROM videos v WHERE v.uploaded_by = ?) AS video_count"
);
$stats_stmt->bind_param('ii', $channel_id, $channel['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Thống kê mở rộng cho kênh
$ext_stmt = $conn->prepare(
    "SELECT
        (SELECT COALESCE(SUM(v.views), 0) FROM videos v WHERE v.uploaded_by = ?) AS total_views,
        (SELECT COUNT(*) FROM subscriptions s WHERE s.channel_id = ? AND s.subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_subs_30d,
        (SELECT COUNT(*) FROM comments c JOIN videos v2 ON c.video_id = v2.id WHERE v2.uploaded_by = ?) AS total_comments,
        (SELECT COUNT(*) FROM favorites f JOIN videos v3 ON f.video_id = v3.id WHERE v3.uploaded_by = ?) AS total_favorites,
        (SELECT COUNT(*) FROM watch_later w JOIN videos v4 ON w.video_id = v4.id WHERE v4.uploaded_by = ?) AS total_watch_later,
        (SELECT MAX(v.upload_date) FROM videos v WHERE v.uploaded_by = ?) AS last_upload_at"
);
$ext_stmt->bind_param('iiiiii', $channel['user_id'], $channel_id, $channel['user_id'], $channel['user_id'], $channel['user_id'], $channel['user_id']);
$ext_stmt->execute();
$ext = $ext_stmt->get_result()->fetch_assoc();

$avg_views_per_video = (!empty($stats['video_count']) && (int)$stats['video_count'] > 0)
    ? round(((int)$ext['total_views']) / (int)$stats['video_count'])
    : 0;

// Danh sách video gần đây của chủ kênh
$videos_stmt = $conn->prepare(
    "SELECT id, title, thumbnail, views, upload_date
     FROM videos
     WHERE uploaded_by = ?
     ORDER BY upload_date DESC
     LIMIT 12"
);
$videos_stmt->bind_param('i', $channel['user_id']);
$videos_stmt->execute();
$videos = $videos_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Danh sách subscribers
$subs_stmt = $conn->prepare(
    "SELECT u.id, u.name, u.email, u.avatar, s.subscribed_at
     FROM subscriptions s
     JOIN users u ON s.subscriber_id = u.id
     WHERE s.channel_id = ?
     ORDER BY s.subscribed_at DESC
     LIMIT 20"
);
$subs_stmt->bind_param('i', $channel_id);
$subs_stmt->execute();
$subscribers = $subs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết kênh - <?= htmlspecialchars($channel['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        body { background: #f5f8fb; }
        .banner { height: 220px; background-size: cover; background-position: center; border-radius: 16px; }
        .avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; margin-top: -50px; }
        .stat-pill { background: #eef5ff; color: #1f5fbf; border-radius: 999px; padding: 6px 12px; font-weight: 600; }
        .video-card { border: 1px solid #eef2f7; border-radius: 12px; overflow: hidden; background: #fff; }
        .video-thumb { width: 100%; aspect-ratio: 16/9; object-fit: cover; background: #ddd; }
        .subscriber-item { display: flex; gap: 12px; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f3f7; }
        .subscriber-item:last-child { border-bottom: 0; }
        .sub-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 16px; }
        .stat-card { background: #ffffff; border: 1px solid #eef2f7; border-radius: 12px; padding: 14px; }
        .stat-title { color: #6b7280; font-size: .9rem; margin-bottom: 6px; }
        .stat-value { font-size: 1.4rem; font-weight: 700; color: #111827; }
        .stat-icon { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; margin-right: 8px; color: #fff; }
        .bg-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .bg-green { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .bg-purple { background: linear-gradient(135deg, #8e44ad, #9b59b6); }
        .bg-orange { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .bg-pink { background: linear-gradient(135deg, #ff5e8a, #ff7aa2); }
        .bg-slate { background: linear-gradient(135deg, #64748b, #475569); }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="container" style="margin-left:15%; padding: 2rem;">
        <a href="javascript:history.back()" class="btn btn-light mb-3"><i class="fas fa-arrow-left"></i> Quay lại</a>

        <div class="banner" style="background-image:url('<?= $channel['banner'] ?: "https://via.placeholder.com/1200x300?text=Banner" ?>');"></div>

        <div class="d-flex align-items-center gap-3">
            <img class="avatar" src="<?= $channel['avatar'] ?: ($channel['user_avatar'] ?: 'https://via.placeholder.com/100x100?text=CH') ?>" alt="avatar">
            <div class="flex-grow-1">
                <h2 class="mb-1"><?= htmlspecialchars($channel['name']) ?></h2>
                <div class="text-muted">Chủ kênh: <?= htmlspecialchars($channel['user_name'] ?: 'Không rõ') ?> (<?= htmlspecialchars($channel['user_email'] ?: 'N/A') ?>)</div>
                <div class="d-flex gap-2 mt-2">
                    <span class="stat-pill"><i class="fas fa-users"></i> <?= number_format($stats['subscriber_count'] ?? 0) ?> đăng ký</span>
                    <span class="stat-pill"><i class="fas fa-video"></i> <?= number_format($stats['video_count'] ?? 0) ?> video</span>
                    <span class="stat-pill"><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($channel['created_at'])) ?></span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-blue"><i class="fas fa-eye"></i></div>
                            <div class="stat-title">Tổng lượt xem</div>
                        </div>
                        <div class="stat-value"><?= number_format((int)($ext['total_views'] ?? 0)) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-green"><i class="fas fa-user-plus"></i></div>
                            <div class="stat-title">ĐK mới 30 ngày</div>
                        </div>
                        <div class="stat-value"><?= number_format((int)($ext['new_subs_30d'] ?? 0)) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-purple"><i class="fas fa-comments"></i></div>
                            <div class="stat-title">Bình luận</div>
                        </div>
                        <div class="stat-value"><?= number_format((int)($ext['total_comments'] ?? 0)) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-orange"><i class="fas fa-heart"></i></div>
                            <div class="stat-title">Yêu thích</div>
                        </div>
                        <div class="stat-value"><?= number_format((int)($ext['total_favorites'] ?? 0)) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-pink"><i class="fas fa-clock"></i></div>
                            <div class="stat-title">Xem sau</div>
                        </div>
                        <div class="stat-value"><?= number_format((int)($ext['total_watch_later'] ?? 0)) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-slate"><i class="fas fa-chart-column"></i></div>
                            <div class="stat-title">TB view/video</div>
                        </div>
                        <div class="stat-value"><?= number_format($avg_views_per_video) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-1">
                            <div class="stat-icon bg-slate"><i class="fas fa-upload"></i></div>
                            <div class="stat-title">Tải lên gần nhất</div>
                        </div>
                        <div class="stat-value"><?= !empty($ext['last_upload_at']) ? date('d/m/Y H:i', strtotime($ext['last_upload_at'])) : '—' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($channel['description'])): ?>
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-align-left"></i> Giới thiệu</h5>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($channel['description'])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mt-4 g-3">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="fas fa-photo-film"></i> Video gần đây</h5>
                </div>
                <div class="row g-3">
                    <?php if (!empty($videos)): ?>
                        <?php foreach ($videos as $v): ?>
                            <div class="col-md-6">
                                <div class="video-card">
                                    <img class="video-thumb" src="<?= $v['thumbnail'] ?: 'https://via.placeholder.com/640x360?text=Thumbnail' ?>" alt="thumb">
                                    <div class="p-2">
                                        <div class="fw-semibold text-truncate" title="<?= htmlspecialchars($v['title'] ?: 'Không tiêu đề') ?>"><?= htmlspecialchars($v['title'] ?: 'Không tiêu đề') ?></div>
                                        <div class="text-muted" style="font-size: .9rem;">
                                            <i class="fas fa-eye"></i> <?= number_format((int)$v['views']) ?> lượt xem · <?= date('d/m/Y', strtotime($v['upload_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-light">Chưa có video nào.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Người đăng ký mới</h5>
                        <?php if (!empty($subscribers)): ?>
                            <?php foreach ($subscribers as $s): ?>
                                <div class="subscriber-item">
                                    <img class="sub-avatar" src="<?= $s['avatar'] ?: 'https://via.placeholder.com/72x72?text=U' ?>" alt="sub">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?= htmlspecialchars($s['name'] ?: 'Người dùng') ?></div>
                                        <div class="text-muted" style="font-size:.85rem;"><?= date('d/m/Y H:i', strtotime($s['subscribed_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-light mb-0">Chưa có người đăng ký.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


