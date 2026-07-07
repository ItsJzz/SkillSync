<?php
// activity_list.php
session_start();
require_once __DIR__ . "/../db_connect.php"; // adjust path

// Require login
if (!isset($_SESSION['user_id'])) {
    die("⚠️ You must be logged in to view activities.");
}
$userId = $_SESSION['user_id'];

// Get topic_id
$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topicId <= 0) {
    die("⚠️ Invalid topic ID.");
}

// Load activities.json
$jsonFile = __DIR__ . "/activities.json";
if (!file_exists($jsonFile)) {
    die("⚠️ activities.json file not found.");
}
$data = json_decode(file_get_contents($jsonFile), true);
if (!$data) {
    die("⚠️ Invalid JSON format.");
}

// Find topic data
$topicData = null;
foreach ($data as $node) {
    if (isset($node['topic_id']) && $node['topic_id'] == $topicId) {
        $topicData = $node;
        break;
    }
}
if (!$topicData) {
    die("⚠️ No activities defined for this topic.");
}

$instructions = $topicData['instructions'] ?? [];
$topicName   = $topicData['name'] ?? "Untitled Topic";

// Get user's class level from assessment_details (stored after promotion test)
$userClassLevel = 'Beginner'; // Default to Beginner
$assessStmt = $conn->prepare("
    SELECT JSON_EXTRACT(assessment_details, '$.class_level') AS class_level
    FROM students 
    WHERE id = ? OR user_id = ? 
    LIMIT 1
");
$assessStmt->bind_param("ii", $userId, $userId);
$assessStmt->execute();
$assessResult = $assessStmt->get_result();
if ($assessRow = $assessResult->fetch_assoc()) {
    if ($assessRow['class_level'] !== null) {
        // Remove quotes from JSON_EXTRACT result
        $userClassLevel = trim($assessRow['class_level'], '"');
    }
}
$assessStmt->close();

// Filter instructions based on user's class level
// Only show activities for the CURRENT level (not previous levels)
$allowedLevels = [$userClassLevel]; // Only show current level

// Filter activities by class level
$filteredInstructions = [];
foreach ($instructions as $inst) {
    $activityClassLevel = $inst['class_level'] ?? 'Beginner'; // Default if not set
    if (in_array($activityClassLevel, $allowedLevels)) {
        $filteredInstructions[] = $inst;
    }
}
$instructions = $filteredInstructions;

// Reset progress
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $stmt = $conn->prepare("DELETE FROM save_progress WHERE user_id = ? AND topic_id = ?");
    $stmt->bind_param("ii", $userId, $topicId);
    $stmt->execute();
}

