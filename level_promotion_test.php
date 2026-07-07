<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get parameters
if (!isset($_GET['subject']) || !isset($_GET['level'])) {
    die("Invalid parameters. Missing subject or level.");
}

$subjectCode = $_GET['subject'];
$currentLevel = $_GET['level']; // Should be 'Beginner' or 'Intermediate'

// Determine target level
$targetLevel = ($currentLevel === 'Beginner') ? 'Intermediate' : 'Expert';

// Verify subject
$subStmt = $conn->prepare("SELECT id, name FROM subjects WHERE code = ?");
$subStmt->bind_param("s", $subjectCode);
$subStmt->execute();
$subject = $subStmt->get_result()->fetch_assoc();
$subStmt->close();

if (!$subject) {
    // Better error message showing what we're looking for
    $allSubjects = $conn->query("SELECT code, name FROM subjects")->fetch_all(MYSQLI_ASSOC);
    $subjectList = array_map(function($s) { return $s['code'] . ' (' . $s['name'] . ')'; }, $allSubjects);
    die("
    <div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>
        <h2 style='color: #e74c3c;'>⚠️ Invalid Subject</h2>
        <p>Subject code '<strong>" . htmlspecialchars($subjectCode) . "</strong>' not found.</p>
        <p><small>Available subjects: " . implode(', ', $subjectList) . "</small></p>
        <a href='student_dashboard.php' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 6px;'>Return to Dashboard</a>
    </div>");
}
$subject_id = $subject['id'];

// Verify student's current level from database
$levelCheck = $conn->prepare("SELECT assessment_details FROM students WHERE id = ? OR user_id = ? LIMIT 1");
$levelCheck->bind_param("ii", $student_id, $student_id);
$levelCheck->execute();
$levelResult = $levelCheck->get_result()->fetch_assoc();
$levelCheck->close();

if ($levelResult && !empty($levelResult['assessment_details'])) {
    $assessmentDetails = json_decode($levelResult['assessment_details'], true);
    $storedLevel = $assessmentDetails['class_level'] ?? 'Beginner';
    
    if ($storedLevel !== $currentLevel) {
        die("
        <div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>
            <h2 style='color: #e74c3c;'>⚠️ Invalid Level</h2>
            <p>Your current level doesn't match. Please return to dashboard.</p>
            <a href='student_dashboard.php' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 6px;'>Return to Dashboard</a>
        </div>");
    }
}

// Get topics for this subject
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
// Fetch questions with proper distribution for level promotion
// 10 questions per topic:
//   - Beginner → Intermediate: 7 Intermediate + 3 Beginner
//   - Intermediate → Expert: 7 Expert + 3 Intermediate
// ----------------------------
$questions_by_topic = [];
$questionsPerTopic = 10;
$totalQuestions = $questionsPerTopic * $topicCount;

foreach ($topics as $topic) {
    $tid = $topic['id'];
    $questions_by_topic[$tid] = [];
    
    // Fetch 7 questions from target level (the level they want to reach)
    $stmt = $conn->prepare("
        SELECT * FROM questions
        WHERE topic_id = ? AND class_level = ?
        ORDER BY RAND()
        LIMIT 7
    ");
    $stmt->bind_param("is", $tid, $targetLevel);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions_by_topic[$tid][] = $row;
    }
    $stmt->close();
    
    // Fetch 3 questions from current level (to verify they still understand basics)
    // For Beginner→Intermediate: get 3 Beginner questions
    // For Intermediate→Expert: get 3 Intermediate questions
    $stmt = $conn->prepare("
        SELECT * FROM questions
        WHERE topic_id = ? AND class_level = ?
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->bind_param("is", $tid, $currentLevel);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions_by_topic[$tid][] = $row;
    }
    $stmt->close();
    
    // Shuffle questions within each topic
    shuffle($questions_by_topic[$tid]);
}

// Build correct answer key
$correctAnswers = [];
$pointsPerQuestion = 100 / $totalQuestions; // Equal points per question

foreach ($questions_by_topic as $topic_id => $topicQs) {
    foreach ($topicQs as $q) {
        $correctAnswers["q".$q['id']] = [
            "answer" => $q['correct_option'],
            "topic_id" => $topic_id,
            "class_level" => $q['class_level'],
            "question_type" => $q['question_type'],
            "points" => $pointsPerQuestion
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level Promotion Test: <?= htmlspecialchars($currentLevel) ?> → <?= htmlspecialchars($targetLevel) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .exam-header {
            background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%);
            color: white;
            padding: 20px 0;
        }
        .timer-box {
            background: rgba(255,255,255,0.2);
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
            border-left: 5px solid #f39c12;
        }
        .topic-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid #f39c12;
            margin-bottom: 20px;
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
            cursor: pointer;
            font-size: 1rem;
        }
        .option-btn:hover {
            border-color: #f39c12;
            background: #fff8e7;
            transform: translateX(5px);
        }
        .option-btn.selected {
            border-color: #f39c12;
            background: #fff3cd;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(243, 156, 18, 0.3);
        }
        .code-snippet {
            background: #f8f9fa;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            border-radius: 5px;
        }
        .promotion-badge {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #f39c12, #f1c40f);
            color: white;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        /* Celebration Modal Styles */
        .celebration-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }
        
        .celebration-content {
            position: relative;
            background: white;
            margin: 5% auto;
            padding: 40px;
            width: 90%;
            max-width: 600px;
            border-radius: 20px;
            text-align: center;
            animation: slideDown 0.5s ease;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
        }
        
        .celebration-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }
        
        .celebration-icon.success {
            color: #27ae60;
        }
        
        .celebration-icon.fail {
            color: #e74c3c;
        }
        
        .celebration-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .celebration-score {
            font-size: 48px;
            font-weight: bold;
            color: #f39c12;
            margin: 20px 0;
        }
        
        .celebration-message {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .celebration-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .celebration-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
        
        .celebration-btn.retry {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .celebration-btn.retry:hover {
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        
        /* Confetti Animation */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f39c12;
            position: absolute;
            animation: confetti-fall 3s linear forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-100px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Celebration Modal -->
    <div id="celebrationModal" class="celebration-modal">
        <div class="celebration-content">
            <div class="celebration-icon" id="celebrationIcon">🎉</div>
            <h2 class="celebration-title" id="celebrationTitle">Congratulations!</h2>
            <div class="celebration-score" id="celebrationScore">78.0%</div>
            <p class="celebration-message" id="celebrationMessage">
                You've passed the promotion test! You're now ready for the next level!
            </p>
            <button class="celebration-btn" id="celebrationBtn">
                <i class="fas fa-home"></i> Go to Dashboard
            </button>
        </div>
    </div>
    <div class="exam-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>
                        <i class="fas fa-trophy me-3"></i>Level Promotion Test
                        <span class="promotion-badge"><?= htmlspecialchars($currentLevel) ?> → <?= htmlspecialchars($targetLevel) ?></span>
                    </h2>
                    <p class="mb-0">Pass with 77% or higher to advance to <?= htmlspecialchars($targetLevel) ?> Level</p>
                </div>
                <div class="col-md-4">
                    <div class="timer-box">
                        <h4 class="mb-1" id="timer">60:00</h4>
                        <small>Time Remaining</small>
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
                    <div class="progress-bar bg-warning" id="progressBar" style="width: 0%"></div>
                </div>
                <small class="text-muted">Progress: <span id="progressText">0/<?= $totalQuestions ?></span></small>
            </div>
        </div>

        <!-- Instructions -->
        <div class="alert alert-warning">
            <h5><i class="fas fa-info-circle"></i> Important Instructions</h5>
            <ul class="mb-0">
                <li>This is a <strong>Level Promotion Test</strong> - you need <strong>77% or higher</strong> to advance</li>
                <li><strong>Question Distribution per topic:</strong> 7 <?= htmlspecialchars($targetLevel) ?> questions + 3 <?= htmlspecialchars($currentLevel) ?> questions</li>
                <li>This tests both your understanding of new concepts and mastery of basics</li>
                <li>You have <strong>60 minutes</strong> to complete all <?= $totalQuestions ?> questions</li>
                <li>Each question carries equal weight</li>
                <li>Review your answers before submitting - you can only take this test once</li>
            </ul>
        </div>

        <!-- Questions -->
        <form id="promotionTestForm">
            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <input type="hidden" name="current_level" value="<?= htmlspecialchars($currentLevel) ?>">
            <input type="hidden" name="target_level" value="<?= htmlspecialchars($targetLevel) ?>">
            
            <?php 
            $globalQuestionIndex = 1;
            foreach ($topics as $topic): 
                if (!empty($questions_by_topic[$topic['id']])):
            ?>
            
            <div class="topic-header">
                <h4><i class="fas fa-book"></i> <?= htmlspecialchars($topic['name']) ?></h4>
                <small class="text-muted"><?= count($questions_by_topic[$topic['id']]) ?> questions</small>
            </div>
            
            <?php foreach ($questions_by_topic[$topic['id']] as $q): ?>
            <div class="question-card" id="question<?= $globalQuestionIndex ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5>Question <?= $globalQuestionIndex ?></h5>
                    <span class="badge bg-<?= $q['class_level'] === 'Intermediate' ? 'warning' : 'danger' ?>">
                        <?= htmlspecialchars($q['class_level']) ?>
                    </span>
                </div>
                
                <p><?= htmlspecialchars($q['question_text']) ?></p>
                
                <?php if (!empty($q['code_snippet'])): ?>
                <div class="code-snippet"><?= htmlspecialchars($q['code_snippet']) ?></div>
                <?php endif; ?>
                
                <div class="options mt-3">
                    <button type="button" class="option-btn" 
                            data-question="q<?= $q['id'] ?>" 
                            data-answer="A"
                            data-global-index="<?= $globalQuestionIndex ?>"
                            onclick="selectAnswer(this)">
                        <strong>A.</strong> <?= htmlspecialchars($q['option_a']) ?>
                    </button>
                    <button type="button" class="option-btn" 
                            data-question="q<?= $q['id'] ?>" 
                            data-answer="B"
                            data-global-index="<?= $globalQuestionIndex ?>"
                            onclick="selectAnswer(this)">
                        <strong>B.</strong> <?= htmlspecialchars($q['option_b']) ?>
                    </button>
                    <button type="button" class="option-btn" 
                            data-question="q<?= $q['id'] ?>" 
                            data-answer="C"
                            data-global-index="<?= $globalQuestionIndex ?>"
                            onclick="selectAnswer(this)">
                        <strong>C.</strong> <?= htmlspecialchars($q['option_c']) ?>
                    </button>
                </div>
            </div>
            <?php 
                $globalQuestionIndex++;
                endforeach; 
            ?>
            
            <?php 
                endif;
            endforeach; 
            ?>

            <!-- Submit Button -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-warning btn-lg px-5">
                        <i class="fas fa-check"></i> Submit Promotion Test
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const correctAnswers = <?= json_encode($correctAnswers) ?>;
        const totalQuestions = <?= $totalQuestions ?>;
        const answers = {};
        let completedItems = 0;

        // Timer (60 minutes)
        let timeLeft = 60 * 60; // 60 minutes in seconds
        const timerElement = document.getElementById('timer');
        
        const timerInterval = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Submitting your test...');
                document.getElementById('promotionTestForm').dispatchEvent(new Event('submit'));
            }
        }, 1000);

        function selectAnswer(button) {
            const questionName = button.dataset.question;
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
            }
            
            // Auto-scroll to next question
            setTimeout(() => {
                const currentIndex = parseInt(button.dataset.globalIndex);
                const nextQuestion = document.getElementById(`question${currentIndex + 1}`);
                if (nextQuestion) {
                    nextQuestion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        }

        function updateProgress() {
            const percentage = (completedItems / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressText').textContent = `${completedItems}/${totalQuestions}`;
        }

        // Form submission
        document.getElementById('promotionTestForm').addEventListener('submit', e => {
            e.preventDefault();
            
            // Check if all questions are answered
            if (Object.keys(answers).length < totalQuestions) {
                if (!confirm(`You have only answered ${Object.keys(answers).length} out of ${totalQuestions} questions. Submit anyway?`)) {
                    return;
                }
            }
            
            // Calculate score and collect detailed analysis data
            let correctCount = 0;
            const analysisDetails = [];
            
            Object.keys(answers).forEach(qName => {
                const studentAnswer = answers[qName];
                const correctAnswer = correctAnswers[qName].answer;
                const isCorrect = studentAnswer === correctAnswer;
                
                if (isCorrect) {
                    correctCount++;
                }
                
                // Store detailed info for each question
                analysisDetails.push({
                    question_id: qName.replace('q', ''),
                    topic_id: correctAnswers[qName].topic_id,
                    topic_name: getTopicName(correctAnswers[qName].topic_id),
                    class_level: correctAnswers[qName].class_level,
                    question_type: correctAnswers[qName].question_type,
                    student_answer: studentAnswer,
                    correct_answer: correctAnswer,
                    is_correct: isCorrect,
                    points: correctAnswers[qName].points
                });
            });
            
            const score = (correctCount / totalQuestions) * 100;
            const passed = score >= 77;
            
            // Prepare payload with detailed analysis data
            const formData = new FormData(e.target);
            const payload = {
                subject_id: formData.get('subject_id'),
                current_level: formData.get('current_level'),
                target_level: formData.get('target_level'),
                answers: answers,
                score: score,
                passed: passed,
                total_questions: totalQuestions,
                correct_count: correctCount,
                details: analysisDetails
            };
            
            // Send to server
            fetch("save_level_promotion_test.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showCelebration(passed, score, formData.get('target_level'));
                } else {
                    alert('Error: ' + (data.message || 'Failed to save results'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error submitting test. Please try again.');
            });
        });
        
        // Helper function to get topic name by topic_id
        const topicNames = {};
        <?php foreach ($topics as $topic): ?>
        topicNames[<?= $topic['id'] ?>] = "<?= addslashes($topic['name']) ?>";
        <?php endforeach; ?>
        
        function getTopicName(topicId) {
            return topicNames[topicId] || 'Unknown Topic';
        }
        
        // Show celebration modal with confetti
        function showCelebration(passed, score, targetLevel) {
            const modal = document.getElementById('celebrationModal');
            const icon = document.getElementById('celebrationIcon');
            const title = document.getElementById('celebrationTitle');
            const scoreEl = document.getElementById('celebrationScore');
            const message = document.getElementById('celebrationMessage');
            const btn = document.getElementById('celebrationBtn');
            
            // Update content based on pass/fail
            if (passed) {
                icon.textContent = '🎉';
                icon.className = 'celebration-icon success';
                title.textContent = '🎊 Congratulations! 🎊';
                scoreEl.textContent = score.toFixed(1) + '%';
                scoreEl.style.color = '#27ae60';
                message.innerHTML = `
                    <strong>You've passed the Level Promotion Test!</strong><br>
                    You've been promoted to <strong>${targetLevel}</strong> level!<br>
                    Your progress has been reset to 0% - time to start your new journey! 🚀
                `;
                btn.className = 'celebration-btn';
                btn.innerHTML = '<i class="fas fa-rocket"></i> Go to Dashboard';
                btn.onclick = () => window.location.href = 'student_dashboard.php';
                
                // Create confetti
                createConfetti();
            } else {
                icon.textContent = '�';
                icon.className = 'celebration-icon fail';
                title.textContent = 'Let\'s Analyze Your Results';
                scoreEl.textContent = score.toFixed(1) + '%';
                scoreEl.style.color = '#e74c3c';
                message.innerHTML = `
                    <strong>You scored ${score.toFixed(1)}%</strong><br>
                    You need <strong>77% or higher</strong> to advance to ${targetLevel}.<br><br>
                    📊 We've prepared a <strong>personalized learning path</strong> based on your performance!<br>
                    Let's analyze what went wrong and how to improve. 💪
                `;
                btn.className = 'celebration-btn retry';
                btn.innerHTML = '<i class="fas fa-chart-line"></i> View Analysis & Learning Path';
                btn.onclick = () => window.location.href = 'promotion_test_analysis.php';
            }
            
            // Show modal
            modal.style.display = 'block';
        }
        
        // Create confetti animation
        function createConfetti() {
            const colors = ['#f39c12', '#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#1abc9c'];
            const confettiCount = 100;
            
            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    // Remove confetti after animation
                    setTimeout(() => confetti.remove(), 5000);
                }, i * 30);
            }
        }
        
        // Note: Button onclick handlers are set in showCelebration() function
    </script>
</body>
</html>
