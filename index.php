<?php // index.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="style.css">
  <meta charset="UTF-8">
  <title>SkillSync</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <style>
    <?php include "style.css"; ?>
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo-container">
      <img src="LOGO.png" alt="Logo">
    </div>
    <nav>
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="services.php">Services</a>
      <a href="contact.php">Contact</a>
      <a href="register.php" class="register-link"><i class="fas fa-user"></i> Register</a>
    </nav>
  </header>

  <!-- Main Content -->
  <div class="main">
    <div class="left-content">
      <div class="left-text">
        <lottie-player src="https://assets9.lottiefiles.com/packages/lf20_x62chJ.json"
          background="transparent" speed="1" style="width:200px;height:200px;" loop autoplay></lottie-player>
        <h1>Support Your Programming Potential</h1>
        <p>SkillSync helps you track your skills, grow through data, and achieve coding excellence.</p>
        <ul class="features">
          <li>Personalized Learning Insights</li>
          <li>Real-time Progress Tracking</li>
          <li>Data-Driven Skill Analysis</li>
        </ul>
        <a href="login.php"><button id="show-login-btn">Login to SkillSync</button></a>
      </div>
    </div>
    <div class="right-content">
      <img src="DESIGN1.png" alt="SkillSync Logo">
    </div>
  </div>
</body>
</html>
