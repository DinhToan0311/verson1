<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập.");
}

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Xây dựng query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(c.name LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query chính
$query = "
    SELECT c.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar,
           COUNT(s.id) as subscriber_count,
           COUNT(v.id) as video_count
    FROM channels c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN subscriptions s ON c.id = s.channel_id
    LEFT JOIN videos v ON c.user_id = v.uploaded_by
    $where_clause
    GROUP BY c.id
    ORDER BY $sort_by $sort_order
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($param_types)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$channels = $stmt->get_result();

// Đếm tổng số channels
$count_query = "
    SELECT COUNT(DISTINCT c.id) as total
    FROM channels c
    LEFT JOIN users u ON c.user_id = u.id
    $where_clause
";

$count_stmt = $conn->prepare($count_query);
if (!empty($where_conditions)) {
    $count_params = array_slice($params, 0, -2); // Bỏ limit và offset
    $count_param_types = substr($param_types, 0, -2);
    $count_stmt->bind_param($count_param_types, ...$count_params);
}
$count_stmt->execute();
$total_channels = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_channels / $limit);

// Thống kê
$stats = [];
$stats['total_channels'] = $conn->query("SELECT COUNT(*) as count FROM channels")->fetch_assoc()['count'];
$stats['total_subscriptions'] = $conn->query("SELECT COUNT(*) as count FROM subscriptions")->fetch_assoc()['count'];
$stats['total_videos'] = $conn->query("SELECT COUNT(*) as count FROM videos")->fetch_assoc()['count'];
$stats['avg_subscribers'] = $stats['total_channels'] > 0 ? round($stats['total_subscriptions'] / $stats['total_channels'], 1) : 0;

