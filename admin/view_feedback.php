<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_username = $_SESSION['username'];

// Database connection
require_once '../db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_priority = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT sf.*, lc.username, lc.email 
          FROM student_feedback sf
          LEFT JOIN login_credentials lc ON sf.student_id = lc.id
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_type !== 'all') {
    $query .= " AND sf.feedback_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($filter_status !== 'all') {
    $query .= " AND sf.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_priority !== 'all') {
    $query .= " AND sf.priority = ?";
    $params[] = $filter_priority;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (sf.subject LIKE ? OR sf.message LIKE ? OR sf.student_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY 
            CASE sf.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            sf.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$feedbackList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN feedback_type = 'concern' THEN 1 ELSE 0 END) as concerns,
    SUM(CASE WHEN feedback_type = 'satisfaction' THEN 1 ELSE 0 END) as satisfaction,
    SUM(CASE WHEN feedback_type = 'bug_report' THEN 1 ELSE 0 END) as bugs,
    SUM(CASE WHEN feedback_type = 'feature_request' THEN 1 ELSE 0 END) as features,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent
    FROM student_feedback";
$stats = $conn->query($statsQuery)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Feedback - Admin | SkillSync</title>
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
    overflow-y: auto; 
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
    margin-top: 20px; 
    text-align: center; 
    padding: 20px; 
    border-top: 1px solid rgba(107, 175, 146, 0.3);
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

.admin-header { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 30px; 
    border-radius: 20px; 
    margin-bottom: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.admin-title { 
    font-size: 2.5rem; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px; 
    font-weight: 700; 
}

/* Stats Cards */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
}

.stat-card { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 20px; 
    border-radius: 20px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    text-align: center; 
    border: 2px solid rgba(107, 175, 146, 0.2);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(75, 139, 110, 0.25);
}

.stat-value { 
    font-size: 2rem; 
    font-weight: bold; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label { 
    color: #4B8B6E;
    font-size: 13px; 
    text-transform: uppercase; 
    margin-top: 5px; 
    font-weight: 600;
}

.stat-icon { 
    font-size: 2rem; 
    margin-bottom: 10px; 
}

/* Filters */
.filters { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 20px; 
    border-radius: 20px; 
    margin-bottom: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.filters h3 { 
    margin-bottom: 15px; 
    color: #4B8B6E;
    font-weight: 600;
}

.filter-row { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 15px; 
}

.filter-group { 
    display: flex; 
    flex-direction: column; 
}

.filter-group label { 
    font-size: 13px; 
    font-weight: 600; 
    color: #4B8B6E;
    margin-bottom: 5px; 
}

.filter-group select, .filter-group input { 
    padding: 10px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    font-size: 14px; 
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
}

.filter-group select:focus, .filter-group input:focus { 
    outline: none; 
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
}

.filter-btn { 
    padding: 10px 20px; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
    border: none; 
    border-radius: 12px; 
    cursor: pointer; 
    font-weight: 600; 
    margin-top: auto; 
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(75, 139, 110, 0.2);
}

.filter-btn:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3);
}

.clear-btn { 
    padding: 10px 20px; 
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    color: white; 
    border: none; 
    border-radius: 12px; 
    cursor: pointer; 
    font-weight: 600; 
    margin-top: auto; 
    transition: all 0.3s ease;
}

.clear-btn:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
}

/* Feedback List */
.feedback-container { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 25px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.feedback-item { 
    background: linear-gradient(135deg, rgba(249, 249, 246, 0.8), rgba(255, 255, 255, 0.9));
    backdrop-filter: blur(5px);
    padding: 20px; 
    border-radius: 15px; 
    margin-bottom: 20px; 
    border-left: 5px solid #6BAF92;
    position: relative; 
    transition: all 0.3s ease;
}

.feedback-item:hover {
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(75, 139, 110, 0.2);
}

.feedback-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: flex-start; 
    margin-bottom: 15px; 
    flex-wrap: wrap; 
    gap: 10px; 
}

.feedback-meta { 
    display: flex; 
    gap: 10px; 
    flex-wrap: wrap; 
}

