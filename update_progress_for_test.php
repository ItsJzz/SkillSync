<?php
// Quick script to update student progress to 75%+ for promotion test debugging

require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get the student ID for jameszzz
$result = $conn->query("SELECT id FROM login_credentials WHERE username = 'jameszzz' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $student_id = $row['id'];
    
    // Create sample assessment data with 76.67% progress (above 75% threshold)
    $assessmentData = [
        'format' => 'scalable',
        'total_score' => 115,  // 115/150 = 76.67%
        'max_total_score' => 150,
        'overall_percentage' => 76.67,
        'topic_scores' => [
            13 => [
                'name' => 'Introduction to OOP Concepts',
                'quiz_correct' => 7,
                'quiz_total' => 8,
                'simulation_correct' => 6,
                'simulation_total' => 8,
                'hands_on_score' => 9,
                'hands_on_max' => 10,
                'percentage' => 80.0,
                'hasActivity' => true
            ],
            14 => [
                'name' => 'Classes and Objects',
                'quiz_correct' => 6,
                'quiz_total' => 8,
                'simulation_correct' => 7,
                'simulation_total' => 8,
                'hands_on_score' => 8,
                'hands_on_max' => 10,
                'percentage' => 76.0,
                'hasActivity' => true
            ],
            15 => [
                'name' => 'Encapsulation',
                'quiz_correct' => 6,
                'quiz_total' => 8,
                'simulation_correct' => 6,
                'simulation_total' => 8,
                'hands_on_score' => 7,
                'hands_on_max' => 10,
                'percentage' => 73.0,
                'hasActivity' => true
            ],
            16 => [
                'name' => 'Inheritance',
                'quiz_correct' => 7,
                'quiz_total' => 8,
                'simulation_correct' => 6,
                'simulation_total' => 8,
                'hands_on_score' => 8,
                'hands_on_max' => 10,
                'percentage' => 78.0,
                'hasActivity' => true
            ],
            17 => [
                'name' => 'Polymorphism',
                'quiz_correct' => 6,
                'quiz_total' => 8,
                'simulation_correct' => 7,
                'simulation_total' => 8,
                'hands_on_score' => 7,
                'hands_on_max' => 10,
                'percentage' => 75.0,
                'hasActivity' => true
            ]
        ]
    ];
    
    // Keep current class level as Beginner so promotion test shows up
    $assessmentDetails = [
        'class_level' => 'Beginner'
    ];
    
    $dataJson = json_encode($assessmentData);
    $detailsJson = json_encode($assessmentDetails);
    
    $stmt = $conn->prepare("UPDATE students SET assessment_data = ?, assessment_details = ? WHERE id = ? OR user_id = ?");
    $stmt->bind_param('ssii', $dataJson, $detailsJson, $student_id, $student_id);
    
    if ($stmt->execute()) {
        echo "✅ SUCCESS! Updated student data:\n\n";
        echo "Student ID: $student_id\n";
        echo "Username: jameszzz\n";
        echo "Class Level: Beginner\n";
        echo "Overall Progress: 76.67% (115/150 points)\n";
        echo "\n=================================\n";
        echo "✨ You should now see the 'Take Level Promotion Test' button on your dashboard!\n";
        echo "=================================\n\n";
        echo "Topic Scores:\n";
        echo "- Introduction to OOP Concepts: 80%\n";
        echo "- Classes and Objects: 76%\n";
        echo "- Encapsulation: 73%\n";
        echo "- Inheritance: 78%\n";
        echo "- Polymorphism: 75%\n";
        echo "\nRefresh your dashboard to see the changes!\n";
    } else {
        echo "❌ Error updating: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "❌ Student 'jameszzz' not found in database\n";
}

$conn->close();
?>