// Top channels
$top_channels = $conn->query("
    SELECT c.*, u.name as user_name, COUNT(s.id) as subscriber_count
    FROM channels c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN subscriptions s ON c.id = s.channel_id
    GROUP BY c.id
    ORDER BY subscriber_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Channels - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

        .back-btn {
            position: fixed;
            top: 2rem;
            left: 2rem;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.5);
            color: #3498db;
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(31, 38, 135, 0.5);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .stat-icon.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .stat-icon.info {
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            color: white;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .filter-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .filter-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            background: rgba(255, 255, 255, 1);
        }

        .btn {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #1f4e79);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        .channels-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            overflow: hidden;
        }

        .channel-card {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .channel-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .channel-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .channel-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
        }

        .channel-info {
            flex: 1;
        }

        .channel-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .channel-owner {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .channel-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-badge {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stat-badge.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .stat-badge.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .channel-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .channel-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .pagination-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .pagination {
            margin-bottom: 0;
            justify-content: center;
        }

        .page-link {
            border: none;
            color: #3498db;
            padding: 12px 20px;
            margin: 0 4px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: rgba(52, 152, 219, 0.1);
            color: #2980b9;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 1050;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            transition: all 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .notification.error {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .top-channels {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
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

        .top-subscribers {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }

            .back-btn {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .channel-header {
                flex-direction: column;
                text-align: center;
            }

            .channel-stats {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/admin_navbar.php'; ?>
    <?php include '../includes/admin_sidebar.php'; ?>

    <a href="javascript:history.back()" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Quay lại
    </a>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-broadcast-tower"></i>
                Quản lý Channels
            </h1>
            <p class="page-subtitle">Quản lý và theo dõi tất cả các kênh của người dùng</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_channels']) ?></div>
                <div class="stat-label">Tổng số kênh</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_subscriptions']) ?></div>
                <div class="stat-label">Tổng số đăng ký</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-video"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_videos']) ?></div>
                <div class="stat-label">Tổng số video</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?= $stats['avg_subscribers'] ?></div>
                <div class="stat-label">TB đăng ký/kênh</div>
            </div>
        </div>

        <!-- Top Channels -->
        <div class="top-channels">
            <h3 class="filter-title">
                <i class="fas fa-trophy"></i>
                Top Channels Phổ Biến
            </h3>
            <?php foreach ($top_channels as $index => $channel): ?>
                <div class="top-item">
                    <div class="top-rank rank-<?= $index < 3 ? $index + 1 : 'other' ?>">
                        <?= $index + 1 ?>
                    </div>
                    <div class="top-content-info">
                        <div class="top-title"><?= htmlspecialchars($channel['name']) ?></div>
                        <div class="top-meta"><?= htmlspecialchars($channel['user_name']) ?></div>
                    </div>
                    <div class="top-subscribers">
                        <?= number_format($channel['subscriber_count']) ?> đăng ký
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filter Container -->
        <div class="filter-container">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Bộ lọc và tìm kiếm
            </h3>
            <form method="get" class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-search"></i>
                            Tìm kiếm
                        </label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               class="form-control" placeholder="Tên kênh, chủ sở hữu...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-sort"></i>
                            Sắp xếp theo
                        </label>
                        <select name="sort" class="form-select">
                            <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Ngày tạo</option>
                            <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Tên kênh</option>
                            <option value="subscriber_count" <?= $sort_by === 'subscriber_count' ? 'selected' : '' ?>>Số đăng ký</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-sort-alpha-down"></i>
                            Thứ tự
                        </label>
                        <select name="order" class="form-select">
                            <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Giảm dần</option>
                            <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Tăng dần</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                        Tìm kiếm
                    </button>
                </div>
            </form>
        </div>

        <!-- Channels List -->
        <div class="channels-container">
            <?php if ($channels->num_rows > 0): ?>
                <?php while ($channel = $channels->fetch_assoc()): ?>
                    <div class="channel-card">
                        <div class="channel-header">
                            <img src="<?= $channel['avatar'] ?: $channel['user_avatar'] ?: 'https://via.placeholder.com/60x60?text=CH' ?>" 
                                 alt="Avatar" class="channel-avatar">
                            <div class="channel-info">
                                <div class="channel-name"><?= htmlspecialchars($channel['name']) ?></div>
                                <div class="channel-owner">
                                    <i class="fas fa-user"></i>
                                    <?= htmlspecialchars($channel['user_name']) ?> (<?= htmlspecialchars($channel['user_email']) ?>)
                                </div>
                            </div>
                        </div>
                        
                        <div class="channel-stats">
                            <span class="stat-badge">
                                <i class="fas fa-users"></i>
                                <?= number_format($channel['subscriber_count']) ?> đăng ký
                            </span>
                            <span class="stat-badge success">
                                <i class="fas fa-video"></i>
                                <?= number_format($channel['video_count']) ?> video
                            </span>
                            <span class="stat-badge warning">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y', strtotime($channel['created_at'])) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($channel['description'])): ?>
                            <div class="channel-description">
                                <?= htmlspecialchars($channel['description']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="channel-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewChannel(<?= $channel['id'] ?>)">
                                <i class="fas fa-eye"></i>
                                Xem chi tiết
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteChannel(<?= $channel['id'] ?>, '<?= htmlspecialchars($channel['name']) ?>')">
                                <i class="fas fa-trash"></i>
                                Xóa kênh
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-broadcast-tower"></i>
                    <h4>Không tìm thấy kênh nào</h4>
                    <p>Không có kênh nào phù hợp với tiêu chí tìm kiếm của bạn.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort_by ?>&order=<?= $sort_order ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Notification system
        function showNotification(message, type = 'success') {
            const notification = $(`
                <div class="notification ${type} show">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    ${message}
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function viewChannel(channelId) {
            // Điều hướng sang trang chi tiết kênh
            window.location.href = `sub.php?id=${channelId}`;
        }

        function deleteChannel(channelId, channelName) {
            if (!confirm(`Bạn có chắc chắn muốn xóa kênh "${channelName}"?\n\n⚠️ CẢNH BÁO: Tất cả video và đăng ký của kênh này cũng sẽ bị xóa!`)) return;
            
            // AJAX request để xóa kênh
            $.post('delete.php', {
                type: 'channel',
                id: channelId
            }, function(res) {
                if (res.trim() === 'success') {
                    showNotification('Đã xóa kênh thành công!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Xóa kênh thất bại!', 'error');
                }
            }).fail(function() {
                showNotification('Lỗi kết nối máy chủ!', 'error');
            });
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (window.location.pathname.includes('aoi.php')) {
                // Optionally refresh without full page reload
            }
        }, 30000);
    </script>
</body>

</html>