.feedback-title { 
    font-size: 1.3rem; 
    font-weight: 600; 
    color: #4B8B6E;
    margin-bottom: 10px; 
}

.feedback-message { 
    color: #555; 
    line-height: 1.6; 
    margin-bottom: 15px; 
}

.student-info { 
    font-size: 14px; 
    color: #6BAF92;
    margin-bottom: 10px; 
}

.student-info i { 
    margin-right: 5px; 
}

/* Badges */
.badge { 
    padding: 5px 12px; 
    border-radius: 15px; 
    font-size: 11px; 
    font-weight: 600; 
    text-transform: uppercase; 
    display: inline-block; 
}

.badge-concern { 
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.2), rgba(231, 76, 60, 0.3));
    color: #c0392b;
    border: 1px solid #e74c3c;
}

.badge-satisfaction { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.2), rgba(107, 175, 146, 0.3));
    color: #4B8B6E;
    border: 1px solid #6BAF92;
}

.badge-feature_request { 
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(52, 152, 219, 0.3));
    color: #2980b9;
    border: 1px solid #3498db;
}

.badge-bug_report { 
    background: linear-gradient(135deg, rgba(230, 126, 34, 0.2), rgba(230, 126, 34, 0.3));
    color: #d35400;
    border: 1px solid #e67e22;
}

.badge-ui_improvement { 
    background: linear-gradient(135deg, rgba(155, 89, 182, 0.2), rgba(155, 89, 182, 0.3));
    color: #8e44ad;
    border: 1px solid #9b59b6;
}

.badge-general { 
    background: linear-gradient(135deg, rgba(149, 165, 166, 0.2), rgba(149, 165, 166, 0.3));
    color: #7f8c8d;
    border: 1px solid #95a5a6;
}

.status-pending { 
    background: linear-gradient(135deg, rgba(232, 197, 71, 0.2), rgba(232, 197, 71, 0.3));
    color: #9a7a1a;
    border: 1px solid #E8C547;
}

.status-reviewed { 
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(52, 152, 219, 0.3));
    color: #2980b9;
    border: 1px solid #3498db;
}

.status-in_progress { 
    background: linear-gradient(135deg, rgba(155, 89, 182, 0.2), rgba(155, 89, 182, 0.3));
    color: #8e44ad;
    border: 1px solid #9b59b6;
}

.status-resolved { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.2), rgba(107, 175, 146, 0.3));
    color: #4B8B6E;
    border: 1px solid #6BAF92;
}

.status-closed { 
    background: linear-gradient(135deg, rgba(149, 165, 166, 0.2), rgba(149, 165, 166, 0.3));
    color: #7f8c8d;
    border: 1px solid #95a5a6;
}

.priority-low { 
    background: linear-gradient(135deg, rgba(149, 165, 166, 0.2), rgba(149, 165, 166, 0.3));
    color: #7f8c8d;
    border: 1px solid #95a5a6;
}

.priority-medium { 
    background: linear-gradient(135deg, rgba(244, 215, 124, 0.3), rgba(244, 215, 124, 0.4));
    color: #9a7a1a;
    border: 1px solid #F4D77C;
}

.priority-high { 
    background: linear-gradient(135deg, rgba(230, 126, 34, 0.2), rgba(230, 126, 34, 0.3));
    color: #d35400;
    border: 1px solid #e67e22;
}

.priority-urgent { 
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.3), rgba(231, 76, 60, 0.4));
    color: #c0392b;
    font-weight: 700;
    border: 2px solid #e74c3c;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Rating Stars */
.rating { 
    color: #E8C547;
}

.rating i { 
    margin-right: 2px; 
}

/* Actions */
.feedback-actions { 
    display: flex; 
    gap: 10px; 
    margin-top: 15px; 
    flex-wrap: wrap; 
}

.btn { 
    padding: 8px 16px; 
    border: none; 
    border-radius: 12px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: all 0.3s; 
    text-decoration: none; 
    display: inline-block; 
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white; 
}

.btn-success:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(39, 174, 96, 0.3);
}

.btn-warning { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #fff; 
}

.btn-warning:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(232, 197, 71, 0.3);
}

.btn-danger { 
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white; 
}

.btn-danger:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
}

