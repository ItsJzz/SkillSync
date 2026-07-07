<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check if student has assessment data in students table
$studentDataStmt = $conn->prepare("SELECT assessment_data FROM students WHERE user_id = ?");
$studentDataStmt->bind_param("i", $student_id);
$studentDataStmt->execute();
$studentDataResult = $studentDataStmt->get_result();
$studentRow = $studentDataResult->fetch_assoc();
$assessmentData = $studentRow ? json_decode($studentRow['assessment_data'], true) : null;
$studentDataStmt->close();

$topicScores = [];
$totalCorrect = 0;
$totalAvailable = 0;

// If assessment data exists in students table, use that
if ($assessmentData && isset($assessmentData['topic_scores'])) {
    // Process data from students.assessment_data JSON
    foreach ($assessmentData['topic_scores'] as $topic_id => $topicData) {
        $topic_name = $topicData['name'] ?? "Topic $topic_id";
        $percentage = $topicData['percentage'] ?? 0;
        
        // Calculate total questions and score
        $quiz_total = $topicData['quiz_total'] ?? 8;
        $simulation_total = $topicData['simulation_total'] ?? 8;
        $total = $quiz_total + $simulation_total;
        
        $quiz_correct = $topicData['quiz_correct'] ?? 0;
        $simulation_correct = $topicData['simulation_correct'] ?? 0;
        $score = $quiz_correct + $simulation_correct;
        
        // Fetch ALL learning materials for this topic
        $materialsRes = $conn->query("
            SELECT id, type, title, url 
            FROM learning_materials 
            WHERE topic_id = $topic_id
            ORDER BY type, title
        ");
        $materials = ['video' => [], 'pdf' => [], 'simulation' => []];
        if ($materialsRes && $materialsRes->num_rows > 0) {
            while($m = $materialsRes->fetch_assoc()) {
                $materials[$m['type']][] = $m;
            }
        }

        // Check if activities exist for this topic
        $activitiesExist = false;
        $activitiesPath = 'Activity/activities.json';
        if (file_exists($activitiesPath)) {
            $activitiesData = json_decode(file_get_contents($activitiesPath), true);
            $activitiesExist = isset($activitiesData[$topic_id]);
        }

        $topicScores[] = [
            'topic_id'   => $topic_id,
            'topic_name' => $topic_name,
            'score'      => $score,
            'total'      => $total,
            'percentage' => round($percentage, 2),
            'materials'  => $materials,
            'activities_exist' => $activitiesExist
        ];

        $totalCorrect += $score;
        $totalAvailable += $total;
    }
} else {
    // Fallback to old method using student_tests table
    // Get only topics from subjects the user is enrolled in or has taken assessments for
    $topicsRes = $conn->query("
        SELECT DISTINCT t.id, t.name, s.name as subject_name
        FROM topics t
        JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN student_tests st ON t.id = st.topic_id AND st.student_id = $student_id
        WHERE st.topic_id IS NOT NULL
        ORDER BY s.name, t.name
    ");

    $topicCount = $topicsRes->num_rows;

    // Fixed 50 questions for comprehensive assessment
    $totalQuestions = 50;
    $questionsPerTopic = ($topicCount > 0) ? floor($totalQuestions / $topicCount) : 0;
    $remainder = ($topicCount > 0) ? $totalQuestions % $topicCount : 0;

    $topicIndex = 0;

    while ($topic = $topicsRes->fetch_assoc()) {
        $topic_id = $topic['id'];
        $topic_name = $topic['name'];
        $subject_name = $topic['subject_name'];
        
        // Add 1 extra question to some topics until remainder is used up
        $extra = ($topicIndex < $remainder) ? 1 : 0;
        $total = $questionsPerTopic + $extra;
        
        // Get latest pre-test score for this topic
        $score = 0;
        $preRes = $conn->query("
            SELECT score 
            FROM student_tests 
            WHERE student_id=$student_id AND topic_id=$topic_id AND test_type='pre'
            ORDER BY attempt_date DESC LIMIT 1
        ");
        if ($preRow = $preRes->fetch_assoc()) {
            $score = $preRow['score'];
        }

        $percentage = ($total > 0) ? ($score / $total) * 100 : 0;

        // Fetch ALL learning materials for this topic
        $materialsRes = $conn->query("
            SELECT id, type, title, url 
            FROM learning_materials 
            WHERE topic_id = $topic_id
            ORDER BY type, title
        ");
        $materials = ['video' => [], 'pdf' => [], 'simulation' => []];
        if ($materialsRes && $materialsRes->num_rows > 0) {
            while($m = $materialsRes->fetch_assoc()) {
                $materials[$m['type']][] = $m;
            }
        }

        // Check if activities exist for this topic
        $activitiesExist = false;
        $activitiesPath = 'Activity/activities.json';
        if (file_exists($activitiesPath)) {
            $activitiesData = json_decode(file_get_contents($activitiesPath), true);
            $activitiesExist = isset($activitiesData[$topic_id]);
        }

        $topicScores[] = [
            'topic_id'   => $topic_id,
            'topic_name' => $topic_name,
            'score'      => $score,
            'total'      => $total,
            'percentage' => round($percentage, 2),
            'materials'  => $materials,
            'activities_exist' => $activitiesExist
        ];

        $totalCorrect += $score;
        $totalAvailable += $total;
        $topicIndex++;
    }
}

$totalPercentage = ($totalAvailable > 0) ? round(($totalCorrect / $totalAvailable) * 100, 2) : 0;

// Count categories for summary
$weakCount = count(array_filter($topicScores, fn($t) => $t['percentage'] < 60));
$strongCount = count(array_filter($topicScores, fn($t) => $t['percentage'] >= 80));
$averageCount = count(array_filter($topicScores, fn($t) => $t['percentage'] >= 60 && $t['percentage'] < 80));

// Debug: Check if we have topics
// echo "<!-- DEBUG: Total topics loaded: " . count($topicScores) . " -->";
// echo "<!-- DEBUG: Total Correct: $totalCorrect, Total Available: $totalAvailable -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topic-Specific Learning Map - SkillSync</title>
<link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
  font-family: 'Poppins', sans-serif; 
  background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
  display: flex; 
  min-height: 100vh;
}

/* Sidebar Styles */
.sidebar { 
  width: 240px; 
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-right: 2px solid rgba(107, 175, 146, 0.2);
  height: 100vh; 
  padding: 20px 0; 
  position: fixed; 
  display: flex; 
  flex-direction: column; 
  justify-content: space-between; 
  box-shadow: 5px 0 20px rgba(75, 139, 110, 0.1);
  z-index: 1000; 
  overflow-y: auto;
}
.sidebar-content a { 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  color: #4B8B6E; 
  padding: 14px 20px; 
  text-decoration: none; 
  font-weight: 600; 
  transition: all 0.3s; 
  font-size: 0.95rem;
}
.sidebar-content a:hover, .sidebar-content a.active { 
  background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
  color: white; 
  border-radius: 0 25px 25px 0; 
  margin-right: 10px; 
  box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);
}
.sidebar .logo { 
  text-align: center; 
  margin-bottom: 25px; 
}
.sidebar .logo img { 
  width: 60px; 
  height: 60px; 
  border-radius: 50%; 
  box-shadow: 0 5px 15px rgba(75, 139, 110, 0.2);
}
.sidebar .logo h2 { 
  font-size: 1.3rem; 
  color: #4B8B6E; 
  margin-top: 12px; 
  font-weight: 700;
}
.student-info { 
  padding: 20px; 
  text-align: center; 
  border-top: 2px solid rgba(107, 175, 146, 0.2);
  color: #6BAF92;
  font-weight: 600;
}
.student-info img { 
  width: 50px; 
  height: 50px; 
  border-radius: 50%; 
  margin-bottom: 8px; 
  border: 3px solid #6BAF92;
  box-shadow: 0 3px 10px rgba(75, 139, 110, 0.2);
}
.student-info strong { 
  display: block; 
  margin-bottom: 5px; 
  font-size: 0.95rem;
}
.student-info div { 
  font-size: 0.85rem; 
}

/* Main Content */
.main-content { 
  margin-left: 240px; 
  width: calc(100% - 240px); 
  padding: 40px; 
  min-height: 100vh; 
}

/* Page Header */
.page-header { 
  margin-bottom: 40px; 
  animation: fadeInDown 0.6s ease;
}
.page-title { 
  font-size: 2.5rem; 
  color: #4B8B6E; 
  font-weight: 800; 
  display: flex; 
  align-items: center; 
  gap: 15px; 
  margin-bottom: 10px; 
}
.page-title i { 
  font-size: 2.5rem; 
  background: linear-gradient(135deg, #E8C547, #F4D77C);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.page-subtitle { 
  color: #6BAF92; 
  font-size: 1.1rem; 
  font-weight: 500;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Topic Cards Grid */
.topics-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); 
  gap: 30px; 
  margin-bottom: 30px; 
}

/* Individual Topic Card */
.topic-card { 
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 25px; 
  padding: 30px; 
  box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15); 
  transition: all 0.3s ease; 
  border: 2px solid rgba(232, 197, 71, 0.3);
  animation: fadeInUp 0.6s ease;
}
.topic-card:hover { 
  transform: translateY(-8px); 
  box-shadow: 0 20px 60px rgba(75, 139, 110, 0.25); 
  border-color: #E8C547;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Topic Header */
.topic-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 25px; 
  padding-bottom: 15px;
  border-bottom: 2px solid rgba(107, 175, 146, 0.2);
}
.topic-name { 
  font-size: 1.4rem; 
  font-weight: 700; 
  color: #4B8B6E; 
  flex: 1;
}
.topic-percentage { 
  font-size: 2rem; 
  font-weight: 800; 
  background: linear-gradient(135deg, #E8C547, #F4D77C);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 2px 10px rgba(232, 197, 71, 0.2);
}

/* Progress Bars Section */
.progress-section { 
  margin-bottom: 25px; 
}
.progress-item { 
  margin-bottom: 18px; 
}
.progress-label { 
  display: flex; 
  justify-content: space-between; 
  margin-bottom: 8px; 
  font-size: 0.95rem; 
  font-weight: 600; 
  color: #4B8B6E; 
}
.progress-label i {
  margin-right: 6px;
  color: #6BAF92;
}
.progress-bar-container { 
  width: 100%; 
  height: 12px; 
  background: rgba(107, 175, 146, 0.15); 
  border-radius: 20px; 
  overflow: hidden; 
  box-shadow: inset 0 2px 5px rgba(75, 139, 110, 0.1); 
}
.progress-bar-fill { 
  height: 100%; 
  border-radius: 20px; 
  transition: width 0.8s ease; 
  position: relative;
}
.progress-bar-fill::after {
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
.progress-quiz { 
  background: linear-gradient(90deg, #4B8B6E, #6BAF92); 
}
.progress-simulation { 
  background: linear-gradient(90deg, #6BAF92, #4B8B6E); 
}
.progress-handson { 
  background: linear-gradient(90deg, #E8C547, #F4D77C); 
}

/* Learning Path */
.learning-path { 
  background: linear-gradient(135deg, rgba(232, 197, 71, 0.1), rgba(244, 215, 124, 0.1)); 
  border-radius: 20px; 
  padding: 20px; 
  margin-top: 20px; 
  border: 2px solid rgba(232, 197, 71, 0.2);
}
.learning-path-title { 
  font-size: 1.1rem; 
  font-weight: 700; 
  color: #4B8B6E; 
  margin-bottom: 15px; 
  display: flex; 
  align-items: center; 
  gap: 10px; 
}
.learning-path-title i { 
  color: #E8C547; 
  font-size: 1.3rem;
}
.learning-steps { 
  display: flex; 
  flex-direction: column; 
  gap: 10px; 
}
.learning-step { 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  font-size: 0.9rem; 
  color: #4B8B6E; 
  padding: 12px 15px; 
  background: rgba(255, 255, 255, 0.8); 
  border-radius: 15px; 
  transition: all 0.3s ease; 
  cursor: pointer; 
  text-decoration: none; 
  border: 2px solid transparent;
}
.learning-step:hover { 
  background: rgba(255, 255, 255, 1); 
  transform: translateX(8px); 
  color: #4B8B6E; 
  border-color: #E8C547;
  box-shadow: 0 5px 20px rgba(232, 197, 71, 0.2);
}
.learning-step .step-number { 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  width: 28px; 
  height: 28px; 
  background: linear-gradient(135deg, #E8C547, #F4D77C); 
  color: white; 
  border-radius: 50%; 
  font-weight: 700; 
  font-size: 0.85rem; 
  flex-shrink: 0; 
  box-shadow: 0 3px 10px rgba(232, 197, 71, 0.3);
}
.learning-step .step-text { 
  flex: 1; 
  font-weight: 500; 
}

/* Empty State */
.empty-state {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 25px;
  padding: 60px 40px;
  text-align: center;
  margin: 40px 0;
  border: 2px solid rgba(232, 197, 71, 0.3);
  box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
}
.empty-state i {
  font-size: 5rem;
  background: linear-gradient(135deg, #E8C547, #F4D77C);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 25px;
  display: inline-block;
}
.empty-state h3 {
  color: #4B8B6E;
  font-size: 1.8rem;
  margin-bottom: 15px;
  font-weight: 700;
}
.empty-state p {
  color: #6BAF92;
  font-size: 1.1rem;
  margin-bottom: 30px;
  line-height: 1.6;
}

/* Action Buttons */
.btn { 
  padding: 16px 40px; 
  margin: 0 10px; 
  border: none; 
  border-radius: 30px; 
  font-size: 1.1rem; 
  font-weight: 700; 
  cursor: pointer; 
  transition: all 0.3s ease; 
  text-decoration: none; 
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: 'Poppins', sans-serif;
  box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
}
.btn i {
  font-size: 1.2rem;
}
.btn-primary { 
  background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
  color: white; 
}
.btn-primary:hover { 
  transform: translateY(-3px); 
  box-shadow: 0 10px 30px rgba(75, 139, 110, 0.4); 
  color: white; 
}
.btn-success { 
  background: linear-gradient(135deg, #E8C547, #F4D77C); 
  color: #4B8B6E; 
}
.btn-success:hover { 
  transform: translateY(-3px); 
  box-shadow: 0 10px 30px rgba(232, 197, 71, 0.4); 
  color: #4B8B6E; 
}

/* Responsive Design */
@media (max-width: 1024px) {
  .topics-grid { 
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
  }
}

@media (max-width: 768px) {
  .sidebar { 
    transform: translateX(-100%); 
  }
  .main-content { 
    margin-left: 0; 
    width: 100%; 
    padding: 20px;
  }
  .topics-grid { 
    grid-template-columns: 1fr; 
  }
  .page-title {
    font-size: 2rem;
  }
}
</style>
</head>
<body>

<!-- Sidebar Navigation -->
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
      <a href="recommendations.php" class="active"><i class="fas fa-lightbulb"></i> Recommendations</a>
      <a href="coding_practice.php"><i class="fas fa-code"></i> Coding Practice</a>
      <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
      <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
  <div class="student-info">
    <img src="student.jpg" alt="Student">
    <div><strong><?= htmlspecialchars($_SESSION['username'] ?? 'Student') ?></strong></div>
    <div><?= htmlspecialchars($_SESSION['email'] ?? 'student@email.com') ?></div>
  </div>
</div>

<!-- Main Content Area -->
<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-backpack"></i>
      Topic-Specific Learning Map
    </h1>
    <p class="page-subtitle">Track your progress across different learning activities and discover personalized learning paths</p>
  </div>

  <!-- Topics Grid -->
  <?php if (empty($topicScores)): ?>
    <!-- No Assessment Data -->
    <div class="empty-state">
      <i class="fas fa-clipboard-list"></i>
      <h3>Complete Your Pre-Assessment First</h3>
      <p>To see your personalized learning map and recommendations, please complete the pre-assessment.</p>
      <a href="pre_assessment_onboarding.php" class="btn btn-primary">
        <i class="fas fa-play-circle"></i> Take Pre-Assessment
      </a>
    </div>
  <?php else: ?>
  <div class="topics-grid">
    <?php foreach ($topicScores as $topic): ?>
      <?php 
      // Calculate sub-scores (simulated for now - you can enhance this with real data)
      $quizScore = round($topic['percentage'] * 0.9); // Slightly lower for quiz
      $simulationScore = round($topic['percentage'] * 1.05); // Slightly higher for simulation
      if ($simulationScore > 100) $simulationScore = 100;
      $handsonScore = round($topic['percentage'] * 0.95); // Slightly lower for hands-on
      ?>
      
      <div class="topic-card">
        <!-- Topic Header -->
        <div class="topic-header">
          <div class="topic-name"><?= htmlspecialchars($topic['topic_name']) ?></div>
          <div class="topic-percentage"><?= $topic['percentage'] ?>%</div>
        </div>

        <!-- Progress Bars -->
        <div class="progress-section">
          <!-- Quiz Progress -->
          <div class="progress-item">
            <div class="progress-label">
              <span><i class="fas fa-question-circle"></i> Quiz</span>
              <span><?= $quizScore ?>%</span>
            </div>
            <div class="progress-bar-container">
              <div class="progress-bar-fill progress-quiz" style="width: <?= $quizScore ?>%;"></div>
            </div>
          </div>

          <!-- Simulation Progress -->
          <div class="progress-item">
            <div class="progress-label">
              <span><i class="fas fa-laptop-code"></i> Simulation</span>
              <span><?= $simulationScore ?>%</span>
            </div>
            <div class="progress-bar-container">
              <div class="progress-bar-fill progress-simulation" style="width: <?= $simulationScore ?>%;"></div>
            </div>
          </div>

          <!-- Hands-on Progress -->
          <div class="progress-item">
            <div class="progress-label">
              <span><i class="fas fa-code"></i> Hands-on</span>
              <span><?= $handsonScore ?>%</span>
            </div>
            <div class="progress-bar-container">
              <div class="progress-bar-fill progress-handson" style="width: <?= $handsonScore ?>%;"></div>
            </div>
          </div>
        </div>

        <!-- Recommended Learning Path -->
        <div class="learning-path">
          <div class="learning-path-title">
            <i class="fas fa-route"></i>
            Recommended Learning Path
          </div>
          <div class="learning-steps">
            <?php if ($topic['percentage'] < 60): ?>
              <!-- Weak Area Recommendations -->
              <a href="Enhancement.php" class="learning-step">
                <span class="step-number">1</span>
                <span class="step-text">Start with Enhancement Process to build foundation</span>
              </a>
              <?php if (!empty($topic['materials']['video'])): ?>
                <a href="view_material.php?id=<?= $topic['materials']['video'][0]['id'] ?>" class="learning-step">
                  <span class="step-number">2</span>
                  <span class="step-text">Watch video tutorials for <?= htmlspecialchars($topic['topic_name']) ?></span>
                </a>
              <?php endif; ?>
              <?php if ($topic['activities_exist']): ?>
                <a href="Activity/activity_list.php?topic_id=<?= $topic['topic_id'] ?>" class="learning-step">
                  <span class="step-number">3</span>
                  <span class="step-text">Practice coding activities</span>
                </a>
              <?php endif; ?>
              <a href="video_materials.php?topic_id=<?= $topic['topic_id'] ?>" class="learning-step">
                <span class="step-number">4</span>
                <span class="step-text">Review learning materials and PDFs</span>
              </a>
            <?php elseif ($topic['percentage'] >= 80): ?>
              <!-- Strong Area Recommendations -->
              <a href="Activity/activity_list.php?topic_id=<?= $topic['topic_id'] ?>" class="learning-step">
                <span class="step-number">1</span>
                <span class="step-text">Excellent! Try advanced coding challenges</span>
              </a>
              <a href="student_dashboard.php" class="learning-step">
                <span class="step-number">2</span>
                <span class="step-text">Review your dashboard for next topic</span>
              </a>
              <a href="feedback.php" class="learning-step">
                <span class="step-number">3</span>
                <span class="step-text">Share your experience via feedback</span>
              </a>
            <?php else: ?>
              <!-- Average Area Recommendations -->
              <a href="Enhancement.php" class="learning-step">
                <span class="step-number">1</span>
                <span class="step-text">Continue enhancement for mastery</span>
              </a>
              <?php if ($topic['activities_exist']): ?>
                <a href="Activity/activity_list.php?topic_id=<?= $topic['topic_id'] ?>" class="learning-step">
                  <span class="step-number">2</span>
                  <span class="step-text">Complete more hands-on activities</span>
                </a>
              <?php endif; ?>
              <a href="video_materials.php?topic_id=<?= $topic['topic_id'] ?>" class="learning-step">
                <span class="step-number">3</span>
                <span class="step-text">Review advanced learning materials</span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Action Buttons -->
  
</div>

</body>
</html>
