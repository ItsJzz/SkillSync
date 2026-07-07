<?php
// simplified_post_test_exam.php - Using existing questions table
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$new_attempt = isset($_GET['new_attempt']) ? $_GET['new_attempt'] : 0;

if ($topic_id <= 0) {
    die("Invalid topic ID");
}

// Get user's current class level from assessment_details
$user_level_stmt = $conn->prepare("
    SELECT JSON_UNQUOTE(JSON_EXTRACT(assessment_details, '$.class_level')) as class_level 
    FROM students 
    WHERE user_id = ?
");
$user_level_stmt->bind_param("i", $user_id);
$user_level_stmt->execute();
$user_level_result = $user_level_stmt->get_result()->fetch_assoc();
$user_level_stmt->close();

// Default to Beginner if no level found
$user_class_level = $user_level_result['class_level'] ?? 'Beginner';
if (empty($user_class_level) || !in_array($user_class_level, ['Beginner', 'Intermediate', 'Expert'])) {
    $user_class_level = 'Beginner';
}

// Check if user completed all 5 levels for this topic
$completion_check = $conn->prepare("
    SELECT COUNT(DISTINCT level) as completed_levels 
    FROM save_progress 
    WHERE user_id = ? AND topic_id = ? 
    GROUP BY user_id, topic_id
");
$completion_check->bind_param("ii", $user_id, $topic_id);
$completion_check->execute();
$completion_result = $completion_check->get_result()->fetch_assoc();
$completion_check->close();

if (!$completion_result || $completion_result['completed_levels'] < 5) {
    die("You must complete all 5 activity levels before taking the post-test.");
}

// Get topic info
$topic_stmt = $conn->prepare("SELECT t.name, s.name as subject_name FROM topics t JOIN subjects s ON t.subject_id = s.id WHERE t.id = ?");
$topic_stmt->bind_param("i", $topic_id);
$topic_stmt->execute();
$topic_info = $topic_stmt->get_result()->fetch_assoc();
$topic_stmt->close();

// Check for active attempt (unless user requested new attempt)
if ($new_attempt) {
    // Mark any existing incomplete attempt as completed
    $mark_complete_stmt = $conn->prepare("
        UPDATE user_post_test_attempts 
        SET completed_at = NOW(), score = 0 
        WHERE user_id = ? AND topic_id = ? AND completed_at IS NULL
    ");
    $mark_complete_stmt->bind_param("ii", $user_id, $topic_id);
    $mark_complete_stmt->execute();
    $mark_complete_stmt->close();
    $active_attempt = null;
} else {
    $active_attempt_stmt = $conn->prepare("
        SELECT * FROM user_post_test_attempts 
        WHERE user_id = ? AND topic_id = ? AND completed_at IS NULL
        ORDER BY id DESC LIMIT 1
    ");
    $active_attempt_stmt->bind_param("ii", $user_id, $topic_id);
    $active_attempt_stmt->execute();
    $active_attempt = $active_attempt_stmt->get_result()->fetch_assoc();
    $active_attempt_stmt->close();
}

// Create new attempt if none exists
if (!$active_attempt) {
    $next_attempt_stmt = $conn->prepare("
        SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt 
        FROM user_post_test_attempts 
        WHERE user_id = ? AND topic_id = ?
    ");
    $next_attempt_stmt->bind_param("ii", $user_id, $topic_id);
    $next_attempt_stmt->execute();
    $next_attempt = $next_attempt_stmt->get_result()->fetch_assoc()['next_attempt'];
    $next_attempt_stmt->close();
    
    // Get 10 random quiz questions (user's current level)
    $quiz_questions_stmt = $conn->prepare("
        SELECT id FROM questions 
        WHERE topic_id = ? AND question_type = 'Quiz question' AND class_level = ?
        ORDER BY RAND() 
        LIMIT 10
    ");
    $quiz_questions_stmt->bind_param("is", $topic_id, $user_class_level);
    $quiz_questions_stmt->execute();
    $quiz_questions = $quiz_questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $quiz_questions_stmt->close();
    
    // Get 10 random simulation questions (user's current level)
    $sim_questions_stmt = $conn->prepare("
        SELECT id FROM questions 
        WHERE topic_id = ? AND question_type = 'Simulation question' AND class_level = ?
        ORDER BY RAND() 
        LIMIT 10
    ");
    $sim_questions_stmt->bind_param("is", $topic_id, $user_class_level);
    $sim_questions_stmt->execute();
    $sim_questions = $sim_questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $sim_questions_stmt->close();
    
    // Combine quiz and simulation questions
    $random_questions = array_merge($quiz_questions, $sim_questions);
    
    // Shuffle the combined array to randomize order
    shuffle($random_questions);
    
    $question_ids = array_map(function($q) { return $q['id']; }, $random_questions);
    $question_ids_json = json_encode($question_ids);
    
    $create_attempt_stmt = $conn->prepare("
        INSERT INTO user_post_test_attempts (user_id, topic_id, attempt_number, started_at, question_ids) 
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $create_attempt_stmt->bind_param("iiis", $user_id, $topic_id, $next_attempt, $question_ids_json);
    $create_attempt_stmt->execute();
    $attempt_id = $conn->insert_id;
    $create_attempt_stmt->close();
    
    $stored_question_ids = $question_ids;
} else {
    $attempt_id = $active_attempt['id'];
    // Get the stored question IDs for this attempt
    $stored_question_ids = json_decode($active_attempt['question_ids'], true);
}

// Get questions in the exact order stored for this attempt
if (!empty($stored_question_ids)) {
    $placeholders = str_repeat('?,', count($stored_question_ids) - 1) . '?';
    $questions_stmt = $conn->prepare("
        SELECT * FROM questions 
        WHERE id IN ($placeholders)
        ORDER BY FIELD(id, $placeholders)
    ");
    $params = array_merge($stored_question_ids, $stored_question_ids);
    $questions_stmt->bind_param(str_repeat('i', count($params)), ...$params);
    $questions_stmt->execute();
    $questions = $questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $questions_stmt->close();
} else {
    // Fallback: get random questions if no stored IDs (10 quiz + 10 simulation, user's level)
    $quiz_fallback_stmt = $conn->prepare("
        SELECT * FROM questions 
        WHERE topic_id = ? AND question_type = 'Quiz question' AND class_level = ?
        ORDER BY RAND() 
        LIMIT 10
    ");
    $quiz_fallback_stmt->bind_param("is", $topic_id, $user_class_level);
    $quiz_fallback_stmt->execute();
    $quiz_fallback = $quiz_fallback_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $quiz_fallback_stmt->close();
    
    $sim_fallback_stmt = $conn->prepare("
        SELECT * FROM questions 
        WHERE topic_id = ? AND question_type = 'Simulation question' AND class_level = ?
        ORDER BY RAND() 
        LIMIT 10
    ");
    $sim_fallback_stmt->bind_param("is", $topic_id, $user_class_level);
    $sim_fallback_stmt->execute();
    $sim_fallback = $sim_fallback_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $sim_fallback_stmt->close();
    
    $questions = array_merge($quiz_fallback, $sim_fallback);
    shuffle($questions);
}

if (empty($questions)) {
    die("No questions available for this topic.");
}

// Get existing responses
$responses_stmt = $conn->prepare("
    SELECT question_id, selected_answer 
    FROM user_post_test_responses 
    WHERE attempt_id = ?
");
$responses_stmt->bind_param("i", $attempt_id);
$responses_stmt->execute();
$responses_result = $responses_stmt->get_result();

$existing_responses = [];
while ($response = $responses_result->fetch_assoc()) {
    $existing_responses[$response['question_id']] = $response['selected_answer'];
}
$responses_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Test: <?php echo htmlspecialchars($topic_info['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .exam-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
        }
        .timer-box {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .question-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 25px;
        }
        .option-btn {
            width: 100%;
            text-align: left;
            margin: 8px 0;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .option-btn:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .option-btn.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .question-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
            gap: 8px;
            margin: 20px 0;
        }
        .nav-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .nav-btn.answered {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .nav-btn.current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        #timer {
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="exam-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-clipboard-check me-3"></i>Post-Test Assessment</h2>
                    <p class="mb-0"><?php echo htmlspecialchars($topic_info['subject_name'] . ' - ' . $topic_info['name']); ?></p>
                    <small style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 12px; display: inline-block; margin-top: 8px;">
                        <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($user_class_level); ?> Level | 
                        <i class="fas fa-question-circle"></i> 10 Quiz + 
                        <i class="fas fa-code"></i> 10 Simulation
                    </small>
                </div>
                <div class="col-md-3">
                    <div class="timer-box">
                        <div><i class="fas fa-clock"></i> Time Remaining</div>
                        <div id="timer">30:00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php if ($active_attempt): ?>
                        <button class="btn btn-warning btn-sm" onclick="startNewAttempt()">
                            <i class="fas fa-refresh"></i> Start New Attempt
                        </button>
                    <?php endif; ?>
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
                <small class="text-muted">Progress: <span id="progressText">0/20</span></small>
            </div>
        </div>

        <!-- Question Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <h6>Questions:</h6>
                <div class="question-nav" id="questionNav">
                    <?php for ($i = 1; $i <= count($questions); $i++): ?>
                        <div class="nav-btn" data-question="<?php echo $i; ?>" onclick="goToQuestion(<?php echo $i; ?>)">
                            <?php echo $i; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Questions -->
        <div id="questionsContainer">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card" id="question<?php echo $index + 1; ?>" style="display: <?php echo $index == 0 ? 'block' : 'none'; ?>;">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5>Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></h5>
                        <span class="badge bg-primary">Topic: <?php echo htmlspecialchars($topic_info['name']); ?></span>
                    </div>
                    
                    <p class="fs-6 mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                    
                    <?php if (!empty($question['code_snippet'])): ?>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre><code><?php echo htmlspecialchars($question['code_snippet']); ?></code></pre>
                        </div>
                    <?php endif; ?>
                    
                    <div class="options">
                        <?php 
                        $options = ['A' => $question['option_a'], 'B' => $question['option_b'], 'C' => $question['option_c']];
                        foreach ($options as $letter => $text): 
                            $selected = isset($existing_responses[$question['id']]) && $existing_responses[$question['id']] == $letter;
                        ?>
                            <button class="option-btn <?php echo $selected ? 'selected' : ''; ?>" 
                                    data-question="<?php echo $question['id']; ?>" 
                                    data-answer="<?php echo $letter; ?>"
                                    onclick="selectAnswer(this)">
                                <strong><?php echo $letter; ?>.</strong> <?php echo htmlspecialchars($text); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigation -->
        <div class="row mt-4">
            <div class="col-6">
                <button class="btn btn-outline-secondary" id="prevBtn" onclick="previousQuestion()">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
            </div>
            <div class="col-6 text-end">
                <button class="btn btn-outline-primary" id="nextBtn" onclick="nextQuestion()">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
                <button class="btn btn-success" id="submitBtn" onclick="submitExam()">
                    <i class="fas fa-check"></i> Submit Exam
                </button>
                <!-- Emergency submit button - always visible on last questions -->
                <button class="btn btn-warning ms-2" id="forceSubmitBtn" onclick="submitExam()" style="display: none;">
                    <i class="fas fa-paper-plane"></i> Force Submit
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentQuestion = 1;
        const totalQuestions = <?php echo count($questions); ?>;
        const attemptId = <?php echo $attempt_id; ?>;
        const answers = {};

        // Load existing answers
        <?php foreach ($existing_responses as $qid => $answer): ?>
            answers[<?php echo $qid; ?>] = '<?php echo $answer; ?>';
        <?php endforeach; ?>

        // Timer (30 minutes)
        let timeLeft = 30 * 60; // 30 minutes in seconds
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 300) { // Last 5 minutes
                document.getElementById('timer').style.color = '#dc3545';
            }
            
            if (timeLeft <= 0) {
                submitExam();
                return;
            }
            timeLeft--;
        }
        
        setInterval(updateTimer, 1000);

        function selectAnswer(button) {
            const questionId = button.dataset.question;
            const answer = button.dataset.answer;
            
            // Remove selected class from siblings
            button.parentNode.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Add selected class to clicked button
            button.classList.add('selected');
            
            // Store answer
            answers[questionId] = answer;
            
            // Save to database
            saveAnswer(questionId, answer);
            
            // Update navigation
            updateProgress();
            
            // Auto-advance to next question after a brief delay (except on last question)
            if (currentQuestion < totalQuestions) {
                setTimeout(() => {
                    nextQuestion();
                }, 800); // 800ms delay for smooth user experience
            }
        }

        function saveAnswer(questionId, answer) {
            fetch('save_simplified_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `attempt_id=${attemptId}&question_id=${questionId}&selected_answer=${answer}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Answer saved:', data);
                } else {
                    console.error('Failed to save answer:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving answer:', error);
            });
        }

        function goToQuestion(questionNum) {
            document.getElementById(`question${currentQuestion}`).style.display = 'none';
            document.getElementById(`question${questionNum}`).style.display = 'block';
            currentQuestion = questionNum;
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

        function updateNavigation() {
            document.getElementById('prevBtn').style.display = currentQuestion === 1 ? 'none' : 'inline-block';
            document.getElementById('nextBtn').style.display = currentQuestion === totalQuestions ? 'none' : 'inline-block';
            document.getElementById('submitBtn').style.display = currentQuestion === totalQuestions ? 'inline-block' : 'none';
            
            // Show force submit on questions 18, 19, 20
            document.getElementById('forceSubmitBtn').style.display = currentQuestion >= 18 ? 'inline-block' : 'none';
            
            // Update question nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('current');
                if (parseInt(btn.dataset.question) === currentQuestion) {
                    btn.classList.add('current');
                }
            });
        }

        function updateProgress() {
            const answeredCount = Object.keys(answers).length;
            const percentage = (answeredCount / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressText').textContent = `${answeredCount}/${totalQuestions}`;
            
            // Update nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                const questionIndex = parseInt(btn.dataset.question);
                const questionId = <?php echo json_encode(array_column($questions, 'id')); ?>[questionIndex - 1];
                if (answers[questionId]) {
                    btn.classList.add('answered');
                }
            });
        }

        function submitExam() {
            if (confirm('Are you sure you want to submit your exam? This action cannot be undone.')) {
                window.location.href = `simplified_submit_post_test.php?attempt_id=${attemptId}`;
            }
        }

        function startNewAttempt() {
            if (confirm('Are you sure you want to start a new attempt? This will discard your current progress and give you a new set of randomized questions.')) {
                window.location.href = `simplified_post_test_exam.php?topic_id=<?php echo $topic_id; ?>&new_attempt=1`;
            }
        }

        // Initialize
        updateNavigation();
        updateProgress();
    </script>
</body>
</html>