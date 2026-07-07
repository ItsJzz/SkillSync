<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
require_once '../db_connect.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get top 10 users from leaderboard
    $leaderboardQuery = "
        SELECT 
            user_id,
            username,
            total_score,
            problems_solved,
            last_solved
        FROM coding_practice_stats
        ORDER BY total_score DESC, problems_solved DESC
        LIMIT 10
    ";
    
    $result = $conn->query($leaderboardQuery);
    
    $leaderboard = [];
    $rank = 1;
    $currentUserRank = null;
    
    while ($row = $result->fetch_assoc()) {
        $entry = [
            'rank' => $rank,
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'total_score' => (int)$row['total_score'],
            'problems_solved' => (int)$row['problems_solved'],
            'is_current_user' => ($row['user_id'] == $current_user_id)
        ];
        
        $leaderboard[] = $entry;
        
        if ($row['user_id'] == $current_user_id) {
            $currentUserRank = $rank;
        }
        
        $rank++;
    }
    
    // If current user is not in top 10, get their stats
    $currentUserStats = null;
    if ($currentUserRank === null) {
        $userQuery = "
            SELECT 
                user_id,
                username,
                total_score,
                problems_solved
            FROM coding_practice_stats
            WHERE user_id = ?
        ";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $current_user_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            
            // Get user's rank
            $rankQuery = "
                SELECT COUNT(*) + 1 as rank
                FROM coding_practice_stats
                WHERE total_score > ? OR (total_score = ? AND problems_solved > ?)
            ";
            $rankStmt = $conn->prepare($rankQuery);
            $rankStmt->bind_param("iii", $userRow['total_score'], $userRow['total_score'], $userRow['problems_solved']);
            $rankStmt->execute();
            $rankResult = $rankStmt->get_result();
            $rankRow = $rankResult->fetch_assoc();
            
            $currentUserStats = [
                'rank' => (int)$rankRow['rank'],
                'user_id' => (int)$userRow['user_id'],
                'username' => $userRow['username'],
                'total_score' => (int)$userRow['total_score'],
                'problems_solved' => (int)$userRow['problems_solved'],
                'is_current_user' => true
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard,
        'current_user' => $currentUserStats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching leaderboard: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
