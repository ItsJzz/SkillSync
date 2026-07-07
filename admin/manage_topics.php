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

// Handle form submissions
$message = '';
$messageType = '';

// Add new subject
if (isset($_POST['add_subject'])) {
    $code = $_POST['code'];
    $name = $_POST['name'];
    
    $stmt = $conn->prepare("INSERT INTO subjects (code, name) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $name);
    
    if ($stmt->execute()) {
        $message = "Subject added successfully!";
        $messageType = "success";
    } else {
        $message = "Error adding subject: " . $conn->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Add new topic
if (isset($_POST['add_topic'])) {
    $subject_id = $_POST['subject_id'];
    $topic_name = $_POST['topic_name'];
    $description = $_POST['description'];
    
    $stmt = $conn->prepare("INSERT INTO topics (subject_id, name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $subject_id, $topic_name, $description);
    
    if ($stmt->execute()) {
        $message = "Topic added successfully!";
        $messageType = "success";
    } else {
        $message = "Error adding topic: " . $conn->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Delete subject
if (isset($_POST['delete_subject'])) {
    $subject_id = $_POST['subject_id'];
    
    // First check if there are topics under this subject
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM topics WHERE subject_id = ?");
    $checkStmt->bind_param("i", $subject_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $message = "Cannot delete subject with existing topics. Please delete all topics first.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subject_id);
        
        if ($stmt->execute()) {
            $message = "Subject deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting subject: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Delete topic
if (isset($_POST['delete_topic'])) {
    $topic_id = $_POST['topic_id'];
    
    // Check if there are questions under this topic
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM questions WHERE topic_id = ?");
    $checkStmt->bind_param("i", $topic_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $message = "Cannot delete topic with existing questions. Please delete all questions first.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM topics WHERE id = ?");
        $stmt->bind_param("i", $topic_id);
        
        if ($stmt->execute()) {
            $message = "Topic deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting topic: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Filtering and search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$subject_filter = isset($_GET['subject_filter']) ? $_GET['subject_filter'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get all subjects with topic counts
$subjectsQuery = "
    SELECT s.*, COUNT(t.id) as topic_count 
    FROM subjects s 
    LEFT JOIN topics t ON s.id = t.subject_id 
    GROUP BY s.id 
    ORDER BY s.code
";
$subjects = $conn->query($subjectsQuery)->fetch_all(MYSQLI_ASSOC);

// Build topics query with filters
$topicsQuery = "
    SELECT t.*, s.name as subject_name, s.code as subject_code, COUNT(q.id) as question_count 
    FROM topics t 
    JOIN subjects s ON t.subject_id = s.id 
    LEFT JOIN questions q ON t.id = q.topic_id 
    WHERE 1=1
";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $topicsQuery .= " AND (t.name LIKE ? OR t.description LIKE ? OR s.name LIKE ? OR s.code LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

// Add subject filter
if (!empty($subject_filter)) {
    $topicsQuery .= " AND s.id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

$topicsQuery .= " GROUP BY t.id ORDER BY s.code, t.name";

// Get total count for pagination
$countQuery = str_replace("SELECT t.*, s.name as subject_name, s.code as subject_code, COUNT(q.id) as question_count", "SELECT COUNT(DISTINCT t.id) as total", $topicsQuery);
$countQuery = str_replace("GROUP BY t.id ORDER BY s.code, t.name", "", $countQuery);

if (!empty($params)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total_topics = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $total_topics = $conn->query($countQuery)->fetch_assoc()['total'];
}

$total_pages = ceil($total_topics / $per_page);

// Add pagination to main query
$topicsQuery .= " LIMIT ? OFFSET ?";
$params = array_merge($params, [$per_page, $offset]);
$types .= "ii";

// Execute topics query
if (!empty($params)) {
    $topicsStmt = $conn->prepare($topicsQuery);
    $topicsStmt->bind_param($types, ...$params);
    $topicsStmt->execute();
    $topics = $topicsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $topicsStmt->close();
} else {
    $topics = $conn->query($topicsQuery)->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Topics & Subjects - SkillSync Admin</title>
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

.admin-nav { 
    padding: 0 20px; 
}

.admin-nav a { 
    display: block; 
    color: #ecf0f1; 
    text-decoration: none; 
    padding: 12px 15px; 
    margin: 5px 0; 
    border-radius: 8px; 
    transition: 0.3s; 
    border-left: 3px solid transparent;
}

.admin-nav a:hover { 
    background: linear-gradient(90deg, rgba(107, 175, 146, 0.2), rgba(232, 197, 71, 0.1));
    color: #6BAF92; 
}

.admin-nav a.active { 
    background: linear-gradient(90deg, rgba(107, 175, 146, 0.2), rgba(232, 197, 71, 0.1));
    border-left-color: #6BAF92;
    color: #6BAF92; 
}

.admin-nav a i { 
    margin-right: 10px; 
    width: 20px; 
}

.admin-info { 
    position: absolute; 
    bottom: 20px; 
    left: 20px; 
    right: 20px; 
    text-align: center; 
    padding: 15px; 
    background: rgba(52, 73, 94, 0.5);
    border-radius: 10px; 
    border-top: 1px solid rgba(107, 175, 146, 0.3);
}

.admin-name { 
    font-weight: bold; 
    background: linear-gradient(135deg, #6BAF92, #E8C547);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.admin-role { 
    font-size: 12px; 
    color: #bdc3c7; 
    margin-top: 5px; 
}

/* Main Content */
.admin-content { 
    margin-left: 260px; 
    padding: 30px; 
    width: calc(100% - 260px); 
    min-height: 100vh; 
}

.page-header { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 30px; 
    border-radius: 20px; 
    margin-bottom: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.page-title { 
    font-size: 28px; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px; 
    font-weight: 700;
}

.page-subtitle { 
    color: #4B8B6E;
    font-size: 16px; 
    font-weight: 500;
}

.content-section { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 25px; 
    margin-bottom: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.section-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 25px; 
}

.section-title { 
    font-size: 20px; 
    color: #4B8B6E;
    font-weight: 600;
}

/* Form Styles */
.form-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
    margin-bottom: 20px; 
}

.form-group { 
    margin-bottom: 20px; 
}

.form-label { 
    display: block; 
    margin-bottom: 8px; 
    font-weight: 600; 
    color: #4B8B6E;
}

.form-input, .form-select, .form-textarea { 
    width: 100%; 
    padding: 12px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    font-size: 14px; 
    transition: 0.3s; 
    background: rgba(255, 255, 255, 0.9);
}

.form-input:focus, .form-select:focus, .form-textarea:focus { 
    outline: none; 
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
}

.form-textarea { 
    resize: vertical; 
    min-height: 100px; 
}

.btn { 
    padding: 12px 24px; 
    border: none; 
    border-radius: 12px; 
    font-weight: 600; 
    cursor: pointer; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    transition: 0.3s; 
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

.btn-danger { 
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white; 
}

.btn-danger:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
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
    margin-top: 20px; 
}

.data-table th, .data-table td { 
    padding: 15px; 
    text-align: left; 
    border-bottom: 1px solid rgba(107, 175, 146, 0.1);
}

.data-table th { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(232, 197, 71, 0.1));
    font-weight: 600; 
    color: #4B8B6E;
}

.data-table tr:hover { 
    background: rgba(107, 175, 146, 0.05);
}

/* Stats Cards */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
}

.stat-card { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 25px; 
    border-radius: 20px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    display: flex; 
    align-items: center; 
    gap: 20px; 
    border: 2px solid rgba(107, 175, 146, 0.2);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(75, 139, 110, 0.25);
}

.stat-icon { 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 24px; 
    color: white; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.stat-icon.subjects { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
}

.stat-icon.topics { 
    background: linear-gradient(135deg, #f093fb, #f5576c); 
}

.stat-icon.questions { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
}

.stat-content h3 { 
    font-size: 28px; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 5px; 
    font-weight: 700;
}

.stat-content p { 
    color: #4B8B6E;
    font-weight: 500; 
}

/* Message Styles */
.message { 
    padding: 15px; 
    border-radius: 12px; 
    margin-bottom: 20px; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
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

/* Modal Styles */
.modal { 
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0; 
    top: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(0,0,0,0.5); 
}

.modal-content { 
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    margin: 5% auto; 
    padding: 30px; 
    width: 90%; 
    max-width: 500px; 
    border-radius: 20px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.3);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.modal-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
}

.modal-title { 
    font-size: 20px; 
    color: #4B8B6E;
    font-weight: 600;
}

.close { 
    font-size: 28px; 
    font-weight: bold; 
    cursor: pointer; 
    color: #aaa; 
}

.close:hover { 
    color: #4B8B6E;
}

/* Search and Filter Styles */
.filters-section { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 25px; 
    margin-bottom: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.filters-grid { 
    display: grid; 
    grid-template-columns: 1fr 200px auto; 
    gap: 15px; 
    align-items: end; 
}

.search-box { 
    position: relative; 
}

.search-input { 
    padding-left: 45px; 
}

.search-icon { 
    position: absolute; 
    left: 15px; 
    top: 50%; 
    transform: translateY(-50%); 
    color: #6BAF92;
}

.filter-buttons { 
    display: flex; 
    gap: 10px; 
    align-items: center; 
}

.filter-buttons .btn { 
    height: 46px; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    white-space: nowrap; 
}

.clear-filters { 
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    color: white; 
    text-decoration: none; 
}

.clear-filters:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
}

/* Pagination Styles */
.pagination { 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    gap: 10px; 
    margin-top: 30px; 
}

.pagination a, .pagination span { 
    padding: 10px 15px; 
    border-radius: 12px; 
    text-decoration: none; 
    color: #4B8B6E;
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.pagination a:hover { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
}

.pagination .current { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
    font-weight: bold; 
}

.pagination .disabled { 
    color: #bdc3c7; 
    cursor: not-allowed; 
}

/* Results Info */
.results-info { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
    color: #4B8B6E;
    font-size: 14px; 
    font-weight: 500;
}

/* Badge Styles */
.badge { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(232, 197, 71, 0.15));
    color: #4B8B6E;
    padding: 6px 12px; 
    border-radius: 12px; 
    font-size: 12px; 
    font-weight: 600; 
    border: 1px solid rgba(107, 175, 146, 0.3);
}

/* Quick Stats */
.quick-stats { 
    display: flex; 
    gap: 20px; 
    margin-bottom: 20px; 
}

.quick-stat { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(232, 197, 71, 0.1));
    padding: 10px 15px; 
    border-radius: 12px; 
    font-size: 14px; 
    border: 1px solid rgba(107, 175, 146, 0.2);
}

.quick-stat strong { 
    color: #4B8B6E;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-content { margin-left: 0; width: 100%; }
    .admin-sidebar { transform: translateX(-100%); }
    .form-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr; }
    .filters-grid { grid-template-columns: 1fr; }
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
        <a href="manage_materials.php"><i class="fas fa-book"></i> Learning Materials</a>
        <a href="add_activity.php"><i class="fas fa-plus-circle"></i> Add Activity</a>
        <a href="add_questions.php"><i class="fas fa-question-circle"></i> Add Questions</a>
        <a href="manage_topics.php" class="active"><i class="fas fa-list"></i> Topics & Subjects</a>
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
        <h1 class="page-title">Topics & Subjects Management 📚</h1>
        <p class="page-subtitle">Organize learning content into subjects and topics</p>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon subjects"><i class="fas fa-graduation-cap"></i></div>
            <div class="stat-content">
                <h3><?= count($subjects) ?></h3>
                <p>Total Subjects</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon topics"><i class="fas fa-list-alt"></i></div>
            <div class="stat-content">
                <h3><?= count($topics) ?></h3>
                <p>Total Topics</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon questions"><i class="fas fa-question-circle"></i></div>
            <div class="stat-content">
                <h3><?= array_sum(array_column($topics, 'question_count')) ?></h3>
                <p>Total Questions</p>
            </div>
        </div>
    </div>

    <!-- Add Subject Section -->
    <div class="content-section">
        <div class="section-header">
            <h2 class="section-title">Add New Subject</h2>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Subject Code</label>
                    <input type="text" name="code" class="form-input" placeholder="e.g., OOP1, WEB1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g., Object Oriented Programming 1" required>
                </div>
            </div>
            <button type="submit" name="add_subject" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Subject
            </button>
        </form>
    </div>

    <!-- Add Topic Section -->
    <div class="content-section">
        <div class="section-header">
            <h2 class="section-title">Add New Topic</h2>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['code'] . ' - ' . $subject['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Topic Name</label>
                    <input type="text" name="topic_name" class="form-input" placeholder="e.g., Classes and Objects" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" placeholder="Brief description of the topic..."></textarea>
            </div>
            <button type="submit" name="add_topic" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Topic
            </button>
        </form>
    </div>

    <!-- Subjects List -->
    <div class="content-section">
        <div class="section-header">
            <h2 class="section-title">Subjects</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Topics</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($subject['code']) ?></strong></td>
                    <td><?= htmlspecialchars($subject['name']) ?></td>
                    <td><span class="badge"><?= $subject['topic_count'] ?> topics</span></td>
                    <td>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this subject?')">
                            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                            <button type="submit" name="delete_subject" class="btn btn-danger" <?= $subject['topic_count'] > 0 ? 'disabled title="Cannot delete subject with topics"' : '' ?>>
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Topics List -->
    <div class="content-section" id="topics-section">
        <div class="section-header">
            <h2 class="section-title">Topics</h2>
        </div>

        <!-- Search and Filter Section -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="form-input search-input" placeholder="Search topics, subjects, or descriptions..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="subject_filter" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>" <?= $subject_filter == $subject['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['code'] . ' - ' . $subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="?" class="btn clear-filters">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <div class="results-info">
            <div class="quick-stats">
                <div class="quick-stat">
                    <strong><?= $total_topics ?></strong> total topics found
                </div>
                <?php if (!empty($search) || !empty($subject_filter)): ?>
                <div class="quick-stat">
                    Showing page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <div>
                Showing <?= count($topics) ?> of <?= $total_topics ?> topics
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Topic Name</th>
                    <th>Description</th>
                    <th>Questions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topics)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                        No topics found matching your criteria.<br>
                        <small>Try adjusting your search or filter settings.</small>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($topic['subject_code']) ?></strong><br>
                        <small><?= htmlspecialchars($topic['subject_name']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($topic['name']) ?></td>
                    <td><?= htmlspecialchars($topic['description'] ?: 'No description') ?></td>
                    <td><span class="badge"><?= $topic['question_count'] ?> questions</span></td>
                    <td>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this topic?')">
                            <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                            <button type="submit" name="delete_topic" class="btn btn-danger" <?= $topic['question_count'] > 0 ? 'disabled title="Cannot delete topic with questions"' : '' ?>>
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <!-- Previous Page -->
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i> Previous</span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>"><?= $total_pages ?></a>
            <?php endif; ?>

            <!-- Next Page -->
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-hide messages after 5 seconds
setTimeout(function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
    });
}, 5000);

// Scroll position preservation
function saveScrollPosition() {
    sessionStorage.setItem('topicsScrollPosition', window.pageYOffset);
}

function restoreScrollPosition() {
    const scrollPosition = sessionStorage.getItem('topicsScrollPosition');
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
        sessionStorage.removeItem('topicsScrollPosition');
    }
}

// Save scroll position before form submission or page navigation
document.addEventListener('DOMContentLoaded', function() {
    // Restore scroll position on page load
    restoreScrollPosition();
    
    // Save scroll position before form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', saveScrollPosition);
    });
    
    // Save scroll position before pagination links
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(link => {
        link.addEventListener('click', saveScrollPosition);
    });
    
    // Auto-submit search form on enter
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                saveScrollPosition();
                this.closest('form').submit();
            }
        });
    }
    
    // Highlight search terms
    const searchTerm = '<?= htmlspecialchars($search) ?>';
    if (searchTerm) {
        const tableBody = document.querySelector('.data-table tbody');
        if (tableBody) {
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            tableBody.innerHTML = tableBody.innerHTML.replace(regex, '<mark style="background: #fff3cd; padding: 2px 4px; border-radius: 3px;">$1</mark>');
        }
    }
    
    // Smooth scroll to topics section after actions
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('scrollToTopics')) {
        setTimeout(() => {
            const topicsSection = document.querySelector('.content-section:last-child');
            if (topicsSection) {
                topicsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }
});

// Quick clear search with scroll preservation
function clearSearch() {
    saveScrollPosition();
    window.location.href = '?';
}
</script>

</body>
</html>