<?php
session_start();
if (!isset($_SESSION['user_id'])) exit("Login required");

$student_id = $_SESSION['user_id'];
$material_id = intval($_POST['material_id'] ?? 0);

if ($material_id > 0) {
    require_once 'db_connect.php';
    if ($conn->connect_error) die("DB error");

    // insert or ignore if already logged
    $stmt = $conn->prepare("
        INSERT IGNORE INTO student_video_progress (student_id, material_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $student_id, $material_id);
    $stmt->execute();

    echo "Logged video watch";
}
?>
