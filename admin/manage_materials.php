<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once '../db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Handle success/error messages
$message = "";
$error = "";
if (isset($_GET['success'])) {
    $message = "Learning material uploaded successfully!";
} elseif (isset($_GET['deleted'])) {
    $message = "Learning material deleted successfully!";
} elseif (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

// Get all learning materials statistics
$videosQuery = "SELECT COUNT(*) as total_videos FROM learning_materials WHERE type = 'video'";
$videosResult = $conn->query($videosQuery)->fetch_assoc();

$pdfsQuery = "SELECT COUNT(*) as total_pdfs FROM learning_materials WHERE type = 'pdf'";
$pdfsResult = $conn->query($pdfsQuery)->fetch_assoc();

$simulationsQuery = "SELECT COUNT(*) as total_simulations FROM learning_materials WHERE type = 'simulation'";
$simulationsResult = $conn->query($simulationsQuery)->fetch_assoc();

$activitiesQuery = "SELECT COUNT(*) as total_activities FROM (SELECT DISTINCT topic_id FROM student_activity_scores) as topics";
$activitiesResult = $conn->query($activitiesQuery)->fetch_assoc();

// Get recent materials
$recentMaterialsQuery = "
    SELECT lm.*, t.name as topic_name, s.name as subject_name 
    FROM learning_materials lm 
    LEFT JOIN topics t ON lm.topic_id = t.id 
    LEFT JOIN subjects s ON t.subject_id = s.id 
    ORDER BY lm.created_at DESC 
    LIMIT 10
";
$recentMaterials = $conn->query($recentMaterialsQuery)->fetch_all(MYSQLI_ASSOC);

// Get materials by type
$materialsByType = [];
$typesQuery = "SELECT type, COUNT(*) as count FROM learning_materials GROUP BY type";
$typesResult = $conn->query($typesQuery);
while ($row = $typesResult->fetch_assoc()) {
    $materialsByType[$row['type']] = $row['count'];
}

// Get subjects for dropdown
$subjects = [];
$subjectsQuery = "SELECT id, name FROM subjects ORDER BY name ASC";
$subjectsResult = $conn->query($subjectsQuery);
if ($subjectsResult) {
    while ($subject = $subjectsResult->fetch_assoc()) {
        $subjects[] = $subject;
    }
}

// Debug output (remove this after testing)
// echo "<!-- DEBUG: Found " . count($subjects) . " subjects and " . count($recentMaterials) . " materials -->";

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Materials - SkillSync Admin</title>
<link rel="shortcut icon" sizes="32x32" href="../LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex; 
    background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
    min-height: 100vh; 
}

/* Admin Sidebar */
.admin-sidebar { 
    width: 260px; 
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
    color: white; 
    height: 100vh; 
    padding: 20px 0; 
    position: fixed; 
    box-shadow: 4px 0 20px rgba(75, 139, 110, 0.2);
    border-right: 2px solid rgba(107, 175, 146, 0.3);
}

.admin-sidebar .logo { 
    text-align: center; 
    margin-bottom: 30px; 
    padding: 20px; 
}

.admin-sidebar .logo img { 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    border: 3px solid #6BAF92;
    box-shadow: 0 4px 15px rgba(107, 175, 146, 0.4);
}

