<?php
header("Content-Type: application/json");
require "db.php";
require "mail_config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email is required."
    ]);
    exit;
}

$checkUser = $conn->prepare("
    SELECT auth_id, is_verified
    FROM donor_authentication
    WHERE email = ?
");
$checkUser->bind_param("s", $email);
$checkUser->execute();
$result = $checkUser->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Account not found."
    ]);
    exit;
}

$user = $result->fetch_assoc();

if ((int)$user['is_verified'] === 1) {
    echo json_encode([
        "status" => "error",
        "message" => "This account is already verified."
    ]);
    exit;
}

$otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$insertOtp = $conn->prepare("
    INSERT INTO otp_codes (email, otp_code, expires_at, is_used)
    VALUES (?, ?, ?, 0)
");
$insertOtp->bind_param("sss", $email, $otp_code, $expires_at);
$insertOtp->execute();
$insertOtp->close();

$mailSent = sendOtpEmail($email, $otp_code);

if ($mailSent) {
    echo json_encode([
        "status" => "success",
        "message" => "A new OTP has been sent to your email."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to resend OTP."
    ]);
}
?>