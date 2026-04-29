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

$sql = "SELECT donation_date
        FROM donation_records
        WHERE donor_id = ?
        ORDER BY donation_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

$donations = [];
$count = 0;

while ($row = $result->fetch_assoc()) {
    $count++;
    $donations[] = [
        "date" => $row["donation_date"],
        "count" => $count // cumulative count for line graph
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $donations
]);

$stmt->close();
$conn->close();
?>