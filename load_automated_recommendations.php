<?php
session_start();
if (!isset($_SESSION['user_id'])) exit("Login required");

$student_id = $_SESSION['user_id'];
$subject_id = intval($_GET['subject_id'] ?? 0);

require_once 'db_connect.php';
if ($conn->connect_error) die("DB error");

// Helper to format as percentage
function fmtPercent($val) {
    return $val !== null ? number_format($val, 0) . "%" : "—";
}

// Get subject name
$subjectRes = $conn->query("SELECT name FROM subjects WHERE id = $subject_id");
if ($subjectRes->num_rows === 0) {
    echo "<p>Subject not found.</p>";
    exit;
}
$subjectName = $subjectRes->fetch_assoc()['name'];

// Fetch all topics under the subject
$topicsRes = $conn->query("
    SELECT id, name 
    FROM topics 
    WHERE subject_id = $subject_id 
    ORDER BY id
");

if ($topicsRes->num_rows === 0) {
    echo "<p>No topics found for this subject.</p>";
    exit;
}

// Collect all topics data
$topicsData = [];

while ($t = $topicsRes->fetch_assoc()) {
    $topic_id = $t['id'];
    $topic_name = htmlspecialchars($t['name']);

    // Get latest pre-test score (from existing system)
    $preScore = null;
    $preRes = $conn->query("
        SELECT score 
        FROM student_tests 
        WHERE student_id=$student_id AND topic_id=$topic_id AND test_type='pre'
        ORDER BY attempt_date DESC LIMIT 1
    ");
    if ($preRow = $preRes->fetch_assoc()) {
        $preScore = ($preRow['score'] / 4) * 100; // Convert to percentage (4 questions)
    }

    // Get latest post-test score (from our new system)
    $postScore = null;
    $postRes = $conn->query("
        SELECT score 
        FROM user_post_test_attempts 
        WHERE user_id=$student_id AND topic_id=$topic_id AND completed_at IS NOT NULL
        ORDER BY completed_at DESC LIMIT 1
    ");
    if ($postRow = $postRes->fetch_assoc()) {
        $postScore = $postRow['score']; // Already a percentage
    }

    // Get latest activity score (from activity progress)
    $actScore = null;
    $actRes = $conn->query("
        SELECT AVG(score) as avg_score
        FROM save_progress 
        WHERE user_id=$student_id AND topic_id=$topic_id
    ");
    if ($actRow = $actRes->fetch_assoc()) {
        $actScore = $actRow['avg_score']; // Already a percentage/points
    }

    // Calculate overall score
    $scores = array_filter([$preScore, $postScore, $actScore], fn($v) => $v !== null);
    $overallScore = !empty($scores) ? array_sum($scores) / count($scores) : null;

    // Determine status and recommendations
    $status = "No Data";
    $recommendation = "No assessment data available yet.";
    $cardColor = "#f8f9fa";
    $textColor = "#6c757d";

    if ($overallScore !== null) {
        if ($overallScore >= 80) {
            $status = "Good Progress - Room for Growth";
            $recommendation = "Continue enhancement for mastery";
            $cardColor = "#fff3cd";
            $textColor = "#856404";
            $actions = [
                "Continue enhancement for mastery",
                "Practice more activities", 
                "Explore learning materials"
            ];
        } elseif ($overallScore >= 60) {
            $status = "Good Progress - Room for Growth";
            $recommendation = "Continue enhancement for mastery";
            $cardColor = "#d1ecf1";
            $textColor = "#0c5460";
            $actions = [
                "Continue enhancement for mastery",
                "Explore learning materials"
            ];
        } else {
            $status = "Focus Area - Needs Improvement";
            $recommendation = "Start Enhancement Process for skill building";
            $cardColor = "#f8d7da";
            $textColor = "#721c24";
            $actions = [
                "Start Enhancement Process for skill building",
                "Watch foundational videos",
                "Practice with coding activities",
                "Get detailed learning path"
            ];
        }
    }

    // Check specific weak areas
    $weakAreas = [];
    if ($preScore !== null && $preScore < 60) $weakAreas[] = "Pre-Test";
    if ($postScore !== null && $postScore < 60) $weakAreas[] = "Post-Test";
    if ($actScore !== null && $actScore < 60) $weakAreas[] = "Activities";

    $topicsData[] = [
        'id' => $topic_id,
        'name' => $topic_name,
        'preScore' => $preScore,
        'postScore' => $postScore,
        'actScore' => $actScore,
        'overallScore' => $overallScore,
        'status' => $status,
        'recommendation' => $recommendation,
        'actions' => $actions ?? [],
        'cardColor' => $cardColor,
        'textColor' => $textColor,
        'weakAreas' => $weakAreas
    ];
}

// Sort by overall score (lowest first - need most help)
usort($topicsData, function($a, $b) {
    $aScore = $a['overallScore'] ?? 0;
    $bScore = $b['overallScore'] ?? 0;
    return $aScore <=> $bScore;
});

// Output cards in the style shown in the image
?>

<div style="margin: 20px 0;">
    <h3 style="color: #495057; margin-bottom: 25px;">
        <i class="fas fa-graduation-cap"></i> Your Personalized Course Recommendations
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach ($topicsData as $topic): ?>
            <div style="
                background: <?= $topic['cardColor'] ?>;
                border: 1px solid #dee2e6;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border-left: 4px solid <?= $topic['overallScore'] >= 80 ? '#28a745' : ($topic['overallScore'] >= 60 ? '#17a2b8' : '#dc3545') ?>;
            ">
                <!-- Topic Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                    <div>
                        <h4 style="margin: 0; color: #212529; font-size: 16px;">
                            <i class="fas fa-book" style="color: #ffc107; margin-right: 8px;"></i>
                            <?= $topic['name'] ?>
                        </h4>
                        <div style="color: <?= $topic['textColor'] ?>; font-size: 12px; margin-top: 5px;">
                            <?= fmtPercent($topic['overallScore']) ?> - <?= $topic['status'] ?>
                        </div>
                    </div>
                </div>

                <!-- Score Breakdown -->
                <div style="margin: 15px 0; font-size: 11px; color: #6c757d;">
                    <div>Pre-Test: <?= fmtPercent($topic['preScore']) ?></div>
                    <div>Post-Test: <?= fmtPercent($topic['postScore']) ?></div>
                    <div>Activities: <?= fmtPercent($topic['actScore']) ?></div>
                </div>

                <!-- Action Items -->
                <div style="margin-top: 15px;">
                    <?php foreach ($topic['actions'] as $action): ?>
                        <div style="
                            display: flex; 
                            align-items: center; 
                            margin: 8px 0; 
                            color: <?= $topic['textColor'] ?>; 
                            font-size: 13px;
                            cursor: pointer;
                        " onclick="handleAction('<?= $action ?>', <?= $topic['id'] ?>)">
                            <i class="fas fa-arrow-up" style="margin-right: 8px; font-size: 10px;"></i>
                            <?= $action ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Learning Materials Link -->
                    <div style="
                        display: flex; 
                        align-items: center; 
                        margin: 8px 0; 
                        color: <?= $topic['textColor'] ?>; 
                        font-size: 13px;
                        cursor: pointer;
                    " onclick="window.open('recommendation.php?topic_id=<?= $topic['id'] ?>', '_blank')">
                        <i class="fas fa-external-link-alt" style="margin-right: 8px; font-size: 10px;"></i>
                        Get detailed learning path
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Action Buttons like in the image -->
<div style="text-align: center; margin-top: 40px;">
    <button onclick="window.location.href='student_dashboard.php'" style="
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 500;
        margin: 0 10px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        transition: transform 0.2s ease;
    " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <i class="fas fa-home"></i> Go to Dashboard
    </button>
    
    <button onclick="window.location.href='recommendations.php'" style="
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 500;
        margin: 0 10px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
        transition: transform 0.2s ease;
    " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <i class="fas fa-heart"></i> View All Recommendations
    </button>
</div>

<script>
function handleAction(action, topicId) {
    if (action.includes('Enhancement Process')) {
        // Redirect to activities page
        window.location.href = 'Activity/activity_list.php?topic_id=' + topicId;
    } else if (action.includes('learning materials')) {
        // Open detailed recommendations
        window.open('recommendation.php?topic_id=' + topicId, '_blank');
    } else if (action.includes('activities')) {
        // Redirect to activities page
        window.location.href = 'Activity/activity_list.php?topic_id=' + topicId;
    } else if (action.includes('videos')) {
        // Open learning materials
        window.open('recommendation.php?topic_id=' + topicId, '_blank');
    } else {
        // Default to detailed recommendations
        window.open('recommendation.php?topic_id=' + topicId, '_blank');
    }
}
</script>