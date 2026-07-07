<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once '../db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$message = "";
$error = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_id = intval($_POST['topic_id']);
    $rawInput = trim($_POST['questions']);
    $lines = preg_split('/\r\n|\r|\n/', $rawInput);

    $questions = [];
    $current = null;
    $options = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "") continue;

        // Normalize bullets (•)
        $line = preg_replace('/^•\s*/u', '', $line);

        // Start of new question
        if (preg_match('/^\d+\./', $line)) {
            if ($current) {
                $current['options'] = $options;
                $questions[] = $current;
            }
            $current = ['question' => preg_replace('/^\d+\.\s*/', '', $line), 'code' => ""];
            $options = [];
        }
        // Code snippet (looks like class or public or System.out etc.)
        elseif (preg_match('/^(class|public|private|protected|System|Phone|Animal|Shape|Vehicle)/i', $line)) {
            $current['code'] .= ($current['code'] ? "\n" : "") . $line;
        }
        // Options
        elseif (preg_match('/^A\.\s*(.+)$/i', $line, $m)) {
            $options['A'] = $m[1];
        } elseif (preg_match('/^B\.\s*(.+)$/i', $line, $m)) {
            $options['B'] = $m[1];
        } elseif (preg_match('/^C\.\s*(.+)$/i', $line, $m)) {
            $options['C'] = $m[1];
        }
        // Correct answer
        elseif (preg_match('/^✅\s*Answer:\s*([A-C])/ui', $line, $m)) {
            $current['answer'] = $options[$m[1]] ?? '';
        } else {
            // Fallback: append to question text
            $current['question'] .= " " . $line;
        }
    }

    // Push last question
    if ($current) {
        $current['options'] = $options;
        $questions[] = $current;
    }

    if (empty($questions)) {
        $error = "No valid questions were parsed. Please check your format.";
    } else {
        // Insert into DB
        $stmt = $conn->prepare("
            INSERT INTO questions (topic_id, question_text, code_snippet, option_a, option_b, option_c, correct_option, class_level, question_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $success_count = 0;
        foreach ($questions as $q) {
            $question   = $q['question'];
            $code       = $q['code'];
            $option_a   = $q['options']['A'] ?? '';
            $option_b   = $q['options']['B'] ?? '';
            $option_c   = $q['options']['C'] ?? '';
            $correct_text = $q['answer'] ?? '';

            // Convert full text answer to option letter (A, B, or C)
            $correct_letter = '';
            if ($correct_text === $option_a) {
                $correct_letter = 'A';
            } elseif ($correct_text === $option_b) {
                $correct_letter = 'B';
            } elseif ($correct_text === $option_c) {
                $correct_letter = 'C';
            } else {
                // If no match found, check if it's already a letter
                if (in_array($correct_text, ['A', 'B', 'C'])) {
                    $correct_letter = $correct_text;
                } else {
                    // Default to A if no match
                    $correct_letter = 'A';
                }
            }

            // Get values from POST or use defaults
            $class_level = $_POST['class_level'] ?? 'Beginner';
            $question_type = $_POST['question_type'] ?? 'Quiz question';
            
            $stmt->bind_param(
                "issssssss",
                $topic_id,
                $question,
                $code,
                $option_a,
                $option_b,
                $option_c,
                $correct_letter,
                $class_level,
                $question_type
            );
            
            if ($stmt->execute()) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            $message = "Successfully added $success_count question(s) to the database!";
        } else {
            $error = "Failed to add questions to the database.";
        }
    }
}

// Get subjects for dropdown
$subjects = [];
$sql = "SELECT id, name FROM subjects ORDER BY name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Questions - SkillSync Admin</title>
<link rel="shortcut icon" sizes="32x32" href="../LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex; 
    background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
    min-height: 100vh; 
}

/* Admin Sidebar */
.admin-sidebar { 
    width: 260px; 
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
    color: white; 
    height: 100vh; 
    padding: 20px 0; 
    position: fixed; 
    box-shadow: 4px 0 20px rgba(75, 139, 110, 0.2);
    border-right: 2px solid rgba(107, 175, 146, 0.3);
    overflow-y: auto;
}

.admin-sidebar .logo { 
    text-align: center; 
    margin-bottom: 30px; 
    padding: 20px; 
}

.admin-sidebar .logo img { 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    border: 3px solid #6BAF92;
    box-shadow: 0 4px 15px rgba(107, 175, 146, 0.4);
}

