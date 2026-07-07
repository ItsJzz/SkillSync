<?php
// Debug script to check dashboard data
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed");

$user_id = 6;

echo "=== CHECKING USER DATA ===\n\n";

// Check students table
$result = $conn->query("SELECT * FROM students WHERE user_id = $user_id");
if ($row = $result->fetch_assoc()) {
    echo "Students Table:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "User ID: " . $row['user_id'] . "\n";
    echo "Assessment Data: " . substr($row['assessment_data'], 0, 200) . "...\n";
    echo "Assessment Details: " . $row['assessment_details'] . "\n\n";
    
    $assessment_details = json_decode($row['assessment_details'], true);
    echo "Class Level: " . ($assessment_details['class_level'] ?? 'NOT SET') . "\n\n";
}

// Check post-test attempts
echo "=== POST-TEST ATTEMPTS ===\n";
$result = $conn->query("SELECT topic_id, attempt_number, score, completed_at FROM user_post_test_attempts WHERE user_id = $user_id ORDER BY topic_id, attempt_number");
while ($row = $result->fetch_assoc()) {
    echo "Topic " . $row['topic_id'] . " - Attempt " . $row['attempt_number'] . ": Score " . $row['score'] . "% - " . ($row['completed_at'] ? "Completed" : "In Progress") . "\n";
}
echo "\n";

// Check activity scores
echo "=== ACTIVITY SCORES ===\n";
$result = $conn->query("SELECT topic_id, COUNT(*) as count FROM student_activity_scores WHERE student_id = $user_id GROUP BY topic_id");
while ($row = $result->fetch_assoc()) {
    echo "Topic " . $row['topic_id'] . ": " . $row['count'] . " activities\n";
}
echo "\n";

// Check save_progress
echo "=== SAVE PROGRESS (Activity Completion) ===\n";
$result = $conn->query("SELECT topic_id, level, COUNT(*) as count FROM save_progress WHERE user_id = $user_id GROUP BY topic_id, level ORDER BY topic_id, level");
while ($row = $result->fetch_assoc()) {
    echo "Topic " . $row['topic_id'] . " - Level " . $row['level'] . ": " . $row['count'] . " completed\n";
}
echo "\n";

// Check overall calculation
echo "=== OVERALL SCORE CALCULATION ===\n";
$subject_id = 3;
$all_topics_stmt = $conn->prepare("
    SELECT 
        t.id, t.name,
        JSON_EXTRACT(s.assessment_data, CONCAT('$.topic_scores.\"', t.id, '\".percentage')) as pre_test_score,
        (
            SELECT MAX(upta.score)
            FROM user_post_test_attempts upta
            WHERE upta.user_id = ? AND upta.topic_id = t.id AND upta.completed_at IS NOT NULL
        ) as post_test_score
    FROM topics t
    LEFT JOIN students s ON (s.id = ? OR s.user_id = ?)
    WHERE t.subject_id = ?
    ORDER BY t.id
");
$all_topics_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $subject_id);
$all_topics_stmt->execute();
$all_topics = $all_topics_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_score = 0;
foreach ($all_topics as $topic) {
    $post_score = $topic['post_test_score'] ?? 0;
    $pre_score = $topic['pre_test_score'] ? floatval($topic['pre_test_score']) : 0;
    $best_score = ($post_score > 0) ? $post_score : $pre_score;
    $total_score += $best_score;
    
    echo "Topic " . $topic['id'] . " (" . $topic['name'] . "):\n";
    echo "  Pre-test: " . $pre_score . "%\n";
    echo "  Post-test: " . $post_score . "%\n";
    echo "  Best Score Used: " . $best_score . "%\n\n";
}

$overall = count($all_topics) > 0 ? ($total_score / count($all_topics)) : 0;
echo "Overall Score: " . round($overall, 2) . "%\n";
echo "Total Topics: " . count($all_topics) . "\n";

$conn->close();
?>
