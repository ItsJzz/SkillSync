<?php
session_start();

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Source folder containing videos
$sourceFolder = 'C:\\Users\\Admin\\Videos\\bago\\';

// Destination folder in your project
$destFolder = 'uploads/videos/';
if (!is_dir($destFolder)) {
    mkdir($destFolder, 0755, true);
}

// Mapping of video files to topic IDs based on their names
$videoMappings = [
    // Web Development 1 (Subject 5)
    'Intro_web_develop.mp4' => ['topic_id' => 27, 'title' => 'Introduction To Web Development'],
    'html_basic.mp4' => ['topic_id' => 28, 'title' => 'HTML Basics'],
    'htmlform_element.mp4' => ['topic_id' => 29, 'title' => 'HTML Forms And Input Elements'],
    'css_basic.mp4' => ['topic_id' => 30, 'title' => 'CSS Basics'],
    'cssBox.mp4' => ['topic_id' => 31, 'title' => 'CSS Box Model And Layout'],
    'introductionJava.mp4' => ['topic_id' => 32, 'title' => 'Introduction To JavaScript'],
    'java_domManipul.mp4' => ['topic_id' => 33, 'title' => 'JavaScript DOM Manipulation'],
    'event_handling_java.mp4' => ['topic_id' => 34, 'title' => 'Event Handling In JavaScript'],
    
    // Web Development 2 (Subject 6)
    'advanced_html.mp4' => ['topic_id' => 35, 'title' => 'Advanced HTML'],
    'advancedCSS.mp4' => ['topic_id' => 36, 'title' => 'Advance CSS'],
    'java_func_scope.mp4' => ['topic_id' => 37, 'title' => 'JavaScript Function And Scope'],
    'JavaScript_obj.mp4' => ['topic_id' => 38, 'title' => 'JavaScript Objects And Arrays'],
    'es6_feature.mp4' => ['topic_id' => 39, 'title' => 'ES6 Features'],
    'asynchronous_java.mp4' => ['topic_id' => 40, 'title' => 'Asychronous JavaScript'],
    'ajaxAndAPI.mp4' => ['topic_id' => 41, 'title' => 'AJAX and Fetch API'],
    'intro_web_api.mp4' => ['topic_id' => 42, 'title' => 'Introduction To Web APIs'],
    
    // Event Driven Programming (Subject 7)
    'introduction_to_event_drivenprog.mp4' => ['topic_id' => 43, 'title' => 'Introduction To Event Driven Progamming'],
    'event_handling_awt.mp4' => ['topic_id' => 44, 'title' => 'Event Handling In AWT and Swing'],
    'Advancedswing.mp4' => ['topic_id' => 45, 'title' => 'Advance Swing Components'],
    'layout_management.mp4' => ['topic_id' => 46, 'title' => 'Layout Management'],
    'introduction_to_database.mp4' => ['topic_id' => 47, 'title' => 'Introduction To DataBase'],
    'crud_operation.mp4' => ['topic_id' => 48, 'title' => 'CRUD Operations Using JDBC'],
    'exception_handling.mp4' => ['topic_id' => 49, 'title' => 'Exception Handling And Best Practices'],
];

$successCount = 0;
$errorCount = 0;
$errors = [];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Bulk Video Upload</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; }
        .success { color: #27ae60; padding: 10px; margin: 5px 0; background: #d4edda; border-radius: 5px; }
        .error { color: #e74c3c; padding: 10px; margin: 5px 0; background: #f8d7da; border-radius: 5px; }
        .info { color: #3498db; padding: 10px; margin: 5px 0; background: #d1ecf1; border-radius: 5px; }
        .summary { margin-top: 20px; padding: 15px; background: #667eea; color: white; border-radius: 5px; }
        .video-item { padding: 8px; margin: 5px 0; border-left: 3px solid #667eea; padding-left: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🎥 Bulk Video Upload Process</h1>
        <p class='info'><strong>Source Folder:</strong> " . htmlspecialchars($sourceFolder) . "</p>
        <p class='info'><strong>Destination Folder:</strong> " . htmlspecialchars($destFolder) . "</p>
        <hr style='margin: 20px 0;'>";

foreach ($videoMappings as $filename => $data) {
    $sourcePath = $sourceFolder . $filename;
    
    if (!file_exists($sourcePath)) {
        echo "<div class='error'>❌ File not found: " . htmlspecialchars($filename) . "</div>";
        $errorCount++;
        $errors[] = "File not found: $filename";
        continue;
    }
    
    // Generate unique filename
    $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
    $newFilename = 'video_' . time() . '_' . uniqid() . '.' . $fileExt;
    $destPath = $destFolder . $newFilename;
    
    // Copy file
    if (copy($sourcePath, $destPath)) {
        $fileSize = filesize($destPath);
        $topic_id = $data['topic_id'];
        $title = $data['title'];
        $type = 'video';
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO learning_materials (topic_id, type, title, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $topic_id, $type, $title, $destPath, $fileSize);
        
        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            echo "<div class='video-item success'>✅ <strong>" . htmlspecialchars($title) . "</strong><br>";
            echo "   &nbsp;&nbsp;&nbsp;File: " . htmlspecialchars($filename) . "<br>";
            echo "   &nbsp;&nbsp;&nbsp;Size: " . number_format($fileSize / 1024 / 1024, 2) . " MB<br>";
            echo "   &nbsp;&nbsp;&nbsp;Topic ID: $topic_id | Material ID: $insertId</div>";
            $successCount++;
            
            // Small delay to ensure unique timestamps
            usleep(100000); // 0.1 second
        } else {
            echo "<div class='error'>❌ Database error for " . htmlspecialchars($filename) . ": " . htmlspecialchars($conn->error) . "</div>";
            $errorCount++;
            $errors[] = "Database error for $filename: " . $conn->error;
            // Delete the copied file since database insert failed
            unlink($destPath);
        }
        $stmt->close();
    } else {
        echo "<div class='error'>❌ Failed to copy: " . htmlspecialchars($filename) . "</div>";
        $errorCount++;
        $errors[] = "Failed to copy: $filename";
    }
}

echo "<div class='summary'>
        <h2>📊 Upload Summary</h2>
        <p><strong>✅ Successful uploads:</strong> $successCount</p>
        <p><strong>❌ Failed uploads:</strong> $errorCount</p>
        <p><strong>📁 Total videos processed:</strong> " . count($videoMappings) . "</p>
      </div>";

if (!empty($errors)) {
    echo "<div style='margin-top: 20px;'>
            <h3 style='color: #e74c3c;'>Error Details:</h3>";
    foreach ($errors as $error) {
        echo "<div class='error'>• " . htmlspecialchars($error) . "</div>";
    }
    echo "</div>";
}

echo "<div style='margin-top: 20px; text-align: center;'>
        <a href='admin/manage_materials.php' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
            ← Back to Manage Materials
        </a>
      </div>";

echo "</div></body></html>";

$conn->close();
?>
