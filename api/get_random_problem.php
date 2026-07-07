<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../db_connect.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get difficulty filter from request
    $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : null;
    
    // Build query based on difficulty filter
    if ($difficulty && in_array($difficulty, ['Easy', 'Medium', 'Intermediate', 'Hard'])) {
        $query = "SELECT * FROM coding_problems WHERE difficulty = ? ORDER BY RAND() LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $difficulty);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Get a random problem from the database
        $query = "SELECT * FROM coding_problems ORDER BY RAND() LIMIT 1";
        $result = $conn->query($query);
    }
    
    if ($result && $result->num_rows > 0) {
        $problem = $result->fetch_assoc();
        
        // Decode JSON fields
        $problem['examples'] = json_decode($problem['examples'], true);
        $problem['test_cases'] = json_decode($problem['test_cases'], true);
        $problem['skeleton'] = json_decode($problem['skeleton'], true);
        
        echo json_encode([
            'success' => true,
            'problem' => $problem
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $difficulty ? "No problems available for {$difficulty} difficulty" : 'No problems available'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching problem: ' . $e->getMessage()
    ]);
}

$conn->close();
?>