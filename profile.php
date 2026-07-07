<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $nickname = $_POST['nickname'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $course = $_POST['course'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $location = $_POST['location'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $learning_style = $_POST['learning_style'] ?? '';
    $study_schedule = $_POST['study_schedule'] ?? '';
    $difficulty_preference = $_POST['difficulty_preference'] ?? '';
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    
    // Update or insert profile
    $updateProfileQuery = "
        INSERT INTO user_profiles (user_id, nickname, first_name, last_name, bio, course, year_level, location, phone, learning_style, study_schedule, difficulty_preference, notifications_enabled)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        nickname = VALUES(nickname),
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        bio = VALUES(bio),
        course = VALUES(course),
        year_level = VALUES(year_level),
        location = VALUES(location),
        phone = VALUES(phone),
        learning_style = VALUES(learning_style),
        study_schedule = VALUES(study_schedule),
        difficulty_preference = VALUES(difficulty_preference),
        notifications_enabled = VALUES(notifications_enabled)
    ";
    
    $updateStmt = $conn->prepare($updateProfileQuery);
    $updateStmt->bind_param("isssssssssssi", $student_id, $nickname, $first_name, $last_name, $bio, $course, $year_level, $location, $phone, $learning_style, $study_schedule, $difficulty_preference, $notifications_enabled);
    
    if ($updateStmt->execute()) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
    $updateStmt->close();
}

// Get user info from login_credentials and user_profiles
$userQuery = "
    SELECT 
        lc.username, 
        lc.email, 
        lc.created_at as member_since,
        up.nickname,
        up.first_name,
        up.last_name,
        up.bio,
        up.course,
        up.year_level,
        up.location,
        up.phone,
        up.date_of_birth,
        up.gender,
        up.learning_style,
        up.study_schedule,
        up.difficulty_preference,
        up.notifications_enabled,
        up.profile_picture
    FROM login_credentials lc
    LEFT JOIN user_profiles up ON lc.id = up.user_id
    WHERE lc.id = ?
";

$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Get user statistics
// 1. Activities Completed
$activitiesQuery = "SELECT COUNT(DISTINCT topic_id) as completed FROM student_activity_scores WHERE student_id = ?";
$actStmt = $conn->prepare($activitiesQuery);
$actStmt->bind_param("i", $student_id);
$actStmt->execute();
$activities_completed = $actStmt->get_result()->fetch_assoc()['completed'];
$actStmt->close();

// 2. Total topics with activities
$totalActQuery = "SELECT COUNT(*) as total FROM topics WHERE id IN (SELECT DISTINCT topic_id FROM save_progress)";
$totalActivities = $conn->query($totalActQuery)->fetch_assoc()['total'];

// 3. Coding Practice Stats
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

// 4. Assessments Taken
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

// 5. Videos Watched
$videosQuery = "SELECT COUNT(*) as videos_watched FROM student_video_progress WHERE student_id = ?";
$videoStmt = $conn->prepare($videosQuery);
$videoStmt->bind_param("i", $student_id);
$videoStmt->execute();
$videos_watched = $videoStmt->get_result()->fetch_assoc()['videos_watched'];
$videoStmt->close();

// 6. Recent Activities
$recentActivitiesQuery = "
    SELECT 'activity' as type, 'Completed activity for topic' as description, date_created as activity_date, t.name as topic_name
    FROM student_activity_scores sas
    JOIN topics t ON sas.topic_id = t.id
    WHERE sas.student_id = ?
    UNION ALL
    SELECT 'video' as type, 'Watched video material' as description, watched_at as activity_date, '' as topic_name
    FROM student_video_progress
    WHERE student_id = ?
    UNION ALL
    SELECT 'test' as type, CONCAT(test_type, '-test taken') as description, attempt_date as activity_date, t.name as topic_name
    FROM student_tests st
    JOIN topics t ON st.topic_id = t.id
    WHERE st.student_id = ?
    ORDER BY activity_date DESC
    LIMIT 4
";
$recentStmt = $conn->prepare($recentActivitiesQuery);
$recentStmt->bind_param("iii", $student_id, $student_id, $student_id);
$recentStmt->execute();
$recentActivities = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStmt->close();

// Calculate overall progress
$overall_progress = $totalActivities > 0 ? round(($activities_completed / $totalActivities) * 100) : 0;

// Display name priority: nickname > first_name last_name > username
$display_name = $user['nickname'] ?: ($user['first_name'] ? $user['first_name'] . ' ' . $user['last_name'] : $user['username']);
$display_name = trim($display_name) ?: $user['username'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>SkillSync - Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="LOGO.png" type="image/x-icon" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
      color: #333333;
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

    .sidebar-content a:hover,
    .sidebar-content a.active {
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
      display: flex;
      align-items: center;
      gap: 15px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-title i {
      color: #4B8B6E;
    }

    .page-subtitle {
      color: #3D6B54;
      font-size: 1.1rem;
      font-weight: 500;
    }

    /* Profile Header Card */
    .profile-header-card {
      background: #FFFFFF;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15);
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .profile-header-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #4B8B6E, #6BAF92, #E8C547);
    }

    .profile-info {
      display: flex;
      align-items: center;
      gap: 30px;
      margin-bottom: 30px;
    }

    .profile-avatar {
      position: relative;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    .profile-avatar img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 4px solid #4B8B6E;
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.3);
    }

    .profile-avatar .status-badge {
      position: absolute;
      bottom: 5px;
      right: 5px;
      width: 25px;
      height: 25px;
      background: #E8C547;
      border: 3px solid #FFFFFF;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    .profile-details h2 {
      font-size: 2rem;
      color: #2D5A47;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .profile-details .profile-meta {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .profile-meta-item {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #4B8B6E;
      font-size: 1rem;
      font-weight: 500;
    }

    .profile-meta-item i {
      color: #E8C547;
      width: 20px;
    }

    /* About Me Section */
    .about-section {
      background: #FFFFFF;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15);
      margin-bottom: 30px;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .about-section h3 {
      font-size: 1.5rem;
      color: #2D5A47;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
    }

    .about-section h3 i {
      color: #E8C547;
    }

    .about-section p {
      font-size: 1.1rem;
      line-height: 1.6;
      color: #2D3E50;
      font-weight: 400;
    }

    /* Enhanced KPI Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 25px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: #FFFFFF;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
    }

    .stat-card.courses::before {
      background: linear-gradient(90deg, #4B8B6E, #6BAF92);
    }

    .stat-card.skills::before {
      background: linear-gradient(90deg, #E8C547, #F4D77C);
    }

    .stat-card.progress::before {
      background: linear-gradient(90deg, #6BAF92, #4B8B6E);
    }

    .stat-card.achievements::before {
      background: linear-gradient(90deg, #4B8B6E, #E8C547);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(75, 139, 110, 0.25);
      border-color: #4B8B6E;
    }

    .stat-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .stat-icon {
      font-size: 2.5rem;
    }

    .stat-icon.courses {
      color: #4B8B6E;
    }

    .stat-icon.skills {
      color: #E8C547;
    }

    .stat-icon.progress {
      color: #6BAF92;
    }

    .stat-icon.achievements {
      color: #4B8B6E;
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: bold;
      color: #2D5A47;
    }

    .stat-label {
      font-size: 1rem;
      color: #4B8B6E;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .stat-detail {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 10px 0;
      border-top: 1px solid rgba(107, 175, 146, 0.2);
    }

    .progress-circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: conic-gradient(#4B8B6E 75%, rgba(107, 175, 146, 0.2) 0);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      font-weight: bold;
      color: #4B8B6E;
      position: relative;
    }

    .progress-circle::after {
      content: '';
      position: absolute;
      width: 60px;
      height: 60px;
      background: #FFFFFF;
      border-radius: 50%;
      z-index: -1;
    }

    /* Additional Info Cards */
    .info-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
    }

    .info-card {
      background: #FFFFFF;
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15);
      transition: all 0.3s ease;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .info-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(75, 139, 110, 0.25);
      border-color: #4B8B6E;
    }

    .info-card h4 {
      font-size: 1.3rem;
      color: #2D5A47;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
    }

    .info-card h4 i {
      color: #E8C547;
    }

    .info-list {
      list-style: none;
    }

    .info-list li {
      padding: 8px 0;
      border-bottom: 1px solid rgba(107, 175, 146, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #2D3E50;
      font-weight: 500;
    }

    .info-list li:last-child {
      border-bottom: none;
    }

    .badge {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    /* Edit Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      backdrop-filter: blur(5px);
      animation: fadeIn 0.3s ease;
    }

    .modal-content {
      background-color: #FFFFFF;
      margin: 5% auto;
      padding: 0;
      border-radius: 20px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 20px 40px rgba(75, 139, 110, 0.3);
      animation: slideIn 0.3s ease;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .modal-header {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      padding: 20px 30px;
      border-radius: 20px 20px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      font-weight: 700;
    }

    .modal-body {
      padding: 30px;
    }

    .close {
      color: white;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .close:hover {
      transform: scale(1.1) rotate(90deg);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #2D5A47;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid rgba(107, 175, 146, 0.3);
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #4B8B6E;
      box-shadow: 0 0 0 3px rgba(75, 139, 110, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
      width: auto;
    }

    .btn-primary {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.4);
    }

    .btn-edit {
      background: linear-gradient(135deg, #E8C547, #F4D77C);
      color: #4B8B6E;
      padding: 10px 20px;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      font-family: 'Poppins', sans-serif;
    }

    .btn-edit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(232, 197, 71, 0.4);
    }

    .alert {
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-weight: 500;
    }

    .alert-success {
      background: linear-gradient(135deg, rgba(75, 139, 110, 0.1), rgba(107, 175, 146, 0.2));
      color: #4B8B6E;
      border: 2px solid rgba(75, 139, 110, 0.3);
    }

    .alert-error {
      background: linear-gradient(135deg, rgba(232, 197, 71, 0.1), rgba(244, 215, 124, 0.2));
      color: #E8C547;
      border: 2px solid rgba(232, 197, 71, 0.3);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideIn {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .main-content {
        margin-left: 0;
        width: 100%;
      }
      
      .profile-info {
        flex-direction: column;
        text-align: center;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .info-cards {
        grid-template-columns: 1fr;
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
        <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
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
      <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'student.jpg') ?>" alt="Student">
      <div><strong><?= htmlspecialchars($display_name) ?></strong></div>
      <div><?= htmlspecialchars($user['email']) ?></div>
    </div>
  </div>

  <div class="main-content">
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">My Profile 👤</h1>
      <p class="page-subtitle">Manage your account and track your learning journey</p>
    </div>

    <!-- Profile Header Card -->
    <div class="profile-header-card">
      <div class="profile-info">
        <div class="profile-avatar">
          <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'student.jpg') ?>" alt="Profile" />
          <div class="status-badge"></div>
        </div>
        <div class="profile-details">
          <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
              <h2><?= htmlspecialchars($display_name) ?></h2>
              <div class="profile-meta">
                <div class="profile-meta-item">
                  <i class="fas fa-envelope"></i>
                  <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="profile-meta-item">
                  <i class="fas fa-graduation-cap"></i>
                  <span><?= htmlspecialchars($user['course'] ?? 'Bachelor of Science in Information Technology (BSIT)') ?></span>
                </div>
                <div class="profile-meta-item">
                  <i class="fas fa-calendar-alt"></i>
                  <span>Member since <?= date('F Y', strtotime($user['member_since'])) ?></span>
                </div>
                <div class="profile-meta-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span><?= htmlspecialchars($user['location'] ?? 'Philippines') ?></span>
                </div>
                <?php if ($user['phone']): ?>
                <div class="profile-meta-item">
                  <i class="fas fa-phone"></i>
                  <span><?= htmlspecialchars($user['phone']) ?></span>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <button class="btn-edit" onclick="openEditModal()">
              <i class="fas fa-edit"></i> Edit Profile
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- About Me Section -->
    <div class="about-section">
      <h3><i class="fas fa-user-circle"></i> About Me</h3>
      <p>
        <?= htmlspecialchars($user['bio'] ?? 'Enthusiastic IT student with a passion for web development, data analysis, and emerging technologies. Always eager to learn and improve through hands-on projects and collaboration.') ?>
      </p>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
      <!-- Courses Card -->
      <div class="stat-card courses">
        <div class="stat-header">
          <i class="fas fa-book-reader stat-icon courses"></i>
          <div class="stat-value"><?= $activities_completed ?></div>
        </div>
        <div class="stat-label">Activities Completed</div>
        <div class="stat-detail">
          <span style="color: #667eea; font-weight: 600;">🎯 <?= $activities_completed ?>/<?= $totalActivities ?> topics</span>
        </div>
      </div>

      <!-- Skills Card -->
      <div class="stat-card skills">
        <div class="stat-header">
          <i class="fas fa-code stat-icon skills"></i>
          <div class="stat-value"><?= $codingStats['problems_solved'] ?></div>
        </div>
        <div class="stat-label">Coding Problems Solved</div>
        <div class="stat-detail">
          <span style="color: #f5576c; font-weight: 600;">💪 Best Score: <?= $codingStats['best_score'] ?></span>
        </div>
      </div>

      <!-- Progress Card -->
      <div class="stat-card progress">
        <div class="stat-header">
          <i class="fas fa-chart-line stat-icon progress"></i>
          <div class="progress-circle"><?= $overall_progress ?>%</div>
        </div>
        <div class="stat-label">Overall Progress</div>
        <div class="stat-detail">
          <span style="color: #4facfe; font-weight: 600;">📈 <?= $overall_progress >= 75 ? 'Excellent' : ($overall_progress >= 50 ? 'Good' : 'Getting Started') ?> progress</span>
        </div>
      </div>

      <!-- Assessments Card -->
      <div class="stat-card achievements">
        <div class="stat-header">
          <i class="fas fa-clipboard-check stat-icon achievements"></i>
          <div class="stat-value"><?= ($assessments['pre_tests'] + $assessments['post_tests']) ?></div>
        </div>
        <div class="stat-label">Assessments Taken</div>
        <div class="stat-detail">
          <span style="color: #43e97b; font-weight: 600;">🏆 <?= $assessments['pre_tests'] ?> pre, <?= $assessments['post_tests'] ?> post</span>
        </div>
      </div>
    </div>

    <!-- Additional Information Cards -->
    <div class="info-cards">
      <!-- Recent Activities -->
      <div class="info-card">
        <h4><i class="fas fa-clock"></i> Recent Activities</h4>
        <ul class="info-list">
          <?php if (empty($recentActivities)): ?>
          <li>
            <span>No recent activities</span>
            <span class="badge">Start Learning!</span>
          </li>
          <?php else: ?>
          <?php foreach ($recentActivities as $activity): ?>
          <li>
            <span><?= htmlspecialchars($activity['description']) ?> <?= $activity['topic_name'] ? 'for ' . htmlspecialchars($activity['topic_name']) : '' ?></span>
            <span class="badge"><?= date('M j', strtotime($activity['activity_date'])) ?></span>
          </li>
          <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Learning Preferences -->
      <div class="info-card">
        <h4><i class="fas fa-cog"></i> Learning Preferences</h4>
        <ul class="info-list">
          <li>
            <span>Preferred Learning Style</span>
            <span style="color: #27ae60; font-weight: 600;"><?= htmlspecialchars($user['learning_style'] ?? 'Visual & Hands-on') ?></span>
          </li>
          <li>
            <span>Study Schedule</span>
            <span style="color: #27ae60; font-weight: 600;"><?= htmlspecialchars($user['study_schedule'] ?? 'Evenings') ?></span>
          </li>
          <li>
            <span>Difficulty Level</span>
            <span style="color: #27ae60; font-weight: 600;"><?= htmlspecialchars($user['difficulty_preference'] ?? 'Intermediate') ?></span>
          </li>
          <li>
            <span>Notifications</span>
            <span style="color: #27ae60; font-weight: 600;"><?= $user['notifications_enabled'] ? 'Enabled' : 'Disabled' ?></span>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3><i class="fas fa-edit"></i> Edit Profile</h3>
        <span class="close" onclick="closeEditModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form method="POST" action="">
          <input type="hidden" name="update_profile" value="1">
          
          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="nickname">Nickname/Display Name</label>
            <input type="text" id="nickname" name="nickname" value="<?= htmlspecialchars($user['nickname'] ?? '') ?>" placeholder="How you'd like to be called">
          </div>

          <div class="form-group">
            <label for="bio">About Me</label>
            <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="course">Course</label>
              <input type="text" id="course" name="course" value="<?= htmlspecialchars($user['course'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="year_level">Year Level</label>
              <select id="year_level" name="year_level">
                <option value="">Select Year Level</option>
                <option value="1st Year" <?= ($user['year_level'] ?? '') == '1st Year' ? 'selected' : '' ?>>1st Year</option>
                <option value="2nd Year" <?= ($user['year_level'] ?? '') == '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                <option value="3rd Year" <?= ($user['year_level'] ?? '') == '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                <option value="4th Year" <?= ($user['year_level'] ?? '') == '4th Year' ? 'selected' : '' ?>>4th Year</option>
                <option value="Graduate" <?= ($user['year_level'] ?? '') == 'Graduate' ? 'selected' : '' ?>>Graduate</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="location">Location</label>
              <input type="text" id="location" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="learning_style">Learning Style</label>
              <select id="learning_style" name="learning_style">
                <option value="Visual & Hands-on" <?= ($user['learning_style'] ?? '') == 'Visual & Hands-on' ? 'selected' : '' ?>>Visual & Hands-on</option>
                <option value="Auditory" <?= ($user['learning_style'] ?? '') == 'Auditory' ? 'selected' : '' ?>>Auditory</option>
                <option value="Reading/Writing" <?= ($user['learning_style'] ?? '') == 'Reading/Writing' ? 'selected' : '' ?>>Reading/Writing</option>
                <option value="Kinesthetic" <?= ($user['learning_style'] ?? '') == 'Kinesthetic' ? 'selected' : '' ?>>Kinesthetic</option>
              </select>
            </div>
            <div class="form-group">
              <label for="study_schedule">Study Schedule</label>
              <select id="study_schedule" name="study_schedule">
                <option value="Mornings" <?= ($user['study_schedule'] ?? '') == 'Mornings' ? 'selected' : '' ?>>Mornings</option>
                <option value="Afternoons" <?= ($user['study_schedule'] ?? '') == 'Afternoons' ? 'selected' : '' ?>>Afternoons</option>
                <option value="Evenings" <?= ($user['study_schedule'] ?? '') == 'Evenings' ? 'selected' : '' ?>>Evenings</option>
                <option value="Weekends" <?= ($user['study_schedule'] ?? '') == 'Weekends' ? 'selected' : '' ?>>Weekends</option>
                <option value="Flexible" <?= ($user['study_schedule'] ?? '') == 'Flexible' ? 'selected' : '' ?>>Flexible</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="difficulty_preference">Difficulty Preference</label>
            <select id="difficulty_preference" name="difficulty_preference">
              <option value="Beginner" <?= ($user['difficulty_preference'] ?? '') == 'Beginner' ? 'selected' : '' ?>>Beginner</option>
              <option value="Intermediate" <?= ($user['difficulty_preference'] ?? '') == 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
              <option value="Advanced" <?= ($user['difficulty_preference'] ?? '') == 'Advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="notifications_enabled" name="notifications_enabled" <?= $user['notifications_enabled'] ? 'checked' : '' ?>>
            <label for="notifications_enabled">Enable notifications</label>
          </div>

          <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function openEditModal() {
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      var modal = document.getElementById('editModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>

</html>
