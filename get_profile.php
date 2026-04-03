<?php
header("Content-Type: application/json");
require "db.php";

$donor_id = $_GET['donor_id'] ?? '';

if (empty($donor_id)) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing donor_id"
    ]);
    exit;
}

// GET BLOOD TYPE + DONATIONS COUNT
$sql = "SELECT 
            d.donor_id,
            bt.blood_type,
            COUNT(dr.donation_id) AS total_donations
        FROM donors d
        LEFT JOIN blood_types bt ON d.blood_type_id = bt.blood_type_id
        LEFT JOIN donation_records dr ON d.donor_id = dr.donor_id
        WHERE d.donor_id = ?
        GROUP BY d.donor_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "blood_type" => $row["blood_type"] ?? "N/A",
        "total_donations" => $row["total_donations"] ?? 0
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
}

$stmt->close();
$conn->close();
?>