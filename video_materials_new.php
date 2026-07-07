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

// Get user info with assessment data
$userQuery = "SELECT lc.username, lc.email, s.assessment_data 
              FROM login_credentials lc 
              LEFT JOIN students s ON lc.id = s.id 
              WHERE lc.id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// === PERFORMANCE ANALYSIS ===
// 1. Quiz Performance (Pre-test + Post-test)
$assessment_data = json_decode($user['assessment_data'] ?? '{}', true);
$quiz_scores = [];
$total_quiz_score = 0;
$quiz_count = 0;

// Get pre-test scores
if (!empty($assessment_data)) {
    foreach ($assessment_data as $subject_id => $subject_data) {
        if (isset($subject_data['topics']) && is_array($subject_data['topics'])) {
            foreach ($subject_data['topics'] as $topic) {
                if (isset($topic['score'])) {
                    $quiz_scores[] = floatval($topic['score']);
                    $total_quiz_score += floatval($topic['score']);
                    $quiz_count++;
                }
            }
        }
    }
}

// Get post-test scores (weighted with activities)
$post_test_query = "SELECT AVG(score) as avg_score, COUNT(*) as count FROM user_post_test_attempts WHERE user_id = ? AND completed_at IS NOT NULL";
$post_test_stmt = $conn->prepare($post_test_query);
$post_test_stmt->bind_param("i", $student_id);
$post_test_stmt->execute();
$post_test_result = $post_test_stmt->get_result()->fetch_assoc();
$post_test_stmt->close();

$avg_quiz_score = $quiz_count > 0 ? ($total_quiz_score / $quiz_count) : 0;
// If user has completed post-tests, use that average instead
if ($post_test_result['count'] > 0 && $post_test_result['avg_score']) {
    $avg_quiz_score = floatval($post_test_result['avg_score']);
}

