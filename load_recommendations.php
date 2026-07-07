<?php
session_start();
if (!isset($_SESSION['user_id'])) exit("Login required");

$student_id  = $_SESSION['user_id'];
$subject_id  = intval($_GET['subject_id']);

require_once 'db_connect.php';
if ($conn->connect_error) die("DB error");

// --- Helper to format as percentage ---
function fmtPercent($val) {
    return $val !== null ? number_format($val, 0) . "%" : "—";
}

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

// Collect all topics first
$topicsData = [];

while ($t = $topicsRes->fetch_assoc()) {
    $topic_id   = $t['id'];
    $topic_name = htmlspecialchars($t['name']);

    $total_items = 4;
    $total_items2 = 50;
    // Get latest pre/post scores
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

    // Latest activity score
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

    $pre  = $tests['pre'];
    $post = $tests['post'];
    $act  = $actScore;

    // Calculate percentages
    $prePct  = ($pre !== null)  ? ($pre / $total_items) * 100 : null;
    $postPct = ($post !== null) ? ($post / $total_items2) * 100 : null;

    // Combined percentage
    $values = array_filter([$prePct, $postPct, $act], fn($v) => $v !== null);
    $combinedPct = !empty($values) ? array_sum($values) / count($values) : null;

    // --- Dynamic recommendation based on weak areas ---
    $weakAreas = [];
    if ($prePct !== null && $prePct <= 50)  $weakAreas[] = "Pre-Test";
    if ($postPct !== null && $postPct <= 50) $weakAreas[] = "Post-Test";
    if ($act !== null && $act <= 50)          $weakAreas[] = "Activity";

    // Base recommendation text
    if (empty($weakAreas)) {
        if ($combinedPct === null) {
            $recText = "❓ No sufficient data for triangulation analysis.";
        } elseif ($combinedPct >= 85) {
            $recText = "✅ Excellent mastery! Keep practicing advanced exercises and projects.";
        } elseif ($combinedPct >= 60) {
            $recText = "📈 Moderate mastery. Reinforce concepts with activities and applied exercises.";
        } else {
            $recText = "⚠️ Weak mastery. Review materials carefully and retry activities to strengthen understanding.";
        }
    } else {
        $recText = "⚠️ Weak areas detected in: " . implode(", ", $weakAreas) . ". ";
        $recText .= "Suggested actions: ";
        if (in_array("Pre-Test", $weakAreas)) {
            $recText .= "Review theory and study the topic basics. ";
        }
        if (in_array("Post-Test", $weakAreas)) {
            $recText .= "Retake the Post-Test after reviewing exercises. ";
        }
        if (in_array("Activity", $weakAreas)) {
            $recText .= "Complete more practice activities and exercises to improve understanding.";
        }
    }

    // Add clickable text for all topics
// Add clickable text for all topics (bold black)
    $recText .= "<br><a href='recommendation.php?topic_id={$topic_id}' style='color:black; text-decoration:underline; font-weight:bold; display:inline-block; margin-top:5px;'>Click here for detailed recommendations</a>";

    $topicsData[] = [
        'name' => $topic_name,
        'prePct' => $prePct,
        'postPct' => $postPct,
        'act' => $act,
        'combinedPct' => $combinedPct,
        'rec' => $recText
    ];
}

// Sort by combined percentage ascending
usort($topicsData, function($a, $b) {
    return ($a['combinedPct'] ?? 0) <=> ($b['combinedPct'] ?? 0);
});

// Output
foreach ($topicsData as $topic) {
    $preStyle  = ($topic['prePct'] !== null && $topic['prePct'] <= 50)  ? "color:red; font-weight:bold;" : "";
    $postStyle = ($topic['postPct'] !== null && $topic['postPct'] <= 50) ? "color:red; font-weight:bold;" : "";
    $actStyle  = ($topic['act'] !== null && $topic['act'] <= 50)          ? "color:red; font-weight:bold;" : "";

    $boxStyle = "border:1px solid #ddd; padding:15px; border-radius:10px; margin:20px 0; background:#fafafa;";

    echo "<div class='topic-box' style='$boxStyle'>";
    echo "<h3>{$topic['name']}</h3>";
    echo "<table>
            <tr><th>Pre-Test</th><th>Post-Test</th><th>Activity</th><th>Combined</th></tr>
            <tr>
                <td style='$preStyle'>".fmtPercent($topic['prePct'])."</td>
                <td style='$postStyle'>".fmtPercent($topic['postPct'])."</td>
                <td style='$actStyle'>".fmtPercent($topic['act'])."</td>
                <td>".fmtPercent($topic['combinedPct'])."</td>
            </tr>
          </table>";
    echo "<div class='rec'>{$topic['rec']}</div>";
    echo "</div>";
}
?>
