<?php
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "db.php";

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON received",
        "raw" => $rawInput
    ]);
    exit;
}

if (
    empty($data['donor_id']) ||
    empty($data['appointment_date']) ||
    empty($data['appointment_time']) ||
    empty($data['donation_center'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing fields",
        "data" => $data
    ]);
    exit;
}

$donor_id = $data['donor_id'];
$date = $data['appointment_date'];
$time = $data['appointment_time'];
$center = $data['donation_center'];

$status = "pending";
$admin_id = null;

$sql = "INSERT INTO appointments 
(donor_id, appointment_date, appointment_time, status, admin_id, donation_center)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("isssis", $donor_id, $date, $time, $status, $admin_id, $center);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Booking successful"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Execute failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>