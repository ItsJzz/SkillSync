<?php
header("Content-Type: application/json");

// --- DB Connection ---
require_once 'db_connect.php';

// --- Validate topic_id ---
if (!isset($_GET['topic_id']) || !is_numeric($_GET['topic_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid topic_id"]);
    exit;
}

$topic_id = intval($_GET['topic_id']);

// --- Fetch videos for the topic ---
$sql = "SELECT id, title, description, url, file_path 
        FROM videos 
        WHERE topic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$result = $stmt->get_result();

$videos = [];
while ($row = $result->fetch_assoc()) {
    $videos[] = [
        "id"          => $row["id"],
        "title"       => $row["title"],
        "description" => $row["description"],
        "url"         => $row["url"],       // for YouTube embeds
        "file_path"   => $row["file_path"]  // for local uploads
    ];
}

// --- Return JSON ---
echo json_encode($videos);

$stmt->close();
$conn->close();
