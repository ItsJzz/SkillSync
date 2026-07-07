<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
require_once '../db_connect.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get user stats
    $statsQuery = "
        SELECT 
            COALESCE(SUM(score), 0) as total_score,
            COALESCE(COUNT(DISTINCT problem_id), 0) as problems_solved,
            COALESCE(MAX(score), 0) as best_score
        FROM coding_practice_scores 
        WHERE user_id = ?
    ";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param("i", $user_id);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    
    // Get user rank from leaderboard
    $rankQuery = "
        SELECT 
            (SELECT COUNT(*) + 1 FROM coding_practice_leaderboard l2 WHERE l2.total_score > l1.total_score) as rank
        FROM coding_practice_leaderboard l1 
        WHERE l1.user_id = ?
    ";
    $rankStmt = $conn->prepare($rankQuery);
    $rankStmt->bind_param("i", $user_id);
    $rankStmt->execute();
    $rankResult = $rankStmt->get_result();
    $rank = $rankResult->num_rows > 0 ? $rankResult->fetch_assoc()['rank'] : '-';
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_solved' => (int)$stats['problems_solved'],
            'best_score' => (int)$stats['best_score'],
            'total_score' => (int)$stats['total_score'],
            'rank' => $rank
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats: ' . $e->getMessage()
    ]);
}

$conn->close();
?>