<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['notification_id']) ? intval($data['notification_id']) : 0;
$mark_all = isset($data['mark_all']) ? (bool)$data['mark_all'] : false;
$donor_id = isset($data['donor_id']) ? intval($data['donor_id']) : 0;

if ($mark_all && $donor_id > 0) {
    // CHANGED 'notification' TO 'notifications'
    $query = "UPDATE notifications SET is_read = 1 WHERE donor_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $donor_id);
} elseif ($notification_id > 0) {
    // CHANGED 'notification' TO 'notifications'
    $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Notification(s) marked as read'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update notification'
    ]);
}

$stmt->close();
$conn->close();
?>