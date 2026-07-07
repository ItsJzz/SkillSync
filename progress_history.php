<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get user info
$userQuery = "SELECT username, email FROM login_credentials WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Get progress history (successful promotions)
$historyQuery = "SELECT * FROM student_progress_history WHERE student_id = ? ORDER BY promoted_at DESC";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $student_id);
$historyStmt->execute();
$progressHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();

// Get promotion test attempts (both passed and failed)
$attemptsQuery = "SELECT 
    lpa.*,
    s.name as subject_name,
    s.code as subject_code
FROM level_promotion_attempts lpa
LEFT JOIN subjects s ON lpa.subject_id = s.id
WHERE lpa.student_id = ? 
ORDER BY lpa.attempt_date DESC";
$attemptsStmt = $conn->prepare($attemptsQuery);
$attemptsStmt->bind_param("i", $student_id);
$attemptsStmt->execute();
$promotionAttempts = $attemptsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$attemptsStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress History - SkillSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .history-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .history-header {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.2);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        .history-header h1 {
            color: #4B8B6E;
            font-weight: 700;
        }
        .history-header h2 {
            font-weight: 700;
        }
        .level-card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        .level-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.25);
            border-color: #4B8B6E;
        }
        .level-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        .level-badge.beginner {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
        }
        .level-badge.intermediate {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
        }
        .level-badge.expert {
            background: linear-gradient(135deg, #4B8B6E, #E8C547);
        }
        .stat-box {
            background: rgba(107, 175, 146, 0.08);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid rgba(107, 175, 146, 0.2);
        }
        .stat-label {
            font-size: 14px;
            color: #6BAF92;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4B8B6E;
        }
        .back-btn {
            background: #FFFFFF;
            color: #4B8B6E;
            border: 2px solid #4B8B6E;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);
        }
        .timeline-icon {
            font-size: 40px;
            color: #4B8B6E;
            margin-bottom: 15px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 80px;
            color: rgba(107, 175, 146, 0.3);
            margin-bottom: 20px;
        }
        .badge {
            font-size: 14px;
            padding: 8px 15px;
            font-weight: 600;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92) !important;
        }
        .badge.bg-danger {
            background: linear-gradient(135deg, #E8C547, #F4D77C) !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92) !important;
            border: none;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.4);
        }
        .text-muted {
            color: #6BAF92 !important;
        }
        h4 {
            color: #4B8B6E;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="history-container">
        <!-- Header -->
        <div class="history-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-history"></i> My Progress History</h1>
                    <p class="text-muted mb-0">Review your learning journey across different levels</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="student_dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Promotion Test Attempts Section -->
        <?php if (!empty($promotionAttempts)): ?>
        <div class="history-header" style="background: linear-gradient(135deg, #E8C547 0%, #F4D77C 100%); color: #4B8B6E; box-shadow: 0 10px 30px rgba(232, 197, 71, 0.3);">
            <h2 style="color: #4B8B6E; font-weight: 700;"><i class="fas fa-graduation-cap"></i> Promotion Test Attempts</h2>
            <p class="mb-0" style="opacity: 0.9; color: #4B8B6E; font-weight: 500;">Review your promotion test attempts and learn from your journey</p>
        </div>

        <?php foreach ($promotionAttempts as $attempt): ?>
        <div class="level-card" style="border-left: 5px solid <?= $attempt['passed'] ? '#4B8B6E' : '#E8C547' ?>;">
            <div class="row align-items-center">
                <div class="col-md-1 text-center">
                    <div style="font-size: 40px;">
                        <?= $attempt['passed'] ? '✅' : '📊' ?>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <h4>
                        <?= htmlspecialchars($attempt['current_level']) ?> → <?= htmlspecialchars($attempt['target_level']) ?>
                        <?php if ($attempt['passed']): ?>
                        <span class="badge bg-success">PASSED</span>
                        <?php else: ?>
                        <span class="badge bg-danger">FAILED</span>
                        <?php endif; ?>
                    </h4>
                    <p class="text-muted mb-2">
                        <i class="fas fa-calendar"></i> <?= date('F j, Y - g:i A', strtotime($attempt['attempt_date'])) ?>
                        <?php if ($attempt['subject_name']): ?>
                        | <i class="fas fa-book"></i> <?= htmlspecialchars($attempt['subject_name']) ?>
                        <?php endif; ?>
                    </p>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="stat-box">
                                <div class="stat-label">Your Score</div>
                                <div class="stat-value" style="color: <?= $attempt['passed'] ? '#4B8B6E' : '#E8C547' ?>;">
                                    <?= round($attempt['score'], 1) ?>%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box">
                                <div class="stat-label">Questions Correct</div>
                                <div class="stat-value">
                                    <?= $attempt['correct_count'] ?>/<?= $attempt['total_questions'] ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box">
                                <div class="stat-label">Pass Threshold</div>
                                <div class="stat-value" style="font-size: 18px;">77%</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!$attempt['passed']): ?>
                    <div class="mt-3">
                        <p class="text-muted mb-2">
                            <i class="fas fa-info-circle"></i> 
                            You needed <?= round(77 - $attempt['score'], 1) ?>% more to pass.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-3 text-center">
                    <?php if (!$attempt['passed']): ?>
                    <a href="promotion_test_analysis.php?attempt_id=<?= $attempt['id'] ?>" 
                       class="btn btn-primary btn-lg" 
                       style="border-radius: 50px; padding: 12px 30px;">
                        <i class="fas fa-chart-line"></i> View Analysis
                    </a>
                    <p class="text-muted mt-2" style="font-size: 12px;">
                        See personalized<br>learning path
                    </p>
                    <?php else: ?>
                    <div style="font-size: 48px; color: #27ae60;">
                        🎉
                    </div>
                    <p class="text-success fw-bold">Successfully<br>Promoted!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div style="margin: 40px 0;"></div> <!-- Spacer -->
        <?php endif; ?>

        <!-- Progress History Timeline (Successful Promotions) -->
        <?php if (empty($progressHistory) && empty($promotionAttempts)): ?>
        <div class="level-card">
            <div class="empty-state">
                <i class="fas fa-rocket"></i>
                <h3>No History Yet</h3>
                <p class="text-muted">Your progress history will appear here once you complete assessments and take promotion tests!</p>
            </div>
        </div>
        <?php elseif (!empty($progressHistory)): ?>
        <div class="history-header" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white;">
            <h2><i class="fas fa-trophy"></i> Completed Levels</h2>
            <p class="mb-0" style="opacity: 0.9;">Celebrate your achievements and milestones</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($progressHistory)): ?>
        <?php foreach ($progressHistory as $index => $history): 
            $achievements = json_decode($history['achievements'], true);
            $activityScores = json_decode($history['activity_scores'], true);
            $postTestScores = json_decode($history['post_test_scores'], true);
        ?>
        <div class="level-card">
            <div class="row">
                <div class="col-md-2 text-center">
                    <div class="timeline-icon">
                        <?php if ($history['level'] == 'Beginner'): ?>
                            <i class="fas fa-seedling"></i>
                        <?php elseif ($history['level'] == 'Intermediate'): ?>
                            <i class="fas fa-rocket"></i>
                        <?php else: ?>
                            <i class="fas fa-trophy"></i>
                        <?php endif; ?>
                    </div>
                    <span class="level-badge <?= strtolower($history['level']) ?>">
                        <?= htmlspecialchars($history['level']) ?>
                    </span>
                </div>
                
                <div class="col-md-10">
                    <h3><?= htmlspecialchars($history['level']) ?> Level Journey</h3>
                    <p class="text-muted">
                        <i class="fas fa-calendar"></i> Completed on: 
                        <strong><?= date('F j, Y', strtotime($history['promoted_at'])) ?></strong>
                    </p>
                    
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Overall Score</div>
                                <div class="stat-value"><?= round($history['overall_score']) ?>%</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Progress</div>
                                <div class="stat-value"><?= round($history['progress_percentage']) ?>%</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Activities Completed</div>
                                <div class="stat-value"><?= $achievements['activities_completed'] ?? 0 ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Promotion Score</div>
                                <div class="stat-value"><?= round($achievements['promotion_score'] ?? 0) ?>%</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($achievements)): ?>
                    <div class="mt-3">
                        <strong><i class="fas fa-star"></i> Achievements:</strong>
                        <ul class="mt-2">
                            <li>Completed <?= $achievements['activities_completed'] ?? 0 ?> hands-on activities</li>
                            <li>Took <?= $achievements['post_tests_taken'] ?? 0 ?> post-assessments</li>
                            <li>Passed promotion test with <?= round($achievements['promotion_score'] ?? 0) ?>%</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
