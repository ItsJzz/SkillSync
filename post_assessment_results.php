<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if (!$subject_id) {
    header("Location: student_dashboard.php");
    exit;
}

require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get subject information
$subjectStmt = $conn->prepare("SELECT name, code FROM subjects WHERE id = ?");
$subjectStmt->bind_param("i", $subject_id);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result()->fetch_assoc();

if (!$subjectResult) {
    header("Location: student_dashboard.php");
    exit;
}

// First, get the TOTAL number of topics in this subject
$totalTopicsStmt = $conn->prepare("SELECT COUNT(*) as total FROM topics WHERE subject_id = ?");
$totalTopicsStmt->bind_param("i", $subject_id);
$totalTopicsStmt->execute();
$totalTopicsResult = $totalTopicsStmt->get_result()->fetch_assoc();
$totalTopics = $totalTopicsResult['total'];
$totalTopicsStmt->close();

// Get ALL topics with their pre and post test scores (including incomplete ones)
$comparisonStmt = $conn->prepare("
    SELECT 
        t.id as topic_id,
        t.name as topic_name,
        COALESCE(pre.score, 0) as pre_score,
        COALESCE(post.score, 0) as post_score,
        (COALESCE(post.score, 0) - COALESCE(pre.score, 0)) as improvement,
        CASE 
            WHEN post.score IS NULL THEN 'not_started'
            WHEN post.score >= 80 THEN 'excellent'
            WHEN post.score >= 70 THEN 'good' 
            WHEN post.score >= 60 THEN 'fair'
            ELSE 'needs_improvement'
        END as performance_level,
        CASE 
            WHEN pre.id IS NOT NULL AND post.id IS NOT NULL THEN 'completed'
            WHEN pre.id IS NOT NULL AND post.id IS NULL THEN 'in_progress'
            ELSE 'not_started'
        END as status
    FROM topics t
    LEFT JOIN student_tests pre ON t.id = pre.topic_id AND pre.student_id = ? AND pre.test_type = 'pre'
    LEFT JOIN student_tests post ON t.id = post.topic_id AND post.student_id = ? AND post.test_type = 'post'
    WHERE t.subject_id = ?
    ORDER BY t.id
");
$comparisonStmt->bind_param("iii", $student_id, $student_id, $subject_id);
$comparisonStmt->execute();
$topicComparisons = $comparisonStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate overall statistics (using ALL topics, not just completed ones)
// Use post-test score if available, otherwise use pre-test score for current progress
$totalPreScore = 0;
$totalPostScore = 0;
$totalCurrentScore = 0;

foreach ($topicComparisons as $topic) {
    $totalPreScore += $topic['pre_score'];
    $totalPostScore += $topic['post_score'];
    
    // For overall progress, use post-test if taken, otherwise use pre-test
    $currentScore = ($topic['post_score'] > 0) ? $topic['post_score'] : $topic['pre_score'];
    $totalCurrentScore += $currentScore;
}

$avgPreScore = $totalTopics > 0 ? $totalPreScore / $totalTopics : 0;
$avgPostScore = $totalTopics > 0 ? $totalPostScore / $totalTopics : 0;
$avgCurrentScore = $totalTopics > 0 ? $totalCurrentScore / $totalTopics : 0;
$overallImprovement = $avgPostScore - $avgPreScore;
$improvementPercentage = $avgPreScore > 0 ? ($overallImprovement / $avgPreScore) * 100 : 0;

// Count completed topics
$completedTopics = count(array_filter($topicComparisons, fn($t) => $t['status'] === 'completed'));

// Performance categorization (only for completed topics)
$completedComparisons = array_filter($topicComparisons, fn($t) => $t['status'] === 'completed');
$excellentTopics = array_filter($completedComparisons, fn($t) => $t['performance_level'] === 'excellent');
$goodTopics = array_filter($completedComparisons, fn($t) => $t['performance_level'] === 'good');
$fairTopics = array_filter($completedComparisons, fn($t) => $t['performance_level'] === 'fair');
$needsImprovementTopics = array_filter($completedComparisons, fn($t) => $t['performance_level'] === 'needs_improvement');

// Get learning journey data
$journeyStmt = $conn->prepare("
    SELECT 
        pre_assessment_score,
        post_assessment_score,
        improvement_percentage,
        pre_assessment_date,
        post_assessment_date
    FROM user_learning_journey 
    WHERE student_id = ? AND subject_id = ?
");
$journeyStmt->bind_param("ii", $student_id, $subject_id);
$journeyStmt->execute();
$journeyData = $journeyStmt->get_result()->fetch_assoc();

// Activity completion data
$activityStmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT sas.topic_id) as completed_activities,
        AVG(sas.avg_score) as avg_activity_score,
        COUNT(sas.id) as total_attempts
    FROM student_activity_scores sas
    JOIN topics t ON sas.topic_id = t.id
    WHERE sas.student_id = ? AND t.subject_id = ?
");
$activityStmt->bind_param("ii", $student_id, $subject_id);
$activityStmt->execute();
$activityData = $activityStmt->get_result()->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post-Assessment Results - SkillSync</title>
    <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            margin: 0; 
            padding: 20px; 
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .header h1 { 
            color: #667eea; 
            margin-bottom: 10px; 
            font-size: 2.5rem;
        }
        .celebration {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            animation: celebrationPulse 2s ease-in-out infinite;
        }
        @keyframes celebrationPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card.improvement {
            border-left-color: #4caf50;
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
        }
        .stat-card.excellent {
            border-left-color: #4caf50;
        }
        .stat-card.good {
            border-left-color: #2196f3;
        }
        .stat-card.warning {
            border-left-color: #ff9800;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .comparison-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .comparison-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .topic-comparison {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .topic-name {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .score-bars {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .score-bar {
            flex: 1;
            margin: 0 5px;
        }
        .score-bar-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        .score-bar-container {
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        .score-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }
        .pre-score { background: #ff9800; }
        .post-score { background: #4caf50; }
        .improvement-indicator {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
        .improvement-positive { color: #4caf50; }
        .improvement-negative { color: #f44336; }
        .improvement-neutral { color: #666; }
        .performance-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .performance-category {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .performance-category.excellent { border-top: 4px solid #4caf50; }
        .performance-category.good { border-top: 4px solid #2196f3; }
        .performance-category.fair { border-top: 4px solid #ff9800; }
        .performance-category.needs-improvement { border-top: 4px solid #f44336; }
        .navigation-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin-top: 30px;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn-primary {
            background: white;
            color: #667eea;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        .recommendations {
            background: #f0f4ff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .recommendation-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .chart-container {
            position: relative;
            margin: 20px 0;
            height: 300px;
        }
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            .nav-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Assessment Complete!</h1>
            <p>Your improvement results for <strong><?= htmlspecialchars($subjectResult['name']) ?></strong></p>
        </div>

        <?php if ($overallImprovement > 0): ?>
        <div class="celebration">
            <h2><i class="fas fa-star"></i> Congratulations!</h2>
            <p>You've improved by <strong><?= round($overallImprovement, 1) ?>%</strong> (<?= round($improvementPercentage, 1) ?>% relative improvement)</p>
            <p>Keep up the excellent work!</p>
        </div>
        <?php elseif ($overallImprovement < 0): ?>
        <div class="celebration" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
            <h2><i class="fas fa-info-circle"></i> Keep Learning!</h2>
            <p>Your score decreased by <?= round(abs($overallImprovement), 1) ?>%, but this is part of the learning process.</p>
            <p>Focus on areas that need improvement and try again!</p>
        </div>
        <?php else: ?>
        <div class="celebration" style="background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);">
            <h2><i class="fas fa-balance-scale"></i> Consistent Performance!</h2>
            <p>You maintained your skill level. Consider challenging yourself with advanced topics!</p>
        </div>
        <?php endif; ?>

        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= round($avgPreScore, 1) ?>%</div>
                <div class="stat-label">Pre-Assessment Average</div>
                <small style="opacity: 0.7;">Initial baseline</small>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="stat-value"><?= round($avgCurrentScore, 1) ?>%</div>
                <div class="stat-label">Current Overall Progress</div>
                <small style="opacity: 0.9;">Best scores across all <?= $totalTopics ?> topics</small>
            </div>
            <div class="stat-card excellent">
                <div class="stat-value"><?= round($avgPostScore, 1) ?>%</div>
                <div class="stat-label">Post-Assessment Average</div>
                <small style="opacity: 0.7;">Completed topics only</small>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $completedTopics ?> / <?= $totalTopics ?></div>
                <div class="stat-label">Topics Completed</div>
                <small style="opacity: 0.7;"><?= round(($completedTopics / $totalTopics) * 100) ?>% done</small>
            </div>
        </div>

        <!-- Performance Breakdown -->
        <div class="comparison-section">
            <h3><i class="fas fa-chart-bar"></i> Performance Breakdown</h3>
            <div class="performance-breakdown">
                <div class="performance-category excellent">
                    <h4>Excellent (80%+)</h4>
                    <div class="stat-value"><?= count($excellentTopics) ?></div>
                    <div class="stat-label">topics</div>
                </div>
                <div class="performance-category good">
                    <h4>Good (70-79%)</h4>
                    <div class="stat-value"><?= count($goodTopics) ?></div>
                    <div class="stat-label">topics</div>
                </div>
                <div class="performance-category fair">
                    <h4>Fair (60-69%)</h4>
                    <div class="stat-value"><?= count($fairTopics) ?></div>
                    <div class="stat-label">topics</div>
                </div>
                <div class="performance-category needs-improvement">
                    <h4>Needs Work (<60%)</h4>
                    <div class="stat-value"><?= count($needsImprovementTopics) ?></div>
                    <div class="stat-label">topics</div>
                </div>
            </div>
        </div>

        <!-- Topic-by-Topic Comparison -->
        <div class="comparison-section">
            <h3><i class="fas fa-chart-line"></i> Topic-by-Topic Progress</h3>
            <div class="comparison-chart">
                <?php foreach ($topicComparisons as $topic): ?>
                <div class="topic-comparison">
                    <div class="topic-name">
                        <?= htmlspecialchars($topic['topic_name']) ?>
                        <?php if ($topic['status'] === 'not_started'): ?>
                            <span style="background: #e0e0e0; color: #666; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 8px;">Not Started</span>
                        <?php elseif ($topic['status'] === 'in_progress'): ?>
                            <span style="background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 8px;">In Progress</span>
                        <?php else: ?>
                            <span style="background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 8px;">✓ Completed</span>
                        <?php endif; ?>
                    </div>
                    <div class="score-bars">
                        <div class="score-bar">
                            <div class="score-bar-label">Pre: <?= round($topic['pre_score'], 1) ?>%</div>
                            <div class="score-bar-container">
                                <div class="score-bar-fill pre-score" style="width: <?= $topic['pre_score'] ?>%;"></div>
                            </div>
                        </div>
                        <div class="score-bar">
                            <div class="score-bar-label">Post: <?= round($topic['post_score'], 1) ?>%</div>
                            <div class="score-bar-container">
                                <div class="score-bar-fill post-score" style="width: <?= $topic['post_score'] ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <?php if ($topic['status'] === 'completed'): ?>
                    <div class="improvement-indicator <?= $topic['improvement'] > 0 ? 'improvement-positive' : ($topic['improvement'] < 0 ? 'improvement-negative' : 'improvement-neutral') ?>">
                        <?= $topic['improvement'] >= 0 ? '+' : '' ?><?= round($topic['improvement'], 1) ?>% 
                        <?= $topic['improvement'] > 0 ? '↗️' : ($topic['improvement'] < 0 ? '↘️' : '➡️') ?>
                    </div>
                    <?php else: ?>
                    <div class="improvement-indicator improvement-neutral">
                        <i class="fas fa-info-circle"></i> Complete post-test to see improvement
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chart Visualization -->
        <div class="comparison-section">
            <h3><i class="fas fa-chart-area"></i> Progress Visualization</h3>
            <div class="chart-container">
                <canvas id="progressChart"></canvas>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="recommendations">
            <h3><i class="fas fa-lightbulb"></i> Personalized Recommendations</h3>
            
            <?php if (!empty($excellentTopics)): ?>
            <div class="recommendation-item">
                <i class="fas fa-star" style="color: #4caf50; margin-right: 10px;"></i>
                <div>
                    <strong>Strengths:</strong> You excel in 
                    <?= implode(', ', array_column($excellentTopics, 'topic_name')) ?>. 
                    Consider mentoring others or exploring advanced concepts in these areas.
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($needsImprovementTopics)): ?>
            <div class="recommendation-item">
                <i class="fas fa-target" style="color: #f44336; margin-right: 10px;"></i>
                <div>
                    <strong>Focus Areas:</strong> Review 
                    <?= implode(', ', array_column($needsImprovementTopics, 'topic_name')) ?>. 
                    Practice more activities and consider seeking additional help.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($activityData['completed_activities'] < $totalTopics): ?>
            <div class="recommendation-item">
                <i class="fas fa-tasks" style="color: #ff9800; margin-right: 10px;"></i>
                <div>
                    <strong>Activity Completion:</strong> Complete remaining activities to solidify your understanding.
                    You've completed <?= $activityData['completed_activities'] ?> out of <?= $totalTopics ?> topics.
                </div>
            </div>
            <?php endif; ?>

            <div class="recommendation-item">
                <i class="fas fa-redo" style="color: #2196f3; margin-right: 10px;"></i>
                <div>
                    <strong>Continuous Learning:</strong> Regular practice is key to retention. 
                    Revisit topics periodically and take practice quizzes to maintain your skills.
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="navigation-section">
            <h3>What's Next?</h3>
            <p>Choose your next step in your learning journey:</p>
            <div class="nav-buttons">
                <a href="student_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="Enhancement.php" class="btn btn-secondary">
                    <i class="fas fa-tasks"></i> Practice More
                </a>
                <a href="recommendations.php" class="btn btn-secondary">
                    <i class="fas fa-lightbulb"></i> Get Recommendations
                </a>
                <a href="progress.php" class="btn btn-secondary">
                    <i class="fas fa-chart-line"></i> View Progress
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.score-bar-fill');
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 200);
                });
            }, 500);

            // Create progress chart
            const ctx = document.getElementById('progressChart').getContext('2d');
            const topicNames = <?= json_encode(array_column($topicComparisons, 'topic_name')) ?>;
            const preScores = <?= json_encode(array_column($topicComparisons, 'pre_score')) ?>;
            const postScores = <?= json_encode(array_column($topicComparisons, 'post_score')) ?>;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: topicNames,
                    datasets: [
                        {
                            label: 'Pre-Assessment',
                            data: preScores,
                            borderColor: '#ff9800',
                            backgroundColor: 'rgba(255, 152, 0, 0.1)',
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'Post-Assessment',
                            data: postScores,
                            borderColor: '#4caf50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Score (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Topics'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Pre vs Post Assessment Comparison'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>