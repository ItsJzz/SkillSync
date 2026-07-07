<?php
// save_simplified_answer.php - Fixed version for simplified post-test
session_start();
require_once "../db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data (form-encoded, not JSON)
$attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$selected_answer = isset($_POST['selected_answer']) ? strtoupper(trim($_POST['selected_answer'])) : '';

if (!$attempt_id || !$question_id || !$selected_answer) {
    echo json_encode(['success' => false, 'message' => 'Missing data: attempt_id=' . $attempt_id . ', question_id=' . $question_id . ', answer=' . $selected_answer]);
    exit();
}

// Verify this attempt belongs to the logged-in user
$verify_stmt = $conn->prepare("SELECT user_id FROM user_post_test_attempts WHERE id = ? AND user_id = ?");
$verify_stmt->bind_param("ii", $attempt_id, $_SESSION['user_id']);
$verify_stmt->execute();
$attempt_data = $verify_stmt->get_result()->fetch_assoc();
$verify_stmt->close();

if (!$attempt_data) {
    echo json_encode(['success' => false, 'message' => 'Invalid attempt or access denied']);
    exit();
}

// Get correct answer from questions table
$correct_stmt = $conn->prepare("SELECT correct_option FROM questions WHERE id = ?");
$correct_stmt->bind_param("i", $question_id);
$correct_stmt->execute();
$correct_data = $correct_stmt->get_result()->fetch_assoc();
$correct_stmt->close();

if (!$correct_data) {
    echo json_encode(['success' => false, 'message' => 'Question not found']);
    exit();
}

// Handle different correct_option formats
$correct_option = trim($correct_data['correct_option']);

// If correct_option is full text, try to match it to A/B/C
if (!in_array($correct_option, ['A', 'B', 'C'])) {
    // Get all options to find which letter matches the correct text
    $options_stmt = $conn->prepare("SELECT option_a, option_b, option_c FROM questions WHERE id = ?");
    $options_stmt->bind_param("i", $question_id);
    $options_stmt->execute();
    $options_data = $options_stmt->get_result()->fetch_assoc();
    $options_stmt->close();
    
    if ($options_data) {
        if (trim($correct_option) === trim($options_data['option_a'])) {
            $correct_option = 'A';
        } elseif (trim($correct_option) === trim($options_data['option_b'])) {
            $correct_option = 'B';
        } elseif (trim($correct_option) === trim($options_data['option_c'])) {
            $correct_option = 'C';
        }
    }
}

$is_correct = ($correct_option === $selected_answer) ? 1 : 0;

// Insert or update response
$response_stmt = $conn->prepare("
    INSERT INTO user_post_test_responses (attempt_id, question_id, selected_answer, is_correct, answered_at) 
    VALUES (?, ?, ?, ?, NOW()) 
    ON DUPLICATE KEY UPDATE 
    selected_answer = VALUES(selected_answer), 
    is_correct = VALUES(is_correct), 
    answered_at = NOW()
");
$response_stmt->bind_param("iisi", $attempt_id, $question_id, $selected_answer, $is_correct);

if ($response_stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'is_correct' => $is_correct,
        'correct_answer' => $correct_option,
        'debug' => [
            'attempt_id' => $attempt_id,
            'question_id' => $question_id,
            'selected' => $selected_answer,
            'correct' => $correct_option,
            'original_correct' => $correct_data['correct_option']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$response_stmt->close();
?>