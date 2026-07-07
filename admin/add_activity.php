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

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_id = intval($_POST['topic_id']);
    $level = intval($_POST['level']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $skeleton = trim($_POST['skeleton']);
    $hint = trim($_POST['hint']);
    $requirements = $_POST['requirements'];
    
    // Basic validation
    if (empty($topic_id) || empty($level) || empty($title) || empty($description) || empty($skeleton)) {
        $error = "Please fill in all required fields.";
    } else {
        // Process requirements
        $formatted_requirements = [];
        foreach ($requirements as $req) {
            if (!empty($req['description']) && !empty($req['pattern'])) {
                $formatted_requirements[$req['description']] = $req['pattern'];
            }
        }
        
        // Create new activity
        $new_activity = [
            "title" => $title,
            "description" => $description,
            "skeleton" => $skeleton,
            "requirements" => $formatted_requirements,
            "hint" => $hint
        ];
        
        // Load existing activities.json
        $json_file = __DIR__ . '/../Activity/activities.json';
        $activities = [];
        
        if (file_exists($json_file)) {
            $activities = json_decode(file_get_contents($json_file), true) ?: [];
        }
        
        // Find or create topic entry
        $topic_found = false;
        foreach ($activities as $key => &$topic_data) {
            if (isset($topic_data['topic_id']) && $topic_data['topic_id'] == $topic_id) {
                $topic_found = true;
                
                // Ensure instructions array exists
                if (!isset($topic_data['instructions'])) {
                    $topic_data['instructions'] = [];
                }
                
                // Find existing level or add to it
                $level_found = false;
                foreach ($topic_data['instructions'] as &$instruction) {
                    // Check if this instruction is for the same level
                    if (isset($instruction['level']) && $instruction['level'] == $level) {
                        // Add as variant to existing level
                        if (!isset($instruction['variants'])) {
                            // Convert existing instruction to variants format
                            $original = $instruction;
                            unset($original['level']);
                            $instruction = [
                                'level' => $level,
                                'variants' => [$original]
                            ];
                        }
                        $instruction['variants'][] = $new_activity;
                        $level_found = true;
                        break;
                    }
                }
                
                // If level not found, create new level entry
                if (!$level_found) {
                    $topic_data['instructions'][] = [
                        'level' => $level,
                        'variants' => [$new_activity]
                    ];
                }
                break;
            }
        }
        
        // If topic not found, get topic name from database and create new entry
        if (!$topic_found) {
            $stmt = $conn->prepare("SELECT name FROM topics WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $topic_name = "Unknown Topic";
            if ($row = $result->fetch_assoc()) {
                $topic_name = $row['name'];
            }
            
            $activities[$topic_id] = [
                "topic_id" => $topic_id,
                "name" => $topic_name,
                "instructions" => [
                    [
                        'level' => $level,
                        'variants' => [$new_activity]
                    ]
                ]
            ];
        }
        
        // Save back to JSON
        if (file_put_contents($json_file, json_encode($activities, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            $message = "Activity added successfully! Level $level now has multiple variants for randomization.";
        } else {
            $error = "Failed to save activity to JSON file.";
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
<title>Add Activity - SkillSync Admin</title>
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
    height: 120px; 
    font-family: monospace; 
    resize: vertical; 
}

.form-textarea.large { 
    height: 200px; 
}

/* Requirements Section */
.requirements-container { 
    border: 2px solid rgba(107, 175, 146, 0.2);
    padding: 20px; 
    border-radius: 12px; 
    background: linear-gradient(135deg, rgba(107, 175, 146, 0.05), rgba(232, 197, 71, 0.05));
}

.requirement-item { 
    display: flex; 
    gap: 12px; 
    margin-bottom: 15px; 
    align-items: center; 
}

.requirement-item .form-input { 
    flex: 1; 
    margin-bottom: 0; 
}

.btn-remove { 
    padding: 8px 12px; 
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-size: 12px; 
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.2);
}

.btn-remove:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.btn-add { 
    background: linear-gradient(135deg, #6BAF92, #4B8B6E);
    color: white; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 10px; 
    cursor: pointer; 
    font-weight: 600; 
    margin-top: 10px; 
    box-shadow: 0 4px 15px rgba(107, 175, 146, 0.2);
}

.btn-add:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(107, 175, 146, 0.3);
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

.btn-primary { 
    background: linear-gradient(135deg, #4B8B6E, #6BAF92);
    color: white; 
}

.btn-primary:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3);
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
    margin-bottom: 10px; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
}

.info-box .info-title i {
    color: #E8C547;
}

.info-box ul { 
    margin-left: 20px; 
    color: #4B8B6E;
}

.info-box li { 
    margin-bottom: 5px; 
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
        <a href="add_activity.php" class="active"><i class="fas fa-plus-circle"></i> Add Activity</a>
        <a href="add_questions.php"><i class="fas fa-question-circle"></i> Add Questions</a>
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
        <h1 class="page-title">Add Activity Variant 🎯</h1>
        <p class="page-subtitle">Create new learning activities for students</p>
    </div>

    <!-- Add Activity Form -->
    <div class="content-section">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-plus-circle"></i> Activity Details</h3>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <div class="info-title">
                <i class="fas fa-lightbulb"></i>
                Randomization Feature
            </div>
            <ul>
                <li>Add multiple variants for the same topic + level</li>
                <li>Students will get a random variant each time they attempt</li>
                <li>Keeps the learning experience fresh and prevents memorization</li>
                <li>Helps students practice different scenarios and edge cases</li>
            </ul>
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
                    <label class="form-label" for="level">Difficulty Level *</label>
                    <select class="form-select" id="level" name="level" required>
                        <option value="">-- Select Level --</option>
                        <option value="1">Level 1 - Basic</option>
                        <option value="2">Level 2 - Intermediate</option>
                        <option value="3">Level 3 - Advanced</option>
                        <option value="4">Level 4 - Expert</option>
                        <option value="5">Level 5 - Master</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="title">Activity Title *</label>
                    <input type="text" class="form-input" id="title" name="title" required placeholder="e.g., Level 1: Basic Object Creation (Variant 2)">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label class="form-label" for="description">Activity Description *</label>
                <textarea class="form-textarea" id="description" name="description" required placeholder="Describe what the student needs to accomplish in this activity..."></textarea>
            </div>
            
            <div class="form-group full-width">
                <label class="form-label" for="skeleton">Skeleton Code *</label>
                <textarea class="form-textarea large" id="skeleton" name="skeleton" required placeholder="Provide the starting code with TODO comments and placeholders..."></textarea>
            </div>
            
            <div class="form-group full-width">
                <label class="form-label">Validation Requirements *</label>
                <div class="requirements-container">
                    <div id="requirements">
                        <div class="requirement-item">
                            <input type="text" class="form-input" name="requirements[0][description]" placeholder="Requirement description (e.g., Must create an object)" required>
                            <input type="text" class="form-input" name="requirements[0][pattern]" placeholder="Regex pattern (e.g., /obj1\.age\s*=/)" required>
                            <button type="button" class="btn-remove" onclick="removeRequirement(this)">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-add" onclick="addRequirement()">
                        <i class="fas fa-plus"></i> Add Requirement
                    </button>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label class="form-label" for="hint">Hint (Optional)</label>
                <input type="text" class="form-input" id="hint" name="hint" placeholder="💡 Hint: Provide a helpful tip for students...">
            </div>
            
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Add Activity Variant
            </button>
        </form>
    </div>
</div>

<script>
let requirementCount = 1;

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

function addRequirement() {
    const container = document.getElementById('requirements');
    const newRequirement = document.createElement('div');
    newRequirement.className = 'requirement-item';
    newRequirement.innerHTML = `
        <input type="text" class="form-input" name="requirements[${requirementCount}][description]" placeholder="Requirement description" required>
        <input type="text" class="form-input" name="requirements[${requirementCount}][pattern]" placeholder="Regex pattern" required>
        <button type="button" class="btn-remove" onclick="removeRequirement(this)">
            <i class="fas fa-times"></i> Remove
        </button>
    `;
    container.appendChild(newRequirement);
    requirementCount++;
}

function removeRequirement(button) {
    if (document.querySelectorAll('.requirement-item').length > 1) {
        button.parentElement.remove();
    } else {
        alert('At least one requirement is needed.');
    }
}
</script>
</body>
</html>