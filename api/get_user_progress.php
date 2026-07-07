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
    // Get progress grouped by difficulty and language
    $progressQuery = "
        SELECT 
            difficulty,
            language,
            COUNT(*) as solved_count,
            SUM(best_score) as total_score
        FROM coding_practice_progress
        WHERE user_id = ? AND solved = 1
        GROUP BY difficulty, language
    ";
    
    $stmt = $conn->prepare($progressQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $progress = [
        'byDifficulty' => [],
        'byLanguage' => [],
        'overall' => [
            'totalSolved' => 0,
            'totalScore' => 0
        ]
    ];
    
    while ($row = $result->fetch_assoc()) {
        $difficulty = $row['difficulty'];
        $language = $row['language'];
        $solved = (int)$row['solved_count'];
        $score = (int)$row['total_score'];
        
        // Group by difficulty
        if (!isset($progress['byDifficulty'][$difficulty])) {
            $progress['byDifficulty'][$difficulty] = [
                'solved' => 0,
                'score' => 0
            ];
        }
        $progress['byDifficulty'][$difficulty]['solved'] += $solved;
        $progress['byDifficulty'][$difficulty]['score'] += $score;
        
        // Group by language
        if (!isset($progress['byLanguage'][$language])) {
            $progress['byLanguage'][$language] = [
                'solved' => 0,
                'score' => 0
            ];
        }
        $progress['byLanguage'][$language]['solved'] += $solved;
        $progress['byLanguage'][$language]['score'] += $score;
        
        // Overall totals
        $progress['overall']['totalSolved'] += $solved;
        $progress['overall']['totalScore'] += $score;
    }
    
    // Get total available problems from JSON
    $jsonFile = __DIR__ . '/../coding_problems.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $data = json_decode($jsonData, true);
        $progress['overall']['totalProblems'] = count($data['problems']);
        
        // Count by difficulty
        $difficultyCount = [];
        foreach ($data['problems'] as $problem) {
            $diff = $problem['difficulty'];
            if (!isset($difficultyCount[$diff])) {
                $difficultyCount[$diff] = 0;
            }
            $difficultyCount[$diff]++;
        }
        
        // Add total counts to each difficulty
        foreach ($difficultyCount as $diff => $count) {
            if (!isset($progress['byDifficulty'][$diff])) {
                $progress['byDifficulty'][$diff] = [
                    'solved' => 0,
                    'score' => 0
                ];
            }
            $progress['byDifficulty'][$diff]['total'] = $count;
        }
    }
    
    echo json_encode([
        'success' => true,
        'progress' => $progress
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching progress: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
