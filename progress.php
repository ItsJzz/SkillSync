<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("DB error");

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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>SkillSync - My Progress</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* --- keep all your styles as before --- */
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Segoe UI',sans-serif;display:flex;background-color:#f7f9fb;color:#2c3e50;}
    .sidebar{width:240px;background-color:#ffffff;border-right:1px solid #e0e0e0;height:100vh;padding:20px 0;position:fixed;display:flex;flex-direction:column;justify-content:space-between;}
    .sidebar-content a{display:flex;align-items:center;gap:10px;color:#2c3e50;padding:12px 20px;text-decoration:none;font-weight:500;transition:all .3s;}
    .sidebar-content a:hover,.sidebar-content a.active{background:linear-gradient(135deg,#27ae60,#2ecc71);color:white;border-radius:0 25px 25px 0;margin-right:10px;}
    .sidebar .logo{text-align:center;margin-bottom:20px;}
    .sidebar .logo img{width:50px;height:50px;border-radius:50%;}
    .sidebar .logo h2{font-size:18px;color:#27ae60;margin-top:10px;}
    .student-info{text-align:center;padding:20px;font-size:14px;}
    .student-info img{width:40px;height:40px;border-radius:50%;margin-bottom:5px;}
    .main-content{margin-left:240px;padding:30px;width:calc(100% - 240px);max-width:1200px;overflow-y:auto;height:100vh;}
    .section{background-color:#ffffff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-bottom:30px;}
    .section h3{margin-bottom:20px;color:#27ae60;}
    .charts-row{display:flex;gap:20px;flex-wrap:wrap;}
    .chart-container{flex:1 1 48%;height:300px;}
    canvas{width:100%!important;height:100%!important;border-radius:12px;padding:20px;box-shadow:0 8px 16px rgba(0,0,0,0.05);background-color:#fff;}
    .recent-activity ul{list-style:none;padding-left:0;}
    .recent-activity li{margin-bottom:12px;padding-left:25px;position:relative;color:#444;}
    .recent-activity li::before{content:'\f111';font-family:"Font Awesome 5 Free";font-weight:900;position:absolute;left:0;top:3px;font-size:10px;color:#27ae60;}
    .badges{display:flex;gap:15px;flex-wrap:wrap;}
    .badge{background-color:#27ae60;color:white;padding:8px 14px;border-radius:20px;font-weight:600;font-size:13px;box-shadow:0 2px 6px rgba(39,174,96,0.5);display:flex;align-items:center;gap:6px;}
    .badge i{font-size:16px;}
    .progress-bar-container{margin-top:10px;background-color:#e0e0e0;border-radius:20px;overflow:hidden;height:22px;width:100%;}
    .progress-bar{height:100%;width:65%;background-color:#27ae60;border-radius:20px 0 0 20px;transition:width .5s ease;}
    .tip-box{background-color:#dff0d8;border:1px solid #c3e6cb;border-radius:10px;padding:15px 20px;color:#3c763d;font-size:15px;font-style:italic;max-width:450px;margin-top:10px;}
  </style>
</head>
<body>
  <div class="sidebar">
    <div>
      <div class="logo">
        <img src="LOGO.png" alt="Logo" />
        <h2>SkillSync</h2>
      </div>
      <div class="sidebar-content">
        <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="video_materials.php"><i class="fas fa-book-open"></i> Learning Materials</a>
        <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
        <a href="recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
        <a href="coding_practice.php"><i class="fas fa-code"></i> Coding Practice</a>
        <a href="progress.php" class="active"><i class="fas fa-chart-line"></i> Progress</a>
        <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    <div class="student-info">
      <img src="student.jpg" alt="Student" />
      <div><strong>Student</strong></div>
      <div>student@email.com</div>
    </div>
  </div>

  <div class="main-content">
    <div class="section">
      <h3>Weekly Progress & Score Overview</h3>
      <div class="charts-row">
        <div class="chart-container"><canvas id="progressChart"></canvas></div>
        <div class="chart-container"><canvas id="progressOverTimeChart"></canvas></div>
      </div>
      <p style="margin-top: 10px; font-size: 15px; color: #555;">
        The left chart shows your weekly activities, quizzes, and videos completed. The right chart compares your Pre-Test and Post-Test scores over time to track improvement.
      </p>
    </div>

    <div class="section recent-activity">
      <h3>Recent Activity</h3>
      <ul>
        <li>Completed Quiz 2 on Functions</li>
        <li>Watched Looping video</li>
        <li>Spent 30 mins on Arrays exercise</li>
        <li>Completed Variables practice task</li>
        <li>Took Quiz 1 on Conditions</li>
      </ul>
    </div>

    <div class="section badges">
      <h3>Achievements</h3>
      <div class="badge"><i class="fas fa-trophy"></i> Completed 5 Quizzes</div>
      <div class="badge"><i class="fas fa-star"></i> 75% Average Score</div>
      <div class="badge"><i class="fas fa-bolt"></i> 3-Day Learning Streak</div>
    </div>

    <div class="section">
      <h3>Course Completion</h3>
      <div class="progress-bar-container" aria-label="Course Completion Progress">
        <div class="progress-bar"></div>
      </div>
      <div style="margin-top: 8px; font-weight: 600; color: #27ae60;">65% Completed</div>
    </div>

    <div class="section tip-box">
      <h3>Tip of the Day</h3>
      <p>Great job! You’re close to mastering "Looping." Try the advanced exercises to boost your skills!</p>
    </div>
  </div>

  <script>
    // Weekly Progress Chart
    new Chart(document.getElementById('progressChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: <?= json_encode($weekLabels) ?>,
        datasets: [
          {
            label: 'Activities Completed',
            data: <?= json_encode($actData) ?>,
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39,174,96,0.1)',
            borderWidth: 2, tension: 0.3, fill: true, pointBackgroundColor: '#27ae60'
          },
          {
            label: 'Quizzes Taken',
            data: <?= json_encode($quizData) ?>,
            borderColor: '#2980b9',
            backgroundColor: 'rgba(52,152,219,0.1)',
            borderWidth: 2, tension: 0.3, fill: true, pointBackgroundColor: '#2980b9'
          },
          {
            label: 'Videos Watched',
            data: <?= json_encode($videoData) ?>,
            borderColor: '#f39c12',
            backgroundColor: 'rgba(243,156,18,0.1)',
            borderWidth: 2, tension: 0.3, fill: true, pointBackgroundColor: '#f39c12'
          }
        ]
      },
      options: {responsive:true,maintainAspectRatio:false,
        scales:{y:{beginAtZero:true,title:{display:true,text:'Count'}},x:{title:{display:true,text:'Week'}}},
        plugins:{legend:{labels:{color:'#004e64',font:{size:13}}},tooltip:{mode:'index',intersect:false}}
      }
    });

    // Pre/Post Test Scores Line Chart
    new Chart(document.getElementById('progressOverTimeChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: <?= json_encode($scoreLabels) ?>,
        datasets: [
          {
            label: 'Pre-Test Scores',
            data: <?= json_encode($preData) ?>,
            borderColor: '#e74c3c',
            backgroundColor: 'rgba(231,76,60,0.1)',
            fill:true, tension:0.3, pointRadius:5
          },
          {
            label: 'Post-Test Scores',
            data: <?= json_encode($postData) ?>,
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39,174,96,0.1)',
            fill:true, tension:0.3, pointRadius:5
          }
        ]
      },
      options:{responsive:true,maintainAspectRatio:false,
        scales:{y:{beginAtZero:true,max:100,title:{display:true,text:'Score (%)'}},x:{title:{display:true,text:'Week'}}},
        plugins:{legend:{labels:{font:{size:14},color:'#004e64'}},tooltip:{mode:'index',intersect:false}}
      }
    });
  </script>
</body>
</html>
