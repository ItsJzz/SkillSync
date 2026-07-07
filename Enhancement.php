<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user has taken any pre-assessment
// Check both student_tests table AND students table (for assessment_data)
$hasPreAssessment = false;

// First check student_tests table
$checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM student_tests WHERE student_id = ? AND test_type = 'pre'");
$checkStmt->bind_param("i", $student_id);
$checkStmt->execute();
$result = $checkStmt->get_result()->fetch_assoc();
$hasPreAssessment = $result['count'] > 0;
$checkStmt->close();

// If not found in student_tests, check students table for assessment_data
if (!$hasPreAssessment) {
    $checkStmt = $conn->prepare("SELECT assessment_data FROM students WHERE user_id = ?");
    $checkStmt->bind_param("i", $student_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Check if assessment_data exists and is not empty
        $hasPreAssessment = !empty($row['assessment_data']) && $row['assessment_data'] != 'null';
    }
    $checkStmt->close();
}

$topicPerformance = [];
$performanceSummary = ['strong' => 0, 'good' => 0, 'focus' => 0];

if ($hasPreAssessment) {
    // Check if data is from students table (JSON format) or student_tests table
    $studentDataStmt = $conn->prepare("SELECT assessment_data FROM students WHERE user_id = ?");
    $studentDataStmt->bind_param("i", $student_id);
    $studentDataStmt->execute();
    $studentDataResult = $studentDataStmt->get_result();
    $studentRow = $studentDataResult->fetch_assoc();
    $assessmentData = $studentRow ? json_decode($studentRow['assessment_data'], true) : null;
    $studentDataStmt->close();
    
    // If assessment_data exists in students table, use that
    if ($assessmentData && isset($assessmentData['topic_scores'])) {
        // Process data from students.assessment_data JSON
        foreach ($assessmentData['topic_scores'] as $topicId => $topicData) {
            $percentage = $topicData['percentage'] ?? 0;
            
            // Determine performance level
            $performanceLevel = '';
            if ($percentage >= 80) {
                $performanceLevel = 'strong';
            } elseif ($percentage >= 60) {
                $performanceLevel = 'good';
            } else {
                $performanceLevel = 'focus';
            }
            
            $topicPerformance[] = [
                'id' => $topicId,
                'name' => $topicData['name'] ?? "Topic $topicId",
                'score' => ($topicData['quiz_correct'] ?? 0) + ($topicData['simulation_correct'] ?? 0),
                'percentage' => $percentage,
                'pre_percentage' => $percentage,
                'post_percentage' => null,
                'total_questions' => ($topicData['quiz_total'] ?? 8) + ($topicData['simulation_total'] ?? 8),
                'performance_level' => $performanceLevel
            ];
            
            $performanceSummary[$performanceLevel]++;
        }
    } else {
        // Fallback to old method using student_tests table
        // Get topic performance based on HIGHEST scores from both pre-test and post-test
        // First, get the total questions used in the pre-assessment (50 questions distributed among topics)
        $totalAssessmentQuestions = 50;
        
        // Get the number of topics that were assessed to calculate questions per topic
        $topicCountQuery = "
            SELECT COUNT(DISTINCT st.topic_id) as topic_count
            FROM student_tests st
            WHERE st.student_id = ? AND st.test_type = 'pre'
        ";
        
        $topicCountStmt = $conn->prepare($topicCountQuery);
        $topicCountStmt->bind_param("i", $student_id);
        $topicCountStmt->execute();
        $topicCountResult = $topicCountStmt->get_result()->fetch_assoc();
        $assessedTopicCount = $topicCountResult['topic_count'];
        $topicCountStmt->close();
        
        // Calculate questions distributed per topic in the assessment
        $questionsPerTopic = ($assessedTopicCount > 0) ? floor($totalAssessmentQuestions / $assessedTopicCount) : 0;
        $remainder = ($assessedTopicCount > 0) ? $totalAssessmentQuestions % $assessedTopicCount : 0;
        
        // Updated query to get scores from both pre-test and post-test tables
        $performanceQuery = "
            SELECT 
                t.id, 
                t.name,
                MAX(st.score) as pre_score,
                MAX(upta.score) as post_score
            FROM topics t
            LEFT JOIN student_tests st ON t.id = st.topic_id AND st.student_id = ? AND st.test_type = 'pre'
            LEFT JOIN user_post_test_attempts upta ON t.id = upta.topic_id AND upta.user_id = ? AND upta.completed_at IS NOT NULL
            JOIN subjects s ON t.subject_id = s.id
            WHERE (st.score IS NOT NULL OR upta.score IS NOT NULL)
            GROUP BY t.id, t.name
            ORDER BY t.id ASC
        ";
        
        $stmt = $conn->prepare($performanceQuery);
        $stmt->bind_param("ii", $student_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topicIndex = 0;
        while ($row = $result->fetch_assoc()) {
            // Calculate actual questions assigned to this topic in the pre-assessment
            $extra = ($topicIndex < $remainder) ? 1 : 0;
            $actualQuestionsForTopic = $questionsPerTopic + $extra;
            
            $preScore = $row['pre_score'];
            $postScore = $row['post_score'];
            
            // Calculate percentages for each test type
            $prePercentage = null;
            $postPercentage = null;
            
            if ($preScore !== null) {
                // Pre-test: score out of distributed questions (e.g., 7/10 = 70%)
                $prePercentage = ($actualQuestionsForTopic > 0) ? round(($preScore / $actualQuestionsForTopic) * 100, 1) : 0;
            }
            
            if ($postScore !== null) {
                // Post-test: typically scored out of 20 questions, but could be stored as percentage
                if ($postScore <= 100) {
                    // If score is <= 100, treat it as a percentage already
                    $postPercentage = round($postScore, 1);
                } else {
                    // If score > 100, it might be raw score, calculate percentage
                    // Assuming post-test has 20 questions typically
                    $postPercentage = round(($postScore / 20) * 100, 1);
                }
            }
            
            // Use the HIGHEST percentage as the current performance
            $highestPercentage = 0;
            $displayScore = 0;
            
            if ($prePercentage !== null && $postPercentage !== null) {
                // Both tests taken - use the higher percentage
                $highestPercentage = max($prePercentage, $postPercentage);
                if ($postPercentage >= $prePercentage) {
                    // Post-test is better, calculate display score based on post-test
                    $displayScore = round(($postPercentage / 100) * 20); // Assuming 20 questions for post-test
                } else {
                    // Pre-test is better, use pre-test score
                    $displayScore = $preScore;
                }
            } elseif ($postPercentage !== null) {
                // Only post-test taken
                $highestPercentage = $postPercentage;
                $displayScore = round(($postPercentage / 100) * 20);
            } elseif ($prePercentage !== null) {
                // Only pre-test taken
                $highestPercentage = $prePercentage;
                $displayScore = $preScore;
            }
            
            // Determine performance level based on highest percentage
            $performanceLevel = '';
            if ($highestPercentage >= 80) {
                $performanceLevel = 'strong';
            } elseif ($highestPercentage >= 60) {
                $performanceLevel = 'good';
            } else {
                $performanceLevel = 'focus';
            }
            
            $row['score'] = $displayScore;
            $row['percentage'] = $highestPercentage;
            $row['pre_percentage'] = $prePercentage;
            $row['post_percentage'] = $postPercentage;
            $row['total_questions'] = $actualQuestionsForTopic;
            $row['performance_level'] = $performanceLevel;
            
            $topicPerformance[] = $row;
            $performanceSummary[$performanceLevel]++;
            $topicIndex++;
        }
        $stmt->close();
    }
}

// Fetch activities data for display
$activitiesData = [];
$activitiesPath = 'Activity/activities.json';
if (file_exists($activitiesPath)) {
    $activitiesData = json_decode(file_get_contents($activitiesPath), true);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>SkillSync - Enhancement Process</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
      font-family: 'Poppins', sans-serif; 
      display: flex; 
      background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
      min-height: 100vh;
      color: #2c3e50; 
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
      font-size: 0.95rem;
    }
    .sidebar-content a:hover, .sidebar-content a.active { 
      background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
      color: white; 
      border-radius: 0 25px 25px 0; 
      margin-right: 10px; 
    }
    .sidebar .logo { text-align: center; margin-bottom: 20px; }
    .sidebar .logo img { width: 50px; height: 50px; border-radius: 50%; }
    .sidebar .logo h2 { font-size: 18px; color: #4B8B6E; margin-top: 10px; font-weight: 700; }
    .student-info { 
      text-align: center; 
      padding: 20px; 
      font-size: 14px; 
      border-top: 1px solid rgba(75, 139, 110, 0.2);
      color: #4B8B6E;
    }
    .student-info img { 
      width: 40px; 
      height: 40px; 
      border-radius: 50%; 
      margin-bottom: 8px;
      border: 2px solid #6BAF92;
    }
    
    /* Main Content */
    .main-content { 
      margin-left: 240px; 
      padding: 40px; 
      width: calc(100% - 240px); 
    }
    
    .topics-container { 
      max-width: 1400px; 
      margin: 0 auto; 
    }
    
    /* Page Header */
    .page-header { 
      text-align: center; 
      margin-bottom: 50px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 40px;
      border-radius: 25px;
      box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
    }
    .page-title { 
      font-size: 2.5rem; 
      color: #2D5A47; 
      margin-bottom: 15px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .page-title i {
      background: linear-gradient(135deg, #6BAF92, #4B8B6E);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .page-subtitle { 
      color: #3D6B54; 
      font-size: 1.15rem;
      font-weight: 600;
      text-align: center;
    }
    
    /* Performance Summary Cards - NEW DESIGN */
    .performance-summary { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
      gap: 30px; 
      margin-bottom: 60px; 
    }
    .summary-card { 
      background: rgba(255, 255, 255, 0.95); 
      backdrop-filter: blur(10px);
      border-radius: 20px; 
      padding: 35px; 
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.12); 
      text-align: center; 
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
    }
    .summary-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      transition: height 0.3s ease;
    }
    .summary-card.focus::before { 
      background: linear-gradient(90deg, #6BAF92, #4B8B6E); 
    }
    .summary-card.good::before { 
      background: linear-gradient(90deg, #E8C547, #F4D77C); 
    }
    .summary-card.strong::before { 
      background: linear-gradient(90deg, #4B8B6E, #6BAF92); 
    }
    .summary-card:hover { 
      transform: translateY(-10px) scale(1.02); 
      box-shadow: 0 20px 50px rgba(75, 139, 110, 0.25);
    }
    .summary-card:hover::before {
      height: 100%;
      opacity: 0.05;
    }
    .summary-card.focus { border-color: rgba(107, 175, 146, 0.3); }
    .summary-card.good { border-color: rgba(232, 197, 71, 0.3); }
    .summary-card.strong { border-color: rgba(75, 139, 110, 0.3); }
    
    .summary-icon { 
      font-size: 3rem; 
      margin-bottom: 20px;
      display: inline-flex;
      width: 90px;
      height: 90px;
      align-items: center;
      justify-content: center;
      border-radius: 20px;
      position: relative;
      z-index: 1;
    }
    .summary-icon.focus { 
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(75, 139, 110, 0.15));
      color: #4B8B6E; 
    }
    .summary-icon.good { 
      background: linear-gradient(135deg, rgba(244, 215, 124, 0.2), rgba(232, 197, 71, 0.2));
      color: #E8C547; 
    }
    .summary-icon.strong { 
      background: linear-gradient(135deg, rgba(75, 139, 110, 0.15), rgba(107, 175, 146, 0.15));
      color: #4B8B6E; 
    }
    .summary-number { 
      font-size: 3.5rem; 
      font-weight: 800; 
      margin-bottom: 10px;
      line-height: 1;
    }
    .summary-number.focus { 
      background: linear-gradient(135deg, #6BAF92, #4B8B6E);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .summary-number.good { 
      background: linear-gradient(135deg, #F4D77C, #E8C547);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .summary-number.strong { 
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .summary-label { 
      font-size: 1.15rem; 
      color: #2D5A47; 
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    
    /* Section Titles - NEW DESIGN */
    .section-title { 
      font-size: 1.9rem; 
      color: #2D5A47; 
      margin: 60px 0 35px 0; 
      padding: 25px 30px; 
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.12);
      display: flex;
      align-items: center;
      gap: 20px;
      font-weight: 700;
      border-left: 6px solid;
    }
    .section-title.focus-section { 
      border-left-color: #6BAF92; 
    }
    .section-title.good-section { 
      border-left-color: #E8C547; 
    }
    .section-title.strong-section { 
      border-left-color: #4B8B6E; 
    }
    .section-title i {
      font-size: 2rem;
    }
    
    /* Topics Grid - REDESIGNED */
    .topics-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); 
      gap: 30px; 
      margin-bottom: 50px; 
    }
    .topic-card { 
      background: rgba(255, 255, 255, 0.95); 
      backdrop-filter: blur(10px);
      border-radius: 20px; 
      padding: 35px; 
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.12); 
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
      cursor: pointer; 
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
    }
    .topic-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 6px;
      height: 100%;
      transition: width 0.3s ease;
    }
    .topic-card.focus::before { 
      background: linear-gradient(180deg, #6BAF92, #4B8B6E); 
    }
    .topic-card.good::before { 
      background: linear-gradient(180deg, #F4D77C, #E8C547); 
    }
    .topic-card.strong::before { 
      background: linear-gradient(180deg, #4B8B6E, #6BAF92); 
    }
    .topic-card:hover { 
      transform: translateY(-8px) translateX(5px); 
      box-shadow: 0 20px 50px rgba(75, 139, 110, 0.25); 
    }
    .topic-card:hover::before {
      width: 100%;
      opacity: 0.05;
    }
    .topic-card.focus { border-color: rgba(107, 175, 146, 0.2); }
    .topic-card.good { border-color: rgba(232, 197, 71, 0.2); }
    .topic-card.strong { border-color: rgba(75, 139, 110, 0.2); }
    .topic-card.focus:hover { border-color: #6BAF92; }
    .topic-card.good:hover { border-color: #E8C547; }
    .topic-card.strong:hover { border-color: #4B8B6E; }
    
    .topic-header { 
      display: flex; 
      align-items: flex-start; 
      gap: 20px; 
      margin-bottom: 25px; 
    }
    .topic-icon-wrapper {
      background: rgba(255,255,255,0.8);
      padding: 18px;
      border-radius: 16px;
      box-shadow: 0 5px 20px rgba(75, 139, 110, 0.15);
      transition: all 0.3s ease;
    }
    .topic-card:hover .topic-icon-wrapper {
      transform: rotate(5deg) scale(1.05);
    }
    .topic-icon { font-size: 2.2rem; }
    .topic-icon.focus { 
      background: linear-gradient(135deg, #6BAF92, #4B8B6E);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .topic-icon.good { 
      background: linear-gradient(135deg, #F4D77C, #E8C547);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .topic-icon.strong { 
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .topic-info { flex: 1; }
    .topic-title { 
      font-size: 1.35rem; 
      font-weight: 700; 
      color: #2D5A47; 
      margin-bottom: 10px;
      line-height: 1.4;
    }
    .topic-score { 
      font-size: 1.05rem; 
      font-weight: 600;
      margin-bottom: 15px;
      padding: 8px 16px;
      border-radius: 20px;
      display: inline-block;
    }
    .topic-score.focus { 
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(75, 139, 110, 0.15));
      color: #4B8B6E; 
    }
    .topic-score.good { 
      background: linear-gradient(135deg, rgba(244, 215, 124, 0.2), rgba(232, 197, 71, 0.2));
      color: #E8C547; 
    }
    .topic-score.strong { 
      background: linear-gradient(135deg, rgba(75, 139, 110, 0.15), rgba(107, 175, 146, 0.15));
      color: #4B8B6E; 
    }
    
    .topic-activities { 
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.08), rgba(75, 139, 110, 0.08));
      padding: 25px;
      border-radius: 15px;
      margin-top: 25px;
      border: 1px solid rgba(107, 175, 146, 0.2);
    }
    .activity-count { 
      font-size: 1rem; 
      color: #2D5A47; 
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 15px;
    }
    .activity-count i { 
      color: #4B8B6E;
      font-size: 1.2rem;
    }
    
    .cta-button {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      padding: 14px 28px;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1rem;
      margin-top: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      box-shadow: 0 5px 20px rgba(75, 139, 110, 0.25);
      font-family: 'Poppins', sans-serif;
    }
    .cta-button:hover {
      background: linear-gradient(135deg, #6BAF92, #4B8B6E);
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(75, 139, 110, 0.35);
    }
    
    /* No Assessment State */
    .no-assessment { 
      background: linear-gradient(135deg, #6BAF92, #4B8B6E); 
      color: white; 
      padding: 60px; 
      border-radius: 25px; 
      text-align: center; 
      margin: 50px 0; 
      box-shadow: 0 15px 50px rgba(75, 139, 110, 0.3);
    }
    .no-assessment h3 { 
      margin-bottom: 25px; 
      font-size: 2rem;
      font-weight: 700;
    }
    .no-assessment p { 
      margin-bottom: 25px; 
      font-size: 1.15rem; 
      line-height: 1.8;
      opacity: 0.95;
    }
    .btn { 
      padding: 16px 35px; 
      background: rgba(255,255,255,0.2); 
      color: white; 
      border: 2px solid rgba(255,255,255,0.4);
      border-radius: 12px; 
      cursor: pointer; 
      font-weight: 700; 
      text-decoration: none; 
      display: inline-block; 
      transition: all 0.3s ease; 
      font-size: 1.1rem;
      font-family: 'Poppins', sans-serif;
    }
    .btn:hover { 
      background: rgba(255,255,255,0.3); 
      border-color: rgba(255,255,255,0.6);
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .topics-grid {
        grid-template-columns: 1fr;
      }
      .performance-summary {
        grid-template-columns: 1fr;
      }
      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
      }
      .sidebar {
        display: none;
      }
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
        <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="progress_history.php"><i class="fas fa-history"></i> Progress History</a>
        <a href="video_materials.php"><i class="fas fa-book-open"></i> Learning Materials</a>
        <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
        <a href="recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
        <a href="coding_practice.php"><i class="fas fa-code"></i> Coding Practice</a>
        <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
        <a href="settings.php"><i class="fas fa-user"></i> Settings</a>
        <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    <div class="student-info">
      <img src="student.jpg" alt="Student">
      <div><strong>Student</strong></div>
      <div>student@email.com</div>
    </div>
  </div>

  <div class="main-content">
    
    <?php if (!$hasPreAssessment): ?>
      <!-- No Pre-Assessment Taken -->
      <div class="no-assessment">
        <h3><i class="fas fa-info-circle"></i> Complete Your Pre-Assessment First</h3>
        <p>To get personalized enhancement recommendations, you need to take a pre-assessment first.</p>
        <p>The assessment will help us understand your current skill level and provide targeted learning activities.</p>
        <a href="pre_assessment_onboarding.php" class="btn">
          <i class="fas fa-play"></i> Take Pre-Assessment
        </a>
      </div>
    
    <?php else: ?>
      <!-- Performance Summary -->
      <div class="topics-container">
        <div class="page-header">
          <h1 class="page-title">
            <i class="fas fa-dumbbell"></i> Your Enhancement Process
          </h1>
          <p class="page-subtitle">Personalized learning path based on your pre-assessment results</p>
        </div>
        
        <div class="performance-summary">
          <div class="summary-card focus">
            <i class="fas fa-exclamation-triangle summary-icon focus"></i>
            <div class="summary-number focus"><?= $performanceSummary['focus'] ?></div>
            <div class="summary-label">Focus Areas</div>
          </div>
          <div class="summary-card good">
            <i class="fas fa-chart-line summary-icon good"></i>
            <div class="summary-number good"><?= $performanceSummary['good'] ?></div>
            <div class="summary-label">Good Progress</div>
          </div>
          <div class="summary-card strong">
            <i class="fas fa-trophy summary-icon strong"></i>
            <div class="summary-number strong"><?= $performanceSummary['strong'] ?></div>
            <div class="summary-label">Strong Areas</div>
          </div>
        </div>

        <?php
        // Group topics by performance level
        $focusTopics = array_filter($topicPerformance, fn($t) => $t['performance_level'] === 'focus');
        $goodTopics = array_filter($topicPerformance, fn($t) => $t['performance_level'] === 'good');
        $strongTopics = array_filter($topicPerformance, fn($t) => $t['performance_level'] === 'strong');
        
        // Check if there are any topics at all
        $hasAnyTopics = !empty($focusTopics) || !empty($goodTopics) || !empty($strongTopics);
        ?>

        <?php if (!$hasAnyTopics): ?>
          <!-- No Topics Data Available -->
          <div class="no-assessment">
            <h3><i class="fas fa-info-circle"></i> No Performance Data Available</h3>
            <p>We couldn't find any topic performance data from your assessment.</p>
            <p>Please try taking the pre-assessment again or contact your instructor for assistance.</p>
            <a href="pre_assessment_onboarding.php" class="btn">
              <i class="fas fa-redo"></i> Take Pre-Assessment
            </a>
          </div>
        <?php endif; ?>

        <?php if (!empty($focusTopics)): ?>
        <div class="section-title focus-section">
          <i class="fas fa-exclamation-triangle" style="color: #6BAF92;"></i> 
          Priority Topics - Needs Focus
        </div>
        <div class="topics-grid">
          <?php foreach ($focusTopics as $topic): ?>
            <div class="topic-card focus" onclick="goToTopic(<?= $topic['id'] ?>)">
              <div class="topic-header">
                <div class="topic-icon-wrapper">
                  <i class="fas fa-target topic-icon focus"></i>
                </div>
                <div class="topic-info">
                  <div class="topic-title"><?= htmlspecialchars($topic['name']) ?></div>
                  <div class="topic-score focus">
                    <?= $topic['percentage'] ?>% - Needs Improvement
                  </div>
                </div>
              </div>
              <div class="topic-activities">
                <?php 
                $activityCount = isset($activitiesData[$topic['id']]) ? count($activitiesData[$topic['id']]['instructions']) : 0;
                ?>
                <div class="activity-count">
                  <i class="fas fa-code"></i> <?= $activityCount ?> Practice Activities Available
                </div>
                <button class="cta-button" onclick="event.stopPropagation(); goToTopic(<?= $topic['id'] ?>)">
                  Start Learning Journey
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($goodTopics)): ?>
        <div class="section-title good-section">
          <i class="fas fa-arrow-up" style="color: #E8C547;"></i> 
          Good Progress Topics - Keep Improving
        </div>
        <div class="topics-grid">
          <?php foreach ($goodTopics as $topic): ?>
            <div class="topic-card good" onclick="goToTopic(<?= $topic['id'] ?>)">
              <div class="topic-header">
                <div class="topic-icon-wrapper">
                  <i class="fas fa-chart-line topic-icon good"></i>
                </div>
                <div class="topic-info">
                  <div class="topic-title"><?= htmlspecialchars($topic['name']) ?></div>
                  <div class="topic-score good">
                    <?= $topic['percentage'] ?>% - Good Progress
                  </div>
                </div>
              </div>
              <div class="topic-activities">
                <?php 
                $activityCount = isset($activitiesData[$topic['id']]) ? count($activitiesData[$topic['id']]['instructions']) : 0;
                ?>
                <div class="activity-count">
                  <i class="fas fa-tasks"></i> <?= $activityCount ?> Enhancement Activities Available
                </div>
                <button class="cta-button" onclick="event.stopPropagation(); goToTopic(<?= $topic['id'] ?>)">
                  Continue Enhancement
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($strongTopics)): ?>
        <div class="section-title strong-section">
          <i class="fas fa-trophy" style="color: #4B8B6E;"></i> 
          Strong Areas - Advanced Practice
        </div>
        <div class="topics-grid">
          <?php foreach ($strongTopics as $topic): ?>
            <div class="topic-card strong" onclick="goToTopic(<?= $topic['id'] ?>)">
              <div class="topic-header">
                <div class="topic-icon-wrapper">
                  <i class="fas fa-star topic-icon strong"></i>
                </div>
                <div class="topic-info">
                  <div class="topic-title"><?= htmlspecialchars($topic['name']) ?></div>
                  <div class="topic-score strong">
                    <?= $topic['percentage'] ?>% - Excellent!
                  </div>
                </div>
              </div>
              <div class="topic-activities">
                <?php 
                $activityCount = isset($activitiesData[$topic['id']]) ? count($activitiesData[$topic['id']]['instructions']) : 0;
                ?>
                <div class="activity-count">
                  <i class="fas fa-graduation-cap"></i> <?= $activityCount ?> Advanced Challenges Available
                </div>
                <button class="cta-button" onclick="event.stopPropagation(); goToTopic(<?= $topic['id'] ?>)" 
                        style="background: linear-gradient(135dg, #27ae60, #2ecc71);">
                  Try Advanced Challenges
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    <?php endif; ?>
  </div>

  <script>
    function goToTopic(topicId) {
      // Redirect to intro page first, then to activities
      window.location.href = `Activity/intro.php?topic_id=${topicId}`;
    }
  </script>

</body>
</html>
