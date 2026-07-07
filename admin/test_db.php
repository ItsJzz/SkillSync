<?php
// Quick test to check subjects table
require_once '../db_connect.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Test</h1>";

// Check subjects
$subjectsQuery = "SELECT id, name FROM subjects ORDER BY name ASC";
$subjectsResult = $conn->query($subjectsQuery);

echo "<h2>Subjects (" . $subjectsResult->num_rows . " found):</h2>";
while ($subject = $subjectsResult->fetch_assoc()) {
    echo "ID: " . $subject['id'] . " - Name: " . $subject['name'] . "<br>";
}

// Check learning materials
$materialsQuery = "SELECT id, title, type FROM learning_materials ORDER BY created_at DESC LIMIT 5";
$materialsResult = $conn->query($materialsQuery);

echo "<h2>Recent Materials (" . $materialsResult->num_rows . " found):</h2>";
while ($material = $materialsResult->fetch_assoc()) {
    echo "ID: " . $material['id'] . " - Title: " . $material['title'] . " - Type: " . $material['type'] . "<br>";
}

$conn->close();
?>