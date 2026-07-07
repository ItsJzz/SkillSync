<?php
session_start();
require_once __DIR__ . "/db_connect.php"; // adjust path

// Ensure logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
$student_id = $_SESSION['user_id'];

header('Content-Type: application/json');

// Fetch all topics
$topics_sql = "SELECT id, name FROM topics ORDER BY id ASC";
$topics_result = $conn->query($topics_sql);

$recommendations = [];

while ($topic = $topics_result->fetch_assoc()) {
    $topic_id = $topic['id'];
    $topic_name = $topic['name'];

    // --- Pre-test score ---
    $pre_stmt = $conn->prepare("
        SELECT score 
        FROM student_tests 
        WHERE student_id=? AND topic_id=? AND test_type='pre'
        ORDER BY attempt_date DESC LIMIT 1
    ");
    $pre_stmt->bind_param("ii", $student_id, $topic_id);
    $pre_stmt->execute();
    $pre_stmt->bind_result($pre_score);
    $pre_stmt->fetch();
    $pre_stmt->close();

    if ($pre_score === null) $pre_score = 0;

    // --- Activity score ---
    $act_stmt = $conn->prepare("
        SELECT MAX(avg_score) 
        FROM student_activity_scores 
        WHERE student_id=? AND topic_id=?
    ");
    $act_stmt->bind_param("ii", $student_id, $topic_id);
    $act_stmt->execute();
    $act_stmt->bind_result($activity_score);
    $act_stmt->fetch();
    $act_stmt->close();

    if ($activity_score === null) $activity_score = 0;

    // --- Triangulation rules ---
    $status = "Strong";
    $resources = [];

    if ($pre_score < 50 && $activity_score < 80) {
        $status = "Weakness";

        // Fetch learning resources
        $res_stmt = $conn->prepare("
            SELECT title, url, type, description 
            FROM learning_resources 
            WHERE topic_id=?
        ");
        $res_stmt->bind_param("i", $topic_id);
        $res_stmt->execute();
        $res_result = $res_stmt->get_result();

        while ($row = $res_result->fetch_assoc()) {
            $resources[] = $row;
        }

        $res_stmt->close();

    } elseif ($pre_score < 50 && $activity_score >= 80) {
        $status = "Improved";
    }

    // Push to results
    $recommendations[] = [
        "topic_id" => $topic_id,
        "topic_name" => $topic_name,
        "pre_score" => $pre_score,
        "activity_score" => $activity_score,
        "status" => $status,
        "resources" => $resources
    ];
}

// Output JSON
echo json_encode($recommendations, JSON_PRETTY_PRINT);
