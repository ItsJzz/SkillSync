<?php
// simplified_post_test_results.php - Show results using existing questions table
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

if (!$attempt_id) {
    die("Invalid attempt ID");
}

// Get attempt details
$attempt_stmt = $conn->prepare("
    SELECT upta.*, t.name as topic_name, s.name as subject_name
    FROM user_post_test_attempts upta
    JOIN topics t ON upta.topic_id = t.id
    JOIN subjects s ON t.subject_id = s.id
    WHERE upta.id = ? AND upta.user_id = ?
");
$attempt_stmt->bind_param("ii", $attempt_id, $user_id);
$attempt_stmt->execute();
$attempt = $attempt_stmt->get_result()->fetch_assoc();
$attempt_stmt->close();

if (!$attempt) {
    die("Attempt not found");
}

// Get pre-test data for comparison
$preTestScore = 0;
$preTestData = [];
$hasPreTest = false;

// Build JSON path dynamically
$topic_id_str = (string)$attempt['topic_id'];
$jsonPath = '$.topic_scores."' . $topic_id_str . '".percentage';
$jsonDetailsPath = '$.topic_scores."' . $topic_id_str . '"';

$preTestStmt = $conn->prepare("
    SELECT JSON_EXTRACT(assessment_data, ?) AS pre_test_score,
           JSON_EXTRACT(assessment_data, ?) AS pre_test_details
    FROM students 
    WHERE id = ? OR user_id = ? 
    LIMIT 1
");
$preTestStmt->bind_param("ssii", $jsonPath, $jsonDetailsPath, $user_id, $user_id);
$preTestStmt->execute();
$preTestResult = $preTestStmt->get_result()->fetch_assoc();
$preTestStmt->close();

if ($preTestResult && $preTestResult['pre_test_score'] !== null) {
    $preTestScore = floatval($preTestResult['pre_test_score']);
    $preTestData = json_decode($preTestResult['pre_test_details'], true);
    $hasPreTest = true;
}

// Calculate improvement
$improvement = $hasPreTest ? ($attempt['score'] - $preTestScore) : 0;
$improvementPercentage = $hasPreTest && $preTestScore > 0 ? (($improvement / $preTestScore) * 100) : 0;

// Get detailed responses with questions
$responses_stmt = $conn->prepare("
    SELECT 
        uptr.*,
        q.question_text,
        q.code_snippet,
        q.option_a,
        q.option_b, 
        q.option_c,
        q.correct_option
    FROM user_post_test_responses uptr
    JOIN questions q ON uptr.question_id = q.id
    WHERE uptr.attempt_id = ?
    ORDER BY uptr.answered_at
");
$responses_stmt->bind_param("i", $attempt_id);
$responses_stmt->execute();
$responses = $responses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$responses_stmt->close();

// Get all attempts for this topic (for progress tracking)
$all_attempts_stmt = $conn->prepare("
    SELECT attempt_number, score, completed_at 
    FROM user_post_test_attempts 
    WHERE user_id = ? AND topic_id = ? AND completed_at IS NOT NULL
    ORDER BY attempt_number
");
$all_attempts_stmt->bind_param("ii", $user_id, $attempt['topic_id']);
$all_attempts_stmt->execute();
$all_attempts = $all_attempts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$all_attempts_stmt->close();

// Get ALL topics in this subject with their pre-test and post-test scores
$subject_id = null;
$get_subject_stmt = $conn->prepare("SELECT subject_id FROM topics WHERE id = ?");
$get_subject_stmt->bind_param("i", $attempt['topic_id']);
$get_subject_stmt->execute();
$subject_result = $get_subject_stmt->get_result()->fetch_assoc();
$subject_id = $subject_result['subject_id'];
$get_subject_stmt->close();

$all_topics_stmt = $conn->prepare("
    SELECT 
        t.id,
        t.name,
        JSON_EXTRACT(s.assessment_data, CONCAT('$.topic_scores.\"', t.id, '\".percentage')) as pre_test_score,
        (
            SELECT MAX(upta.score)
            FROM user_post_test_attempts upta
            WHERE upta.user_id = ? AND upta.topic_id = t.id AND upta.completed_at IS NOT NULL
        ) as post_test_score,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM user_post_test_attempts upta 
                WHERE upta.user_id = ? AND upta.topic_id = t.id AND upta.completed_at IS NOT NULL
            ) THEN 'completed'
            WHEN JSON_EXTRACT(s.assessment_data, CONCAT('$.topic_scores.\"', t.id, '\".percentage')) IS NOT NULL THEN 'in_progress'
            ELSE 'not_started'
        END as status
    FROM topics t
    LEFT JOIN students s ON (s.id = ? OR s.user_id = ?)
    WHERE t.subject_id = ?
    ORDER BY t.id
");
$all_topics_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $subject_id);
$all_topics_stmt->execute();
$all_topics = $all_topics_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$all_topics_stmt->close();

// Calculate overall progress (average of all topics)
// Use post-test score if available, otherwise use pre-test score
$total_topics = count($all_topics);
$total_score = 0;
$completed_topics_count = 0;
foreach ($all_topics as $topic) {
    $post_score = $topic['post_test_score'] ?? 0;
    $pre_score = $topic['pre_test_score'] ? floatval($topic['pre_test_score']) : 0;
    
    // Use post-test score if taken, otherwise use pre-test score
    $topic_score = ($post_score > 0) ? $post_score : $pre_score;
    $total_score += $topic_score;
    
    if ($topic['status'] === 'completed') {
        $completed_topics_count++;
    }
}
$overall_progress = $total_topics > 0 ? ($total_score / $total_topics) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Test Results - <?php echo htmlspecialchars($attempt['topic_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .results-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 0;
        }
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .performance-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        .question-review {
            border-left: 4px solid #dee2e6;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
        }
        .question-review.correct {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .question-review.incorrect {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .stat-box {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin: 10px 0;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
    </style>
</head>
<body>
    <div class="results-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-trophy me-3"></i>Post-Test Results</h1>
                    <p class="lead mb-0"><?php echo htmlspecialchars($attempt['subject_name'] . ' - ' . $attempt['topic_name']); ?></p>
                </div>
                <div class="col-md-4">
                    <div class="score-circle">
                        <div class="text-center">
                            <div style="font-size: 2rem; font-weight: bold;"><?php echo $attempt['score']; ?>%</div>
                            <small>Your Score</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <?php if ($hasPreTest): ?>
        <!-- Pre-Test vs Post-Test Comparison -->
        <div class="performance-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 30px;">
            <h3 class="mb-4"><i class="fas fa-chart-line me-2"></i>Pre-Test vs Post-Test Comparison</h3>
            
            <div class="row text-center">
                <!-- Pre-Test Score -->
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.15); border-radius: 15px; padding: 30px;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📝</div>
                        <h2 style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo number_format($preTestScore, 1); ?>%</h2>
                        <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">Pre-Test Score</p>
                        <small style="opacity: 0.7;">Initial Assessment</small>
                    </div>
                </div>
                
                <!-- Improvement Arrow -->
                <div class="col-md-4 d-flex align-items-center justify-content-center">
                    <div style="text-align: center;">
                        <?php if ($improvement > 0): ?>
                            <div style="font-size: 4rem; animation: bounce 2s infinite;">
                                <i class="fas fa-arrow-up" style="color: #4ade80;"></i>
                            </div>
                            <h3 style="font-weight: bold; margin: 15px 0;">
                                <span style="color: #4ade80;">+<?php echo number_format(abs($improvement), 1); ?>%</span>
                            </h3>
                            <p style="font-size: 1.1rem; margin: 0;">Growth</p>
                            <small style="opacity: 0.8;">(<?php echo number_format(abs($improvementPercentage), 1); ?>% increase)</small>
                        <?php elseif ($improvement < 0): ?>
                            <div style="font-size: 4rem;">
                                <i class="fas fa-arrow-down" style="color: #f87171;"></i>
                            </div>
                            <h3 style="font-weight: bold; margin: 15px 0;">
                                <span style="color: #f87171;"><?php echo number_format($improvement, 1); ?>%</span>
                            </h3>
                            <p style="font-size: 1.1rem; margin: 0;">Decline</p>
                            <small style="opacity: 0.8;">Keep practicing!</small>
                        <?php else: ?>
                            <div style="font-size: 4rem;">
                                <i class="fas fa-equals" style="color: #fbbf24;"></i>
                            </div>
                            <h3 style="font-weight: bold; margin: 15px 0;">
                                <span style="color: #fbbf24;">No Change</span>
                            </h3>
                            <p style="font-size: 1.1rem; margin: 0;">Same Score</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Post-Test Score -->
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.15); border-radius: 15px; padding: 30px;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">🏆</div>
                        <h2 style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo number_format($attempt['score'], 1); ?>%</h2>
                        <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">Post-Test Score</p>
                        <small style="opacity: 0.7;">After Practice</small>
                    </div>
                </div>
            </div>
            
            <!-- Performance Message -->
            <div class="mt-4 text-center" style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 20px;">
                <?php if ($improvement >= 20): ?>
                    <h4><i class="fas fa-star me-2"></i>Outstanding Progress!</h4>
                    <p style="margin: 0; font-size: 1.05rem;">You've made exceptional improvement! Your hard work is paying off. 🌟</p>
                <?php elseif ($improvement >= 10): ?>
                    <h4><i class="fas fa-thumbs-up me-2"></i>Great Improvement!</h4>
                    <p style="margin: 0; font-size: 1.05rem;">Excellent progress! You're mastering the concepts well. Keep it up! 💪</p>
                <?php elseif ($improvement > 0): ?>
                    <h4><i class="fas fa-chart-line me-2"></i>Good Progress!</h4>
                    <p style="margin: 0; font-size: 1.05rem;">You're moving in the right direction. Continue practicing to improve further! 📈</p>
                <?php elseif ($improvement === 0): ?>
                    <h4><i class="fas fa-info-circle me-2"></i>Consistent Performance</h4>
                    <p style="margin: 0; font-size: 1.05rem;">Your score remained the same. Try reviewing the materials to improve! 📚</p>
                <?php else: ?>
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Review Recommended</h4>
                    <p style="margin: 0; font-size: 1.05rem;">Your score decreased. Review the learning materials and practice more activities. 💡</p>
                <?php endif; ?>
            </div>
            
            <!-- Detailed Breakdown Comparison -->
            <?php if ($hasPreTest && isset($preTestData['quiz_correct']) && isset($preTestData['simulation_correct'])): ?>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 20px;">
                        <h5 class="mb-3"><i class="fas fa-question-circle me-2"></i>Quiz Questions</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small>Pre-Test</small>
                                <h4><?php echo $preTestData['quiz_correct'] ?? 0; ?>/<?php echo $preTestData['quiz_total'] ?? 8; ?></h4>
                            </div>
                            <i class="fas fa-arrow-right fa-2x" style="opacity: 0.5;"></i>
                            <div>
                                <small>Post-Test</small>
                                <h4><?php echo round($attempt['correct_answers'] * 0.5); ?>/10</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 20px;">
                        <h5 class="mb-3"><i class="fas fa-code me-2"></i>Simulation Questions</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small>Pre-Test</small>
                                <h4><?php echo $preTestData['simulation_correct'] ?? 0; ?>/<?php echo $preTestData['simulation_total'] ?? 8; ?></h4>
                            </div>
                            <i class="fas fa-arrow-right fa-2x" style="opacity: 0.5;"></i>
                            <div>
                                <small>Post-Test</small>
                                <h4><?php echo round($attempt['correct_answers'] * 0.5); ?>/10</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Progress Chart -->
        <?php if (count($all_attempts) > 1): ?>
        <div class="performance-card">
            <h4><i class="fas fa-chart-line me-2"></i>Progress Over Time</h4>
            <canvas id="progressChart" style="max-height: 300px;"></canvas>
        </div>
        <?php endif; ?>

        <!-- Overall Progress Across All Topics -->
        <div class="performance-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; margin-bottom: 30px;">
            <h3 class="mb-4"><i class="fas fa-chart-pie me-2"></i>Overall Progress Across All Topics</h3>
            
            <div class="row text-center mb-4">
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.15); border-radius: 15px; padding: 25px;">
                        <h2 style="font-size: 3rem; font-weight: bold; margin-bottom: 10px;"><?php echo round($overall_progress, 1); ?>%</h2>
                        <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">Average Score</p>
                        <small style="opacity: 0.7;">Across all <?php echo $total_topics; ?> topics</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.15); border-radius: 15px; padding: 25px;">
                        <h2 style="font-size: 3rem; font-weight: bold; margin-bottom: 10px;"><?php echo $completed_topics_count; ?> / <?php echo $total_topics; ?></h2>
                        <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">Topics Completed</p>
                        <small style="opacity: 0.7;"><?php echo round(($completed_topics_count / $total_topics) * 100); ?>% of subject</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: rgba(255,255,255,0.15); border-radius: 15px; padding: 25px;">
                        <h2 style="font-size: 3rem; font-weight: bold; margin-bottom: 10px;"><?php echo round(($overall_progress / 75) * 100); ?>%</h2>
                        <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">To Next Level</p>
                        <small style="opacity: 0.7;">Target: 75% average</small>
                    </div>
                </div>
            </div>
            
            <!-- Topic Breakdown -->
            <div style="background: rgba(255,255,255,0.1); border-radius: 15px; padding: 25px;">
                <h4 class="mb-3"><i class="fas fa-list me-2"></i>Topic Breakdown</h4>
                <?php foreach ($all_topics as $topic): ?>
                <?php 
                    $pre_score = $topic['pre_test_score'] ? floatval($topic['pre_test_score']) : 0;
                    $post_score = $topic['post_test_score'] ?? 0;
                    $is_current = $topic['id'] == $attempt['topic_id'];
                    $status_color = $topic['status'] === 'completed' ? '#10b981' : ($topic['status'] === 'in_progress' ? '#f59e0b' : '#6b7280');
                ?>
                <div style="background: rgba(255,255,255,<?php echo $is_current ? '0.25' : '0.1'; ?>); border-radius: 10px; padding: 15px; margin-bottom: 10px; border-left: 4px solid <?php echo $status_color; ?>;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="flex-grow-1">
                            <strong style="font-size: 1.05rem;">
                                <?php echo htmlspecialchars($topic['name']); ?>
                                <?php if ($is_current): ?>
                                    <span style="background: rgba(255,255,255,0.3); padding: 2px 10px; border-radius: 12px; font-size: 0.8rem; margin-left: 8px;">← Current</span>
                                <?php endif; ?>
                            </strong>
                            <div class="mt-1">
                                <small style="opacity: 0.9;">
                                    Pre-Test: <?php echo round($pre_score); ?>% 
                                    <i class="fas fa-arrow-right mx-2"></i> 
                                    Post-Test: <?php echo $post_score > 0 ? round($post_score) . '%' : 'Not taken'; ?>
                                </small>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <?php if ($topic['status'] === 'completed'): ?>
                                <div style="background: rgba(16, 185, 129, 0.3); padding: 8px 16px; border-radius: 20px; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle me-1"></i> Completed
                                </div>
                            <?php elseif ($topic['status'] === 'in_progress'): ?>
                                <div style="background: rgba(245, 158, 11, 0.3); padding: 8px 16px; border-radius: 20px; font-size: 0.9rem;">
                                    <i class="fas fa-clock me-1"></i> In Progress
                                </div>
                            <?php else: ?>
                                <div style="background: rgba(107, 114, 128, 0.3); padding: 8px 16px; border-radius: 20px; font-size: 0.9rem;">
                                    <i class="fas fa-circle me-1"></i> Not Started
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Progress to Next Class Level -->
        <div class="performance-card" style="background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%); color: white; padding: 0; border-radius: 20px; overflow: hidden;">
            <div class="row g-0">
                <div class="col-md-9" style="padding: 40px;">
                    <div class="d-flex align-items-center mb-3">
                        <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 15px; margin-right: 20px;">
                            <i class="fas fa-check-square" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-weight: bold;"><?php echo $overall_progress >= 75 ? 'Congratulations!' : 'Keep Going!'; ?></h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 1.1rem;">
                                <?php if ($overall_progress >= 75): ?>
                                    You've reached the 75% threshold for the next class level!
                                <?php else: ?>
                                    You're making progress! Keep working to reach the 75% threshold for the next class level.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <p style="margin-bottom: 20px; opacity: 0.9;">
                        <?php if ($completed_topics_count < $total_topics): ?>
                            Complete remaining topics to improve your overall performance.
                        <?php else: ?>
                            Focus on improving your scores in weaker areas.
                        <?php endif; ?>
                    </p>
                    
                    <!-- Progress Bar -->
                    <div style="background: rgba(255,255,255,0.2); border-radius: 10px; height: 40px; position: relative; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #ffffff 0%, rgba(255,255,255,0.8) 100%); height: 100%; width: <?php echo min($overall_progress, 100); ?>%; transition: width 0.5s ease; border-radius: 10px; display: flex; align-items: center; padding: 0 15px;">
                            <span style="color: #ec4899; font-weight: bold; font-size: 0.9rem;">Current: <?php echo round($overall_progress, 1); ?>%</span>
                        </div>
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-weight: bold; font-size: 0.9rem;">Target: 75%</span>
                    </div>
                </div>
                
                <div class="col-md-3 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.1); padding: 40px;">
                    <div class="text-center">
                        <h2 style="font-size: 4rem; font-weight: bold; margin: 0; line-height: 1;"><?php echo round($overall_progress, 1); ?>%</h2>
                        <p style="margin: 0; opacity: 0.9; font-size: 1.1rem;">Overall Score</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Areas to Improve -->
        <?php 
        // Get incorrect answers and their topics
        $incorrectAnswers = array_filter($responses, function($r) { return !$r['is_correct']; });
        ?>
        
        <div class="performance-card" style="background: #fef3c7; border-left: 5px solid #f59e0b; padding: 30px;">
            <h4 style="color: #92400e; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle me-2"></i>Areas to Improve to Reach Next Level:
            </h4>
            
            <?php if (count($incorrectAnswers) > 0): ?>
                <ul style="color: #92400e; font-size: 1.05rem; line-height: 2; margin: 0;">
                    <?php 
                    $weakTopics = [];
                    foreach ($incorrectAnswers as $response) {
                        // You can enhance this to track which specific concepts were wrong
                        $weakTopics[] = $attempt['topic_name'];
                    }
                    $weakTopics = array_unique($weakTopics);
                    
                    // Show current topic and related areas
                    echo "<li><strong>" . htmlspecialchars($attempt['topic_name']) . "</strong> (" . $attempt['score'] . "%)";
                    echo " - <a href='activity_list.php?topic_id=" . $attempt['topic_id'] . "' style='color: #92400e; text-decoration: underline;'>Practice Activities</a></li>";
                    
                    // Show other topics as learning path with their actual scores
                    $displayCount = 0;
                    foreach ($all_topics as $topic) {
                        if ($topic['id'] != $attempt['topic_id'] && $displayCount < 4) {
                            // Get the best available score (post-test if taken, otherwise pre-test)
                            $pre_score = $topic['pre_test_score'] ? floatval($topic['pre_test_score']) : 0;
                            $post_score = $topic['post_test_score'] ?? 0;
                            $best_score = ($post_score > 0) ? $post_score : $pre_score;
                            
                            echo "<li><strong>" . htmlspecialchars($topic['name']) . "</strong> (" . round($best_score) . "%)";
                            echo " - <a href='activity_list.php?topic_id=" . $topic['id'] . "' style='color: #92400e; text-decoration: underline;'>Start Learning</a></li>";
                            $displayCount++;
                        }
                    }
                    ?>
                </ul>
                
                <div class="mt-4" style="background: rgba(245, 158, 11, 0.1); border-radius: 10px; padding: 20px;">
                    <h5 style="color: #92400e; margin-bottom: 15px;">
                        <i class="fas fa-lightbulb me-2"></i>Recommended Actions:
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer;" onclick="window.location.href='../Enhancement.php'">
                                <h6 style="color: #92400e; margin-bottom: 10px;">
                                    <i class="fas fa-tools me-2" style="color: #f59e0b;"></i>Enhancement Process
                                </h6>
                                <p style="color: #92400e; margin: 0; font-size: 0.95rem;">Complete practice activities to strengthen your understanding</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer;" onclick="window.location.href='../recommendations.php'">
                                <h6 style="color: #92400e; margin-bottom: 10px;">
                                    <i class="fas fa-book me-2" style="color: #f59e0b;"></i>Recommendations
                                </h6>
                                <p style="color: #92400e; margin: 0; font-size: 0.95rem;">View personalized learning materials and video resources</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: #92400e; font-size: 1.05rem; margin: 0;">
                    <i class="fas fa-trophy me-2" style="color: #f59e0b;"></i>
                    Perfect score! You've mastered this topic. Continue to the next topic to keep progressing.
                </p>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        
    </div>

    <script>
        <?php if (count($all_attempts) > 1): ?>
        // Progress Chart
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($a) { return '"Attempt ' . $a['attempt_number'] . '"'; }, $all_attempts)); ?>],
                datasets: [{
                    label: 'Score (%)',
                    data: [<?php echo implode(',', array_column($all_attempts, 'score')); ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.1)',
                    tension: 0.4,
                    fill: true
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
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>