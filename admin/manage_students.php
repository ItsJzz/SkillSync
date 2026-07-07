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

// Get all students with their statistics
$studentsQuery = "
    SELECT 
        lc.id,
        lc.username,
        lc.email,
        lc.created_at,
        lc.completed_preassessment,
        COUNT(DISTINCT sas.topic_id) as activities_completed,
        COUNT(DISTINCT st.id) as tests_taken,
        COUNT(DISTINCT svp.id) as videos_watched
    FROM login_credentials lc
    LEFT JOIN student_activity_scores sas ON lc.id = sas.student_id
    LEFT JOIN student_tests st ON lc.id = st.student_id
    LEFT JOIN student_video_progress svp ON lc.id = svp.student_id
    WHERE lc.role = 'student'
    GROUP BY lc.id, lc.username, lc.email, lc.created_at, lc.completed_preassessment
    ORDER BY lc.created_at DESC
";
$students = $conn->query($studentsQuery)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students - SkillSync Admin</title>
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

/* Content Section */
.content-section { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
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

/* Search and Filters */
.search-bar { 
    display: flex; 
    gap: 15px; 
    margin-bottom: 25px; 
}

.search-input { 
    flex: 1; 
    padding: 14px 18px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    font-size: 14px; 
    transition: all 0.3s;
    background: rgba(255, 255, 255, 0.9);
}

.search-input:focus { 
    outline: none; 
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
}

.search-btn { 
    padding: 14px 24px; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
    border: none; 
    border-radius: 12px; 
    cursor: pointer; 
    font-weight: 600; 
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
}

.search-btn:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(75, 139, 110, 0.4);
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

/* Status Badges */
.status-badge { 
    padding: 6px 14px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: 600; 
    text-transform: uppercase; 
}

.status-badge.active { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
}

.status-badge.pending { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
}

.status-badge.low { 
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white; 
}

/* Stats Pills */
.stat-pill { 
    display: inline-block; 
    padding: 6px 12px; 
    border-radius: 12px; 
    font-size: 11px; 
    font-weight: 600; 
    margin: 2px; 
}

.stat-pill.activities { 
    background: rgba(107, 175, 146, 0.15);
    color: #4B8B6E;
    border: 1px solid rgba(107, 175, 146, 0.3);
}

.stat-pill.tests { 
    background: rgba(75, 139, 110, 0.15);
    color: #4B8B6E;
    border: 1px solid rgba(75, 139, 110, 0.3);
}

.stat-pill.videos { 
    background: rgba(232, 197, 71, 0.15);
    color: #4B8B6E;
    border: 1px solid rgba(232, 197, 71, 0.3);
}

/* Action Buttons */
.btn { 
    padding: 8px 16px; 
    border: none; 
    border-radius: 10px; 
    font-size: 12px; 
    font-weight: 600; 
    cursor: pointer; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    transition: all 0.3s; 
    margin: 2px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-primary { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
}

.btn-primary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(75, 139, 110, 0.3);
}

.btn-warning { 
    background: linear-gradient(135deg, #E8C547, #F4D77C);
    color: #4B8B6E;
}

.btn-warning:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(232, 197, 71, 0.3);
}

.btn-danger { 
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white; 
}

.btn-danger:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

/* Student Avatar */
.student-avatar {
    width: 40px; 
    height: 40px; 
    background: linear-gradient(135deg, #6BAF92, #E8C547);
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(107, 175, 146, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-content { margin-left: 0; width: 100%; }
    .search-bar { flex-direction: column; }
    .section-header { flex-direction: column; gap: 15px; align-items: flex-start; }
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
        <a href="manage_students.php" class="active"><i class="fas fa-users"></i> Manage Students</a>
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
        <div class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="admin-role">System Administrator</div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Student Management 👥</h1>
        <p class="page-subtitle">Monitor and manage all registered students</p>
    </div>

    <!-- Students Section -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-users"></i> All Students (<?= count($students) ?>)</h3>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" class="search-input" placeholder="Search students by name or email..." id="searchInput">
            <button class="search-btn"><i class="fas fa-search"></i> Search</button>
        </div>

        <!-- Students Table -->
        <table class="data-table" id="studentsTable">
            <thead>
                <tr>
                    <th>Student Info</th>
                    <th>Status</th>
                    <th>Progress Stats</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 40px;">
                        <i class="fas fa-user-plus" style="font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                        <strong>No students registered yet</strong>
                        <br><small>Students will appear here once they register</small>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="student-avatar">
                                <?= strtoupper(substr($student['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($student['username']) ?></div>
                                <div style="font-size: 12px; color: #7f8c8d;"><?= htmlspecialchars($student['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($student['completed_preassessment']): ?>
                            <span class="status-badge active">Active</span>
                        <?php else: ?>
                            <span class="status-badge pending">Pending Assessment</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <span class="stat-pill activities"><?= $student['activities_completed'] ?> Activities</span>
                            <span class="stat-pill tests"><?= $student['tests_taken'] ?> Tests</span>
                            <span class="stat-pill videos"><?= $student['videos_watched'] ?> Videos</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 14px; color: #2c3e50;"><?= date('M j, Y', strtotime($student['created_at'])) ?></div>
                        <div style="font-size: 11px; color: #7f8c8d;"><?= date('g:i A', strtotime($student['created_at'])) ?></div>
                    </td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <a href="view_student_detail.php?id=<?= $student['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit_student.php?id=<?= $student['id'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="reset_student_progress.php?id=<?= $student['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reset this student\'s progress?')">
                                <i class="fas fa-refresh"></i> Reset
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Simple search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#studentsTable tbody tr');
    
    tableRows.forEach(row => {
        const studentInfo = row.querySelector('td:first-child').textContent.toLowerCase();
        if (studentInfo.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
</body>
</html>