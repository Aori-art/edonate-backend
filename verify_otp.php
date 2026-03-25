<?php
header("Content-Type: application/json");
require "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}

$email = trim($_POST['email'] ?? '');
$otp   = trim($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and OTP are required."
    ]);
    exit;
}

$getOtp = $conn->prepare("
    SELECT otp_id, expires_at, is_used
    FROM otp_codes
    WHERE email = ? AND otp_code = ?
    ORDER BY otp_id DESC
    LIMIT 1
");
$getOtp->bind_param("ss", $email, $otp);
$getOtp->execute();
$result = $getOtp->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid OTP."
    ]);
    exit;
}

$row = $result->fetch_assoc();
$otp_id = $row['otp_id'];
$expires_at = $row['expires_at'];
$is_used = $row['is_used'];

if ((int)$is_used === 1) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP has already been used."
    ]);
    exit;
}

if (strtotime($expires_at) < time()) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP has expired."
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $markOtpUsed = $conn->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ?");
    $markOtpUsed->bind_param("i", $otp_id);
    $markOtpUsed->execute();
    $markOtpUsed->close();

    $verifyAccount = $conn->prepare("
        UPDATE donor_authentication
        SET is_verified = 1, verified_at = NOW()
        WHERE email = ?
    ");
    $verifyAccount->bind_param("s", $email);
    $verifyAccount->execute();
    $verifyAccount->close();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Email verified successfully. You may now log in."
    ]);
} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => "Verification failed."
    ]);
}
?>