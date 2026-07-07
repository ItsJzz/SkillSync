<?php
require_once __DIR__ . "/../db_connect.php";

/**
 * Aggregate progress for a topic and store/update overall score.
 *
 * @param mysqli $conn
 * @param int $userId
 * @param int $topicId
 * @param int $totalLevels (how many levels this topic has)
 * @param string $moduleKey (identifier string, e.g., 'oop1_classes_objects')
 * @return bool true if score inserted/updated, false if not all levels completed
 */
function aggregateTopicProgress($conn, $userId, $topicId, $totalLevels, $moduleKey) {
    // Fetch best score per level from unified save_progress
    $stmt = $conn->prepare("
        SELECT level, MAX(score) as best_score
        FROM save_progress
        WHERE user_id = ? AND topic_id = ?
        GROUP BY level
    ");
    $stmt->bind_param("ii", $userId, $topicId);
    $stmt->execute();
    $result = $stmt->get_result();

    $scores = [];
    while ($row = $result->fetch_assoc()) {
        $scores[$row['level']] = $row['best_score'];
    }

    // Only proceed if all levels are completed
    if (count($scores) < $totalLevels) {
        return false;
    }

    // Calculate average
    $totalScore = array_sum($scores);
    $overallAverage = round($totalScore / $totalLevels, 2);

    // Insert or update overall score in student_activity_scores
    $stmtInsert = $conn->prepare("
        INSERT INTO student_activity_scores
        (student_id, topic_id, module, avg_score, date_created, last_updated)
        VALUES (?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            avg_score = VALUES(avg_score),
            last_updated = NOW()
    ");
    $stmtInsert->bind_param("iisd", $userId, $topicId, $moduleKey, $overallAverage);
    $stmtInsert->execute();

    return true;
}
