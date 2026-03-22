<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

// Get POST data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required"
    ]);
    exit;
}

// Prepare SQL statement to prevent SQL injection
$sql = "SELECT da.auth_id, da.donor_id, da.email, da.password, 
               d.first_name, d.last_name, d.blood_type_id
        FROM donor_authentication da
        LEFT JOIN donors d ON da.donor_id = d.donor_id
        WHERE da.email = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (// allow plain text (temporary)
    password_verify($password, $user['password']) ||
    $password === $user['password'] 
) {//if (password_verify($password, $user['password'])) {
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "auth_id" => $user['auth_id'],
                "donor_id" => $user['donor_id'],
                "email" => $user['email']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email or password"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
}

$stmt->close();
$conn->close();
?>