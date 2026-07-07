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

// Filtering and search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$subject_filter = isset($_GET['subject_filter']) ? $_GET['subject_filter'] : '';
$progress_filter = isset($_GET['progress_filter']) ? $_GET['progress_filter'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get dashboard statistics
// 1. Total Students
$totalStudentsQuery = "SELECT COUNT(*) as total FROM login_credentials WHERE role = 'student'";
$totalStudents = $conn->query($totalStudentsQuery)->fetch_assoc()['total'];

// 2. Active Students (with activity in last 30 days)
$activeStudentsQuery = "
    SELECT COUNT(DISTINCT student_id) as active 
    FROM student_activity_scores 
    WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$activeStudents = $conn->query($activeStudentsQuery)->fetch_assoc()['active'];

// 3. Total Activities Completed
$totalActivitiesQuery = "SELECT COUNT(*) as total FROM student_activity_scores";
$totalActivities = $conn->query($totalActivitiesQuery)->fetch_assoc()['total'];

// 4. Average Progress Rate
$avgProgressQuery = "
    SELECT AVG(avg_score) as avg_progress 
    FROM student_activity_scores
";
$avgProgress = $conn->query($avgProgressQuery)->fetch_assoc()['avg_progress'] ?: 0;

// 5. Students at Risk (Simplified)
$studentsAtRiskQuery = "
    SELECT 
        lc.id,
        lc.username,
        lc.email,
        lc.created_at,
        COALESCE(AVG(sas.avg_score), 0) as avg_score,
        MAX(sas.last_updated) as last_activity,
        COUNT(sas.id) as activities_completed
    FROM login_credentials lc
    LEFT JOIN student_activity_scores sas ON lc.id = sas.student_id
    WHERE lc.role = 'student' 
    GROUP BY lc.id, lc.username, lc.email, lc.created_at
    HAVING 
        (avg_score < 50 AND avg_score > 0) OR
        (activities_completed = 0 AND lc.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)) OR
        (last_activity <= DATE_SUB(NOW(), INTERVAL 14 DAY) AND activities_completed > 0)
    ORDER BY avg_score ASC, last_activity ASC
    LIMIT 10
";
$studentsAtRisk = $conn->query($studentsAtRiskQuery)->fetch_all(MYSQLI_ASSOC);

// Get all students for dropdown
$allStudentsQuery = "
    SELECT 
        id, 
        username,
        username as full_name
    FROM login_credentials 
    WHERE role = 'student' 
    ORDER BY username
";
$allStudents = $conn->query($allStudentsQuery)->fetch_all(MYSQLI_ASSOC);

// 6. Dynamic Performance Query (Subject or Topic based on filter)
$performance_type = isset($_GET['performance_type']) ? $_GET['performance_type'] : 'subject';
$performance_id = isset($_GET['performance_id']) ? $_GET['performance_id'] : '';
$selected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// Build WHERE clause for student filtering
$student_where = "";
$student_params = [];
$param_types = "";

if (!empty($selected_student_id)) {
    $student_where = " AND sas.student_id = ?";
    $student_params[] = $selected_student_id;
    $param_types .= "i";
}

