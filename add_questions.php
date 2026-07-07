<?php
session_start();

// Require login (optional)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$message = "";

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

    // Insert into DB
$stmt = $conn->prepare("
    INSERT INTO questions (topic_id, question_text, code_snippet, option_a, option_b, option_c, correct_option, class_level, question_type)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($questions as $q) {
    $question   = $q['question'];
    $code       = $q['code'];
    $option_a   = $q['options']['A'] ?? '';
    $option_b   = $q['options']['B'] ?? '';
    $option_c   = $q['options']['C'] ?? '';
    $correct    = $q['answer'] ?? '';
    
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
        $correct,
        $class_level,
        $question_type
    );
    $stmt->execute();
}


    // After finishing insertion
header("Location: success.php?topic_id=" . $topic_id);
exit();

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Questions</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 40px; }
.container { max-width: 900px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
textarea { width: 100%; height: 400px; margin-bottom: 20px; font-family: monospace; padding: 10px; }
button { background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
button:hover { background: #219150; }
.success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 6px; }
</style>
</head>
<body>
<div class="container">
    <h2>Add Multiple Questions</h2>
    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Topic ID:</label><br>
        <input type="number" name="topic_id" required><br><br>
        
        <label>Class Level:</label><br>
        <select name="class_level" style="width: 100%; padding: 8px; margin-bottom: 15px;">
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
            <option value="Expert">Expert</option>
        </select><br>
        
        <label>Question Type:</label><br>
        <select name="question_type" style="width: 100%; padding: 8px; margin-bottom: 15px;">
            <option value="Quiz question">Quiz question</option>
            <option value="Simulation question">Simulation question</option>
        </select><br>
        
        <label>Paste Questions:</label><br>
        <textarea name="questions" placeholder="Paste formatted questions here..." required></textarea><br>
        <button type="submit">Insert Questions</button>
    </form>
</div>
</body>
</html>
