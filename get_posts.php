<?php
// ============================================================
// get_posts.php
// GET /get_posts.php                        -> latest 50 posts
// GET /get_posts.php?limit=20&before_id=41   -> pagination
//
// Response shape matches lib/newsfeed.dart's Post model exactly,
// including a server-computed "time_ago" string.
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php'; // provides $conn (mysqli)

function time_ago(string $datetime): string {
    $then = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $then->getTimestamp();

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) == 1 ? '' : 's') . ' ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) == 1 ? '' : 's') . ' ago';
    return $then->format('M j, Y');
}

$limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : 50;
$beforeId = isset($_GET['before_id']) ? (int) $_GET['before_id'] : null;

if ($beforeId !== null) {
    $sql = 'SELECT id, type, author, author_avatar, author_badge, content, image,
                   likes, blood_type, hospital, event_date, event_location, urgency,
                   created_at
            FROM posts
            WHERE id < ?
            ORDER BY created_at DESC, id DESC
            LIMIT ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query prepare failed']);
        exit;
    }
    $stmt->bind_param('ii', $beforeId, $limit);
} else {
    $sql = 'SELECT id, type, author, author_avatar, author_badge, content, image,
                   likes, blood_type, hospital, event_date, event_location, urgency,
                   created_at
            FROM posts
            ORDER BY created_at DESC, id DESC
            LIMIT ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query prepare failed']);
        exit;
    }
    $stmt->bind_param('i', $limit);
}

$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = [
        'id'            => (int) $row['id'],
        'type'          => $row['type'],
        'author'        => $row['author'],
        'authorAvatar'  => $row['author_avatar'],
        'authorBadge'   => $row['author_badge'],
        'timeAgo'       => time_ago($row['created_at']),
        'content'       => $row['content'],
        'image'         => $row['image'],
        'likes'         => (int) $row['likes'],
        'bloodType'     => $row['blood_type'],
        'hospital'      => $row['hospital'],
        'eventDate'     => $row['event_date'],
        'eventLocation' => $row['event_location'],
        'urgency'       => $row['urgency'],
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'posts'   => $posts,
]);