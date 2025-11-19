<?php
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập để xem yêu thích.");
}

$userId = $_SESSION['user_id'];

$sql = "SELECT v.id, v.title, v.filename, v.thumbnail, v.duration, v.views, v.upload_date, c.name AS channel_name, f.id AS fav_id
        FROM favorites f
        JOIN videos v ON f.video_id = v.id
        JOIN channels c ON v.uploaded_by = c.user_id
        WHERE f.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

function formatDuration($seconds)
{
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $secs);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Video Yêu Thích</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 20px;
        }

        .section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 10px;
            margin-left: 14%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        h2 {
            font-size: 24px;
            margin-bottom: 24px;
            color: #222;
        }

        .video-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            text-decoration: none;
            color: inherit;
            gap: 16px;
            background: #fff;
            padding: 14px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .video-item img {
            width: 180px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .video-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .video-info h3 {
            font-size: 16px;
            margin: 0 0 8px;
            color: #0066cc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .video-info p {
            font-size: 13px;
            color: #666;
            margin: 2px 0;
        }

        .video-duration {
            position: absolute;
            bottom: 8px;
            right: 12px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .remove-fav-btn {
            position: absolute;
            top: 10px;
            right: 12px;
            background: none;
            border: none;
            font-size: 16px;
            color: #888;
            cursor: pointer;
            display: none;
        }

        .video-item:hover .remove-fav-btn {
            display: block;
        }

        .remove-fav-btn:hover {
            color: red;
        }

        footer {
            margin-top: 50px;
            background: #f0f0f0;
            text-align: center;
            padding: 20px;
            color: #555;
            font-size: 14px;
            border-top: 1px solid #ddd;
        }

        /* ================= Responsive Mobile Support ================= */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .section {
                margin-left: 0;
                padding: 16px;
            }

            h2 {
                font-size: 20px;
                text-align: center;
                margin-bottom: 20px;
            }

            .video-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px;
                gap: 12px;
                position: relative;
            }

            .video-item img {
                width: 100%;
                height: auto;
                object-fit: cover;
                border-radius: 8px;
            }

            .video-info {
                width: 100%;
            }

            .video-info h3 {
                font-size: 16px;
                margin: 0 0 6px;
                white-space: normal;
            }

            .video-info p {
                font-size: 13px;
                line-height: 1.5;
                margin: 2px 0;
            }

            .video-duration {
                position: absolute;
                bottom: 10px;
                right: 16px;
                background: rgba(0, 0, 0, 0.7);
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 12px;
                color: white;
            }

            .remove-fav-btn {
                position: absolute;
                top: 10px;
                right: 12px;
                font-size: 18px;
                color: #888;
                background: none;
                border: none;
                cursor: pointer;
                display: block;
            }

            .remove-fav-btn:hover {
                color: red;
            }

            footer {
                font-size: 13px;
                padding: 15px;
                text-align: center;
                background: #f0f0f0;
                margin-top: 40px;
            }
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

    <div class="section">
        <h2>❤️ Video đã yêu thích</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $thumb = $row['thumbnail'] ?: 'default.jpg';
            ?>
                <div class="video-item" id="fav-<?= $row['fav_id'] ?>">
                    <a href="watch.php?id=<?= $row['id'] ?>" style="position: relative; display: inline-block;">
                        <img src="<?= htmlspecialchars($row['thumbnail']) ?>" class="video-thumb" alt="Thumbnail">

                        <?php if (!empty($row['duration'])): ?>
                            <div class="video-duration"><?= formatDuration($row['duration']) ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="video-info">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['channel_name']) ?></p>
                        <p><?= number_format($row['views']) ?> lượt xem • <?= date('d/m/Y', strtotime($row['upload_date'])) ?></p>
                    </div>
                    <button class="remove-fav-btn" onclick="removeFavorite(<?= $row['fav_id'] ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Bạn chưa yêu thích video nào.</p>
        <?php endif; ?>
    </div>
    <footer>
        © <?= date('Y') ?> MMG ToBe - @2025 - Thanks You!
    </footer>
    <script>
        function removeFavorite(favId) {
            if (!confirm("Bạn có chắc muốn xóa video này khỏi yêu thích?")) return;

            fetch("toggle_favorite.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "id=" + favId
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "OK") {
                        const item = document.getElementById("fav-" + favId);
                        if (item) item.remove();
                    } else {
                        alert("Lỗi khi xóa yêu thích.");
                    }
                });

        }
    </script>

</body>

</html>