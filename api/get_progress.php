<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$language = $_GET['language'] ?? '';

if (!$language) {
    echo json_encode(['success' => false, 'message' => 'Language parameter required']);
    exit;
}

require_once '../db_connect.php';

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Load problems from JSON to get total count per difficulty
    $jsonFile = __DIR__ . '/../coding_problems.json';
    if (!file_exists($jsonFile)) {
        echo json_encode(['success' => false, 'message' => 'Problems file not found']);
        exit;
    }
    
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    
    // Count total problems per difficulty
    $totalByDifficulty = [
        'Easy' => 0,
        'Medium' => 0,
        'Hard' => 0
    ];
    
    foreach ($data['problems'] as $problem) {
        $diff = ucfirst(strtolower($problem['difficulty']));
        if (isset($totalByDifficulty[$diff])) {
            $totalByDifficulty[$diff]++;
        }
    }
    
    // Get completed problems for this user and language
    $query = "SELECT problem_id FROM coding_practice_completed 
              WHERE user_id = ? AND language = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $language);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $completedIds = [];
    while ($row = $result->fetch_assoc()) {
        $completedIds[] = (int)$row['problem_id'];
    }
    
    // Count completed by difficulty
    $completedByDifficulty = [
        'Easy' => 0,
        'Medium' => 0,
        'Hard' => 0
    ];
    
    foreach ($data['problems'] as $problem) {
        if (in_array($problem['id'], $completedIds)) {
            $diff = ucfirst(strtolower($problem['difficulty']));
            if (isset($completedByDifficulty[$diff])) {
                $completedByDifficulty[$diff]++;
            }
        }
    }
    
    // Build progress response
    $progress = [
        'Easy' => [
            'completed' => $completedByDifficulty['Easy'],
            'total' => $totalByDifficulty['Easy']
        ],
        'Medium' => [
            'completed' => $completedByDifficulty['Medium'],
            'total' => $totalByDifficulty['Medium']
        ],
        'Hard' => [
            'completed' => $completedByDifficulty['Hard'],
            'total' => $totalByDifficulty['Hard']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'completed_ids' => $completedIds
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