// Fetch progress
$progress = [];
$res = $conn->prepare("
    SELECT level, score, attempt_time
    FROM save_progress
    WHERE user_id = ? AND topic_id = ?
    ORDER BY attempt_time DESC
");
$res->bind_param("ii", $userId, $topicId);
$res->execute();
$result = $res->get_result();
while ($row = $result->fetch_assoc()) {
    if (!isset($progress[$row['level']])) {
        $progress[$row['level']] = $row; // keep latest
    }
}

// Calculate average score and track basic levels (1-5) completion
$totalScore = 0;
$completedLevels = 0;
$basicLevelsCompleted = 0; // Levels 1-5 are basic
$scoreHistory = []; // Track score progression

foreach ($progress as $level => $p) {
    if (isset($p['score'])) {
        $totalScore += $p['score'];
        $completedLevels++;
        $scoreHistory[] = ['level' => $level, 'score' => $p['score'], 'time' => $p['attempt_time']];
        
        // Count basic levels (1-5) completion
        if ($level >= 1 && $level <= 5) {
            $basicLevelsCompleted++;
        }
    }
}

// Sort score history by time
usort($scoreHistory, function($a, $b) {
    return strtotime($a['time']) - strtotime($b['time']);
});

$averageScore = $completedLevels > 0 ? round($totalScore / $completedLevels, 2) : 0;
$maxLevel = count($instructions);
$basicLevelsRequired = 5; // Must complete levels 1-5 to unlock post-test
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($topicName) ?> - Activities</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
            min-height: 100vh;
            color: #2c3e50;
            padding: 20px;
        }
        
        /* Top Navigation */
        .topnav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        
        .topnav a {
            color: #4B8B6E;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .topnav a:hover {
            color: #6BAF92;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Page Header */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            color: #4B8B6E;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .page-subtitle {
            color: #6BAF92;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        
        .class-level-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(75, 139, 110, 0.15));
            color: #4B8B6E;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 700;
            border: 2px solid rgba(75, 139, 110, 0.3);
        }
        
        .class-level-badge i {
            font-size: 1.3rem;
        }
        
        .level-note {
            font-size: 0.9rem;
            color: #6BAF92;
            margin-top: 8px;
            font-weight: 500;
        }
        
        /* Activity Cards */
        .activities-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .activity-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(75, 139, 110, 0.12);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid rgba(107, 175, 146, 0.2);
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .activity-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
            transition: width 0.3s ease;
        }
        
        .activity-card.completed::before {
            background: linear-gradient(180deg, #4B8B6E, #6BAF92);
        }
        
        .activity-card.locked::before {
            background: linear-gradient(180deg, #cbd5e1, #94a3b8);
        }
        
        .activity-card.available::before {
            background: linear-gradient(180deg, #E8C547, #F4D77C);
        }
        
        .activity-card:hover:not(.locked) {
            transform: translateY(-5px) translateX(5px);
            box-shadow: 0 15px 40px rgba(75, 139, 110, 0.25);
        }
        
        .activity-card:hover::before {
            width: 100%;
            opacity: 0.05;
        }
        
        /* Activity Icon */
        .activity-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.2);
            transition: all 0.3s ease;
        }
        
        .activity-card.completed .activity-icon {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
        }
        
        .activity-card.locked .activity-icon {
            background: linear-gradient(135deg, #cbd5e1, #94a3b8);
            color: white;
        }
        
        .activity-card.available .activity-icon {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            color: white;
        }
        
        .activity-card:hover .activity-icon {
            transform: rotate(5deg) scale(1.05);
        }
        
        /* Activity Content */
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #4B8B6E;
            margin-bottom: 8px;
        }
        
        .activity-description {
            color: #6BAF92;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .activity-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(75, 139, 110, 0.1));
            color: #4B8B6E;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid rgba(107, 175, 146, 0.2);
        }
        
        .meta-badge.variants {
            background: linear-gradient(135deg, rgba(244, 215, 124, 0.15), rgba(232, 197, 71, 0.15));
            color: #E8C547;
            border-color: rgba(232, 197, 71, 0.3);
        }
        
        .meta-badge.score {
            background: linear-gradient(135deg, rgba(75, 139, 110, 0.15), rgba(107, 175, 146, 0.15));
            color: #4B8B6E;
            border-color: rgba(75, 139, 110, 0.3);
        }
        
        /* Activity Actions */
        .activity-action {
            flex-shrink: 0;
        }
        
        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-start {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            color: #4B8B6E;
            box-shadow: 0 5px 20px rgba(232, 197, 71, 0.3);
        }
        
        .btn-start:hover {
            background: linear-gradient(135deg, #F4D77C, #E8C547);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(232, 197, 71, 0.4);
        }
        
        .btn-done {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
        }
        
        .btn-done:hover {
            background: linear-gradient(135deg, #6BAF92, #4B8B6E);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(75, 139, 110, 0.4);
        }
        
        .btn-locked {
            background: #cbd5e1;
            color: #64748b;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        /* Progress Stats Section */
        .progress-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #4B8B6E;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.08), rgba(75, 139, 110, 0.08));
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6BAF92;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .progress-bar-container {
            background: rgba(107, 175, 146, 0.1);
            border-radius: 25px;
            height: 30px;
            overflow: hidden;
            position: relative;
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4B8B6E, #6BAF92);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 25px;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 0.9rem;
            color: #6BAF92;
            font-weight: 600;
        }
        
        /* Chart Section */
        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        
        .chart-canvas {
            max-height: 300px;
        }
        
        /* Recommendation Box */
        .recommendation-box {
            background: linear-gradient(135deg, rgba(244, 215, 124, 0.15), rgba(232, 197, 71, 0.15));
            border-left: 6px solid #E8C547;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid rgba(232, 197, 71, 0.3);
        }
        
        .recommendation-box h4 {
            color: #4B8B6E;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .recommendation-box ul {
            margin: 15px 0;
            padding-left: 25px;
            color: #6BAF92;
        }
        
        .recommendation-box li {
            margin-bottom: 10px;
            font-weight: 500;
            line-height: 1.6;
        }
        
        /* Footer Actions */
        .footer-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn-posttest {
            background: linear-gradient(135deg, #6BAF92, #4B8B6E);
            color: white;
            padding: 16px 35px;
            box-shadow: 0 5px 20px rgba(107, 175, 146, 0.3);
        }
        
        .btn-posttest:hover {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(107, 175, 146, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #E8C547, #F4D77C);
            color: #4B8B6E;
            padding: 14px 30px;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #F4D77C, #E8C547);
            transform: translateY(-2px);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #6BAF92;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #4B8B6E;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #6BAF92;
            font-size: 1.1rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .activity-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .activity-card {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
</head>
<body>

<div class="topnav">
    <a href="../student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title"><?= htmlspecialchars($topicName) ?></h1>
        <p class="page-subtitle">Practice and master your skills through interactive coding activities</p>
        
        <div class="class-level-badge">
            <i class="fas fa-graduation-cap"></i>
            <span>Your Class Level: <strong><?= htmlspecialchars($userClassLevel) ?></strong></span>
        </div>
        
        <?php if ($userClassLevel === 'Beginner'): ?>
            <p class="level-note">Showing Beginner activities - Pass promotion test to unlock Intermediate</p>
        <?php elseif ($userClassLevel === 'Intermediate'): ?>
            <p class="level-note">Showing Intermediate activities - Pass promotion test to unlock Expert</p>
        <?php else: ?>
            <p class="level-note">Showing Expert activities - You've mastered them all! 🎉</p>
        <?php endif; ?>
    </div>

    <!-- Activities Grid -->
    <?php if (empty($instructions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Activities Available</h3>
            <p>There are no activities defined for this topic yet.</p>
        </div>
    <?php else: ?>
        <div class="activities-grid">
        <?php foreach ($instructions as $inst): 
            // Handle both old format (direct) and new format (with variants)
            if (isset($inst['variants'])) {
                // New format with variants
                $level = $inst['level'];
                $firstVariant = $inst['variants'][0]; // Show first variant info
                $title = $firstVariant['title'] ?? "Level $level";
                $description = $firstVariant['description'] ?? "No description.";
                $variantCount = count($inst['variants']);
            } else {
                // Old format (direct instruction)
                $level = $inst['level'] ?? (array_search($inst, $instructions) + 1);
                $title = $inst['title'] ?? "Untitled";
                $description = $inst['description'] ?? "No description.";
                $variantCount = 1;
            }
            
            $completed = isset($progress[$level]);
            $score = $progress[$level]['score'] ?? null;
            $locked = ($level > 1 && !isset($progress[$level-1]));
            
            $cardClass = $completed ? 'completed' : ($locked ? 'locked' : 'available');
        ?>
            <div class="activity-card <?= $cardClass ?>">
                <div class="activity-icon">
                    <?php if ($completed): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($locked): ?>
                        <i class="fas fa-lock"></i>
                    <?php else: ?>
                        <i class="fas fa-code"></i>
                    <?php endif; ?>
                </div>
                
                <div class="activity-content">
                    <h3 class="activity-title"><?= htmlspecialchars($title) ?></h3>
                    <p class="activity-description"><?= htmlspecialchars($description) ?></p>
                    
                    <div class="activity-meta">
                        <?php if ($variantCount > 1): ?>
                            <span class="meta-badge variants">
                                <i class="fas fa-dice"></i> <?= $variantCount ?> Variants
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($score !== null): ?>
                            <span class="meta-badge score">
                                <i class="fas fa-star"></i> Latest: <?= $score ?> pts
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="activity-action">
                    <?php if ($completed): ?>
                        <a href="activity.php?topic_id=<?= $topicId ?>&level=<?= $level ?>" class="btn btn-done">
                            <i class="fas fa-redo"></i> Practice Again
                        </a>
                    <?php elseif ($locked): ?>
                        <button class="btn btn-locked" disabled>
                            <i class="fas fa-lock"></i> Locked
                        </button>
                    <?php else: ?>
                        <a href="activity.php?topic_id=<?= $topicId ?>&level=<?= $level ?>" class="btn btn-start">
                            <i class="fas fa-play"></i> Start Activity
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    // ============================================
    // PREDICTIVE ANALYSIS & REGRESSION MODEL
    // ============================================
    
    // Initialize prediction variables
    $trendSlope = 0;
    $consistency = 0;
    $recentPerformance = 0;
    $successProbability = 0;
    $readinessLevel = 'Not Ready';
    $predictionMessage = '';
    $predictionIcon = '';
    $predictionColor = '#64748b';
    
    if (count($scoreHistory) >= 2) {
        // Calculate trend slope (linear regression)
        $n = count($scoreHistory);
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumXX = 0;
        
        foreach ($scoreHistory as $i => $item) {
            $sumX += $i;
            $sumY += $item['score'];
            $sumXY += $i * $item['score'];
            $sumXX += $i * $i;
        }
        
        $trendSlope = ($n * $sumXY - $sumX * $sumY) / max(($n * $sumXX - $sumX * $sumX), 1);
        
        // Calculate consistency (lower standard deviation = more consistent)
        $scores = array_map(function($item) { return $item['score']; }, $scoreHistory);
        $mean = array_sum($scores) / count($scores);
        $variance = array_sum(array_map(function($score) use ($mean) { return pow($score - $mean, 2); }, $scores)) / count($scores);
        $stdDev = sqrt($variance);
        $consistency = 100 - min($stdDev, 100); // Convert to 0-100 scale (higher = more consistent)
        
        // Recent performance (last 3 attempts)
        $recentScores = array_slice($scores, -3);
        $recentPerformance = array_sum($recentScores) / count($recentScores);
    }
    
    // Predictive model for post-test success
    if ($basicLevelsCompleted >= $basicLevelsRequired && count($scoreHistory) >= 2) {
        // Calculate success probability based on multiple factors
        $avgScoreFactor = min($averageScore / 100, 1.0); // 0-1 scale
        $trendFactor = $trendSlope > 0 ? 1.2 : ($trendSlope < -2 ? 0.7 : 1.0); // Bonus for improving
        $consistencyFactor = $consistency / 100; // 0-1 scale
        $recentFactor = $recentPerformance > 0 ? min($recentPerformance / 100, 1.0) : $avgScoreFactor;
        
        // Weighted probability calculation
        $successProbability = (
            ($avgScoreFactor * 0.40) +      // 40% weight on average
            ($recentFactor * 0.30) +         // 30% weight on recent performance
            ($consistencyFactor * 0.20) +    // 20% weight on consistency
            ($trendFactor * 0.10)            // 10% weight on trend
        ) * 100;
        
        // Determine readiness level and message
        if ($successProbability >= 85) {
            $readinessLevel = 'Highly Ready';
            $predictionIcon = '🎯';
            $predictionColor = '#10b981'; // Green
            $predictionMessage = "Excellent performance! Your average score of <strong>{$averageScore} pts</strong> and consistent improvement indicate <strong>high probability of success</strong> in the post-assessment. You're ready to take the test now!";
        } elseif ($successProbability >= 70) {
            $readinessLevel = 'Ready';
            $predictionIcon = '✅';
            $predictionColor = '#22c55e'; // Light green
            $predictionMessage = "Good performance! Your average of <strong>{$averageScore} pts</strong> suggests you're <strong>likely to pass</strong> the post-assessment. Consider reviewing any challenging topics, then take the test.";
        } elseif ($successProbability >= 55) {
            $readinessLevel = 'Moderately Ready';
            $predictionIcon = '⚠️';
            $predictionColor = '#f59e0b'; // Orange
            $predictionMessage = "Average performance detected. Your score of <strong>{$averageScore} pts</strong> indicates <strong>moderate success probability</strong>. We recommend practicing more on lower-scoring levels before attempting the post-assessment.";
        } elseif ($successProbability >= 40) {
            $readinessLevel = 'Need More Practice';
            $predictionIcon = '📚';
            $predictionColor = '#f97316'; // Dark orange
            $predictionMessage = "Your average score of <strong>{$averageScore} pts</strong> suggests you need <strong>more practice</strong>. Complete additional attempts on levels 1-5 and aim for scores above 80 points to improve your success rate.";
        } else {
            $readinessLevel = 'Not Ready';
            $predictionIcon = '❌';
            $predictionColor = '#ef4444'; // Red
            $predictionMessage = "Low performance detected. Your average of <strong>{$averageScore} pts</strong> indicates <strong>low success probability</strong>. Focus on understanding core concepts and redo basic levels until you consistently score above 70 points.";
        }
        
        // Add trend-specific insights
        if ($trendSlope > 2) {
            $predictionMessage .= " <strong>Positive trend detected</strong> 📈 - Your scores are improving steadily!";
        } elseif ($trendSlope < -2) {
            $predictionMessage .= " <strong>Declining trend detected</strong> 📉 - Take breaks and review earlier material.";
        }
        
        // Add consistency insights
        if ($consistency > 80) {
            $predictionMessage .= " Your performance is highly consistent, which is excellent!";
        } elseif ($consistency < 50) {
            $predictionMessage .= " Your scores vary significantly - aim for more consistent performance.";
        }
    }
    ?>

    <!-- Progress Tracking Section -->
    <div class="progress-section">
        <h3 class="section-title">
            <i class="fas fa-chart-line"></i> Progress Overview
        </h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $basicLevelsCompleted ?></div>
                <div class="stat-label">Levels Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $averageScore ?></div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $completedLevels ?></div>
                <div class="stat-label">Total Attempts</div>
            </div>
        </div>
        
        <div class="progress-bar-container">
            <?php 
            $basicProgress = ($basicLevelsCompleted / $basicLevelsRequired) * 100;
            ?>
            <div class="progress-bar-fill" style="width: <?= min($basicProgress, 100) ?>%">
                <?= round($basicProgress) ?>% Complete
            </div>
        </div>
        
        <div class="progress-stats">
            <span><i class="fas fa-tasks"></i> Required: <?= $basicLevelsRequired ?> levels</span>
            <span><i class="fas fa-check-circle"></i> Completed: <?= $basicLevelsCompleted ?> levels</span>
        </div>
        
        <!-- Early Prediction for Incomplete Progress -->
        <?php if ($basicLevelsCompleted < $basicLevelsRequired && $completedLevels > 0): ?>
        <div class="recommendation-box" style="margin-top: 20px;">
            <h4><i class="fas fa-lightbulb"></i> Keep Going!</h4>
            <p>Complete <strong><?= $basicLevelsRequired - $basicLevelsCompleted ?></strong> more level(s) to unlock post-assessment.</p>
            <?php if ($averageScore >= 80): ?>
                <p>Your high average (<strong><?= $averageScore ?> pts</strong>) suggests strong readiness! 🎯</p>
            <?php elseif ($averageScore >= 70): ?>
                <p>Your average is good (<strong><?= $averageScore ?> pts</strong>). Keep it up! 💪</p>
            <?php else: ?>
                <p>Current average: <strong><?= $averageScore ?> pts</strong>. Aim for 80+ for better success rate! 📚</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Performance Chart -->
    <?php if (!empty($scoreHistory)): ?>
    <div class="chart-container">
        <h3 class="section-title"><i class="fas fa-chart-area"></i> Performance Trend</h3>
        <canvas id="performanceChart" class="chart-canvas"></canvas>
        
        <!-- AI Predictive Analysis Section -->
        <?php if ($basicLevelsCompleted >= $basicLevelsRequired && $successProbability > 0): ?>
        <div class="recommendation-box" style="margin-top: 25px;">
            <h4>
                <i class="fas fa-brain"></i> AI Predictive Analysis
                <span style="font-size: 0.8rem; background: linear-gradient(135deg, #4B8B6E, #6BAF92); color: white; padding: 6px 14px; border-radius: 20px; margin-left: 10px;">
                    <?= $readinessLevel ?>
                </span>
            </h4>
            <div style="margin: 12px 0; font-size: 0.95rem; color: #6BAF92; font-weight: 600;">
                Success Probability: <strong style="color: #4B8B6E;"><?= number_format($successProbability, 1) ?>%</strong>
                <span style="margin-left: 15px;">Average: <strong><?= $averageScore ?> pts</strong></span>
                <span style="margin-left: 15px;">Consistency: <strong><?= number_format($consistency, 1) ?>%</strong></span>
            </div>
            <p style="margin: 0; line-height: 1.8;">
                <?= $predictionMessage ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php
    // ============================================
    // POST-ASSESSMENT ELIGIBILITY & RECOMMENDATION SYSTEM
    // ============================================
    
    // Get user's pre-test score for this topic
    $preTestTopicScore = 0;
    $preTestStmt = $conn->prepare("
        SELECT JSON_EXTRACT(assessment_data, '$.topic_scores.\"$topicId\".percentage') AS topic_score
        FROM students 
        WHERE id = ? OR user_id = ? 
        LIMIT 1
    ");
    $preTestStmt->bind_param("ii", $userId, $userId);
    $preTestStmt->execute();
    $preTestResult = $preTestStmt->get_result();
    if ($preTestRow = $preTestResult->fetch_assoc()) {
        $preTestTopicScore = floatval($preTestRow['topic_score'] ?? 0);
    }
    $preTestStmt->close();
    
    // Check post-test history
    $postTestQuery = "
        SELECT 
            id,
            score,
            total_questions,
            correct_answers,
            started_at,
            completed_at
        FROM user_post_test_attempts 
        WHERE user_id = ? AND topic_id = ? AND completed_at IS NOT NULL
        ORDER BY started_at DESC
    ";
    $postTestStmt = $conn->prepare($postTestQuery);
    $postTestStmt->bind_param("ii", $userId, $topicId);
    $postTestStmt->execute();
    $postTestResult = $postTestStmt->get_result();
    $postTestAttempts = [];
    while ($attempt = $postTestResult->fetch_assoc()) {
        $postTestAttempts[] = $attempt;
    }
    $postTestStmt->close();
    
    $hasPostTest = !empty($postTestAttempts);
    $lastPostTest = $hasPostTest ? $postTestAttempts[0] : null;
    $lastPostTestScore = $lastPostTest ? floatval($lastPostTest['score']) : 0; // 'score' already stores percentage
    $postTestPassed = $lastPostTestScore >= 75; // 75% passing threshold
    
    // Calculate improvement metrics
    $improvementFromPreTest = $averageScore > 0 ? (($averageScore - $preTestTopicScore) / max($preTestTopicScore, 1)) * 100 : 0;
    $improvementNeeded = 5; // Minimum 5% improvement required for retake
    $canRetakePostTest = false;
    $retakeReason = "";
    
    // Eligibility logic
    $basicLevelsComplete = $basicLevelsCompleted >= $basicLevelsRequired;
    $canTakePostTest = false;
    
    if ($basicLevelsComplete && !$hasPostTest) {
        // First time: Can take post-test after completing basic levels
        $canTakePostTest = true;
    } elseif ($hasPostTest && !$postTestPassed) {
        // Failed post-test: Check improvement requirement
        if ($improvementFromPreTest >= $improvementNeeded) {
            $canRetakePostTest = true;
            $canTakePostTest = true;
        } else {
            $retakeReason = sprintf(
                "You need at least %.1f%% improvement in activity scores. Current improvement: %.1f%%",
                $improvementNeeded,
                $improvementFromPreTest
            );
        }
    } elseif ($postTestPassed) {
        // Passed: No need to retake
        $canTakePostTest = false;
    }
    
    // Generate learning recommendations for failed post-test
    $recommendations = [];
    if ($hasPostTest && !$postTestPassed) {
        if ($averageScore < 70) {
            $recommendations[] = "Your average activity score is low ({$averageScore} pts). Redo basic levels 1-3 to strengthen fundamentals.";
        }
        if ($lastPostTestScore < 50) {
            $recommendations[] = "Post-test score is below 50%. Focus on understanding core concepts before retaking.";
        }
        if ($improvementFromPreTest < 2) {
            $recommendations[] = "Minimal progress detected. Review video materials and complete all activity variants.";
        }
        if (count($scoreHistory) < 3) {
            $recommendations[] = "Practice more! Complete multiple attempts of each level to build consistency.";
        }
        $recommendations[] = "Review your mistakes from the post-test results page.";
        $recommendations[] = "Focus on levels where you scored below 80 points.";
    }
    ?>

    <!-- Post-Test Status Messages -->
    <?php if ($hasPostTest && $postTestPassed): ?>
        <div class="success-notice">
            <h4><i class="fas fa-trophy"></i> Post-Assessment Passed!</h4>
            <p>Congratulations! You scored <?= number_format($lastPostTestScore, 1) ?>% on the post-assessment. Your topic performance has been updated.</p>
        </div>
    <?php elseif ($hasPostTest && !$postTestPassed && !$canRetakePostTest): ?>
        <div class="restriction-notice">
            <h4><i class="fas fa-exclamation-triangle"></i> Post-Assessment Retake Restricted</h4>
            <p><strong>Last Score:</strong> <?= number_format($lastPostTestScore, 1) ?>% (Failed - Need 75%+)</p>
            <p><strong>Reason:</strong> <?= htmlspecialchars($retakeReason) ?></p>
            <p style="margin-top:12px;"><i class="fas fa-info-circle"></i> <strong>System Analysis:</strong> Our regression model predicts low success rate without sufficient practice improvement. Complete more activities to improve your average score by at least 5%.</p>
        </div>
    <?php elseif ($hasPostTest && !$postTestPassed && $canRetakePostTest): ?>
        <div class="success-notice">
            <h4><i class="fas fa-check-circle"></i> Eligible for Post-Assessment Retake</h4>
            <p>You've improved your activity average by <?= number_format($improvementFromPreTest, 1) ?>%! You can now retake the post-assessment.</p>
        </div>
    <?php endif; ?>

    <!-- Learning Path Recommendations -->
    <?php if (!empty($recommendations)): ?>
        <div class="recommendation-box">
            <h4><i class="fas fa-lightbulb"></i> Personalized Learning Path</h4>
            <p style="margin: 0 0 12px 0; font-weight: 600;">To improve your performance and unlock post-test retake:</p>
            <ul>
                <?php foreach ($recommendations as $rec): ?>
                    <li><?= htmlspecialchars($rec) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="footer-actions">
        <?php if ($basicLevelsComplete): ?>
            <?php if ($canTakePostTest): ?>
                <a href="simplified_post_test_exam.php?topic_id=<?= $topicId ?>" class="btn btn-posttest">
                    <i class="fas fa-graduation-cap"></i> 
                    <?= $hasPostTest ? 'Retake Post-Assessment' : 'Take Post-Assessment' ?>
                </a>
            <?php elseif ($postTestPassed): ?>
                <a href="simplified_post_test_results.php?attempt_id=<?= $lastPostTest['id'] ?>" class="btn btn-done">
                    <i class="fas fa-trophy"></i> View Passing Results
                </a>
            <?php else: ?>
                <button class="btn btn-locked" title="<?= htmlspecialchars($retakeReason) ?>">
                    <i class="fas fa-lock"></i> Post-Assessment Locked
                </button>
                <a href="simplified_post_test_results.php?attempt_id=<?= $lastPostTest['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-chart-line"></i> Review Last Attempt
                </a>
            <?php endif; ?>
        <?php else: ?>
            <button class="btn btn-locked" title="Complete levels 1-5 first">
                <i class="fas fa-lock"></i> Complete Basic Levels First
            </button>
        <?php endif; ?>
        
        <a href="?topic_id=<?= $topicId ?>&reset=1" onclick="return confirm('This will delete all your progress. Continue?')" class="btn btn-warning">
            <i class="fas fa-sync"></i> Reset Progress
        </a>
    </div>
</div>

<!-- Chart.js Performance Visualization -->
<?php if (!empty($scoreHistory)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Prepare data
    const labels = <?= json_encode(array_map(function($item) { return 'Level ' . $item['level']; }, $scoreHistory)) ?>;
    const scores = <?= json_encode(array_map(function($item) { return $item['score']; }, $scoreHistory)) ?>;
    const avgScore = <?= $averageScore ?>;
    
    // Calculate trend line (simple linear regression)
    const n = scores.length;
    let sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;
    for (let i = 0; i < n; i++) {
        sumX += i;
        sumY += scores[i];
        sumXY += i * scores[i];
        sumXX += i * i;
    }
    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;
    const trendLine = scores.map((_, i) => slope * i + intercept);
    
    // Determine trend direction
    const isImproving = slope > 0.5;
    const isDeclining = slope < -0.5;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Your Score',
                    data: scores,
                    borderColor: '#4B8B6E',
                    backgroundColor: 'rgba(75, 139, 110, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    pointBackgroundColor: '#4B8B6E',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointHoverBackgroundColor: '#6BAF92',
                    pointHoverBorderColor: '#fff'
                },
                {
                    label: 'Average (' + avgScore + ' pts)',
                    data: Array(scores.length).fill(avgScore),
                    borderColor: '#E8C547',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [8, 4],
                    pointRadius: 0
                },
                {
                    label: 'Trend Line',
                    data: trendLine,
                    borderColor: isImproving ? '#6BAF92' : (isDeclining ? '#E8C547' : '#94a3b8'),
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [10, 5],
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#4B8B6E',
                        font: {
                            family: 'Poppins',
                            size: 13,
                            weight: '600'
                        },
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(75, 139, 110, 0.95)',
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: '700'
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    padding: 12,
                    borderColor: '#6BAF92',
                    borderWidth: 2,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + ' pts';
                        }
                    }
                },
                title: {
                    display: true,
                    text: isImproving ? '📈 Improving Performance - Keep it up!' : (isDeclining ? '📉 Practice More to Improve' : '📊 Consistent Performance'),
                    color: isImproving ? '#4B8B6E' : (isDeclining ? '#E8C547' : '#6BAF92'),
                    font: {
                        family: 'Poppins',
                        size: 16,
                        weight: '700'
                    },
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Score (points)',
                        color: '#4B8B6E',
                        font: {
                            family: 'Poppins',
                            size: 13,
                            weight: '600'
                        }
                    },
                    ticks: {
                        color: '#6BAF92',
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(107, 175, 146, 0.1)',
                        lineWidth: 1
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Activity Level',
                        color: '#4B8B6E',
                        font: {
                            family: 'Poppins',
                            size: 13,
                            weight: '600'
                        }
                    },
                    ticks: {
                        color: '#6BAF92',
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>
