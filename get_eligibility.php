<?php
header("Content-Type: application/json");
include "db.php";

if (!isset($_GET['donor_id']) || empty($_GET['donor_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing donor_id"
    ]);
    exit;
}

$donor_id = $_GET['donor_id'];

$sql = "SELECT last_donation_date, next_eligible_date, status 
        FROM eligibility_status 
        WHERE donor_id = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "last_donation_date" => $row["last_donation_date"],
        "next_eligible_date" => $row["next_eligible_date"],
        "eligibility" => $row["status"]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No eligibility record found"
    ]);
}

$stmt->close();
$conn->close();
?>