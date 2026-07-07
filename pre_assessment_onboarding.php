<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Check if user has completed any pre-assessment
$user_id = $_SESSION['user_id'];
$checkQuery = "SELECT completed_preassessment FROM login_credentials WHERE id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If already completed, redirect to dashboard
if ($result['completed_preassessment'] == 1) {
    header("Location: student_dashboard.php");
    exit();
}

// Fetch available subjects
$subjects = [];
$subjectQuery = "SELECT id, code, name FROM subjects ORDER BY code";
$subjectResult = $conn->query($subjectQuery);
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to SkillSync - Pre-Assessment</title>
  <link rel="shortcut icon" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #2c3e50;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(107, 175, 146, 0.1) 0%, transparent 70%);
      animation: rotate 30s linear infinite;
      z-index: 0;
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .welcome-container {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      padding: 40px;
      max-width: 600px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(75, 139, 110, 0.3);
      text-align: center;
      animation: slideUp 0.6s ease-out;
      border: 2px solid rgba(107, 175, 146, 0.3);
      position: relative;
      z-index: 1;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .logo-section {
      margin-bottom: 30px;
    }

    .logo-section img {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      margin-bottom: 15px;
      border: 4px solid #6BAF92;
      box-shadow: 0 8px 25px rgba(107, 175, 146, 0.3);
      transition: all 0.3s ease;
    }

    .logo-section img:hover {
      transform: scale(1.05) rotate(5deg);
    }

    .logo-section h1 {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 0 2px 10px rgba(75, 139, 110, 0.1);
    }

    .logo-section p {
      color: #6BAF92;
      font-size: 1.1rem;
      font-weight: 500;
    }

    .welcome-message {
      margin: 30px 0;
      padding: 25px;
      background: linear-gradient(135deg, rgba(249, 249, 246, 0.9), rgba(255, 255, 255, 0.95));
      backdrop-filter: blur(10px);
      border-radius: 20px;
      border-left: 5px solid #6BAF92;
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.1);
    }

    .welcome-message h2 {
      color: #4B8B6E;
      margin-bottom: 15px;
      font-size: 1.8rem;
      font-weight: 700;
    }

    .welcome-message h2 i {
      color: #E8C547;
      margin-right: 10px;
    }

    .welcome-message p {
      color: #5a6c7d;
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .subject-selection {
      margin: 30px 0;
    }

    .subject-selection h3 {
      color: #4B8B6E;
      margin-bottom: 20px;
      font-size: 1.4rem;
      font-weight: 600;
    }

    .subject-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
      margin: 20px 0;
    }

    .subject-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 2px solid rgba(107, 175, 146, 0.3);
      border-radius: 15px;
      padding: 20px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      box-shadow: 0 4px 15px rgba(75, 139, 110, 0.1);
    }

    .subject-card:hover {
      border-color: #6BAF92;
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(107, 175, 146, 0.25);
      background: rgba(255, 255, 255, 1);
    }

    .subject-card.selected {
      border-color: #6BAF92;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.4);
      transform: translateY(-5px) scale(1.02);
    }

    .subject-card h4 {
      font-size: 1.2rem;
      margin-bottom: 8px;
      font-weight: 700;
    }

    .subject-card p {
      font-size: 0.9rem;
      opacity: 0.9;
    }

    .subject-card.selected p {
      opacity: 1;
    }

    .action-buttons {
      margin-top: 30px;
      display: flex;
      gap: 15px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn {
      padding: 14px 35px;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(75, 139, 110, 0.3);
    }

    .btn-secondary {
      background: linear-gradient(135deg, #95a5a6, #7f8c8d);
      color: white;
    }

    .btn-secondary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
    }

    .btn:disabled {
      background: linear-gradient(135deg, #bdc3c7, #95a5a6);
      cursor: not-allowed;
      transform: none;
      opacity: 0.6;
    }

    .info-box {
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(107, 175, 146, 0.15));
      border: 2px solid #6BAF92;
      border-radius: 15px;
      padding: 15px;
      margin: 20px 0;
      text-align: left;
    }

    .info-box .icon {
      color: #6BAF92;
      margin-right: 10px;
      font-size: 1.1rem;
    }

    .info-box div {
      padding: 8px 0;
      color: #4B8B6E;
      font-weight: 500;
    }

    .assessment-method-selection {
      margin: 30px 0;
      padding: 25px;
      background: linear-gradient(135deg, rgba(249, 249, 246, 0.9), rgba(255, 255, 255, 0.95));
      backdrop-filter: blur(10px);
      border-radius: 20px;
      border: 2px solid rgba(107, 175, 146, 0.3);
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.1);
    }

    .assessment-method-selection h3 {
      color: #4B8B6E;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }

    .method-grid {
      display: flex;
      justify-content: center;
      margin: 20px 0;
    }

    .method-card {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      border: 2px solid #6BAF92;
      border-radius: 20px;
      padding: 35px;
      max-width: 400px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.3);
      color: white;
      transition: all 0.3s ease;
    }

    .method-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(75, 139, 110, 0.4);
    }

    .method-card i {
      font-size: 3.5rem;
      margin-bottom: 20px;
      color: #E8C547;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .method-card h4 {
      font-size: 1.6rem;
      margin-bottom: 15px;
      font-weight: 700;
    }

    .method-card p {
      font-size: 1rem;
      margin-bottom: 20px;
      opacity: 0.95;
      line-height: 1.6;
    }

    .method-card ul {
      list-style: none;
      padding: 0;
      margin: 0;
      text-align: left;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      padding: 15px;
    }

    .method-card ul li {
      padding: 10px 0;
      font-size: 0.95rem;
      position: relative;
      padding-left: 30px;
    }

    .method-card ul li:before {
      content: "✓";
      position: absolute;
      left: 0;
      font-weight: bold;
      color: #E8C547;
      font-size: 1.3rem;
    }

    @media (max-width: 768px) {
      .welcome-container {
        padding: 25px;
      }

      .logo-section h1 {
        font-size: 2rem;
      }

      .subject-grid {
        grid-template-columns: 1fr;
      }

      .method-card {
        padding: 25px;
      }
    }

  </style>
