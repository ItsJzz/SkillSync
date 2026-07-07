<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit feedback.']);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get form data
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['username'];
$student_email = $_SESSION['email'] ?? '';
$feedback_type = $_POST['feedback_type'] ?? '';
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? intval($_POST['rating']) : null;

// Validation
$errors = [];

if (empty($feedback_type)) {
    $errors[] = 'Please select a feedback type.';
}

if (empty($subject)) {
    $errors[] = 'Subject is required.';
} elseif (strlen($subject) < 5) {
    $errors[] = 'Subject must be at least 5 characters long.';
} elseif (strlen($subject) > 200) {
    $errors[] = 'Subject must not exceed 200 characters.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
} elseif (strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters long.';
}

// Validate feedback type
$valid_types = ['concern', 'satisfaction', 'feature_request', 'bug_report', 'ui_improvement', 'general'];
if (!in_array($feedback_type, $valid_types)) {
    $errors[] = 'Invalid feedback type.';
}

// Validate priority
$valid_priorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $valid_priorities)) {
    $errors[] = 'Invalid priority level.';
}

// Validate rating for satisfaction feedback
if ($feedback_type === 'satisfaction') {
    if ($rating === null || $rating < 1 || $rating > 5) {
        $errors[] = 'Please provide a rating between 1 and 5 for satisfaction feedback.';
    }
} else {
    $rating = null; // Clear rating for non-satisfaction feedback
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit();
}

// Get student email if not in session
if (empty($student_email)) {
    $emailQuery = "SELECT email FROM login_credentials WHERE id = ?";
    $stmt = $conn->prepare($emailQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $student_email = $row['email'];
    }
    $stmt->close();
}

// Insert feedback into database
$insertQuery = "INSERT INTO student_feedback 
                (student_id, student_name, student_email, feedback_type, subject, message, rating, priority, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("isssssss", $student_id, $student_name, $student_email, $feedback_type, $subject, $message, $rating, $priority);

if ($stmt->execute()) {
    $feedback_id = $stmt->insert_id;
    
    // Create success message based on feedback type
    $messages = [
        'concern' => 'Your concern has been submitted. We will address it as soon as possible.',
        'satisfaction' => 'Thank you for your positive feedback! We appreciate your satisfaction.',
        'feature_request' => 'Your feature request has been submitted. We will review it for future updates.',
        'bug_report' => 'Thank you for reporting this bug. Our team will investigate and fix it.',
        'ui_improvement' => 'Your UI improvement suggestion has been received. Thank you!',
        'general' => 'Your feedback has been submitted successfully. Thank you!'
    ];
    
    $message = $messages[$feedback_type] ?? 'Feedback submitted successfully!';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'feedback_id' => $feedback_id
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to submit feedback. Please try again.'
    ]);
}

$stmt->close();
$conn->close();
?>
