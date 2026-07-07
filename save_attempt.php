<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$student_id = $_SESSION['user_id'];

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// Get JSON input from fetch()
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Debug logging
error_log("Raw input: " . $rawInput);
error_log("Parsed data: " . print_r($data, true));

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input", "raw" => $rawInput]);
    exit;
}

$attempt_date = date("Y-m-d H:i:s");
$test_type = "pre";

// Check if this is the new scalable assessment
if (isset($data['assessmentType']) && $data['assessmentType'] === 'scalable_pretest') {
    // Handle new scalable scoring system
    $topicPercentages = $data['topicPercentages'] ?? [];
    $activities = $data['activities'] ?? [];
    $scoringData = $data['scoringData'] ?? [];
    
    // Allow partial submissions for debugging purposes
    // if (empty($topicPercentages)) {
    //     http_response_code(400);
    //     echo json_encode(["error" => "No topic data provided", "debug" => $data]);
    //     exit;
    // }
    
    // Calculate total score across all topics
    $totalScore = 0;
    $maxTotalScore = 0;
    $topicDetails = [];
    
    foreach ($topicPercentages as $topic_id => $topicData) {
        $totalScore += $topicData['totalScore'] ?? 0;
        $maxTotalScore += $topicData['maxScore'] ?? 30;
        
        // Prepare topic breakdown for storage with the expected structure
        $topicDetails[$topic_id] = [
            'questionScore' => $topicData['questionScore'] ?? 0,
            'handsOnScore' => $topicData['handsOnScore'] ?? 0,
            'totalScore' => $topicData['totalScore'] ?? 0,
            'maxScore' => $topicData['maxScore'] ?? 30,
            'percentage' => $topicData['percentage'] ?? 0,
            'name' => $topicData['name'] ?? 'Unknown Topic',
            'hasActivity' => isset($activities[$topic_id]),
            // Include detailed breakdown from frontend
            'quiz_correct' => $topicData['quiz_correct'] ?? 0,
            'quiz_total' => $topicData['quiz_total'] ?? 8,
            'simulation_correct' => $topicData['simulation_correct'] ?? 0,
            'simulation_total' => $topicData['simulation_total'] ?? 8,
            'hands_on_score' => $topicData['hands_on_score'] ?? 0,
            'hands_on_max' => $topicData['hands_on_max'] ?? 10
        ];
    }
    
    // Determine class level FIRST before using it
    $classLevel = 'Beginner';
    $progressToNext = 0;
    $passingScore = 115; // 115/150 for intermediate
    
    if ($totalScore >= $passingScore) {
        $classLevel = 'Intermediate';
        $progressToNext = 0; // Starts from 0 for intermediate
    } else {
        // Calculate progress towards intermediate
        $progressToNext = $maxTotalScore > 0 ? round(($totalScore / $passingScore) * 100, 1) : 0;
    }
    
    // Store comprehensive assessment results in students table
    $assessmentData = [
        'format' => 'scalable',
        'total_score' => $totalScore,
        'max_total_score' => $maxTotalScore,
        'overall_percentage' => $maxTotalScore > 0 ? round(($totalScore / $maxTotalScore) * 100, 2) : 0,
        'topic_scores' => $topicDetails,
        'scoring_data' => $scoringData,
        'activities' => $activities,
        'assessment_type' => 'scalable_pretest'
    ];
    
    // Update or Insert students table with assessment data
    // First check if student record exists
    $checkStudent = $conn->prepare("SELECT id FROM students WHERE user_id = ? OR id = ?");
    $checkStudent->bind_param("ii", $student_id, $student_id);
    $checkStudent->execute();
    $result = $checkStudent->get_result();
    
    $assessmentJson = json_encode($assessmentData);
    $detailsJson = json_encode([
        'class_level' => $classLevel,
        'progress_to_next' => $progressToNext,
        'completion_date' => $attempt_date
    ]);
    
    if ($result->num_rows > 0) {
        // Update existing record
        $updateStudent = $conn->prepare("
            UPDATE students 
            SET assessment_data = ?, assessment_details = ?, updated_at = NOW()
            WHERE user_id = ? OR id = ?
        ");
        $updateStudent->bind_param("ssii", $assessmentJson, $detailsJson, $student_id, $student_id);
        
        if (!$updateStudent->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update student record", "sql_error" => $updateStudent->error]);
            exit;
        }
        $updateStudent->close();
    } else {
        // Create new student record
        $insertStudent = $conn->prepare("
            INSERT INTO students (user_id, assessment_data, assessment_details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insertStudent->bind_param("iss", $student_id, $assessmentJson, $detailsJson);
        
        if (!$insertStudent->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to create student record", "sql_error" => $insertStudent->error]);
            exit;
        }
        $insertStudent->close();
    }
    
    $checkStudent->close();
    
    // Mark pre-assessment as completed in login_credentials table
    $updateCompleted = $conn->prepare("UPDATE login_credentials SET completed_preassessment = 1 WHERE id = ?");
    $updateCompleted->bind_param("i", $student_id);
    
    if (!$updateCompleted->execute()) {
        error_log("Failed to update completed_preassessment flag for user $student_id: " . $updateCompleted->error);
    }
    $updateCompleted->close();
    
    // Store in database - simplified for reliability
    $test_id = 1; // Pre-test ID
    
    try {
        // Only store the essential data for now to avoid database issues
        
        echo json_encode([
            "success" => true,
            "message" => "Scalable pre-test results saved",
            "totalScore" => $totalScore,
            "maxTotalScore" => $maxTotalScore,
            "overallPercentage" => $assessmentData['overall_percentage'],
            "classLevel" => $classLevel,
            "progressToNext" => $progressToNext,
            "assessmentType" => "scalable_pretest"
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error: " . $e->getMessage(),
            "debug" => [
                "student_id" => $student_id,
                "totalScore" => $totalScore,
                "maxTotalScore" => $maxTotalScore
            ]
        ]);
    }
    
} else {
    // Handle legacy scoring system (backwards compatibility)
    if (!isset($data['topicScores'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid legacy input"]);
        exit;
    }
    
    $topicScores = $data['topicScores'];
    
    // Calculate totals for legacy system
    $total_score = 0;
    $total_items = 0;
    foreach ($topicScores as $topic) {
        $total_score += intval($topic['correct']);
        $total_items += intval($topic['total']);
    }
    
    $test_id = 1;
    $stmt = $conn->prepare("
        INSERT INTO student_test_attempts (student_id, test_id, score, attempt_time)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $student_id, $test_id, $total_score, $attempt_date);
    $stmt->execute();
    $stmt->close();
    
    // Update student_tests for legacy format
    foreach ($topicScores as $topic_id => $topic) {
        $score = intval($topic['correct']);
        
        $check = $conn->prepare("
            SELECT id FROM student_tests
            WHERE student_id = ? AND topic_id = ? AND test_type = ?
        ");
        $check->bind_param("iis", $student_id, $topic_id, $test_type);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $update = $conn->prepare("
                UPDATE student_tests
                SET score = ?, attempt_date = ?
                WHERE student_id = ? AND topic_id = ? AND test_type = ?
            ");
            $update->bind_param("isiis", $score, $attempt_date, $student_id, $topic_id, $test_type);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("
                INSERT INTO student_tests (student_id, topic_id, test_type, score, attempt_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->bind_param("iisis", $student_id, $topic_id, $test_type, $score, $attempt_date);
            $insert->execute();
            $insert->close();
        }
        
        $check->close();
    }
    
    // Mark pre-assessment as completed in login_credentials table
    $updateCompleted = $conn->prepare("UPDATE login_credentials SET completed_preassessment = 1 WHERE id = ?");
    $updateCompleted->bind_param("i", $student_id);
    
    if (!$updateCompleted->execute()) {
        error_log("Failed to update completed_preassessment flag for user $student_id: " . $updateCompleted->error);
    }
    $updateCompleted->close();
    
    echo json_encode([
        "success" => true,
        "message" => "Legacy pre-test results saved",
        "total_score" => $total_score,
        "total_items" => $total_items
    ]);
}