<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get the promotion test attempt (either specific or latest failed)
if (isset($_GET['attempt_id'])) {
    // Specific attempt from history
    $attempt_id = intval($_GET['attempt_id']);
    $stmt = $conn->prepare("
        SELECT * FROM level_promotion_attempts 
        WHERE id = ? AND student_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("ii", $attempt_id, $student_id);
} else {
    // Latest failed attempt (default behavior)
    $stmt = $conn->prepare("
        SELECT * FROM level_promotion_attempts 
        WHERE student_id = ? AND passed = 0 
        ORDER BY attempt_date DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $student_id);
}

$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$attempt) {
    header("Location: student_dashboard.php");
    exit;
}

// If this was a passed test, redirect to dashboard with message
if ($attempt['passed']) {
    header("Location: progress_history.php?message=passed_test");
    exit;
}

// Parse the answers data
$answersData = json_decode($attempt['answers_data'], true);
$score = $attempt['score'];
$currentLevel = $attempt['current_level'];
$targetLevel = $attempt['target_level'];

// Analyze performance by topic and question type
$topicPerformance = [];
$questionTypePerformance = ['Quiz question' => ['correct' => 0, 'total' => 0], 'Simulation question' => ['correct' => 0, 'total' => 0]];
$levelPerformance = [$currentLevel => ['correct' => 0, 'total' => 0], $targetLevel => ['correct' => 0, 'total' => 0]];

foreach ($answersData['details'] as $detail) {
    $topicId = $detail['topic_id'];
    $isCorrect = $detail['is_correct'] ? 1 : 0;
    $questionType = $detail['question_type'];
    $classLevel = $detail['class_level'];
    
    // Topic performance
    if (!isset($topicPerformance[$topicId])) {
        $topicPerformance[$topicId] = ['name' => $detail['topic_name'], 'correct' => 0, 'total' => 0];
    }
    $topicPerformance[$topicId]['correct'] += $isCorrect;
    $topicPerformance[$topicId]['total']++;
    
    // Question type performance
    $questionTypePerformance[$questionType]['correct'] += $isCorrect;
    $questionTypePerformance[$questionType]['total']++;
    
    // Level performance
    $levelPerformance[$classLevel]['correct'] += $isCorrect;
    $levelPerformance[$classLevel]['total']++;
}

// Calculate percentages
foreach ($topicPerformance as &$topic) {
    $topic['percentage'] = ($topic['total'] > 0) ? ($topic['correct'] / $topic['total']) * 100 : 0;
}
foreach ($questionTypePerformance as &$type) {
    $type['percentage'] = ($type['total'] > 0) ? ($type['correct'] / $type['total']) * 100 : 0;
}
foreach ($levelPerformance as &$level) {
    $level['percentage'] = ($level['total'] > 0) ? ($level['correct'] / $level['total']) * 100 : 0;
}

// Determine weak areas
$weakTopics = array_filter($topicPerformance, fn($t) => $t['percentage'] < 60);
$weakQuestionTypes = array_filter($questionTypePerformance, fn($t) => $t['percentage'] < 60);
$weakLevels = array_filter($levelPerformance, fn($l) => $l['percentage'] < 60);

// Generate recommendations based on analysis
$recommendations = [];

// 1. Level-specific recommendations
if ($levelPerformance[$targetLevel]['percentage'] < $levelPerformance[$currentLevel]['percentage']) {
    $recommendations[] = [
        'icon' => 'fa-layer-group',
        'title' => 'Focus on ' . $targetLevel . ' Level Content',
        'description' => "You scored " . round($levelPerformance[$targetLevel]['percentage'], 1) . "% on $targetLevel questions vs " . round($levelPerformance[$currentLevel]['percentage'], 1) . "% on $currentLevel questions.",
        'actions' => [
            "Study $targetLevel level materials thoroughly",
            "Practice more complex problems",
            "Review advanced concepts in each topic"
        ],
        'priority' => 'high'
    ];
}

// 2. Question type recommendations
if ($questionTypePerformance['Quiz question']['percentage'] < 60) {
    $recommendations[] = [
        'icon' => 'fa-book-open',
        'title' => 'Strengthen Theoretical Understanding',
        'description' => "Quiz questions: " . round($questionTypePerformance['Quiz question']['percentage'], 1) . "% correct",
        'actions' => [
            "Review learning materials and documentation",
            "Create concept maps to connect ideas",
            "Practice explaining concepts in your own words",
            "Take practice quizzes regularly"
        ],
        'priority' => 'high'
    ];
}