if ($performance_type == 'topic' && !empty($performance_id)) {
    // Topic-specific performance
    $performanceQuery = "
        SELECT 
            t.name as item_name,
            t.id as item_id,
            t.subject_id,
            COUNT(DISTINCT sas.student_id) as student_count,
            AVG(sas.avg_score) as avg_performance,
            COUNT(sas.id) as total_activities,
            MIN(sas.avg_score) as min_score,
            MAX(sas.avg_score) as max_score
        FROM topics t
        LEFT JOIN student_activity_scores sas ON t.id = sas.topic_id
        WHERE t.id = ?" . $student_where . "
        GROUP BY t.id, t.name, t.subject_id
    ";
    $stmt = $conn->prepare($performanceQuery);
    $all_params = array_merge([$performance_id], $student_params);
    $all_types = "i" . $param_types;
    if (!empty($all_params)) {
        $stmt->bind_param($all_types, ...$all_params);
    }
    $stmt->execute();
    $performanceData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Subject-specific performance (default)
    if (!empty($performance_id)) {
        $performanceQuery = "
            SELECT 
                t.name as item_name,
                t.id as item_id,
                t.subject_id,
                COUNT(DISTINCT sas.student_id) as student_count,
                AVG(sas.avg_score) as avg_performance,
                COUNT(sas.id) as total_activities,
                MIN(sas.avg_score) as min_score,
                MAX(sas.avg_score) as max_score
            FROM topics t
            LEFT JOIN student_activity_scores sas ON t.id = sas.topic_id
            WHERE t.subject_id = ?" . $student_where . "
            GROUP BY t.id, t.name, t.subject_id
            ORDER BY avg_performance DESC
        ";
        $stmt = $conn->prepare($performanceQuery);
        $all_params = array_merge([$performance_id], $student_params);
        $all_types = "i" . $param_types;
        if (!empty($all_params)) {
            $stmt->bind_param($all_types, ...$all_params);
        }
        $stmt->execute();
        $performanceData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // All subjects performance
        $performanceQuery = "
            SELECT 
                s.name as item_name,
                s.id as item_id,
                s.id as subject_id,
                COUNT(DISTINCT sas.student_id) as student_count,
                AVG(sas.avg_score) as avg_performance,
                COUNT(sas.id) as total_activities,
                MIN(sas.avg_score) as min_score,
                MAX(sas.avg_score) as max_score
            FROM subjects s
            JOIN topics t ON s.id = t.subject_id
            LEFT JOIN student_activity_scores sas ON t.id = sas.topic_id
            WHERE 1=1" . $student_where . "
            GROUP BY s.id, s.name
            ORDER BY avg_performance DESC
        ";
        if (!empty($student_params)) {
            $stmt = $conn->prepare($performanceQuery);
            $stmt->bind_param($param_types, ...$student_params);
            $stmt->execute();
            $performanceData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $performanceData = $conn->query($performanceQuery)->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Get Progress Card Data (Pre-test/Post-test style comparison)
$progressCards = [];
if (!empty($selected_student_id)) {
    // For individual student - get topic-level progress cards
    $progressCardQuery = "
        SELECT 
            t.id,
            t.name as topic_name,
            s.code as subject_code,
            s.name as subject_name,
            MIN(sas.avg_score) as pre_score,
            MAX(sas.avg_score) as post_score,
            COUNT(sas.id) as practice_sessions,
            AVG(sas.avg_score) as avg_score,
            CASE 
                WHEN MAX(sas.avg_score) > MIN(sas.avg_score) THEN 'improvement'
                WHEN MAX(sas.avg_score) < MIN(sas.avg_score) THEN 'decline'
                ELSE 'stable'
            END as trend,
            ROUND(((MAX(sas.avg_score) - MIN(sas.avg_score)) / MIN(sas.avg_score)) * 100, 1) as improvement_percentage
        FROM topics t
        JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN student_activity_scores sas ON t.id = sas.topic_id AND sas.student_id = ?
        WHERE sas.student_id IS NOT NULL
        GROUP BY t.id, t.name, s.code, s.name
        ORDER BY s.code, t.name
    ";
    $stmt = $conn->prepare($progressCardQuery);
    $stmt->bind_param("i", $selected_student_id);
    $stmt->execute();
    $progressCards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // For general view - get subject-level summary cards
    $progressCardQuery = "
        SELECT 
            s.id,
            s.name as subject_name,
            s.code as subject_code,
            COUNT(DISTINCT sas.student_id) as total_students,
            AVG(sas.avg_score) as avg_score,
            COUNT(sas.id) as total_activities,
            MIN(sas.avg_score) as min_score,
            MAX(sas.avg_score) as max_score,
            CASE 
                WHEN AVG(sas.avg_score) >= 80 THEN 'excellent'
                WHEN AVG(sas.avg_score) >= 60 THEN 'good'
                ELSE 'needs_attention'
            END as performance_level
        FROM subjects s
        JOIN topics t ON s.id = t.subject_id
        LEFT JOIN student_activity_scores sas ON t.id = sas.topic_id
        GROUP BY s.id, s.name, s.code
        ORDER BY s.code
    ";
    $progressCards = $conn->query($progressCardQuery)->fetch_all(MYSQLI_ASSOC);
}

// 7. 30-Day Activity Trends (Simplified)
$activityTrendsQuery = "
    SELECT 
        DATE(date_created) as date,
        COUNT(*) as activities_completed,
        COUNT(DISTINCT student_id) as unique_students
    FROM student_activity_scores
    WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(date_created)
    ORDER BY date ASC
";
$activityTrends = $conn->query($activityTrendsQuery)->fetch_all(MYSQLI_ASSOC);

// 8. Video & Material Engagement (Last 30 days)
$videoTrendsQuery = "
    SELECT 
        DATE(watched_at) as date,
        COUNT(*) as videos_watched
    FROM student_video_progress
    WHERE watched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(watched_at)
    ORDER BY date ASC
";
$videoTrends = $conn->query($videoTrendsQuery)->fetch_all(MYSQLI_ASSOC);

// Get all subjects and topics for filters
$subjectsQuery = "SELECT * FROM subjects ORDER BY code";
$subjects = $conn->query($subjectsQuery)->fetch_all(MYSQLI_ASSOC);

$topicsQuery = "SELECT t.*, s.code as subject_code FROM topics t JOIN subjects s ON t.subject_id = s.id ORDER BY s.code, t.name";
$topics = $conn->query($topicsQuery)->fetch_all(MYSQLI_ASSOC);

// Get all subjects for filter
$subjectsQuery = "SELECT * FROM subjects ORDER BY code";
$subjects = $conn->query($subjectsQuery)->fetch_all(MYSQLI_ASSOC);

// Build student progress query with filters
$progressQuery = "
    SELECT 
        lc.id as student_id,
        lc.username,
        lc.email,
        lc.created_at as registration_date,
        COUNT(DISTINCT sas.topic_id) as topics_completed,
        COUNT(DISTINCT st.topic_id) as assessments_taken,
        AVG(sas.avg_score) as overall_progress,
        MAX(sas.last_updated) as last_activity,
        COUNT(DISTINCT svp.material_id) as videos_watched,
        (SELECT COUNT(*) FROM topics) as total_topics
    FROM login_credentials lc
    LEFT JOIN student_activity_scores sas ON lc.id = sas.student_id
    LEFT JOIN student_tests st ON lc.id = st.student_id
    LEFT JOIN student_video_progress svp ON lc.id = svp.student_id
    WHERE lc.role = 'student'
";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $progressQuery .= " AND (lc.username LIKE ? OR lc.email LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
    $types .= "ss";
}

// Add subject filter (students who have activity in specific subject)
if (!empty($subject_filter)) {
    $progressQuery .= " AND EXISTS (
        SELECT 1 FROM student_activity_scores sas2 
        JOIN topics t ON sas2.topic_id = t.id 
        WHERE sas2.student_id = lc.id AND t.subject_id = ?
    )";
    $params[] = $subject_filter;
    $types .= "i";
}

$progressQuery .= " GROUP BY lc.id, lc.username, lc.email, lc.created_at";

// Add progress filter
if (!empty($progress_filter)) {
    switch ($progress_filter) {
        case 'high':
            $progressQuery .= " HAVING overall_progress >= 80";
            break;
        case 'medium':
            $progressQuery .= " HAVING overall_progress >= 50 AND overall_progress < 80";
            break;
        case 'low':
            $progressQuery .= " HAVING overall_progress < 50";
            break;
        case 'inactive':
            $progressQuery .= " HAVING topics_completed = 0";
            break;
    }
}

$progressQuery .= " ORDER BY overall_progress DESC, last_activity DESC";

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM (" . $progressQuery . ") as subquery";

if (!empty($params)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total_students = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $total_students = $conn->query($countQuery)->fetch_assoc()['total'];
}

$total_pages = ceil($total_students / $per_page);

// Add pagination to main query
$progressQuery .= " LIMIT ? OFFSET ?";
$params = array_merge($params, [$per_page, $offset]);
$types .= "ii";

// Execute progress query
if (!empty($params)) {
    $progressStmt = $conn->prepare($progressQuery);
    $progressStmt->bind_param($types, ...$params);
    $progressStmt->execute();
    $students = $progressStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $progressStmt->close();
} else {
    $students = $conn->query($progressQuery)->fetch_all(MYSQLI_ASSOC);
}

// Get recent activity for dashboard
$recentActivityQuery = "
    SELECT 
        lc.username,
        sas.topic_id,
        t.name as topic_name,
        s.code as subject_code,
        sas.avg_score,
        sas.last_updated
    FROM student_activity_scores sas
    JOIN login_credentials lc ON sas.student_id = lc.id
    JOIN topics t ON sas.topic_id = t.id
    JOIN subjects s ON t.subject_id = s.id
    ORDER BY sas.last_updated DESC
    LIMIT 10
";
$recentActivity = $conn->query($recentActivityQuery)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Progress - SkillSync Admin</title>
<link rel="shortcut icon" sizes="32x32" href="../LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

.stat-icon.students { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
}

.stat-icon.active { 
    background: linear-gradient(135deg, #f093fb, #f5576c); 
}

.stat-icon.activities { 
    background: linear-gradient(135deg, #4facfe, #00f2fe); 
}

.stat-icon.progress { 
    background: linear-gradient(135deg, #43e97b, #38f9d7); 
}

.stat-icon.risk { 
    background: linear-gradient(135deg, #ff6b6b, #ee5a24); 
}

/* Analytics Cards */
.analytics-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
}

.analytics-card { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 25px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.analytics-card h3 { 
    color: #4B8B6E;
    margin-bottom: 20px; 
    font-size: 18px; 
    font-weight: 600;
}

/* Chart Containers */
.chart-container { 
    position: relative; 
    height: 300px; 
    margin: 20px 0; 
}

.chart-small { 
    height: 200px; 
}

/* Risk Analytics */
.risk-section { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
}

.risk-section h3 { 
    color: white; 
}

.risk-item { 
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 15px; 
    border-radius: 12px; 
    margin-bottom: 10px; 
}

.risk-level { 
    font-weight: bold; 
    text-transform: uppercase; 
    font-size: 12px; 
}

.risk-high { 
    border-left: 4px solid #e74c3c; 
}

.risk-medium { 
    border-left: 4px solid #E8C547;
}

.risk-low { 
    border-left: 4px solid #F4D77C;
}

/* Performance Metrics */
.metric-row { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 10px 0; 
    border-bottom: 1px solid rgba(107, 175, 146, 0.1);
}

.metric-row:last-child { 
    border-bottom: none; 
}

.metric-label { 
    font-weight: 600; 
    color: #4B8B6E;
}

.metric-value { 
    font-size: 18px; 
    font-weight: bold; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.metric-trend { 
    font-size: 12px; 
    margin-left: 10px; 
}

.trend-up { 
    color: #6BAF92;
}

.trend-down { 
    color: #e74c3c; 
}

.trend-stable { 
    color: #95a5a6; 
}

/* Data Tables */
.data-table-analytics { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 15px; 
}

.data-table-analytics th, .data-table-analytics td { 
    padding: 12px; 
    text-align: left; 
    border-bottom: 1px solid rgba(107, 175, 146, 0.1);
}

.data-table-analytics th { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(232, 197, 71, 0.1));
    font-weight: 600; 
    color: #4B8B6E;
    font-size: 14px; 
}

.data-table-analytics tr:hover { 
    background: rgba(107, 175, 146, 0.05);
}

/* KPI Cards */
.kpi-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 15px; 
    margin-bottom: 30px; 
}

.kpi-card { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 20px; 
    border-radius: 12px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    text-align: center; 
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.kpi-value { 
    font-size: 32px; 
    font-weight: bold; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 5px; 
}

.kpi-label { 
    color: #4B8B6E;
    font-size: 14px; 
    font-weight: 500; 
}

/* Progress Cards */
.progress-card { 
    transition: all 0.3s ease; 
}

.progress-card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 15px 50px rgba(75, 139, 110, 0.25) !important; 
}

.clickable-topic { 
    cursor: pointer; 
    transition: all 0.2s ease;
}

.clickable-topic:hover { 
    background: rgba(107, 175, 146, 0.05) !important; 
    transform: translateX(5px);
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 60px rgba(75, 139, 110, 0.3);
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(107, 175, 146, 0.2);
}

.modal-title {
    font-size: 20px;
    font-weight: bold;
    color: #4B8B6E;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #7f8c8d;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: rgba(107, 175, 146, 0.1);
    color: #4B8B6E;
}

.progress-chart {
    height: 400px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        padding: 20px;
        margin: 10px;
    }
    .progress-chart {
        height: 300px;
    }
}

.kpi-change { 
    font-size: 12px; 
    margin-top: 8px; 
}

/* Progress Indicators */
.progress-indicator { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    margin: 8px 0; 
}

.progress-bar-small { 
    flex: 1; 
    height: 6px; 
    background: rgba(107, 175, 146, 0.2);
    border-radius: 3px; 
    overflow: hidden; 
}

.progress-fill-small { 
    height: 100%; 
    background: linear-gradient(90deg, #e74c3c 0%, #E8C547 50%, #6BAF92 100%);
    transition: width 0.3s; 
}

.progress-percentage { 
    font-weight: 600; 
    color: #4B8B6E;
    min-width: 45px; 
    text-align: right; 
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
    grid-template-columns: 1fr 200px 150px auto; 
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

/* Form Styles */
.form-input, .form-select { 
    width: 100%; 
    padding: 12px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    font-size: 14px; 
    transition: 0.3s; 
    background: rgba(255, 255, 255, 0.9);
}

.form-input:focus, .form-select:focus { 
    outline: none; 
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
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

.clear-filters { 
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    color: white; 
    text-decoration: none; 
}

.clear-filters:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
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

/* Progress Bars */
.progress-bar { 
    width: 100%; 
    height: 8px; 
    background: rgba(107, 175, 146, 0.2);
    border-radius: 4px; 
    overflow: hidden; 
}
.progress-fill { 
    height: 100%; 
    background: linear-gradient(90deg, #e74c3c 0%, #E8C547 50%, #6BAF92 100%);
    transition: width 0.3s; 
}

/* Status Badges */
.status-badge { 
    padding: 4px 12px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: 600; 
    text-transform: uppercase; 
}
.status-high { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.2), rgba(107, 175, 146, 0.3));
    color: #4B8B6E;
    border: 1px solid #6BAF92;
}
.status-medium { 
    background: linear-gradient(135deg, rgba(232, 197, 71, 0.2), rgba(232, 197, 71, 0.3));
    color: #9a7a1a;
    border: 1px solid #E8C547;
}
.status-low { 
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.2), rgba(231, 76, 60, 0.3));
    color: #c0392b;
    border: 1px solid #e74c3c;
}
.status-inactive { 
    background: rgba(149, 165, 166, 0.2);
    color: #7f8c8d;
    border: 1px solid #95a5a6;
}

/* Results Info */
.results-info { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
    color: #4B8B6E;
    font-size: 14px; 
}
.quick-stats { 
    display: flex; 
    gap: 20px; 
}
.quick-stat { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 10px 15px; 
    border-radius: 12px; 
    font-size: 14px; 
    border: 1px solid rgba(107, 175, 146, 0.2);
}
.quick-stat strong { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Pagination */
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
    backdrop-filter: blur(10px);
    border: 2px solid rgba(107, 175, 146, 0.2);
    transition: all 0.3s ease;
}
.pagination a:hover { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3);
}
.pagination .current { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
    font-weight: bold; 
}
.pagination .disabled { 
    color: #bdc3c7; 
    cursor: not-allowed; 
    opacity: 0.5;
}

/* Activity Feed */
.activity-item { 
    display: flex; 
    align-items: center; 
    padding: 12px 0; 
    border-bottom: 1px solid rgba(107, 175, 146, 0.1);
}
.activity-item:last-child { 
    border-bottom: none; 
}
.activity-icon { 
    width: 40px; 
    height: 40px; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    margin-right: 15px; 
}
.activity-content { 
    flex: 1; 
}
.activity-title { 
    font-weight: 600; 
    color: #4B8B6E;
    margin-bottom: 4px; 
}
.activity-meta { 
    font-size: 12px; 
    color: #6BAF92;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-content { margin-left: 0; width: 100%; }
    .admin-sidebar { transform: translateX(-100%); }
    .filters-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr; }
    .analytics-grid { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
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
        <a href="view_progress.php" class="active"><i class="fas fa-chart-line"></i> Student Progress</a>
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
        <h1 class="page-title">Student Progress Analytics 📊</h1>
        <p class="page-subtitle">Monitor student learning progress and performance metrics</p>
    </div>

    <!-- Simple Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon students"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-content">
                <h3><?= $totalStudents ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?= $activeStudents ?></h3>
                <p>Active Students (30 days)</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon activities"><i class="fas fa-tasks"></i></div>
            <div class="stat-content">
                <h3><?= $totalActivities ?></h3>
                <p>Activities Completed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon progress"><i class="fas fa-chart-bar"></i></div>
            <div class="stat-content">
                <h3><?= number_format($avgProgress, 1) ?>%</h3>
                <p>Average Progress</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon risk"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-content">
                <h3><?= count($studentsAtRisk) ?></h3>
                <p>Students at Risk</p>
            </div>
        </div>
    </div>

    <!-- Simplified Analytics Dashboard -->
    <div class="analytics-grid">
        <!-- Dynamic Performance Analysis -->
        <div class="analytics-card">
            <h3><i class="fas fa-chart-bar"></i> Performance Analysis</h3>
            <div style="margin-bottom: 20px;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <select name="student_id" class="form-select" style="width: 200px;">
                        <option value="">All Students (General View)</option>
                        <?php foreach ($allStudents as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= isset($_GET['student_id']) && $_GET['student_id'] == $student['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(trim($student['full_name']) ?: $student['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="performance_type" class="form-select" style="width: auto;" onchange="togglePerformanceOptions()">
                        <option value="subject" <?= $performance_type == 'subject' ? 'selected' : '' ?>>By Subject</option>
                        <option value="topic" <?= $performance_type == 'topic' ? 'selected' : '' ?>>By Topic</option>
                    </select>
                    
                    <select name="performance_id" class="form-select" style="width: auto;" id="performanceSelect">
                        <option value="">All Items</option>
                        <?php if ($performance_type == 'topic'): ?>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?= $topic['id'] ?>" <?= $performance_id == $topic['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($topic['subject_code'] . ' - ' . $topic['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= $performance_id == $subject['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['code'] . ' - ' . $subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary" style="padding: 8px 16px; margin-left: auto;">Update</button>
                </form>
            </div>
            
            <div class="chart-container chart-small">
                <canvas id="performanceChart"></canvas>
            </div>
            
            <!-- Progress Cards Section -->
            <?php if (!empty($selected_student_id)): ?>
                <!-- Individual Student Progress Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <?php foreach ($progressCards as $card): ?>
                        <div class="progress-card clickable-topic" data-topic-id="<?= $card['id'] ?>" data-student-id="<?= $selected_student_id ?>" style="background: white; border-radius: 12px; padding: 20px; border-left: 4px solid <?= 
                            $card['trend'] == 'improvement' ? '#27ae60' : 
                            ($card['trend'] == 'decline' ? '#e74c3c' : '#f39c12') 
                        ?>; box-shadow: 0 2px 10px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4 style="margin: 0; color: #2c3e50; font-size: 16px;"><?= htmlspecialchars($card['topic_name']) ?></h4>
                                <span style="background: #ecf0f1; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #7f8c8d;">
                                    <?= htmlspecialchars($card['subject_code']) ?>
                                </span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: bold; color: #3498db;"><?= number_format($card['pre_score'], 0) ?>%</div>
                                    <div style="font-size: 12px; color: #7f8c8d;">Initial</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: bold; color: #2c3e50;"><?= number_format($card['post_score'], 0) ?>%</div>
                                    <div style="font-size: 12px; color: #7f8c8d;">Current</div>
                                </div>
                            </div>
                            
                            <div style="background: <?= 
                                $card['trend'] == 'improvement' ? 'rgba(39, 174, 96, 0.1)' : 
                                ($card['trend'] == 'decline' ? 'rgba(231, 76, 60, 0.1)' : 'rgba(243, 156, 18, 0.1)') 
                            ?>; padding: 10px; border-radius: 6px; text-align: center;">
                                <div style="font-weight: bold; color: <?= 
                                    $card['trend'] == 'improvement' ? '#27ae60' : 
                                    ($card['trend'] == 'decline' ? '#e74c3c' : '#f39c12') 
                                ?>;">
                                    <?php if ($card['trend'] == 'improvement'): ?>
                                        <i class="fas fa-arrow-up"></i> +<?= abs($card['improvement_percentage']) ?>% Improvement!
                                    <?php elseif ($card['trend'] == 'decline'): ?>
                                        <i class="fas fa-arrow-down"></i> -<?= abs($card['improvement_percentage']) ?>% Decline
                                    <?php else: ?>
                                        <i class="fas fa-minus"></i> Stable Performance
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                                    <i class="fas fa-dumbbell"></i> <?= $card['practice_sessions'] ?> practice sessions completed<br>
                                    Average: <?= number_format($card['avg_score'], 1) ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- General Subject Overview Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <?php foreach ($progressCards as $card): ?>
                        <div class="progress-card" style="background: white; border-radius: 12px; padding: 20px; border-left: 4px solid <?= 
                            $card['performance_level'] == 'excellent' ? '#27ae60' : 
                            ($card['performance_level'] == 'good' ? '#f39c12' : '#e74c3c') 
                        ?>; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4 style="margin: 0; color: #2c3e50; font-size: 16px;"><?= htmlspecialchars($card['subject_name']) ?></h4>
                                <span style="background: #ecf0f1; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #7f8c8d;">
                                    <?= htmlspecialchars($card['subject_code']) ?>
                                </span>
                            </div>
                            
                            <div style="text-align: center; margin-bottom: 15px;">
                                <div style="font-size: 32px; font-weight: bold; color: <?= 
                                    $card['performance_level'] == 'excellent' ? '#27ae60' : 
                                    ($card['performance_level'] == 'good' ? '#f39c12' : '#e74c3c') 
                                ?>;"><?= number_format($card['avg_score'], 1) ?>%</div>
                                <div style="font-size: 12px; color: #7f8c8d;">Average Performance</div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; font-size: 14px; color: #7f8c8d;">
                                <span><i class="fas fa-users"></i> <?= $card['total_students'] ?> students</span>
                                <span><i class="fas fa-tasks"></i> <?= $card['total_activities'] ?> activities</span>
                            </div>
                            
                            <div style="background: <?= 
                                $card['performance_level'] == 'excellent' ? 'rgba(39, 174, 96, 0.1)' : 
                                ($card['performance_level'] == 'good' ? 'rgba(243, 156, 18, 0.1)' : 'rgba(231, 76, 60, 0.1)') 
                            ?>; padding: 8px; border-radius: 6px; margin-top: 10px; text-align: center; font-size: 12px; font-weight: bold; color: <?= 
                                $card['performance_level'] == 'excellent' ? '#27ae60' : 
                                ($card['performance_level'] == 'good' ? '#f39c12' : '#e74c3c') 
                            ?>;">
                                <?php if ($card['performance_level'] == 'excellent'): ?>
                                    <i class="fas fa-star"></i> Excellent Performance
                                <?php elseif ($card['performance_level'] == 'good'): ?>
                                    <i class="fas fa-thumbs-up"></i> Good Performance
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle"></i> Needs Attention
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Performance Chart -->
        <div class="analytics-card">
            <h3><i class="fas fa-chart-bar"></i> Detailed Performance Breakdown</h3>
            <div class="chart-container chart-small">
                <canvas id="performanceChart"></canvas>
            </div>
            
            <table class="data-table-analytics" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?= ucfirst($performance_type) ?> Name</th>
                        <th>Students</th>
                        <th>Avg Score</th>
                        <th>Activities</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performanceData as $item): ?>
                    <tr class="<?= !empty($selected_student_id) ? 'clickable-topic' : '' ?>" 
                        <?= !empty($selected_student_id) ? 'data-topic-id="' . $item['item_id'] . '" data-student-id="' . $selected_student_id . '" style="cursor: pointer;"' : '' ?>>
                        <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                        <td><?= $item['student_count'] ?></td>
                        <td>
                            <span style="color: <?= $item['avg_performance'] >= 80 ? '#27ae60' : ($item['avg_performance'] >= 60 ? '#f39c12' : '#e74c3c') ?>">
                                <?= number_format($item['avg_performance'], 1) ?>%
                            </span>
                        </td>
                        <td><?= $item['total_activities'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Students at Risk (Simple List) -->
        <div class="analytics-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Students at Risk</h3>
            <div style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">
                Students with low scores or no recent activity
            </div>
            
            <?php if (empty($studentsAtRisk)): ?>
                <div style="text-align: center; padding: 40px; color: #27ae60;">
                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i><br>
                    <strong>Great news!</strong><br>
                    No students at risk currently
                </div>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($studentsAtRisk as $student): ?>
                    <div class="risk-item" style="background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #e74c3c;">
                        <div style="font-weight: bold; color: #2c3e50;">
                            <?= htmlspecialchars($student['username']) ?>
                        </div>
                        <div style="font-size: 12px; color: #7f8c8d; margin: 5px 0;">
                            <?= htmlspecialchars($student['email']) ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span>Score: <strong style="color: #e74c3c;"><?= number_format($student['avg_score'], 1) ?>%</strong></span>
                            <span>Activities: <strong><?= $student['activities_completed'] ?></strong></span>
                        </div>
                        <?php if ($student['last_activity']): ?>
                            <div style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                                Last active: <?= date('M j, Y', strtotime($student['last_activity'])) ?>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 12px; color: #e74c3c; margin-top: 5px;">
                                No activity yet
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 30-Day Activity Trends (Simplified) -->
        <div class="analytics-card">
            <h3><i class="fas fa-chart-line"></i> 30-Day Activity Trends</h3>
            <div class="chart-container">
                <canvas id="activityChart"></canvas>
            </div>
            <div style="margin-top: 15px; display: flex; justify-content: space-around; text-align: center;">
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: #3498db;">
                        <?= array_sum(array_column($activityTrends, 'activities_completed')) ?>
                    </div>
                    <div style="font-size: 12px; color: #7f8c8d;">Total Activities</div>
                </div>
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: #27ae60;">
                        <?= array_sum(array_column($videoTrends, 'videos_watched')) ?>
                    </div>
                    <div style="font-size: 12px; color: #7f8c8d;">Videos Watched</div>
                </div>
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: #e74c3c;">
                        <?= count(array_unique(array_column($activityTrends, 'unique_students'))) ?>
                    </div>
                    <div style="font-size: 12px; color: #7f8c8d;">Active Students</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="content-section">
        <div class="section-header">
            <h2 class="section-title">Recent Activity</h2>
        </div>
        <div class="activity-feed">
            <?php foreach ($recentActivity as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">
                        <?= htmlspecialchars($activity['username']) ?> completed 
                        <strong><?= htmlspecialchars($activity['topic_name']) ?></strong>
                    </div>
                    <div class="activity-meta">
                        <?= htmlspecialchars($activity['subject_code']) ?> • 
                        Score: <?= number_format($activity['avg_score'], 1) ?>% • 
                        <?= date('M j, Y g:i A', strtotime($activity['last_updated'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Student Progress List -->
    <div class="content-section" id="progress-section">
        <div class="section-header">
            <h2 class="section-title">Student Progress</h2>
        </div>

        <!-- Search and Filter Section -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="form-input search-input" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="subject_filter" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>" <?= $subject_filter == $subject['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['code'] . ' - ' . $subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="progress_filter" class="form-select">
                    <option value="">All Progress Levels</option>
                    <option value="high" <?= $progress_filter == 'high' ? 'selected' : '' ?>>High (80%+)</option>
                    <option value="medium" <?= $progress_filter == 'medium' ? 'selected' : '' ?>>Medium (50-79%)</option>
                    <option value="low" <?= $progress_filter == 'low' ? 'selected' : '' ?>>Low (<50%)</option>
                    <option value="inactive" <?= $progress_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
                    <strong><?= count($students) ?></strong> students showing
                </div>
                <div class="quick-stat">
                    Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
                </div>
            </div>
            <div>
                Total: <?= $total_students ?> students
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Overall Progress</th>
                    <th>Topics Completed</th>
                    <th>Assessments</th>
                    <th>Videos Watched</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                        No students found matching your criteria.<br>
                        <small>Try adjusting your search or filter settings.</small>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($students as $student): 
                    $progress = $student['overall_progress'] ?: 0;
                    $status = $progress >= 80 ? 'high' : ($progress >= 50 ? 'medium' : ($progress > 0 ? 'low' : 'inactive'));
                    $statusText = $status == 'high' ? 'Excellent' : ($status == 'medium' ? 'Good' : ($status == 'low' ? 'Needs Help' : 'Inactive'));
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($student['username']) ?></strong><br>
                        <small><?= htmlspecialchars($student['email']) ?></small><br>
                        <small style="color: #7f8c8d;">Joined: <?= date('M j, Y', strtotime($student['registration_date'])) ?></small>
                    </td>
                    <td>
                        <div style="margin-bottom: 5px;">
                            <strong><?= number_format($progress, 1) ?>%</strong>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= min(100, $progress) ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <strong><?= $student['topics_completed'] ?></strong> / <?= $student['total_topics'] ?><br>
                        <small><?= $student['total_topics'] > 0 ? number_format(($student['topics_completed'] / $student['total_topics']) * 100, 1) : 0 ?>% completed</small>
                    </td>
                    <td>
                        <i class="fas fa-clipboard-check" style="color: #3498db;"></i> 
                        <?= $student['assessments_taken'] ?> taken
                    </td>
                    <td>
                        <i class="fas fa-play-circle" style="color: #e74c3c;"></i> 
                        <?= $student['videos_watched'] ?> watched
                    </td>
                    <td>
                        <?php if ($student['last_activity']): ?>
                            <?= date('M j, Y', strtotime($student['last_activity'])) ?><br>
                            <small style="color: #7f8c8d;"><?= date('g:i A', strtotime($student['last_activity'])) ?></small>
                        <?php else: ?>
                            <span style="color: #bdc3c7;">No activity</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $status ?>">
                            <?= $statusText ?>
                        </span>
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
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>&progress_filter=<?= $progress_filter ?>">
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
                <a href="?page=1&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>&progress_filter=<?= $progress_filter ?>">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>&progress_filter=<?= $progress_filter ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>&progress_filter=<?= $progress_filter ?>"><?= $total_pages ?></a>
            <?php endif; ?>

            <!-- Next Page -->
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&subject_filter=<?= $subject_filter ?>&progress_filter=<?= $progress_filter ?>">
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
// Scroll position preservation
function saveScrollPosition() {
    sessionStorage.setItem('progressScrollPosition', window.pageYOffset);
}

function restoreScrollPosition() {
    const scrollPosition = sessionStorage.getItem('progressScrollPosition');
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
        sessionStorage.removeItem('progressScrollPosition');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Restore scroll position on page load
    restoreScrollPosition();
    
    // Initialize Charts
    initializeCharts();
    
    // Initialize clickable topics
    initializeClickableTopics();
    
    // Save scroll position before form submissions and navigation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', saveScrollPosition);
    });
    
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
});

function initializeCharts() {
    // Dynamic Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const performanceData = <?= json_encode($performanceData) ?>;
    
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: performanceData.map(item => item.item_name),
            datasets: [{
                label: 'Average Performance (%)',
                data: performanceData.map(item => parseFloat(item.avg_performance) || 0),
                backgroundColor: performanceData.map(item => {
                    const score = parseFloat(item.avg_performance) || 0;
                    return score >= 80 ? '#27ae60' : score >= 60 ? '#f39c12' : '#e74c3c';
                }),
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Performance %'
                    }
                }
            }
        }
    });

    // Activity Trends Chart (Simplified)
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityData = <?= json_encode($activityTrends) ?>;
    const videoData = <?= json_encode($videoTrends) ?>;
    
    // Merge data by date
    const dates = [...new Set([
        ...activityData.map(item => item.date),
        ...videoData.map(item => item.date)
    ])].sort();
    
    const activitiesCompleted = dates.map(date => {
        const found = activityData.find(item => item.date === date);
        return found ? found.activities_completed : 0;
    });
    
    const videosWatched = dates.map(date => {
        const found = videoData.find(item => item.date === date);
        return found ? found.videos_watched : 0;
    });
    
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Activities Completed',
                    data: activitiesCompleted,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Videos Watched',
                    data: videosWatched,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    });
}

// Dynamic performance filter handling
function togglePerformanceOptions() {
    const type = document.querySelector('[name="performance_type"]').value;
    const select = document.getElementById('performanceSelect');
    const subjects = <?= json_encode($subjects) ?>;
    const topics = <?= json_encode($topics) ?>;
    
    // Clear current options
    select.innerHTML = '<option value="">All Items</option>';
    
    if (type === 'topic') {
        topics.forEach(topic => {
            const option = document.createElement('option');
            option.value = topic.id;
            option.textContent = topic.subject_code + ' - ' + topic.name;
            select.appendChild(option);
        });
    } else {
        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = subject.code + ' - ' + subject.name;
            select.appendChild(option);
        });
    }
}

