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
    d.donor_id,
    d.first_name,
    d.middle_initial,
    d.last_name,
    d.suffix,
    d.birthdate,
    d.contact_number,
    l.street_address,
    l.barangay_name,
    l.city,
    l.province,
    da.email,
    bt.blood_type,
    COUNT(dr.donation_id) AS total_donations,
    MAX(dr.donation_date) AS last_donation
FROM donors d
LEFT JOIN donor_authentication da ON d.donor_id = da.donor_id
LEFT JOIN blood_types bt ON d.blood_type_id = bt.blood_type_id
LEFT JOIN locations l ON d.location_id = l.location_id
LEFT JOIN donation_records dr ON d.donor_id = dr.donor_id
WHERE d.donor_id = ?
GROUP BY d.donor_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    $parts = array_filter([
        $row["first_name"] ?? '',
        $row["middle_initial"] ?? '',
        $row["last_name"] ?? '',
        $row["suffix"] ?? ''
    ]);
    $fullName = trim(implode(' ', $parts));

    echo json_encode([
        "status" => "success",
        "data" => [
            "full_name"       => $fullName,
            "email"           => $row["email"] ?? "",
            "phone"           => $row["contact_number"] ?? "",
            "birthdate"       => $row["birthdate"] ?? "",
            "blood_type"      => $row["blood_type"] ?? "N/A",
            "total_donations" => (int)$row["total_donations"],
            "last_donation"   => $row["last_donation"] ?? null,
            "street"          => $row["street_address"] ?? "",
            "barangay"        => $row["barangay_name"] ?? "",
            "city"            => $row["city"] ?? "",
            "state"           => $row["province"] ?? "",
            "zip_code"        => ""
        ]
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