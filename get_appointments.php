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

$sql = "SELECT 
    appointment_id,
    appointment_date,
    appointment_time,
    status,
    donation_center
FROM appointments
WHERE donor_id = ?
AND appointment_date >= CURDATE()
ORDER BY appointment_date ASC, appointment_time ASC
LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        "appointment_id"   => $row["appointment_id"],
        "appointment_date" => $row["appointment_date"],
        "appointment_time" => $row["appointment_time"],
        "status"           => $row["status"],
        "donation_center"  => $row["donation_center"]
    ];
}

echo json_encode([
    "status" => "success",
    "data"   => $appointments
]);

$stmt->close();
$conn->close();
?>