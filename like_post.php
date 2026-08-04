<?php
// ============================================================
// like_post.php
// POST /like_post.php
// Body (JSON): { "post_id": 3, "donor_id": "d123", "liked": true }
//
// Increments/decrements the likes counter on `posts` and records
// (or removes) the like in `post_likes` so the same donor can't
// like a post twice.
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php'; // provides $conn (mysqli)

$input = json_decode(file_get_contents('php://input'), true);

$postId  = isset($input['post_id']) ? (int) $input['post_id'] : null;
$donorId = isset($input['donor_id']) ? trim($input['donor_id']) : null;
$liked   = isset($input['liked']) ? (bool) $input['liked'] : null;

if ($postId === null || !$donorId || $liked === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'post_id, donor_id, and liked are required']);
    exit;
}

$conn->begin_transaction();

try {
    if ($liked) {
        $stmt = $conn->prepare(
            'INSERT IGNORE INTO post_likes (post_id, donor_id) VALUES (?, ?)'
        );
        $stmt->bind_param('is', $postId, $donorId);
        $stmt->execute();
        $inserted = $stmt->affected_rows > 0;
        $stmt->close();

        if ($inserted) {
            $stmt = $conn->prepare('UPDATE posts SET likes = likes + 1 WHERE id = ?');
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare(
            'DELETE FROM post_likes WHERE post_id = ? AND donor_id = ?'
        );
        $stmt->bind_param('is', $postId, $donorId);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if ($deleted) {
            $stmt = $conn->prepare('UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE id = ?');
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $stmt = $conn->prepare('SELECT likes FROM posts WHERE id = ?');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('Post not found');
    }

    $conn->commit();

    echo json_encode(['success' => true, 'likes' => (int) $row['likes']]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update like']);
} finally {
    $conn->close();
}