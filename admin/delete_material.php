<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once '../db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['material_id'])) {
    $material_id = intval($_POST['material_id']);
    
    // Get material info before deleting
    $stmt = $conn->prepare("SELECT type, url FROM learning_materials WHERE id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete file if it's a PDF
        if ($row['type'] === 'pdf' && file_exists('../' . $row['url'])) {
            unlink('../' . $row['url']);
        }
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM learning_materials WHERE id = ?");
        $deleteStmt->bind_param("i", $material_id);
        
        if ($deleteStmt->execute()) {
            header("Location: manage_materials.php?deleted=1");
        } else {
            header("Location: manage_materials.php?error=" . urlencode("Failed to delete material"));
        }
    } else {
        header("Location: manage_materials.php?error=" . urlencode("Material not found"));
    }
} else {
    header("Location: manage_materials.php");
}

$conn->close();
?>