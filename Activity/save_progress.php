<?php
// save_progress.php
session_start();
require_once __DIR__ . "/../db_connect.php"; // adjust path
require_once __DIR__ . "/sas_converter.php"; // include aggregator

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}
$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// accept application/x-www-form-urlencoded or JSON
$topicId = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : null;
$level   = isset($_POST['level']) ? intval($_POST['level']) : null;
$score   = isset($_POST['score']) ? intval($_POST['score']) : null;

// If JSON body
if (($topicId === null || $level === null || $score === null) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $topicId = $topicId ?? intval($json['topic_id'] ?? 0);
        $level   = $level   ?? intval($json['level']   ?? 0);
        $score   = $score   ?? intval($json['score']   ?? 0);
    }
}

if (!$topicId || !$level) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields (topic_id, level)']);
    exit;
}

try {
    // 1. Insert progress
    $stmt = $conn->prepare("
        INSERT INTO save_progress (user_id, topic_id, level, score, attempt_time)
        VALUES (?, ?, ?, ?, NOW())
    ");
    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param("iiii", $userId, $topicId, $level, $score);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $insertId = $conn->insert_id;

    // 2. Figure out how many levels this topic has
    $jsonFile = __DIR__ . "/activities.json";
    $totalLevels = 0;
    if (file_exists($jsonFile)) {
        $activities = json_decode(file_get_contents($jsonFile), true);
        foreach ($activities as $node) {
            if (isset($node['topic_id']) && $node['topic_id'] == $topicId) {
                $totalLevels = isset($node['instructions']) ? count($node['instructions']) : 0;
                break;
            }
        }
    }

    // Fallback if not found in JSON
    if ($totalLevels <= 0) {
        $totalLevels = 5;
    }

    // 3. Build module key (you can adjust naming)
    $moduleKey = "topic_" . $topicId;

    // 4. Run aggregator
    $aggDone = aggregateTopicProgress($conn, $userId, $topicId, $totalLevels, $moduleKey);

    // 5. Check if user completed all 5 levels for post-test eligibility
    $postTestEligible = checkPostTestEligibility($conn, $userId, $topicId);

    // 6. Response
    echo json_encode([
        'status' => 'ok',
        'insert_id' => $insertId,
        'aggregate_updated' => $aggDone,
        'post_test_eligible' => $postTestEligible,
        'message' => $postTestEligible ? 'Congratulations! You\'ve completed all levels. Post-test is now available!' : null
    ]);
    exit;

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $ex->getMessage()]);
    exit;
}

/**
 * Check if user has completed all 5 levels and update post-test eligibility
 */
function checkPostTestEligibility($conn, $userId, $topicId) {
    try {
        // Check if user has completed all 5 levels for this topic
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT level) as completed_levels
            FROM save_progress 
            WHERE user_id = ? AND topic_id = ? AND level BETWEEN 1 AND 5
        ");
        $stmt->bind_param("ii", $userId, $topicId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $completedLevels = $result['completed_levels'] ?? 0;
        $stmt->close();

        // If all 5 levels completed, create or update eligibility record
        if ($completedLevels >= 5) {
            $stmt = $conn->prepare("
                INSERT INTO user_post_test_eligibility 
                (user_id, topic_id, completed_all_levels, completion_date, post_test_available) 
                VALUES (?, ?, TRUE, NOW(), TRUE)
                ON DUPLICATE KEY UPDATE 
                completed_all_levels = TRUE, 
                completion_date = NOW(), 
                post_test_available = TRUE
            ");
            $stmt->bind_param("ii", $userId, $topicId);
            $stmt->execute();
            $stmt->close();
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Post-test eligibility check error: " . $e->getMessage());
        return false;
    }
}
