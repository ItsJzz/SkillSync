<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Database connection
require_once 'db_connect.php';

// Get user info
$userQuery = "SELECT username, email FROM login_credentials WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

/** ----------------------
 * Get Assessment Data for Class Level & Progress
 * Calculate using the best available scores (post-test if taken, otherwise pre-test)
 * ---------------------- */
$assessmentData = null;
$classLevel = 'Not Assessed';
$overallPercentage = 0;
$progressToNext = 0;
$isNewUser = false;

// Get pre-test data AND assessment_details (which contains class_level)
$assessStmt = $conn->prepare("SELECT assessment_data, assessment_details FROM students WHERE id = ? OR user_id = ? LIMIT 1");
$assessStmt->bind_param("ii", $student_id, $student_id);
$assessStmt->execute();
$assessResult = $assessStmt->get_result();
$assessmentDetails = null;
if ($assessRow = $assessResult->fetch_assoc()) {
    if (!empty($assessRow['assessment_data'])) {
        $assessmentData = json_decode($assessRow['assessment_data'], true);
    } else {
        $isNewUser = true; // No assessment taken yet
    }
    
    // Get stored class_level from assessment_details
    if (!empty($assessRow['assessment_details'])) {
        $assessmentDetails = json_decode($assessRow['assessment_details'], true);
    }
} else {
    $isNewUser = true; // No student record
}
$assessStmt->close();

