<?php
session_start();

if (!isset($_POST['otp_input'])) {
    header("Location: verify_otp.php?error=missing_otp");
    exit;
}

$userOtp = $_POST['otp_input'];
$realOtp = $_SESSION['otp'] ?? null;
$otpExpiry = $_SESSION['otp_expiry'] ?? 0;

if (time() > $otpExpiry) {
    session_destroy();
    header("Location: verify_otp.php?error=expired");
    exit;
}

if ($userOtp == $realOtp) {
    $_SESSION['verified_email'] = $_SESSION['otp_email'];
    header("Location: reset_password.php");
    exit;
} else {
    header("Location: verify_otp.php?error=invalid");
    exit;
}