</head>
<body>
  <div class="welcome-container">
    <!-- Logo & Title -->
    <div class="logo-section">
      <img src="LOGO.png" alt="SkillSync Logo">
      <h1>Welcome to SkillSync!</h1>
      <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
    </div>

    <!-- Welcome Message -->
    <div class="welcome-message">
      <h2><i class="fas fa-star"></i> Let's Get Started!</h2>
      <p>To provide you with the best learning experience, we need to understand your current skill level.</p>
      <p>Please choose a subject below to take your first pre-assessment. This will help us:</p>
      <div class="info-box">
        <div><i class="fas fa-chart-line icon"></i>Identify your strengths and areas for improvement</div>
        <div><i class="fas fa-lightbulb icon"></i>Provide personalized learning recommendations</div>
        <div><i class="fas fa-target icon"></i>Create a customized learning path just for you</div>
      </div>
    </div>

    <!-- Subject Selection -->
    <div class="subject-selection">
      <h3>Choose Your First Subject:</h3>
      
      <div class="subject-grid">
        <?php foreach ($subjects as $subject): ?>
          <div class="subject-card" onclick="selectSubject('<?= $subject['code'] ?>', this)">
            <h4><?= htmlspecialchars($subject['code']) ?></h4>
            <p><?= htmlspecialchars($subject['name']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Assessment Method Selection -->
      <div class="assessment-method-selection" id="assessmentMethodSection" style="display: none;">
        <h3>Assessment Method:</h3>
        <div class="method-grid">
          <div class="method-card">
            <i class="fas fa-clipboard-list"></i>
            <h4>Traditional Quiz</h4>
            <p>Take our comprehensive pre-assessment quiz to evaluate your skills</p>
            <ul>
              <li>80 carefully designed questions</li>
              <li>Covers all essential topics</li>
              <li>Immediate results</li>
              <li>Personalized learning path</li>
            </ul>
          </div>
        </div>
      </div>



      <div class="action-buttons">
        <button id="startBtn" class="btn btn-primary" disabled onclick="startPreAssessment()">
          <i class="fas fa-clipboard-list"></i> Start Quiz Assessment
        </button>
        <a href="Homepage.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to Homepage
        </a>
      </div>
    </div>
  </div>

  <script>
    let selectedSubject = null;

    function selectSubject(subjectCode, cardElement) {
      // Remove previous selection
      document.querySelectorAll('.subject-card').forEach(card => {
        card.classList.remove('selected');
      });
      
      // Add selection to clicked card
      cardElement.classList.add('selected');
      selectedSubject = subjectCode;
      
      // Show assessment method section
      document.getElementById('assessmentMethodSection').style.display = 'block';
      
      // Enable start button
      document.getElementById('startBtn').disabled = false;
    }

    function startPreAssessment() {
      if (selectedSubject) {
        window.location.href = `pre_test.php?subject=${selectedSubject}&onboarding=1`;
      }
    }
  </script>
</body>
</html>