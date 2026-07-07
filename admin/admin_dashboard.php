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

// Get dashboard statistics
// 1. Total Students
$totalStudentsQuery = "SELECT COUNT(*) as total FROM login_credentials WHERE role = 'student'";
$totalStudents = $conn->query($totalStudentsQuery)->fetch_assoc()['total'];

// 2. Total Topics
$totalTopicsQuery = "SELECT COUNT(*) as total FROM topics";
$totalTopics = $conn->query($totalTopicsQuery)->fetch_assoc()['total'];

// 3. Total Activities Completed
$totalActivitiesQuery = "SELECT COUNT(*) as total FROM student_activity_scores";
$totalActivities = $conn->query($totalActivitiesQuery)->fetch_assoc()['total'];

// 4. Total Assessments Taken
$totalAssessmentsQuery = "SELECT COUNT(*) as total FROM student_tests";
$totalAssessments = $conn->query($totalAssessmentsQuery)->fetch_assoc()['total'];

// 5. Feedback Statistics
$feedbackStatsQuery = "SELECT 
    COUNT(*) as total_feedback,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_feedback,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_feedback
    FROM student_feedback";
$feedbackStats = $conn->query($feedbackStatsQuery)->fetch_assoc();

// 6. Recent Student Activities
$recentActivitiesQuery = "
    SELECT 
        lc.username,
        'Completed Activity' as activity_type,
        t.name as topic_name,
        sas.date_created as activity_date
    FROM student_activity_scores sas
    JOIN login_credentials lc ON sas.student_id = lc.id
    JOIN topics t ON sas.topic_id = t.id
    ORDER BY sas.date_created DESC
    LIMIT 10
";
$recentActivities = $conn->query($recentActivitiesQuery)->fetch_all(MYSQLI_ASSOC);

// 7. Student Progress Overview
$studentProgressQuery = "
    SELECT 
        lc.username,
        lc.email,
        COUNT(DISTINCT sas.topic_id) as activities_completed,
        COUNT(DISTINCT st.id) as tests_taken,
        lc.created_at as joined_date
    FROM login_credentials lc
    LEFT JOIN student_activity_scores sas ON lc.id = sas.student_id
    LEFT JOIN student_tests st ON lc.id = st.student_id
    WHERE lc.role = 'student'
    GROUP BY lc.id, lc.username, lc.email, lc.created_at
    ORDER BY lc.created_at DESC
    LIMIT 10
";
$studentProgress = $conn->query($studentProgressQuery)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - SkillSync</title>
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

.admin-header { 
    margin-bottom: 40px; 
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

.admin-subtitle { 
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
    transition: all 0.3s ease; 
    border: 2px solid rgba(107, 175, 146, 0.2);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4B8B6E, #E8C547);
}

.stat-card:hover { 
    transform: translateY(-8px); 
    box-shadow: 0 15px 50px rgba(75, 139, 110, 0.25);
    border-color: #6BAF92;
}

.stat-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 15px; 
}

.stat-icon { 
    font-size: 2.5rem; 
}

