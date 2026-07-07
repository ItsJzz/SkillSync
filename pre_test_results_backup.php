<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Check if this is from onboarding
$isOnboarding = isset($_GET['onboarding']) || isset($_SESSION['onboarding_pretest']);
if ($isOnboarding) {
    $updateStmt = $conn->prepare("UPDATE login_credentials SET completed_preassessment = 1 WHERE id = ?");
    $updateStmt->bind_param("i", $student_id);
    $updateStmt->execute();
    $updateStmt->close();
    unset($_SESSION['onboarding_pretest']);
}

// Get the latest comprehensive assessment data
$attemptStmt = $conn->prepare("
    SELECT assessment_data, score, attempt_time 
    FROM student_test_attempts 
    WHERE student_id = ? AND test_id = 1 
    ORDER BY attempt_time DESC 
    LIMIT 1
");
$attemptStmt->bind_param("i", $student_id);
$attemptStmt->execute();
$attemptResult = $attemptStmt->get_result();
$attemptData = $attemptResult->fetch_assoc();
$attemptStmt->close();

// Initialize assessment variables
$assessmentData = null;
$isScalableAssessment = false;
$totalScore = 0;
$maxTotalScore = 150; // Default for 5 topics
$overallPercentage = 0;
$classLevel = 'Beginner';
$progressToNext = 0;
$topicDetails = [];

if ($attemptData && !empty($attemptData['assessment_data'])) {
    // New scalable assessment format
    $assessmentData = json_decode($attemptData['assessment_data'], true);
    if ($assessmentData && isset($assessmentData['assessmentType']) && $assessmentData['assessmentType'] === 'scalable_pretest') {
        $isScalableAssessment = true;
        $totalScore = $assessmentData['totalScore'];
        $maxTotalScore = $assessmentData['maxTotalScore'];
        $overallPercentage = $assessmentData['overallPercentage'];
        $topicDetails = $assessmentData['topicDetails'];
        
        // Calculate class level and progress
        $passingScore = 115; // 115/150 for intermediate
        if ($totalScore >= $passingScore) {
            $classLevel = 'Intermediate';
            $progressToNext = 0; // Reset progress for intermediate
        } else {
            $classLevel = 'Beginner';
            $progressToNext = ($totalScore / $passingScore) * 100;
        }
    }
}

if (!$isScalableAssessment) {
    // Fallback to legacy system
    $topicScoresRes = $conn->query("
        SELECT t.id AS topic_id, t.name AS topic_name, st.score, s.name as subject_name
        FROM student_tests st
        JOIN topics t ON st.topic_id = t.id
        JOIN subjects s ON t.subject_id = s.id
        WHERE st.student_id = $student_id
        AND st.test_type='pre'
        ORDER BY t.id
    ");
    
    $topicCount = $topicScoresRes->num_rows;
    $questionsPerTopic = 16; // Default for legacy
    $totalScore = 0;
    $maxTotalScore = $topicCount * ($questionsPerTopic + 10); // Questions + hands-on
    
    while($row = $topicScoresRes->fetch_assoc()) {
        $totalScore += $row['score'];
        $topicDetails[$row['topic_id']] = [
            'topic_name' => $row['topic_name'],
            'totalScore' => $row['score'],
            'maxScore' => $questionsPerTopic + 10,
            'percentage' => round(($row['score'] / ($questionsPerTopic + 10)) * 100, 2)
        ];
    }
    
    $overallPercentage = round(($totalScore / $maxTotalScore) * 100, 2);
    $passingScore = $maxTotalScore * 0.77; // 77% passing
    $classLevel = ($totalScore >= $passingScore) ? 'Intermediate' : 'Beginner';
    $progressToNext = ($classLevel === 'Beginner') ? ($totalScore / $passingScore) * 100 : 0;
}

// Get topic information for recommendations
$topicsRes = $conn->query("
    SELECT t.id, t.name, s.name as subject_name 
    FROM topics t 
    JOIN subjects s ON t.subject_id = s.id 
    WHERE t.id IN (" . implode(',', array_keys($topicDetails)) . ")
    ORDER BY t.id
");

$topics = [];
while($topic = $topicsRes->fetch_assoc()) {
    $topics[$topic['id']] = $topic;
}

// Analyze weak areas and recommendations
$weakAreas = [];
$strongAreas = [];
$overallWeaknesses = ['quiz' => 0, 'simulation' => 0, 'hands_on' => 0];

foreach ($topicDetails as $topic_id => $details) {
    $percentage = $details['percentage'];
    
    if ($percentage < 60) {
        $weakAreas[] = [
            'topic_id' => $topic_id,
            'topic_name' => $topics[$topic_id]['name'] ?? 'Unknown Topic',
            'percentage' => $percentage,
            'details' => $details
        ];
    } elseif ($percentage >= 80) {
        $strongAreas[] = [
            'topic_id' => $topic_id,
            'topic_name' => $topics[$topic_id]['name'] ?? 'Unknown Topic', 
            'percentage' => $percentage,
            'details' => $details
        ];
    }
    
    // Analyze specific skill weaknesses (for scalable assessment)
    if ($isScalableAssessment) {
        $questionScore = $details['questionScore'] ?? 0;
        $handsOnScore = $details['handsOnScore'] ?? 0;
        $maxQuestionScore = 20; // From scoring system
        $maxHandsOnScore = $details['maxScore'] - $maxQuestionScore;
        
        if (($questionScore / $maxQuestionScore) < 0.6) {
            $overallWeaknesses['quiz']++;
            $overallWeaknesses['simulation']++;
        }
        if (($handsOnScore / $maxHandsOnScore) < 0.6) {
            $overallWeaknesses['hands_on']++;
        }
    }
}

// Generate learning path recommendations
$learningPath = [];
if ($classLevel === 'Beginner') {
    $learningPath = [
        [
            'title' => 'Foundation Building',
            'description' => 'Focus on understanding basic concepts and terminology',
            'icon' => 'fas fa-foundation',
            'priority' => 'high'
        ],
        [
            'title' => 'Practice Quiz Questions',
            'description' => 'Strengthen theoretical knowledge through quiz practice',
            'icon' => 'fas fa-question-circle',
            'priority' => 'high'
        ],
        [
            'title' => 'Hands-on Coding',
            'description' => 'Start with beginner-level coding exercises',
            'icon' => 'fas fa-code',
            'priority' => 'medium'
        ]
    ];
} else {
    $learningPath = [
        [
            'title' => 'Advanced Concepts',
            'description' => 'Dive deeper into complex topics and applications',
            'icon' => 'fas fa-graduation-cap',
            'priority' => 'high'
        ],
        [
            'title' => 'Real-world Projects',
            'description' => 'Apply knowledge through practical projects',
            'icon' => 'fas fa-project-diagram',
            'priority' => 'high'
        ],
        [
            'title' => 'Simulation Mastery',
            'description' => 'Excel in simulation-based problem solving',
            'icon' => 'fas fa-flask',
            'priority' => 'medium'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Assessment Results - SkillSync</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        /* Header Section */
        .header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.3;
        }
        .header-content { position: relative; z-index: 1; }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; font-weight: 700; }
        .header .subtitle { font-size: 1.2rem; opacity: 0.9; margin-bottom: 30px; }
        
        /* Score Display */
        .score-display {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 40px;
            align-items: center;
            margin-top: 20px;
        }
        .total-score {
            text-align: center;
        }
        .score-number {
            font-size: 4rem;
            font-weight: bold;
            line-height: 1;
        }
        .score-label {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        /* Class Level Display */
        .class-level {
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .class-badge {
            display: inline-block;
            background: rgba(255,255,255,0.9);
            color: #27ae60;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .progress-container {
            margin-top: 20px;
        }
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            position: relative;
        }
        .progress-ring svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        .progress-ring circle {
            fill: none;
            stroke-width: 8;
        }
        .progress-ring .progress-bg {
            stroke: rgba(255,255,255,0.3);
        }
        .progress-ring .progress-bar {
            stroke: #fff;
            stroke-linecap: round;
            transition: stroke-dasharray 0.6s ease;
        }
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }
        .progress-label {
            text-align: center;
            margin-top: 15px;
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Content Sections */
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .section-title i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        /* Performance Analysis */
        .performance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .chart-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Topic Performance */
        .topic-list {
            display: grid;
            gap: 15px;
        }
        .topic-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid #e74c3c;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .topic-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .topic-item.excellent { border-left-color: #27ae60; }
        .topic-item.good { border-left-color: #f39c12; }
        .topic-item.needs-work { border-left-color: #e74c3c; }
        
        .topic-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        .topic-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
        }
        .topic-percentage {
            font-size: 1.3rem;
            font-weight: bold;
        }
        .topic-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .breakdown-item {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .breakdown-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        /* Weakness Analysis */
        .weakness-analysis {
            background: linear-gradient(135deg, #ff7675, #fd79a8);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .strength-analysis {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .analysis-title {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .analysis-item {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 15px;
            backdrop-filter: blur(10px);
        }
        
        /* Learning Path */
        .learning-path {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 30px;
        }
        .path-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .path-step {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .path-step:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px);
        }
        .step-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .step-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .step-description {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        /* Action Buttons */
        .action-section {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .score-display { grid-template-columns: 1fr; gap: 20px; }
            .performance-grid { grid-template-columns: 1fr; }
            .path-steps { grid-template-columns: 1fr; }
            .analysis-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Score and Class Level -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> Pre-Assessment Results</h1>
                <p class="subtitle">Your comprehensive skill evaluation and learning roadmap</p>
                
                <div class="score-display">
                    <div class="total-score">
                        <div class="score-number"><?= $totalScore ?></div>
                        <div class="score-label">Total Score</div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">out of <?= $maxTotalScore ?> points</div>
                    </div>
                    
                    <div class="class-level">
                        <div class="class-badge">
                            <i class="fas fa-<?= $classLevel === 'Intermediate' ? 'star' : 'seedling' ?>"></i>
                            <?= $classLevel ?> Level
                        </div>
                        
                        <?php if ($classLevel === 'Beginner'): ?>
                        <div class="progress-container">
                            <div class="progress-ring">
                                <svg>
                                    <circle class="progress-bg" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress-bar" cx="60" cy="60" r="54" 
                                            stroke-dasharray="<?= $progressToNext * 3.39 ?> 339"
                                            stroke-dashoffset="0"></circle>
                                </svg>
                                <div class="progress-text"><?= round($progressToNext) ?>%</div>
                            </div>
                            <div class="progress-label">Progress to Intermediate</div>
                            <div style="font-size: 0.9rem; opacity: 0.8; margin-top: 10px;">
                                Need <?= $maxTotalScore * 0.77 - $totalScore ?> more points (115/150 required)
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="progress-container">
                            <div class="progress-ring">
                                <svg>
                                    <circle class="progress-bg" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress-bar" cx="60" cy="60" r="54" 
                                            stroke-dasharray="0 339"></circle>
                                </svg>
                                <div class="progress-text">0%</div>
                            </div>
                            <div class="progress-label">Intermediate Level - Start Fresh!</div>
                            <div style="font-size: 0.9rem; opacity: 0.8; margin-top: 10px;">
                                Ready for advanced challenges
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="total-score">
                        <div class="score-number"><?= round($overallPercentage) ?>%</div>
                        <div class="score-label">Overall Performance</div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">
                            <?= $overallPercentage >= 77 ? 'Excellent!' : ($overallPercentage >= 60 ? 'Good Progress' : 'Needs Improvement') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
.nav-step:hover { background: rgba(255,255,255,0.25); transform: translateY(-3px); }
.nav-step-icon { font-size: 2.5rem; margin-bottom: 15px; display: block; }
.nav-step-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 10px; }
.nav-step-desc { font-size: 0.95rem; opacity: 0.9; line-height: 1.4; }
.comprehensive-recommendations { margin: 40px 0; }
.rec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; }
.course-card { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border-left: 5px solid #e74c3c; transition: all 0.3s ease; }
.course-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
.course-card.strong { border-left-color: #27ae60; }
.course-card.average { border-left-color: #f39c12; }
.course-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
.course-icon { font-size: 2rem; }
.course-icon.weak { color: #e74c3c; }
.course-icon.strong { color: #27ae60; }
.course-icon.average { color: #f39c12; }
.course-title { font-size: 1.3rem; font-weight: bold; color: #2c3e50; }
.course-score { font-size: 0.9rem; margin-top: 5px; }
.course-actions { margin: 20px 0; }
.action-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; transition: all 0.3s ease; }
.action-item:hover { background: #e9ecef; }
.action-icon { font-size: 1.1rem; color: #6c757d; }
.action-text { font-weight: 500; color: #495057; }
.recommendations-summary { background: linear-gradient(135deg, #3498db, #2ecc71); color: white; padding: 30px; border-radius: 20px; margin: 30px 0; text-align: center; }
.summary-title { font-size: 1.6rem; margin-bottom: 15px; }
.summary-stats { display: flex; justify-content: center; gap: 40px; margin: 20px 0; flex-wrap: wrap; }
.stat { text-align: center; }
.stat-number { font-size: 2rem; font-weight: bold; display: block; }
.stat-label { font-size: 0.9rem; opacity: 0.9; }
</style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <div class="header">
        <h1><i class="fas fa-graduation-cap"></i> Your Learning Assessment Complete!</h1>
        <div class="overall-score">
            Overall Score: <?= $totalCorrect ?>/<?= $totalAvailable ?> (<?= $totalPercentage ?>%)
        </div>
        <div class="progress-indicator">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $totalPercentage ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Assessment Summary -->
    <div class="recommendations-summary">
        <div class="summary-title"><i class="fas fa-trophy"></i> Your Personalized Learning Path Awaits!</div>
        <p style="font-size: 1.1rem; margin: 15px 0;">Based on your assessment, we've created a customized roadmap for your success.</p>
        <div class="summary-stats">
            <?php 
            $weakCount = count(array_filter($topicScores, fn($t) => $t['percentage'] < 60));
            $strongCount = count(array_filter($topicScores, fn($t) => $t['percentage'] >= 80));
            $averageCount = count(array_filter($topicScores, fn($t) => $t['percentage'] >= 60 && $t['percentage'] < 80));
            ?>
            <div class="stat">
                <span class="stat-number"><?= $strongCount ?></span>
                <span class="stat-label">Strong Areas</span>
            </div>
            <div class="stat">
                <span class="stat-number"><?= $averageCount ?></span>
                <span class="stat-label">Good Progress</span>
            </div>
            <div class="stat">
                <span class="stat-number"><?= $weakCount ?></span>
                <span class="stat-label">Focus Areas</span>
            </div>
        </div>
    </div>

    <!-- Navigation Guide -->
    <div class="navigation-guide">
        <div class="nav-title"><i class="fas fa-map-marked-alt"></i> Your Next Steps in SkillSync</div>
        <p style="text-align: center; font-size: 1.1rem; margin-bottom: 20px;">Here's how to make the most of your learning journey:</p>
        
        <div class="nav-steps">
            <div class="nav-step" onclick="window.location.href='Enhancement.php'">
                <i class="fas fa-tools nav-step-icon"></i>
                <div class="nav-step-title">Enhancement Process</div>
                <div class="nav-step-desc">Go here to enhance your coding skills through structured learning activities and practice exercises</div>
            </div>
            
            <div class="nav-step" onclick="window.location.href='recommendations.php'">
                <i class="fas fa-lightbulb nav-step-icon"></i>
                <div class="nav-step-title">Recommendations</div>
                <div class="nav-step-desc">Check detailed recommendations for learning materials, videos, and personalized study plans</div>
            </div>
            
            <div class="nav-step" onclick="window.location.href='student_dashboard.php'">
                <i class="fas fa-tachometer-alt nav-step-icon"></i>
                <div class="nav-step-title">Dashboard</div>
                <div class="nav-step-desc">Visit your dashboard for progress visualization, performance tracking, and overall learning analytics</div>
            </div>
        </div>
    </div>

    <!-- Comprehensive Course Recommendations -->
    <div class="comprehensive-recommendations">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 30px;">
            <i class="fas fa-graduation-cap"></i> Your Personalized Course Recommendations
        </h2>
        
        <div class="rec-grid">
            <?php foreach ($topicScores as $topic): ?>
                <?php 
                $cardClass = '';
                $iconClass = '';
                $status = '';
                if ($topic['percentage'] < 60) {
                    $cardClass = 'weak';
                    $iconClass = 'weak';
                    $status = 'Focus Area - Needs Improvement';
                } elseif ($topic['percentage'] >= 80) {
                    $cardClass = 'strong';
                    $iconClass = 'strong';
                    $status = 'Strong Area - Keep It Up!';
                } else {
                    $cardClass = 'average';
                    $iconClass = 'average';
                    $status = 'Good Progress - Room for Growth';
                }
                ?>
                
                <div class="course-card <?= $cardClass ?>">
                    <div class="course-header">
                        <i class="fas fa-book course-icon <?= $iconClass ?>"></i>
                        <div>
                            <div class="course-title"><?= htmlspecialchars($topic['topic_name']) ?></div>
                            <div class="course-score <?= $iconClass ?>"><?= $topic['score'] ?>/<?= $topic['total'] ?> (<?= $topic['percentage'] ?>%) - <?= $status ?></div>
                        </div>
                    </div>
                    
                    <div class="course-actions">
                        <?php if ($topic['percentage'] < 60): ?>
                            <!-- Focus Area Actions -->
                            <div class="action-item" onclick="window.location.href='Enhancement.php'">
                                <i class="fas fa-dumbbell action-icon"></i>
                                <span class="action-text">Start Enhancement Process for skill building</span>
                            </div>
                            <?php if (!empty($topic['materials']['video'])): ?>
                                <div class="action-item" onclick="window.location.href='view_material.php?id=<?= $topic['materials']['video'][0]['id'] ?>'">
                                    <i class="fas fa-play-circle action-icon"></i>
                                    <span class="action-text">Watch foundational videos</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($topic['activities_exist']): ?>
                                <div class="action-item" onclick="window.location.href='Activity/activity_list.php?topic_id=<?= $topic['topic_id'] ?>'">
                                    <i class="fas fa-code action-icon"></i>
                                    <span class="action-text">Practice with coding activities</span>
                                </div>
                            <?php endif; ?>
                            <div class="action-item" onclick="window.location.href='recommendation.php?topic_id=<?= $topic['topic_id'] ?>'">
                                <i class="fas fa-route action-icon"></i>
                                <span class="action-text">Get detailed learning path</span>
                            </div>
                            
                        <?php elseif ($topic['percentage'] >= 80): ?>
                            <!-- Strong Area Actions -->
                            <div class="action-item">
                                <i class="fas fa-star action-icon" style="color: #f39c12;"></i>
                                <span class="action-text">Excellent mastery! Keep practicing</span>
                            </div>
                            <?php if ($topic['activities_exist']): ?>
                                <div class="action-item" onclick="window.location.href='Activity/activity_list.php?topic_id=<?= $topic['topic_id'] ?>'">
                                    <i class="fas fa-trophy action-icon"></i>
                                    <span class="action-text">Try advanced challenges</span>
                                </div>
                            <?php endif; ?>
                            <div class="action-item" onclick="window.location.href='student_dashboard.php'">
                                <i class="fas fa-chart-line action-icon"></i>
                                <span class="action-text">Track progress on dashboard</span>
                            </div>
                            
                        <?php else: ?>
                            <!-- Average Area Actions -->
                            <div class="action-item" onclick="window.location.href='Enhancement.php'">
                                <i class="fas fa-arrow-up action-icon"></i>
                                <span class="action-text">Continue enhancement for mastery</span>
                            </div>
                            <?php if ($topic['activities_exist']): ?>
                                <div class="action-item" onclick="window.location.href='Activity/activity_list.php?topic_id=<?= $topic['topic_id'] ?>'">
                                    <i class="fas fa-tasks action-icon"></i>
                                    <span class="action-text">Practice more activities</span>
                                </div>
                            <?php endif; ?>
                            <div class="action-item" onclick="window.location.href='recommendations.php'">
                                <i class="fas fa-compass action-icon"></i>
                                <span class="action-text">Explore learning materials</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Action Buttons -->
    <div class="action-buttons">
        <?php if ($isOnboarding): ?>
            <button class="btn btn-success" onclick="showOnboardingComplete()">
                <i class="fas fa-rocket"></i> Complete Onboarding & Start Learning!
            </button>

        <?php endif; ?> 
    </div>
</div>

<script>
const ctx = document.getElementById('topicPie').getContext('2d');

let pieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php foreach ($topicScores as $t): ?>
                '<?= htmlspecialchars($t['topic_name']) ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach ($topicScores as $t): ?>
                    <?= $t['percentage'] ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                <?php foreach ($topicScores as $t): ?>
                    '<?= $t['percentage'] >= 80 ? "rgba(39, 174, 96, 0.8)" : ($t['percentage'] >= 60 ? "rgba(243, 156, 18, 0.8)" : "rgba(231, 76, 60, 0.8)") ?>',
                <?php endforeach; ?>
            ],
            borderColor: [
                <?php foreach ($topicScores as $t): ?>
                    '<?= $t['percentage'] >= 80 ? "rgba(39, 174, 96, 1)" : ($t['percentage'] >= 60 ? "rgba(243, 156, 18, 1)" : "rgba(231, 76, 60, 1)") ?>',
                <?php endforeach; ?>
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: "Performance by Topic (%)",
                font: { size: 16, weight: 'bold' }
            },
            legend: {
                position: 'bottom',
                labels: { padding: 20, usePointStyle: true }
            }
        }
    }
});

function resetPie() {
    pieChart.data.datasets[0].data = [<?= $totalCorrect ?>, <?= $totalAvailable - $totalCorrect ?>];
    pieChart.options.plugins.title.text = "Overall Performance (<?= $totalCorrect ?>/<?= $totalAvailable ?>)";
    pieChart.update();
}

<?php if ($isOnboarding): ?>
function showOnboardingComplete() {
    alert('🎉 Congratulations! You have completed your first pre-assessment. SkillSync now knows your skill level and can provide personalized recommendations. Welcome to your learning journey!');
    window.location.href = 'student_dashboard.php';
}
<?php endif; ?>
</script>

</body>
</html>
