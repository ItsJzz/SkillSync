<?php
// post_test_results.php - Display post-test results with skill improvement analysis
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

if ($attempt_id <= 0) {
    die("Invalid attempt ID");
}

// Get attempt details
$attempt_stmt = $conn->prepare("
    SELECT ua.*, t.name as topic_name
    FROM user_post_test_attempts ua
    JOIN topics t ON ua.topic_id = t.id
    WHERE ua.id = ? AND ua.user_id = ? AND ua.status = 'completed'
");
$attempt_stmt->bind_param("ii", $attempt_id, $user_id);
$attempt_stmt->execute();
$attempt = $attempt_stmt->get_result()->fetch_assoc();
$attempt_stmt->close();

if (!$attempt) {
    die("Attempt not found or not completed");
}

// Get detailed responses
$responses_stmt = $conn->prepare("
    SELECT 
        r.*, 
        q.question_text, 
        q.option_a, 
        q.option_b, 
        q.option_c, 
        q.option_d, 
        q.correct_answer,
        q.difficulty
    FROM user_post_test_responses r
    JOIN topic_post_test_questions q ON r.question_id = q.id
    WHERE r.attempt_id = ?
    ORDER BY q.question_order, q.id
");
$responses_stmt->bind_param("i", $attempt_id);
$responses_stmt->execute();
$responses = $responses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$responses_stmt->close();

// Get skill improvement data
$improvement_stmt = $conn->prepare("
    SELECT * FROM user_post_test_eligibility 
    WHERE user_id = ? AND topic_id = ?
");
$improvement_stmt->bind_param("ii", $user_id, $attempt['topic_id']);
$improvement_stmt->execute();
$improvement = $improvement_stmt->get_result()->fetch_assoc();
$improvement_stmt->close();

// Get pre-assessment score for comparison
$pre_score_stmt = $conn->prepare("
    SELECT AVG(score) as pre_score 
    FROM student_tests 
    WHERE student_id = ? AND topic_id = ? AND test_type = 'pre'
");
$pre_score_stmt->bind_param("ii", $user_id, $attempt['topic_id']);
$pre_score_stmt->execute();
$pre_score_result = $pre_score_stmt->get_result()->fetch_assoc();
$pre_score = $pre_score_result['pre_score'] ?? 0;
$pre_score_stmt->close();

// Calculate statistics
$correct_by_difficulty = ['easy' => 0, 'medium' => 0, 'hard' => 0];
$total_by_difficulty = ['easy' => 0, 'medium' => 0, 'hard' => 0];

foreach ($responses as $response) {
    $difficulty = $response['difficulty'];
    $total_by_difficulty[$difficulty]++;
    if ($response['is_correct']) {
        $correct_by_difficulty[$difficulty]++;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Test Results: <?= htmlspecialchars($attempt['topic_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 1.1rem; }
        
        .content { padding: 40px; }
        
        .score-display { text-align: center; margin-bottom: 40px; }
        .score-circle { width: 200px; height: 200px; border-radius: 50%; margin: 0 auto 20px; position: relative; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold; color: white; }
        .score-excellent { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .score-good { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .score-poor { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #f8f9ff; padding: 20px; border-radius: 12px; text-align: center; border-left: 4px solid #667eea; }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #667eea; }
        .stat-card .label { color: #666; font-size: 0.9rem; margin-top: 5px; }
        
        .improvement-section { background: #e8f5e8; border-left: 4px solid #27ae60; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .improvement-section.negative { background: #ffeaa7; border-left-color: #f39c12; }
        .improvement-section h3 { color: #27ae60; margin-bottom: 10px; }
        .improvement-section.negative h3 { color: #e67e22; }
        
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .chart-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .chart-container h4 { margin-bottom: 15px; color: #333; }
        
        .detailed-results { margin-top: 30px; }
        .detailed-results h3 { margin-bottom: 20px; color: #333; }
        .question-result { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #e9ecef; }
        .question-result.correct { border-left-color: #27ae60; }
        .question-result.incorrect { border-left-color: #e74c3c; }
        .question-text { font-weight: bold; margin-bottom: 10px; }
        .answer-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem; }
        .user-answer { font-weight: bold; }
        .correct-answer { color: #27ae60; }
        .incorrect-answer { color: #e74c3c; }
        
        .action-buttons { text-align: center; margin-top: 30px; }
        .btn { padding: 12px 25px; border: none; border-radius: 25px; font-weight: bold; cursor: pointer; margin: 0 10px; text-decoration: none; display: inline-block; transition: all 0.3s ease; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a67d8; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        @media (max-width: 768px) {
            .charts-grid { grid-template-columns: 1fr; }
            .score-circle { width: 150px; height: 150px; font-size: 2rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Post-Test Results</h1>
            <p><?= htmlspecialchars($attempt['topic_name']) ?> - Attempt #<?= $attempt['attempt_number'] ?></p>
        </div>
        
        <div class="content">
            <div class="score-display">
                <div class="score-circle <?= $attempt['score_percentage'] >= 80 ? 'score-excellent' : ($attempt['score_percentage'] >= 60 ? 'score-good' : 'score-poor') ?>">
                    <?= number_format($attempt['score_percentage'], 1) ?>%
                </div>
                <h2>Your Score: <?= $attempt['correct_answers'] ?>/<?= $attempt['total_questions'] ?></h2>
                <p>Completed in <?= $attempt['time_taken_minutes'] ?> minutes</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?= $attempt['correct_answers'] ?></div>
                    <div class="label">Correct Answers</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $attempt['total_questions'] - $attempt['correct_answers'] ?></div>
                    <div class="label">Incorrect Answers</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $attempt['time_taken_minutes'] ?></div>
                    <div class="label">Minutes Taken</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= number_format($improvement['best_post_test_score'] ?? 0, 1) ?>%</div>
                    <div class="label">Your Best Score</div>
                </div>
            </div>
            
            <?php if ($improvement): ?>
            <div class="improvement-section <?= $improvement['skill_improvement_percentage'] < 0 ? 'negative' : '' ?>">
                <h3>
                    <i class="fas fa-chart-line"></i> Skill Improvement Analysis
                </h3>
                <p>
                    <strong>Pre-Assessment Score:</strong> <?= number_format($pre_score, 1) ?>% | 
                    <strong>Post-Test Score:</strong> <?= number_format($attempt['score_percentage'], 1) ?>% | 
                    <strong>Improvement:</strong> 
                    <span style="color: <?= $improvement['skill_improvement_percentage'] >= 0 ? '#27ae60' : '#e67e22' ?>">
                        <?= $improvement['skill_improvement_percentage'] >= 0 ? '+' : '' ?><?= number_format($improvement['skill_improvement_percentage'], 1) ?>%
                    </span>
                </p>
                <?php if ($improvement['skill_improvement_percentage'] >= 20): ?>
                    <p style="margin-top: 10px;"><i class="fas fa-trophy" style="color: #f39c12;"></i> <strong>Excellent improvement! You've shown significant skill development.</strong></p>
                <?php elseif ($improvement['skill_improvement_percentage'] >= 10): ?>
                    <p style="margin-top: 10px;"><i class="fas fa-thumbs-up" style="color: #27ae60;"></i> <strong>Good progress! Your skills are developing well.</strong></p>
                <?php elseif ($improvement['skill_improvement_percentage'] >= 0): ?>
                    <p style="margin-top: 10px;"><i class="fas fa-chart-line" style="color: #3498db;"></i> <strong>You're making progress! Keep practicing to improve further.</strong></p>
                <?php else: ?>
                    <p style="margin-top: 10px;"><i class="fas fa-info-circle" style="color: #e67e22;"></i> <strong>Consider reviewing the material and practicing more activities.</strong></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="charts-grid">
                <div class="chart-container">
                    <h4><i class="fas fa-pie-chart"></i> Performance by Difficulty</h4>
                    <canvas id="difficultyChart" width="300" height="200"></canvas>
                </div>
                <div class="chart-container">
                    <h4><i class="fas fa-chart-bar"></i> Score Distribution</h4>
                    <canvas id="scoreChart" width="300" height="200"></canvas>
                </div>
            </div>
            
            <div class="detailed-results">
                <h3><i class="fas fa-list-alt"></i> Detailed Question Review</h3>
                <?php foreach ($responses as $index => $response): ?>
                <div class="question-result <?= $response['is_correct'] ? 'correct' : 'incorrect' ?>">
                    <div class="question-text">
                        Question <?= $index + 1 ?>: <?= htmlspecialchars($response['question_text']) ?>
                    </div>
                    <div class="answer-row">
                        <span>Your Answer: <span class="user-answer <?= $response['is_correct'] ? 'correct-answer' : 'incorrect-answer' ?>"><?= $response['user_answer'] ?>. <?= htmlspecialchars($response['option_' . strtolower($response['user_answer'])]) ?></span></span>
                        <span>Difficulty: <?= ucfirst($response['difficulty']) ?></span>
                    </div>
                    <?php if (!$response['is_correct']): ?>
                    <div class="answer-row">
                        <span>Correct Answer: <span class="correct-answer"><?= $response['correct_answer'] ?>. <?= htmlspecialchars($response['option_' . strtolower($response['correct_answer'])]) ?></span></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="action-buttons">
                <a href="topic_post_test.php?topic_id=<?= $attempt['topic_id'] ?>" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Take Again
                </a>
                <a href="../student_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Difficulty Chart
        const difficultyCtx = document.getElementById('difficultyChart').getContext('2d');
        new Chart(difficultyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Easy', 'Medium', 'Hard'],
                datasets: [{
                    data: [
                        <?= $correct_by_difficulty['easy'] ?>,
                        <?= $correct_by_difficulty['medium'] ?>,
                        <?= $correct_by_difficulty['hard'] ?>
                    ],
                    backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const difficulty = context.label.toLowerCase();
                                const correct = context.parsed;
                                const total = <?= json_encode($total_by_difficulty) ?>[difficulty];
                                return `${context.label}: ${correct}/${total} correct`;
                            }
                        }
                    }
                }
            }
        });
        
        // Score Distribution Chart
        const scoreCtx = document.getElementById('scoreChart').getContext('2d');
        new Chart(scoreCtx, {
            type: 'bar',
            data: {
                labels: ['Pre-Assessment', 'Post-Test'],
                datasets: [{
                    label: 'Score %',
                    data: [<?= number_format($pre_score, 1) ?>, <?= number_format($attempt['score_percentage'], 1) ?>],
                    backgroundColor: ['#3498db', '#667eea'],
                    borderColor: ['#2980b9', '#5a67d8'],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>