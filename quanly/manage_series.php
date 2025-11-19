<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập.");
}

// Phân trang tập phim
$limit = 10;
$page_episode = isset($_GET['page_episode']) ? max(1, (int)$_GET['page_episode']) : 1;
$offset_episode = ($page_episode - 1) * $limit;

// Lọc theo series
$filter_series = $_GET['series'] ?? '';

// Danh sách series để đổ dropdown
$keyword = $_GET['keyword'] ?? '';
if (!empty($keyword)) {
    $stmt_series = $conn->prepare("SELECT id, title FROM series WHERE title LIKE ? ORDER BY title ASC");
    $like_keyword = '%' . $keyword . '%';
    $stmt_series->bind_param("s", $like_keyword);
    $stmt_series->execute();
    $series_list = $stmt_series->get_result();
} else {
    $series_list = $conn->query("SELECT id, title FROM series ORDER BY title ASC");
}

// Lấy danh sách tập (nếu có chọn phim)
$episodes = [];
$total_episode = 0;
if (!empty($filter_series)) {
    $filter_series = (int)$filter_series;

    // ✅ Lấy trực tiếp từ bảng episodes, không còn JOIN
    $stmt = $conn->prepare("
        SELECT * FROM episodes
        WHERE series_id = ?
        ORDER BY id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $filter_series, $limit, $offset_episode);
    $stmt->execute();
    $episodes = $stmt->get_result();

    // Đếm tổng số tập
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM episodes WHERE series_id = ?");
    $count_stmt->bind_param("i", $filter_series);
    $count_stmt->execute();
    $total_episode = $count_stmt->get_result()->fetch_assoc()['total'];
}
$total_page_episode = ceil($total_episode / $limit);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tập phim - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        :root {
            --primary: #1a5da0;
            --primary-dark: #154b85;
            --primary-light: #2169b5;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --gray-light: #f8f9fa;
            --gray: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #E8F4FD 0%, #F0F8FF 50%, #E6F3FF 100%);
            min-height: 100vh;
            color: var(--secondary);
            line-height: 1.6;
        }

        .main-container {
            margin-left: 240px;
            padding: 2rem;
            min-height: 100vh;
            transition: var(--transition);
        }

        body.sidebar-collapsed .main-container {
            margin-left: 0;
        }



        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255, 255, 255, 0.18);
            animation: fadeIn 0.5s ease-out;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title i {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }

        .page-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
            font-weight: 400;
            opacity: 0.8;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .form-control,
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus,
        .form-select:focus {
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

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.05);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            border-color: rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }

        .video-preview {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .views-badge {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        .btn-outline-danger {
            border: 2px solid #e74c3c;
            color: #e74c3c;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
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
            border-radius: var(--border-radius);
            color: white;
            font-weight: 500;
            box-shadow: var(--box-shadow);
            transform: translateX(400px);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 300px;
        }

        .notification.show {
            transform: translateX(0);
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
        }

        .notification.error {
            background: linear-gradient(135deg, var(--danger), #c0392b);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(400px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .page-subtitle {
                font-size: 1rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .filter-container .row {
                flex-direction: column;
            }

            .filter-container .col-md-4,
            .filter-container .col-md-6,
            .filter-container .col-md-2 {
                width: 100%;
                margin-bottom: 1rem;
            }

            .table-container {
                padding: 1rem;
            }

            .notification {
                top: auto;
                bottom: 1rem;
                right: 1rem;
                left: 1rem;
                transform: translateY(100px);
            }

            .notification.show {
                transform: translateY(0);
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 1.5rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .table td,
            .table th {
                padding: 0.75rem;
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
                <i class="fas fa-film"></i>
                Quản lý tập phim
            </h1>
            <p class="page-subtitle">Quản lý và theo dõi tất cả các tập phim trong hệ thống</p>
        </div>

        <?php
        // Thống kê tổng quan
        $total_series = $conn->query("SELECT COUNT(*) as count FROM series")->fetch_assoc()['count'];
        $total_episodes = $conn->query("SELECT COUNT(*) as count FROM episodes")->fetch_assoc()['count'];
        $total_views = $conn->query("SELECT SUM(views) as total FROM episodes")->fetch_assoc()['total'] ?? 0;
        $avg_episodes = $total_series > 0 ? round($total_episodes / $total_series, 1) : 0;
        ?>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-tv"></i>
                </div>
                <div class="stat-number"><?= number_format($total_series) ?></div>
                <div class="stat-label">Tổng số phim bộ</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-number"><?= number_format($total_episodes) ?></div>
                <div class="stat-label">Tổng số tập phim</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?= number_format($total_views) ?></div>
                <div class="stat-label">Tổng lượt xem</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?= $avg_episodes ?></div>
                <div class="stat-label">TB tập/phim</div>
            </div>
        </div>

        <!-- Filter Container -->
        <div class="filter-container">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Bộ lọc và tìm kiếm
            </h3>
            <form method="get">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-search"></i>
                                Tìm theo tên phim bộ
                            </label>
                            <input type="text" name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                                class="form-control" placeholder="Nhập từ khóa tìm kiếm...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-list"></i>
                                Chọn phim bộ
                            </label>
                            <select class="form-select" name="series" onchange="this.form.submit()">
                                <option value="">-- Chọn phim bộ để xem tập --</option>
                                <?php while ($s = $series_list->fetch_assoc()): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($filter_series == $s['id']) ? 'selected' : '' ?>>
                                        [ID: <?= $s['id'] ?>] <?= htmlspecialchars($s['title']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (!empty($filter_series)): ?>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger w-100" id="delete-series" data-id="<?= $filter_series ?>">
                                <i class="fas fa-trash"></i>
                                Xóa phim bộ
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Episodes Table -->
        <div class="table-container">
            <?php if (!empty($filter_series) && $episodes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-play"></i> Tên tập</th>
                                <th><i class="fas fa-eye"></i> Lượt xem</th>
                                <th><i class="fas fa-video"></i> Video</th>
                                <th><i class="fas fa-cogs"></i> Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ep = $episodes->fetch_assoc()): ?>
                                <tr id="episode-<?= $ep['id'] ?>">
                                    <td>
                                        <span class="badge bg-primary">#<?= $ep['id'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ep['title']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="views-badge">
                                            <i class="fas fa-eye"></i>
                                            <?= number_format($ep['views'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($ep['video_path'])): ?>
                                            <video class="video-preview" controls preload="metadata">
                                                <source src="<?= htmlspecialchars($ep['video_path']) ?>" type="video/mp4">
                                                Trình duyệt không hỗ trợ video.
                                            </video>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Chưa có video
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-episode" data-id="<?= $ep['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (!empty($filter_series)): ?>
                <div class="empty-state">
                    <i class="fas fa-film"></i>
                    <h4>Chưa có tập nào</h4>
                    <p>Phim bộ này chưa có tập phim nào được upload.</p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h4>Chọn phim bộ</h4>
                    <p>Vui lòng chọn một phim bộ từ danh sách để xem các tập phim.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_page_episode > 1): ?>
            <div class="pagination-container">
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_page_episode; $i++): ?>
                            <li class="page-item <?= ($i == $page_episode) ? 'active' : '' ?>">
                                <a class="page-link" href="?series=<?= $filter_series ?>&page_episode=<?= $i ?>">
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

        $(document).ready(function() {
            // Delete episode functionality
            $('.btn-delete-episode').click(function() {
                const id = $(this).data('id');
                const episodeTitle = $(this).closest('tr').find('td:nth-child(2) strong').text();

                if (!confirm(`Bạn có chắc chắn muốn xóa tập "${episodeTitle}"?`)) return;

                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang xóa...').prop('disabled', true);

                $.post('delete.php', {
                    type: 'episode',
                    id: id
                }, function(res) {
                    if (res.trim() === 'success') {
                        $('#episode-' + id).fadeOut(300, function() {
                            $(this).remove();
                        });
                        showNotification('Đã xóa tập phim thành công!', 'success');
                    } else {
                        showNotification('Xóa tập phim thất bại!', 'error');
                        $btn.html(originalText).prop('disabled', false);
                    }
                }).fail(function() {
                    showNotification('Lỗi kết nối máy chủ!', 'error');
                    $btn.html(originalText).prop('disabled', false);
                });
            });

            // Delete series functionality
            $('#delete-series').click(function() {
                const seriesId = $(this).data('id');

                if (!confirm('Bạn có chắc chắn muốn xóa phim bộ này?\n\n⚠️ CẢNH BÁO: Tất cả tập phim trong phim bộ này cũng sẽ bị xóa vĩnh viễn!')) return;

                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang xóa...').prop('disabled', true);

                $.post('delete.php', {
                    type: 'series',
                    id: seriesId
                }, function(res) {
                    if (res.trim() === 'success') {
                        showNotification('Đã xóa phim bộ thành công!', 'success');
                        setTimeout(() => {
                            window.location.href = 'manage_series.php';
                        }, 1500);
                    } else {
                        showNotification('Xóa phim bộ thất bại!', 'error');
                        $btn.html(originalText).prop('disabled', false);
                    }
                }).fail(function() {
                    showNotification('Lỗi kết nối máy chủ!', 'error');
                    $btn.html(originalText).prop('disabled', false);
                });
            });

            // Add loading animation to form submission
            $('form').on('submit', function() {
                const $submitBtn = $(this).find('button[type="submit"]');
                if ($submitBtn.length) {
                    $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang tải...').prop('disabled', true);
                }
            });

            // Add hover effects to table rows
            $('.table tbody tr').hover(
                function() {
                    $(this).addClass('table-hover-effect');
                },
                function() {
                    $(this).removeClass('table-hover-effect');
                }
            );

            // Auto-refresh stats every 30 seconds
            setInterval(function() {
                if (window.location.pathname.includes('manage_series.php')) {
                    // Optionally refresh stats without full page reload
                    // This could be implemented with AJAX if needed
                }
            }, 30000);
        });
    </script>
</body>

</html>