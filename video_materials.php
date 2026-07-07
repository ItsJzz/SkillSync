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

$student_id = $_SESSION['user_id'];

// Get user info
$userQuery = "SELECT username, email FROM login_credentials WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Get filter parameters
$type_filter = $_GET['type'] ?? '';
$topic_filter = $_GET['topic'] ?? '';
$search = $_GET['search'] ?? '';

// Build the WHERE clause for filtering
$whereConditions = [];
$params = [];
$types = "";

if ($type_filter) {
    $whereConditions[] = "lm.type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($topic_filter) {
    $whereConditions[] = "lm.topic_id = ?";
    $params[] = intval($topic_filter);
    $types .= "i";
}

if ($search) {
    $whereConditions[] = "(lm.title LIKE ? OR lm.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get all materials with filtering
$materialsQuery = "
    SELECT lm.*, t.name as topic_name, s.name as subject_name 
    FROM learning_materials lm 
    LEFT JOIN topics t ON lm.topic_id = t.id 
    LEFT JOIN subjects s ON t.subject_id = s.id 
    $whereClause
    ORDER BY lm.created_at DESC
";

$stmt = $conn->prepare($materialsQuery);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all topics for filter dropdown
$topicsQuery = "SELECT t.*, s.name as subject_name FROM topics t LEFT JOIN subjects s ON t.subject_id = s.id ORDER BY s.name, t.name";
$topics = $conn->query($topicsQuery)->fetch_all(MYSQLI_ASSOC);

// Get statistics
$statsQuery = "
    SELECT 
        type,
        COUNT(*) as count
    FROM learning_materials 
    GROUP BY type
";
$stats = $conn->query($statsQuery)->fetch_all(MYSQLI_ASSOC);
$materialStats = [];
foreach ($stats as $stat) {
    $materialStats[$stat['type']] = $stat['count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Learning Materials - SkillSync</title>
<link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; display: flex; background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%); min-height: 100vh; }

/* Sidebar Styles */
.sidebar { width: 240px; background: #FFFFFF; border-right: 2px solid #4B8B6E; height: 100vh; padding: 20px 0; position: fixed; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 2px 0 10px rgba(75, 139, 110, 0.1); }
.sidebar-content a { display: flex; align-items: center; gap: 10px; color: #4B8B6E; padding: 12px 20px; text-decoration: none; font-weight: 500; transition: all 0.3s; }
.sidebar-content a:hover, .sidebar-content a.active { background: linear-gradient(135deg, #4B8B6E, #6BAF92); color: white; border-radius: 0 25px 25px 0; margin-right: 10px; }
.sidebar .logo { text-align: center; margin-bottom: 20px; }
.sidebar .logo img { width: 50px; height: 50px; border-radius: 50%; }
.sidebar .logo h2 { font-size: 18px; color: #4B8B6E; margin-top: 10px; font-weight: 700; }
.student-info { text-align: center; padding: 20px; font-size: 14px; border-top: 2px solid #6BAF92; }
.student-info img { width: 40px; height: 40px; border-radius: 50%; margin-bottom: 5px; }

/* Main Content */
.main-content { margin-left: 240px; padding: 30px; width: calc(100% - 240px); }

/* Page Header */
.page-header { margin-bottom: 40px; }
.page-title { font-size: 2.5rem; color: #2D5A47; margin-bottom: 10px; font-weight: 700; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
.page-subtitle { color: #3D6B54; font-size: 1.1rem; font-weight: 500; }

/* Enhanced KPI Cards */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
.stat-card { background: #FFFFFF; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); transition: all 0.3s ease; position: relative; overflow: hidden; border: 2px solid rgba(107, 175, 146, 0.2); }
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
.stat-card.video::before { background: linear-gradient(90deg, #4B8B6E, #6BAF92); }
.stat-card.pdf::before { background: linear-gradient(90deg, #E8C547, #F4D77C); }
.stat-card.simulation::before { background: linear-gradient(90deg, #6BAF92, #4B8B6E); }
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(75, 139, 110, 0.25); border-color: #4B8B6E; }
.stat-icon { font-size: 2.5rem; margin-bottom: 15px; }
.stat-icon.video { color: #4B8B6E; }
.stat-icon.pdf { color: #E8C547; }
.stat-icon.simulation { color: #6BAF92; }
.stat-number { font-size: 2.5rem; font-weight: bold; color: #2D5A47; margin-bottom: 5px; }
.stat-label { color: #4B8B6E; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

/* Filter Section */
.filter-section { background: #FFFFFF; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); margin-bottom: 30px; border: 2px solid rgba(107, 175, 146, 0.2); }
.filter-grid { display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 20px; align-items: end; }
.filter-group { display: flex; flex-direction: column; gap: 8px; }
.filter-group label { font-weight: 600; color: #2D5A47; font-size: 14px; }
.filter-input { padding: 12px; border: 2px solid rgba(107, 175, 146, 0.3); border-radius: 8px; font-size: 14px; transition: all 0.3s; font-family: 'Poppins', sans-serif; }
.filter-input:focus { outline: none; border-color: #4B8B6E; box-shadow: 0 0 0 3px rgba(75, 139, 110, 0.1); }
.filter-btn { background: linear-gradient(135deg, #4B8B6E, #6BAF92); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s; font-family: 'Poppins', sans-serif; }
.filter-btn:hover { background: linear-gradient(135deg, #3a7058, #4B8B6E); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3); }

/* Materials Grid */
.materials-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px; }
.material-card { background: #FFFFFF; border-radius: 16px; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 4px 20px rgba(75, 139, 110, 0.1); border: 2px solid rgba(107, 175, 146, 0.2); }
.material-card:hover { transform: translateY(-8px); box-shadow: 0 12px 35px rgba(75, 139, 110, 0.25); border-color: #4B8B6E; }

.material-header { padding: 20px; display: flex; align-items: center; gap: 15px; border-bottom: 1px solid rgba(107, 175, 146, 0.2); }
.material-type-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
.material-type-icon.video { background: linear-gradient(135deg, #4B8B6E, #6BAF92); }
.material-type-icon.pdf { background: linear-gradient(135deg, #E8C547, #F4D77C); }
.material-type-icon.simulation { background: linear-gradient(135deg, #6BAF92, #4B8B6E); }
.material-info h3 { color: #2D5A47; font-size: 1.1rem; margin-bottom: 5px; font-weight: 700; }
.material-meta { color: #4B8B6E; font-size: 13px; font-weight: 500; }

.material-body { padding: 20px; }
.material-description { color: #3D6B54; line-height: 1.6; margin-bottom: 20px; font-weight: 400; }
.material-tags { display: flex; gap: 8px; margin-bottom: 20px; }
.tag { background: rgba(107, 175, 146, 0.15); color: #2D5A47; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; border: 1px solid rgba(107, 175, 146, 0.3); }

.material-actions { display: flex; gap: 10px; }
.btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-family: 'Poppins', sans-serif; }
.btn-primary { background: linear-gradient(135deg, #4B8B6E, #6BAF92); color: white; }
.btn-primary:hover { background: linear-gradient(135deg, #3a7058, #4B8B6E); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3); }
.btn-secondary { background: linear-gradient(135deg, #E8C547, #F4D77C); color: #4B8B6E; }
.btn-secondary:hover { background: linear-gradient(135deg, #d4b03d, #E8C547); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(232, 197, 71, 0.3); }

/* No Results */
.no-results { text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: 20px; box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); border: 2px solid rgba(107, 175, 146, 0.2); }
.no-results i { font-size: 4rem; color: rgba(107, 175, 146, 0.3); margin-bottom: 20px; }
.no-results h3 { color: #2D5A47; margin-bottom: 10px; font-weight: 700; font-size: 1.5rem; }
.no-results p { color: #3D6B54; font-weight: 500; }

/* Category Card Hover Effects */
.category-card { transition: transform 0.3s, box-shadow 0.3s; }
.category-card:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(75, 139, 110, 0.2) !important; }
.resource-link { transition: transform 0.2s, box-shadow 0.2s, background 0.2s; }
.resource-link:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3); }

/* Subject Dropdown Styles */
.subject-dropdown { margin-bottom: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(75, 139, 110, 0.15); }
.subject-header-btn { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
    color: white; 
    padding: 12px 18px; 
    width: 100%; 
    border: none; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 0.95rem; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
}
.subject-header-btn:hover { 
    background: linear-gradient(135deg, #3a7058, #5a9f82); 
    box-shadow: 0 4px 12px rgba(75, 139, 110, 0.3);
}
.subject-header-btn i.chevron { 
    transition: transform 0.3s ease; 
}
.subject-header-btn.active i.chevron { 
    transform: rotate(180deg); 
}
.subject-content { 
    max-height: 0; 
    overflow: hidden; 
    transition: max-height 0.4s ease-out;
    background: rgba(107, 175, 146, 0.05); 
}
.subject-content.expanded { 
    max-height: 1000px; 
    transition: max-height 0.5s ease-in; 
}
.subject-videos-grid { 
    padding: 15px; 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 12px; 
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
    .materials-grid { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
}

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main-content { margin-left: 0; width: 100%; }
    .filter-grid { grid-template-columns: 1fr; }
    .materials-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="logo">
                <img src="LOGO.png" alt="Logo">
                <h2>SkillSync</h2>
            </div>
            <div class="sidebar-content">
                <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="progress_history.php"><i class="fas fa-history"></i> Progress History</a>
                <a href="video_materials.php" class="active"><i class="fas fa-book-open"></i> Learning Materials</a>
                <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
                <a href="recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
                <a href="coding_practice.php"><i class="fas fa-code"></i> Coding Practice</a>
                <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="student-info">
            <img src="student.jpg" alt="Student">
            <div><strong><?= htmlspecialchars($user['username'] ?? 'Student') ?></strong></div>
            <div><?= htmlspecialchars($user['email'] ?? 'student@email.com') ?></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 10px;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #4B8B6E, #6BAF92); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);">
                    <i class="fas fa-lightbulb" style="font-size: 28px; color: white;"></i>
                </div>
                <h1 class="page-title" style="margin-bottom: 0;">Learning Materials</h1>
            </div>
            <p class="page-subtitle">Explore curated resources to strengthen your understanding and skills</p>
        </div>

        <!-- Introduction Text -->
        <div style="text-align: center; color: #3D6B54; margin-bottom: 40px; font-size: 1.05rem; font-weight: 500;">
            Based on your learning needs, here's your customized learning strategy:
        </div>

        <!-- Categorized Learning Materials -->
        <?php
        // Categorize materials by type
        $pdfMaterials = array_filter($materials, fn($m) => $m['type'] === 'pdf');
        $videoMaterials = array_filter($materials, fn($m) => $m['type'] === 'video');
        $simulationMaterials = array_filter($materials, fn($m) => $m['type'] === 'simulation');
        ?>

        <?php if (empty($materials)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No Materials Found</h3>
                <p>Start your learning journey by exploring our comprehensive materials.</p>
            </div>
        <?php else: ?>
            
            <!-- Category 1: Strengthen Conceptual Understanding (PDF Materials) -->
            <?php if (!empty($pdfMaterials)): ?>
            <div class="category-card" style="background: #FFFFFF; border-radius: 16px; padding: 35px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(75, 139, 110, 0.15); border: 2px solid rgba(107, 175, 146, 0.2);">
                <div style="display: flex; align-items: flex-start; gap: 20px; margin-bottom: 25px;">
                    <div style="width: 65px; height: 65px; background: linear-gradient(135deg, #6BAF92, #4B8B6E); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 5px 15px rgba(107, 175, 146, 0.3);">
                        <i class="fas fa-book" style="font-size: 28px; color: white;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: #2D5A47; font-size: 1.6rem; margin: 0 0 8px 0; font-weight: 700;">Strengthen Conceptual Understanding</h2>
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 5px;">
                            <span style="background: linear-gradient(135deg, rgba(107, 175, 146, 0.25), rgba(75, 139, 110, 0.35)); color: #2D5A47; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(75, 139, 110, 0.3);">
                                Current: <?= count($pdfMaterials) ?> Resources
                            </span>
                            <span style="color: #4B8B6E; font-size: 0.9rem; font-weight: 600;">Reading & Documentation</span>
                        </div>
                    </div>
                </div>
                
                <p style="color: #3D6B54; line-height: 1.7; margin-bottom: 25px; font-weight: 500;">
                    Your reading materials focus on building theoretical knowledge through comprehensive documentation and detailed explanations.
                </p>
                
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-list-ul" style="color: #4B8B6E;"></i>
                        <strong style="color: #2D5A47; font-weight: 700;">Action Plan:</strong>
                    </div>
                    <ul style="margin-left: 25px; color: #3D6B54; line-height: 1.8; font-weight: 500;">
                        <li>Focus on <strong style="color: #2D5A47;">modular learning</strong> - break down concepts into smaller chunks</li>
                        <li>Review learning materials and documentation for each topic</li>
                        <li>Create concept maps to visualize relationships between OOP principles</li>
                        <li>Take notes and summarize key concepts in your own words</li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-link" style="color: #4B8B6E;"></i>
                        <strong style="color: #2D5A47; font-weight: 700;">Recommended Resources:</strong>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <?php foreach (array_slice($pdfMaterials, 0, 6) as $material): ?>
                            <a href="view_material.php?id=<?= $material['id'] ?>" class="resource-link" style="background: linear-gradient(135deg, #6BAF92, #4B8B6E); color: white; padding: 12px 18px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 700; box-shadow: 0 3px 12px rgba(107, 175, 146, 0.3);">
                                <i class="fas fa-book-open"></i>
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($material['title']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Category 2: Improve Problem-Solving Skills (Video Materials) -->
            <?php if (!empty($videoMaterials)): ?>
            <div class="category-card" style="background: #FFFFFF; border-radius: 16px; padding: 35px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(75, 139, 110, 0.15); border: 2px solid rgba(107, 175, 146, 0.2);">
                <div style="display: flex; align-items: flex-start; gap: 20px; margin-bottom: 25px;">
                    <div style="width: 65px; height: 65px; background: linear-gradient(135deg, #6BAF92, #4B8B6E); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 5px 15px rgba(107, 175, 146, 0.3);">
                        <i class="fas fa-desktop" style="font-size: 28px; color: white;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: #2D5A47; font-size: 1.6rem; margin: 0 0 8px 0; font-weight: 700;">Improve Problem-Solving Skills</h2>
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 5px;">
                            <span style="background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(75, 139, 110, 0.25)); color: #2D5A47; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(107, 175, 146, 0.3);">
                                Current: <?= count($videoMaterials) ?> Resources
                            </span>
                            <span style="color: #4B8B6E; font-size: 0.9rem; font-weight: 600;">Video Tutorials</span>
                        </div>
                    </div>
                </div>
                
                <p style="color: #3D6B54; line-height: 1.7; margin-bottom: 25px; font-weight: 500;">
                    You need more practice applying concepts to real-world scenarios through guided video demonstrations.
                </p>
                
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-list-ul" style="color: #4B8B6E;"></i>
                        <strong style="color: #2D5A47; font-weight: 700;">Action Plan:</strong>
                    </div>
                    <ul style="margin-left: 25px; color: #3D6B54; line-height: 1.8; font-weight: 500;">
                        <li>Watch <strong style="color: #2D5A47;">video tutorials</strong> showing step-by-step problem solving</li>
                        <li>Visit the <strong style="color: #2D5A47;">simulation playground</strong> to practice in a safe environment</li>
                        <li>Analyze sample code and trace execution flow</li>
                        <li>Work through guided examples before attempting independent problems</li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-link" style="color: #4B8B6E;"></i>
                        <strong style="color: #2D5A47; font-weight: 700;">Recommended Resources:</strong>
                    </div>
                    
                    <?php 
                    // Group videos by subject
                    $videosBySubject = [];
                    foreach ($videoMaterials as $video) {
                        $subjectName = $video['subject_name'] ?? 'Other';
                        if (!isset($videosBySubject[$subjectName])) {
                            $videosBySubject[$subjectName] = [];
                        }
                        $videosBySubject[$subjectName][] = $video;
                    }
                    
                    $subjectIndex = 0;
                    ?>
                    
                    <?php foreach ($videosBySubject as $subjectName => $videos): ?>
                        <div class="subject-dropdown">
                            <button class="subject-header-btn <?= $subjectIndex === 0 ? 'active' : '' ?>" onclick="toggleSubject(<?= $subjectIndex ?>)">
                                <span>
                                    <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($subjectName) ?>
                                    <span style="opacity: 0.8; font-size: 0.85rem; margin-left: 10px;">(<?= count($videos) ?> video<?= count($videos) > 1 ? 's' : '' ?>)</span>
                                </span>
                                <i class="fas fa-chevron-down chevron"></i>
                            </button>
                            <div class="subject-content <?= $subjectIndex === 0 ? 'expanded' : '' ?>" id="subject-content-<?= $subjectIndex ?>">
                                <div class="subject-videos-grid">
                                    <?php foreach ($videos as $material): ?>
                                        <a href="view_material.php?id=<?= $material['id'] ?>" class="resource-link" style="background: linear-gradient(135deg, #6BAF92, #4B8B6E); color: white; padding: 12px 18px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 700; box-shadow: 0 3px 12px rgba(107, 175, 146, 0.3);">
                                            <i class="fas fa-video"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($material['title']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php $subjectIndex++; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Category 3: Build Practical Coding Skills (Simulation Materials) -->
            <?php if (!empty($simulationMaterials)): ?>
            <div class="category-card" style="background: #FFFFFF; border-radius: 16px; padding: 35px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(75, 139, 110, 0.15); border: 2px solid rgba(107, 175, 146, 0.2);">
                <div style="display: flex; align-items: flex-start; gap: 20px; margin-bottom: 25px;">
                    <div style="width: 65px; height: 65px; background: linear-gradient(135deg, #6BAF92, #4B8B6E); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 5px 15px rgba(107, 175, 146, 0.3);">
                        <i class="fas fa-keyboard" style="font-size: 28px; color: white;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: #2D5A47; font-size: 1.6rem; margin: 0 0 8px 0; font-weight: 700;">Build Practical Coding Skills</h2>
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 5px;">
                            <span style="background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(75, 139, 110, 0.25)); color: #2D5A47; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(107, 175, 146, 0.3);">
                                Current: <?= count($simulationMaterials) ?> Resources
                            </span>
                            <span style="color: #4B8B6E; font-size: 0.9rem; font-weight: 600;">Hands-on Practice</span>
                        </div>
                    </div>
                </div>
                
                <p style="color: #3D6B54; line-height: 1.7; margin-bottom: 25px; font-weight: 500;">
                    You need more hands-on coding practice to solidify your skills through interactive exercises.
                </p>
                
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-list-ul" style="color: #4B8B6E;"></i>
                        <strong style="color: #2D5A47; font-weight: 700;">Action Plan:</strong>
                    </div>
                    <ul style="margin-left: 25px; color: #3D6B54; line-height: 1.8; font-weight: 500;">
                        <li>Complete the <strong style="color: #2D5A47;">enhancement process</strong> activities for weak topics</li>
                        <li>Practice in the <strong style="color: #2D5A47;">coding practice environment</strong> daily</li>
                        <li>Start with simple exercises and gradually increase complexity</li>
                        <li>Review and refactor your code to learn best practices</li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-link" style="color: #4B8B6E;"></i>
                        <strong style="color: #2D5A47; font-weight: 700;">Recommended Resources:</strong>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <?php foreach (array_slice($simulationMaterials, 0, 6) as $material): ?>
                            <a href="view_material.php?id=<?= $material['id'] ?>" class="resource-link" style="background: linear-gradient(135deg, #6BAF92, #4B8B6E); color: white; padding: 12px 18px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 700; box-shadow: 0 3px 12px rgba(107, 175, 146, 0.3);">
                                <i class="fas fa-code"></i>
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($material['title']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>

    <script>
        function toggleSubject(index) {
            const content = document.getElementById('subject-content-' + index);
            const buttons = document.querySelectorAll('.subject-header-btn');
            const button = buttons[index];
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                button.classList.remove('active');
            } else {
                content.classList.add('expanded');
                button.classList.add('active');
            }
        }
    </script>
</body>
</html>
