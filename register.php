<?php // register.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - SkillSync</title>
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

    /* Navigation Bar */
    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 15px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 20px rgba(75, 139, 110, 0.1);
      z-index: 1000;
      border-bottom: 2px solid rgba(107, 175, 146, 0.1);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      transition: transform 0.3s ease;
    }

    .navbar-brand:hover {
      transform: scale(1.05);
    }

    .navbar-brand img {
      width: 45px;
      height: 45px;
      filter: drop-shadow(0 2px 5px rgba(75, 139, 110, 0.2));
    }

    .navbar-brand-text {
      font-size: 22px;
      font-weight: 800;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .navbar-links {
      display: flex;
      gap: 30px;
      align-items: center;
    }

    .navbar-links a {
      text-decoration: none;
      color: #4B8B6E;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s ease;
      padding: 8px 16px;
      border-radius: 20px;
    }

    .navbar-links a:hover {
      background: rgba(107, 175, 146, 0.1);
      color: #4B8B6E;
    }

    .navbar-links a.btn-nav {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: white;
      padding: 10px 25px;
      border-radius: 25px;
      box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
    }

    .navbar-links a.btn-nav:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(75, 139, 110, 0.4);
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
      display: none;
      flex-direction: column;
      gap: 5px;
      cursor: pointer;
      padding: 8px;
    }

    .mobile-menu-toggle span {
      width: 25px;
      height: 3px;
      background: #4B8B6E;
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    /* Register Container */
    .register-container {
      position: relative;
      z-index: 1;
      margin-top: 80px;
      background: #FFFFFF;
      border-radius: 30px;
      box-shadow: 0 20px 60px rgba(75, 139, 110, 0.25);
      max-width: 520px;
      width: 100%;
      padding: 50px 45px;
      animation: slideIn 0.6s ease;
      border: 2px solid rgba(107, 175, 146, 0.2);
    }

    /* Logo and Header */
    .register-header {
      text-align: center;
      margin-bottom: 35px;
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

    .register-header h1 {
      font-size: 32px;
      color: #4B8B6E;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .register-header p {
      font-size: 15px;
      color: #6BAF92;
      font-weight: 500;
    }

    /* Form Styles */
    .register-form {
      margin-bottom: 25px;
    }

    .input-group {
      position: relative;
      margin-bottom: 20px;
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

    .register-form input {
      width: 100%;
      padding: 16px 20px 16px 55px;
      border: 2px solid #E5E5E5;
      border-radius: 15px;
      font-size: 15px;
      font-family: "Poppins", sans-serif;
      transition: all 0.3s ease;
      background: #F9F9F6;
      color: #4A4A4A;
    }

    .register-form input:focus {
      outline: none;
      border-color: #6BAF92;
      background: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(107, 175, 146, 0.1);
    }

    .register-form input::placeholder {
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

    /* Password Strength Indicator */
    .password-strength {
      height: 4px;
      border-radius: 2px;
      margin-top: 8px;
      transition: all 0.3s;
      background: #E5E5E5;
    }

    .password-strength.weak {
      width: 33%;
      background: #E74C3C;
    }

    .password-strength.medium {
      width: 66%;
      background: #F39C12;
    }

    .password-strength.strong {
      width: 100%;
      background: #27AE60;
    }

    .strength-text {
      font-size: 12px;
      margin-top: 4px;
      color: #6BAF92;
      font-weight: 500;
    }

    /* Register Button */
    .register-btn {
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
      margin-top: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-family: "Poppins", sans-serif;
      z-index: 1;
    }

    .register-btn i {
      font-size: 18px;
    }

    .register-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
      z-index: -1;
    }

    .register-btn:hover::before {
      left: 100%;
    }

    .register-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(75, 139, 110, 0.4);
    }

    .register-btn:active {
      transform: translateY(0);
    }

    .register-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
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
    .login-link {
      text-align: center;
      margin-bottom: 20px;
    }

    .login-link p {
      color: #4A4A4A;
      font-size: 15px;
    }

    .login-link a {
      color: #6BAF92;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .login-link a:hover {
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

    /* Benefits List */
    .benefits-list {
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #E5E5E5;
    }

    .benefit-item {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
      font-size: 14px;
      color: #4A4A4A;
    }

    .benefit-item i {
      color: #E8C547;
      font-size: 16px;
      min-width: 20px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .navbar {
        padding: 15px 20px;
      }

      .navbar-links {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 100%;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        flex-direction: column;
        padding: 20px;
        gap: 15px;
        box-shadow: 0 10px 30px rgba(75, 139, 110, 0.2);
        transition: left 0.3s ease;
        border-bottom: 2px solid rgba(107, 175, 146, 0.2);
      }

      .navbar-links.active {
        left: 0;
      }

      .mobile-menu-toggle {
        display: flex;
      }

      .mobile-menu-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
      }

      .mobile-menu-toggle.active span:nth-child(2) {
        opacity: 0;
      }

      .mobile-menu-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(8px, -8px);
      }

      .register-container {
        padding: 40px 30px;
        margin: 20px;
      }

      .register-header h1 {
        font-size: 26px;
      }

      .logo-wrapper img {
        width: 60px;
        height: 60px;
      }
    }
  </style>
  <script>
    function validateForm() {
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const confirm = document.getElementById('confirm').value;
      
      // Email validation
      const gmailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
      if (!gmailPattern.test(email)) {
        alert('Please enter a valid @gmail.com email address!');
        return false;
      }
      
      // Password length validation
      if (password.length < 8) {
        alert('Password must be at least 8 characters long!');
        return false;
      }
      
      // Confirm password match
      if (password !== confirm) {
        alert('Passwords do not match!');
        return false;
      }
      
      return true;
    }

    // Password strength checker
    function checkPasswordStrength(password) {
      const strengthBar = document.getElementById('strengthBar');
      const strengthText = document.getElementById('strengthText');
      
      if (password.length === 0) {
        strengthBar.className = 'password-strength';
        strengthText.textContent = '';
        return;
      }
      
      let strength = 0;
      if (password.length >= 8) strength++;
      if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;
      
      if (strength <= 1) {
        strengthBar.className = 'password-strength weak';
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#E74C3C';
      } else if (strength === 2 || strength === 3) {
        strengthBar.className = 'password-strength medium';
        strengthText.textContent = 'Medium strength';
        strengthText.style.color = '#F39C12';
      } else {
        strengthBar.className = 'password-strength strong';
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#27AE60';
      }
    }

    // Password toggle functionality
    window.onload = function() {
      const togglePassword = document.getElementById('togglePassword');
      const password = document.getElementById('password');
      const toggleConfirm = document.getElementById('toggleConfirm');
      const confirm = document.getElementById('confirm');

      if (togglePassword) {
        togglePassword.addEventListener('click', function() {
          const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
          password.setAttribute('type', type);
          this.classList.toggle('fa-eye');
          this.classList.toggle('fa-eye-slash');
        });
      }

      if (toggleConfirm) {
        toggleConfirm.addEventListener('click', function() {
          const type = confirm.getAttribute('type') === 'password' ? 'text' : 'password';
          confirm.setAttribute('type', type);
          this.classList.toggle('fa-eye');
          this.classList.toggle('fa-eye-slash');
        });
      }

      // Password strength checker
      password.addEventListener('input', function() {
        checkPasswordStrength(this.value);
      });

      // Mobile menu toggle
      const mobileMenuToggle = document.getElementById('mobileMenuToggle');
      const navbarLinks = document.getElementById('navbarLinks');

      if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
          this.classList.toggle('active');
          navbarLinks.classList.toggle('active');
        });

        // Close menu when clicking on a link
        const navLinks = navbarLinks.querySelectorAll('a');
        navLinks.forEach(link => {
          link.addEventListener('click', function() {
            mobileMenuToggle.classList.remove('active');
            navbarLinks.classList.remove('active');
          });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
          if (!mobileMenuToggle.contains(event.target) && !navbarLinks.contains(event.target)) {
            mobileMenuToggle.classList.remove('active');
            navbarLinks.classList.remove('active');
          }
        });
      }
    };
  </script>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar">
    <a href="Homepage.php" class="navbar-brand">
      <img src="LOGO.png" alt="SkillSync Logo">
      <span class="navbar-brand-text">SkillSync</span>
    </a>
    <div class="navbar-links" id="navbarLinks">
      <a href="Homepage.php"><i class="fas fa-home"></i> Home</a>
      <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
      <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
      <a href="login.php" class="btn-nav"><i class="fas fa-sign-in-alt"></i> Login</a>
    </div>
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </nav>

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

  <!-- Register Container -->
  <div class="register-container">
    <!-- Header -->
    <div class="register-header">
      <div class="logo-wrapper">
        <img src="LOGO.png" alt="SkillSync Logo">
      </div>
      <h1>Register to SkillSync</h1>
      <p>Start your programming journey today</p>
    </div>

    <!-- Register Form -->
    <form method="POST" action="register.php" onsubmit="return validateForm();" class="register-form">
      <div class="input-group">
        <i class="fas fa-user input-icon"></i>
        <input type="text" name="name" placeholder="Full Name" required>
      </div>

      <div class="input-group">
        <i class="fas fa-envelope input-icon"></i>
        <input type="email" id="email" name="email" placeholder="Email (must be @gmail.com)" title="Please enter a valid Gmail address" required>
      </div>

      <div class="input-group">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" id="password" name="password" placeholder="Password (minimum 8 characters)" title="Must be at least 8 characters" required>
        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
      </div>
      <div id="strengthBar" class="password-strength"></div>
      <div id="strengthText" class="strength-text"></div>

      <div class="input-group" style="margin-top: 20px;">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" id="confirm" name="confirm" placeholder="Confirm Password" required>
        <i class="fas fa-eye password-toggle" id="toggleConfirm"></i>
      </div>

      <button type="submit" class="register-btn">
        <i class="fas fa-user-plus"></i> Create Account
      </button>
    </form>

    <!-- Benefits -->
    <div class="benefits-list">
      <div class="benefit-item">
        <i class="fas fa-check-circle"></i>
        <span>Free access to personalized learning paths</span>
      </div>
      <div class="benefit-item">
        <i class="fas fa-check-circle"></i>
        <span>Track your progress in real-time</span>
      </div>
      <div class="benefit-item">
        <i class="fas fa-check-circle"></i>
        <span>Practice with hands-on coding exercises</span>
      </div>
    </div>

    <!-- Divider -->
    <div class="divider">
      <span>OR</span>
    </div>

    <!-- Login Link -->
    <div class="login-link">
      <p>Already have an account? <a href="login.php">Login</a></p>
    </div>

    <!-- Back to Homepage -->
    <a href="Homepage.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Back to Homepage
    </a>
  </div>

  <?php
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $name = $_POST['name'];
      $email = $_POST['email'];
      $password = $_POST['password'];
      $confirm = $_POST['confirm'];

      // Server-side email validation for @gmail.com
      if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
          echo "<script>alert('Email must be a valid @gmail.com address!');</script>";
      } elseif ($password !== $confirm) {
          echo "<script>alert('Passwords do not match!');</script>";
      } elseif (strlen($password) < 8) {
          echo "<script>alert('Password must be at least 8 characters long!');</script>";
      } else {
          // Connect to database
          require_once 'db_connect.php';

          // Hash password for security
          $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

          // Use prepared statement to prevent SQL injection
          $stmt = $conn->prepare("INSERT INTO `login_credentials` (username, password, email, completed_preassessment) VALUES (?, ?, ?, 0)");
          $stmt->bind_param("sss", $name, $hashedPassword, $email);

          if ($stmt->execute()) {
              echo "<script>alert('Registration successful! Redirecting to login page...'); window.location.href='login.php';</script>";
          } else {
              echo "<script>alert('Error: " . $stmt->error . "');</script>";
          }

          $stmt->close();
          $conn->close();
      }
  }
  ?>
</body>
</html>
