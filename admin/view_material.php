<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once '../db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$material_id = intval($_GET['id'] ?? 0);

if ($material_id <= 0) {
    header("Location: manage_materials.php");
    exit();
}

// Get material details with topic and subject info
$materialQuery = "
    SELECT lm.*, t.name as topic_name, s.name as subject_name 
    FROM learning_materials lm 
    LEFT JOIN topics t ON lm.topic_id = t.id 
    LEFT JOIN subjects s ON t.subject_id = s.id 
    WHERE lm.id = ?
";
$stmt = $conn->prepare($materialQuery);
$stmt->bind_param("i", $material_id);
$stmt->execute();
$result = $stmt->get_result();

if ($material = $result->fetch_assoc()) {
    // Log video watch if it's a video and user is a student (avoid duplicates)
    if ($material['type'] === 'video' && $_SESSION['role'] === 'student') {
        $logQuery = "INSERT IGNORE INTO student_video_progress (student_id, material_id) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("ii", $_SESSION['user_id'], $material_id);
        $logStmt->execute();
    }
} else {
    header("Location: manage_materials.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($material['title']) ?> - SkillSync</title>
<link rel="shortcut icon" sizes="32x32" href="../LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #6BAF92 0%, #4B8B6E 100%);
    min-height: 100vh; 
}

/* Header */
.header { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 20px 0; 
    margin-bottom: 30px; 
    box-shadow: 0 4px 20px rgba(75, 139, 110, 0.2);
}

.header-content { 
    max-width: 1200px; 
    margin: 0 auto; 
    padding: 0 30px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
}

.header-title { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
}

.material-icon { 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-size: 24px; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.material-icon.video { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
}

.material-icon.pdf { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
}

.material-icon.simulation { 
    background: linear-gradient(135deg, #9b59b6, #8e44ad); 
}

.title-info h1 { 
    font-size: 1.8rem; 
    color: #2c3e50; 
    margin-bottom: 5px; 
}

.title-info .breadcrumb { 
    color: #7f8c8d; 
    font-size: 14px; 
}

.topic-badge { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
    padding: 8px 16px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: 600; 
    box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
}

/* Main Content */
.main-content { 
    max-width: 1200px; 
    margin: 0 auto; 
    padding: 0 30px; 
}

.content-card { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 0; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.25);
    margin-bottom: 30px; 
    overflow: hidden; 
    border: 2px solid rgba(107, 175, 146, 0.2);
}

/* Video Content */
.video-container { 
    position: relative; 
    width: 100%; 
    height: 600px; 
    background: #000; 
}

.video-iframe { 
    width: 100%; 
    height: 100%; 
    border: none; 
}

video.video-iframe { 
    object-fit: contain; 
}

/* PDF Content */
.pdf-container { 
    width: 100%; 
    height: 800px; 
    position: relative; 
    background: #f8f9fa; 
}

.pdf-iframe { 
    width: 100%; 
    height: 100%; 
    border: none; 
    background: white; 
}

.pdf-fallback { 
    padding: 40px; 
    text-align: center; 
}

.pdf-fallback .icon { 
    font-size: 4rem; 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px; 
}

.pdf-download-btn { 
    display: inline-flex; 
    align-items: center; 
    gap: 10px; 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
    padding: 15px 30px; 
    border-radius: 12px; 
    text-decoration: none; 
    font-weight: 600; 
    margin-top: 20px; 
    box-shadow: 0 4px 15px rgba(232, 197, 71, 0.3);
    transition: all 0.3s;
}

.pdf-download-btn:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(232, 197, 71, 0.4);
}

/* Simulation Content */
.simulation-container { 
    width: 100%; 
    min-height: 800px; 
}

.simulation-iframe { 
    width: 100%; 
    min-height: 800px; 
    border: none; 
}

/* Action Buttons */
.action-buttons { 
    padding: 20px 30px; 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.05), rgba(232, 197, 71, 0.05));
    display: flex; 
    gap: 15px; 
    align-items: center; 
    justify-content: space-between; 
    border-top: 2px solid rgba(107, 175, 146, 0.2);
}

.btn { 
    padding: 12px 20px; 
    border: none; 
    border-radius: 12px; 
    font-size: 14px; 
    font-weight: 600; 
    cursor: pointer; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    transition: all 0.3s; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-secondary { 
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white; 
}

.btn-secondary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
}

.btn-success { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
}

.btn-success:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(107, 175, 146, 0.4);
}

.btn-primary { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
}

