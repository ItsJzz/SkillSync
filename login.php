<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - SkillSync</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    /* Animated Background Elements */
    .bg-decoration {
      position: absolute;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 0;
    }

    .dot-pattern {
      position: absolute;
      width: 8px;
      height: 8px;
      background: rgba(75, 139, 110, 0.15);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }

    .dot-pattern:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; }
    .dot-pattern:nth-child(2) { top: 20%; right: 8%; animation-delay: 1s; }
    .dot-pattern:nth-child(3) { top: 50%; left: 3%; animation-delay: 2s; }
    .dot-pattern:nth-child(4) { top: 70%; right: 5%; animation-delay: 1.5s; }
    .dot-pattern:nth-child(5) { top: 85%; left: 10%; animation-delay: 0.5s; }
    .dot-pattern:nth-child(6) { top: 30%; right: 20%; animation-delay: 0.8s; }

    .circle-decoration {
      position: absolute;
      border: 2px solid rgba(75, 139, 110, 0.1);
      border-radius: 50%;
      animation: rotate 20s linear infinite;
    }

    .circle-decoration:nth-child(7) { 
      width: 150px; 
      height: 150px; 
      top: 15%; 
      right: 15%;
    }

    .circle-decoration:nth-child(8) { 
      width: 100px; 
      height: 100px; 
      bottom: 20%; 
      left: 8%;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Login Container */
    .login-container {
      position: relative;
      z-index: 1;
      background: #FFFFFF;
      border-radius: 30px;
      box-shadow: 0 20px 60px rgba(75, 139, 110, 0.25);
      max-width: 480px;
      width: 100%;
      padding: 50px 45px;
      animation: slideIn 0.6s ease;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    /* Logo and Header */
    .login-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .logo-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 20px;
    }

    .logo-wrapper img {
      width: 80px;
      height: 80px;
      filter: drop-shadow(0 4px 8px rgba(75, 139, 110, 0.3));
      animation: float 3s ease-in-out infinite;
    }

    .login-header h1 {
      font-size: 32px;
      color: #4B8B6E;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .login-header p {
      font-size: 15px;
      color: #6BAF92;
      font-weight: 500;
    }

    /* Form Styles */
    .login-form {
      margin-bottom: 25px;
    }

    .input-group {
      position: relative;
      margin-bottom: 25px;
    }

    .input-icon {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      color: #6BAF92;
      font-size: 18px;
      z-index: 1;
    }

    .login-form input {
      width: 100%;
      padding: 18px 20px 18px 55px;
      border: 2px solid #E5E5E5;
      border-radius: 15px;
      font-size: 15px;
      font-family: "Poppins", sans-serif;
      transition: all 0.3s ease;
      background: #F9F9F6;
      color: #4A4A4A;
    }

    .login-form input:focus {
      outline: none;
      border-color: #6BAF92;
      background: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(107, 175, 146, 0.1);
    }

    .login-form input::placeholder {
      color: #A0A0A0;
    }

    /* Password Toggle */
    .password-toggle {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      color: #6BAF92;
      cursor: pointer;
      font-size: 18px;
      transition: color 0.3s;
    }

    .password-toggle:hover {
      color: #4B8B6E;
    }

    /* Login Button */
    .login-btn {
      width: 100%;
      padding: 18px;
      background: linear-gradient(135deg, #4B8B6E 0%, #6BAF92 100%);
      color: #FFFFFF;
      border: none;
      border-radius: 15px;
      font-size: 17px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.3);
      position: relative;
      overflow: hidden;
    }

    .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }

    .login-btn:hover::before {
      left: 100%;
    }

    .login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(75, 139, 110, 0.4);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    /* Divider */
    .divider {
      text-align: center;
      margin: 25px 0;
      position: relative;
    }

    .divider::before,
    .divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 40%;
      height: 1px;
      background: #E5E5E5;
    }

    .divider::before {
      left: 0;
    }

    .divider::after {
      right: 0;
    }

    .divider span {
      background: #FFFFFF;
      padding: 0 15px;
      color: #A0A0A0;
      font-size: 14px;
      position: relative;
    }

    /* Links */
    .register-link {
      text-align: center;
      margin-bottom: 20px;
    }

    .register-link p {
      color: #4A4A4A;
      font-size: 15px;
    }

    .register-link a {
      color: #6BAF92;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .register-link a:hover {
      color: #E8C547;
    }

    /* Back Button */
    .back-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      color: #6BAF92;
      text-decoration: none;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.3s;
      padding: 12px;
      border-radius: 10px;
    }

    .back-link:hover {
      color: #4B8B6E;
      background: rgba(107, 175, 146, 0.1);
    }

    .back-link i {
      transition: transform 0.3s;
    }

    .back-link:hover i {
      transform: translateX(-5px);
    }

    /* Feature Badges */
    .feature-badges {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .badge {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 8px 15px;
      background: rgba(107, 175, 146, 0.1);
      border-radius: 20px;
      font-size: 13px;
      color: #4B8B6E;
      font-weight: 500;
    }

    .badge i {
      color: #E8C547;
      font-size: 14px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .login-container {
        padding: 40px 30px;
        margin: 20px;
      }

      .login-header h1 {
        font-size: 26px;
      }

      .logo-wrapper img {
        width: 60px;
        height: 60px;
      }
    }
  </style>
</head>
<body>
  <!-- Background Decorations -->
  <div class="bg-decoration">
    <div class="dot-pattern"></div>
    <div class="dot-pattern"></div>
    <div class="dot-pattern"></div>
    <div class="dot-pattern"></div>
    <div class="dot-pattern"></div>
    <div class="dot-pattern"></div>
    <div class="circle-decoration"></div>
    <div class="circle-decoration"></div>
  </div>

  <!-- Login Container -->
  <div class="login-container">
    <!-- Header -->
    <div class="login-header">
      <div class="logo-wrapper">
        <img src="LOGO.png" alt="SkillSync Logo">
      </div>
      <h1>Login to SkillSync</h1>
      <p>Welcome back! Continue your learning journey</p>
    </div>

    <!-- Login Form -->
    <form method="POST" action="login.php" class="login-form">
      <div class="input-group">
        <i class="fas fa-envelope input-icon"></i>
        <input type="email" name="email" placeholder="Email Address" required>
      </div>

      <div class="input-group">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
      </div>

      <button type="submit" class="login-btn">
        <i class="fas fa-sign-in-alt"></i> Login
      </button>
    </form>

    <!-- Divider -->
    <div class="divider">
      <span>OR</span>
    </div>

    <!-- Register Link -->
    <div class="register-link">
      <p>Don't have an account? <a href="register.php">Register Now</a></p>
    </div>

    <!-- Back to Homepage -->
    <a href="Homepage.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Back to Homepage
    </a>

    <!-- Feature Badges -->
    <div class="feature-badges">
      <div class="badge">
        <i class="fas fa-shield-alt"></i> Secure Login
      </div>
      <div class="badge">
        <i class="fas fa-bolt"></i> Fast Access
      </div>
      <div class="badge">
        <i class="fas fa-user-check"></i> Personalized
      </div>
    </div>
  </div>

  <script>
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.classList.toggle('fa-eye');
      this.classList.toggle('fa-eye-slash');
    });
  </script>

  <?php
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $email = $_POST['email'];
      $password = $_POST['password'];

      // Database connection
      require_once 'db_connect.php';

      // Use prepared statement to fetch user with role
      $stmt = $conn->prepare("SELECT id, username, password, role FROM login_credentials WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows > 0) {
          $stmt->bind_result($id, $username, $hashedPassword, $role);
          $stmt->fetch();

          // Verify the password is hashed (should start with $2y$ for bcrypt)
          // If password is not hashed, this is a security issue that needs fixing
          if (empty($hashedPassword)) {
              echo "<script>alert('Error: Password data is missing. Please contact administrator.');</script>";
          } elseif (password_verify($password, $hashedPassword)) {
            // Login successful - password verified correctly
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // Check user role and redirect accordingly
            if ($role === 'admin') {
                // Admin login - redirect to admin dashboard
                echo "<script>
                    alert('Welcome Admin!');
                    window.location.href='admin/admin_dashboard.php';
                </script>";
            } else {
                // Student login - check pre-assessment completion
                $preAssessStmt = $conn->prepare("SELECT completed_preassessment FROM login_credentials WHERE id = ?");
                $preAssessStmt->bind_param("i", $id);
                $preAssessStmt->execute();
                $preAssessResult = $preAssessStmt->get_result()->fetch_assoc();
                $preAssessStmt->close();

                if ($preAssessResult['completed_preassessment'] == 0) {
                    // New student - redirect to pre-assessment onboarding
                    echo "<script>
                        alert('Welcome to SkillSync! Let\\'s start with a quick assessment to personalize your learning experience.');
                        window.location.href='pre_assessment_onboarding.php';
                    </script>";
                } else {
                    // Existing student - go to dashboard
                    echo "<script>
                        alert('Welcome back!');
                        window.location.href='student_dashboard.php';
                    </script>";
                }
            }
          } else {
              echo "<script>alert('Invalid password!');</script>";
          }
      } else {
          echo "<script>alert('Email not found!');</script>";
      }

      $stmt->close();
      $conn->close();
  }
  ?>
</body>
</html>
