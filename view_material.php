<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$material_id = intval($_GET['id'] ?? 0);

if ($material_id <= 0) {
    header("Location: student_dashboard.php");
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
    // Log video watch if it's a video (avoid duplicates with INSERT IGNORE)
    if ($material['type'] === 'video') {
        $logQuery = "INSERT IGNORE INTO student_video_progress (student_id, material_id) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("ii", $_SESSION['user_id'], $material_id);
        $logStmt->execute();
    }
    
    // Determine video source for local vs YouTube videos
    $isLocalVideo = false;
    $videoSource = '';
    if ($material['type'] === 'video') {
        $isLocalVideo = !empty($material['file_path']);
        if ($isLocalVideo) {
            // For local videos, use file_path
            $videoSource = $material['file_path'];
        } else {
            // For YouTube/external videos, use url
            $videoSource = $material['url'];
        }
    }
} else {
    header("Location: student_dashboard.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($material['title']) ?> - SkillSync</title>
<link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #6BAF92 0%, #4B8B6E 100%); min-height: 100vh; }

/* Header */
.header { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 20px 0; margin-bottom: 30px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
.header-content { max-width: 1200px; margin: 0 auto; padding: 0 30px; display: flex; align-items: center; justify-content: space-between; }
.header-title { display: flex; align-items: center; gap: 15px; }
.material-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
.material-icon.video { background: linear-gradient(135deg, #4B8B6E, #6BAF92); }
.material-icon.pdf { background: linear-gradient(135deg, #6BAF92, #4B8B6E); }
.material-icon.simulation { background: linear-gradient(135deg, #4B8B6E, #6BAF92); }
.title-info h1 { font-size: 1.8rem; color: #2c3e50; margin-bottom: 5px; }
.title-info .breadcrumb { color: #7f8c8d; font-size: 14px; }
.topic-badge { background: #4B8B6E; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* Main Content */
.main-content { max-width: 1200px; margin: 0 auto; padding: 0 30px; }
.content-card { background: white; border-radius: 15px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; overflow: hidden; }

/* Video Content */
.video-container { position: relative; width: 100%; height: 600px; background: #000; }
.video-iframe { width: 100%; height: 100%; border: none; }
video { object-fit: contain; }

/* PDF Content */
.pdf-container { width: 100%; height: 800px; position: relative; background: #f8f9fa; }
.pdf-iframe { width: 100%; height: 100%; border: none; background: white; }
.pdf-fallback { padding: 40px; text-align: center; }
.pdf-fallback .icon { font-size: 4rem; color: #6BAF92; margin-bottom: 20px; }
.pdf-download-btn { display: inline-flex; align-items: center; gap: 10px; background: #4B8B6E; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 20px; }
.pdf-download-btn:hover { background: #6BAF92; }

/* Simulation Content */
.simulation-container { width: 100%; min-height: 800px; }
.simulation-iframe { width: 100%; min-height: 800px; border: none; }

/* Action Buttons */
.action-buttons { padding: 20px 30px; background: #f8f9fa; display: flex; gap: 15px; align-items: center; justify-content: space-between; }
.btn { padding: 12px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #5a6268; }
.btn-success { background: #4B8B6E; color: white; }
.btn-success:hover { background: #6BAF92; }
.btn-primary { background: #6BAF92; color: white; }
.btn-primary:hover { background: #4B8B6E; }

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
                    <?php if ($isLocalVideo): ?>
                        <!-- Local Video Player -->
                        <?php if (file_exists($videoSource)): ?>
                            <video controls style="width: 100%; height: 100%; object-fit: contain;" controlsList="nodownload" preload="metadata">
                                <source src="<?= htmlspecialchars($videoSource) ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #4B8B6E; flex-direction: column; gap: 20px;">
                                <i class="fas fa-video-slash" style="font-size: 4rem;"></i>
                                <h2>Video File Not Found</h2>
                                <p>The video file is not available: <?= htmlspecialchars($videoSource) ?></p>
                                <p style="font-size: 0.9rem; color: #7f8c8d;">Please contact your administrator.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- YouTube/External Video -->
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
                    $pdfPath = $material['url'];
                    // Check if file exists or if it's a relative path
                    if (file_exists($pdfPath) || !str_starts_with($pdfPath, 'http')): ?>
                        <iframe class="pdf-iframe" 
                                src="<?= htmlspecialchars($material['url']) ?>#toolbar=1&navpanes=1&scrollbar=1&view=FitH" 
                                title="<?= htmlspecialchars($material['title']) ?>">
                            <p>Your browser doesn't support PDF viewing. 
                               <a href="<?= htmlspecialchars($material['url']) ?>" download>Download the PDF</a> instead.
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
                            src="<?= htmlspecialchars($material['url']) ?>" 
                            title="<?= htmlspecialchars($material['title']) ?>"
                            frameborder="0"
                            allowfullscreen>
                        <p>Your browser doesn't support iframe viewing. 
                           <a href="<?= htmlspecialchars($material['url']) ?>" target="_blank">Open simulation in new tab</a>.
                        </p>
                    </iframe>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <div>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
                
                <div>
                    <?php if ($material['type'] === 'pdf'): ?>
                        <a href="<?= htmlspecialchars($material['url']) ?>" class="btn btn-primary" download>
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($material['topic_name']): ?>
                        <a href="Activity/activity.php?topic_id=<?= $material['topic_id'] ?>" class="btn btn-success">
                            <i class="fas fa-code"></i> Try Activities for This Topic
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>