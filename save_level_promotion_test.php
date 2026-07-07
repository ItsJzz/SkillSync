<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Extract data
$subject_id = intval($data['subject_id']);
$current_level = $data['current_level'];
$target_level = $data['target_level'];
$score = floatval($data['score']);
$passed = $data['passed'];
$answers = $data['answers'];
$total_questions = intval($data['total_questions']);
$correct_count = intval($data['correct_count']);
$details = isset($data['details']) ? $data['details'] : [];

// Validate
if (empty($current_level) || empty($target_level)) {
    echo json_encode(['success' => false, 'message' => 'Invalid level data']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Get current student record
    $stmt = $conn->prepare("SELECT id, assessment_details FROM students WHERE id = ? OR user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $studentRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$studentRow) {
        throw new Exception('Student record not found');
    }
    
    // Store attempt with detailed analysis data in level_promotion_attempts table
    $answersDataJson = json_encode([
        'answers' => $answers,
        'details' => $details,
        'total_questions' => $total_questions,
        'correct_count' => $correct_count
    ]);
    
    $insertAttempt = $conn->prepare("
        INSERT INTO level_promotion_attempts 
        (student_id, subject_id, current_level, target_level, score, passed, total_questions, correct_count, answers_data) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertAttempt->bind_param(
        "iissdiiis", 
        $student_id, 
        $subject_id, 
        $current_level, 
        $target_level, 
        $score, 
        $passed, 
        $total_questions, 
        $correct_count, 
        $answersDataJson
    );
    $insertAttempt->execute();
    $insertAttempt->close();
    
    // Parse current assessment_details
    $assessmentDetails = [];
    if (!empty($studentRow['assessment_details'])) {
        $assessmentDetails = json_decode($studentRow['assessment_details'], true);
    }
    
    // Record test attempt (optional - store in level_promotion_tests table if it exists)
    // Skip this if table doesn't exist - not critical for functionality
    /* Commented out - optional feature
    try {
        $insertTest = $conn->prepare("
            INSERT INTO level_promotion_tests 
            (student_id, from_level, to_level, score, passed, test_data) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $testData = json_encode([
            'answers' => $answers,
            'total_questions' => $total_questions,
            'correct_count' => $correct_count,
            'subject_id' => $subject_id
        ]);
        $insertTest->bind_param("issdis", $student_id, $current_level, $target_level, $score, $passed, $testData);
        $insertTest->execute();
        $insertTest->close();
    } catch (Exception $e) {
        // Table might not exist, continue anyway
    }
    */
    
    // If passed, update class_level and reset progress
    if ($passed) {
        // ============================================
        // STEP 1: BACKUP CURRENT PROGRESS TO HISTORY
        // ============================================
        
        // Get current assessment_data for backup
        $getAssessmentStmt = $conn->prepare("SELECT assessment_data FROM students WHERE id = ?");
        $getAssessmentStmt->bind_param("i", $studentRow['id']);
        $getAssessmentStmt->execute();
        $currentAssessmentData = $getAssessmentStmt->get_result()->fetch_assoc();
        $getAssessmentStmt->close();
        
        // Get activity scores for backup (handle if table/columns don't exist)
        $activityScores = [];
        try {
            $getActivities = $conn->prepare("SELECT topic_id, completed_at FROM student_activity_scores WHERE student_id = ?");
            $getActivities->bind_param("i", $student_id);
            $getActivities->execute();
            $activityScores = $getActivities->get_result()->fetch_all(MYSQLI_ASSOC);
            $getActivities->close();
        } catch (Exception $e) {
            // Table or columns might not exist, continue
            $activityScores = [];
        }
        
        // Get post-test scores for backup (handle if table/columns don't exist)
        $postTestScores = [];
        try {
            $getPostTests = $conn->prepare("SELECT topic_id, score, completed_at FROM user_post_test_attempts WHERE user_id = ?");
            $getPostTests->bind_param("i", $student_id);
            $getPostTests->execute();
            $postTestScores = $getPostTests->get_result()->fetch_all(MYSQLI_ASSOC);
            $getPostTests->close();
        } catch (Exception $e) {
            // Table or columns might not exist, continue
            $postTestScores = [];
        }
        
        // Calculate overall score for this level
        $overallScore = 0;
        if (!empty($currentAssessmentData['assessment_data'])) {
            $assessData = json_decode($currentAssessmentData['assessment_data'], true);
            if (isset($assessData['topic_scores'])) {
                $total = 0;
                $count = 0;
                foreach ($assessData['topic_scores'] as $topicScore) {
                    if (isset($topicScore['percentage'])) {
                        $total += floatval($topicScore['percentage']);
                        $count++;
                    }
                }
                $overallScore = $count > 0 ? ($total / $count) : 0;
            }
        }
        
        // Get current progress percentage
        $currentProgress = isset($assessmentDetails['progress_to_next']) ? $assessmentDetails['progress_to_next'] : 0;
        
        // Create achievements summary
        $achievements = [
            'activities_completed' => count($activityScores),
            'post_tests_taken' => count($postTestScores),
            'promotion_score' => $score,
            'promotion_test_date' => date('Y-m-d H:i:s')
        ];
        
        // Save to history table (completely optional - won't break promotion if it fails)
        try {
            // Check if table exists first
            $tableCheck = $conn->query("SHOW TABLES LIKE 'student_progress_history'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $backupStmt = $conn->prepare("
                    INSERT INTO student_progress_history 
                    (student_id, level, overall_score, progress_percentage, assessment_data, activity_scores, post_test_scores, achievements) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $assessmentDataJson = $currentAssessmentData['assessment_data'] ?? '{}';
                $backupStmt->bind_param(
                    "isddssss",
                    $student_id,
                    $current_level,
                    $overallScore,
                    $currentProgress,
                    $assessmentDataJson,
                    json_encode($activityScores),
                    json_encode($postTestScores),
                    json_encode($achievements)
                );
                $backupStmt->execute();
                $backupStmt->close();
            }
        } catch (Exception $e) {
            // Backup failed but continue with promotion - this is not critical
            error_log("Progress history backup failed (not critical): " . $e->getMessage());
        }
        
        // ============================================
        // STEP 2: UPDATE TO NEW LEVEL
        // ============================================
        
        // Update class_level to target level
        $assessmentDetails['class_level'] = $target_level;
        
        // Reset progress to 0 for the new level
        $assessmentDetails['progress_to_next'] = 0;
        
        // Update completion date
        $assessmentDetails['level_promotion_date'] = date('Y-m-d H:i:s');
        
        // Save updated assessment_details
        $updateStmt = $conn->prepare("
            UPDATE students 
            SET assessment_details = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $detailsJson = json_encode($assessmentDetails);
        $updateStmt->bind_param("si", $detailsJson, $studentRow['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // ============================================
        // STEP 3: RESET PROGRESS FOR NEW LEVEL
        // ============================================
        // (Progress is already backed up in student_progress_history table)
        
        // 1. Clear activity scores (backed up)
        $resetStmt = $conn->prepare("DELETE FROM student_activity_scores WHERE student_id = ?");
        $resetStmt->bind_param("i", $student_id);
        $resetStmt->execute();
        $resetStmt->close();
        
        // 2. Clear post-test attempts (backed up)
        $resetPostTests = $conn->prepare("DELETE FROM user_post_test_attempts WHERE user_id = ?");
        $resetPostTests->bind_param("i", $student_id);
        $resetPostTests->execute();
        $resetPostTests->close();
        
        // 3. Clear save_progress table (activity progress tracking)
        try {
            $resetSaveProgress = $conn->prepare("DELETE FROM save_progress WHERE user_id = ?");
            $resetSaveProgress->bind_param("i", $student_id);
            $resetSaveProgress->execute();
            $resetSaveProgress->close();
        } catch (Exception $e) {
            // Table might not exist, continue
        }
        
        // 4. Reset assessment_data topic scores to 0 (original data backed up)
        // Get current assessment_data and reset all topic scores
        $getAssessmentStmt = $conn->prepare("SELECT assessment_data FROM students WHERE id = ?");
        $getAssessmentStmt->bind_param("i", $studentRow['id']);
        $getAssessmentStmt->execute();
        $assessmentDataRow = $getAssessmentStmt->get_result()->fetch_assoc();
        $getAssessmentStmt->close();
        
        if (!empty($assessmentDataRow['assessment_data'])) {
            $assessmentData = json_decode($assessmentDataRow['assessment_data'], true);
            
            // Reset all topic scores to 0
            if (isset($assessmentData['topic_scores'])) {
                foreach ($assessmentData['topic_scores'] as $topicId => $topicData) {
                    $assessmentData['topic_scores'][$topicId]['percentage'] = 0;
                    $assessmentData['topic_scores'][$topicId]['score'] = 0;
                }
            }
            
            // Update assessment_data with reset scores
            $updateAssessmentData = $conn->prepare("UPDATE students SET assessment_data = ? WHERE id = ?");
            $assessmentDataJson = json_encode($assessmentData);
            $updateAssessmentData->bind_param("si", $assessmentDataJson, $studentRow['id']);
            $updateAssessmentData->execute();
            $updateAssessmentData->close();
        }
    } else {
        // Failed - just record the attempt, don't change level
        // Optionally update assessment_details to track the failed attempt
        if (!isset($assessmentDetails['promotion_attempts'])) {
            $assessmentDetails['promotion_attempts'] = [];
        }
        $assessmentDetails['promotion_attempts'][] = [
            'from' => $current_level,
            'to' => $target_level,
            'score' => $score,
            'date' => date('Y-m-d H:i:s'),
            'passed' => false
        ];
        
        $updateStmt = $conn->prepare("
            UPDATE students 
            SET assessment_details = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $detailsJson = json_encode($assessmentDetails);
        $updateStmt->bind_param("si", $detailsJson, $studentRow['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'passed' => $passed,
        'score' => $score,
        'new_level' => $passed ? $target_level : $current_level,
        'message' => $passed ? 'Congratulations! Level promoted successfully!' : 'Test recorded. Keep studying!'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error saving results: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
