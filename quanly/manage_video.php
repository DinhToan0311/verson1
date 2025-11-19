<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập.");
}

// PHÂN TRANG
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Lọc video
$filter_uploader = $_GET['uploader'] ?? '';
$filter_sort = $_GET['sort'] ?? '';

// Danh sách người đăng (sử dụng tên thật)
$uploader_result = $conn->query("
    SELECT DISTINCT u.id, u.name 
    FROM users u 
    INNER JOIN videos v ON v.uploaded_by = u.id
");

// Lấy video có lọc + phân trang
$sql = "
    SELECT v.*, u.name AS uploader_name
    FROM videos v
    LEFT JOIN users u ON v.uploaded_by = u.id
";
$conditions = [];

if (!empty($filter_uploader)) {
    $conditions[] = "v.uploaded_by = '" . $conn->real_escape_string($filter_uploader) . "'";
}
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
if ($filter_sort === 'views_desc') {
    $sql .= " ORDER BY v.views DESC";
} elseif ($filter_sort === 'views_asc') {
    $sql .= " ORDER BY v.views ASC";
} else {
    $sql .= " ORDER BY v.id DESC";
}
$sql .= " LIMIT $limit OFFSET $offset";
$video_result = $conn->query($sql);

// Tổng số video để phân trang
$total_video_query = "SELECT COUNT(*) AS total FROM videos v";
if (!empty($conditions)) {
    $total_video_query .= " WHERE " . implode(" AND ", $conditions);
}
$total_video = $conn->query($total_video_query)->fetch_assoc()['total'];

$total_pages = ceil($total_video / $limit);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Video - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Light-blue + white theme */
            --primary: #cfeefd;
            /* very light blue */
            --primary-dark: #6fb8e6;
            /* medium blue */
            --primary-light: #e9f9ff;
            /* pale blue */
            --secondary: #1f3f5a;
            /* dark text color */
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --gray-light: #ffffff;
            --gray: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 8px 32px rgba(31, 38, 135, 0.06);
            --transition: all 0.25s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

        .header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 28px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(70, 130, 180, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(70, 130, 180, 0.2);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-weight: 500;
        }

        .filters-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(70, 130, 180, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(111, 184, 230, 0.12);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--secondary);
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(70, 130, 180, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--secondary);
            border: none;
            padding: 20px 15px;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 20px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: linear-gradient(90deg, rgba(111, 184, 230, 0.06), rgba(233, 249, 255, 0.06));
            transform: scale(1.01);
        }

        .video-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Thumbnail image (if exists) */
        .video-thumbnail {
            width: 120px;
            height: 68px;
            border-radius: var(--border-radius);
            object-fit: cover;
            display: block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Fallback thumbnail when no image available */
        .video-thumbnail-default {
            width: 120px;
            height: 68px;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .video-details h6 {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .video-details small {
            color: #666;
        }

        .views-badge {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .date-badge {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--secondary);
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(231, 76, 60, 0.3);
        }

        .btn-edit {
            background: linear-gradient(135deg, #87CEEB, #B0E0E6);
            color: #2C3E50;
            margin-right: 8px;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(135, 206, 235, 0.3);
        }

        .pagination-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            margin-top: 30px;
            box-shadow: 0 8px 32px rgba(70, 130, 180, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .pagination {
            justify-content: center;
            margin: 0;
        }

        .page-link {
            background: transparent;
            border: 2px solid #E8F4FD;
            color: #4682B4;
            padding: 10px 15px;
            margin: 0 3px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #4682B4;
            color: white;
            border-color: #4682B4;
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #4682B4, #5F9EA0);
            border-color: #4682B4;
            color: white;
        }



        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
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

        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .table-container {
                overflow-x: auto;
            }


        }
    </style>
</head>

<body>
    <?php include '../includes/admin_navbar.php'; ?>
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-container fade-in">
        <!-- Header Section -->
        <div class="header">
            <h1>
                <i class="fas fa-video"></i>
                Quản lý Video
            </h1>
            <p>Quản lý và theo dõi tất cả video trong hệ thống</p>
        </div>

        <!-- Statistics Section -->
        <div class="stats-container">
            <?php
            // Tính tổng lượt xem
            $totalViews = $conn->query("SELECT SUM(views) AS total FROM videos")->fetch_assoc()['total'] ?? 0;
            ?>

            <div class="stat-card">
                <i class="fas fa-video"></i>
                <h3><?= $total_video ?></h3>
                <p>Tổng Video</p>
            </div>

            <div class="stat-card">
                <i class="fas fa-eye"></i>
                <h3><?= number_format($totalViews) ?></h3>
                <p>Tổng lượt xem</p>
            </div>

            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?= $uploader_result->num_rows ?></h3>
                <p>Người đăng</p>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-container">
            <form method="get">
                <input type="hidden" name="page" value="<?= $page ?>">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-user"></i> Lọc theo người đăng</label>
                        <select name="uploader">
                            <option value="">-- Tất cả người đăng --</option>
                            <?php
                            $uploader_result->data_seek(0); // Reset pointer
                            while ($uploader = $uploader_result->fetch_assoc()): ?>
                                <option value="<?= $uploader['id'] ?>" <?= ($filter_uploader == $uploader['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($uploader['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> Sắp xếp theo lượt xem</label>
                        <select name="sort">
                            <option value="">-- Mặc định (Mới nhất) --</option>
                            <option value="views_desc" <?= ($filter_sort == 'views_desc') ? 'selected' : '' ?>>Nhiều lượt xem nhất</option>
                            <option value="views_asc" <?= ($filter_sort == 'views_asc') ? 'selected' : '' ?>>Ít lượt xem nhất</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <table class="table" id="video-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-video"></i> Video</th>
                        <th><i class="fas fa-user"></i> Người đăng</th>
                        <th><i class="fas fa-eye"></i> Lượt xem</th>
                        <th><i class="fas fa-calendar"></i> Ngày đăng</th>
                        <th><i class="fas fa-cogs"></i> Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($video_result->num_rows > 0): ?>
                        <?php while ($video = $video_result->fetch_assoc()): ?>
                            <tr id="video-<?= $video['id'] ?>">
                                <td>
                                    <strong>#<?= $video['id'] ?></strong>
                                </td>
                                <td>
                                    <div class="video-info">
                                        <?php if (!empty($video['thumbnail'])): ?>
                                            <img
                                                class="video-thumbnail"
                                                src="<?= htmlspecialchars($video['thumbnail']) ?>"
                                                alt="Thumbnail for <?= htmlspecialchars($video['title']) ?>"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';" />
                                            <div class="video-thumbnail-default" style="display:none;">
                                                <i class="fas fa-play"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="video-thumbnail-default">
                                                <i class="fas fa-play"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="video-details">
                                            <h6><?= htmlspecialchars($video['title']) ?></h6>
                                            <small>ID: <?= $video['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="video-details">
                                        <h6><?= htmlspecialchars($video['uploader_name'] ?? 'Không xác định') ?></h6>
                                    </div>
                                </td>
                                <td>
                                    <span class="views-badge">
                                        <i class="fas fa-eye"></i>
                                        <?= number_format($video['views']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-badge">
                                        <?= date('d/m/Y', strtotime($video['upload_date'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="editVideo(<?= $video['id'] ?>)">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    <button class="btn-action btn-delete btn-delete-video" data-id="<?= $video['id'] ?>">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-video-slash"></i>
                                <h4>Không có video nào</h4>
                                <p>Không tìm thấy video nào phù hợp với bộ lọc của bạn.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&uploader=<?= urlencode($filter_uploader) ?>&sort=<?= $filter_sort ?>">
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
        $(document).ready(function() {
            // Enhanced delete video function
            $('.btn-delete-video').click(function() {
                const id = $(this).data('id');
                const videoTitle = $(this).closest('tr').find('.video-details h6').text();

                if (!confirm(`Bạn có chắc chắn muốn xóa video "${videoTitle}"?\n\nHành động này không thể hoàn tác!`)) {
                    return;
                }

                // Show loading state
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang xóa...').prop('disabled', true);

                $.post('delete.php', {
                    type: 'video',
                    id: id
                }, function(res) {
                    if (res === 'success') {
                        // Add fade out animation
                        $('#video-' + id).fadeOut(300, function() {
                            $(this).remove();
                        });

                        // Show success message
                        showNotification('Video đã được xóa thành công!', 'success');
                    } else {
                        alert('Xóa thất bại! Vui lòng thử lại.');
                        $btn.html(originalText).prop('disabled', false);
                    }
                }).fail(function() {
                    alert('Có lỗi xảy ra! Vui lòng thử lại.');
                    $btn.html(originalText).prop('disabled', false);
                });
            });

            // Edit video function
            window.editVideo = function(id) {
                // You can implement edit functionality here
                showNotification('Chức năng chỉnh sửa video ID: ' + id + ' sẽ được triển khai', 'info');
            };

            // Notification system
            function showNotification(message, type = 'info') {
                const notification = $(`
                    <div class="notification notification-${type}">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `);

                $('body').append(notification);

                setTimeout(() => {
                    notification.addClass('show');
                }, 100);

                setTimeout(() => {
                    notification.removeClass('show');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }

            // Add smooth animations
            $('.stat-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
            });

            // Add loading animation
            window.addEventListener('load', function() {
                document.body.classList.add('loaded');
            });
        });
    </script>

    <style>
        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            border-left-color: #2ecc71;
        }

        .notification-success i {
            color: #2ecc71;
        }

        .notification-error {
            border-left-color: #e74c3c;
        }

        .notification-error i {
            color: #e74c3c;
        }

        .notification-info {
            border-left-color: #3498db;
        }

        .notification-info i {
            color: #3498db;
        }

        /* Loading states */
        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Enhanced animations */
        .stat-card {
            animation: slideInUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

</body>

</html>