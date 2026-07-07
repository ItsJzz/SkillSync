<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Load JSON file
$jsonData = file_get_contents(__DIR__ . "/intros.json");
$data = json_decode($jsonData, true);

// Get topic_id from URL (e.g., intro.php?topic_id=14)
$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;

if (!$topicId) {
    die("Invalid or missing topic_id.");
}

// Map topic_id to topic key
$topicMapping = [
    13 => 'introduction',
    14 => 'classes_objects', 
    15 => 'encapsulation',
    16 => 'inheritance',
    17 => 'polymorphism',
    30 => 'css_basics',
    // Add more common programming topics
    18 => 'abstraction',
    19 => 'interfaces',
    20 => 'constructors',
    21 => 'methods',
    22 => 'variables',
    23 => 'data_types',
    24 => 'arrays',
    25 => 'loops',
    26 => 'conditions',
    27 => 'functions',
    28 => 'exceptions',
    29 => 'file_handling',
    31 => 'html_basics',
    32 => 'javascript_basics',
    33 => 'php_basics',
    34 => 'sql_basics',
    35 => 'database_design',
    36 => 'web_development',
    37 => 'algorithms',
    38 => 'data_structures',
    39 => 'sorting',
    40 => 'searching',
    41 => 'recursion',
    42 => 'complexity',
    43 => 'testing',
    44 => 'debugging',
    45 => 'version_control'
];

$topicKey = isset($topicMapping[$topicId]) ? $topicMapping[$topicId] : null;

// If topic not mapped, try to skip intro and go directly to activities
if (!$topicKey || !isset($data['topics'][$topicKey])) {
    // Redirect directly to activities if no intro exists
    header("Location: activity.php?topic_id=$topicId");
    exit;
}

$topic = $data['topics'][$topicKey];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($topic['title']); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f7f9fb; margin:0; padding:20px; }
    .container { max-width: 800px; margin: auto; background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h2 { color: #27ae60; margin-bottom: 15px; }
    h3 { margin-top: 20px; color: #2c3e50; }
    p { margin: 8px 0; line-height: 1.6; }
    .buttons { margin-top: 30px; display: flex; gap: 10px; }
    .btn { padding: 10px 15px; text-decoration: none; border-radius: 6px; font-weight: bold; }
    .btn-green { background: #27ae60; color: #fff; }
    .btn-red { background: #e74c3c; color: #fff; }
    .btn:hover { opacity: 0.9; }
  </style>
</head>
<body>
  <div class="container">
    <h2><?php echo htmlspecialchars($topic['title']); ?></h2>
    <p><em><?php echo htmlspecialchars($topic['summary']); ?></em></p>

    <?php foreach ($topic['intro'] as $section): ?>
      <h3><?php echo htmlspecialchars($section['section']); ?></h3>
      <p><?php echo htmlspecialchars($section['content']); ?></p>
    <?php endforeach; ?>

    <div class="buttons">
      <a href="Enhancement.php" class="btn btn-red">← Return</a>
      <!-- FIX: pass topic ID instead of string -->
      <a href="activity_list.php?topic_id=<?php echo urlencode($topic['id']); ?>" class="btn btn-green">Continue →</a>
    </div>
  </div>
</body>
</html>
