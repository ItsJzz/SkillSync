<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>About Us - SkillSync</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
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
      background: rgba(75, 139, 110, 0.1);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }

    .dot-pattern:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; }
    .dot-pattern:nth-child(2) { top: 20%; right: 8%; animation-delay: 1s; }
    .dot-pattern:nth-child(3) { top: 50%; left: 3%; animation-delay: 2s; }
    .dot-pattern:nth-child(4) { top: 70%; right: 5%; animation-delay: 1.5s; }
    .dot-pattern:nth-child(5) { top: 85%; left: 10%; animation-delay: 0.5s; }

    .circle-decoration {
      position: absolute;
      border: 2px solid rgba(75, 139, 110, 0.1);
      border-radius: 50%;
      animation: rotate 20s linear infinite;
    }

    .circle-decoration:nth-child(6) { 
      width: 150px; 
      height: 150px; 
      top: 15%; 
      right: 15%;
    }

    .circle-decoration:nth-child(7) { 
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

    /* Header Styles */
    header {
      width: 100%;
      padding: 20px 60px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #FFFFFF;
      backdrop-filter: blur(10px);
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 2px solid #4B8B6E;
      box-shadow: 0 2px 10px rgba(75, 139, 110, 0.1);
      transition: all 0.3s ease;
    }

    header.scrolled {
      padding: 15px 60px;
      box-shadow: 0 4px 20px rgba(75, 139, 110, 0.2);
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo-container img {
      width: 45px;
      height: 45px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }

    .logo-container span {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: 0.5px;
      color: #4B8B6E;
    }

    nav {
      display: flex;
      align-items: center;
      gap: 35px;
    }

    nav a {
      color: #4B8B6E;
      text-decoration: none;
      font-weight: 500;
      font-size: 15px;
      transition: all 0.3s;
      position: relative;
    }

    nav a:not(.register-link):hover {
      color: #E8C547;
      transform: translateY(-2px);
    }

    nav a:not(.register-link)::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -5px;
      left: 0;
      background: #E8C547;
      transition: width 0.3s;
    }

    nav a:not(.register-link):hover::after {
      width: 100%;
    }

    .register-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 25px;
      border: 2px solid #E8C547;
      border-radius: 30px;
      background: rgba(244, 215, 124, 0.1);
      color: #4B8B6E;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(232, 197, 71, 0.2);
    }

    .register-link:hover {
      background: #E8C547;
      color: #FFFFFF;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(232, 197, 71, 0.4);
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
      display: none;
      flex-direction: column;
      cursor: pointer;
      gap: 5px;
    }

    .menu-toggle span {
      width: 25px;
      height: 3px;
      background: #4B8B6E;
      border-radius: 3px;
      transition: all 0.3s;
    }

    .menu-toggle.active span:nth-child(1) {
      transform: rotate(45deg) translate(8px, 8px);
    }

    .menu-toggle.active span:nth-child(2) {
      opacity: 0;
    }

    .menu-toggle.active span:nth-child(3) {
      transform: rotate(-45deg) translate(7px, -7px);
    }

    /* About Section */
    .about-section {
      position: relative;
      z-index: 1;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 80px 80px;
      gap: 60px;
      max-width: 1400px;
      margin: 0 auto;
      animation: slideIn 0.6s ease;
    }

    .about-text {
      flex: 1;
      max-width: 650px;
    }

    .about-text h1 {
      font-size: 48px;
      color: #4B8B6E;
      margin-bottom: 25px;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .about-text h1 i {
      color: #E8C547;
      font-size: 42px;
    }

    .about-point {
      background: #FFFFFF;
      border: 2px solid rgba(107, 175, 146, 0.2);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 25px;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .about-point::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(180deg, #4B8B6E, #6BAF92);
      transform: scaleY(0);
      transition: transform 0.4s ease;
    }

    .about-point:hover::before {
      transform: scaleY(1);
    }

    .about-point:hover {
      transform: translateX(10px);
      box-shadow: 0 15px 40px rgba(75, 139, 110, 0.2);
      border-color: #4B8B6E;
    }

    .about-point i {
      font-size: 32px;
      color: #6BAF92;
      margin-bottom: 15px;
      display: inline-block;
      transition: all 0.3s ease;
    }

    .about-point:hover i {
      color: #E8C547;
      transform: scale(1.1) rotate(5deg);
    }

    .about-point p {
      font-size: 17px;
      color: #4A4A4A;
      line-height: 1.7;
      margin: 0;
    }

    .about-point strong {
      color: #4B8B6E;
      font-weight: 700;
    }

    .highlight-key {
      color: #E8C547;
      font-weight: 700;
      position: relative;
    }

    /* Illustration */
    .about-illustration {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .illustration-wrapper {
      position: relative;
      animation: float 6s ease-in-out infinite;
    }

    .illustration-wrapper::before {
      content: '';
      position: absolute;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(107, 175, 146, 0.15) 0%, transparent 70%);
      border-radius: 50%;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 0;
      animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
      50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.3; }
    }

    .about-illustration img {
      max-width: 100%;
      height: auto;
      position: relative;
      z-index: 1;
      filter: drop-shadow(0 10px 30px rgba(75, 139, 110, 0.3));
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .about-section {
        flex-direction: column;
        padding: 60px 40px;
        text-align: center;
      }

      .about-text {
        max-width: 100%;
      }

      .about-text h1 {
        font-size: 38px;
        justify-content: center;
      }

      .illustration-wrapper::before {
        width: 350px;
        height: 350px;
      }

      header {
        padding: 15px 30px;
      }

      .menu-toggle {
        display: flex;
      }

      nav {
        position: fixed;
        top: 0;
        right: -100%;
        width: 70%;
        height: 100vh;
        background: #FFFFFF;
        flex-direction: column;
        align-items: flex-start;
        padding: 80px 30px;
        transition: right 0.3s ease;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
      }

      nav.active {
        right: 0;
      }
    }

    @media (max-width: 768px) {
      .about-section {
        padding: 50px 20px;
      }

      .about-text h1 {
        font-size: 32px;
        flex-direction: column;
        gap: 10px;
      }

      .about-text h1 i {
        font-size: 36px;
      }

      .about-point {
        padding: 25px 20px;
      }

      .about-point p {
        font-size: 16px;
      }

      .illustration-wrapper::before {
        width: 280px;
        height: 280px;
      }

      header {
        padding: 15px 20px;
      }

      .logo-container span {
        font-size: 20px;
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
    <div class="circle-decoration"></div>
    <div class="circle-decoration"></div>
  </div>

  <!-- Header -->
  <header>
    <div class="logo-container">
      <img src="LOGO.png" alt="SkillSync Logo">
      <span>SKILLSYNC</span>
    </div>
    <div class="menu-toggle">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <nav>
      <a href="Homepage.php">HOME</a>
      <a href="about.php">ABOUT</a>
      <a href="services.php">SERVICES</a>
      <a href="contact.php">CONTACT</a>
      <a href="register.php" class="register-link">
        <i class="fas fa-user-plus"></i> Register
      </a>
    </nav>
  </header>

  <!-- About Section -->
  <div class="about-section">
    <div class="about-text">
      <h1><i class="fas fa-info-circle"></i> About Us</h1>

      <div class="about-point">
        <i class="fas fa-chart-line"></i>
        <p><strong>SkillSync</strong> is a <span class="highlight-key">data-driven platform</span> that puts analytics at the heart of skill development. Our goal is to help <span class="highlight-key">BSIT students</span> enhance and track their programming abilities through clear, actionable insights.</p>
      </div>

      <div class="about-point">
        <i class="fas fa-brain"></i>
        <p>We utilize <span class="highlight-key">regression analysis</span> to assess student performance, helping identify both strengths and areas for improvement.</p>
      </div>

      <div class="about-point">
        <i class="fas fa-bullseye"></i>
        <p><strong>SkillSync</strong> empowers learners to take control of their growth with support, structured tracking, and helps them focus on their <span class="highlight-key">goals</span> every step of the way.</p>
      </div>
    </div>

    <div class="about-illustration">
      <div class="illustration-wrapper">
        <img src="DESIGN.png" alt="SkillSync Illustration">
      </div>
    </div>
  </div>

  <script>
    // Header scroll effect
    window.addEventListener('scroll', () => {
      const header = document.querySelector('header');
      if (window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('nav');
    
    if (menuToggle) {
      menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
        nav.classList.toggle('active');
      });

      // Close menu when clicking on a link
      const navLinks = document.querySelectorAll('nav a');
      navLinks.forEach(link => {
        link.addEventListener('click', () => {
          menuToggle.classList.remove('active');
          nav.classList.remove('active');
        });
      });
    }
  </script>
</body>

</html>