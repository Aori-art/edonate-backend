<?php
header("Content-Type: application/json");
include "db.php";

$city_code = $_GET['city_code'] ?? '';

if (empty($city_code)) {
    echo json_encode([
        "status" => "error",
        "message" => "city_code is required"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT brgyCode, brgyDesc FROM refbrgy WHERE citymunCode = ? ORDER BY brgyDesc ASC");
$stmt->bind_param("s", $city_code);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "code" => $row["brgyCode"],
        "name" => $row["brgyDesc"]
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);

$stmt->close();
$conn->close();
?>