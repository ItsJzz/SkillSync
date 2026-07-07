<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['topics'=>[], 'scores'=>[]]);
    exit();
}

$student_id = $_SESSION['user_id'];
$module = $_GET['module'] ?? 'OOP1';

require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Fetch highest score per topic for this student in the selected module
$stmt = $conn->prepare("
    SELECT t.name AS topic, 
           COALESCE((
               SELECT MAX(sas.avg_score)
               FROM student_activity_scores sas
               WHERE sas.topic_id = t.id AND sas.student_id = ?
           ), 0) AS score
    FROM topics t
    WHERE t.subject_id = (SELECT id FROM subjects WHERE code = ?)
    ORDER BY t.id ASC
");

$stmt->bind_param("is", $student_id, $module);
$stmt->execute();
$result = $stmt->get_result();

$topics = [];
$scores = [];
while($row = $result->fetch_assoc()){
    $topics[] = $row['topic'];
    $scores[] = (float)$row['score'];
}

$stmt->close();
$conn->close();

echo json_encode(['topics'=>$topics, 'scores'=>$scores]);
?>
