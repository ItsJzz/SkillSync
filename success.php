<?php
session_start();
require_once __DIR__ . "/db_connect.php";

if (!isset($_GET['topic_id'])) {
    die("Invalid request.");
}

$topic_id = intval($_GET['topic_id']);

// Get last 20 questions (or adjust to match how many you usually insert in one batch)
$sql = "SELECT * FROM questions WHERE topic_id = ? ORDER BY id DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Questions Added Successfully</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 40px; }
.container { max-width: 900px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.question { margin-bottom: 25px; padding: 15px; border-bottom: 1px solid #ddd; }
.code { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin: 10px 0; white-space: pre; font-family: monospace; }
.options { margin: 10px 0; }
.correct { color: green; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <h2>✅ Questions Inserted Successfully</h2>
    <p>Here are your most recent questions for topic <strong><?= htmlspecialchars($topic_id) ?></strong>:</p>
    <?php
    $num = 1;
    while ($row = $result->fetch_assoc()):
    ?>
    <div class="question">
        <strong><?= $num++ ?>. <?= htmlspecialchars($row['question_text']) ?></strong>
        <?php if (!empty($row['code_snippet'])): ?>
            <div class="code"><?= htmlspecialchars($row['code_snippet']) ?></div>
        <?php endif; ?>
        <div class="options">
            A. <?= htmlspecialchars($row['option_a']) ?><br>
            B. <?= htmlspecialchars($row['option_b']) ?><br>
            C. <?= htmlspecialchars($row['option_c']) ?><br>
        </div>
        <div class="correct">✅ Correct Answer: <?= htmlspecialchars($row['correct_option']) ?></div>
    </div>
    <?php endwhile; ?>
</div>
</body>
</html>
