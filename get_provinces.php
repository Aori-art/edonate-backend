<?php
header("Content-Type: application/json");
include "db.php";

$sql = "SELECT provCode, provDesc FROM refprovince ORDER BY provDesc ASC";
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "code" => $row["provCode"],
        "name" => $row["provDesc"]
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);

$conn->close();
?>