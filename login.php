<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require "db.php";

// Get POST data
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required"
    ]);
    exit;
}

// Get user with verification status
$sql = "SELECT 
            da.auth_id, 
            da.donor_id, 
            da.email, 
            da.password, 
            da.is_verified,
            d.first_name, 
            d.last_name
        FROM donor_authentication da
        LEFT JOIN donors d ON da.donor_id = d.donor_id
        WHERE da.email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// 🔒 CHECK IF VERIFIED FIRST
if ((int)$user['is_verified'] !== 1) {
    echo json_encode([
        "status" => "error",
        "message" => "Please verify your email first."
    ]);
    exit;
}

// Verify password
if (
    password_verify($password, $user['password']) ||
    $password === $user['password'] // temporary fallback
) {
    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "donor_id" => $user['donor_id'],
        "user_name" => $fullName,
        "email" => $user['email']
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
}

$stmt->close();
$conn->close();
?>