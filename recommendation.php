<?php
session_start();
if (!isset($_SESSION['user_id'])) exit("Login required");

$student_id = $_SESSION['user_id'];
$topic_id   = intval($_GET['topic_id'] ?? 0);

if ($topic_id <= 0) {
    exit("Invalid topic.");
}

require_once 'db_connect.php';
if ($conn->connect_error) die("DB connection error");

// Fetch topic info
$topicRes = $conn->query("SELECT name FROM topics WHERE id = $topic_id");
if ($topicRes->num_rows === 0) exit("Topic not found.");
$topic = $topicRes->fetch_assoc();
$topic_name = htmlspecialchars($topic['name']);

// Fetch latest pre/post scores
$tests = ["pre" => null, "post" => null];
$q = $conn->query("
    SELECT test_type, score 
    FROM student_tests 
    WHERE student_id=$student_id AND topic_id=$topic_id
    ORDER BY attempt_date DESC
");
while ($row = $q->fetch_assoc()) {
    if ($tests[$row['test_type']] === null) {
        $tests[$row['test_type']] = $row['score'];
    }
}

// Fetch latest activity score
$actScore = null;
$q2 = $conn->query("
    SELECT avg_score 
    FROM student_activity_scores 
    WHERE student_id=$student_id AND topic_id=$topic_id
    ORDER BY last_updated DESC LIMIT 1
");
if ($row2 = $q2->fetch_assoc()) {
    $actScore = $row2['avg_score'];
}

$pre  = $tests['pre'] ?? 0;
$post = $tests['post'] ?? 0;
$act  = $actScore ?? 0;

// Convert to percentages
$total_items = 4;   // pre-test fixed items
$total_items2 = 50; // post-test fixed items
$prePct  = ($pre / $total_items) * 100;
$postPct = ($post / $total_items2) * 100;
$actPct  = $act; // already %

$values = array_filter([$prePct, $postPct, $actPct], fn($v) => $v !== null);
$combinedPct = !empty($values) ? array_sum($values) / count($values) : 0;

// Determine weak areas
$weakAreas = [];
if ($prePct <= 50)  $weakAreas[] = "Pre-Test";
if ($postPct <= 50) $weakAreas[] = "Post-Test";
if ($actPct <= 50)  $weakAreas[] = "Activity";

// Generate detailed recommendations
$recDetails = [];
if (empty($weakAreas)) {
    $recDetails[] = "✅ No weak areas detected. Keep up the good work!";
} else {
    if (in_array("Pre-Test", $weakAreas))  $recDetails[] = "Pre-Test: Review theory and study the topic basics.";
    if (in_array("Post-Test", $weakAreas)) $recDetails[] = "Post-Test: Retake after reviewing exercises.";
    if (in_array("Activity", $weakAreas))  $recDetails[] = "Activity: Complete more practice activities to improve understanding.";
}

// Fetch learning materials dynamically
$videos = [];
$pdfs = [];
$simulations = [];

$matRes = $conn->query("SELECT id, type, title, url FROM learning_materials WHERE topic_id=$topic_id");
while ($mat = $matRes->fetch_assoc()) {
    if ($mat['type'] === 'video') {
        $videos[] = $mat;
    } elseif ($mat['type'] === 'pdf') {
        $pdfs[] = $mat;
    } elseif ($mat['type'] === 'simulation') {
        $simulations[] = $mat;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Detailed Recommendations - <?= $topic_name ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
.container { max-width: 900px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h2 { margin-bottom: 20px; color: #333; }
ul { line-height: 1.6; padding-left: 20px; }
a.back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff; font-weight: bold; }
.chart-container { margin-top: 30px; }
.materials { margin-top: 20px; display: none; }
.materials h3 { margin-bottom: 15px; }
iframe { width: 100%; height: 315px; border: none; border-radius: 8px; margin-bottom: 20px; }
.module-list li { margin-bottom: 10px; }
button.next-btn { margin-top: 20px; padding: 10px 15px; background: #007bff; border: none; color: #fff; border-radius: 5px; cursor: pointer; font-weight: bold; }
button.next-btn:hover { background: #0056b3; }
button.watch-btn { margin-top: 10px; padding: 8px 12px; background: #28a745; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button.watch-btn:hover { background: #1e7e34; }
</style>
</head>
<body>
<div class="container">
    <!-- Panel 1: Recommendations & Chart -->
    <div id="panel-recommendations">
        <h2>Detailed Recommendations for: <?= $topic_name ?></h2>

        <ul>
            <?php foreach ($recDetails as $rec) : ?>
                <li><?= htmlspecialchars($rec) ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="chart-container">
            <canvas id="scoreChart"></canvas>
        </div>

        <button class="next-btn" onclick="showMaterials()">Next &raquo;</button>
        <a href="javascript:history.back()" class="back-link">&larr; Go Back</a>
    </div>

    <!-- Panel 2: Learning Materials -->
    <div id="panel-materials" class="materials">
        <h2>📚 Learning Materials for <?= $topic_name ?></h2>

        <?php if ($videos): ?>
            <h3>Videos</h3>
            <?php foreach ($videos as $vid): ?>
                <div class="video-block">
                    <p><strong><?= htmlspecialchars($vid['title']) ?></strong></p>
                    <button class="watch-btn" onclick="watchVideo(<?= $vid['id'] ?>)">▶ Watch Video</button>
                    <div id="video-<?= $vid['id'] ?>" style="display:none; margin-top:10px;">
                        <iframe src="<?= htmlspecialchars($vid['url']) ?>" allowfullscreen></iframe>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($pdfs): ?>
            <h3>PDF Modules</h3>
            <ul class="module-list">
                <?php foreach ($pdfs as $pdf): ?>
                    <li><a href="<?= htmlspecialchars($pdf['url']) ?>" target="_blank"><?= htmlspecialchars($pdf['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($simulations): ?>
            <h3>Interactive Simulations</h3>
            <ul class="module-list">
                <?php foreach ($simulations as $sim): ?>
                    <li><a href="<?= htmlspecialchars($sim['url']) ?>" target="_blank"><?= htmlspecialchars($sim['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="javascript:history.back()" class="back-link">&larr; Go Back</a>
    </div>
</div>

<script>
const ctx = document.getElementById('scoreChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Pre-Test', 'Post-Test', 'Activity'],
        datasets: [{
            label: 'Score (%)',
            data: [<?= $prePct ?>, <?= $postPct ?>, <?= $actPct ?>],
            backgroundColor: ['#ff6384', '#36a2eb', '#ffce56'],
            borderColor: ['#ff6384', '#36a2eb', '#ffce56'],
            borderWidth: 1
        }]
    },
    options: {
        scales: { y: { beginAtZero: true, max: 100 } },
        plugins: { legend: { display: false } }
    }
});

// Switch panels
function showMaterials() {
    document.getElementById("panel-recommendations").style.display = "none";
    document.getElementById("panel-materials").style.display = "block";
}

// Track video watching
function watchVideo(materialId) {
    document.getElementById("video-" + materialId).style.display = "block";
    fetch("log_video_watch.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "material_id=" + materialId
    }).then(res => res.text()).then(msg => console.log(msg));
}
</script>
</body>
</html>