.admin-sidebar .logo h2 { 
    font-size: 22px; 
    background: linear-gradient(135deg, #6BAF92, #E8C547);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-top: 10px; 
    font-weight: 700;
}

.admin-sidebar .logo p { 
    font-size: 12px; 
    color: #bdc3c7; 
    margin-top: 5px; 
}

.admin-nav a { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    color: #ecf0f1; 
    padding: 15px 25px; 
    text-decoration: none; 
    font-weight: 500; 
    transition: all 0.3s; 
    border-left: 3px solid transparent; 
}

.admin-nav a:hover, .admin-nav a.active { 
    background: linear-gradient(90deg, rgba(107, 175, 146, 0.2), rgba(232, 197, 71, 0.1));
    border-left-color: #6BAF92; 
    color: #6BAF92; 
}

.admin-nav a i { 
    width: 20px; 
    font-size: 16px; 
}

.admin-info { 
    position: absolute; 
    bottom: 20px; 
    left: 0; 
    right: 0; 
    text-align: center; 
    padding: 0 20px; 
    border-top: 1px solid rgba(107, 175, 146, 0.3);
    padding-top: 20px; 
}

.admin-info .admin-name { 
    font-weight: bold; 
    background: linear-gradient(135deg, #6BAF92, #E8C547);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.admin-info .admin-role { 
    font-size: 12px; 
    color: #bdc3c7; 
}

/* Main Content */
.admin-content { 
    margin-left: 260px; 
    padding: 30px; 
    width: calc(100% - 260px); 
}

.page-header { 
    margin-bottom: 40px; 
}

.page-title { 
    font-size: 2.5rem; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px; 
    font-weight: 700; 
}

.page-subtitle { 
    color: #4B8B6E;
    font-size: 1.1rem; 
    font-weight: 500;
}

/* Stats Cards */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
    gap: 25px; 
    margin-bottom: 40px; 
}

.stat-card { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 25px; 
    border-radius: 20px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(75, 139, 110, 0.25);
}

.stat-card .icon { 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    margin-bottom: 15px; 
    font-size: 24px; 
    color: white; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.stat-card .icon.videos { 
    background: linear-gradient(135deg, #e74c3c, #c0392b); 
}

.stat-card .icon.activities { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
}

.stat-card .icon.simulations { 
    background: linear-gradient(135deg, #9b59b6, #8e44ad); 
}

.stat-card .icon.topics { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
}

.stat-card .number { 
    font-size: 2rem; 
    font-weight: bold; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 5px; 
}

.stat-card .label { 
    color: #4B8B6E;
    font-size: 14px; 
    font-weight: 600;
}

/* Content Section */
.content-section { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    margin-bottom: 30px; 
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.section-header { 
    display: flex; 
    justify-content: space-between;
    align-items: center; 
    margin-bottom: 25px; 
    padding-bottom: 15px; 
    border-bottom: 2px solid rgba(107, 175, 146, 0.2);
}

.section-title { 
    font-size: 1.5rem; 
    color: #4B8B6E;
    font-weight: 600; 
}

/* Action Buttons */
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
    margin: 5px; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-primary { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
}

.btn-primary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3);
}

.btn-success { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
}

.btn-success:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(107, 175, 146, 0.3);
}

/* Table Styles */
.data-table { 
    width: 100%; 
    border-collapse: collapse; 
}

.data-table th { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(232, 197, 71, 0.1));
    padding: 15px 12px; 
    text-align: left; 
    font-weight: 600; 
    color: #4B8B6E;
    border-bottom: 2px solid rgba(107, 175, 146, 0.3);
    font-size: 14px; 
}

.data-table td { 
    padding: 15px 12px; 
    border-bottom: 1px solid rgba(107, 175, 146, 0.1);
    vertical-align: middle; 
    color: #2c3e50;
}

.data-table tr:hover { 
    background: rgba(107, 175, 146, 0.05);
}

/* Video Thumbnail */
.video-thumb { 
    width: 60px; 
    height: 40px; 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(232, 197, 71, 0.1));
    border-radius: 8px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #6BAF92;
    border: 2px solid rgba(107, 175, 146, 0.2);
}

/* Badge Styles */
.badge { 
    padding: 6px 12px; 
    border-radius: 12px; 
    font-size: 11px; 
    font-weight: 600; 
    text-transform: uppercase; 
}

.badge.popular { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
}

.badge.recent { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white;
}

/* Action Buttons Small */
.btn-sm { 
    padding: 6px 12px; 
    font-size: 12px; 
}

/* Messages */
.message { 
    padding: 15px 20px; 
    margin-bottom: 25px; 
    border-radius: 12px; 
    font-weight: 500; 
}

.message.success { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(75, 139, 110, 0.15));
    color: #4B8B6E;
    border: 2px solid rgba(107, 175, 146, 0.3);
}

.message.error { 
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(192, 57, 43, 0.15));
    color: #c0392b;
    border: 2px solid rgba(231, 76, 60, 0.3);
}

