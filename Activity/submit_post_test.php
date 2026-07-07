<?php
// submit_post_test.php - Finalize and score the post-test attempt
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

if (!$input || !isset($input['attempt_id'], $input['topic_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

$attempt_id = intval($input['attempt_id']);
$topic_id = intval($input['topic_id']);

try {
    // Verify this attempt belongs to the current user and is in progress
    $verify_stmt = $conn->prepare("
        SELECT id, started_at FROM user_post_test_attempts 
        WHERE id = ? AND user_id = ? AND topic_id = ? AND status = 'in_progress'
    ");
    $verify_stmt->bind_param("iii", $attempt_id, $user_id, $topic_id);
    $verify_stmt->execute();
    $attempt_data = $verify_stmt->get_result()->fetch_assoc();
    $verify_stmt->close();
    
    if (!$attempt_data) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid attempt or already completed']);
        exit();
    }
    
    // Calculate time taken
    $start_time = strtotime($attempt_data['started_at']);
    $current_time = time();
    $time_taken_minutes = ceil(($current_time - $start_time) / 60);
    
    // Count correct answers
    $score_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_answered,
            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
        FROM user_post_test_responses 
        WHERE attempt_id = ?
    ");
    $score_stmt->bind_param("i", $attempt_id);
    $score_stmt->execute();
    $score_result = $score_stmt->get_result()->fetch_assoc();
    $score_stmt->close();
    
    $correct_answers = $score_result['correct_answers'] ?? 0;
    $total_answered = $score_result['total_answered'] ?? 0;
    $score_percentage = ($correct_answers / 20) * 100; // Always out of 20 questions
    
    // Update attempt with final scores
    $update_stmt = $conn->prepare("
        UPDATE user_post_test_attempts 
        SET 
            correct_answers = ?,
            score_percentage = ?,
            time_taken_minutes = ?,
            completed_at = NOW(),
            status = 'completed'
        WHERE id = ?
    ");
    $update_stmt->bind_param("idii", $correct_answers, $score_percentage, $time_taken_minutes, $attempt_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Update eligibility table with best score and skill improvement
    $best_score_stmt = $conn->prepare("
        SELECT MAX(score_percentage) as best_score 
        FROM user_post_test_attempts 
        WHERE user_id = ? AND topic_id = ? AND status = 'completed'
    ");
    $best_score_stmt->bind_param("ii", $user_id, $topic_id);
    $best_score_stmt->execute();
    $best_score_result = $best_score_stmt->get_result()->fetch_assoc();
    $best_score = $best_score_result['best_score'] ?? 0;
    $best_score_stmt->close();
    
    // Get pre-assessment score for improvement calculation
    $pre_score_stmt = $conn->prepare("
        SELECT AVG(score) as pre_score 
        FROM student_tests 
        WHERE student_id = ? AND topic_id = ? AND test_type = 'pre'
    ");
    $pre_score_stmt->bind_param("ii", $user_id, $topic_id);
    $pre_score_stmt->execute();
    $pre_score_result = $pre_score_stmt->get_result()->fetch_assoc();
    $pre_score = $pre_score_result['pre_score'] ?? 0;
    $pre_score_stmt->close();
    
    $skill_improvement = $best_score - $pre_score;
    
    // Update eligibility record
    $update_eligibility_stmt = $conn->prepare("
        UPDATE user_post_test_eligibility 
        SET 
            post_test_taken = TRUE,
            best_post_test_score = ?,
            skill_improvement_percentage = ?
        WHERE user_id = ? AND topic_id = ?
    ");
    $update_eligibility_stmt->bind_param("ddii", $best_score, $skill_improvement, $user_id, $topic_id);
    $update_eligibility_stmt->execute();
    $update_eligibility_stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'correct_answers' => $correct_answers,
        'total_questions' => 20,
        'score_percentage' => round($score_percentage, 2),
        'time_taken_minutes' => $time_taken_minutes,
        'skill_improvement' => round($skill_improvement, 2)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>