<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$feedback_id = $_POST['feedback_id'] ?? 0;
$response = trim($_POST['response'] ?? '');
$new_status = $_POST['new_status'] ?? 'reviewed';

// Validate
if (empty($feedback_id) || empty($response)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

if (strlen($response) < 10) {
    echo json_encode(['success' => false, 'message' => 'Response must be at least 10 characters long.']);
    exit();
}

$valid_statuses = ['reviewed', 'in_progress', 'resolved', 'closed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit();
}

// Update feedback with admin response
$query = "UPDATE student_feedback 
          SET admin_response = ?, 
              admin_id = ?, 
              responded_at = NOW(), 
              status = ?,
              updated_at = NOW() 
          WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sisi", $response, $admin_id, $new_status, $feedback_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Response sent successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send response.']);
}

$stmt->close();
$conn->close();
?>