// Calculate overall percentage using best available scores
if (!$isNewUser && $assessmentData) {
    // Get all topics with their scores
    $subject_id = 3; // OOP subject (you can make this dynamic if needed)
    
    // Get subject code for promotion test link
    $subjectCode = 'OOP1'; // Default
    $subjectStmt = $conn->prepare("SELECT code FROM subjects WHERE id = ?");
    $subjectStmt->bind_param("i", $subject_id);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result()->fetch_assoc();
    if ($subjectResult) {
        $subjectCode = $subjectResult['code'];
    }
    $subjectStmt->close();
    
    $all_topics_stmt = $conn->prepare("
        SELECT 
            t.id,
            JSON_EXTRACT(s.assessment_data, CONCAT('$.topic_scores.\"', t.id, '\".percentage')) as pre_test_score,
            (
                SELECT MAX(upta.score)
                FROM user_post_test_attempts upta
                WHERE upta.user_id = ? AND upta.topic_id = t.id AND upta.completed_at IS NOT NULL
            ) as post_test_score
        FROM topics t
        LEFT JOIN students s ON (s.id = ? OR s.user_id = ?)
        WHERE t.subject_id = ?
        ORDER BY t.id
    ");
    $all_topics_stmt->bind_param("iiii", $student_id, $student_id, $student_id, $subject_id);
    $all_topics_stmt->execute();
    $all_topics = $all_topics_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $all_topics_stmt->close();
    
    // Calculate overall progress using best available score for each topic
    $total_topics = count($all_topics);
    $total_score = 0;
    
    foreach ($all_topics as $topic) {
        $post_score = $topic['post_test_score'] ?? 0;
        $pre_score = $topic['pre_test_score'] ? floatval($topic['pre_test_score']) : 0;
        
        // Use post-test score if taken, otherwise use pre-test score
        $topic_score = ($post_score > 0) ? $post_score : $pre_score;
        $total_score += $topic_score;
    }
    
    $overallPercentage = $total_topics > 0 ? ($total_score / $total_topics) : 0;
    
    // Get class_level from stored assessment_details (NOT auto-calculated from score)
    // This ensures students must pass level promotion test before advancing
    if ($assessmentDetails && isset($assessmentDetails['class_level'])) {
        $classLevel = $assessmentDetails['class_level'];
    } else {
        // Default to Beginner if no level stored yet
        $classLevel = 'Beginner';
    }
    
    // Calculate progress to next level based on CURRENT level
    if ($classLevel === 'Beginner') {
        // Progress to Intermediate (at 75% they're ready for promotion test)
        $progressToNext = ($overallPercentage / 75) * 100;
    } else if ($classLevel === 'Intermediate') {
        // Progress to Expert (you can adjust this later)
        $progressToNext = ($overallPercentage >= 75) ? (($overallPercentage - 75) / 20) * 100 : 0;
    } else {
        $progressToNext = 100; // Already at Expert
    }
}

// Check if user has completed onboarding (using a session or user preference)
$showOnboarding = !isset($_SESSION['onboarding_completed']) && $isNewUser;

/** ----------------------
 * Enhanced KPIs with more accurate data
 * ---------------------- */

// 1. Activities Completed (unique topics with activities done)
$activitiesQuery = "SELECT COUNT(DISTINCT topic_id) as completed FROM student_activity_scores WHERE student_id = ?";
$actStmt = $conn->prepare($activitiesQuery);
$actStmt->bind_param("i", $student_id);
$actStmt->execute();
$activities_completed = $actStmt->get_result()->fetch_assoc()['completed'];
$actStmt->close();

// Total available activities (topics with activities)
$totalActQuery = "SELECT COUNT(*) as total FROM topics WHERE id IN (SELECT DISTINCT topic_id FROM save_progress)";
$totalActivities = $conn->query($totalActQuery)->fetch_assoc()['total'];

// 2. Coding Practice Stats
$codingStatsQuery = "
    SELECT 
        COUNT(DISTINCT problem_id) as problems_solved,
        COALESCE(SUM(score), 0) as total_score,
        COALESCE(MAX(score), 0) as best_score
    FROM coding_practice_scores 
    WHERE user_id = ?
";
$codingStmt = $conn->prepare($codingStatsQuery);
$codingStmt->bind_param("i", $student_id);
$codingStmt->execute();
$codingStats = $codingStmt->get_result()->fetch_assoc();
$codingStmt->close();

// 3. Assessments Taken
$assessmentsQuery = "
    SELECT 
        COUNT(CASE WHEN test_type = 'pre' THEN 1 END) as pre_tests,
        COUNT(CASE WHEN test_type = 'post' THEN 1 END) as post_tests
    FROM student_tests 
    WHERE student_id = ?
";
$assessStmt = $conn->prepare($assessmentsQuery);
$assessStmt->bind_param("i", $student_id);
$assessStmt->execute();
$assessments = $assessStmt->get_result()->fetch_assoc();
$assessStmt->close();

// 4. Video Materials Watched
$videosQuery = "SELECT COUNT(*) as videos_watched FROM student_video_progress WHERE student_id = ?";
$videoStmt = $conn->prepare($videosQuery);
$videoStmt->bind_param("i", $student_id);
$videoStmt->execute();
$videos_watched = $videoStmt->get_result()->fetch_assoc()['videos_watched'];
$videoStmt->close();

/** ----------------------
 * Weekly Progress Data for Chart (Working version from progress.php)
 * ---------------------- */

// -------- Weekly Activities --------
$activities = [];
$res = $conn->query("
  SELECT WEEK(date_created,1) AS week, COUNT(*) AS cnt
  FROM student_activity_scores
  WHERE student_id = $student_id
  GROUP BY WEEK(date_created,1)
");
while ($r = $res->fetch_assoc()) {
  $activities[$r['week']] = (int)$r['cnt'];
}

// -------- Weekly Quizzes --------
$quizzes = [];
$res = $conn->query("
  SELECT WEEK(attempt_date,1) AS week, COUNT(*) AS cnt
  FROM student_tests
  WHERE student_id = $student_id
  GROUP BY WEEK(attempt_date,1)
");
while ($r = $res->fetch_assoc()) {
  $quizzes[$r['week']] = (int)$r['cnt'];
}

// -------- Weekly Videos --------
$videos = [];
$res = $conn->query("
  SELECT WEEK(watched_at,1) AS week, COUNT(*) AS cnt
  FROM student_video_progress
  WHERE student_id = $student_id
  GROUP BY WEEK(watched_at,1)
");
while ($r = $res->fetch_assoc()) {
  $videos[$r['week']] = (int)$r['cnt'];
}

// Collect all distinct weeks
$weeks = array_unique(array_merge(array_keys($activities), array_keys($quizzes), array_keys($videos)));
sort($weeks);

$weekLabels = []; $actData = []; $quizData = []; $videoData = [];
foreach ($weeks as $w) {
  $weekLabels[] = "Week $w";
  $actData[] = $activities[$w] ?? 0;
  $quizData[] = $quizzes[$w] ?? 0;
  $videoData[] = $videos[$w] ?? 0;
}

// -------- Pre/Post Test Scores Over Time --------
$preScores = []; $postScores = []; $scoreWeeks = [];
$res = $conn->query("
  SELECT WEEK(attempt_date,1) AS week, test_type, AVG(score) AS avg_score
  FROM student_tests
  WHERE student_id = $student_id
  GROUP BY WEEK(attempt_date,1), test_type
  ORDER BY week ASC
");
while ($r = $res->fetch_assoc()) {
  $scoreWeeks[$r['week']] = "Week ".$r['week'];
  if ($r['test_type'] == 'pre') $preScores[$r['week']] = (float)$r['avg_score'];
  if ($r['test_type'] == 'post') $postScores[$r['week']] = (float)$r['avg_score'];
}

$scoreLabels = array_values($scoreWeeks);
$preData = []; $postData = [];
foreach (array_keys($scoreWeeks) as $w) {
  $preData[] = $preScores[$w] ?? null;
  $postData[] = $postScores[$w] ?? null;
}

/** ----------------------
 * Topic Progress with Percentage-based Improvement
 * ---------------------- */
$topicProgressQuery = "
    SELECT 
        t.id,
        t.name,
        s.code as subject_code,
        
        -- Pre-test data
        st_pre.score as pre_score,
        st_pre.attempt_date as pre_date,
        
        -- Post-test data (from user_post_test_attempts)
        upta.score as post_score,
        upta.completed_at as post_date,
        
        -- Activity data
        COALESCE(AVG(sp.score), 0) as activity_avg_score,
        COUNT(sp.id) as activity_attempts
        
    FROM topics t
    JOIN subjects s ON t.subject_id = s.id
    LEFT JOIN student_tests st_pre ON t.id = st_pre.topic_id AND st_pre.student_id = ? AND st_pre.test_type = 'pre'
    LEFT JOIN (
        SELECT topic_id, MAX(score) as score, MAX(completed_at) as completed_at
        FROM user_post_test_attempts 
        WHERE user_id = ? AND completed_at IS NOT NULL
        GROUP BY topic_id
    ) upta ON t.id = upta.topic_id
    LEFT JOIN save_progress sp ON t.id = sp.topic_id AND sp.user_id = ?
    WHERE st_pre.score IS NOT NULL OR upta.score IS NOT NULL OR sp.id IS NOT NULL
    GROUP BY t.id, t.name, s.code, st_pre.score, st_pre.attempt_date, upta.score, upta.completed_at
    ORDER BY s.code, t.name
";
$topicStmt = $conn->prepare($topicProgressQuery);
$topicStmt->bind_param("iii", $student_id, $student_id, $student_id);
$topicStmt->execute();
$topicProgress = $topicStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$topicStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillSync - Student Dashboard</title>
<link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { 
  font-family: 'Poppins', sans-serif; 
  display: flex; 
  background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
  min-height: 100vh; 
}

/* Sidebar Styles */
.sidebar { 
  width: 240px; 
  background: #FFFFFF; 
  border-right: 2px solid #4B8B6E; 
  height: 100vh; 
  padding: 20px 0; 
  position: fixed; 
  display: flex; 
  flex-direction: column; 
  justify-content: space-between; 
  box-shadow: 2px 0 10px rgba(75, 139, 110, 0.1); 
}
.sidebar-content a { 
  display: flex; 
  align-items: center; 
  gap: 10px; 
  color: #4B8B6E; 
  padding: 12px 20px; 
  text-decoration: none; 
  font-weight: 500; 
  transition: all 0.3s; 
}
.sidebar-content a:hover, .sidebar-content a.active { 
  background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
  color: white; 
  border-radius: 0 25px 25px 0; 
  margin-right: 10px; 
}
.sidebar .logo { 
  text-align: center; 
  margin-bottom: 20px; 
}
.sidebar .logo img { 
  width: 50px; 
  height: 50px; 
  border-radius: 50%; 
}
.sidebar .logo h2 { 
  font-size: 18px; 
  color: #4B8B6E; 
  margin-top: 10px; 
  font-weight: 700;
}
.student-info { 
  text-align: center; 
  padding: 20px; 
  font-size: 14px; 
  border-top: 2px solid #6BAF92; 
}
.student-info img { 
  width: 40px; 
  height: 40px; 
  border-radius: 50%; 
  margin-bottom: 5px; 
}

/* Main Content */
.main-content { 
  margin-left: 240px; 
  padding: 30px; 
  width: calc(100% - 240px); 
}
.page-header { 
  margin-bottom: 40px; 
}
.page-title { 
  font-size: 2.5rem; 
  color: #2D5A47; 
  margin-bottom: 10px; 
  font-weight: 700; 
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.page-subtitle { 
  color: #3D6B54; 
  font-size: 1.1rem; 
  font-weight: 500;
}

/* Enhanced KPI Cards */
.kpi-cards { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
  gap: 25px; 
  margin-bottom: 40px; 
}
.kpi-card { 
  background: #FFFFFF; 
  padding: 30px; 
  border-radius: 20px; 
  box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); 
  transition: all 0.3s ease; 
  position: relative; 
  overflow: hidden; 
  border: 2px solid rgba(107, 175, 146, 0.2);
}
.kpi-card::before { 
  content: ''; 
  position: absolute; 
  top: 0; 
  left: 0; 
  right: 0; 
  height: 4px; 
}
.kpi-card.activities::before { 
  background: linear-gradient(90deg, #4B8B6E, #6BAF92); 
}
.kpi-card.coding::before { 
  background: linear-gradient(90deg, #E8C547, #F4D77C); 
}
.kpi-card.assessments::before { 
  background: linear-gradient(90deg, #6BAF92, #4B8B6E); 
}
.kpi-card.videos::before { 
  background: linear-gradient(90deg, #4B8B6E, #E8C547); 
}
.kpi-card:hover { 
  transform: translateY(-5px); 
  box-shadow: 0 20px 40px rgba(75, 139, 110, 0.25); 
  border-color: #4B8B6E;
}
.kpi-header { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  margin-bottom: 20px; 
}
.kpi-icon { 
  font-size: 2.5rem; 
}
.kpi-icon.activities { 
  color: #4B8B6E; 
}
.kpi-icon.coding { 
  color: #E8C547; 
}
.kpi-icon.assessments { 
  color: #6BAF92; 
}
.kpi-icon.videos { 
  color: #4B8B6E; 
}
.kpi-value { 
  font-size: 2.5rem; 
  font-weight: bold; 
  color: #4B8B6E; 
}
.kpi-label { 
  font-size: 1rem; 
  color: #6BAF92; 
  margin-bottom: 15px; 
  font-weight: 500;
}
.kpi-detail { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  padding: 10px 0; 
  border-top: 1px solid rgba(107, 175, 146, 0.2); 
}
.kpi-detail-item { 
  text-align: center; 
}
.kpi-detail-value { 
  font-size: 1.4rem; 
  font-weight: bold; 
  color: #4B8B6E; 
}
.kpi-detail-label { 
  font-size: 0.8rem; 
  color: #6BAF92; 
}

/* Chart Sections */
.chart-section { 
  background: #FFFFFF; 
  border-radius: 20px; 
  padding: 30px; 
  box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); 
  margin-bottom: 30px; 
  border: 2px solid rgba(107, 175, 146, 0.2);
}
.chart-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 25px; 
}
.chart-title { 
  font-size: 1.5rem; 
  color: #4B8B6E; 
  font-weight: 700; 
}
.chart-controls { 
  display: flex; 
  gap: 15px; 
  align-items: center; 
}
.chart-select { 
  padding: 8px 15px; 
  border: 2px solid #6BAF92; 
  border-radius: 20px; 
  background: white; 
  font-weight: 500; 
  transition: all 0.3s; 
  color: #4B8B6E;
  font-family: 'Poppins', sans-serif;
}
.chart-select:focus { 
  outline: none; 
  border-color: #4B8B6E; 
}

/* Progress Overview */
.progress-overview { 
  margin-bottom: 30px; 
}
.weekly-progress { 
  background: #FFFFFF; 
  border-radius: 20px; 
  padding: 30px; 
  box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); 
  border: 2px solid rgba(107, 175, 146, 0.2);
}

/* Topic Progress Cards */
.topic-progress-section { 
  margin-top: 40px; 
}
.topic-cards { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
  gap: 25px; 
}
.topic-card { 
  background: #FFFFFF; 
  border-radius: 15px; 
  padding: 25px; 
  box-shadow: 0 8px 25px rgba(75, 139, 110, 0.15); 
  transition: all 0.3s ease; 
  border-left: 5px solid rgba(107, 175, 146, 0.3); 
}
.topic-card:hover { 
  transform: translateY(-3px); 
  box-shadow: 0 15px 35px rgba(75, 139, 110, 0.25); 
}
.topic-card.improved { 
  border-left-color: #4B8B6E; 
}
.topic-card.declined { 
  border-left-color: #E8C547; 
}
.topic-card.stable { 
  border-left-color: #6BAF92; 
}
.topic-header { 
  margin-bottom: 20px; 
}
.topic-name { 
  font-size: 1.2rem; 
  font-weight: bold; 
  color: #4B8B6E; 
  margin-bottom: 8px; 
}
.topic-subject { 
  font-size: 0.9rem; 
  color: #6BAF92; 
  margin-bottom: 15px; 
}
.test-scores { 
  display: grid; 
  grid-template-columns: 1fr 1fr; 
  gap: 15px; 
  margin-bottom: 20px; 
}
.test-score { 
  text-align: center; 
  padding: 15px; 
  background: rgba(107, 175, 146, 0.05); 
  border-radius: 10px; 
}
.test-score.pre { 
  border-left: 3px solid #6BAF92; 
}
.test-score.post { 
  border-left: 3px solid #4B8B6E; 
}
.score-value { 
  font-size: 1.5rem; 
  font-weight: bold; 
  color: #4B8B6E; 
}
.score-label { 
  font-size: 0.8rem; 
  color: #6BAF92; 
  margin-top: 5px; 
}
.improvement-indicator { 
  text-align: center; 
  padding: 10px; 
  border-radius: 10px; 
  font-weight: bold; 
}
.improvement-indicator.positive { 
  background: linear-gradient(135deg, rgba(75, 139, 110, 0.1), rgba(107, 175, 146, 0.2)); 
  color: #4B8B6E; 
}
.improvement-indicator.negative { 
  background: linear-gradient(135deg, rgba(232, 197, 71, 0.1), rgba(244, 215, 124, 0.2)); 
  color: #E8C547; 
}
.improvement-indicator.neutral { 
  background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(75, 139, 110, 0.1)); 
  color: #6BAF92; 
}

/* Class Level & Progress Card */
.class-level-card { 
  background: linear-gradient(135deg, #4B8B6E 0%, #6BAF92 100%); 
  border-radius: 20px; 
  padding: 30px; 
  margin-bottom: 30px; 
  box-shadow: 0 15px 35px rgba(75, 139, 110, 0.3); 
  color: white; 
  animation: slideIn 0.5s ease; 
}
@keyframes slideIn { 
  from { opacity: 0; transform: translateY(-20px); } 
  to { opacity: 1; transform: translateY(0); } 
}
.class-level-content { 
  display: grid; 
  grid-template-columns: auto 1fr; 
  gap: 30px; 
  align-items: center; 
}
.class-level-info { 
  display: flex; 
  align-items: center; 
  gap: 20px; 
}
.class-badge { 
  background: rgba(255,255,255,0.2); 
  backdrop-filter: blur(10px); 
  padding: 20px 30px; 
  border-radius: 50px; 
  display: flex; 
  align-items: center; 
  gap: 15px; 
  font-size: 1.2rem; 
  font-weight: bold; 
  border: 2px solid rgba(255,255,255,0.3); 
}
.class-badge i { 
  font-size: 2rem; 
}
.class-badge.beginner { 
  background: linear-gradient(135deg, rgba(232, 197, 71, 0.3), rgba(244, 215, 124, 0.3)); 
}
.class-badge.intermediate { 
  background: linear-gradient(135deg, rgba(107, 175, 146, 0.3), rgba(75, 139, 110, 0.3)); 
}
.class-details h3 { 
  margin-bottom: 8px; 
  font-size: 1.3rem; 
  color: #FFFFFF;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  font-weight: 700;
}
.class-details p { 
  font-size: 1rem; 
  opacity: 1; 
  color: #FFFFFF;
  font-weight: 500;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
.class-progress-bar { 
  flex: 1; 
}
.progress-header { 
  display: flex; 
  justify-content: space-between; 
  margin-bottom: 10px; 
  font-size: 0.95rem; 
  font-weight: 600; 
  color: #FFFFFF;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
.progress-percentage { 
  font-size: 1.2rem; 
  color: #FFFFFF;
  font-weight: 700;
}
.progress-track { 
  height: 20px; 
  background: rgba(255,255,255,0.2); 
  border-radius: 10px; 
  overflow: hidden; 
  position: relative; 
}
.progress-fill { 
  height: 100%; 
  background: linear-gradient(90deg, #E8C547, #F4D77C); 
  border-radius: 10px; 
  transition: width 0.5s ease; 
  position: relative; 
  animation: fillProgress 1s ease; 
}
@keyframes fillProgress { 
  from { width: 0; } 
}
.progress-fill::after { 
  content: ''; 
  position: absolute; 
  top: 0; 
  left: 0; 
  right: 0; 
  bottom: 0; 
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); 
  animation: shimmer 2s infinite; 
}
@keyframes shimmer { 
  0%, 100% { transform: translateX(-100%); } 
  50% { transform: translateX(100%); } 
}
.progress-footer { 
  display: flex; 
  justify-content: space-between; 
  margin-top: 8px; 
  font-size: 0.85rem; 
  opacity: 1; 
  color: #FFFFFF;
  font-weight: 500;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

/* Onboarding Banner */
.onboarding-banner { 
  background: linear-gradient(135deg, #E8C547 0%, #F4D77C 100%); 
  border-radius: 20px; 
  padding: 30px; 
  margin-bottom: 30px; 
  box-shadow: 0 15px 35px rgba(232, 197, 71, 0.3); 
  animation: slideIn 0.5s ease; 
}
.onboarding-content { 
  display: flex; 
  align-items: center; 
  gap: 25px; 
  color: #4B8B6E; 
}
.onboarding-icon { 
  font-size: 3.5rem; 
  animation: pulse 2s infinite; 
}
@keyframes pulse { 
  0%, 100% { transform: scale(1); } 
  50% { transform: scale(1.1); } 
}
.onboarding-text { 
  flex: 1; 
}
.onboarding-text h3 { 
  font-size: 1.8rem; 
  margin-bottom: 10px; 
  color: #4B8B6E;
  font-weight: 700;
}
.onboarding-text p { 
  font-size: 1.1rem; 
  opacity: 0.95; 
  line-height: 1.6; 
  color: #4B8B6E;
}
.onboarding-actions { 
  display: flex; 
  gap: 15px; 
  flex-direction: column; 
}
.btn-assessment, .btn-tour { 
  padding: 15px 30px; 
  border-radius: 50px; 
  font-weight: 600; 
  font-size: 1rem; 
  text-decoration: none; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  gap: 10px; 
  transition: all 0.3s; 
  border: none; 
  cursor: pointer; 
  font-family: 'Poppins', sans-serif;
}
.btn-assessment { 
  background: #4B8B6E; 
  color: white; 
  box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3); 
}
.btn-assessment:hover { 
  transform: translateY(-3px); 
  box-shadow: 0 8px 20px rgba(75, 139, 110, 0.4); 
}
.btn-tour { 
  background: rgba(255,255,255,0.5); 
  color: #4B8B6E; 
  border: 2px solid rgba(75, 139, 110, 0.3); 
  backdrop-filter: blur(10px); 
}
.btn-tour:hover { 
  background: rgba(255,255,255,0.7); 
  transform: translateY(-3px); 
}

/* Tour Overlay */
.tour-overlay { 
  position: fixed; 
  top: 0; 
  left: 0; 
  right: 0; 
  bottom: 0; 
  z-index: 10000; 
}
.tour-backdrop { 
  position: absolute; 
  top: 0; 
  left: 0; 
  right: 0; 
  bottom: 0; 
  background: rgba(0,0,0,0.7); 
  backdrop-filter: blur(5px); 
}
.tour-content { 
  position: absolute; 
  top: 50%; 
  left: 50%; 
  transform: translate(-50%, -50%); 
  background: white; 
  border-radius: 20px; 
  padding: 40px; 
  max-width: 500px; 
  width: 90%; 
  box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
  animation: tourPop 0.3s ease; 
}
@keyframes tourPop { 
  from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); } 
  to { opacity: 1; transform: translate(-50%, -50%) scale(1); } 
}
.tour-close { 
  position: absolute; 
  top: 15px; 
  right: 15px; 
  background: rgba(107, 175, 146, 0.1); 
  border: none; 
  width: 35px; 
  height: 35px; 
  border-radius: 50%; 
  font-size: 1.5rem; 
  cursor: pointer; 
  transition: all 0.3s; 
  color: #4B8B6E;
}
.tour-close:hover { 
  background: rgba(107, 175, 146, 0.2); 
  transform: rotate(90deg); 
}
.tour-step h3 { 
  font-size: 1.8rem; 
  color: #4B8B6E; 
  margin-bottom: 15px; 
  font-weight: 700;
}
.tour-step p { 
  font-size: 1.1rem; 
  color: #6BAF92; 
  line-height: 1.7; 
  margin-bottom: 30px; 
}
.tour-nav { 
  display: flex; 
  justify-content: space-between; 
  gap: 15px; 
}
.tour-nav button { 
  flex: 1; 
  padding: 12px 24px; 
  border-radius: 50px; 
  font-weight: 600; 
  font-size: 1rem; 
  cursor: pointer; 
  transition: all 0.3s; 
  border: none; 
  font-family: 'Poppins', sans-serif;
}
.tour-skip { 
  background: rgba(107, 175, 146, 0.1); 
  color: #6BAF92; 
}
.tour-skip:hover { 
  background: rgba(107, 175, 146, 0.2); 
}
.tour-prev { 
  background: rgba(107, 175, 146, 0.1); 
  color: #4B8B6E; 
}
.tour-prev:hover { 
  background: rgba(107, 175, 146, 0.2); 
}
.tour-next, .tour-finish { 
  background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
  color: white; 
  box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3); 
}
.tour-next:hover, .tour-finish:hover { 
  transform: translateY(-3px); 
  box-shadow: 0 8px 20px rgba(75, 139, 110, 0.4); 
}

/* Responsive Design */
@media (max-width: 1024px) {
    .kpi-cards { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
    .class-level-content { grid-template-columns: 1fr; }
    .onboarding-content { flex-direction: column; text-align: center; }
}

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main-content { margin-left: 0; width: 100%; }
    .kpi-cards { grid-template-columns: 1fr; }
    .topic-cards { grid-template-columns: 1fr; }
    .class-level-info { flex-direction: column; text-align: center; }
    .onboarding-actions { width: 100%; }
}
</style>
</head>
<body>
<div class="sidebar">
  <div>
    <div class="logo">
      <img src="LOGO.png" alt="Logo">
      <h2>SkillSync</h2>
    </div>
    <div class="sidebar-content">
      <a href="student_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
      <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
      <a href="progress_history.php"><i class="fas fa-history"></i> Progress History</a>
      <a href="video_materials.php"><i class="fas fa-book-open"></i> Learning Materials</a>
      <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
      <a href="recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
      <a href="coding_practice.php"><i class="fas fa-code"></i> Coding Practice</a>
      <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
      <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
  <div class="student-info">
    <img src="student.jpg" alt="Student">
    <div><strong><?= htmlspecialchars($user['username'] ?? 'Student') ?></strong></div>
    <div><?= htmlspecialchars($user['email'] ?? 'student@email.com') ?></div>
  </div>
</div>

<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <h1 class="page-title">Welcome back, <?= htmlspecialchars($user['username'] ?? 'Student') ?>! 👋</h1>
    <p class="page-subtitle">Here's your learning progress overview</p>
  </div>

  <!-- Class Level & Progress Card -->
  <?php if (!$isNewUser && $assessmentData): ?>
  <div class="class-level-card">
    <div class="class-level-content">
      <div class="class-level-info">
        <div class="class-badge <?= strtolower($classLevel) ?>">
          <i class="fas fa-<?= $classLevel === 'Beginner' ? 'seedling' : 'rocket' ?>"></i>
          <span><?= $classLevel ?> Level</span>
        </div>
        <div class="class-details">
          <h3>Your Current Progress</h3>
          <p>Overall Score: <strong><?= round($overallPercentage) ?>%</strong></p>
        </div>
      </div>
      <div class="class-progress-bar">
        <div class="progress-header">
          <span>Progress to Next Level</span>
          <span class="progress-percentage"><?= round($progressToNext) ?>%</span>
        </div>
        <div class="progress-track">
          <div class="progress-fill" style="width: <?= round($progressToNext) ?>%"></div>
        </div>
        <div class="progress-footer">
          <?php if ($classLevel === 'Beginner'): ?>
            <span>Beginner</span>
            <span>Target: 75%</span>
            <span>Intermediate</span>
          <?php else: ?>
            <span>Intermediate</span>
            <span>Keep excelling!</span>
            <span>Expert</span>
          <?php endif; ?>
        </div>
        
        <?php 
        // Show Level Promotion Test button when student reaches 100% progress
        if ($classLevel === 'Beginner' && $progressToNext >= 100): 
        ?>
        <div class="promotion-test-alert" style="margin-top: 20px; padding: 15px; background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%); border-radius: 10px; text-align: center;">
          <h4 style="color: white; margin-bottom: 10px;">
            <i class="fas fa-trophy"></i> Ready for Level Promotion!
          </h4>
          <p style="color: white; margin-bottom: 15px;">
            You've completed all Beginner content. Take the Level Promotion Test to advance to Intermediate level!
          </p>
          <a href="level_promotion_test.php?subject=<?= htmlspecialchars($subjectCode ?? 'OOP1') ?>&level=Beginner" 
             class="btn" 
             style="background: white; color: #f39c12; font-weight: bold; padding: 12px 30px; border-radius: 25px; text-decoration: none; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <i class="fas fa-graduation-cap"></i> Take Level Promotion Test
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- New User Onboarding Guide -->
  <?php if ($isNewUser): ?>
  <div class="onboarding-banner">
    <div class="onboarding-content">
      <div class="onboarding-icon">
        <i class="fas fa-star"></i>
      </div>
      <div class="onboarding-text">
        <h3>Welcome to SkillSync! 🎉</h3>
        <p>You're new here! Take our assessment to unlock personalized learning paths and track your progress.</p>
      </div>
      <div class="onboarding-actions">
        <a href="pre_test.php" class="btn-assessment">
          <i class="fas fa-clipboard-check"></i> Take Assessment
        </a>
        <button class="btn-tour" onclick="startTour()">
          <i class="fas fa-route"></i> Take Tour
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Interactive Tour Overlay -->
  <div id="tourOverlay" class="tour-overlay" style="display: none;">
    <div class="tour-backdrop"></div>
    <div class="tour-content">
      <button class="tour-close" onclick="closeTour()">×</button>
      <div class="tour-step" id="tourStep1" style="display: none;">
        <div class="tour-arrow"></div>
        <h3>📚 Learning Materials</h3>
        <p>Access video tutorials, documentation, and resources to strengthen your knowledge.</p>
        <div class="tour-nav">
          <button onclick="closeTour()" class="tour-skip">Skip Tour</button>
          <button onclick="nextTourStep(2)" class="tour-next">Next</button>
        </div>
      </div>
      <div class="tour-step" id="tourStep2" style="display: none;">
        <div class="tour-arrow"></div>
        <h3>💡 Recommendations</h3>
        <p>Get personalized learning suggestions based on your assessment results and performance.</p>
        <div class="tour-nav">
          <button onclick="prevTourStep(1)" class="tour-prev">Back</button>
          <button onclick="nextTourStep(3)" class="tour-next">Next</button>
        </div>
      </div>
      <div class="tour-step" id="tourStep3" style="display: none;">
        <div class="tour-arrow"></div>
        <h3>💻 Coding Practice</h3>
        <p>Solve programming challenges to improve your skills and earn points.</p>
        <div class="tour-nav">
          <button onclick="prevTourStep(2)" class="tour-prev">Back</button>
          <button onclick="nextTourStep(4)" class="tour-next">Next</button>
        </div>
      </div>
      <div class="tour-step" id="tourStep4" style="display: none;">
        <div class="tour-arrow"></div>
        <h3>📊 Dashboard</h3>
        <p>Track your progress with detailed statistics, charts, and performance metrics.</p>
        <div class="tour-nav">
          <button onclick="prevTourStep(3)" class="tour-prev">Back</button>
          <button onclick="completeTour()" class="tour-finish">Got It!</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Enhanced KPI Cards -->
  <div class="kpi-cards">
    <!-- Activities Card -->
    <div class="kpi-card activities">
      <div class="kpi-header">
        <i class="fas fa-tasks kpi-icon activities"></i>
        <div class="kpi-value"><?= $activities_completed ?></div>
      </div>
      <div class="kpi-label">Activities Completed</div>
      <div class="kpi-detail">
        <div class="kpi-detail-item">
          <div class="kpi-detail-value"><?= $totalActivities ?></div>
          <div class="kpi-detail-label">Available</div>
        </div>
        <div class="kpi-detail-item">
          <div class="kpi-detail-value"><?= $totalActivities > 0 ? round(($activities_completed / $totalActivities) * 100) : 0 ?>%</div>
          <div class="kpi-detail-label">Progress</div>
        </div>
      </div>
    </div>

    <!-- Coding Practice Card -->
    <div class="kpi-card coding">
      <div class="kpi-header">
        <i class="fas fa-code kpi-icon coding"></i>
        <div class="kpi-value"><?= $codingStats['problems_solved'] ?></div>
      </div>
      <div class="kpi-label">Coding Problems Solved</div>
      <div class="kpi-detail">
        <div class="kpi-detail-item">
          <div class="kpi-detail-value"><?= $codingStats['total_score'] ?></div>
          <div class="kpi-detail-label">Total Score</div>
        </div>
        <div class="kpi-detail-item">
          <div class="kpi-detail-value"><?= $codingStats['best_score'] ?></div>
          <div class="kpi-detail-label">Best Score</div>
        </div>
      </div>
    </div>

    <!-- Assessments Card -->
    <div class="kpi-card assessments">
      <div class="kpi-header">
        <i class="fas fa-clipboard-check kpi-icon assessments"></i>
        <div class="kpi-value"><?= ($assessments['pre_tests'] + $assessments['post_tests']) ?></div>
      </div>
      <div class="kpi-label">Assessments Taken</div>
      <div class="kpi-detail">
        <div class="kpi-detail-item">
          <div class="kpi-detail-value"><?= $assessments['pre_tests'] ?></div>
          <div class="kpi-detail-label">Pre-Tests</div>
        </div>
        <div class="kpi-detail-item">
          <div class="kpi-detail-value"><?= $assessments['post_tests'] ?></div>
          <div class="kpi-detail-label">Post-Tests</div>
        </div>
      </div>
    </div>

    <!-- Videos Card -->
    <div class="kpi-card videos">
      <div class="kpi-header">
        <i class="fas fa-play-circle kpi-icon videos"></i>
        <div class="kpi-value"><?= $videos_watched ?></div>
      </div>
      <div class="kpi-label">Videos Watched</div>
      <div class="kpi-detail">
        <div class="kpi-detail-item">
          <div class="kpi-detail-value">✨</div>
          <div class="kpi-detail-label">Learning</div>
        </div>
        <div class="kpi-detail-item">
          <div class="kpi-detail-value">📚</div>
          <div class="kpi-detail-label">Materials</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Overview -->
  <div class="progress-overview">
    <!-- Weekly Progress Chart -->
    <div class="weekly-progress">
      <div class="chart-header">
        <h3 class="chart-title"><i class="fas fa-chart-line"></i> Weekly Progress & Score Overview</h3>
      </div>
      <canvas id="weeklyChart" style="height: 300px; max-height: 300px;"></canvas>
      <p style="margin-top: 15px; font-size: 14px; color: #6c757d; text-align: center;">
        Track your weekly activities, quizzes, and videos completed over time
      </p>
    </div>
  </div>

  <!-- Topic Progress Section -->
  <div class="topic-progress-section">
    <div class="chart-section">
      <div class="chart-header">
        <h3 class="chart-title"><i class="fas fa-graduation-cap"></i> Topic Progress & Improvement Tracking</h3>
      </div>
      <div class="topic-cards">
        <?php foreach ($topicProgress as $topic): 
          // Calculate percentage-based improvement
          $prePercentage = null;
          $postPercentage = null;
          $improvement = 0;
          $improvementClass = 'stable';
          $improvementText = 'No data';
          
          if ($topic['pre_score'] !== null) {
            // Assuming pre-test is out of 10 questions (adjust as needed)
            $prePercentage = round(($topic['pre_score'] / 10) * 100, 1);
          }
          
          if ($topic['post_score'] !== null) {
            // Post-test scores are already percentages
            $postPercentage = round($topic['post_score'], 1);
          }
          
          if ($prePercentage !== null && $postPercentage !== null) {
            $improvement = $postPercentage - $prePercentage;
            if ($improvement > 5) {
              $improvementClass = 'improved';
              $improvementText = '+' . round($improvement, 1) . '% Improvement! 🎉';
            } elseif ($improvement < -5) {
              $improvementClass = 'declined';
              $improvementText = round($improvement, 1) . '% Decline 📉';
            } else {
              $improvementClass = 'stable';
              $improvementText = 'Stable Performance 📊';
            }
          } elseif ($postPercentage !== null) {
            $improvementClass = 'improved';
            $improvementText = 'Post-test completed! 🎯';
          } elseif ($prePercentage !== null) {
            $improvementClass = 'stable';
            $improvementText = 'Pre-test completed ✅';
          }
        ?>
          <div class="topic-card <?= $improvementClass ?>">
            <div class="topic-header">
              <div class="topic-name"><?= htmlspecialchars($topic['name']) ?></div>
              <div class="topic-subject"><?= htmlspecialchars($topic['subject_code']) ?></div>
            </div>
            
            <div class="test-scores">
              <div class="test-score pre">
                <div class="score-value"><?= $prePercentage !== null ? $prePercentage . '%' : '—' ?></div>
                <div class="score-label">Pre-Test</div>
              </div>
              <div class="test-score post">
                <div class="score-value"><?= $postPercentage !== null ? $postPercentage . '%' : '—' ?></div>
                <div class="score-label">Post-Test</div>
              </div>
            </div>
            
            <div class="improvement-indicator <?= $improvementClass === 'improved' ? 'positive' : ($improvementClass === 'declined' ? 'negative' : 'neutral') ?>">
              <?= $improvementText ?>
            </div>
            
            <?php if ($topic['activity_attempts'] > 0): ?>
            <div style="margin-top: 15px; text-align: center; color: #6c757d; font-size: 0.9rem;">
              <i class="fas fa-dumbbell"></i> <?= $topic['activity_attempts'] ?> practice sessions completed
              <br>Average: <?= round($topic['activity_avg_score'], 1) ?>%
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Weekly Progress Chart (using working data from progress.php)
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
new Chart(weeklyCtx, {
  type: 'line',
  data: {
    labels: <?= json_encode($weekLabels) ?>,
    datasets: [
      {
        label: 'Activities Completed',
        data: <?= json_encode($actData) ?>,
        borderColor: '#27ae60',
        backgroundColor: 'rgba(39, 174, 96, 0.1)',
        tension: 0.3,
        fill: true,
        borderWidth: 2,
        pointBackgroundColor: '#27ae60'
      },
      {
        label: 'Quizzes Taken',
        data: <?= json_encode($quizData) ?>,
        borderColor: '#3498db',
        backgroundColor: 'rgba(52, 152, 219, 0.1)',
        tension: 0.3,
        fill: true,
        borderWidth: 2,
        pointBackgroundColor: '#3498db'
      },
      {
        label: 'Videos Watched',
        data: <?= json_encode($videoData) ?>,
        borderColor: '#f39c12',
        backgroundColor: 'rgba(243, 156, 18, 0.1)',
        tension: 0.3,
        fill: true,
        borderWidth: 2,
        pointBackgroundColor: '#f39c12'
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      title: {
        display: true,
        text: 'Your Learning Activity Over Time'
      },
      legend: {
        position: 'top',
        labels: {
          color: '#004e64',
          font: { size: 13 }
        }
      },
      tooltip: {
        mode: 'index',
        intersect: false
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 1
        },
        title: {
          display: true,
          text: 'Count'
        }
      },
      x: {
        title: {
          display: true,
          text: 'Week'
        }
      }
    }
  }
});

// Helper function to get week number
Date.prototype.getWeek = function() {
  var d = new Date(Date.UTC(this.getFullYear(), this.getMonth(), this.getDate()));
  var dayNum = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - dayNum);
  var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
  return Math.ceil((((d - yearStart) / 86400000) + 1)/7);
};

// ==========================================
// Interactive Tour Functions
// ==========================================
let currentTourStep = 0;

function startTour() {
    document.getElementById('tourOverlay').style.display = 'block';
    showTourStep(1);
}

function closeTour() {
    document.getElementById('tourOverlay').style.display = 'none';
    hideAllTourSteps();
    // Mark tour as completed
    fetch('complete_onboarding.php', { method: 'POST' })
        .then(() => console.log('Tour completed'))
        .catch(err => console.error('Error:', err));
}

function hideAllTourSteps() {
    for (let i = 1; i <= 4; i++) {
        const step = document.getElementById('tourStep' + i);
        if (step) step.style.display = 'none';
    }
}

function showTourStep(stepNum) {
    hideAllTourSteps();
    const step = document.getElementById('tourStep' + stepNum);
    if (step) {
        step.style.display = 'block';
        currentTourStep = stepNum;
    }
}

function nextTourStep(stepNum) {
    showTourStep(stepNum);
}

function prevTourStep(stepNum) {
    showTourStep(stepNum);
}

function completeTour() {
    closeTour();
    // Show success message
    alert('🎉 Tour completed! You\'re all set to start learning with SkillSync!');
}
</script>
</body>
</html>
