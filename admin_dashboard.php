<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get admin info
$admin_id = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admin_users WHERE id = ?";
$adminStmt = $conn->prepare($adminQuery);
$adminStmt->bind_param("i", $admin_id);
$adminStmt->execute();
$admin = $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();

// Dashboard Statistics
// 1. Total Students
$totalStudentsQuery = "SELECT COUNT(*) as total FROM login_credentials";
$result = $conn->query($totalStudentsQuery);
$totalStudents = $result ? $result->fetch_assoc()['total'] : 0;

// 2. Learning Topics Count
$topicsQuery = "SELECT COUNT(*) as total FROM topics";
$result = $conn->query($topicsQuery);
$totalTopics = $result ? $result->fetch_assoc()['total'] : 0;

// 3. Total Activities Completed
$totalActivitiesQuery = "SELECT COUNT(*) as total FROM student_activity_scores";
$result = $conn->query($totalActivitiesQuery);
$totalActivities = $result ? $result->fetch_assoc()['total'] : 0;

// 4. Total Assessments Taken
$totalAssessmentsQuery = "SELECT COUNT(*) as total FROM student_tests";
$result = $conn->query($totalAssessmentsQuery);
$totalAssessments = $result ? $result->fetch_assoc()['total'] : 0;

// 5. Total Feedback
$totalFeedback = 0;
$totalFeedbackQuery = "SELECT COUNT(*) as total FROM feedback";
$result = $conn->query($totalFeedbackQuery);
if ($result) {
    $totalFeedback = $result->fetch_assoc()['total'];
}

// 6. Pending Feedback (status = 'pending')
$pendingFeedback = 0;
$pendingFeedbackQuery = "SELECT COUNT(*) as total FROM feedback WHERE status = 'pending'";
$result = $conn->query($pendingFeedbackQuery);
if ($result) {
    $pendingFeedback = $result->fetch_assoc()['total'];
}

// 7. Recent Activities (Last 10)
$recentActivitiesQuery = "
    SELECT 
        'assessment' as type,
        CONCAT(lc.username, ' took ', st.test_type, '-test for topic ID ', st.topic_id) as description,
        st.attempt_date as activity_date,
        lc.username
    FROM student_tests st
    JOIN login_credentials lc ON st.student_id = lc.id
    UNION ALL
    SELECT 
        'activity' as type,
        CONCAT(lc.username, ' completed activity for topic ID ', sas.topic_id) as description,
        sas.date_created as activity_date,
        lc.username
    FROM student_activity_scores sas
    JOIN login_credentials lc ON sas.student_id = lc.id
    UNION ALL
    SELECT 
        'video' as type,
        CONCAT(lc.username, ' watched a video') as description,
        svp.watched_at as activity_date,
        lc.username
    FROM student_video_progress svp
    JOIN login_credentials lc ON svp.student_id = lc.id
    ORDER BY activity_date DESC
    LIMIT 10
";
$result = $conn->query($recentActivitiesQuery);
$recentActivities = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// 8. System Performance Data (optional - not displayed on main dashboard yet)
$performanceData = [];

// 9. Top Performing Students
$topStudents = [];
$topStudentsQuery = "
    SELECT 
        lc.username,
        lc.email,
        AVG(st.score) as avg_score,
        COUNT(st.id) as test_count
    FROM login_credentials lc
    JOIN student_tests st ON lc.id = st.student_id
    GROUP BY lc.id, lc.username, lc.email
    HAVING test_count >= 3
    ORDER BY avg_score DESC
    LIMIT 5