// Quick clear search with scroll preservation
function clearSearch() {
    saveScrollPosition();
    window.location.href = '?';
}

// Initialize clickable topics
function initializeClickableTopics() {
    const clickableElements = document.querySelectorAll('.clickable-topic');
    clickableElements.forEach(element => {
        element.addEventListener('click', function() {
            const topicId = this.getAttribute('data-topic-id');
            const studentId = this.getAttribute('data-student-id');
            
            if (topicId && studentId) {
                openProgressModal(topicId, studentId);
            }
        });
    });
}

// Open progress modal and load data
function openProgressModal(topicId, studentId) {
    const modal = document.getElementById('progressModal');
    modal.style.display = 'flex';
    
    // Show loading state
    document.getElementById('modalStudentInfo').innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading student progress...</div>';
    document.getElementById('modalProgressDetails').innerHTML = '';
    
    // Load progress data
    fetch(`get_student_progress.php?topic_id=${topicId}&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateModalContent(data);
                renderProgressChart(data.progressData);
            } else {
                document.getElementById('modalStudentInfo').innerHTML = '<div style="color: #e74c3c; text-align: center;">Error loading progress data</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalStudentInfo').innerHTML = '<div style="color: #e74c3c; text-align: center;">Error loading progress data</div>';
        });
}

// Update modal content with student data
function updateModalContent(data) {
    const studentInfo = document.getElementById('modalStudentInfo');
    const progressDetails = document.getElementById('modalProgressDetails');
    
    studentInfo.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0; color: #2c3e50;">${data.studentInfo.name || data.studentInfo.username}</h4>
                <p style="margin: 5px 0; color: #7f8c8d;">${data.topicInfo.subject_code} - ${data.topicInfo.topic_name}</p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 24px; font-weight: bold; color: #3498db;">${data.summary.current_score}%</div>
                <div style="font-size: 12px; color: #7f8c8d;">Current Score</div>
            </div>
        </div>
    `;
    
    progressDetails.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div style="text-align: center; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold; color: #27ae60;">${data.summary.improvement}%</div>
                <div style="font-size: 12px; color: #7f8c8d;">Improvement</div>
            </div>
            <div style="text-align: center; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold; color: #3498db;">${data.summary.total_attempts}</div>
                <div style="font-size: 12px; color: #7f8c8d;">Total Attempts</div>
            </div>
            <div style="text-align: center; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold; color: #9b59b6;">${data.summary.days_active}</div>
                <div style="font-size: 12px; color: #7f8c8d;">Days Active</div>
            </div>
        </div>
    `;
}

// Render progress over time chart
function renderProgressChart(progressData) {
    const ctx = document.getElementById('progressOverTimeChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (window.progressChart && typeof window.progressChart.destroy === 'function') {
        window.progressChart.destroy();
    }
    
    window.progressChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: progressData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Score Progress',
                data: progressData.map(item => item.score),
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3498db',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Score (%)'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });
}

// Close progress modal
function closeProgressModal() {
    const modal = document.getElementById('progressModal');
    modal.style.display = 'none';
    
    // Destroy chart to prevent memory leaks
    if (window.progressChart && typeof window.progressChart.destroy === 'function') {
        window.progressChart.destroy();
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('progressModal');
    if (event.target === modal) {
        closeProgressModal();
    }
});
</script>

<!-- Progress Over Time Modal -->
<div id="progressModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Progress Over Time</h3>
            <button class="modal-close" onclick="closeProgressModal()">&times;</button>
        </div>
        <div id="modalStudentInfo" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <!-- Student info will be loaded here -->
        </div>
        <div class="progress-chart">
            <canvas id="progressOverTimeChart"></canvas>
        </div>
        <div id="modalProgressDetails" style="margin-top: 20px;">
            <!-- Progress details will be loaded here -->
        </div>
    </div>
</div>

</body>
</html>