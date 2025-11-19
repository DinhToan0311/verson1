<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../loginphp/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập.");
}

$subject = '';
$message = '';
$link = '';
$fromEmail = $_SESSION['user']['email'] ?? '';
$resultSummary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $fromEmail = trim($_POST['from_email'] ?? $fromEmail);

    if ($subject === '' || $message === '') {
        $resultSummary = ['ok' => false, 'msg' => 'Tiêu đề và nội dung là bắt buộc.'];
    } else {
        $users = $conn->query("SELECT email FROM users WHERE email IS NOT NULL AND email <> ''");
        $emails = [];
        while ($row = $users->fetch_assoc()) {
            $emails[] = $row['email'];
        }

        if (empty($emails)) {
            $resultSummary = ['ok' => false, 'msg' => 'Không có email người dùng để gửi.'];
        } else {
            $batchSize = 30;
            $total = count($emails);
            $sent = 0;
            $failed = 0;

            $htmlBody = nl2br(htmlspecialchars($message));
            if ($link !== '') {
                $safeLink = htmlspecialchars($link);
                $htmlBody .= '<br><br><a href="' . $safeLink . '" target="_blank">Xem chi tiết</a>';
            }

            $boundary = md5(uniqid(time()));
            $headersBase = '';
            if ($fromEmail !== '') {
                $headersBase .= 'From: ' . $fromEmail . "\r\n";
                $headersBase .= 'Reply-To: ' . $fromEmail . "\r\n";
            }
            $headersBase .= "MIME-Version: 1.0\r\n";
            $headersBase .= "Content-Type: text/html; charset=UTF-8\r\n";

            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($emails, $i, $batchSize);

                $headers = $headersBase;
                $headers .= 'Bcc: ' . implode(',', $batch) . "\r\n";

                // Một số host yêu cầu có địa chỉ To hợp lệ
                $toAddress = $fromEmail !== '' ? $fromEmail : 'no-reply@localhost';

                $ok = @mail($toAddress, $subject, $htmlBody, $headers);
                if ($ok) {
                    $sent += count($batch);
                } else {
                    $failed += count($batch);
                }
            }

            $resultSummary = [
                'ok' => $failed === 0,
                'msg' => 'Đã xử lý gửi thông báo.',
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gửi thông báo tới tất cả người dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../logo.png" type="image/png">
    <style>
        body { background: #f5f8fb; }
        .main { margin-left: 15%; padding: 2rem; }
        .card { border: 1px solid #eef2f7; border-radius: 14px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0"><i class="fas fa-bullhorn"></i> Gửi thông báo</h3>
            <a href="javascript:history.back()" class="btn btn-light"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <?php if ($resultSummary): ?>
            <div class="alert <?= $resultSummary['ok'] ? 'alert-success' : 'alert-warning' ?>">
                <div class="fw-semibold mb-1"><?= htmlspecialchars($resultSummary['msg']) ?></div>
                <?php if (isset($resultSummary['total'])): ?>
                    <div>Tổng email: <strong><?= (int)$resultSummary['total'] ?></strong></div>
                    <div>Gửi thành công ước tính: <strong><?= (int)$resultSummary['sent'] ?></strong></div>
                    <div>Thất bại ước tính: <strong><?= (int)$resultSummary['failed'] ?></strong></div>
                <?php endif; ?>
                <div class="mt-2 text-muted" style="font-size:.9rem;">Lưu ý: Việc gửi email phụ thuộc cấu hình máy chủ (SMTP/mail()), bạn nên dùng email gốc cùng tên miền hosting để cải thiện tỉ lệ gửi.</div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email người gửi (tuỳ chọn, nên dùng email theo tên miền)</label>
                        <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($fromEmail) ?>" placeholder="admin@yourdomain.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($subject) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nội dung</label>
                        <textarea name="message" rows="8" class="form-control" required><?= htmlspecialchars($message) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Liên kết (tuỳ chọn)</label>
                        <input type="url" name="link" class="form-control" value="<?= htmlspecialchars($link) ?>" placeholder="https://...">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi thông báo</button>
                        <a href="nofi.php" class="btn btn-outline-secondary">Làm mới</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


