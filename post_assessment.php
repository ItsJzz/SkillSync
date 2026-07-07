<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check eligibility for post-assessment
$eligibilityCheck = $conn->prepare("
    SELECT 
        s.id as subject_id,
        s.name as subject_name,
        s.code as subject_code,
        COUNT(DISTINCT t.id) as total_topics,
        COUNT(DISTINCT sas.topic_id) as completed_topics
    FROM subjects s
    JOIN topics t ON s.id = t.subject_id
    JOIN student_tests st ON t.id = st.topic_id 
    LEFT JOIN student_activity_scores sas ON t.id = sas.topic_id AND sas.student_id = ?
    WHERE st.student_id = ? AND st.test_type = 'pre'
    GROUP BY s.id, s.name, s.code
    HAVING completed_topics = total_topics
");

$eligibilityCheck->bind_param("ii", $student_id, $student_id);
$eligibilityCheck->execute();
$eligibleSubjects = $eligibilityCheck->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($eligibleSubjects)) {
    header("Location: student_dashboard.php?error=not_eligible");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = intval($_POST['subject_id']);
    $answers = $_POST['answers'] ?? [];
    
    // Verify subject eligibility
    $isEligible = false;
    foreach ($eligibleSubjects as $subject) {
        if ($subject['subject_id'] == $subject_id) {
            $isEligible = true;
            break;
        }
    }
    
    if (!$isEligible) {
        header("Location: student_dashboard.php?error=invalid_subject");
        exit;
    }
    
    // Get topics for this subject (same distribution as pre-test)
    $topicStmt = $conn->prepare("
        SELECT t.id, t.name 
        FROM topics t 
        JOIN student_tests st ON t.id = st.topic_id
        WHERE t.subject_id = ? AND st.student_id = ? AND st.test_type = 'pre'
        ORDER BY t.id
    ");
    $topicStmt->bind_param("ii", $subject_id, $student_id);
    $topicStmt->execute();
    $topics = $topicStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Distribute 50 questions across topics (same logic as pre-test)
    $questionsPerTopic = floor(50 / count($topics));
    $remainder = 50 % count($topics);
    
    $allQuestions = [];
    foreach ($topics as $index => $topic) {
        $topicQuestions = $questionsPerTopic + ($index < $remainder ? 1 : 0);
        
        $qStmt = $conn->prepare("SELECT * FROM questions WHERE topic_id = ? ORDER BY RAND() LIMIT ?");
        $qStmt->bind_param("ii", $topic['id'], $topicQuestions);
        $qStmt->execute();
        $questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $allQuestions = array_merge($allQuestions, $questions);
    }
    
    // Calculate scores by topic
    $topicScores = [];
    $totalCorrect = 0;
    $totalQuestions = count($allQuestions);
    
    foreach ($topics as $topic) {
        $topicCorrect = 0;
        $topicTotal = 0;
        
        foreach ($allQuestions as $question) {
            if ($question['topic_id'] == $topic['id']) {
                $topicTotal++;
                if (isset($answers[$question['id']]) && $answers[$question['id']] === $question['correct_option']) {
                    $topicCorrect++;
                    $totalCorrect++;
                }
            }
        }
        
        if ($topicTotal > 0) {
            $topicScores[$topic['id']] = ($topicCorrect / $topicTotal) * 100;
            
            // Store post-test results
            $insertStmt = $conn->prepare("INSERT INTO student_tests (student_id, topic_id, test_type, score, date_created) VALUES (?, ?, 'post', ?, NOW())");
            $insertStmt->bind_param("iid", $student_id, $topic['id'], $topicScores[$topic['id']]);
            $insertStmt->execute();
        }
    }
    
    // Calculate overall score
    $overallScore = ($totalCorrect / $totalQuestions) * 100;
    
    // Update learning journey
    $journeyStmt = $conn->prepare("
        INSERT INTO user_learning_journey (student_id, subject_id, post_assessment_score, post_assessment_date, journey_status)
        VALUES (?, ?, ?, NOW(), 'post_assessment_taken')
        ON DUPLICATE KEY UPDATE
        post_assessment_score = VALUES(post_assessment_score),
        post_assessment_date = VALUES(post_assessment_date),
        journey_status = VALUES(journey_status),
        improvement_percentage = CASE 
            WHEN pre_assessment_score > 0 THEN 
                ((VALUES(post_assessment_score) - pre_assessment_score) / pre_assessment_score * 100)
            ELSE 0 
        END
    ");
    
    $journeyStmt->bind_param("iid", $student_id, $subject_id, $overallScore);
    $journeyStmt->execute();
    
    // Redirect to results
    header("Location: post_assessment_results.php?subject_id=" . $subject_id);
    exit;
}

// Get subject to test (from GET parameter or first eligible)
$selectedSubjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : $eligibleSubjects[0]['subject_id'];
$selectedSubject = null;

foreach ($eligibleSubjects as $subject) {
    if ($subject['subject_id'] == $selectedSubjectId) {
        $selectedSubject = $subject;
        break;
    }
}

if (!$selectedSubject) {
    $selectedSubject = $eligibleSubjects[0];
    $selectedSubjectId = $selectedSubject['subject_id'];
}

// Check if already taken post-assessment for this subject
$alreadyTakenStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM student_tests st
    JOIN topics t ON st.topic_id = t.id
    WHERE st.student_id = ? AND t.subject_id = ? AND st.test_type = 'post'
");
$alreadyTakenStmt->bind_param("ii", $student_id, $selectedSubjectId);
$alreadyTakenStmt->execute();
$alreadyTakenResult = $alreadyTakenStmt->get_result()->fetch_assoc();
$alreadyTaken = $alreadyTakenResult['count'] > 0;

if ($alreadyTaken) {
    header("Location: post_assessment_results.php?subject_id=" . $selectedSubjectId);
    exit;
}

// Get topics and questions for this subject
$topicsStmt = $conn->prepare("
    SELECT t.id, t.name 
    FROM topics t 
    JOIN student_tests st ON t.id = st.topic_id
    WHERE t.subject_id = ? AND st.student_id = ? AND st.test_type = 'pre'
    ORDER BY t.id
");
$topicsStmt->bind_param("ii", $selectedSubjectId, $student_id);
$topicsStmt->execute();
$topics = $topicsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Distribute 50 questions across topics
$questionsPerTopic = floor(50 / count($topics));
$remainder = 50 % count($topics);

$questions = [];
foreach ($topics as $index => $topic) {
    $topicQuestions = $questionsPerTopic + ($index < $remainder ? 1 : 0);
    
    $qStmt = $conn->prepare("SELECT * FROM questions WHERE topic_id = ? ORDER BY RAND() LIMIT ?");
    $qStmt->bind_param("ii", $topic['id'], $topicQuestions);
    $qStmt->execute();
    $topicQs = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $questions = array_merge($questions, $topicQs);
}

// Shuffle questions for randomized order
shuffle($questions);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post-Assessment - SkillSync</title>
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
            max-width: 900px; 
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
        .header p { 
            color: #666; 
            font-size: 1.1rem; 
            margin: 5px 0;
        }
        .assessment-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .progress-container {
            margin-bottom: 20px;
        }
        .progress-bar { 
            background: #e0e0e0; 
            height: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            overflow: hidden;
        }
        .progress-fill { 
            background: linear-gradient(90deg, #667eea, #764ba2); 
            height: 100%; 
            border-radius: 5px; 
            transition: width 0.3s; 
            width: 0%;
        }
        .progress-text {
            text-align: center;
            color: #666;
            font-weight: bold;
            margin-top: 5px;
        }
        .question-card { 
            background: #f8f9fa; 
            border-radius: 10px; 
            padding: 25px; 
            margin-bottom: 20px; 
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .question-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 20px;
            color: #333;
        }
        .options { 
            margin-top: 15px; 
        }
        .option { 
            margin: 12px 0; 
            padding: 15px; 
            background: white; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        .option:hover { 
            border-color: #667eea; 
            transform: translateX(5px);
        }
        .option.selected { 
            border-color: #667eea; 
            background: #f0f4ff; 
            transform: translateX(5px);
        }
        .option input[type="radio"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        .navigation { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 40px; 
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .btn { 
            padding: 12px 30px; 
            border: none; 
            border-radius: 25px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 1rem;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        .btn-secondary { 
            background: #6c757d; 
            color: white; 
        }
        .btn-success {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
        }
        .timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-weight: bold;
            color: #667eea;
        }
        .subject-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .container { 
                margin: 10px; 
                padding: 20px; 
            }
            .header h1 { 
                font-size: 2rem; 
            }
            .timer {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="timer">
        <i class="fas fa-clock"></i>
        <span id="timer">60:00</span>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Post-Assessment</h1>
            <p>Measure your improvement after completing all activities!</p>
        </div>

        <?php if (count($eligibleSubjects) > 1): ?>
        <div class="subject-selector">
            <h3>Select Subject for Post-Assessment:</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <?php foreach ($eligibleSubjects as $subject): ?>
                    <a href="post_assessment.php?subject_id=<?= $subject['subject_id'] ?>" 
                       class="option <?= ($subject['subject_id'] == $selectedSubjectId) ? 'selected' : '' ?>"
                       style="text-decoration: none; color: inherit;">
                        <div>
                            <strong><?= htmlspecialchars($subject['subject_name']) ?></strong><br>
                            <small><?= htmlspecialchars($subject['subject_code']) ?> - <?= $subject['completed_topics'] ?>/<?= $subject['total_topics'] ?> topics completed</small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="assessment-info">
            <h3><?= htmlspecialchars($selectedSubject['subject_name']) ?> (<?= htmlspecialchars($selectedSubject['subject_code']) ?>)</h3>
            <p><i class="fas fa-check-circle"></i> All <?= $selectedSubject['total_topics'] ?> topics completed</p>
            <p><i class="fas fa-clock"></i> Time Limit: 60 minutes | <i class="fas fa-question-circle"></i> Questions: <?= count($questions) ?></p>
        </div>

        <form method="POST" id="assessmentForm">
            <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Question 1 of <?= count($questions) ?></div>
            </div>

            <?php foreach ($questions as $index => $question): ?>
            <div class="question-card" id="question-<?= $index ?>" style="<?= $index > 0 ? 'display: none;' : '' ?>">
                <div class="question-number"><?= $index + 1 ?></div>
                <div class="question-text"><?= htmlspecialchars($question['question_text']) ?></div>
                
                <div class="options">
                    <?php
                    $options = ['A', 'B', 'C', 'D'];
                    foreach ($options as $opt): 
                        $optionText = $question['option_' . strtolower($opt)];
                        if (!empty($optionText)):
                    ?>
                    <label class="option" for="q<?= $question['id'] ?>_<?= $opt ?>">
                        <input type="radio" 
                               name="answers[<?= $question['id'] ?>]" 
                               value="<?= $opt ?>" 
                               id="q<?= $question['id'] ?>_<?= $opt ?>"
                               onchange="selectOption(this)">
                        <span><?= $opt ?>. <?= htmlspecialchars($optionText) ?></span>
                    </label>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="navigation">
                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="previousQuestion()" disabled>
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextQuestion()">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
                
                <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                    <i class="fas fa-paper-plane"></i> Submit Assessment
                </button>
            </div>
        </form>
    </div>

    <script>
        let currentQuestion = 0;
        const totalQuestions = <?= count($questions) ?>;
        let timeLeft = 3600; // 60 minutes in seconds
        
        // Timer functionality
        const timer = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                alert('Time is up! Submitting your assessment...');
                document.getElementById('assessmentForm').submit();
            }
        }, 1000);

        function updateProgress() {
            const progress = ((currentQuestion + 1) / totalQuestions) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
            document.getElementById('progressText').textContent = 
                `Question ${currentQuestion + 1} of ${totalQuestions}`;
        }

        function showQuestion(index) {
            // Hide all questions
            for (let i = 0; i < totalQuestions; i++) {
                document.getElementById(`question-${i}`).style.display = 'none';
            }
            
            // Show current question
            document.getElementById(`question-${index}`).style.display = 'block';
            
            // Update navigation buttons
            document.getElementById('prevBtn').disabled = (index === 0);
            
            if (index === totalQuestions - 1) {
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'inline-block';
            } else {
                document.getElementById('nextBtn').style.display = 'inline-block';
                document.getElementById('submitBtn').style.display = 'none';
            }
            
            updateProgress();
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                currentQuestion++;
                showQuestion(currentQuestion);
            }
        }

        function previousQuestion() {
            if (currentQuestion > 0) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
        }

        function selectOption(radio) {
            // Remove selected class from all options in this question
            const questionCard = radio.closest('.question-card');
            questionCard.querySelectorAll('.option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Add selected class to chosen option
            radio.closest('.option').classList.add('selected');
        }

        // Form submission with confirmation
        document.getElementById('assessmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Count answered questions
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            const unansweredCount = totalQuestions - answeredQuestions;
            
            let confirmMessage = `You have answered ${answeredQuestions} out of ${totalQuestions} questions.`;
            if (unansweredCount > 0) {
                confirmMessage += `\n${unansweredCount} questions are unanswered and will be marked as incorrect.`;
            }
            confirmMessage += '\n\nAre you sure you want to submit your post-assessment?';
            
            if (confirm(confirmMessage)) {
                clearInterval(timer);
                this.submit();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                previousQuestion();
            } else if (e.key === 'ArrowRight') {
                nextQuestion();
            }
        });

        // Initialize
        updateProgress();
    </script>
</body>
</html>