.stat-icon.students { color: #4B8B6E; }
.stat-icon.topics { color: #E8C547; }
.stat-icon.activities { color: #6BAF92; }
.stat-icon.assessments { color: #F4D77C; }

.stat-value { 
    font-size: 2.2rem; 
    font-weight: 700; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label { 
    color: #4B8B6E;
    font-size: 13px; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
    font-weight: 600;
}

/* Content Sections */
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

.section-title i {
    color: #E8C547;
    margin-right: 8px;
}

/* Tables */
.data-table { 
    width: 100%; 
    border-collapse: collapse; 
}

.data-table th { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(232, 197, 71, 0.1));
    padding: 12px; 
    text-align: left; 
    font-weight: 600; 
    color: #4B8B6E;
    border-bottom: 2px solid rgba(107, 175, 146, 0.3);
}

.data-table td { 
    padding: 12px; 
    border-bottom: 1px solid rgba(107, 175, 146, 0.1);
    color: #2c3e50;
}

.data-table tr:hover { 
    background: rgba(107, 175, 146, 0.05);
}

/* Badges */
.badge { 
    padding: 6px 12px; 
    border-radius: 12px; 
    font-size: 11px; 
    font-weight: 600; 
}

.badge.success { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
}

.badge.info { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
}

.badge.warning { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
}

/* Action Buttons */
.btn { 
    padding: 10px 20px; 
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

.btn-primary { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
}

.btn-primary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3);
}

.btn-success { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
}

.btn-success:hover { 
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

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-content { margin-left: 0; width: 100%; }
    .stats-grid { grid-template-columns: 1fr; }
    .section-header { flex-direction: column; align-items: flex-start; gap: 15px; }
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
        <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a>
        <a href="manage_materials.php"><i class="fas fa-book"></i> Learning Materials</a>
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
        <div class="admin-name"><?= htmlspecialchars($admin_username) ?></div>
        <div class="admin-role">System Administrator</div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content">
    <!-- Header -->
    <div class="admin-header">
        <h1 class="admin-title">Welcome back, <?= htmlspecialchars($admin_username) ?>! 👋</h1>
        <p class="admin-subtitle">Here's what's happening with SkillSync today</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-value"><?= $totalStudents ?></div>
                <i class="fas fa-users stat-icon students"></i>
            </div>
            <div class="stat-label">Total Students</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-value"><?= $totalTopics ?></div>
                <i class="fas fa-list stat-icon topics"></i>
            </div>
            <div class="stat-label">Learning Topics</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-value"><?= $totalActivities ?></div>
                <i class="fas fa-tasks stat-icon activities"></i>
            </div>
            <div class="stat-label">Activities Completed</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-value"><?= $totalAssessments ?></div>
                <i class="fas fa-clipboard-check stat-icon assessments"></i>
            </div>
            <div class="stat-label">Assessments Taken</div>
        </div>

        <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='view_feedback.php'">
            <div class="stat-header">
                <div class="stat-value"><?= $feedbackStats['total_feedback'] ?? 0 ?></div>
                <i class="fas fa-comments stat-icon" style="color: #9b59b6;"></i>
            </div>
            <div class="stat-label">Total Feedback</div>
        </div>

        <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='view_feedback.php?status=pending'">
            <div class="stat-header">
                <div class="stat-value"><?= $feedbackStats['pending_feedback'] ?? 0 ?></div>
                <i class="fas fa-clock stat-icon" style="color: #f39c12;"></i>
            </div>
            <div class="stat-label">Pending Feedback</div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-clock"></i> Recent Student Activities</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Activity</th>
                    <th>Topic</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentActivities)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No recent activities found
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentActivities as $activity): ?>
                <tr>
                    <td><?= htmlspecialchars($activity['username']) ?></td>
                    <td><span class="badge success"><?= htmlspecialchars($activity['activity_type']) ?></span></td>
                    <td><?= htmlspecialchars($activity['topic_name']) ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($activity['activity_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Student Overview -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-graduation-cap"></i> Student Overview</h3>
            <a href="manage_students.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Manage All Students
            </a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Activities</th>
                    <th>Tests</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($studentProgress)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-user-plus" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No students registered yet
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($studentProgress as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['username']) ?></td>
                    <td><?= htmlspecialchars($student['email']) ?></td>
                    <td><span class="badge info"><?= $student['activities_completed'] ?> completed</span></td>
                    <td><span class="badge warning"><?= $student['tests_taken'] ?> taken</span></td>
                    <td><?= date('M j, Y', strtotime($student['joined_date'])) ?></td>
                    <td>
                        <a href="view_student_progress.php?student=<?= $student['username'] ?>" class="btn btn-success">
                            <i class="fas fa-chart-bar"></i> View Progress
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>