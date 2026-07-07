<?php
// load_topics.php
require_once 'db_connect.php';

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$topics = [];

if ($subject_id > 0) {
  $sql = "SELECT id, name, redirect_url FROM topics WHERE subject_id=? ORDER BY name ASC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $subject_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $topics[] = $row;
  }
}

header('Content-Type: application/json');
echo json_encode($topics);
