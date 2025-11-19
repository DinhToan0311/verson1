<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập.");
}

$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$type = $_GET['type'] ?? 'all'; // uploads|subscriptions|comments|watch_later|views
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$q = trim($_GET['q'] ?? '');

// Validate dates
$from_date = $from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from . ' 00:00:00' : null;
$to_date = $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to . ' 23:59:59' : null;

// Build activity union query
$filters_sql = [];
$params = [];
$types = '';

if ($from_date) {
    $filters_sql[] = "activity_time >= ?";
    $params[] = $from_date;
    $types .= 's';
}
if ($to_date) {
    $filters_sql[] = "activity_time <= ?";
    $params[] = $to_date;
    $types .= 's';
}
if ($q !== '') {
    $filters_sql[] = "(LOWER(activity_title) LIKE ? OR LOWER(activity_meta) LIKE ?)";
    $params[] = '%' . strtolower($q) . '%';
    $params[] = '%' . strtolower($q) . '%';
    $types .= 'ss';
}

// Build per-type selects
$select_uploads = "SELECT 'uploads' AS activity_type, v.upload_date AS activity_time, v.id AS ref_id,
    CONCAT('Tải lên video: ', COALESCE(v.title, 'Không tiêu đề')) AS activity_title,
    CONCAT('Bởi user #', v.uploaded_by) AS activity_meta
  FROM videos v";

$select_subs = "SELECT 'subscriptions' AS activity_type, s.subscribed_at AS activity_time, s.id AS ref_id,
    CONCAT('Đăng ký kênh #', s.channel_id) AS activity_title,
    CONCAT('Người đăng ký: user #', s.subscriber_id) AS activity_meta
  FROM subscriptions s";

$select_comments = "SELECT 'comments' AS activity_type, c.created_at AS activity_time, c.id AS ref_id,
    CONCAT('Bình luận của user #', COALESCE(c.user_id, 0)) AS activity_title,
    LEFT(c.content, 120) AS activity_meta
  FROM comments c";

$select_watch_later = "SELECT 'watch_later' AS activity_type, w.added_at AS activity_time, w.id AS ref_id,
    CONCAT('Thêm vào xem sau: video #', w.video_id) AS activity_title,
    CONCAT('Bởi user #', w.user_id) AS activity_meta
  FROM watch_later w";

$select_views = "SELECT 'views' AS activity_type, vl.viewed_at AS activity_time, vl.id AS ref_id,
    CONCAT('Xem series #', vl.series_id) AS activity_title,
    '' AS activity_meta
  FROM views_log vl";

$union_parts = [];
if ($type === 'all' || $type === 'uploads') {
    $union_parts[] = $select_uploads;
}
if ($type === 'all' || $type === 'subscriptions') {
    $union_parts[] = $select_subs;
}
if ($type === 'all' || $type === 'comments') {
    $union_parts[] = $select_comments;
}
if ($type === 'all' || $type === 'watch_later') {
    $union_parts[] = $select_watch_later;
}
if ($type === 'all' || $type === 'views') {
    $union_parts[] = $select_views;
}

$union_sql = implode("\nUNION ALL\n", $union_parts);

$where_clause = '';
if (!empty($filters_sql)) {
    $where_clause = 'WHERE ' . implode(' AND ', $filters_sql);
}

$activity_sql = "SELECT * FROM (\n$union_sql\n) AS activities $where_clause ORDER BY activity_time DESC LIMIT ? OFFSET ?";
$params_with_page = $params;
$types_with_page = $types . 'ii';
$params_with_page[] = $limit;
$params_with_page[] = $offset;

