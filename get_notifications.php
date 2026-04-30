<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

// Check database connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Get donor_id from request
$donor_id = isset($_GET['donor_id']) ? intval($_GET['donor_id']) : 0;

if ($donor_id === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Donor ID is required'
    ]);
    exit;
}

// Get notifications for the donor - CHANGED 'notification' TO 'notifications'
$query = "SELECT 
    notification_id,
    donor_id,
    message,
    notification_type,
    is_read,
    created_at
FROM notifications 
WHERE donor_id = ? 
ORDER BY created_at DESC 
LIMIT 50";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Query preparation failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count = 0;

while ($row = $result->fetch_assoc()) {
    // Format the date
    $created_date = new DateTime($row['created_at']);
    $now = new DateTime();
    $interval = $now->diff($created_date);
    
    if ($interval->days == 0) {
        $date_display = "Today";
    } elseif ($interval->days == 1) {
        $date_display = "Yesterday";
    } else {
        $date_display = $created_date->format('F j, Y');
    }
    
    $notifications[] = [
        'notification_id' => (int)$row['notification_id'],
        'title' => getNotificationTitle($row['notification_type']),
        'message' => $row['message'],
        'notification_type' => $row['notification_type'],
        'is_read' => (bool)$row['is_read'],
        'date' => $date_display,
        'created_at' => $row['created_at']
    ];
    
    if (!$row['is_read']) {
        $unread_count++;
    }
}

function getNotificationTitle($type) {
    switch ($type) {
        case 'appointment':
            return 'Appointment Update';
        case 'reminder':
            return 'Reminder';
        case 'thank_you':
            return 'Thank You!';
        case 'eligibility':
            return 'Eligibility Update';
        default:
            return 'Notification';
    }
}

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

$stmt->close();
$conn->close();
?>