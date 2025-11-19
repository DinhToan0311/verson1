<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendors/autoload.php';
require __DIR__ . '/../loginphp/db.php';


$email = trim($_POST['email'] ?? '');

if ($email) {
    // Kiểm tra email tồn tại
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Gán lỗi vào session và chuyển hướng về lại form
        $_SESSION['error'] = "Email không tồn tại.";
        header("Location: forgot_password.php");
        exit;
    }

    // Tạo mã OTP
    $otp = rand(1000, 9999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expiry'] = time() + 60; // 1 phút

    // Gửi email OTP
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'buidinhtoan2311@gmail.com';
        $mail->Password = 'nvzg zwms yyfk rqrq'; // App password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('buidinhtoan2311@gmail.com', 'Rainbow-zz');
        $mail->addAddress($email);
        $mail->Subject = 'Your OTP code!';
        $mail->Body = "Xin chào,\n\nMã xác nhận OTP của bạn là: $otp\nMã có hiệu lực trong vòng 1 phút.\n\nTrân trọng,\nRainbow-zz";

        $mail->send();

        // Thành công, chuyển sang trang xác minh
        header("Location: verify_otp.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
        header("Location: forgot_password.php");
        exit;
    }
} else {
    // Trường hợp gửi rỗng
    $_SESSION['error'] = "Vui lòng nhập email.";
    header("Location: forgot_password.php");
    exit;
}