$stmt = $conn->prepare($activity_sql);
if (!empty($types)) {
    $stmt->bind_param($types_with_page, ...$params_with_page);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count total for pagination
$count_sql = "SELECT COUNT(*) AS total FROM (\n$union_sql\n) AS activities $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = (int)ceil($total_rows / $limit);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật ký hoạt động</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        :root {
            --primary-light: #e3f2fd;
            --primary: #2196f3;
            --primary-dark: #1976d2;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --text-primary: #2c3e50;
            --text-secondary: #64748b;
        }

        body {
            background: var(--gray-100);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .main {
            margin-left: 15%;
            padding: 2rem;
            max-width: 1400px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            background: var(--white);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .filter {
            background: linear-gradient(135deg, var(--primary-light), var(--white));
        }

        .filter .form-control,
        .filter .form-select {
            border-radius: 12px;
            border: 1px solid var(--gray-300);
            padding: 0.6rem 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .filter .form-control:focus,
        .filter .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.15);
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .activity-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            margin: 8px 0;
            border-radius: 12px;
            transition: background-color 0.2s;
        }

        .activity-item:hover {
            background: var(--gray-100);
        }

        .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .i-uploads {
            background: linear-gradient(135deg, #64b5f6, #2196f3);
        }

        .i-subscriptions {
            background: linear-gradient(135deg, #81c784, #4caf50);
        }

        .i-comments {
            background: linear-gradient(135deg, #ba68c8, #9c27b0);
        }

        .i-watch_later {
            background: linear-gradient(135deg, #ffb74d, #ff9800);
        }

        .i-views {
            background: linear-gradient(135deg, #90a4ae, #607d8b);
        }

        .text-meta {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .pagination {
            gap: 4px;
        }

        .page-link {
            border-radius: 8px;
            color: var(--primary);
            border: 1px solid var(--gray-200);
            padding: 0.5rem 1rem;
        }

        .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }

        h3 {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .btn-light {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
        }

        .btn-light:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }
    </style>
</head>

<body>
    <?php include '../includes/admin_navbar.php'; ?>
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0"><i class="fas fa-list-check"></i> Nhật ký hoạt động</h3>
            <a href="javascript:history.back()" class="btn btn-light"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <div class="card filter mb-3">
            <div class="card-body">
                <form class="row g-2" method="get">
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="uploads" <?= $type === 'uploads' ? 'selected' : '' ?>>Tải lên</option>
                            <option value="subscriptions" <?= $type === 'subscriptions' ? 'selected' : '' ?>>Đăng ký</option>
                            <option value="comments" <?= $type === 'comments' ? 'selected' : '' ?>>Bình luận</option>
                            <option value="watch_later" <?= $type === 'watch_later' ? 'selected' : '' ?>>Xem sau</option>
                            <option value="views" <?= $type === 'views' ? 'selected' : '' ?>>Lượt xem series</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control" placeholder="Từ ngày"></div>
                    <div class="col-md-2"><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control" placeholder="Đến ngày"></div>
                    <div class="col-md-3"><input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Tìm tiêu đề/nội dung..."></div>
                    <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Lọc</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $a): ?>
                        <div class="activity-item">
                            <div class="icon i-<?= htmlspecialchars($a['activity_type']) ?>">
                                <?php if ($a['activity_type'] === 'uploads'): ?><i class="fas fa-upload"></i><?php endif; ?>
                                <?php if ($a['activity_type'] === 'subscriptions'): ?><i class="fas fa-user-plus"></i><?php endif; ?>
                                <?php if ($a['activity_type'] === 'comments'): ?><i class="fas fa-comment"></i><?php endif; ?>
                                <?php if ($a['activity_type'] === 'watch_later'): ?><i class="fas fa-clock"></i><?php endif; ?>
                                <?php if ($a['activity_type'] === 'views'): ?><i class="fas fa-eye"></i><?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= htmlspecialchars($a['activity_title']) ?></div>
                                <div class="text-meta"><?= htmlspecialchars($a['activity_meta']) ?></div>
                            </div>
                            <div class="text-meta" style="min-width:160px; text-align:right;">
                                <?= date('d/m/Y H:i', strtotime($a['activity_time'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">Không có hoạt động nào phù hợp.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&q=<?= urlencode($q) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>