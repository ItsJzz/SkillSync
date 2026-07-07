<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if this is onboarding flow
$isOnboarding = isset($_GET['onboarding']) && $_GET['onboarding'] == '1';
if ($isOnboarding) {
    $_SESSION['onboarding_pretest'] = true;
}

require_once 'db_connect.php';

// Check if user has already taken pre-test for this subject (restriction)
$student_id = $_SESSION['user_id'];
$checkStmt = $conn->prepare("SELECT COUNT(*) as test_count FROM student_tests WHERE student_id = ? AND topic_id IN (SELECT id FROM topics WHERE subject_id = ?) AND test_type = 'pre'");
$checkStmt->bind_param("ii", $student_id, $subject_id);
$checkStmt->execute();
$result = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($result['test_count'] > 0 && !$isOnboarding) {
    die("
    <div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>
        <h2 style='color: #e74c3c;'>⚠️ Pre-Assessment Already Completed</h2>
        <p>You have already taken the pre-assessment for this subject. Each pre-assessment can only be taken once to ensure accurate skill evaluation.</p>
        <a href='student_dashboard.php' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 6px;'>Return to Dashboard</a>
    </div>");
}

// ----------------------------
// 1. Get subject by code (from query param)
// ----------------------------
if (!isset($_GET['subject'])) {
    die("Subject not specified.");
}
$subjectCode = $_GET['subject'];

// Verify subject
$subStmt = $conn->prepare("SELECT id, name FROM subjects WHERE code = ?");
$subStmt->bind_param("s", $subjectCode);
$subStmt->execute();
$subject = $subStmt->get_result()->fetch_assoc();
$subStmt->close();

if (!$subject) {
    die("Invalid subject.");
}
$subject_id = $subject['id'];

// ----------------------------
// 2. Fetch topics under this subject
// ----------------------------
$topicsRes = $conn->prepare("SELECT id, name FROM topics WHERE subject_id = ? ORDER BY id");
$topicsRes->bind_param("i", $subject_id);
$topicsRes->execute();
$topics = $topicsRes->get_result()->fetch_all(MYSQLI_ASSOC);
$topicsRes->close();

$topicCount = count($topics);
if ($topicCount === 0) {
    die("No topics found for subject: " . htmlspecialchars($subject['name']));
}

// ----------------------------
// 3. Calculate scalable assessment structure (Auto-scaling to 80 questions total)
// ----------------------------
$targetTotalQuestions = 80;
$questionsPerTopic = floor($targetTotalQuestions / $topicCount);
$remainder = $targetTotalQuestions % $topicCount;
$totalQuestions = $targetTotalQuestions; // Total questions for the entire assessment

// Calculate quiz and simulation distribution per topic (50/50 split)
$quizPerTopic = floor($questionsPerTopic / 2);
$simPerTopic = floor($questionsPerTopic / 2);

// If odd number, give the extra to quiz questions
if ($questionsPerTopic % 2 != 0) {
    $quizPerTopic += 1;
}

// Calculate beginner/intermediate split (approximately 60/40 ratio)
$beginnerQuizPerTopic = ceil($quizPerTopic * 0.6);
$intermediateQuizPerTopic = $quizPerTopic - $beginnerQuizPerTopic;
$beginnerSimPerTopic = ceil($simPerTopic * 0.6);
$intermediateSimPerTopic = $simPerTopic - $beginnerSimPerTopic;

// ----------------------------
// 4. Fetch random questions per topic with auto-scaled distribution
// ----------------------------
$questions_by_topic = [];
$activities_by_topic = [];
$topicIndex = 0;

foreach ($topics as $topic) {
    $tid = $topic['id'];
    $questions_by_topic[$tid] = [];
    
    // Add 1 extra question to first few topics if there's a remainder
    $extraQuestion = ($topicIndex < $remainder) ? 1 : 0;
    $currentBeginnerQuiz = $beginnerQuizPerTopic + ($extraQuestion && $beginnerQuizPerTopic > 0 ? 1 : 0);
    $currentIntermediateQuiz = $intermediateQuizPerTopic;
    $currentBeginnerSim = $beginnerSimPerTopic;
    $currentIntermediateSim = $intermediateSimPerTopic;
    
    // Fetch beginner quiz questions
    if ($currentBeginnerQuiz > 0) {
        $stmt = $conn->prepare("
            SELECT * FROM questions
            WHERE topic_id = ? AND question_type = 'Quiz question' AND class_level = 'Beginner'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("ii", $tid, $currentBeginnerQuiz);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $questions_by_topic[$tid][] = $row;
        }
        $stmt->close();
    }
    
    // Fetch intermediate quiz questions
    if ($currentIntermediateQuiz > 0) {
        $stmt = $conn->prepare("
            SELECT * FROM questions
            WHERE topic_id = ? AND question_type = 'Quiz question' AND class_level = 'Intermediate'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("ii", $tid, $currentIntermediateQuiz);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $questions_by_topic[$tid][] = $row;
        }
        $stmt->close();
    }
    
    // Fetch beginner simulation questions
    if ($currentBeginnerSim > 0) {
        $stmt = $conn->prepare("
            SELECT * FROM questions
            WHERE topic_id = ? AND question_type = 'Simulation question' AND class_level = 'Beginner'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("ii", $tid, $currentBeginnerSim);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $questions_by_topic[$tid][] = $row;
        }
        $stmt->close();
    }
    
    // Fetch intermediate simulation questions
    if ($currentIntermediateSim > 0) {
        $stmt = $conn->prepare("
            SELECT * FROM questions
            WHERE topic_id = ? AND question_type = 'Simulation question' AND class_level = 'Intermediate'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("ii", $tid, $currentIntermediateSim);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $questions_by_topic[$tid][] = $row;
        }
        $stmt->close();
    }
    
    // Shuffle questions within each topic to randomize order
    shuffle($questions_by_topic[$tid]);
    $topicIndex++;
}

// ----------------------------
// 5. Load and select random hands-on activities
// ----------------------------
$activitiesJsonPath = 'Activity/activities.json';
$activitiesData = [];
if (file_exists($activitiesJsonPath)) {
    $activitiesJson = file_get_contents($activitiesJsonPath);
    $activitiesData = json_decode($activitiesJson, true);
}

foreach ($topics as $topic) {
    $tid = $topic['id'];
    $activities_by_topic[$tid] = null;
    
    // Check if activities exist for this topic
    if (isset($activitiesData[$tid]) && isset($activitiesData[$tid]['instructions'])) {
        $availableActivities = [];
        
        // Collect level 1-2 intermediate activities
        foreach ($activitiesData[$tid]['instructions'] as $instruction) {
            if (($instruction['level'] == 1 || $instruction['level'] == 2) && 
                $instruction['class_level'] == 'Intermediate') {
                foreach ($instruction['variants'] as $variant) {
                    $activity = $variant;
                    $activity['level'] = $instruction['level'];
                    $activity['class_level'] = $instruction['class_level'];
                    $activity['topic_name'] = $activitiesData[$tid]['name'];
                    $availableActivities[] = $activity;
                }
            }
        }
        
        // Randomly select one activity if available
        if (!empty($availableActivities)) {
            $activities_by_topic[$tid] = $availableActivities[array_rand($availableActivities)];
        }
    }
}

// ----------------------------
// 6. Calculate scalable scoring system - Total 150 points (100 questions + 50 hands-on)
// ----------------------------
$totalQuestionPoints = 100; // Total points for all questions
$totalHandsOnPoints = 50;   // Total points for all hands-on activities
$totalMaxScore = 150;       // Grand total

// Points per question (100 points ÷ 80 questions = 1.25 points per question)
$pointsPerQuestion = $totalQuestionPoints / $totalQuestions;

// Points per hands-on activity (50 points ÷ number of topics)
$handsOnPointsPerTopic = round($totalHandsOnPoints / $topicCount, 2);

// For backward compatibility and display
$questionPointsPerTopic = $pointsPerQuestion * $questionsPerTopic;
$maxScorePerTopic = $questionPointsPerTopic + $handsOnPointsPerTopic;

// All questions have the same point value for simplicity
$beginnerQuestionPoints = $pointsPerQuestion;
$intermediateQuestionPoints = $pointsPerQuestion;

// ----------------------------
// 7. Build correct answer key with scoring info
// ----------------------------
$correctAnswers = [];
foreach ($questions_by_topic as $topic_id => $topicQs) {
    foreach ($topicQs as $q) {
        $points = ($q['class_level'] == 'Beginner') ? $beginnerQuestionPoints : $intermediateQuestionPoints;
        $correctAnswers["q".$q['id']] = [
            "answer" => $q['correct_option'],
            "topic_id" => $topic_id,
            "class_level" => $q['class_level'],
            "question_type" => $q['question_type'],
            "points" => $points
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Test: <?= htmlspecialchars($subject['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
            min-height: 100vh;
        }

        .exam-header {
            background: linear-gradient(135deg, #4B8B6E 0%, #6BAF92 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 4px 20px rgba(75, 139, 110, 0.3);
        }

        .exam-header h2 {
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timer-box {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .question-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15);
            margin-bottom: 20px;
            padding: 30px;
            border: 2px solid rgba(107, 175, 146, 0.2);
            transition: all 0.3s ease;
        }

        .question-card:hover {
            box-shadow: 0 15px 40px rgba(75, 139, 110, 0.25);
        }

        .topic-page {
            display: none;
        }

        .topic-page.active {
            display: block;
        }

        .topic-header {
            background: linear-gradient(135deg, rgba(249, 249, 246, 0.95), rgba(255, 255, 255, 0.98));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border-left: 5px solid #6BAF92;
            box-shadow: 0 8px 25px rgba(75, 139, 110, 0.1);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }

        .topic-header h4 {
            color: #4B8B6E;
            font-weight: 700;
        }

        .topic-btn {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92) !important;
            color: white !important;
            border-color: #6BAF92 !important;
        }

        .topic-btn.current {
            background: linear-gradient(135deg, #3a705a, #4B8B6E) !important;
            border-color: #4B8B6E !important;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(75, 139, 110, 0.4);
        }

        .option-btn {
            width: 100%;
            text-align: left;
            margin: 10px 0;
            padding: 15px 20px;
            border: 2px solid rgba(107, 175, 146, 0.3);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .option-btn:hover {
            border-color: #6BAF92;
            background: rgba(249, 249, 246, 0.98);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(107, 175, 146, 0.2);
        }

        .option-btn.selected {
            border-color: #6BAF92;
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(107, 175, 146, 0.25));
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(107, 175, 146, 0.3);
            color: #4B8B6E;
        }

        .option-btn:active {
            transform: scale(0.98);
        }

        .question-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(45px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .nav-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid rgba(107, 175, 146, 0.3);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .nav-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(107, 175, 146, 0.3);
        }

        .nav-btn.answered {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            border-color: #6BAF92;
            box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
        }

        .nav-btn.current {
            background: linear-gradient(135deg, #6BAF92, #4B8B6E);
            color: white;
            border-color: #6BAF92;
            box-shadow: 0 4px 15px rgba(107, 175, 146, 0.4);
            transform: scale(1.15);
        }

        .code-snippet {
            background: linear-gradient(135deg, rgba(249, 249, 246, 0.9), rgba(255, 255, 255, 0.95));
            backdrop-filter: blur(5px);
            border-left: 4px solid #6BAF92;
            padding: 20px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(75, 139, 110, 0.1);
        }

        .hands-on-section {
            border-top: 3px solid #E8C547;
            padding-top: 20px;
        }

        .activity-card {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(232, 197, 71, 0.3);
            box-shadow: 0 8px 25px rgba(232, 197, 71, 0.15);
        }

        .activity-card:hover {
            box-shadow: 0 10px 35px rgba(232, 197, 71, 0.25);
            transform: translateY(-2px);
        }

        .requirements-section .list-group-item {
            border: none;
            padding: 8px 16px;
            background: transparent;
        }

        /* Floating Navigation */
        .floating-nav {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 50px;
            padding: 12px 25px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.3);
            border: 2px solid rgba(107, 175, 146, 0.3);
            z-index: 1000;
            display: none;
            animation: slideUp 0.3s ease-out;
        }

        .floating-nav.show {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .floating-nav .btn {
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .floating-nav .btn-primary {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            border: none;
        }

        .floating-nav .btn-primary:hover {
            background: linear-gradient(135deg, #3a705a, #4B8B6E);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
        }

        .scroll-top-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(75, 139, 110, 0.3);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .scroll-top-btn:hover {
            background: linear-gradient(135deg, #3a705a, #4B8B6E);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(75, 139, 110, 0.4);
        }

        .scroll-top-btn.show {
            display: flex;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        /* Progress Bar */
        .progress {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            background: linear-gradient(90deg, #4B8B6E, #6BAF92);
            transition: width 0.3s ease;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92) !important;
        }

        .badge.bg-info {
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
        }

        .badge.bg-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d) !important;
        }

        .text-primary {
            color: #4B8B6E !important;
            font-weight: 600;
        }

        /* Buttons */
        .btn-success {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            border: none;
            font-weight: 600;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #3a705a, #4B8B6E);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            border: none;
            color: #fff;
            font-weight: 600;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d4b03d, #E8C547);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(232, 197, 71, 0.3);
        }

        /* Navigation Buttons - Previous/Next */
        .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(5px);
            border: 2px solid #95a5a6 !important;
            color: #7f8c8d !important;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.2);
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d) !important;
            color: white !important;
            border-color: #7f8c8d !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
        }

        .btn-outline-success {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(5px);
            border: 2px solid #6BAF92 !important;
            color: #4B8B6E !important;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 175, 146, 0.2);
        }

        .btn-outline-success:hover {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92) !important;
            color: white !important;
            border-color: #6BAF92 !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 175, 146, 0.3);
        }

        /* Floating Nav Buttons */
        .floating-nav .btn-outline-secondary,
        .floating-nav .btn-outline-success {
            padding: 10px 20px;
        }

        /* Center Pagination Navigation */
        #centerPageInfo {
            font-weight: 700;
            font-size: 1.1rem;
            color: #4B8B6E;
            min-width: 120px;
            text-align: center;
        }

        #centerPrevBtn, #centerNextBtn {
            transition: all 0.3s ease;
        }

        #centerPrevBtn:disabled, #centerNextBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Hide regular navigation on mobile when floating nav is active */
        @media (max-width: 768px) {
            .floating-nav.show ~ .container .row:last-child {
                margin-bottom: 100px;
            }
            
            .question-nav {
                grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
            }
            
            .nav-btn {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="exam-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-clipboard-list me-3"></i>Pre-Test Assessment</h2>
                    <p class="mb-0"><?= htmlspecialchars($subject['name']) ?> - Comprehensive Skill Evaluation</p>
                </div>
                <div class="col-md-4">
                    <div class="timer-box">
                        <div><i class="fas fa-chart-line"></i> Pre-Assessment</div>
                        <div id="questionCounter">Topic <?= $topicCount ?> Assessment</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Progress Bar -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                </div>
                <small class="text-muted">Progress: <span id="progressText">0/<?= $totalQuestions + $topicCount ?></span> (Questions + Hands-on)</small>
            </div>
        </div>

        <!-- Topic Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <h6>Navigation:</h6>
                <div class="question-nav" id="topicNav">
                    <?php 
                    $navIndex = 1;
                    $topicIndex = 1;
                    foreach ($topics as $topic) {
                        if (!empty($questions_by_topic[$topic['id']])) {
                            $questionCount = count($questions_by_topic[$topic['id']]);
                            // Questions page
                            echo "<div class='nav-btn topic-btn' data-page='$navIndex' onclick='goToPage($navIndex)' title='{$topic['name']} Questions ($questionCount questions)'>Q$topicIndex</div>";
                            $navIndex++;
                            
                            // Hands-on page (if exists)
                            if (isset($activities_by_topic[$topic['id']]) && $activities_by_topic[$topic['id']]) {
                                echo "<div class='nav-btn activity-btn' data-page='$navIndex' onclick='goToPage($navIndex)' title='{$topic['name']} Hands-on Activity' style='background: #e67e22; color: white; border-color: #e67e22;'>H$topicIndex</div>";
                                $navIndex++;
                            }
                            $topicIndex++;
                        }
                    }
                    ?>
                </div>
                <small class="text-muted mt-2 d-block">Q = Questions, H = Hands-on Activities. Navigate through the assessment sections.</small>
            </div>
        </div>

        <!-- Questions -->
        <form id="preTestForm">
            <div id="questionsContainer">
                <?php 
                $topicIndex = 1;
                $globalQuestionIndex = 1;
                $pageIndex = 1; // For both questions and hands-on pages
                
                foreach ($topics as $topic) {
                    if (!empty($questions_by_topic[$topic['id']])) {
                        $isFirst = $pageIndex === 1;
                        $questionCount = count($questions_by_topic[$topic['id']]);
                        ?>
                        <!-- Questions Page for Topic <?= $topicIndex ?> -->
                        <div class="topic-page" id="page<?= $pageIndex ?>" style="display: <?= $isFirst ? 'block' : 'none' ?>;">
                            <div class="topic-header mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4><i class="fas fa-book me-2"></i><?= htmlspecialchars($topic['name']) ?> - Questions</h4>
                                        <p class="text-muted mb-0">Topic <?= $topicIndex ?> of <?= count($topics) ?> • <?= $questionCount ?> Questions</p>
                                    </div>
                                    <div class="topic-progress">
                                        <span class="badge bg-success fs-6" id="topicProgress<?= $topicIndex ?>">0/<?= $questionCount ?> Answered</span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php 
                            foreach ($questions_by_topic[$topic['id']] as $q) {
                                $qName = "q".$q['id'];
                                ?>
                                <div class="question-card" id="question<?= $globalQuestionIndex ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="text-primary">Question <?= $globalQuestionIndex ?> of <?= $totalQuestions ?></h6>
                                        <div>
                                            <span class="badge bg-outline-secondary">Topic: <?= htmlspecialchars($topic['name']) ?></span>
                                            <span class="badge bg-info"><?= $q['question_type'] ?></span>
                                            <span class="badge bg-secondary"><?= $q['class_level'] ?></span>
                                        </div>
                                    </div>
                                    
                                    <p class="fs-6 mb-3"><?= htmlspecialchars($q['question_text']) ?></p>
                                    
                                    <?php if (!empty($q['code_snippet'])): ?>
                                        <div class="code-snippet">
                                            <?= htmlspecialchars($q['code_snippet']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="options">
                                        <button type="button" class="option-btn" 
                                                data-question="<?= $qName ?>" 
                                                data-topic="<?= $topicIndex ?>"
                                                data-global-index="<?= $globalQuestionIndex ?>"
                                                data-answer="A"
                                                onclick="selectAnswer(this)">
                                            <strong>A.</strong> <?= htmlspecialchars($q['option_a']) ?>
                                        </button>
                                        <button type="button" class="option-btn" 
                                                data-question="<?= $qName ?>" 
                                                data-topic="<?= $topicIndex ?>"
                                                data-global-index="<?= $globalQuestionIndex ?>"
                                                data-answer="B"
                                                onclick="selectAnswer(this)">
                                            <strong>B.</strong> <?= htmlspecialchars($q['option_b']) ?>
                                        </button>
                                        <button type="button" class="option-btn" 
                                                data-question="<?= $qName ?>" 
                                                data-topic="<?= $topicIndex ?>"
                                                data-global-index="<?= $globalQuestionIndex ?>"
                                                data-answer="C"
                                                onclick="selectAnswer(this)">
                                            <strong>C.</strong> <?= htmlspecialchars($q['option_c']) ?>
                                        </button>
                                    </div>
                                </div>
                                <?php
                                $globalQuestionIndex++;
                            }
                            ?>
                        </div>
                        <?php
                        $pageIndex++;
                        
                        // Add Hands-on Activity Page after questions
                        if (isset($activities_by_topic[$topic['id']]) && $activities_by_topic[$topic['id']]):
                        ?>
                        <!-- Hands-on Activity Page for Topic <?= $topicIndex ?> -->
                        <div class="topic-page" id="page<?= $pageIndex ?>" style="display: none;">
                            <div class="topic-header mb-4" style="border-left-color: #e67e22;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4><i class="fas fa-code me-2"></i><?= htmlspecialchars($topic['name']) ?> - Hands-on Activity</h4>
                                        <p class="text-muted mb-0">Topic <?= $topicIndex ?> of <?= count($topics) ?> • Practical Coding Exercise</p>
                                    </div>
                                    <div>
                                        <span class="badge bg-warning text-dark">Level <?= $activities_by_topic[$topic['id']]['level'] ?> - <?= $activities_by_topic[$topic['id']]['class_level'] ?></span>
                                        <span class="badge bg-success"><?= round($handsOnPointsPerTopic, 1) ?> points</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-card" style="background: #fff7e6; border: 2px solid #e67e22; border-radius: 15px; padding: 25px;">
                                <h6 class="text-primary mb-3"><?= htmlspecialchars($activities_by_topic[$topic['id']]['title']) ?></h6>
                                <p class="mb-3"><?= htmlspecialchars($activities_by_topic[$topic['id']]['description']) ?></p>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Starter Code:</strong></label>
                                    <div class="code-snippet" style="background: #f8f9fa; border-left-color: #e67e22;">
<?= htmlspecialchars($activities_by_topic[$topic['id']]['skeleton']) ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Your Solution:</strong></label>
                                    <textarea class="form-control" name="activity_<?= $topic['id'] ?>" 
                                              rows="15" 
                                              style="font-family: 'Courier New', monospace; font-size: 14px;"
                                              placeholder="Write your Java code here..."><?= htmlspecialchars($activities_by_topic[$topic['id']]['skeleton']) ?></textarea>
                                </div>
                                
                                <div class="requirements-section">
                                    <label class="form-label"><strong>Requirements to meet:</strong></label>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($activities_by_topic[$topic['id']]['requirements'] as $req_name => $req_pattern): ?>
                                        <li class="list-group-item bg-transparent">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?= htmlspecialchars($req_name) ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="hint-section mt-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <?= htmlspecialchars($activities_by_topic[$topic['id']]['hint']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $pageIndex++;
                        endif;
                        
                        $topicIndex++;
                    }
                }
                $totalPages = $pageIndex - 1;
                ?>
            </div>

            <!-- Center Pagination Navigation -->
            <div class="row mt-5 mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center align-items-center gap-3" style="background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px); padding: 20px; border-radius: 20px; box-shadow: 0 8px 25px rgba(75, 139, 110, 0.15); border: 2px solid rgba(107, 175, 146, 0.2);">
                        <button type="button" class="btn btn-outline-secondary" id="centerPrevBtn" onclick="previousPage()" style="min-width: 120px;">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <span class="text-muted fw-bold" id="centerPageInfo" style="font-size: 1.1rem; color: #4B8B6E !important;">Page 1 of <?= $totalPages ?></span>
                        <button type="button" class="btn btn-outline-success" id="centerNextBtn" onclick="nextPage()" style="min-width: 120px;">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="row mt-4">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="previousPage()">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                </div>
                <div class="col-6 text-end">
                    <button type="button" class="btn btn-outline-success" id="nextBtn" onclick="nextPage()">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-check"></i> Submit Pre-Test
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Floating Navigation -->
    <div class="floating-nav" id="floatingNav">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="floatingPrevBtn" onclick="previousPage()">
            <i class="fas fa-chevron-left"></i> Previous
        </button>
        <span class="text-muted small" id="floatingPageInfo">Page 1 of <?= $totalPages ?></span>
        <button type="button" class="btn btn-outline-success btn-sm" id="floatingNextBtn" onclick="nextPage()">
            Next <i class="fas fa-chevron-right"></i>
        </button>
        <button type="submit" class="btn btn-success btn-sm" id="floatingSubmitBtn" form="preTestForm">
            <i class="fas fa-check"></i> Submit
        </button>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top-btn" id="scrollTopBtn" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Answer key with topic mapping
        const correctAnswers = <?= json_encode($correctAnswers) ?>;
        const totalPages = <?= $totalPages ?>;
        const totalTopics = <?= count($topics) ?>;
        
        let currentPage = 1;
        const totalQuestions = <?= $totalQuestions ?>;
        const totalItems = <?= $totalQuestions + $topicCount ?>; // Questions + Hands-on
        const answers = {};
        let completedItems = 0;

        function selectAnswer(button) {
            const questionName = button.dataset.question;
            const topicIndex = button.dataset.topic;
            const globalIndex = parseInt(button.dataset.globalIndex);
            const answer = button.dataset.answer;
            
            // Remove selected class from siblings
            button.parentNode.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Add selected class to clicked button
            button.classList.add('selected');
            
            // Store answer
            const wasNewAnswer = !answers[questionName];
            answers[questionName] = answer;
            
            // Update progress
            if (wasNewAnswer) {
                completedItems++;
                updateProgress();
                updateNavigation();
            }
            
            // Update topic progress
            updateTopicProgress(topicIndex);
            
            // Auto-scroll to next question (with delay for visual feedback)
            setTimeout(() => {
                autoScrollToNext(globalIndex);
            }, 300);
        }

        function autoScrollToNext(currentQuestionIndex) {
            // Find next question in current page
            const nextQuestion = document.querySelector(`#question${currentQuestionIndex + 1}`);
            
            if (nextQuestion) {
                // Scroll to next question with smooth animation
                nextQuestion.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Add a subtle highlight effect
                nextQuestion.style.transition = 'all 0.3s ease';
                nextQuestion.style.boxShadow = '0 0 20px rgba(39, 174, 96, 0.3)';
                setTimeout(() => {
                    nextQuestion.style.boxShadow = '';
                }, 1000);
            } else {
                // If no next question, scroll to bottom to show navigation
                const currentPageElement = document.getElementById(`page${currentPage}`);
                if (currentPageElement) {
                    currentPageElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'end'
                    });
                }
            }
        }

        function goToPage(pageNum) {
            // Hide current page
            document.getElementById(`page${currentPage}`).style.display = 'none';
            // Show target page
            document.getElementById(`page${pageNum}`).style.display = 'block';
            currentPage = pageNum;
            updateNavigation();
            
            // Smooth scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function nextPage() {
            if (currentPage < totalPages) {
                goToPage(currentPage + 1);
            }
        }

        function previousPage() {
            if (currentPage > 1) {
                goToPage(currentPage - 1);
            }
        }

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function updateNavigation() {
            // Update regular navigation buttons
            document.getElementById('prevBtn').style.display = currentPage === 1 ? 'none' : 'inline-block';
            document.getElementById('nextBtn').style.display = currentPage === totalPages ? 'none' : 'inline-block';
            document.getElementById('submitBtn').style.display = currentPage === totalPages ? 'inline-block' : 'none';
            
            // Update center navigation buttons
            document.getElementById('centerPrevBtn').style.display = currentPage === 1 ? 'none' : 'inline-block';
            document.getElementById('centerNextBtn').style.display = currentPage === totalPages ? 'none' : 'inline-block';
            document.getElementById('centerPageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
            
            // Update floating navigation buttons
            document.getElementById('floatingPrevBtn').style.display = currentPage === 1 ? 'none' : 'inline-block';
            document.getElementById('floatingNextBtn').style.display = currentPage === totalPages ? 'none' : 'inline-block';
            document.getElementById('floatingSubmitBtn').style.display = currentPage === totalPages ? 'inline-block' : 'none';
            
            // Update floating page info
            document.getElementById('floatingPageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
            
            // Update navigation buttons highlighting
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('current');
                if (parseInt(btn.dataset.page) === currentPage) {
                    btn.classList.add('current');
                }
            });
        }

        function updateTopicProgress(topicIndex) {
            const topicPage = document.getElementById(`page${currentPage}`);
            if (!topicPage) return;
            
            const topicQuestions = topicPage.querySelectorAll('.option-btn[data-answer]');
            const answeredInTopic = Array.from(topicQuestions).filter(btn => btn.classList.contains('selected')).length / 3; // Divide by 3 since each question has 3 options
            const totalInTopic = topicQuestions.length / 3;
            
            const progressElement = document.getElementById(`topicProgress${topicIndex}`);
            if (progressElement) {
                progressElement.textContent = `${Math.floor(answeredInTopic)}/${totalInTopic} Answered`;
                if (answeredInTopic === totalInTopic) {
                    progressElement.classList.remove('bg-success');
                    progressElement.classList.add('bg-primary');
                }
            }
        }

        function updateProgress() {
            const percentage = (completedItems / totalItems) * 100;
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressText').textContent = `${completedItems}/${totalItems}`;
        }

        // Form submission with scalable scoring
        document.getElementById('preTestForm').addEventListener('submit', e => {
            e.preventDefault();

            // Prepare scoring data
            const scoringData = {
                maxScorePerTopic: <?= round($maxScorePerTopic, 2) ?>,
                questionPointsPerTopic: <?= round($questionPointsPerTopic, 2) ?>,
                handsOnPointsPerTopic: <?= round($handsOnPointsPerTopic, 2) ?>,
                beginnerPoints: <?= round($beginnerQuestionPoints, 2) ?>,
                intermediatePoints: <?= round($intermediateQuestionPoints, 2) ?>
            };

            // Topic information for reference
            const topicNames = {
                <?php 
                $topic_entries = [];
                foreach ($topics as $topic) {
                    $topic_entries[] = $topic['id'] . ': "' . addslashes($topic['name']) . '"';
                }
                echo !empty($topic_entries) ? implode(',', $topic_entries) : '';
                ?>
            };

            // Score questions per topic with detailed breakdown
            let topicScores = {};
            Object.keys(correctAnswers).forEach(qName => {
                const topic_id = correctAnswers[qName].topic_id;
                const questionType = correctAnswers[qName].question_type;
                
                if (!topicScores[topic_id]) {
                    topicScores[topic_id] = { 
                        questionScore: 0, 
                        totalQuestionPoints: 0,
                        handsOnScore: 0,
                        quiz_correct: 0,
                        quiz_total: 0,
                        simulation_correct: 0,
                        simulation_total: 0
                    };
                }
                
                const questionPoints = correctAnswers[qName].points;
                topicScores[topic_id].totalQuestionPoints += questionPoints;
                
                // Track quiz vs simulation separately
                if (questionType === 'Quiz question') {
                    topicScores[topic_id].quiz_total++;
                    if (answers[qName] && answers[qName] === correctAnswers[qName].answer) {
                        topicScores[topic_id].quiz_correct++;
                        topicScores[topic_id].questionScore += questionPoints;
                    }
                } else if (questionType === 'Simulation question') {
                    topicScores[topic_id].simulation_total++;
                    if (answers[qName] && answers[qName] === correctAnswers[qName].answer) {
                        topicScores[topic_id].simulation_correct++;
                        topicScores[topic_id].questionScore += questionPoints;
                    }
                }
            });

            // Collect hands-on activities
            const activities = {};
            <?php foreach ($topics as $topic): ?>
                <?php if (isset($activities_by_topic[$topic['id']]) && $activities_by_topic[$topic['id']]): ?>
                const activity_<?= $topic['id'] ?> = document.querySelector('textarea[name="activity_<?= $topic['id'] ?>"]');
                const skeleton_<?= $topic['id'] ?> = <?= json_encode($activities_by_topic[$topic['id']]['skeleton']) ?>;
                if (activity_<?= $topic['id'] ?>) {
                    activities[<?= $topic['id'] ?>] = {
                        code: activity_<?= $topic['id'] ?>.value,
                        requirements: <?= json_encode($activities_by_topic[$topic['id']]['requirements']) ?>,
                        maxPoints: <?= $handsOnPointsPerTopic ?>
                    };
                    // Only give points if the user actually modified the skeleton code
                    const code = activity_<?= $topic['id'] ?>.value.trim();
                    const originalSkeleton = skeleton_<?= $topic['id'] ?>.trim();
                    
                    // Check if code was actually modified from skeleton
                    if (code === originalSkeleton || code === '') {
                        // No modification - give 0 points
                        topicScores[<?= $topic['id'] ?>].handsOnScore = 0;
                    } else {
                        // Code was modified - score based on substantial changes
                        const changeLength = Math.abs(code.length - originalSkeleton.length);
                        if (changeLength >= 50 || code.split('\n').length > originalSkeleton.split('\n').length + 3) {
                            // Substantial changes - full points
                            topicScores[<?= $topic['id'] ?>].handsOnScore = <?= $handsOnPointsPerTopic ?>;
                        } else if (changeLength >= 20) {
                            // Moderate changes - half points
                            topicScores[<?= $topic['id'] ?>].handsOnScore = Math.round(<?= $handsOnPointsPerTopic ?> * 0.5);
                        } else {
                            // Minimal changes - quarter points
                            topicScores[<?= $topic['id'] ?>].handsOnScore = Math.round(<?= $handsOnPointsPerTopic ?> * 0.25);
                        }
                    }
                }
                <?php endif; ?>
            <?php endforeach; ?>

            // Calculate per-topic percentages with detailed breakdown
            let topicPercentages = {};
            Object.keys(topicScores).forEach(topic_id => {
                const questionScore = topicScores[topic_id].questionScore;
                const handsOnScore = topicScores[topic_id].handsOnScore;
                const totalScore = questionScore + handsOnScore;
                const percentage = (totalScore / <?= $maxScorePerTopic ?>) * 100;
                
                topicPercentages[topic_id] = {
                    questionScore: questionScore,
                    handsOnScore: handsOnScore,
                    totalScore: totalScore,
                    maxScore: <?= $maxScorePerTopic ?>,
                    percentage: Math.round(percentage * 100) / 100,
                    name: topicNames[topic_id] || 'Unknown Topic',
                    // Add detailed breakdown for results page
                    quiz_correct: topicScores[topic_id].quiz_correct || 0,
                    quiz_total: topicScores[topic_id].quiz_total || 0,
                    simulation_correct: topicScores[topic_id].simulation_correct || 0,
                    simulation_total: topicScores[topic_id].simulation_total || 0,
                    hands_on_score: handsOnScore,
                    hands_on_max: <?= $handsOnPointsPerTopic ?>
                };
            });

            // Prepare the payload
            const payload = { 
                topicScores: topicScores,
                topicPercentages: topicPercentages,
                activities: activities,
                scoringData: scoringData,
                assessmentType: 'scalable_pretest'
            };
            
            // Debug: Log what we're sending
            console.log('Submitting assessment data:', payload);
            console.log('Topic Percentages:', topicPercentages);
            console.log('Topic Scores:', topicScores);

            // Send results to server
            fetch("save_attempt.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            })
            .then(res => {
                console.log('Response status:', res.status);
                return res.json();
            })
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    <?php if ($isOnboarding): ?>
                    window.location.href = "pre_test_results.php?onboarding=1";
                    <?php else: ?>
                    window.location.href = "pre_test_results.php";
                    <?php endif; ?>
                } else {
                    console.error('Server returned error:', data);
                    alert('Error saving results: ' + (data.message || data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error details:', err);
                alert('Error submitting assessment. Please check console for details.');
            });
        });

        // Initialize
        updateNavigation();
        updateProgress();
        
        // Handle floating navigation and scroll to top button visibility
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const floatingNav = document.getElementById('floatingNav');
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            
            // Show floating nav when scrolled down past header
            if (scrollTop > 300) {
                floatingNav.classList.add('show');
                scrollTopBtn.classList.add('show');
            } else {
                floatingNav.classList.remove('show');
                scrollTopBtn.classList.remove('show');
            }
        });
    </script>
</body>
</html>