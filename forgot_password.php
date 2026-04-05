<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

include "db.php";
require "mail_config.php";

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email is required"]);
    exit;
}

$stmt = $conn->prepare("SELECT donor_id FROM donor_authentication WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Email not found"]);
    exit;
}

$user = $result->fetch_assoc();
$donor_id = $user['donor_id'];

$token = bin2hex(random_bytes(32));
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

$insert = $conn->prepare("INSERT INTO donor_forget (donor_id, reset_token, token_expires, used) VALUES (?, ?, ?, 0)");
$insert->bind_param("iss", $donor_id, $token, $expires);

if (!$insert->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save reset token"
    ]);
    exit;
}

$resetLink = "http://192.168.1.8/edonate_api/reset_password.php?token=$token";

$resultMail = sendResetEmail($email, $resetLink);

if ($resultMail === true) {
    echo json_encode([
        "status" => "success",
        "message" => "Reset link sent to your email"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send email: $resultMail"
    ]);
}
?>