// 2. Hands-on Activities Performance
$activity_query = "SELECT AVG(score) as avg_score, COUNT(*) as count FROM save_progress WHERE user_id = ?";
$activity_stmt = $conn->prepare($activity_query);
$activity_stmt->bind_param("i", $student_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result()->fetch_assoc();
$activity_stmt->close();
$avg_activity_score = floatval($activity_result['avg_score'] ?? 0);

// 3. Simulation/Coding Practice Performance
$simulation_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM user_coding_progression WHERE user_id = ?";
$simulation_stmt = $conn->prepare($simulation_query);
$simulation_stmt->bind_param("i", $student_id);
$simulation_stmt->execute();
$simulation_result = $simulation_stmt->get_result()->fetch_assoc();
$simulation_stmt->close();

$avg_simulation_score = $simulation_result['total'] > 0 
    ? ($simulation_result['completed'] / $simulation_result['total']) * 100 
    : 0;

// === GENERATE PERSONALIZED RECOMMENDATIONS ===
$recommendations = [];

// Quiz Performance Check
if ($avg_quiz_score < 60) {
    $recommendations[] = [
        'icon' => 'book',
        'title' => 'Strengthen Conceptual Understanding',
        'category' => 'Quiz Performance',
        'current' => round($avg_quiz_score),
        'status' => 'needs-attention',
        'description' => 'Your quiz scores indicate opportunities for deeper conceptual learning. Focus on understanding core OOP principles through structured reading and video content.',
        'action_plan' => [
            'Review foundational concepts through video tutorials',
            'Complete reading materials for weak topic areas',
            'Practice with conceptual quizzes after each module',
            'Create summary notes of key concepts'
        ],
        'resources' => [
            ['type' => 'video', 'text' => 'Watch Video Tutorials', 'filter' => 'type=video'],
            ['type' => 'pdf', 'text' => 'Read PDF Modules', 'filter' => 'type=pdf']
        ]
    ];
}

// Activity Performance Check
if ($avg_activity_score < 60) {
    $recommendations[] = [
        'icon' => 'code',
        'title' => 'Build Practical Coding Skills',
        'category' => 'Hands-on Practice',
        'current' => round($avg_activity_score),
        'status' => 'needs-attention',
        'description' => 'Enhance your practical skills by completing more hands-on coding activities. Practice implementing OOP concepts in real code scenarios.',
        'action_plan' => [
            'Complete all available coding activities',
            'Review activity solutions and best practices',
            'Experiment with code variations',
            'Focus on understanding syntax and structure'
        ],
        'resources' => [
            ['type' => 'activity', 'text' => 'Practice Activities', 'url' => 'Activity/activity_list.php'],
            ['type' => 'simulation', 'text' => 'Try Code Simulations', 'filter' => 'type=simulation']
        ]
    ];
}

// Simulation Performance Check
if ($avg_simulation_score < 60) {
    $recommendations[] = [
        'icon' => 'puzzle-piece',
        'title' => 'Improve Problem-Solving Skills',
        'category' => 'Code Simulations',
        'current' => round($avg_simulation_score),
        'status' => 'needs-attention',
        'description' => 'Strengthen your problem-solving abilities by working through interactive coding simulations and challenges.',
        'action_plan' => [
            'Complete coding practice challenges',
            'Work through simulation exercises systematically',
            'Analyze problem patterns and solutions',
            'Practice debugging and testing skills'
        ],
        'resources' => [
            ['type' => 'simulation', 'text' => 'Start Simulations', 'filter' => 'type=simulation'],
            ['type' => 'coding', 'text' => 'Coding Practice', 'url' => 'coding_practice.php']
        ]
    ];
}

// If all areas are strong (60%+), add a maintenance recommendation
if (empty($recommendations)) {
    $recommendations[] = [
        'icon' => 'star',
        'title' => 'Continue Your Excellence',
        'category' => 'Overall Performance',
        'current' => round(($avg_quiz_score + $avg_activity_score + $avg_simulation_score) / 3),
        'status' => 'excellent',
        'description' => 'Great work! You\'re performing well across all areas. Continue practicing to maintain and further improve your skills.',
        'action_plan' => [
            'Explore advanced topics and materials',
            'Challenge yourself with complex problems',
            'Help peers understand difficult concepts',
            'Apply learning to real-world projects'
        ],
        'resources' => [
            ['type' => 'video', 'text' => 'Advanced Videos', 'filter' => 'type=video'],
            ['type' => 'coding', 'text' => 'Advanced Challenges', 'url' => 'coding_practice.php']
        ]
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Personalized Learning - SkillSync</title>
<link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }

/* Sidebar Styles */
.sidebar { width: 240px; background: #fff; border-right: 1px solid #e0e0e0; height: 100vh; padding: 20px 0; position: fixed; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 2px 0 10px rgba(0,0,0,0.1); overflow-y: auto; }
.sidebar-content a { display: flex; align-items: center; gap: 10px; color: #2c3e50; padding: 12px 20px; text-decoration: none; font-weight: 500; transition: all 0.3s; }
.sidebar-content a:hover, .sidebar-content a.active { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 0 25px 25px 0; margin-right: 10px; }
.sidebar .logo { text-align: center; margin-bottom: 20px; }
.sidebar .logo img { width: 50px; height: 50px; border-radius: 50%; }
.sidebar .logo h2 { font-size: 18px; color: #27ae60; margin-top: 10px; }
.student-info { text-align: center; padding: 20px; font-size: 14px; border-top: 1px solid #eee; }
.student-info img { width: 40px; height: 40px; border-radius: 50%; margin-bottom: 5px; }

/* Main Content */
.main-content { margin-left: 240px; padding: 40px; width: calc(100% - 240px); max-width: 1400px; }

/* Personalized Header */
.recommendations-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    margin-bottom: 40px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}

.recommendations-header .header-icon {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: 0.9;
}

.recommendations-header h1 {
    font-size: 2.5rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.recommendations-header p {
    font-size: 1.1rem;
    opacity: 0.95;
    line-height: 1.6;
}

/* Performance Stats Bar */
.performance-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-box {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-box .stat-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 8px;
}

.stat-box.quiz .stat-value { color: #667eea; }
.stat-box.activity .stat-value { color: #3498db; }
.stat-box.simulation .stat-value { color: #1abc9c; }

.stat-box .stat-label {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Recommendation Cards */
.recommendations-grid {
    display: grid;
    gap: 30px;
}

.recommendation-card {
    background: white;
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.recommendation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 45px rgba(0,0,0,0.12);
}

.recommendation-card-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 2px solid #f0f0f0;
}

.recommendation-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
}

.recommendation-icon.needs-attention {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.recommendation-icon.excellent {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
}

.recommendation-header-text {
    flex: 1;
}

.recommendation-header-text h2 {
    color: #2c3e50;
    font-size: 1.6rem;
    margin-bottom: 10px;
    font-weight: 600;
}

.performance-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.performance-badge.needs-attention {
    background: #fee;
    color: #e74c3c;
}

.performance-badge.excellent {
    background: #e8f8f5;
    color: #27ae60;
}

.performance-category {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.recommendation-description {
    color: #555;
    line-height: 1.7;
    margin-bottom: 25px;
    font-size: 1.05rem;
}

.action-plan-section {
    margin-bottom: 25px;
}

.action-plan-section h3 {
    color: #2c3e50;
    font-size: 1.2rem;
    margin-bottom: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-plan-section h3 i {
    color: #667eea;
}

.action-plan-section ul {
    list-style: none;
    padding: 0;
}

.action-plan-section li {
    padding: 12px 0 12px 30px;
    color: #555;
    line-height: 1.6;
    position: relative;
    border-left: 3px solid #e9ecef;
    margin-left: 10px;
}

.action-plan-section li:before {
    content: '✓';
    position: absolute;
    left: -13px;
    top: 10px;
    background: #667eea;
    color: white;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.resources-section h3 {
    color: #2c3e50;
    font-size: 1.2rem;
    margin-bottom: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.resources-section h3 i {
    color: #667eea;
}

.resources-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.resource-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    color: white;
}

.resource-btn.video {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.resource-btn.pdf {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.resource-btn.simulation {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.resource-btn.activity {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.resource-btn.coding {
    background: linear-gradient(135deg, #1abc9c, #16a085);
}

.resource-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main-content { margin-left: 0; width: 100%; padding: 20px; }
    .recommendations-header { padding: 30px 25px; }
    .recommendations-header h1 { font-size: 1.8rem; }
    .recommendation-card { padding: 25px; }
    .resources-buttons { flex-direction: column; }
    .resource-btn { width: 100%; justify-content: center; }
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
            <div><small><?= htmlspecialchars($user['email'] ?? 'student@email.com') ?></small></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Personalized Header -->
        <div class="recommendations-header">
            <div class="header-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <h1>Personalized Learning Recommendations</h1>
            <p>Based on your performance patterns, here's your customized learning strategy:</p>
        </div>

        <!-- Performance Stats -->
        <div class="performance-stats">
            <div class="stat-box quiz">
                <div class="stat-value"><?= round($avg_quiz_score) ?>%</div>
                <div class="stat-label">Quiz Performance</div>
            </div>
            <div class="stat-box activity">
                <div class="stat-value"><?= round($avg_activity_score) ?>%</div>
                <div class="stat-label">Hands-on Activities</div>
            </div>
            <div class="stat-box simulation">
                <div class="stat-value"><?= round($avg_simulation_score) ?>%</div>
                <div class="stat-label">Code Simulations</div>
            </div>
        </div>

        <!-- Recommendations Grid -->
        <div class="recommendations-grid">
            <?php foreach ($recommendations as $rec): ?>
                <div class="recommendation-card">
                    <div class="recommendation-card-header">
                        <div class="recommendation-icon <?= $rec['status'] ?>">
                            <i class="fas fa-<?= $rec['icon'] ?>"></i>
                        </div>
                        <div class="recommendation-header-text">
                            <h2><?= htmlspecialchars($rec['title']) ?></h2>
                            <div class="performance-badge <?= $rec['status'] ?>">
                                Current: <?= $rec['current'] ?>%
                            </div>
                            <div class="performance-category"><?= htmlspecialchars($rec['category']) ?></div>
                        </div>
                    </div>

                    <div class="recommendation-description">
                        <?= htmlspecialchars($rec['description']) ?>
                    </div>

                    <div class="action-plan-section">
                        <h3><i class="fas fa-tasks"></i> Action Plan</h3>
                        <ul>
                            <?php foreach ($rec['action_plan'] as $action): ?>
                                <li><?= htmlspecialchars($action) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="resources-section">
                        <h3><i class="fas fa-book-reader"></i> Recommended Resources</h3>
                        <div class="resources-buttons">
                            <?php foreach ($rec['resources'] as $resource): ?>
                                <a href="<?= isset($resource['url']) ? htmlspecialchars($resource['url']) : 'video_materials.php?' . $resource['filter'] ?>" 
                                   class="resource-btn <?= $resource['type'] ?>">
                                    <?php if ($resource['type'] == 'video'): ?>
                                        <i class="fas fa-play"></i>
                                    <?php elseif ($resource['type'] == 'pdf'): ?>
                                        <i class="fas fa-file-pdf"></i>
                                    <?php elseif ($resource['type'] == 'simulation'): ?>
                                        <i class="fas fa-code"></i>
                                    <?php elseif ($resource['type'] == 'activity'): ?>
                                        <i class="fas fa-tasks"></i>
                                    <?php elseif ($resource['type'] == 'coding'): ?>
                                        <i class="fas fa-laptop-code"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($resource['text']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
