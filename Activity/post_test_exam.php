<?php
// post_test_exam.php - The actual 20-question exam interface
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

// Check eligibility
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
    die("You are not eligible for this post-test.");
}

// Check if there's an active attempt
$active_attempt_stmt = $conn->prepare("
    SELECT * FROM user_post_test_attempts 
    WHERE user_id = ? AND topic_id = ? AND status = 'in_progress'
    ORDER BY id DESC LIMIT 1
");
$active_attempt_stmt->bind_param("ii", $user_id, $topic_id);
$active_attempt_stmt->execute();
$active_attempt = $active_attempt_stmt->get_result()->fetch_assoc();
$active_attempt_stmt->close();

// If no active attempt, create new one
if (!$active_attempt) {
    // Get next attempt number
    $next_attempt_stmt = $conn->prepare("
        SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt 
        FROM user_post_test_attempts 
        WHERE user_id = ? AND topic_id = ?
    ");
    $next_attempt_stmt->bind_param("ii", $user_id, $topic_id);
    $next_attempt_stmt->execute();
    $next_attempt_result = $next_attempt_stmt->get_result()->fetch_assoc();
    $next_attempt_number = $next_attempt_result['next_attempt'];
    $next_attempt_stmt->close();
    
    // Create new attempt
    $create_attempt_stmt = $conn->prepare("
        INSERT INTO user_post_test_attempts (user_id, topic_id, attempt_number, total_questions, status, started_at)
        VALUES (?, ?, ?, 20, 'in_progress', NOW())
    ");
    $create_attempt_stmt->bind_param("iii", $user_id, $topic_id, $next_attempt_number);
    $create_attempt_stmt->execute();
    $attempt_id = $conn->insert_id;
    $create_attempt_stmt->close();
    
    $attempt_number = $next_attempt_number;
    $started_at = date('Y-m-d H:i:s');
} else {
    $attempt_id = $active_attempt['id'];
    $attempt_number = $active_attempt['attempt_number'];
    $started_at = $active_attempt['started_at'];
}

// Get questions for this topic (20 questions)
$questions_stmt = $conn->prepare("
    SELECT * FROM topic_post_test_questions 
    WHERE topic_id = ? 
    ORDER BY question_order, id 
    LIMIT 20
");
$questions_stmt->bind_param("i", $topic_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$questions_stmt->close();

if (empty($questions)) {
    die("No questions available for this topic. Please contact administrator.");
}

// Get existing responses for this attempt
$responses_stmt = $conn->prepare("
    SELECT question_id, user_answer 
    FROM user_post_test_responses 
    WHERE attempt_id = ?
");
$responses_stmt->bind_param("i", $attempt_id);
$responses_stmt->execute();
$existing_responses = $responses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$responses_stmt->close();

// Convert to array for easy lookup
$user_answers = [];
foreach ($existing_responses as $response) {
    $user_answers[$response['question_id']] = $response['user_answer'];
}

$conn->close();

// Calculate time remaining (30 minutes from start)
$start_time = strtotime($started_at);
$current_time = time();
$elapsed_seconds = $current_time - $start_time;
$remaining_seconds = max(0, (30 * 60) - $elapsed_seconds); // 30 minutes
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Test Exam: <?= htmlspecialchars($eligibility['topic_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        
        .exam-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .exam-title { font-size: 1.2rem; }
        .timer { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        .timer.warning { background: #e74c3c; animation: pulse 1s infinite; }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        
        .exam-container { max-width: 800px; margin: 80px auto 20px; padding: 20px; }
        
        .progress-bar { background: #e9ecef; height: 8px; border-radius: 4px; margin-bottom: 30px; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; transition: width 0.3s ease; }
        
        .question-card { background: white; border-radius: 15px; padding: 30px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .question-number { color: #667eea; font-weight: bold; margin-bottom: 10px; }
        .question-text { font-size: 1.1rem; color: #333; margin-bottom: 20px; line-height: 1.6; }
        
        .options { display: grid; gap: 12px; }
        .option { display: flex; align-items: center; padding: 15px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; transition: all 0.3s ease; }
        .option:hover { background: #e9ecef; border-color: #667eea; }
        .option.selected { background: #e8f4fd; border-color: #667eea; color: #667eea; }
        .option input[type="radio"] { margin-right: 12px; }
        
        .navigation { display: flex; justify-content: space-between; margin-top: 30px; }
        .nav-btn { padding: 12px 25px; border: none; border-radius: 25px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; }
        .prev-btn { background: #6c757d; color: white; }
        .prev-btn:hover { background: #5a6268; }
        .next-btn { background: #667eea; color: white; }
        .next-btn:hover { background: #5a67d8; }
        .submit-btn { background: #27ae60; color: white; }
        .submit-btn:hover { background: #219a52; }
        
        .question-nav { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .question-nav h4 { margin-bottom: 15px; color: #333; }
        .question-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 8px; }
        .question-dot { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; transition: all 0.3s ease; }
        .question-dot.unanswered { background: #e9ecef; color: #6c757d; }
        .question-dot.answered { background: #27ae60; color: white; }
        .question-dot.current { background: #667eea; color: white; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3); }
        
        .submit-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="exam-header">
        <div class="header-content">
            <div class="exam-title">
                <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($eligibility['topic_name']) ?> - Attempt #<?= $attempt_number ?>
            </div>
            <div class="timer" id="timer">
                <i class="fas fa-clock"></i> <span id="time-display">30:00</span>
            </div>
        </div>
    </div>
    
    <div class="exam-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
        </div>
        
        <div class="question-nav">
            <h4><i class="fas fa-list"></i> Question Navigation</h4>
            <div class="question-grid" id="question-grid">
                <?php for($i = 1; $i <= count($questions); $i++): ?>
                <div class="question-dot unanswered" onclick="goToQuestion(<?= $i ?>)" id="dot-<?= $i ?>"><?= $i ?></div>
                <?php endfor; ?>
            </div>
        </div>
        
        <form id="exam-form">
            <input type="hidden" id="attempt-id" value="<?= $attempt_id ?>">
            <input type="hidden" id="topic-id" value="<?= $topic_id ?>">
            
            <?php foreach($questions as $index => $question): ?>
            <div class="question-card" id="question-<?= $index + 1 ?>" style="display: <?= $index === 0 ? 'block' : 'none' ?>">
                <div class="question-number">Question <?= $index + 1 ?> of <?= count($questions) ?></div>
                <div class="question-text"><?= htmlspecialchars($question['question_text']) ?></div>
                
                <div class="options">
                    <?php 
                    $selected_answer = $user_answers[$question['id']] ?? '';
                    foreach(['A', 'B', 'C', 'D'] as $option): 
                    ?>
                    <label class="option <?= $selected_answer === $option ? 'selected' : '' ?>">
                        <input type="radio" 
                               name="question_<?= $question['id'] ?>" 
                               value="<?= $option ?>"
                               data-question-id="<?= $question['id'] ?>"
                               data-question-num="<?= $index + 1 ?>"
                               <?= $selected_answer === $option ? 'checked' : '' ?>
                               onchange="saveAnswer(this)">
                        <span><?= $option ?>. <?= htmlspecialchars($question['option_' . strtolower($option)]) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
        
        <div class="navigation">
            <button class="nav-btn prev-btn" id="prev-btn" onclick="previousQuestion()" disabled>
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <button class="nav-btn next-btn" id="next-btn" onclick="nextQuestion()">
                Next <i class="fas fa-chevron-right"></i>
            </button>
            <button class="nav-btn submit-btn" id="submit-btn" onclick="submitExam()" style="display: none;">
                <i class="fas fa-check"></i> Submit Exam
            </button>
        </div>
        
        <div class="submit-warning" id="submit-warning" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            You have unanswered questions. Please review before submitting.
        </div>
    </div>
    
    <script>
        let currentQuestion = 1;
        const totalQuestions = <?= count($questions) ?>;
        let timeRemaining = <?= $remaining_seconds ?>;
        let answered = new Set();
        
        // Initialize answered questions
        <?php foreach($user_answers as $qId => $answer): ?>
        const questionNum<?= $qId ?> = <?php 
            foreach($questions as $idx => $q) {
                if ($q['id'] == $qId) echo $idx + 1;
            }
        ?>;
        if (questionNum<?= $qId ?>) {
            answered.add(questionNum<?= $qId ?>);
            document.getElementById('dot-' + questionNum<?= $qId ?>).className = 'question-dot answered';
        }
        <?php endforeach; ?>
        
        // Timer
        function updateTimer() {
            if (timeRemaining <= 0) {
                submitExam(true); // Auto-submit
                return;
            }
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('time-display').textContent = display;
            
            const timerElement = document.getElementById('timer');
            if (timeRemaining <= 300) { // 5 minutes warning
                timerElement.className = 'timer warning';
            }
            
            timeRemaining--;
        }
        
        setInterval(updateTimer, 1000);
        updateTimer();
        
        // Navigation
        function updateNavigation() {
            document.getElementById('prev-btn').disabled = currentQuestion === 1;
            
            if (currentQuestion === totalQuestions) {
                document.getElementById('next-btn').style.display = 'none';
                document.getElementById('submit-btn').style.display = 'inline-block';
            } else {
                document.getElementById('next-btn').style.display = 'inline-block';
                document.getElementById('submit-btn').style.display = 'none';
            }
            
            // Update progress
            const progress = (answered.size / totalQuestions) * 100;
            document.getElementById('progress-fill').style.width = progress + '%';
            
            // Update current question dot
            document.querySelectorAll('.question-dot').forEach(dot => {
                dot.classList.remove('current');
            });
            document.getElementById(`dot-${currentQuestion}`).classList.add('current');
        }
        
        function goToQuestion(num) {
            document.getElementById(`question-${currentQuestion}`).style.display = 'none';
            document.getElementById(`question-${num}`).style.display = 'block';
            currentQuestion = num;
            updateNavigation();
        }
        
        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                goToQuestion(currentQuestion + 1);
            }
        }
        
        function previousQuestion() {
            if (currentQuestion > 1) {
                goToQuestion(currentQuestion - 1);
            }
        }
        
        // Save answer
        function saveAnswer(radio) {
            const questionId = radio.dataset.questionId;
            const questionNum = parseInt(radio.dataset.questionNum);
            const answer = radio.value;
            
            // Update UI
            document.querySelectorAll(`input[name="question_${questionId}"]`).forEach(input => {
                input.closest('.option').classList.remove('selected');
            });
            radio.closest('.option').classList.add('selected');
            
            // Update answered set
            answered.add(questionNum);
            document.getElementById(`dot-${questionNum}`).className = 'question-dot answered';
            
            // Save to database
            fetch('save_post_test_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attempt_id: document.getElementById('attempt-id').value,
                    question_id: questionId,
                    answer: answer
                })
            });
            
            updateNavigation();
        }
        
        // Submit exam
        function submitExam(autoSubmit = false) {
            const unanswered = totalQuestions - answered.size;
            
            if (!autoSubmit && unanswered > 0) {
                document.getElementById('submit-warning').style.display = 'block';
                if (!confirm(`You have ${unanswered} unanswered questions. Are you sure you want to submit?`)) {
                    return;
                }
            }
            
            if (autoSubmit) {
                alert('Time is up! Your exam will be submitted automatically.');
            }
            
            // Submit exam
            fetch('submit_post_test.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attempt_id: document.getElementById('attempt-id').value,
                    topic_id: document.getElementById('topic-id').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = `post_test_results.php?attempt_id=${document.getElementById('attempt-id').value}`;
                } else {
                    alert('Error submitting exam: ' + data.message);
                }
            });
        }
        
        // Initialize
        updateNavigation();
        
        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html>