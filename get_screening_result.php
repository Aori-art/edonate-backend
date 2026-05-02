<?php
header("Content-Type: application/json");
include "db.php";

$screening_id = isset($_GET["screening_id"])
    ? intval($_GET["screening_id"])
    : 0;

if ($screening_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid screening_id"
    ]);
    exit;
}

$sql = "
    SELECT
        q.question_text,
        q.followup_prompt,
        a.answer,
        a.followup_answer
    FROM donor_screening_answers a
    INNER JOIN screening_questions q
        ON a.question_id = q.question_id
    WHERE a.screening_id = ?
    ORDER BY q.question_order ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $screening_id);
$stmt->execute();

$result = $stmt->get_result();

$answers = [];

while ($row = $result->fetch_assoc()) {
    $answers[] = $row;
}

$stmt->close();

echo json_encode([
    "status" => "success",
    "answers" => $answers
]);

$conn->close();
?>