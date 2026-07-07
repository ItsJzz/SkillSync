<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['username'];
$student_email = $_SESSION['email'] ?? '';

// Check if table exists first
$tableExists = false;
$previousFeedback = [];

$tableCheck = $conn->query("SHOW TABLES LIKE 'student_feedback'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $tableExists = true;
    // Get student's previous feedback
    $feedbackQuery = "SELECT * FROM student_feedback WHERE student_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($feedbackQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $previousFeedback = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>SkillSync - Feedback</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      display: flex;
      background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
      color: #2c3e50;
      min-height: 100vh;
    }

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

    .sidebar-content a:hover,
    .sidebar-content a.active {
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
      text-align: center;
      padding: 20px;
      font-size: 0.9rem;
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

    .main-content {
      margin-left: 240px;
      padding: 30px;
      width: calc(100% - 240px);
      min-height: 100vh;
    }

    .page-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 25px;
      padding: 40px;
      margin-bottom: 30px;
      box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
      border: 2px solid rgba(107, 175, 146, 0.2);
      text-align: center;
    }

    .page-header h1 {
      font-size: 2.8rem;
      color: #2D5A47;
      font-weight: 800;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-header p {
      color: #3D6B54;
      font-size: 1.15rem;
      font-weight: 500;
    }

    .feedback-section {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      max-width: 900px;
      margin: 0 auto 30px;
      padding: 40px;
      border-radius: 25px;
      box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .feedback-section h2 {
      color: #2D5A47;
      margin-bottom: 25px;
      font-size: 1.8rem;
      font-weight: 700;
    }

    .feedback-type-section {
      margin: 30px 0;
    }

    .feedback-type-section h3 {
      color: #2D5A47;
      margin-bottom: 20px;
      font-size: 1.3rem;
      font-weight: 700;
    }

    .feedback-types {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .feedback-type-card {
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.08), rgba(75, 139, 110, 0.08));
      border: 3px solid rgba(107, 175, 146, 0.25);
      border-radius: 20px;
      padding: 30px 25px;
      text-align: center;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      position: relative;
      overflow: hidden;
    }

    .feedback-type-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #4B8B6E, #6BAF92);
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }

    .feedback-type-card:hover::before {
      transform: scaleX(1);
    }

    .feedback-type-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 15px 40px rgba(75, 139, 110, 0.25);
      border-color: #4B8B6E;
    }

    .feedback-type-card.selected {
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.2), rgba(75, 139, 110, 0.2));
      border-color: #4B8B6E;
      box-shadow: 0 12px 35px rgba(75, 139, 110, 0.35);
      transform: scale(1.05);
    }

    .feedback-type-card.selected::before {
      transform: scaleX(1);
      height: 100%;
      opacity: 0.1;
    }

    .feedback-type-card .icon {
      font-size: 3.5rem;
      margin-bottom: 15px;
      transition: transform 0.3s ease;
    }

    .feedback-type-card:hover .icon {
      transform: scale(1.1) rotate(5deg);
    }

    .feedback-type-card.concern .icon { color: #e74c3c; }
    .feedback-type-card.satisfaction .icon { color: #4B8B6E; }
    .feedback-type-card.feature .icon { color: #3498db; }
    .feedback-type-card.bug .icon { color: #f39c12; }
    .feedback-type-card.ui .icon { color: #9b59b6; }
    .feedback-type-card.general .icon { color: #95a5a6; }

    .feedback-type-card .label {
      color: #2D5A47;
      font-weight: 700;
      font-size: 1.05rem;
    }

    label {
      display: block;
      margin-top: 25px;
      font-weight: 600;
      color: #2D5A47;
      font-size: 1.05rem;
      margin-bottom: 10px;
    }

    input[type="text"],
    textarea {
      width: 100%;
      padding: 15px;
      border-radius: 15px;
      border: 2px solid rgba(107, 175, 146, 0.3);
      margin-top: 5px;
      font-size: 1rem;
      resize: none;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
      background: rgba(255, 255, 255, 0.8);
    }

    input[type="text"]:focus,
    textarea:focus {
      outline: none;
      border-color: #4B8B6E;
      box-shadow: 0 5px 20px rgba(75, 139, 110, 0.2);
      background: white;
      transform: translateY(-2px);
    }

    button,
    .submit-btn {
      margin-top: 30px;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      border: none;
      padding: 16px 40px;
      font-size: 1.1rem;
      font-weight: 700;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
      box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }

    button:hover,
    .submit-btn:hover {
      background: linear-gradient(135deg, #6BAF92, #4B8B6E);
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(75, 139, 110, 0.4);
    }

    .success {
      color: #2D5A47;
      font-weight: 700;
      margin-top: 20px;
      padding: 20px 25px;
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.2), rgba(75, 139, 110, 0.2));
      border-radius: 15px;
      border-left: 6px solid #4B8B6E;
      border: 2px solid rgba(75, 139, 110, 0.4);
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.05rem;
      animation: slideInUp 0.5s ease;
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .error {
      color: #e74c3c;
      font-weight: 700;
      margin-top: 20px;
      padding: 20px;
      background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(231, 76, 60, 0.15));
      border-radius: 15px;
      border-left: 6px solid #e74c3c;
      border: 2px solid rgba(231, 76, 60, 0.3);
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.05rem;
    }

    .rating-container {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }

    .star {
      font-size: 2.8rem;
      color: rgba(232, 197, 71, 0.3);
      cursor: pointer;
      transition: all 0.3s;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    .star:hover,
    .star.active {
      color: #E8C547;
      transform: scale(1.15) rotate(10deg);
      filter: drop-shadow(0 4px 8px rgba(232, 197, 71, 0.4));
    }

    .hidden {
      display: none;
    }

    /* Previous Feedback Section */
    .previous-feedback {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      max-width: 900px;
      margin: 30px auto;
      padding: 40px;
      border-radius: 25px;
      box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .previous-feedback h3 {
      color: #2D5A47;
      margin-bottom: 25px;
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .feedback-item {
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.08), rgba(75, 139, 110, 0.08));
      padding: 25px;
      margin-bottom: 20px;
      border-radius: 15px;
      border-left: 6px solid #6BAF92;
      border: 2px solid rgba(107, 175, 146, 0.25);
      transition: all 0.3s ease;
    }

    .feedback-item:hover {
      transform: translateX(5px);
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.15);
      border-left-color: #4B8B6E;
    }

    .feedback-item-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .feedback-type-badge {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 700;
      box-shadow: 0 3px 10px rgba(75, 139, 110, 0.3);
    }

    .feedback-date {
      color: #4B8B6E;
      font-size: 0.9rem;
      font-weight: 600;
    }
      font-weight: 500;
    }

    .feedback-subject {
      font-size: 1.15rem;
      font-weight: 700;
      color: #2D5A47;
      margin-bottom: 10px;
    }

    .feedback-message {
      color: #2D3E50;
      line-height: 1.7;
      font-weight: 400;
    }

    .feedback-status {
      margin-top: 15px;
      padding: 10px 15px;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 700;
      display: inline-block;
    }

    .feedback-status.pending {
      background: linear-gradient(135deg, rgba(244, 215, 124, 0.25), rgba(232, 197, 71, 0.25));
      color: #d4b03d;
      border: 2px solid rgba(232, 197, 71, 0.4);
    }

    .feedback-status.resolved {
      background: linear-gradient(135deg, rgba(107, 175, 146, 0.25), rgba(75, 139, 110, 0.25));
      color: #2D5A47;
      border: 2px solid rgba(75, 139, 110, 0.4);
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 200px;
      }
      
      .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
      }

      .feedback-types {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .previous-feedback {
      margin-top: 40px;
      padding-top: 30px;
      border-top: 2px solid #e0e0e0;
    }

    .feedback-item {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
      border-left: 4px solid #27ae60;
    }

    .feedback-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .feedback-type-badge {
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-concern {
      background-color: #fee;
      color: #e74c3c;
    }

    .badge-satisfaction {
      background-color: #d4edda;
      color: #27ae60;
    }

    .badge-feature {
      background-color: #d1ecf1;
      color: #3498db;
    }

    .badge-bug {
      background-color: #ffe8d1;
      color: #e67e22;
    }

    .badge-ui {
      background-color: #f3e5f5;
      color: #9b59b6;
    }

    .badge-general {
      background-color: #e9ecef;
      color: #6c757d;
    }

    .status-badge {
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-reviewed {
      background-color: #d1ecf1;
      color: #0c5460;
    }

    .status-resolved {
      background-color: #d4edda;
      color: #155724;
    }

    .admin-response {
      background-color: #fff;
      padding: 12px;
      border-radius: 6px;
      margin-top: 10px;
      border-left: 3px solid #3498db;
    }

    .admin-response-header {
      font-weight: 600;
      color: #3498db;
      margin-bottom: 5px;
    }

    .priority-select {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-top: 5px;
      font-size: 16px;
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <div class="logo">
        <img src="LOGO.png" alt="Logo">
        <h2>SkillSync</h2>
      </div>
      <div class="sidebar-content">
        <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="video_materials.php"><i class="fas fa-book-open"></i> Learning Materials</a>
        <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
        <a href="recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
        <a href="coding_practice.php"><i class="fas fa-code"></i> Coding Practice</a>
        <a href="feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    <div class="student-info">
      <img src="student.jpg" alt="Student">
      <div><strong>Student</strong></div>
      <div>student@email.com</div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
      <h1><i class="fas fa-comments"></i> Share Your Feedback</h1>
      <p>Help us improve SkillSync! Share your concerns, satisfaction, feature requests, or report issues.</p>
    </div>

    <div class="feedback-section">
      <?php if (!$tableExists): ?>
        <div style="background: linear-gradient(135deg, rgba(243, 156, 18, 0.15), rgba(243, 156, 18, 0.15)); border-left: 6px solid #f39c12; padding: 25px; margin-bottom: 30px; border-radius: 15px; border: 2px solid rgba(243, 156, 18, 0.3);">
          <h3 style="color: #4B8B6E; margin-bottom: 15px; font-weight: 700; font-size: 1.3rem;">
            <i class="fas fa-exclamation-triangle"></i> Setup Required
          </h3>
          <p style="color: #6BAF92; margin-bottom: 20px; line-height: 1.6;">
            The feedback system database table needs to be created first. Please ask your administrator to run the setup, or click the button below:
          </p>
          <a href="setup_feedback_system.php" class="submit-btn">
            <i class="fas fa-tools"></i> Run Setup Now
          </a>
        </div>
      <?php else: ?>

      <form id="feedbackForm" method="POST">
        <div class="feedback-type-section">
          <h3>Select Feedback Type</h3>
          <div class="feedback-types">
            <div class="feedback-type-card concern" data-type="concern">
              <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div class="label">Concern</div>
            </div>
            <div class="feedback-type-card satisfaction" data-type="satisfaction">
              <div class="icon">
                <i class="fas fa-smile"></i>
              </div>
              <div class="label">Satisfaction</div>
            </div>
            <div class="feedback-type-card feature" data-type="feature_request">
              <div class="icon">
                <i class="fas fa-lightbulb"></i>
              </div>
              <div class="label">Feature Request</div>
            </div>
            <div class="feedback-type-card bug" data-type="bug_report">
              <div class="icon">
                <i class="fas fa-bug"></i>
              </div>
              <div class="label">Bug Report</div>
            </div>
            <div class="feedback-type-card ui" data-type="ui_improvement">
              <div class="icon">
                <i class="fas fa-palette"></i>
              </div>
              <div class="label">UI Improvement</div>
            </div>
            <div class="feedback-type-card general" data-type="general">
              <div class="icon">
                <i class="fas fa-comment"></i>
              </div>
              <div class="label">General</div>
            </div>
          </div>
        </div>
        <input type="hidden" id="feedbackType" name="feedback_type" required>

        <!-- Rating (only for satisfaction) -->
        <div id="ratingSection" class="hidden">
          <label>How satisfied are you?</label>
          <div class="rating-container">
            <i class="fas fa-star star" data-rating="1"></i>
            <i class="fas fa-star star" data-rating="2"></i>
            <i class="fas fa-star star" data-rating="3"></i>
            <i class="fas fa-star star" data-rating="4"></i>
            <i class="fas fa-star star" data-rating="5"></i>
          </div>
          <input type="hidden" id="rating" name="rating">
        </div>

        <label for="subject">Subject / Title</label>
        <input type="text" id="subject" name="subject" placeholder="Brief summary of your feedback" required>

        <label for="message">Detailed Message</label>
        <textarea id="message" name="message" rows="6" placeholder="Please provide detailed information..." required></textarea>

        <label for="priority">Priority Level (Optional)</label>
        <select id="priority" name="priority" style="width: 100%; padding: 15px; border-radius: 15px; border: 2px solid rgba(107, 175, 146, 0.3); margin-top: 5px; font-size: 1rem; font-family: 'Poppins', sans-serif; background: rgba(255, 255, 255, 0.8);">
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
        </select>

        <button type="submit" class="submit-btn">
          <i class="fas fa-paper-plane"></i> Submit Feedback
        </button>

        <div id="responseMessage"></div>
      </form>

      <!-- Previous Feedback -->
      <?php if (!empty($previousFeedback)): ?>
      <div class="previous-feedback">
        <h3><i class="fas fa-history"></i> Your Previous Feedback</h3>
        <?php foreach ($previousFeedback as $fb): 
          $typeClass = str_replace(['_', ' '], '-', strtolower($fb['feedback_type']));
        ?>
        <div class="feedback-item">
          <div class="feedback-item-header">
            <span class="feedback-type-badge"><?= ucwords(str_replace('_', ' ', $fb['feedback_type'])) ?></span>
            <span class="feedback-date">
              <i class="far fa-clock"></i> <?= date('M j, Y', strtotime($fb['created_at'])) ?>
            </span>
          </div>
          <div class="feedback-subject"><?= htmlspecialchars($fb['subject']) ?></div>
          <div class="feedback-message"><?= nl2br(htmlspecialchars($fb['message'])) ?></div>
          <div class="feedback-status <?= strtolower($fb['status']) ?>">
            <i class="fas fa-circle"></i> <?= ucfirst($fb['status']) ?>
          </div>
          <?php if ($fb['admin_response']): ?>
          <div style="margin-top: 15px; padding: 15px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.1)); border-left: 4px solid #3498db; border-radius: 10px;">
            <div style="font-weight: 700; color: #3498db; margin-bottom: 8px;">
              <i class="fas fa-user-shield"></i> Admin Response:
            </div>
            <div style="color: #555; line-height: 1.6;"><?= nl2br(htmlspecialchars($fb['admin_response'])) ?></div>
            <small style="color: #6BAF92; margin-top: 8px; display: block;">
              <?= date('M j, Y g:i A', strtotime($fb['responded_at'])) ?>
            </small>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      
      <?php endif; // End table check ?>
    </div>
  </div>

  <script>
    // Feedback type selection
    const typeCards = document.querySelectorAll('.feedback-type-card');
    const feedbackTypeInput = document.getElementById('feedbackType');
    const ratingSection = document.getElementById('ratingSection');
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating');

    typeCards.forEach(card => {
      card.addEventListener('click', function() {
        typeCards.forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        const type = this.dataset.type;
        feedbackTypeInput.value = type;
        
        // Show rating section only for satisfaction
        if (type === 'satisfaction') {
          ratingSection.classList.remove('hidden');
        } else {
          ratingSection.classList.add('hidden');
          ratingInput.value = '';
        }
      });
    });

    // Star rating
    stars.forEach(star => {
      star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        ratingInput.value = rating;
        
        stars.forEach((s, index) => {
          if (index < rating) {
            s.classList.add('active');
          } else {
            s.classList.remove('active');
          }
        });
      });
    });

    // Feedback type card selection
    typeCards.forEach(card => {
      card.addEventListener('click', function() {
        typeCards.forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        const type = this.dataset.type;
        feedbackTypeInput.value = type;
        
        // Show rating section only for satisfaction
        if (type === 'satisfaction') {
          ratingSection.classList.remove('hidden');
        } else {
          ratingSection.classList.add('hidden');
          ratingInput.value = '';
        }
      });
    });

    // Star rating
    stars.forEach(star => {
      star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        ratingInput.value = rating;
        
        stars.forEach((s, index) => {
          if (index < rating) {
            s.classList.add('active');
          } else {
            s.classList.remove('active');
          }
        });
      });
    });

    // Form submission
    document.getElementById('feedbackForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const feedbackType = feedbackTypeInput.value;
      if (!feedbackType) {
        document.getElementById('responseMessage').innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> Please select a feedback type</div>';
        return;
      }

      if (feedbackType === 'satisfaction' && !ratingInput.value) {
        document.getElementById('responseMessage').innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> Please provide a rating for satisfaction feedback</div>';
        return;
      }

      const formData = new FormData(this);

      fetch('submit_feedback.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('responseMessage').innerHTML = '<div class="success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
          this.reset();
          typeCards.forEach(c => c.classList.remove('selected'));
          feedbackTypeInput.value = '';
          ratingSection.classList.add('hidden');
          stars.forEach(s => s.classList.remove('active'));
          
          // Reload after 2 seconds to show new feedback
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          document.getElementById('responseMessage').innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
        }
      })
      .catch(error => {
        document.getElementById('responseMessage').innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.</div>';
        console.error('Error:', error);
      });
    });
  </script>
</body>

</html>