.admin-sidebar .logo h2 { 
    font-size: 22px; 
    background: linear-gradient(135deg, #6BAF92, #E8C547);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-top: 10px; 
    font-weight: 700;
}

.admin-sidebar .logo p { 
    font-size: 12px; 
    color: #bdc3c7; 
    margin-top: 5px; 
}

.admin-nav a { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    color: #ecf0f1; 
    padding: 15px 25px; 
    text-decoration: none; 
    font-weight: 500; 
    transition: all 0.3s; 
    border-left: 3px solid transparent; 
}

.admin-nav a:hover, .admin-nav a.active { 
    background: linear-gradient(90deg, rgba(107, 175, 146, 0.2), rgba(232, 197, 71, 0.1));
    border-left-color: #6BAF92; 
    color: #6BAF92; 
}

.admin-nav a i { 
    width: 20px; 
    font-size: 16px; 
}

.admin-info { 
    position: absolute; 
    bottom: 20px; 
    left: 0; 
    right: 0; 
    text-align: center; 
    padding: 0 20px; 
    border-top: 1px solid rgba(107, 175, 146, 0.3);
    padding-top: 20px; 
}

.admin-info .admin-name { 
    font-weight: bold; 
    background: linear-gradient(135deg, #6BAF92, #E8C547);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.admin-info .admin-role { 
    font-size: 12px; 
    color: #bdc3c7; 
}

/* Main Content */
.admin-content { 
    margin-left: 260px; 
    padding: 30px; 
    width: calc(100% - 260px); 
}

.page-header { 
    margin-bottom: 40px; 
}

.page-title { 
    font-size: 2.5rem; 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px; 
    font-weight: 700; 
}

.page-subtitle { 
    color: #4B8B6E;
    font-size: 1.1rem; 
    font-weight: 500;
}

/* Content Section */
.content-section { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px; 
    padding: 30px; 
    box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    margin-bottom: 30px; 
    border: 2px solid rgba(107, 175, 146, 0.2);
}

.section-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 25px; 
    padding-bottom: 15px; 
    border-bottom: 2px solid rgba(107, 175, 146, 0.2);
}

.section-title { 
    font-size: 1.5rem; 
    color: #4B8B6E;
    font-weight: 600; 
}

.section-title i {
    color: #E8C547;
    margin-right: 8px;
}

/* Form Styles */
.form-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
    margin-bottom: 20px; 
}

.form-group { 
    margin-bottom: 20px; 
}

.form-group.full-width { 
    grid-column: 1 / -1; 
}

.form-label { 
    display: block; 
    margin-bottom: 8px; 
    font-weight: 600; 
    color: #4B8B6E;
    font-size: 14px; 
}

.form-input, .form-select, .form-textarea { 
    width: 100%; 
    padding: 12px 15px; 
    border: 2px solid rgba(107, 175, 146, 0.3);
    border-radius: 12px; 
    font-size: 14px; 
    transition: all 0.3s; 
    background: rgba(255, 255, 255, 0.9);
}

.form-input:focus, .form-select:focus, .form-textarea:focus { 
    outline: none; 
    border-color: #6BAF92;
    box-shadow: 0 0 0 3px rgba(107, 175, 146, 0.1);
}

.form-textarea { 
    height: 400px; 
    font-family: 'Courier New', monospace; 
    resize: vertical; 
}

/* Buttons */
.btn { 
    padding: 12px 24px; 
    border: none; 
    border-radius: 12px; 
    font-size: 14px; 
    font-weight: 600; 
    cursor: pointer; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    transition: all 0.3s; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-success { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
    width: 100%; 
    justify-content: center; 
    font-size: 16px; 
    padding: 15px; 
}

.btn-success:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(107, 175, 146, 0.4);
}

/* Messages */
.message { 
    padding: 15px 20px; 
    margin-bottom: 25px; 
    border-radius: 12px; 
    font-weight: 500; 
}

.message.success { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(75, 139, 110, 0.15));
    color: #4B8B6E;
    border: 2px solid rgba(107, 175, 146, 0.3);
}

.message.error { 
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(192, 57, 43, 0.15));
    color: #c0392b;
    border: 2px solid rgba(231, 76, 60, 0.3);
}

/* Info Box */
.info-box { 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.05), rgba(232, 197, 71, 0.05));
    border: 2px solid rgba(107, 175, 146, 0.2);
    border-radius: 12px; 
    padding: 20px; 
    margin-bottom: 25px; 
}

.info-box .info-title { 
    font-weight: 600; 
    color: #4B8B6E;
    margin-bottom: 15px; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
}