";
$result = $conn->query($topStudentsQuery);
if ($result) {
    $topStudents = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSync - Admin Dashboard</title>
    <link rel="shortcut icon" href="LOGO.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Force browser to reload CSS - v2.0 -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
            color: #2c3e50;
            min-height: 100vh;
        }

        /* Header */
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            box-shadow: 0 4px 20px rgba(75, 139, 110, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 2px solid rgba(107, 175, 146, 0.2);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-left img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(75, 139, 110, 0.3);
        }

        .header-left h1 {
            font-size: 1.6rem;
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        .header-left h1 i {
            color: #E8C547;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-info {
            text-align: right;
        }

        .admin-info .name {
            font-weight: 700;
            color: #4B8B6E;
            font-size: 1rem;
        }

        .admin-info .role {
            font-size: 0.85rem;
            color: #6BAF92;
            font-weight: 500;
        }

        .logout-btn {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            color: #4B8B6E;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(232, 197, 71, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232, 197, 71, 0.4);
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding: 40px;
        }

        .page-title {
            font-size: 2.8rem;
            color: #4B8B6E;
            margin-bottom: 8px;
            font-weight: 800;
            animation: fadeInDown 0.6s ease;
        }

        .page-title i {
            color: #E8C547;
        }

        .page-subtitle {
            color: #6BAF92;
            font-size: 1.15rem;
            margin-bottom: 40px;
            font-weight: 500;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(107, 175, 146, 0.2);
            animation: fadeInUp 0.6s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .stat-card.students::before { background: linear-gradient(90deg, #4B8B6E, #6BAF92); }
        .stat-card.topics::before { background: linear-gradient(90deg, #E8C547, #F4D77C); }
        .stat-card.activities::before { background: linear-gradient(90deg, #6BAF92, #4B8B6E); }
        .stat-card.assessments::before { background: linear-gradient(90deg, #F4D77C, #E8C547); }
        .stat-card.feedback::before { background: linear-gradient(90deg, #4B8B6E, #E8C547); }
        .stat-card.pending::before { background: linear-gradient(90deg, #E8C547, #6BAF92); }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(75, 139, 110, 0.25);
            border-color: #4B8B6E;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-icon {
            font-size: 2.5rem;
        }

        .stat-icon.students { color: #4B8B6E; }
        .stat-icon.topics { color: #E8C547; }
        .stat-icon.activities { color: #6BAF92; }
        .stat-icon.assessments { color: #F4D77C; }
        .stat-icon.feedback { color: #4B8B6E; }
        .stat-icon.pending { color: #E8C547; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #6BAF92;
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(75, 139, 110, 0.15);
            text-decoration: none;
            color: #4B8B6E;
            transition: all 0.3s ease;
            text-align: center;
            border: 2px solid rgba(107, 175, 146, 0.2);
            font-weight: 600;
        }

        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(75, 139, 110, 0.25);
            border-color: #4B8B6E;
        }

        .action-btn i {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
            transition: transform 0.3s ease;
        }

        .action-btn:hover i {
            transform: scale(1.1);
        }

        .action-btn.manage-students i { 
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .action-btn.manage-materials i { 
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .action-btn.view-progress i { 
            background: linear-gradient(135deg, #6BAF92, #4B8B6E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .action-btn.system-settings i { 
            background: linear-gradient(135deg, #4B8B6E, #E8C547);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .action-btn.manage-feedback i { 
            background: linear-gradient(135deg, #F4D77C, #E8C547);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .action-btn.view-reports i { 
            background: linear-gradient(135deg, #6BAF92, #E8C547);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(107, 175, 146, 0.2);
        }

        .card-title {
            font-size: 1.4rem;
            color: #4B8B6E;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: #E8C547;
        }
        .card-title i {
            color: #E8C547;
        }

        /* Recent Activities */
        .activity-list {
            max-height: 450px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .activity-list::-webkit-scrollbar {
            width: 6px;
        }

        .activity-list::-webkit-scrollbar-track {
            background: rgba(107, 175, 146, 0.1);
            border-radius: 10px;
        }

        .activity-list::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            border-radius: 10px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid rgba(107, 175, 146, 0.1);
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .activity-item:hover {
            background: rgba(107, 175, 146, 0.05);
            transform: translateX(5px);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .activity-icon.assessment {
            background: rgba(232, 197, 71, 0.15);
            color: #E8C547;
        }

        .activity-icon.activity {
            background: rgba(107, 175, 146, 0.15);
            color: #6BAF92;
        }

        .activity-icon.video {
            background: rgba(75, 139, 110, 0.15);
            color: #4B8B6E;
        }

        .activity-content {
            flex: 1;
        }

        .activity-description {
            font-size: 0.95rem;
            color: #4B8B6E;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #6BAF92;
            font-weight: 500;
        }

        /* Top Students */
        .student-list {
            list-style: none;
        }

        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(107, 175, 146, 0.1);
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .student-item:hover {
            background: rgba(107, 175, 146, 0.05);
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 700;
            color: #4B8B6E;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .student-email {
            font-size: 0.85rem;
            color: #6BAF92;
            font-weight: 500;
        }

        .student-score {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            color: #4B8B6E;
            padding: 6px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 3px 10px rgba(232, 197, 71, 0.3);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
                margin-top: 90px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            }

            .header-right {
                gap: 10px;
            }

            .admin-info {
                display: none;
            }

            .page-title {
                font-size: 2rem;
            }

            .action-btn {
                padding: 20px 15px;
            }

            .action-btn i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="header-left">
            <img src="LOGO.png" alt="SkillSync">
            <h1><i class="fas fa-shield-alt"></i> SkillSync Admin</h1>
        </div>
        <div class="header-right">
            <div class="admin-info">
                <div class="name"><?= htmlspecialchars($admin['full_name']) ?></div>
                <div class="role"><?= ucfirst(str_replace('_', ' ', $admin['role'])) ?></div>
            </div>
            <a href="admin_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Welcome back, <?= htmlspecialchars($admin['full_name']) ?>! 👋</h1>
        <p class="page-subtitle">Here's what's happening in your SkillSync platform</p>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card students">
                <div class="stat-header">
                    <i class="fas fa-users stat-icon students"></i>
                    <div class="stat-value"><?= $totalStudents ?></div>
                </div>
                <div class="stat-label">Total Students</div>
            </div>

            <div class="stat-card topics">
                <div class="stat-header">
                    <i class="fas fa-list stat-icon topics"></i>
                    <div class="stat-value"><?= $totalTopics ?></div>
                </div>
                <div class="stat-label">Learning Topics</div>
            </div>

            <div class="stat-card activities">
                <div class="stat-header">
                    <i class="fas fa-check-circle stat-icon activities"></i>
                    <div class="stat-value"><?= $totalActivities ?></div>
                </div>
                <div class="stat-label">Activities Completed</div>
            </div>

            <div class="stat-card assessments">
                <div class="stat-header">
                    <i class="fas fa-clipboard-check stat-icon assessments"></i>
                    <div class="stat-value"><?= $totalAssessments ?></div>
                </div>
                <div class="stat-label">Assessments Taken</div>
            </div>

            <div class="stat-card feedback">
                <div class="stat-header">
                    <i class="fas fa-comments stat-icon feedback"></i>
                    <div class="stat-value"><?= $totalFeedback ?></div>
                </div>
                <div class="stat-label">Total Feedback</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-header">
                    <i class="fas fa-clock stat-icon pending"></i>
                    <div class="stat-value"><?= $pendingFeedback ?></div>
                </div>
                <div class="stat-label">Pending Feedback</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="admin_students.php" class="action-btn manage-students">
                <i class="fas fa-users"></i>
                <div>Manage Students</div>
            </a>
            <a href="admin_materials.php" class="action-btn manage-materials">
                <i class="fas fa-book-open"></i>
                <div>Manage Materials</div>
            </a>
            <a href="admin_progress.php" class="action-btn view-progress">
                <i class="fas fa-chart-line"></i>
                <div>View Progress</div>
            </a>
            <a href="admin_feedback.php" class="action-btn manage-feedback">
                <i class="fas fa-comments"></i>
                <div>Manage Feedback</div>
            </a>
            <a href="admin_settings.php" class="action-btn system-settings">
                <i class="fas fa-cogs"></i>
                <div>System Settings</div>
            </a>
            <a href="admin_reports.php" class="action-btn view-reports">
                <i class="fas fa-chart-bar"></i>
                <div>View Reports</div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activities -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Recent Student Activities</h3>
                    <a href="admin_activity_logs.php" style="color: #4B8B6E; text-decoration: none; font-size: 0.9rem; font-weight: 600;">View All →</a>
                </div>
                <div class="activity-list">
                    <?php if (empty($recentActivities)): ?>
                    <div style="text-align: center; color: #6c757d; padding: 20px;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No recent activities</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $activity['type'] ?>">
                            <?php if ($activity['type'] === 'assessment'): ?>
                                <i class="fas fa-clipboard-check"></i>
                            <?php elseif ($activity['type'] === 'activity'): ?>
                                <i class="fas fa-tasks"></i>
                            <?php else: ?>
                                <i class="fas fa-play-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-description"><?= htmlspecialchars($activity['description']) ?></div>
                            <div class="activity-time"><?= date('M j, Y g:i A', strtotime($activity['activity_date'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Performing Students -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy"></i> Top Students</h3>
                    <a href="admin_students.php" style="color: #27ae60; text-decoration: none; font-size: 0.9rem;">View All</a>
                </div>
                <?php if (empty($topStudents)): ?>
                <div style="text-align: center; color: #6c757d; padding: 20px;">
                    <i class="fas fa-user-graduate" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No student data available</p>
                </div>
                <?php else: ?>
                <ul class="student-list">
                    <?php foreach ($topStudents as $student): ?>
                    <li class="student-item">
                        <div class="student-info">
                            <div class="student-name"><?= htmlspecialchars($student['username']) ?></div>
                            <div class="student-email"><?= htmlspecialchars($student['email']) ?></div>
                        </div>
                        <div class="student-score"><?= round($student['avg_score'], 1) ?>%</div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>