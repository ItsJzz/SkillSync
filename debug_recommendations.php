<?php
// Debug script to check recommendations data for user 4
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = 4; // Your user ID

echo "<h2>Debugging Recommendations Data for User $student_id</h2>";

// Check students table
$stmt = $conn->prepare("SELECT assessment_data FROM students WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$studentRow = $result->fetch_assoc();
$stmt->close();

if ($studentRow) {
    echo "<h3>Assessment Data Found:</h3>";
    echo "<pre>";
    $assessmentData = json_decode($studentRow['assessment_data'], true);
    print_r($assessmentData);
    echo "</pre>";
    
    if ($assessmentData && isset($assessmentData['topic_scores'])) {
        echo "<h3>Topic Scores:</h3>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Topic ID</th><th>Topic Name</th><th>Quiz Correct</th><th>Quiz Total</th><th>Simulation Correct</th><th>Simulation Total</th><th>Percentage</th></tr>";
        
        foreach ($assessmentData['topic_scores'] as $topic_id => $topicData) {
            echo "<tr>";
            echo "<td>$topic_id</td>";
            echo "<td>" . ($topicData['name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($topicData['quiz_correct'] ?? 0) . "</td>";
            echo "<td>" . ($topicData['quiz_total'] ?? 0) . "</td>";
            echo "<td>" . ($topicData['simulation_correct'] ?? 0) . "</td>";
            echo "<td>" . ($topicData['simulation_total'] ?? 0) . "</td>";
            echo "<td>" . ($topicData['percentage'] ?? 0) . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No topic_scores found in assessment_data</p>";
    }
} else {
    echo "<p style='color: red;'>No student record found for user $student_id</p>";
}

// Check learning materials
echo "<h3>Available Learning Materials:</h3>";
$materialsRes = $conn->query("SELECT topic_id, type, COUNT(*) as count FROM learning_materials GROUP BY topic_id, type");
if ($materialsRes && $materialsRes->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Topic ID</th><th>Type</th><th>Count</th></tr>";
    while ($row = $materialsRes->fetch_assoc()) {
        echo "<tr><td>{$row['topic_id']}</td><td>{$row['type']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No learning materials found</p>";
}

// Check activities
echo "<h3>Activities Files:</h3>";
$activitiesPath = 'Activity/activities.json';
if (file_exists($activitiesPath)) {
    echo "<p>✓ activities.json exists</p>";
    $activitiesData = json_decode(file_get_contents($activitiesPath), true);
    echo "<pre>";
    print_r(array_keys($activitiesData));
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ activities.json NOT found</p>";
}

$conn->close();
?>