/* Upload Form */
.upload-form { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
.form-group { margin-bottom: 15px; }
.form-label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
.form-input { width: 100%; padding: 10px 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; }
.form-input:focus { outline: none; border-color: #3498db; }

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-content { margin-left: 0; width: 100%; }
    .stats-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<!-- Admin Sidebar -->
<div class="admin-sidebar">
    <div class="logo">
        <img src="../LOGO.png" alt="SkillSync Logo">
        <h2>SkillSync</h2>
        <p>Admin Panel</p>
    </div>
    
    <nav class="admin-nav">
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a>
        <a href="manage_materials.php" class="active"><i class="fas fa-book"></i> Learning Materials</a>
        <a href="add_activity.php"><i class="fas fa-plus-circle"></i> Add Activity</a>
        <a href="add_questions.php"><i class="fas fa-question-circle"></i> Add Questions</a>
        <a href="manage_topics.php"><i class="fas fa-list"></i> Topics & Subjects</a>
        <a href="view_progress.php"><i class="fas fa-chart-line"></i> Student Progress</a>
        <a href="manage_recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
        <a href="view_feedback.php"><i class="fas fa-comments"></i> Feedback</a>
        <a href="system_settings.php"><i class="fas fa-cog"></i> System Settings</a>
        <a href="../login.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    
    <div class="admin-info">
        <div class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="admin-role">System Administrator</div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Learning Materials 📚</h1>
        <p class="page-subtitle">Manage videos, activities, and learning resources</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon videos"><i class="fas fa-play"></i></div>
            <div class="number"><?= $videosResult['total_videos'] ?></div>
            <div class="label">Video Materials</div>
        </div>
        <div class="stat-card">
            <div class="icon activities"><i class="fas fa-file-pdf"></i></div>
            <div class="number"><?= $pdfsResult['total_pdfs'] ?></div>
            <div class="label">PDF Modules</div>
        </div>
        <div class="stat-card">
            <div class="icon simulations"><i class="fas fa-code"></i></div>
            <div class="number"><?= $simulationsResult['total_simulations'] ?></div>
            <div class="label">Simulations</div>
        </div>
        <div class="stat-card">
            <div class="icon topics"><i class="fas fa-tasks"></i></div>
            <div class="number"><?= $activitiesResult['total_activities'] ?></div>
            <div class="label">Activity Topics</div>
        </div>
    </div>

    <!-- Add New Material -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-plus"></i> Add New Learning Material</h3>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <form action="../upload_material.php" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label">Material Title</label>
                        <input type="text" class="form-input" name="title" required placeholder="Enter material title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Type</label>
                        <select class="form-input" name="type" required onchange="toggleUploadFields(this.value)">
                            <option value="">Select Type</option>
                            <option value="video">Video</option>
                            <option value="pdf">PDF Module</option>
                            <option value="simulation">Simulation</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select class="form-input" name="subject_id" required onchange="loadTopicsForUpload(this.value)">
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Topic</label>
                        <select class="form-input" name="topic_id" required id="topicSelect">
                            <option value="">Select Subject First</option>
                        </select>
                    </div>
                </div>
                
                <!-- Video Upload Fields -->
                <div id="videoFields" style="display: none; margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label">Video File (MP4, AVI, MOV)</label>
                        <input type="file" class="form-input" name="video_file" accept=".mp4,.avi,.mov,.mkv,.webm">
                        <small style="color: #6c757d; font-size: 0.85rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Max file size: 100MB. Supported formats: MP4, AVI, MOV, MKV, WebM
                        </small>
                    </div>
                </div>
                
                <!-- PDF Upload Fields -->
                <div id="pdfFields" style="display: none; margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label">PDF File</label>
                        <input type="file" class="form-input" name="pdf_file" accept=".pdf">
                    </div>
                </div>
                
                <!-- Simulation Upload Fields -->
                <div id="simulationFields" style="display: none; margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label">Simulation File Path</label>
                        <input type="text" class="form-input" name="simulation_path" placeholder="e.g., simulation/classes-objects.php">
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload Material
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Materials -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-clock"></i> Recent Learning Materials</h3>
            <a href="../video_materials.php" class="btn btn-primary">View All Materials</a>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title & Topic</th>
                    <th>Subject</th>
                    <th>Upload Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentMaterials)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 40px;">
                        <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                        <strong>No learning materials uploaded yet</strong>
                        <br><small>Upload your first material using the form above</small>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentMaterials as $material): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php if ($material['type'] == 'video'): ?>
                                <div style="background: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px;">
                                    <i class="fas fa-play"></i> VIDEO
                                </div>
                            <?php elseif ($material['type'] == 'pdf'): ?>
                                <div style="background: #f39c12; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </div>
                            <?php elseif ($material['type'] == 'simulation'): ?>
                                <div style="background: #9b59b6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px;">
                                    <i class="fas fa-code"></i> SIM
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;"><?= htmlspecialchars($material['title']) ?></div>
                        <div style="font-size: 12px; color: #7f8c8d;"><?= htmlspecialchars($material['topic_name'] ?? 'Unknown Topic') ?></div>
                    </td>
                    <td>
                        <div style="font-size: 14px; color: #2c3e50;"><?= htmlspecialchars($material['subject_name'] ?? 'Unknown Subject') ?></div>
                    </td>
                    <td>
                        <div><?= date('M j, Y', strtotime($material['created_at'])) ?></div>
                        <div style="font-size: 11px; color: #7f8c8d;"><?= date('g:i A', strtotime($material['created_at'])) ?></div>
                    </td>
                    <td>
                        <a href="view_material.php?id=<?= $material['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <button class="btn btn-danger btn-sm" onclick="deleteMaterial(<?= $material['id'] ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Materials by Type -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-chart-pie"></i> Materials Overview</h3>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach (['video' => 'Videos', 'pdf' => 'PDF Modules', 'simulation' => 'Simulations'] as $type => $label): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border: 2px solid #dee2e6;">
                <div style="font-size: 2rem; color: #6c757d; margin-bottom: 10px;">
                    <?php if ($type == 'video'): ?>
                        <i class="fas fa-play-circle"></i>
                    <?php elseif ($type == 'pdf'): ?>
                        <i class="fas fa-file-pdf"></i>
                    <?php else: ?>
                        <i class="fas fa-laptop-code"></i>
                    <?php endif; ?>
                </div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50; margin-bottom: 5px;">
                    <?= $materialsByType[$type] ?? 0 ?>
                </div>
                <div style="color: #6c757d; font-size: 14px;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function loadTopicsForUpload(subjectId) {
    const topicSelect = document.getElementById('topicSelect');
    topicSelect.innerHTML = "<option value=''>Loading...</option>";
    
    if (subjectId) {
        fetch(`../load_topics.php?subject_id=${subjectId}`)
            .then(response => response.json())
            .then(topics => {
                topicSelect.innerHTML = "<option value=''>-- Select Topic --</option>";
                topics.forEach(topic => {
                    topicSelect.innerHTML += `<option value="${topic.id}">${topic.name}</option>`;
                });
            })
            .catch(error => {
                topicSelect.innerHTML = "<option value=''>Error loading topics</option>";
                console.error('Error:', error);
            });
    } else {
        topicSelect.innerHTML = "<option value=''>-- Select Subject First --</option>";
    }
}

function toggleUploadFields(type) {
    // Hide all fields first
    document.getElementById('videoFields').style.display = 'none';
    document.getElementById('pdfFields').style.display = 'none';
    document.getElementById('simulationFields').style.display = 'none';
    
    // Show relevant fields
    if (type === 'video') {
        document.getElementById('videoFields').style.display = 'block';
    } else if (type === 'pdf') {
        document.getElementById('pdfFields').style.display = 'block';
    } else if (type === 'simulation') {
        document.getElementById('simulationFields').style.display = 'block';
    }
}

function deleteMaterial(materialId) {
    if (confirm('Are you sure you want to delete this learning material? This action cannot be undone.')) {
        // Create a form to submit the deletion request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_material.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'material_id';
        input.value = materialId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>