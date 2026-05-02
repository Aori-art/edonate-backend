<?php 
header("Content-Type: application/json");
include "db.php";

$sql = "SELECT 
            question_id,
            question_text,
            followup_prompt,
            followup_trigger,
            question_order,
            extra_data
        FROM screening_questions
        WHERE is_active = 1
        ORDER BY question_order ASC";

$result = $conn->query($sql);

$questions = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Decode JSON safely
        $extraData = null;
        if (!empty($row["extra_data"])) {
            $decoded = json_decode($row["extra_data"], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $extraData = $decoded;
            }
        }

        $questions[] = [
            "question_id" => (int)$row["question_id"],
            "question_text" => $row["question_text"],
            "followup_prompt" => $row["followup_prompt"],
            "followup_trigger" => strtolower($row["followup_trigger"]),
            "question_order" => (int)$row["question_order"],
            "extra_data" => $extraData
        ];
    }

    echo json_encode([
        "status" => "success",
        "questions" => $questions
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "questions" => []
    ]);
}

$conn->close();
?>