/* Response Section */
.admin-response { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 15px; 
    border-radius: 12px; 
    margin-top: 15px; 
    border-left: 3px solid #6BAF92;
}

.admin-response-header { 
    font-weight: 600; 
    color: #4B8B6E;
    margin-bottom: 8px; 
}

.response-form { 
    margin-top: 15px; 
    display: none; 
}

.response-form.active { 
    display: block; 
}

.response-form textarea { 
    width: 100%; 
    padding: 12px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    resize: vertical; 
    min-height: 100px; 
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
}

.response-form textarea:focus {
    outline: none;
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
}

.response-form select { 
    padding: 10px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    margin-top: 10px; 
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
}

.response-form select:focus {
    outline: none;
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
}

.empty-state { 
    text-align: center; 
    padding: 60px 20px; 
    color: #6BAF92;
}

.empty-state i { 
    font-size: 4rem; 
    margin-bottom: 20px; 
    opacity: 0.3; 
    color: #6BAF92;
}

@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-content { margin-left: 0; width: 100%; }
    .stats-grid, .filter-row { grid-template-columns: 1fr; }
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
        <a href="manage_topics.php"><i class="fas fa-list"></i> Topics & Subjects</a>
        <a href="view_progress.php"><i class="fas fa-chart-line"></i> Student Progress</a>
        <a href="view_feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a>
        <a href="../login.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    
    <div class="admin-info">
        <div class="admin-name"><?= htmlspecialchars($admin_username) ?></div>
        <div class="admin-role">System Administrator</div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content">
    <div class="admin-header">
        <h1 class="admin-title"><i class="fas fa-comments"></i> Student Feedback Management</h1>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-inbox stat-icon" style="color: #3498db;"></i>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Feedback</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock stat-icon" style="color: #f39c12;"></i>
            <div class="stat-value"><?= $stats['pending'] ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle stat-icon" style="color: #27ae60;"></i>
            <div class="stat-value"><?= $stats['resolved'] ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-exclamation-triangle stat-icon" style="color: #e74c3c;"></i>
            <div class="stat-value"><?= $stats['urgent'] ?></div>
            <div class="stat-label">Urgent</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-smile stat-icon" style="color: #27ae60;"></i>
            <div class="stat-value"><?= $stats['satisfaction'] ?></div>
            <div class="stat-label">Satisfaction</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-bug stat-icon" style="color: #e67e22;"></i>
            <div class="stat-value"><?= $stats['bugs'] ?></div>
            <div class="stat-label">Bug Reports</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <h3><i class="fas fa-filter"></i> Filter Feedback</h3>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Feedback Type</label>
                    <select name="type">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="concern" <?= $filter_type === 'concern' ? 'selected' : '' ?>>Concerns</option>
                        <option value="satisfaction" <?= $filter_type === 'satisfaction' ? 'selected' : '' ?>>Satisfaction</option>
                        <option value="feature_request" <?= $filter_type === 'feature_request' ? 'selected' : '' ?>>Feature Requests</option>
                        <option value="bug_report" <?= $filter_type === 'bug_report' ? 'selected' : '' ?>>Bug Reports</option>
                        <option value="ui_improvement" <?= $filter_type === 'ui_improvement' ? 'selected' : '' ?>>UI Improvements</option>
                        <option value="general" <?= $filter_type === 'general' ? 'selected' : '' ?>>General</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="reviewed" <?= $filter_status === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                        <option value="in_progress" <?= $filter_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="closed" <?= $filter_status === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="all" <?= $filter_priority === 'all' ? 'selected' : '' ?>>All Priorities</option>
                        <option value="urgent" <?= $filter_priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        <option value="high" <?= $filter_priority === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="medium" <?= $filter_priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="low" <?= $filter_priority === 'low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search subject, message..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Filter</button>
                <a href="view_feedback.php" class="clear-btn"><i class="fas fa-times"></i> Clear</a>
            </div>
        </form>
    </div>

    <!-- Feedback List -->
    <div class="feedback-container">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-list"></i> Feedback Items (<?= count($feedbackList) ?>)
        </h3>

        <?php if (empty($feedbackList)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No feedback found</h3>
                <p>Try adjusting your filters or wait for students to submit feedback.</p>
            </div>
        <?php else: ?>
            <?php foreach ($feedbackList as $feedback): ?>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="feedback-meta">
                            <span class="badge badge-<?= $feedback['feedback_type'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $feedback['feedback_type'])) ?>
                            </span>
                            <span class="badge status-<?= $feedback['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $feedback['status'])) ?>
                            </span>
                            <span class="badge priority-<?= $feedback['priority'] ?>">
                                <?= strtoupper($feedback['priority']) ?> Priority
                            </span>
                        </div>
                        <small style="color: #7f8c8d;">
                            <i class="fas fa-calendar"></i> 
                            <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?>
                        </small>
                    </div>

                    <div class="student-info">
                        <i class="fas fa-user"></i> <strong><?= htmlspecialchars($feedback['student_name']) ?></strong>
                        <i class="fas fa-envelope" style="margin-left: 15px;"></i> <?= htmlspecialchars($feedback['student_email']) ?>
                    </div>

                    <h4 class="feedback-title"><?= htmlspecialchars($feedback['subject']) ?></h4>
                    <p class="feedback-message"><?= nl2br(htmlspecialchars($feedback['message'])) ?></p>

                    <?php if ($feedback['rating'] !== null): ?>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $feedback['rating'] ? '' : ' far' ?>"></i>
                            <?php endfor; ?>
                            <span style="margin-left: 8px; color: #555;">
                                (<?= $feedback['rating'] ?>/5)
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($feedback['admin_response']): ?>
                        <div class="admin-response">
                            <div class="admin-response-header">
                                <i class="fas fa-user-shield"></i> Admin Response:
                            </div>
                            <p><?= nl2br(htmlspecialchars($feedback['admin_response'])) ?></p>
                            <small style="color: #7f8c8d;">
                                Responded on <?= date('M j, Y g:i A', strtotime($feedback['responded_at'])) ?>
                            </small>
                        </div>
                    <?php endif; ?>

                    <div class="feedback-actions">
                        <button class="btn btn-primary" onclick="toggleResponse(<?= $feedback['id'] ?>)">
                            <i class="fas fa-reply"></i> <?= $feedback['admin_response'] ? 'Update Response' : 'Respond' ?>
                        </button>
                        <button class="btn btn-success" onclick="updateStatus(<?= $feedback['id'] ?>, 'reviewed')">
                            <i class="fas fa-eye"></i> Mark Reviewed
                        </button>
                        <button class="btn btn-warning" onclick="updateStatus(<?= $feedback['id'] ?>, 'in_progress')">
                            <i class="fas fa-spinner"></i> In Progress
                        </button>
                        <button class="btn btn-success" onclick="updateStatus(<?= $feedback['id'] ?>, 'resolved')">
                            <i class="fas fa-check"></i> Resolve
                        </button>
                    </div>

                    <!-- Response Form -->
                    <div class="response-form" id="response-form-<?= $feedback['id'] ?>">
                        <form onsubmit="submitResponse(event, <?= $feedback['id'] ?>)">
                            <textarea name="response" placeholder="Write your response here..." required><?= $feedback['admin_response'] ?></textarea>
                            <select name="new_status">
                                <option value="reviewed">Mark as Reviewed</option>
                                <option value="in_progress">Mark as In Progress</option>
                                <option value="resolved">Mark as Resolved</option>
                            </select>
                            <div style="margin-top: 10px;">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i> Send Response
                                </button>
                                <button type="button" class="btn btn-danger" onclick="toggleResponse(<?= $feedback['id'] ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleResponse(feedbackId) {
    const form = document.getElementById('response-form-' + feedbackId);
    form.classList.toggle('active');
}

function updateStatus(feedbackId, status) {
    if (!confirm('Are you sure you want to update the status?')) return;

    fetch('update_feedback_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ feedback_id: feedbackId, status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

function submitResponse(event, feedbackId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('feedback_id', feedbackId);

    fetch('submit_admin_response.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Response sent successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error('Error:', error);
    });
}
</script>
</body>
</html>
<?php $conn->close(); ?>
