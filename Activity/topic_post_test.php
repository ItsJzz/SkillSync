<?php
// topic_post_test.php - Main post-test interface for a specific topic
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

if ($topic_id <= 0) {
    die("Invalid topic ID");
}

// Check if user is eligible for post-test
$eligibility_stmt = $conn->prepare("
    SELECT ue.*, t.name as topic_name 
    FROM user_post_test_eligibility ue
    JOIN topics t ON ue.topic_id = t.id
    WHERE ue.user_id = ? AND ue.topic_id = ? AND ue.completed_all_levels = TRUE AND ue.post_test_available = TRUE
");
$eligibility_stmt->bind_param("ii", $user_id, $topic_id);
$eligibility_stmt->execute();
$eligibility = $eligibility_stmt->get_result()->fetch_assoc();
$eligibility_stmt->close();

if (!$eligibility) {
    die("You are not eligible for this post-test. Please complete all 5 activity levels first.");
}

// Get existing attempts
$attempts_stmt = $conn->prepare("
    SELECT * FROM user_post_test_attempts 
    WHERE user_id = ? AND topic_id = ? 
    ORDER BY attempt_number DESC
");
$attempts_stmt->bind_param("ii", $user_id, $topic_id);
$attempts_stmt->execute();
$attempts = $attempts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$attempts_stmt->close();

$best_score = 0;
$total_attempts = count($attempts);
foreach ($attempts as $attempt) {
    if ($attempt['score_percentage'] > $best_score) {
        $best_score = $attempt['score_percentage'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Test: <?= htmlspecialchars($eligibility['topic_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 1.1rem; }
        
        .content { padding: 40px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9ff; padding: 20px; border-radius: 12px; text-align: center; border-left: 4px solid #667eea; }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #667eea; }
        .stat-card .label { color: #666; font-size: 0.9rem; margin-top: 5px; }
        
        .instructions { background: #e8f4fd; border-left: 4px solid #3498db; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .instructions h3 { color: #2980b9; margin-bottom: 10px; }
        .instructions ul { margin-left: 20px; color: #555; }
        .instructions li { margin-bottom: 5px; }
        
        .start-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; border: none; border-radius: 25px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: transform 0.3s ease; display: flex; align-items: center; gap: 10px; margin: 0 auto; }
        .start-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); }
        
        .attempts-history { margin-top: 30px; }
        .attempts-history h3 { margin-bottom: 20px; color: #333; }
        .attempt-card { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .attempt-info { display: flex; gap: 20px; }
        .attempt-score { font-weight: bold; font-size: 1.1rem; }
        .score-excellent { color: #27ae60; }
        .score-good { color: #f39c12; }
        .score-poor { color: #e74c3c; }
        
        .back-btn { position: absolute; top: 20px; left: 20px; background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 15px; border-radius: 20px; cursor: pointer; transition: background 0.3s; }
        .back-btn:hover { background: rgba(255,255,255,0.3); }
    </style>
</head>
<body>
    <button class="back-btn" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> Post-Test Assessment</h1>
            <p><?= htmlspecialchars($eligibility['topic_name']) ?></p>
        </div>
        
        <div class="content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">20</div>
                    <div class="label">Questions</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $total_attempts ?></div>
                    <div class="label">Attempts Made</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= number_format($best_score, 1) ?>%</div>
                    <div class="label">Best Score</div>
                </div>
                <div class="stat-card">
                    <div class="number">30</div>
                    <div class="label">Minutes Limit</div>
                </div>
            </div>
            
            <div class="instructions">
                <h3><i class="fas fa-info-circle"></i> Instructions</h3>
                <ul>
                    <li>This post-test contains <strong>20 multiple-choice questions</strong> about <?= htmlspecialchars($eligibility['topic_name']) ?></li>
                    <li>You have <strong>30 minutes</strong> to complete the test</li>
                    <li>Each question has 4 options (A, B, C, D) - select the best answer</li>
                    <li>You can take this test multiple times to improve your score</li>
                    <li>Your highest score will be recorded for skill improvement tracking</li>
                    <li>Make sure you have a stable internet connection</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <button class="start-btn" onclick="startPostTest()">
                    <i class="fas fa-play"></i> Start Post-Test
                </button>
            </div>
            
            <?php if (!empty($attempts)): ?>
            <div class="attempts-history">
                <h3><i class="fas fa-history"></i> Previous Attempts</h3>
                <?php foreach ($attempts as $attempt): ?>
                <div class="attempt-card">
                    <div class="attempt-info">
                        <span>Attempt #<?= $attempt['attempt_number'] ?></span>
                        <span><?= date('M j, Y g:i A', strtotime($attempt['completed_at'])) ?></span>
                        <span><?= $attempt['time_taken_minutes'] ?> minutes</span>
                    </div>
                    <div class="attempt-score <?= $attempt['score_percentage'] >= 80 ? 'score-excellent' : ($attempt['score_percentage'] >= 60 ? 'score-good' : 'score-poor') ?>">
                        <?= number_format($attempt['score_percentage'], 1) ?>%
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function startPostTest() {
            if (confirm('Are you ready to start the post-test? Once started, the timer will begin and you cannot pause.')) {
                window.location.href = 'post_test_exam.php?topic_id=<?= $topic_id ?>';
            }
        }
    </script>
</body>
</html>