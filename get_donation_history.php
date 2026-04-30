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

$sql = "SELECT donation_date, blood_units, remarks
        FROM donation_records
        WHERE donor_id = ?
        ORDER BY donation_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);

$stmt->close();
$conn->close();
?>