.btn-primary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(232, 197, 71, 0.4);
}
.btn-primary { background: #007bff; color: white; }
.btn-primary:hover { background: #0056b3; }

/* Progress indicator for videos */
.progress-info { font-size: 12px; color: #6c757d; }

/* Responsive */
@media (max-width: 768px) {
    .header-content { flex-direction: column; gap: 15px; text-align: center; }
    .main-content { padding: 0 15px; }
    .video-container { height: 300px; }
    .pdf-container { height: 500px; }
    .action-buttons { flex-direction: column; gap: 10px; }
}
</style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-title">
                <div class="material-icon <?= $material['type'] ?>">
                    <?php if ($material['type'] == 'video'): ?>
                        <i class="fas fa-play"></i>
                    <?php elseif ($material['type'] == 'pdf'): ?>
                        <i class="fas fa-file-pdf"></i>
                    <?php else: ?>
                        <i class="fas fa-code"></i>
                    <?php endif; ?>
                </div>
                <div class="title-info">
                    <h1><?= htmlspecialchars($material['title']) ?></h1>
                    <div class="breadcrumb">
                        <?= htmlspecialchars($material['subject_name'] ?? 'Unknown Subject') ?> > 
                        <?= htmlspecialchars($material['topic_name'] ?? 'Unknown Topic') ?>
                    </div>
                </div>
            </div>
            <div class="topic-badge">
                <?= strtoupper($material['type']) ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-card">
            <?php if ($material['type'] === 'video'): ?>
                <!-- Video Content -->
                <div class="video-container">
                    <?php 
                    // Check if video is a local file or URL
                    $videoSource = !empty($material['file_path']) ? '../' . $material['file_path'] : $material['url'];
                    $isLocalVideo = !empty($material['file_path']);
                    ?>
                    
                    <?php if ($isLocalVideo): ?>
                        <!-- Local Video Player -->
                        <video class="video-iframe" controls controlsList="nodownload">
                            <source src="<?= htmlspecialchars($videoSource) ?>" type="video/mp4">
                            <source src="<?= htmlspecialchars($videoSource) ?>" type="video/webm">
                            <source src="<?= htmlspecialchars($videoSource) ?>" type="video/ogg">
                            Your browser doesn't support the video tag.
                            <a href="<?= htmlspecialchars($videoSource) ?>" target="_blank">Download Video</a>
                        </video>
                    <?php else: ?>
                        <!-- Embedded Video (YouTube, etc) -->
                        <iframe class="video-iframe" 
                                src="<?= htmlspecialchars($videoSource) ?>" 
                                title="<?= htmlspecialchars($material['title']) ?>"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                allowfullscreen>
                        </iframe>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($material['type'] === 'pdf'): ?>
                <!-- PDF Content -->
                <div class="pdf-container">
                    <?php 
                    $pdfPath = "../" . $material['url'];
                    if (file_exists($pdfPath) || !str_starts_with($material['url'], 'http')): ?>
                        <iframe class="pdf-iframe" 
                                src="../<?= htmlspecialchars($material['url']) ?>#toolbar=1&navpanes=1&scrollbar=1&view=FitH" 
                                title="<?= htmlspecialchars($material['title']) ?>">
                            <p>Your browser doesn't support PDF viewing. 
                               <a href="../<?= htmlspecialchars($material['url']) ?>" download>Download the PDF</a> instead.
                            </p>
                        </iframe>
                    <?php else: ?>
                        <div class="pdf-fallback">
                            <div class="icon"><i class="fas fa-file-pdf"></i></div>
                            <h3>PDF Document</h3>
                            <p>Unable to display PDF inline. You can download it to view.</p>
                            <a href="<?= htmlspecialchars($material['url']) ?>" class="pdf-download-btn" download>
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($material['type'] === 'simulation'): ?>
                <!-- Simulation Content -->
                <div class="simulation-container">
                    <iframe class="simulation-iframe" 
                            src="../<?= htmlspecialchars($material['url']) ?>" 
                            title="<?= htmlspecialchars($material['title']) ?>"
                            frameborder="0"
                            allowfullscreen>
                        <p>Your browser doesn't support iframe viewing. 
                           <a href="../<?= htmlspecialchars($material['url']) ?>" target="_blank">Open simulation in new tab</a>.
                        </p>
                    </iframe>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <div>
                    <?php if ($_SESSION['role'] === 'student'): ?>
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </a>
                    <?php else: ?>
                        <a href="manage_materials.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Materials
                        </a>
                    <?php endif; ?>
                </div>
                
                <div>
                    <?php if ($material['type'] === 'pdf'): ?>
                        <a href="<?= htmlspecialchars($material['url']) ?>" class="btn btn-primary" download>
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] === 'student' && $material['topic_name']): ?>
                        <a href="../Activity/activity.php?topic_id=<?= $material['topic_id'] ?>" class="btn btn-success">
                            <i class="fas fa-code"></i> Try Activities for This Topic
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>