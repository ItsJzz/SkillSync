<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Log logout activity
$admin_id = $_SESSION['admin_id'];
$activity = "Admin logged out";
$ip_address = $_SERVER['REMOTE_ADDR'];

$logQuery = "INSERT INTO admin_activity_logs (admin_id, activity, ip_address) VALUES (?, ?, ?)";
$logStmt = $conn->prepare($logQuery);
$logStmt->bind_param("iss", $admin_id, $activity, $ip_address);
$logStmt->execute();
$logStmt->close();

$conn->close();

// Destroy session
session_destroy();

// Redirect to login page
header("Location: admin_login.php?message=logged_out");
exit();
?>