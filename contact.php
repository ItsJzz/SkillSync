<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Contact Us - SkillSync</title>
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

    /* Contact Wrapper */
    .contact-wrapper {
      position: relative;
      z-index: 1;
      padding: 80px 40px;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Page Title */
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

    .page-header p {
      font-size: 18px;
      color: #6BAF92;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Contact Container */
    .contact-container {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 50px;
      animation: slideIn 0.6s ease 0.2s;
      animation-fill-mode: both;
    }

    /* Contact Info Section */
    .contact-info {
      background: #FFFFFF;
      border-radius: 30px;
      padding: 50px 40px;
      border: 2px solid rgba(107, 175, 146, 0.2);
      box-shadow: 0 20px 60px rgba(75, 139, 110, 0.15);
    }

    .contact-info h2 {
      font-size: 36px;
      color: #4B8B6E;
      font-weight: 800;
      margin-bottom: 15px;
    }

    .contact-info > p {
      font-size: 16px;
      color: #6BAF92;
      margin-bottom: 40px;
      line-height: 1.6;
    }

    .info-box {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      margin-bottom: 30px;
      padding: 20px;
      background: rgba(107, 175, 146, 0.05);
      border-radius: 15px;
      transition: all 0.3s ease;
    }

    .info-box:hover {
      transform: translateX(10px);
      background: rgba(107, 175, 146, 0.1);
      box-shadow: 0 5px 15px rgba(75, 139, 110, 0.1);
    }

    .info-icon {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);
    }

    .info-icon i {
      color: #FFFFFF;
      font-size: 22px;
    }

    .info-content strong {
      display: block;
      font-size: 18px;
      color: #4B8B6E;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .info-content span {
      font-size: 15px;
      color: #4A4A4A;
    }

    /* Social Icons */
    .social-icons {
      display: flex;
      gap: 15px;
      margin-top: 40px;
      padding-top: 30px;
      border-top: 1px solid rgba(107, 175, 146, 0.2);
    }

    .social-icons a {
      width: 45px;
      height: 45px;
      background: rgba(107, 175, 146, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #6BAF92;
      font-size: 20px;
      transition: all 0.3s;
    }

    .social-icons a:hover {
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: #FFFFFF;
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);
    }

    /* Contact Form Section */
    .contact-form {
      background: #FFFFFF;
      border-radius: 30px;
      padding: 50px 40px;
      border: 2px solid rgba(107, 175, 146, 0.2);
      box-shadow: 0 20px 60px rgba(75, 139, 110, 0.15);
    }

    .contact-form h2 {
      font-size: 32px;
      color: #4B8B6E;
      font-weight: 800;
      margin-bottom: 30px;
    }

    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-icon {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      color: #6BAF92;
      font-size: 18px;
      z-index: 1;
    }

    .contact-form input,
    .contact-form textarea {
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

    .contact-form textarea {
      resize: vertical;
      min-height: 150px;
      padding-top: 18px;
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
      outline: none;
      border-color: #6BAF92;
      background: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(107, 175, 146, 0.1);
    }

    .contact-form input::placeholder,
    .contact-form textarea::placeholder {
      color: #A0A0A0;
    }

    .submit-btn {
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

    .submit-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }

    .submit-btn:hover::before {
      left: 100%;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(75, 139, 110, 0.4);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .submit-btn i {
      margin-left: 8px;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .contact-container {
        grid-template-columns: 1fr;
        gap: 40px;
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
      .contact-wrapper {
        padding: 50px 20px;
      }

      .page-header h1 {
        font-size: 32px;
      }

      .page-header p {
        font-size: 16px;
      }

      .contact-info,
      .contact-form {
        padding: 35px 25px;
      }

      .contact-info h2 {
        font-size: 28px;
      }

      .contact-form h2 {
        font-size: 26px;
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

  <!-- Contact Wrapper -->
  <div class="contact-wrapper">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Get In Touch</h1>
      <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </div>

    <!-- Contact Container -->
    <div class="contact-container">
      <!-- Contact Info -->
      <div class="contact-info">
        <h2>CONTACT</h2>
        <p>Any questions or remarks? Just write us a message!</p>
        
        <div class="info-box">
          <div class="info-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="info-content">
            <strong>Address</strong>
            <span>3007, Bustos, Bulacan</span>
          </div>
        </div>

        <div class="info-box">
          <div class="info-icon">
            <i class="fas fa-phone"></i>
          </div>
          <div class="info-content">
            <strong>Phone</strong>
            <span>09901020304</span>
          </div>
        </div>

        <div class="info-box">
          <div class="info-icon">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="info-content">
            <strong>Email</strong>
            <span>skillsync@gmail.com</span>
          </div>
        </div>

        <!-- Social Icons -->
        <div class="social-icons">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>

      <!-- Message Form -->
      <div class="contact-form">
        <h2>Send Message</h2>
        <form method="POST" action="contact.php">
          <div class="form-group">
            <i class="fas fa-user form-icon"></i>
            <input type="text" name="name" placeholder="Full Name" required>
          </div>

          <div class="form-group">
            <i class="fas fa-envelope form-icon"></i>
            <input type="email" name="email" placeholder="Email" required>
          </div>

          <div class="form-group">
            <i class="fas fa-comment-dots form-icon"></i>
            <textarea name="message" placeholder="Type your Message..." required></textarea>
          </div>

          <button type="submit" class="submit-btn">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
        </form>
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