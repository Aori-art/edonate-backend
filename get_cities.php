<?php
header("Content-Type: application/json");
include "db.php";

$province_code = $_GET['province_code'] ?? '';

if (empty($province_code)) {
    echo json_encode([
        "status" => "error",
        "message" => "province_code is required"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT citymunCode, citymunDesc FROM refcitymun WHERE provCode = ? ORDER BY citymunDesc ASC");
$stmt->bind_param("s", $province_code);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "code" => $row["citymunCode"],
        "name" => $row["citymunDesc"]
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);

$stmt->close();
$conn->close();
?>