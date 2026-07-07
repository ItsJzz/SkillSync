<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$student_id = $_SESSION['user_id'];

// Get assessment data
$stmt = $conn->prepare("SELECT assessment_data, assessment_details FROM students WHERE id = ? OR user_id = ? LIMIT 1");
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

echo "<h2>Raw Assessment Data</h2>";
echo "<pre style='background: #f8f9fa; padding: 20px; border-radius: 10px;'>";
echo "Assessment Data:\n";
echo $student['assessment_data'];
echo "\n\n";
echo "Parsed Assessment Data:\n";
$assessmentData = json_decode($student['assessment_data'], true);
print_r($assessmentData);
echo "</pre>";

echo "<h2>Topic Scores Detail</h2>";
if (isset($assessmentData['topic_scores'])) {
    foreach ($assessmentData['topic_scores'] as $topic_id => $data) {
        echo "<div style='background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea;'>";
        echo "<strong>Topic ID: $topic_id - {$data['name']}</strong><br>";
        echo "Quiz Correct: {$data['quiz_correct']} / {$data['quiz_total']}<br>";
        echo "Simulation Correct: {$data['simulation_correct']} / {$data['simulation_total']}<br>";
        echo "Hands-on: {$data['hands_on_score']} / {$data['hands_on_max']}<br>";
        echo "Total: {$data['totalScore']} / {$data['maxScore']} = {$data['percentage']}%";
        echo "</div>";
    }
}
?>
