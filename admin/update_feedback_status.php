<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$feedback_id = $input['feedback_id'] ?? 0;
$status = $input['status'] ?? '';

// Validate
if (empty($feedback_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$valid_statuses = ['pending', 'reviewed', 'in_progress', 'resolved', 'closed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit();
}

// Update status
$query = "UPDATE student_feedback SET status = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $status, $feedback_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
?>
