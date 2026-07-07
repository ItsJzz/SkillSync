<?php
/**
 * Recalculate Post-Test Scores to Include Activity Scores
 * 
 * This script updates all existing post-test attempts to include
 * hands-on activity scores in the final score calculation.
 * 
 * Run this ONCE after implementing the activity score integration fix.
 */

require_once "../db_connect.php";

echo "<h2>Recalculating Post-Test Scores with Activity Integration</h2>";
echo "<p>This will update all post-test scores to include hands-on activity performance...</p>";

// Get all completed post-test attempts
$attempts_stmt = $conn->prepare("
    SELECT id, user_id, topic_id, correct_answers, total_questions, score as old_score
    FROM user_post_test_attempts
    WHERE completed_at IS NOT NULL
    ORDER BY id
");
$attempts_stmt->execute();
$attempts = $attempts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$attempts_stmt->close();

$updated_count = 0;
$skipped_count = 0;

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'>
    <th style='padding: 10px;'>Attempt ID</th>
    <th style='padding: 10px;'>User ID</th>
    <th style='padding: 10px;'>Topic ID</th>
    <th style='padding: 10px;'>Quiz Score</th>
    <th style='padding: 10px;'>Activity Avg</th>
    <th style='padding: 10px;'>Old Score</th>
    <th style='padding: 10px;'>New Score</th>
    <th style='padding: 10px;'>Status</th>
</tr>";

foreach ($attempts as $attempt) {
    $attempt_id = $attempt['id'];
    $user_id = $attempt['user_id'];
    $topic_id = $attempt['topic_id'];
    $correct_answers = $attempt['correct_answers'];
    $total_questions = $attempt['total_questions'];
    $old_score = $attempt['old_score'];
    
    // Calculate quiz/simulation percentage
    $quiz_sim_percentage = ($total_questions > 0) ? round(($correct_answers / $total_questions) * 100, 2) : 0;
    
    // Get hands-on activity average score for this topic
    $activity_stmt = $conn->prepare("
        SELECT AVG(score) as avg_activity_score
        FROM save_progress
        WHERE user_id = ? AND topic_id = ?
    ");
    $activity_stmt->bind_param("ii", $user_id, $topic_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result()->fetch_assoc();
    $activity_stmt->close();
    
    $activity_avg = $activity_result['avg_activity_score'] ?: 0;
    
    // Calculate new combined score
    $new_score = round(
        ($quiz_sim_percentage * 0.6667) +  // Quiz + Simulation = 66.67%
        ($activity_avg * 0.3333),          // Activities = 33.33%
        2
    );
    
    // Only update if the score changed
    if (abs($new_score - $old_score) > 0.01) {
        $update_stmt = $conn->prepare("
            UPDATE user_post_test_attempts 
            SET score = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("di", $new_score, $attempt_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Also update eligibility record
        $eligibility_update = $conn->prepare("
            UPDATE user_post_test_eligibility
            SET best_post_test_score = GREATEST(best_post_test_score, ?)
            WHERE user_id = ? AND topic_id = ?
        ");
        $eligibility_update->bind_param("dii", $new_score, $user_id, $topic_id);
        $eligibility_update->execute();
        $eligibility_update->close();
        
        $status = "<span style='color: green;'>✓ Updated</span>";
        $updated_count++;
    } else {
        $status = "<span style='color: gray;'>- No change</span>";
        $skipped_count++;
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px; text-align: center;'>{$attempt_id}</td>";
    echo "<td style='padding: 8px; text-align: center;'>{$user_id}</td>";
    echo "<td style='padding: 8px; text-align: center;'>{$topic_id}</td>";
    echo "<td style='padding: 8px; text-align: center;'>{$quiz_sim_percentage}%</td>";
    echo "<td style='padding: 8px; text-align: center;'>" . round($activity_avg, 1) . " pts</td>";
    echo "<td style='padding: 8px; text-align: center; background: #ffebee;'>{$old_score}%</td>";
    echo "<td style='padding: 8px; text-align: center; background: #e8f5e9;'><strong>{$new_score}%</strong></td>";
    echo "<td style='padding: 8px;'>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div style='background: #e3f2fd; padding: 20px; border-left: 4px solid #2196f3; margin: 20px 0;'>";
echo "<h3 style='margin: 0 0 10px 0;'>Summary</h3>";
echo "<p><strong>Total Attempts Processed:</strong> " . count($attempts) . "</p>";
echo "<p><strong>Updated:</strong> <span style='color: green; font-weight: bold;'>{$updated_count}</span></p>";
echo "<p><strong>Skipped (no change):</strong> <span style='color: gray;'>{$skipped_count}</span></p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<h3 style='margin: 0 0 10px 0;'>⚠️ Important Notes</h3>";
echo "<ul style='margin: 0; padding-left: 20px;'>";
echo "<li>This script has been run successfully.</li>";
echo "<li>All post-test scores now include hands-on activity performance (33.33% weight).</li>";
echo "<li>Students should refresh their results pages to see updated scores.</li>";
echo "<li><strong>You can delete this file after running it once.</strong></li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='../student_dashboard.php' style='display: inline-block; background: #4caf50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>← Back to Dashboard</a>";
echo "</div>";

$conn->close();
?>