.info-box .info-title i {
    color: #E8C547;
}

.info-box .format-example { 
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(107, 175, 146, 0.2);
    border-radius: 8px; 
    padding: 15px; 
    font-family: 'Courier New', monospace; 
    font-size: 12px; 
    color: #495057; 
    white-space: pre-line; 
    margin-top: 10px; 
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-content { margin-left: 0; width: 100%; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<!-- Admin Sidebar -->
<div class="admin-sidebar">
    <div class="logo">
        <img src="../LOGO.png" alt="SkillSync Logo">
        <h2>SkillSync</h2>
        <p>Admin Panel</p>
    </div>
    
    <nav class="admin-nav">
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a>
        <a href="manage_materials.php"><i class="fas fa-book"></i> Learning Materials</a>
        <a href="add_activity.php"><i class="fas fa-plus-circle"></i> Add Activity</a>
        <a href="add_questions.php" class="active"><i class="fas fa-question-circle"></i> Add Questions</a>
        <a href="manage_topics.php"><i class="fas fa-list"></i> Topics & Subjects</a>
        <a href="view_progress.php"><i class="fas fa-chart-line"></i> Student Progress</a>
        <a href="manage_recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
        <a href="view_feedback.php"><i class="fas fa-comments"></i> Feedback</a>
        <a href="system_settings.php"><i class="fas fa-cog"></i> System Settings</a>
        <a href="../login.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    
    <div class="admin-info">
        <div class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="admin-role">System Administrator</div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Add Questions 📝</h1>
        <p class="page-subtitle">Bulk import multiple-choice questions for assessments</p>
    </div>

    <!-- Format Guide -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-info-circle"></i> Question Format Guide</h3>
        </div>

        <div class="info-box">
            <div class="info-title">
                <i class="fas fa-lightbulb"></i>
                How to Format Your Questions
            </div>
            <p><strong>Copy and paste questions following this exact format:</strong></p>
            <div class="format-example">1. Which code correctly creates an object of class Car?
class Car {
    String brand;
    int year;
}
A. Car.brand = "Toyota";
B. Car myCar = new Car();
C. Car();
✅ Answer: B

2. What is the output of this code?
class Student {
    String name;
}
Student s = new Student();
s.name = "John";
System.out.println(s.name);
A. null
B. John
C. Compiler error
✅ Answer: B</div>
            <p><strong>Important:</strong> Use ✅ before "Answer:" and specify either the <strong>option letter (A, B, or C)</strong> OR the <strong>full text of the correct answer</strong>. The system will automatically convert it to the correct format.</p>
            <p style="margin-top: 10px;"><strong>Examples:</strong> "✅ Answer: B" or "✅ Answer: Car myCar = new Car();" both work!</p>
        </div>
    </div>

    <!-- Add Questions Form -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-question-circle"></i> Add Multiple Questions</h3>
        </div>
        
        <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="subject">Subject *</label>
                    <select class="form-select" id="subject" name="subject_id" required onchange="loadTopics()">
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="topic">Topic *</label>
                    <select class="form-select" id="topic" name="topic_id" required>
                        <option value="">-- Select Subject First --</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="class_level">Class Level</label>
                    <select class="form-select" id="class_level" name="class_level">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="question_type">Question Type</label>
                    <select class="form-select" id="question_type" name="question_type">
                        <option value="Quiz question">Quiz question</option>
                        <option value="Simulation question">Simulation question</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label class="form-label" for="questions">Paste Questions Here *</label>
                <textarea class="form-textarea" id="questions" name="questions" required placeholder="Paste your formatted questions here following the format guide above..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Import Questions
            </button>
        </form>
    </div>
</div>

<script>
function loadTopics() {
    const subjectId = document.getElementById('subject').value;
    const topicSelect = document.getElementById('topic');
    topicSelect.innerHTML = "<option value=''>Loading...</option>";
    
    if (subjectId) {
        fetch(`../load_topics.php?subject_id=${subjectId}`)
            .then(response => response.json())
            .then(topics => {
                topicSelect.innerHTML = "<option value=''>-- Select Topic --</option>";
                topics.forEach(topic => {
                    topicSelect.innerHTML += `<option value="${topic.id}">${topic.name}</option>`;
                });
            })
            .catch(error => {
                topicSelect.innerHTML = "<option value=''>Error loading topics</option>";
                console.error('Error:', error);
            });
    } else {
        topicSelect.innerHTML = "<option value=''>-- Select Subject First --</option>";
    }
}
</script>
</body>
</html>