if ($questionTypePerformance['Simulation question']['percentage'] < 60) {
    $recommendations[] = [
        'icon' => 'fa-code',
        'title' => 'Improve Problem-Solving Skills',
        'description' => "Simulation questions: " . round($questionTypePerformance['Simulation question']['percentage'], 1) . "% correct",
        'actions' => [
            "Watch video tutorials with code examples",
            "Practice in the simulation playground",
            "Trace through code step-by-step",
            "Work on more coding challenges"
        ],
        'priority' => 'high'
    ];
}

// 3. Topic-specific recommendations
usort($weakTopics, fn($a, $b) => $a['percentage'] <=> $b['percentage']);
foreach (array_slice($weakTopics, 0, 3) as $topic) {
    $recommendations[] = [
        'icon' => 'fa-bullseye',
        'title' => 'Master: ' . $topic['name'],
        'description' => "Performance: " . round($topic['percentage'], 1) . "% (" . $topic['correct'] . "/" . $topic['total'] . " correct)",
        'actions' => [
            "Review {$topic['name']} learning materials",
            "Watch tutorial videos on {$topic['name']}",
            "Complete hands-on activities for this topic",
            "Take post-test to verify understanding"
        ],
        'priority' => $topic['percentage'] < 40 ? 'critical' : 'medium'
    ];
}

