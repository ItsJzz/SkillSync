<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $topic_id = intval($_POST['topic_id']);
    $url = "";
    
    // Validate required fields
    if (empty($title) || empty($type) || empty($topic_id)) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle different material types
        switch ($type) {
            case 'video':
                // Handle video file upload
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/videos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Get file extension
                    $fileExt = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
                    
                    if (!in_array($fileExt, $allowedExts)) {
                        $error = "Invalid file format. Allowed formats: MP4, AVI, MOV, MKV, WebM";
                        break;
                    }
                    
                    // Check file size (100MB max)
                    if ($_FILES['video_file']['size'] > 100 * 1024 * 1024) {
                        $error = "File size too large. Maximum size is 100MB.";
                        break;
                    }
                    
                    $fileName = 'video_' . time() . '_' . uniqid() . '.' . $fileExt;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath)) {
                        $fileSize = $_FILES['video_file']['size'];
                        
                        // Insert into database with file_path and file_size
                        $stmt = $conn->prepare("INSERT INTO learning_materials (topic_id, type, title, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssi", $topic_id, $type, $title, $uploadPath, $fileSize);
                        
                        if ($stmt->execute()) {
                            header("Location: admin/manage_materials.php?success=1");
                            exit();
                        } else {
                            $error = "Database error: " . $conn->error;
                            // Delete uploaded file if database insert fails
                            if (file_exists($uploadPath)) {
                                unlink($uploadPath);
                            }
                        }
                    } else {
                        $error = "Failed to upload video file.";
                    }
                } else {
                    $error = "Video file is required.";
                }
                // Skip the rest of the switch for videos since we handle everything above
                $conn->close();
                if (!empty($error)) {
                    header("Location: admin/manage_materials.php?error=" . urlencode($error));
                }
                exit();
                break;
                
            case 'pdf':
                // Handle PDF file upload
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'modules/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['pdf_file']['name']);
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $uploadPath)) {
                        $url = $uploadPath;
                    } else {
                        $error = "Failed to upload PDF file.";
                    }
                } else {
                    $error = "PDF file is required.";
                }
                break;
                
            case 'simulation':
                $url = trim($_POST['simulation_path']);
                if (empty($url)) {
                    $error = "Simulation file path is required.";
                }
                break;
                
            default:
                $error = "Invalid material type.";
        }
        
        // Insert into database if no errors
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO learning_materials (topic_id, type, title, url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $topic_id, $type, $title, $url);
            
            if ($stmt->execute()) {
                $message = "Learning material uploaded successfully!";
                header("Location: admin/manage_materials.php?success=1");
                exit();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

$conn->close();

// Redirect back with error message
if (!empty($error)) {
    header("Location: admin/manage_materials.php?error=" . urlencode($error));
    exit();
}
?>