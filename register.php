<?php
header("Content-Type: application/json");
require "db.php";
require "mail_config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}

$full_name      = trim($_POST['full_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$birthdate      = trim($_POST['birthdate'] ?? '');
$gender         = trim($_POST['gender'] ?? '');
$blood_type     = trim($_POST['blood_type'] ?? '');
$street_address = trim($_POST['street_address'] ?? '');
$barangay       = trim($_POST['barangay'] ?? '');
$municipality   = trim($_POST['municipality'] ?? '');
$province       = trim($_POST['province'] ?? '');
$password       = trim($_POST['password'] ?? '');

if (
    empty($full_name) || empty($email) || empty($phone) || empty($birthdate) ||
    empty($gender) || empty($blood_type) || empty($street_address) ||
    empty($barangay) || empty($municipality) || empty($province) || empty($password)
) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email address."
    ]);
    exit;
}

$checkEmail = $conn->prepare("SELECT auth_id FROM donor_authentication WHERE email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();

if ($checkEmail->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already registered."
    ]);
    exit;
}
$checkEmail->close();

$nameParts = preg_split('/\s+/', $full_name);
$first_name = $nameParts[0] ?? '';
$last_name = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $first_name;

$getBloodType = $conn->prepare("SELECT blood_type_id FROM blood_types WHERE blood_type = ?");
$getBloodType->bind_param("s", $blood_type);
$getBloodType->execute();
$resultBlood = $getBloodType->get_result();

if ($resultBlood->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid blood type."
    ]);
    exit;
}

$bloodRow = $resultBlood->fetch_assoc();
$blood_type_id = $bloodRow['blood_type_id'];
$getBloodType->close();

$conn->begin_transaction();

try {
    $insertLocation = $conn->prepare("
        INSERT INTO locations (street_address, barangay_name, city, province)
        VALUES (?, ?, ?, ?)
    ");
    $insertLocation->bind_param("ssss", $street_address, $barangay, $municipality, $province);
    $insertLocation->execute();
    $location_id = $conn->insert_id;
    $insertLocation->close();

    $insertDonor = $conn->prepare("
        INSERT INTO donors (first_name, last_name, gender, birthdate, contact_number, blood_type_id, location_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insertDonor->bind_param(
        "sssssii",
        $first_name,
        $last_name,
        $gender,
        $birthdate,
        $phone,
        $blood_type_id,
        $location_id
    );
    $insertDonor->execute();
    $donor_id = $conn->insert_id;
    $insertDonor->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertAuth = $conn->prepare("
        INSERT INTO donor_authentication (donor_id, email, password, is_verified, verification_sent_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $insertAuth->bind_param("iss", $donor_id, $email, $hashedPassword);
    $insertAuth->execute();
    $insertAuth->close();

    $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $insertOtp = $conn->prepare("
        INSERT INTO otp_codes (email, otp_code, expires_at, is_used)
        VALUES (?, ?, ?, 0)
    ");
    $insertOtp->bind_param("sss", $email, $otp_code, $expires_at);
    $insertOtp->execute();
    $insertOtp->close();

    $mailSent = sendOtpEmail($email, $otp_code);

    if ($mailSent !== true) {
    throw new Exception("Failed to send OTP email. " . $mailSent);
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Registration successful. OTP sent to your email."
    ]);
} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => "Registration failed: " . $e->getMessage()
    ]);
}
?>