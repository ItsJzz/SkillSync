<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Database connection
require_once 'db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $theme = $_POST['theme'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;

    // Validate inputs
    if (empty($fullname) || empty($email)) {
        $_SESSION['error_message'] = "Full name and email are required!";
        header("Location: settings.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format!";
        header("Location: settings.php");
        exit();
    }

    // Check if email already exists for another user
    $checkEmailQuery = "SELECT id FROM login_credentials WHERE email = ? AND id != ?";
    $checkStmt = $conn->prepare($checkEmailQuery);
    $checkStmt->bind_param("si", $email, $student_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $_SESSION['error_message'] = "Email address is already in use by another account!";
        $checkStmt->close();
        header("Location: settings.php");
        exit();
    }
    $checkStmt->close();

    // Update user settings
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE login_credentials SET username = ?, email = ?, password = ?, theme_preference = ?, email_notifications = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssssii", $fullname, $email, $hashed_password, $theme, $notifications, $student_id);
    } else {
        // Update without changing password
        $updateQuery = "UPDATE login_credentials SET username = ?, email = ?, theme_preference = ?, email_notifications = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sssii", $fullname, $email, $theme, $notifications, $student_id);
    }

    if ($updateStmt->execute()) {
        $_SESSION['success_message'] = "Settings updated successfully!";
        
        // Update session username if changed
        $_SESSION['username'] = $fullname;
    } else {
        $_SESSION['error_message'] = "Failed to update settings. Please try again.";
    }

    $updateStmt->close();
    $conn->close();

    header("Location: settings.php");
    exit();
} else {
    // Redirect if accessed directly
    header("Location: settings.php");
    exit();
}
?>
