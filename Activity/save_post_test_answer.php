<?php
// save_post_test_answer.php - Save individual question answers during the exam
session_start();
require_once "../db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['attempt_id'], $input['question_id'], $input['answer'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

$attempt_id = intval($input['attempt_id']);
$question_id = intval($input['question_id']);
$answer = strtoupper(trim($input['answer']));

if (!in_array($answer, ['A', 'B', 'C', 'D'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid answer']);
    exit();
}

try {
    // Verify this attempt belongs to the current user
    $verify_stmt = $conn->prepare("
        SELECT id FROM user_post_test_attempts 
        WHERE id = ? AND user_id = ? AND status = 'in_progress'
    ");
    $verify_stmt->bind_param("ii", $attempt_id, $user_id);
    $verify_stmt->execute();
    if (!$verify_stmt->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid attempt']);
        exit();
    }
    $verify_stmt->close();
    
    // Get correct answer
    $correct_stmt = $conn->prepare("
        SELECT correct_answer FROM topic_post_test_questions WHERE id = ?
    ");
    $correct_stmt->bind_param("i", $question_id);
    $correct_stmt->execute();
    $correct_result = $correct_stmt->get_result()->fetch_assoc();
    $correct_stmt->close();
    
    if (!$correct_result) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Question not found']);
        exit();
    }
    
    $is_correct = ($answer === $correct_result['correct_answer']);
    
    // Save or update response
    $save_stmt = $conn->prepare("
        INSERT INTO user_post_test_responses 
        (attempt_id, question_id, user_answer, is_correct, answered_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        user_answer = VALUES(user_answer),
        is_correct = VALUES(is_correct),
        answered_at = NOW()
    ");
    $save_stmt->bind_param("iisi", $attempt_id, $question_id, $answer, $is_correct);
    $save_stmt->execute();
    $save_stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'is_correct' => $is_correct
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

$conn->close();
?>