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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$problem_id = $input['problem_id'] ?? 0;
$language = $input['language'] ?? '';

if (!$problem_id || !$language) {
    echo json_encode(['success' => false, 'message' => 'Missing problem_id or language']);
    exit;
}

try {
    // Mark problem as completed for this user and language
    $query = "INSERT INTO coding_practice_completed (user_id, problem_id, language, completed_at) 
              VALUES (?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE completed_at = NOW()";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $user_id, $problem_id, $language);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Problem marked as completed! 🎉'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to mark as completed'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
