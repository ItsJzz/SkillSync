<?php
session_start();

// Basic admin check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

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
        $json_file = __DIR__ . '/activities.json';
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
            require_once __DIR__ . "/../db_connect.php";
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
require_once __DIR__ . "/../db_connect.php";
$subjects = [];
$sql = "SELECT id, name FROM subjects ORDER BY name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Activity Variant - SkillSync Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        select, input, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        textarea { height: 120px; font-family: monospace; }
        .skeleton { height: 200px; }
        .requirements-container { border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9; }
        .requirement-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .requirement-item input[type="text"] { flex: 1; }
        .requirement-item button { padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .add-requirement { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        .submit-btn { background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        .submit-btn:hover { background: #0056b3; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #e2f3ff; color: #0c5460; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info strong { color: #004085; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Activity Variant</h1>
        
        <div class="info">
            <strong>💡 Randomization Feature:</strong><br>
            • Add multiple variants for the same topic + level<br>
            • Students will get a random variant each time they attempt<br>
            • Keeps the learning experience fresh and prevents memorization
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="subject">Subject *</label>
                <select id="subject" name="subject_id" required onchange="loadTopics()">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="topic">Topic *</label>
                <select id="topic" name="topic_id" required>
                    <option value="">-- Select Subject First --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="level">Level *</label>
                <select id="level" name="level" required>
                    <option value="">-- Select Level --</option>
                    <option value="1">Level 1</option>
                    <option value="2">Level 2</option>
                    <option value="3">Level 3</option>
                    <option value="4">Level 4</option>
                    <option value="5">Level 5</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="title">Activity Title *</label>
                <input type="text" id="title" name="title" required placeholder="e.g., Level 1: Basic Object (Variant 2)">
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required placeholder="Describe what the student needs to accomplish..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="skeleton">Skeleton Code *</label>
                <textarea id="skeleton" name="skeleton" class="skeleton" required placeholder="Provide the starting code with TODO comments..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Requirements (Validation Rules) *</label>
                <div class="requirements-container">
                    <div id="requirements">
                        <div class="requirement-item">
                            <input type="text" name="requirements[0][description]" placeholder="Requirement description" required>
                            <input type="text" name="requirements[0][pattern]" placeholder="Regex pattern (e.g., /obj1\.age\s*=/)" required>
                            <button type="button" onclick="removeRequirement(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="add-requirement" onclick="addRequirement()">+ Add Requirement</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="hint">Hint</label>
                <input type="text" id="hint" name="hint" placeholder="💡 Hint: Provide a helpful tip...">
            </div>
            
            <button type="submit" class="submit-btn">Add Activity Variant</button>
        </form>
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
                <input type="text" name="requirements[${requirementCount}][description]" placeholder="Requirement description" required>
                <input type="text" name="requirements[${requirementCount}][pattern]" placeholder="Regex pattern" required>
                <button type="button" onclick="removeRequirement(this)">Remove</button>
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