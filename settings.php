<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Database connection
require_once 'db_connect.php';

// Get user info - handle case where theme columns don't exist yet
$userQuery = "SELECT username, email FROM login_credentials WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Try to get theme preferences if columns exist
$theme = 'light';
$notifications = 1;
try {
    $themeQuery = "SELECT theme_preference, email_notifications FROM login_credentials WHERE id = ?";
    $themeStmt = $conn->prepare($themeQuery);
    $themeStmt->bind_param("i", $student_id);
    $themeStmt->execute();
    $themeResult = $themeStmt->get_result();
    if ($themeData = $themeResult->fetch_assoc()) {
        $theme = $themeData['theme_preference'] ?? 'light';
        $notifications = $themeData['email_notifications'] ?? 1;
    }
    $themeStmt->close();
} catch (Exception $e) {
    // Columns don't exist yet - use defaults
    $theme = 'light';
    $notifications = 1;
}

// Handle success/error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Set default values
$username = $user['username'] ?? '';
$email = $user['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>SkillSync - Settings</title>
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

    /* Sidebar */
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

    /* Main Content */
    .main-content {
      margin-left: 240px;
      padding: 30px;
      width: calc(100% - 240px);
      min-height: 100vh;
    }

    .settings-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 25px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
      max-width: 800px;
      margin: 0 auto;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    .settings-card h2 {
      text-align: center;
      margin-bottom: 35px;
      color: #4B8B6E;
      font-size: 2rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
    }

    .settings-card h2 i {
      color: #4B8B6E;
    }

    form label {
      display: block;
      margin: 25px 0 10px;
      font-weight: 600;
      color: #4B8B6E;
      font-size: 1.05rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    select {
      width: 100%;
      padding: 15px;
      border-radius: 15px;
      border: 2px solid rgba(107, 175, 146, 0.3);
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      transition: all 0.3s;
      background: rgba(255, 255, 255, 0.8);
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    select:focus {
      outline: none;
      border-color: #4B8B6E;
      box-shadow: 0 5px 20px rgba(75, 139, 110, 0.15);
      background: white;
    }

    .checkbox {
      margin-top: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .checkbox input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: #4B8B6E;
    }

    .checkbox label {
      font-weight: 500;
      margin: 0;
      color: #6BAF92;
      cursor: pointer;
    }

    .btn {
      margin-top: 30px;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      border: none;
      padding: 16px 40px;
      font-weight: 700;
      border-radius: 30px;
      cursor: pointer;
      width: 100%;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
      font-size: 1.1rem;
      box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(75, 139, 110, 0.4);
      background: linear-gradient(135deg, #3d7459, #5a9c80);
    }

    .alert {
      padding: 15px;
      border-radius: 15px;
      margin-bottom: 25px;
      font-weight: 500;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .alert.success {
      background-color: rgba(107, 175, 146, 0.15);
      color: #4B8B6E;
      border: 2px solid rgba(107, 175, 146, 0.3);
    }

    .alert.error {
      background-color: rgba(231, 76, 60, 0.15);
      color: #e74c3c;
      border: 2px solid rgba(231, 76, 60, 0.3);
    }

    footer {
      text-align: center;
      padding: 20px;
      font-size: 0.9rem;
      color: rgba(75, 139, 110, 0.6);
      margin-top: 50px;
      font-weight: 500;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 200px;
      }

      .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
      }

      .settings-card {
        padding: 25px;
      }
    }

    @media (max-width: 600px) {
      .sidebar {
        display: none;
      }

      .main-content {
        margin-left: 0;
        width: 100%;
      }
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
        <a href="progress.php"><i class="fas fa-chart-line"></i> Progress</a>
        <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
        <a class="active" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    <div class="student-info">
      <img src="student.jpg" alt="Student">
      <div><strong><?php echo htmlspecialchars($username); ?></strong></div>
      <div><?php echo htmlspecialchars($email); ?></div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="settings-card">
      <h2><i class="fas fa-cog"></i> Account Settings</h2>
      
      <?php if ($success_message): ?>
        <div class="alert success">
          <i class="fas fa-check-circle"></i>
          <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
        <div class="alert error">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <form action="update_settings.php" method="POST">
        <label for="fullname"><i class="fas fa-user"></i> Full Name</label>
        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" value="<?php echo htmlspecialchars($username); ?>" required>

        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>" required>

        <label for="password"><i class="fas fa-lock"></i> Change Password</label>
        <input type="password" id="password" name="password" placeholder="Enter new password (leave blank to keep current)">

        <div class="checkbox">
          <input type="checkbox" id="notifications" name="notifications" <?php echo ($notifications == 1) ? 'checked' : ''; ?>>
          <label for="notifications">Receive email notifications for new quizzes and activities</label>
        </div>

        <button type="submit" class="btn">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>

    <footer>
      &copy; 2025 SkillSync. All rights reserved.
    </footer>
  </div>

  <script>
    // Auto-hide success/error messages after 5 seconds
    setTimeout(function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
      });
    }, 5000);

    // Form validation
    const form = document.querySelector('form');
    const passwordInput = document.getElementById('password');
    
    form.addEventListener('submit', function(e) {
      const password = passwordInput.value;
      
      // Validate password if entered
      if (password.length > 0 && password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
      }
    });

    // Show confirmation when password is being changed
    passwordInput.addEventListener('input', function() {
      if (this.value.length > 0) {
        form.addEventListener('submit', function(e) {
          if (passwordInput.value.length > 0) {
            if (!confirm('Are you sure you want to change your password?')) {
              e.preventDefault();
              return false;
            }
          }
        }, { once: true });
      }
    });
  </script>
</body>

</html>
