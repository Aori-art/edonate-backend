<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request body"
    ]);
    exit;
}

$donor_id = isset($data["donor_id"]) ? intval($data["donor_id"]) : 0;
$answers = $data["answers"] ?? [];

if ($donor_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid donor_id"
    ]);
    exit;
}

if (empty($answers)) {
    echo json_encode([
        "status" => "error",
        "message" => "No answers submitted"
    ]);
    exit;
}

$conn->begin_transaction();

try {
    // STEP 1: create eligibility record
    $stmt = $conn->prepare("
        INSERT INTO eligibility_status (donor_id, status)
        VALUES (?, 'pending')
    ");

    $stmt->bind_param("i", $donor_id);
    $stmt->execute();

    $eligibility_id = $conn->insert_id;
    $stmt->close();

    // STEP 2: save answers
    $stmt = $conn->prepare("
        INSERT INTO donor_screening_answers
        (eligibility_id, question_id, answer, followup_answer)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($answers as $item) {
        $question_id = intval($item["question_id"]);
        $answer = strtolower(trim($item["answer"]));
        $followup = $item["followup_answer"] ?? null;

        if ($answer !== "yes" && $answer !== "no") {
            throw new Exception("Invalid answer value");
        }

        $stmt->bind_param(
            "iiss",
            $eligibility_id,
            $question_id,
            $answer,
            $followup
        );

        $stmt->execute();
    }

    $stmt->close();
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Screening submitted successfully",
        "eligibility_id" => $eligibility_id
    ]);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>