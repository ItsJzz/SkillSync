<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error"=>"Unauthorized"]);
    exit();
}

$student_id = $_SESSION['user_id'];

if (!isset($_GET['topic_id'])) {
    echo json_encode(["error"=>"Missing topic_id"]);
    exit();
}

$topic_id = intval($_GET['topic_id']);

// DB connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$stmt = $conn->prepare("
    SELECT avg_score, DATE(date_created) as date
    FROM student_activity_scores
    WHERE student_id = ? AND topic_id = ?
    ORDER BY date_created ASC
");
$stmt->bind_param("ii", $student_id, $topic_id);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
$scores = [];
while($row = $result->fetch_assoc()){
    $dates[] = $row['date'];
    $scores[] = (float)$row['avg_score'];
}

$stmt->close();
$conn->close();

echo json_encode(["dates"=>$dates,"scores"=>$scores]);
