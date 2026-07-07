<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Database connection
require_once '../db_connect.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$topic_id || !$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

try {
    // Get student information
    $studentQuery = "
        SELECT 
            id,
            username,
            username as name,
            email
        FROM login_credentials 
        WHERE id = ? AND role = 'student'
    ";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $studentInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$studentInfo) {
        throw new Exception('Student not found');
    }
    
    // Get topic information
    $topicQuery = "
        SELECT 
            t.id,
            t.name as topic_name,
            s.code as subject_code,
            s.name as subject_name
        FROM topics t
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.id = ?
    ";
    $stmt = $conn->prepare($topicQuery);
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $topicInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$topicInfo) {
        throw new Exception('Topic not found');
    }
    
    // Get progress data over time
    $progressQuery = "
        SELECT 
            DATE(date_created) as date,
            AVG(avg_score) as score,
            COUNT(*) as attempts_on_date
        FROM student_activity_scores 
        WHERE student_id = ? AND topic_id = ?
        GROUP BY DATE(date_created)
        ORDER BY date ASC
    ";
    $stmt = $conn->prepare($progressQuery);
    $stmt->bind_param("ii", $student_id, $topic_id);
    $stmt->execute();
    $progressData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get summary statistics
    $summaryQuery = "
        SELECT 
            MIN(avg_score) as first_score,
            MAX(avg_score) as current_score,
            AVG(avg_score) as average_score,
            COUNT(*) as total_attempts,
            COUNT(DISTINCT DATE(date_created)) as days_active,
            MIN(date_created) as first_attempt,
            MAX(date_created) as last_attempt
        FROM student_activity_scores 
        WHERE student_id = ? AND topic_id = ?
    ";
    $stmt = $conn->prepare($summaryQuery);
    $stmt->bind_param("ii", $student_id, $topic_id);
    $stmt->execute();
    $summaryResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Calculate improvement
    $improvement = 0;
    if ($summaryResult['first_score'] > 0) {
        $improvement = round((($summaryResult['current_score'] - $summaryResult['first_score']) / $summaryResult['first_score']) * 100, 1);
    }
    
    $summary = [
        'first_score' => round($summaryResult['first_score'], 1),
        'current_score' => round($summaryResult['current_score'], 1),
        'average_score' => round($summaryResult['average_score'], 1),
        'total_attempts' => $summaryResult['total_attempts'],
        'days_active' => $summaryResult['days_active'],
        'improvement' => $improvement,
        'first_attempt' => $summaryResult['first_attempt'],
        'last_attempt' => $summaryResult['last_attempt']
    ];
    
    // Format progress data for Chart.js
    $formattedProgressData = array_map(function($item) {
        return [
            'date' => $item['date'],
            'score' => round($item['score'], 1),
            'attempts' => $item['attempts_on_date']
        ];
    }, $progressData);
    
    // Return response
    echo json_encode([
        'success' => true,
        'studentInfo' => $studentInfo,
        'topicInfo' => $topicInfo,
        'progressData' => $formattedProgressData,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>