// 4. General study recommendations
$recommendations[] = [
    'icon' => 'fa-graduation-cap',
    'title' => 'Study Strategy',
    'description' => "Recommended approach to reach 77% passing score",
    'actions' => [
        "Set aside 2-3 hours daily for focused study",
        "Follow the topic-specific paths above in order",
        "Test yourself with practice questions after each topic",
        "Review all weak areas before retaking the test"
    ],
    'priority' => 'medium'
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion Test Analysis - SkillSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .container {
            max-width: 1200px;
        }
        .analysis-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(231, 76, 60, 0.3);
        }
        .score-display {
            font-size: 60px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .target-score {
            font-size: 24px;
            text-align: center;
            opacity: 0.9;
        }
        
        .performance-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .section-title i {
            color: #e74c3c;
            font-size: 28px;
        }
        
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .performance-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid #e0e0e0;
            transition: all 0.3s;
        }
        .performance-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .performance-card.weak {
            border-left-color: #e74c3c;
            background: #ffe6e6;
        }
        .performance-card.moderate {
            border-left-color: #f39c12;
            background: #fff8e6;
        }
        .performance-card.strong {
            border-left-color: #27ae60;
            background: #e6f7ed;
        }
        .card-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .card-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        .card-detail {
            font-size: 14px;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        .recommendation-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .recommendation-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .recommendation-card.priority-critical {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        .recommendation-card.priority-high {
            border-left-color: #f39c12;
            background: #fffbf5;
        }
        .recommendation-card.priority-medium {
            border-left-color: #3498db;
        }
        .rec-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .rec-icon {
            font-size: 32px;
            color: #3498db;
        }
        .priority-critical .rec-icon {
            color: #e74c3c;
        }
        .priority-high .rec-icon {
            color: #f39c12;
        }
        .rec-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .rec-description {
            color: #7f8c8d;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .rec-actions {
            list-style: none;
            padding: 0;
        }
        .rec-actions li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #34495e;
        }
        .rec-actions li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
        }
        
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .priority-badge.critical {
            background: #e74c3c;
            color: white;
        }
        .priority-badge.high {
            background: #f39c12;
            color: white;
        }
        .priority-badge.medium {
            background: #3498db;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        .btn-custom {
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-study {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        .btn-study:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        .btn-dashboard {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin: 30px 0;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #3498db, #27ae60);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 25px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -37px;
            top: 5px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #3498db;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #3498db;
        }
        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        .timeline-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .timeline-desc {
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="analysis-header">
            <div class="text-center">
                <div style="font-size: 60px; margin-bottom: 10px;">📚</div>
                <h1 style="font-size: 36px; margin-bottom: 10px;">Level Promotion Test Analysis</h1>
                <p style="font-size: 18px; opacity: 0.9;">Let's analyze your performance and create a personalized learning path</p>
                <p style="font-size: 14px; opacity: 0.8; margin-top: 10px;">
                    <i class="fas fa-calendar"></i> Attempt Date: <?= date('F j, Y - g:i A', strtotime($attempt['attempt_date'])) ?>
                    | <?= htmlspecialchars($currentLevel) ?> → <?= htmlspecialchars($targetLevel) ?>
                </p>
            </div>
            <div class="score-display"><?= round($score, 1) ?>%</div>
            <div class="target-score">Target: 77% or higher | Gap: <?= round(77 - $score, 1) ?>%</div>
        </div>

        <!-- Overall Performance Breakdown -->
        <div class="performance-section">
            <div class="section-title">
                <i class="fas fa-chart-bar"></i>
                Performance Breakdown
            </div>
            <div class="performance-grid">
                <?php foreach ($topicPerformance as $topic): 
                    $class = $topic['percentage'] >= 70 ? 'strong' : ($topic['percentage'] >= 50 ? 'moderate' : 'weak');
                ?>
                <div class="performance-card <?= $class ?>">
                    <div class="card-label"><?= htmlspecialchars($topic['name']) ?></div>
                    <div class="card-value"><?= round($topic['percentage'], 1) ?>%</div>
                    <div class="card-detail"><?= $topic['correct'] ?> / <?= $topic['total'] ?> correct</div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="performance-grid">
                <div class="performance-card <?= $questionTypePerformance['Quiz question']['percentage'] >= 60 ? 'strong' : 'weak' ?>">
                    <div class="card-label">📝 Quiz Questions</div>
                    <div class="card-value"><?= round($questionTypePerformance['Quiz question']['percentage'], 1) ?>%</div>
                    <div class="card-detail"><?= $questionTypePerformance['Quiz question']['correct'] ?> / <?= $questionTypePerformance['Quiz question']['total'] ?> correct</div>
                </div>
                <div class="performance-card <?= $questionTypePerformance['Simulation question']['percentage'] >= 60 ? 'strong' : 'weak' ?>">
                    <div class="card-label">💻 Simulation Questions</div>
                    <div class="card-value"><?= round($questionTypePerformance['Simulation question']['percentage'], 1) ?>%</div>
                    <div class="card-detail"><?= $questionTypePerformance['Simulation question']['correct'] ?> / <?= $questionTypePerformance['Simulation question']['total'] ?> correct</div>
                </div>
                <div class="performance-card <?= $levelPerformance[$currentLevel]['percentage'] >= 60 ? 'strong' : 'weak' ?>">
                    <div class="card-label">📊 <?= $currentLevel ?> Level</div>
                    <div class="card-value"><?= round($levelPerformance[$currentLevel]['percentage'], 1) ?>%</div>
                    <div class="card-detail"><?= $levelPerformance[$currentLevel]['correct'] ?> / <?= $levelPerformance[$currentLevel]['total'] ?> correct</div>
                </div>
                <div class="performance-card <?= $levelPerformance[$targetLevel]['percentage'] >= 60 ? 'strong' : 'weak' ?>">
                    <div class="card-label">🚀 <?= $targetLevel ?> Level</div>
                    <div class="card-value"><?= round($levelPerformance[$targetLevel]['percentage'], 1) ?>%</div>
                    <div class="card-detail"><?= $levelPerformance[$targetLevel]['correct'] ?> / <?= $levelPerformance[$targetLevel]['total'] ?> correct</div>
                </div>
            </div>
        </div>

        <!-- Personalized Learning Path -->
        <div class="performance-section">
            <div class="section-title">
                <i class="fas fa-route"></i>
                Your Personalized Learning Path
            </div>
            <p style="color: #7f8c8d; margin-bottom: 30px;">
                Based on your test results, we've created a customized study plan to help you reach the 77% passing threshold. 
                Follow these recommendations in order for the best results.
            </p>
            
            <?php foreach ($recommendations as $index => $rec): ?>
            <div class="recommendation-card priority-<?= $rec['priority'] ?>">
                <div class="rec-header">
                    <i class="fas <?= $rec['icon'] ?> rec-icon"></i>
                    <div style="flex: 1;">
                        <div class="rec-title">
                            <?= $index + 1 ?>. <?= htmlspecialchars($rec['title']) ?>
                            <span class="priority-badge <?= $rec['priority'] ?>"><?= $rec['priority'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="rec-description"><?= $rec['description'] ?></div>
                <ul class="rec-actions">
                    <?php foreach ($rec['actions'] as $action): ?>
                    <li><?= htmlspecialchars($action) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Suggested Study Timeline -->
        <div class="performance-section">
            <div class="section-title">
                <i class="fas fa-calendar-alt"></i>
                Suggested 2-Week Study Plan
            </div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-title">Week 1: Days 1-3</div>
                        <div class="timeline-desc">Focus on your weakest topics. Review materials, watch videos, and take notes.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-title">Week 1: Days 4-7</div>
                        <div class="timeline-desc">Practice with simulation questions and hands-on activities in weak areas.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-title">Week 2: Days 1-4</div>
                        <div class="timeline-desc">Review all topics, complete practice tests, and identify any remaining gaps.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-title">Week 2: Days 5-7</div>
                        <div class="timeline-desc">Final review and confidence building. Retake the promotion test when ready!</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="recommendations.php" class="btn-custom btn-study">
                <i class="fas fa-book-reader"></i> Start Learning Path
            </a>
            <a href="progress_history.php" class="btn-custom btn-dashboard">
                <i class="fas fa-history"></i> View History
            </a>
            <a href="student_dashboard.php" class="btn-custom btn-dashboard">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
