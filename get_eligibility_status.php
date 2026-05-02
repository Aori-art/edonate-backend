<?php
header("Content-Type: application/json");
require 'db.php'; // your DB connection

$donor_id = intval($_GET['donor_id'] ?? 0);
if (!$donor_id) {
    echo json_encode(["status" => "pending"]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT status FROM eligibility_status WHERE donor_id = ? ORDER BY eligibility_id DESC LIMIT 1"
);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["status" => $row["status"]]);
} else {
    echo json_encode(["status" => "pending"]); // no record = treat as pending
}
$stmt->close();