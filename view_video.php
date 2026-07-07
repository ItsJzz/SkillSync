<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$material_id = intval($_GET['id'] ?? 0);
if ($material_id <= 0) {
    die("Invalid material ID.");
}

require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch material details
$stmt = $conn->prepare("SELECT lm.*, t.name as topic_name 
                        FROM learning_materials lm 
                        JOIN topics t ON lm.topic_id = t.id 
                        WHERE lm.id = ? AND lm.type = 'video'");
$stmt->bind_param("i", $material_id);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$material) {
    die("Video material not found.");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($material['title']) ?> - SkillSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f7f9fb; 
        }
        .container { 
            max-width: 1000px; 
            margin: auto; 
            background: #fff; 
            border-radius: 12px; 
            padding: 30px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .header h1 { 
            color: #2c3e50; 
            margin-bottom: 10px; 
        }
        .topic-badge { 
            background: #27ae60; 
            color: white; 
            padding: 5px 15px; 
            border-radius: 20px; 
            font-size: 0.9rem; 
        }
        .video-container { 
            position: relative; 
            width: 100%; 
            height: 0; 
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            margin: 20px 0; 
        }
        .video-container iframe { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            border: none; 
            border-radius: 8px; 
        }
        .actions { 
            text-align: center; 
            margin-top: 30px; 
        }
        .btn { 
            padding: 12px 24px; 
            margin: 0 10px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: bold; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s ease; 
        }
        .btn-primary { 
            background: #27ae60; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #219a52; 
        }
        .btn-secondary { 
            background: #6c757d; 
            color: white; 
        }
        .btn-secondary:hover { 
            background: #5a6268; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-play-circle"></i> <?= htmlspecialchars($material['title']) ?></h1>
            <span class="topic-badge"><?= htmlspecialchars($material['topic_name']) ?></span>
        </div>
        
        <div class="video-container">
            <iframe src="<?= htmlspecialchars($material['url']) ?>" allowfullscreen></iframe>
        </div>
        
        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            <a href="Activity/activity_list.php?topic_id=<?= $material['topic_id'] ?>" class="btn btn-primary">
                <i class="fas fa-code"></i> Try Activities for This Topic
            </a>
            <a href="recommendation.php?topic_id=<?= $material['topic_id'] ?>" class="btn btn-primary">
                <i class="fas fa-lightbulb"></i> Get More Recommendations
            </a>
        </div>
    </div>
</body>
</html>