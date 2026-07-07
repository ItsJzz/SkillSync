<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("You must be logged in as an admin.");
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $title = $_POST['title'];
    $url = $_POST['url'];
    
    // Validate inputs
    if (empty($type) || empty($title) || empty($url)) {
        $error = "All fields are required.";
    } else {
        // Convert YouTube URLs to embed format
        if ($type === 'video') {
            // Check if the URL is a valid YouTube URL
            if (preg_match('/youtube\.com\/(?:[^\/]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                $video_id = $matches[1];
                $url = "https://www.youtube.com/embed/" . $video_id;  // Convert to embed URL
            } else {
                $error = "Invalid YouTube URL.";
            }
        }
        
        if (!isset($error)) {
            // Connect to the database
            require_once 'db_connect.php';

            // Insert the new material into the database
            $stmt = $conn->prepare("INSERT INTO learning_materials (topic_id, type, title, url, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $_POST['topic_id'], $type, $title, $url);
            
            if ($stmt->execute()) {
                $success_message = "Learning material added successfully!";
            } else {
                $error_message = "Failed to add material. Please try again.";
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Learning Material</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f9f9f9;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Learning Material</h2>
    
    <!-- Display Success or Error message -->
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <?php if (isset($success_message)): ?>
        <div class="success"><?= $success_message ?></div>
    <?php endif; ?>

    <!-- Form to add new material -->
    <form method="POST" action="insert_material.php">
        <div class="form-group">
            <label for="topic_id">Topic ID:</label>
            <input type="text" id="topic_id" name="topic_id" required>
        </div>

        <div class="form-group">
            <label for="type">Material Type:</label>
            <select id="type" name="type" required>
                <option value="video">Video</option>
                <option value="pdf">PDF</option>
                <option value="simulation">Simulation</option>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="url">URL:</label>
            <input type="text" id="url" name="url" required>
        </div>

        <button type="submit">Add Material</button>
    </form>
</div>

</body>
</html>
