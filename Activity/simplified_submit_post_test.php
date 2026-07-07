<?php
// submit_post_test.php - Finalize the post-test attempt (simplified version)
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

if (!$attempt_id) {
    die("Invalid attempt ID");
}

// Verify this attempt belongs to the user
$verify_stmt = $conn->prepare("
    SELECT upta.*, t.name as topic_name 
    FROM user_post_test_attempts upta
    JOIN topics t ON upta.topic_id = t.id
    WHERE upta.id = ? AND upta.user_id = ? AND upta.completed_at IS NULL
");
$verify_stmt->bind_param("ii", $attempt_id, $user_id);
$verify_stmt->execute();
$attempt = $verify_stmt->get_result()->fetch_assoc();
$verify_stmt->close();

if (!$attempt) {
    die("Invalid or already completed attempt");
}

// Calculate final score - combining quiz/simulation questions AND hands-on activity score
$score_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_answered,
        SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
    FROM user_post_test_responses 
    WHERE attempt_id = ?
");
$score_stmt->bind_param("i", $attempt_id);
$score_stmt->execute();
$score_data = $score_stmt->get_result()->fetch_assoc();
$score_stmt->close();

$total_answered = $score_data['total_answered'] ?: 0;
$correct_answers = $score_data['correct_answers'] ?: 0;
$total_questions = 20; // Fixed at 20 questions (10 quiz + 10 simulation)
$quiz_sim_percentage = ($total_answered > 0) ? round(($correct_answers / $total_questions) * 100, 2) : 0;

// Get hands-on activity average score for this topic
$activity_score_stmt = $conn->prepare("
    SELECT AVG(score) as avg_activity_score
    FROM save_progress
    WHERE user_id = ? AND topic_id = ?
");
$activity_score_stmt->bind_param("ii", $user_id, $attempt['topic_id']);
$activity_score_stmt->execute();
$activity_result = $activity_score_stmt->get_result()->fetch_assoc();
$activity_score_stmt->close();

$activity_avg = $activity_result['avg_activity_score'] ?: 0;

// Calculate final score: 
// Each topic is 20% of overall, divided into 3 components:
// - Quiz Questions (10 questions): 33.33% → 6.67% of overall
// - Simulation Questions (10 questions): 33.33% → 6.67% of overall  
// - Hands-on Activities (100-point scale): 33.33% → 6.67% of overall
// 
// Since quiz+simulation are combined in the 20 questions (66.67% of topic):
$final_score_percentage = round(
    ($quiz_sim_percentage * 0.6667) +  // Quiz + Simulation = 66.67% of topic score
    ($activity_avg * 0.3333),          // Activity score = 33.33% of topic score
    2
);

// Update the attempt as completed
$update_attempt_stmt = $conn->prepare("
    UPDATE user_post_test_attempts 
    SET 
        total_questions = ?,
        correct_answers = ?,
        score = ?,
        completed_at = NOW()
    WHERE id = ?
");
$update_attempt_stmt->bind_param("iidi", $total_questions, $correct_answers, $final_score_percentage, $attempt_id);
$update_attempt_stmt->execute();
$update_attempt_stmt->close();

// Update or create eligibility record
$eligibility_stmt = $conn->prepare("
    INSERT INTO user_post_test_eligibility 
    (user_id, topic_id, completed_all_levels, post_test_taken, best_post_test_score, updated_at) 
    VALUES (?, ?, TRUE, TRUE, ?, NOW())
    ON DUPLICATE KEY UPDATE 
    post_test_taken = TRUE,
    best_post_test_score = GREATEST(best_post_test_score, VALUES(best_post_test_score)),
    updated_at = NOW()
");
$eligibility_stmt->bind_param("iid", $user_id, $attempt['topic_id'], $final_score_percentage);
$eligibility_stmt->execute();
$eligibility_stmt->close();

// Redirect to results page
header("Location: simplified_post_test_results.php?attempt_id=" . $attempt_id);
exit();
?>