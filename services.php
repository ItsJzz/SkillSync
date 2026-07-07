<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Our Services - SkillSync</title>
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

    /* Services Container */
    .services-wrapper {
      position: relative;
      z-index: 1;
      padding: 80px 40px;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Page Header */
    .page-header {
      text-align: center;
      margin-bottom: 60px;
      animation: slideIn 0.6s ease;
    }

    .page-header h1 {
      font-size: 48px;
      color: #4B8B6E;
      font-weight: 800;
      margin-bottom: 15px;
    }

    .page-header h1 span {
      color: #E8C547;
    }

    .page-header p {
      font-size: 18px;
      color: #6BAF92;
      max-width: 700px;
      margin: 0 auto 20px;
      line-height: 1.6;
    }

    .divider {
      width: 80px;
      height: 4px;
      background: linear-gradient(90deg, #4B8B6E, #E8C547);
      margin: 0 auto;
      border-radius: 5px;
    }

    /* Services Grid */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 35px;
      margin-top: 50px;
      animation: slideIn 0.6s ease 0.2s;
      animation-fill-mode: both;
    }

    .service-card {
      background: #FFFFFF;
      border: 2px solid rgba(107, 175, 146, 0.2);
      border-radius: 25px;
      padding: 45px 35px;
      text-align: center;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .service-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, #4B8B6E, #6BAF92);
      transform: scaleX(0);
      transition: transform 0.4s ease;
    }

    .service-card:hover::before {
      transform: scaleX(1);
    }

    .service-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 20px 50px rgba(75, 139, 110, 0.25);
      border-color: #4B8B6E;
    }

    .service-icon {
      width: 90px;
      height: 90px;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      transition: all 0.4s ease;
      box-shadow: 0 8px 20px rgba(75, 139, 110, 0.3);
    }

    .service-card:hover .service-icon {
      background: linear-gradient(135deg, #E8C547, #F4D77C);
      transform: scale(1.15) rotate(10deg);
    }

    .service-icon i {
      font-size: 40px;
      color: #FFFFFF;
    }

    .service-card h3 {
      font-size: 24px;
      color: #4B8B6E;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .service-card p {
      font-size: 16px;
      color: #4A4A4A;
      line-height: 1.7;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .services-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
      }

      .page-header h1 {
        font-size: 38px;
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
      .services-wrapper {
        padding: 50px 20px;
      }

      .page-header h1 {
        font-size: 32px;
      }

      .page-header p {
        font-size: 16px;
      }

      .services-grid {
        grid-template-columns: 1fr;
        gap: 25px;
      }

      .service-card {
        padding: 35px 25px;
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

  <!-- Services Wrapper -->
  <div class="services-wrapper">
    <!-- Page Header -->
    <div class="page-header">
      <h1>OUR <span>SERVICES</span></h1>
      <p>
        Empowering BSIT students with smart analytics for programming success.
      </p>
      <div class="divider"></div>
    </div>

    <!-- Services Grid -->
    <div class="services-grid">
      <div class="service-card">
        <div class="service-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <h3>Performance Tracking</h3>
        <p>Automatically monitor and record student progress in programming tasks and assessments.</p>
      </div>

      <div class="service-card">
        <div class="service-icon">
          <i class="fas fa-brain"></i>
        </div>
        <h3>Data-Driven Insights</h3>
        <p>Use regression analysis to predict skill trends and provide meaningful performance evaluations.</p>
      </div>

      <div class="service-card">
        <div class="service-icon">
          <i class="fas fa-lightbulb"></i>
        </div>
        <h3>Personalized Recommendations</h3>
        <p>Offer custom learning strategies based on each student's strengths and areas needing improvement.</p>
      </div>

      <div class="service-card">
        <div class="service-icon">
          <i class="fas fa-chart-pie"></i>
        </div>
        <h3>Admin Dashboard</h3>
        <p>Give instructors access to visual analytics, student rankings, and tailored feedback tools.</p>
      </div>

      <div class="service-card">
        <div class="service-icon">
          <i class="fas fa-file-alt"></i>
        </div>
        <h3>Progress Reports</h3>
        <p>Generate weekly/monthly reports on skill development, learning patterns, and milestones.</p>
      </div>

      <div class="service-card">
        <div class="service-icon">
          <i class="fas fa-users"></i>
        </div>
        <h3>Student Engagement</h3>
        <p>Help students set programming goals, earn achievements, and stay motivated through gamified stats.</p>
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