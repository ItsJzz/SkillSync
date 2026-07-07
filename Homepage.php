<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>SkillSync - Elevate Your Programming Journey</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

    /* Fade-in Animation on Scroll */
    .fade-in {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.8s ease, transform 0.8s ease;
    }

    .fade-in.visible {
      opacity: 1;
      transform: translateY(0);
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
      text-shadow: none;
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
      text-shadow: none;
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

    /* Main Content */
    .main {
      position: relative;
      z-index: 1;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 60px 80px;
      gap: 60px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .left-content {
      flex: 1;
      max-width: 600px;
    }

    .left-text h1 {
      font-size: 48px;
      color: #4B8B6E;
      margin-bottom: 20px;
      line-height: 1.2;
      font-weight: 800;
      text-shadow: none;
    }

    .left-text h1 .highlight {
      color: #E8C547;
      position: relative;
      display: inline-block;
    }

    /* Typewriter Effect */
    .typewriter {
      overflow: hidden;
      border-right: 3px solid #E8C547;
      white-space: nowrap;
      animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
      display: inline-block;
    }

    @keyframes typing {
      from { width: 0; }
      to { width: 100%; }
    }

    @keyframes blink-caret {
      from, to { border-color: transparent; }
      50% { border-color: #E8C547; }
    }

    .left-text p {
      font-size: 18px;
      color: #4A4A4A;
      margin-bottom: 35px;
      line-height: 1.6;
      text-shadow: none;
    }

    .features {
      list-style: none;
      padding: 0;
      margin-bottom: 40px;
    }

    .features li {
      margin-bottom: 15px;
      font-size: 16px;
      padding-left: 35px;
      position: relative;
      color: #6BAF92;
      font-weight: 500;
      text-shadow: none;
    }

    .features li::before {
      content: "✓";
      color: #E8C547;
      position: absolute;
      left: 0;
      font-size: 20px;
      font-weight: bold;
      background: rgba(244, 215, 124, 0.15);
      width: 25px;
      height: 25px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #E8C547;
    }

    .cta-button {
      display: inline-block;
      background: linear-gradient(135deg, #4B8B6E 0%, #6BAF92 100%);
      color: #FFFFFF;
      border: none;
      padding: 18px 45px;
      font-size: 18px;
      font-weight: 700;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      box-shadow: 0 8px 25px rgba(75, 139, 110, 0.3);
      position: relative;
      overflow: hidden;
    }

    .cta-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }

    .cta-button:hover::before {
      left: 100%;
    }

    .cta-button::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .cta-button:active::after {
      width: 300px;
      height: 300px;
    }

    .cta-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(75, 139, 110, 0.5);
    }

    .cta-button i {
      margin-left: 8px;
      transition: transform 0.3s;
    }

    .cta-button:hover i {
      transform: translateX(5px);
    }

    .read-more-btn {
      display: inline-block;
      margin-left: 20px;
      color: #4B8B6E;
      text-decoration: none;
      font-weight: 600;
      font-size: 16px;
      padding: 10px 20px;
      border: 2px solid rgba(75, 139, 110, 0.5);
      border-radius: 30px;
      transition: all 0.3s;
    }

    .read-more-btn:hover {
      background: rgba(75, 139, 110, 0.1);
      border-color: #4B8B6E;
    }

    /* Right Content - Illustration */
    .right-content {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .illustration-container {
      position: relative;
      width: 100%;
      max-width: 600px;
      animation: float 6s ease-in-out infinite;
    }

    .illustration-container img {
      width: 100%;
      height: auto;
      filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));
    }

    /* Isometric Elements Styling */
    .isometric-elements {
      position: relative;
      width: 500px;
      height: 500px;
    }

    .iso-element {
      position: absolute;
      background: #FFFFFF;
      backdrop-filter: blur(10px);
      border: 2px solid #6BAF92;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 8px 32px rgba(75, 139, 110, 0.15);
    }

    .iso-element i {
      font-size: 40px;
      color: #F4D77C;
    }

    .iso-element:nth-child(1) {
      top: 50px;
      left: 50px;
      animation: float 3s ease-in-out infinite;
    }

    .iso-element:nth-child(2) {
      top: 150px;
      right: 50px;
      animation: float 4s ease-in-out infinite 0.5s;
    }

    .iso-element:nth-child(3) {
      bottom: 100px;
      left: 100px;
      animation: float 3.5s ease-in-out infinite 1s;
    }

    /* Stats Cards */
    .stats-container {
      display: flex;
      gap: 20px;
      margin-top: 40px;
    }

    .stat-card {
      background: #FFFFFF;
      backdrop-filter: blur(10px);
      border: 2px solid #6BAF92;
      border-radius: 15px;
      padding: 20px;
      flex: 1;
      text-align: center;
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      background: rgba(244, 215, 124, 0.1);
    }

    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: #4B8B6E;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 14px;
      color: #4A4A4A;
      font-weight: 500;
    }

    /* Features Section */
    .features-section {
      position: relative;
      z-index: 1;
      padding: 80px 80px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .section-title {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-title h2 {
      font-size: 42px;
      color: #4B8B6E;
      font-weight: 800;
      margin-bottom: 15px;
    }

    .section-title p {
      font-size: 18px;
      color: #4A4A4A;
      max-width: 600px;
      margin: 0 auto;
    }

    .feature-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      margin-top: 40px;
    }

    .feature-card {
      background: #FFFFFF;
      border: 2px solid #6BAF92;
      border-radius: 20px;
      padding: 40px 30px;
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #4B8B6E, #E8C547);
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }

    .feature-card:hover::before {
      transform: scaleX(1);
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(75, 139, 110, 0.2);
      border-color: #4B8B6E;
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      transition: all 0.3s ease;
    }

    .feature-card:hover .feature-icon {
      background: linear-gradient(135deg, #E8C547, #F4D77C);
      transform: scale(1.1) rotate(5deg);
    }

    .feature-icon i {
      font-size: 36px;
      color: #FFFFFF;
    }

    .feature-card h3 {
      font-size: 24px;
      color: #4B8B6E;
      margin-bottom: 15px;
      font-weight: 700;
    }

    .feature-card p {
      font-size: 16px;
      color: #4A4A4A;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    .feature-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #6BAF92;
      font-weight: 600;
      text-decoration: none;
      font-size: 15px;
      transition: all 0.3s;
    }

    .feature-link:hover {
      color: #E8C547;
      gap: 12px;
    }

    .feature-link i {
      transition: transform 0.3s;
    }

    .feature-link:hover i {
      transform: translateX(5px);
    }

    /* How It Works Section */
    .how-it-works {
      position: relative;
      z-index: 1;
      padding: 80px 80px;
      max-width: 1400px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.5);
      backdrop-filter: blur(10px);
      border-radius: 30px;
    }

    .steps-container {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      margin-top: 50px;
      position: relative;
    }

    .steps-container::before {
      content: '';
      position: absolute;
      top: 50px;
      left: 12%;
      right: 12%;
      height: 2px;
      background: linear-gradient(90deg, #4B8B6E, #6BAF92, #E8C547);
      z-index: -1;
    }

    .step-card {
      text-align: center;
      padding: 30px 20px;
      background: #FFFFFF;
      border-radius: 20px;
      border: 2px solid #6BAF92;
      transition: all 0.3s ease;
      position: relative;
    }

    .step-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(75, 139, 110, 0.2);
    }

    .step-number {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #4B8B6E, #6BAF92);
      color: #FFFFFF;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      font-weight: 800;
      margin: 0 auto 20px;
      box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);
    }

    .step-card h3 {
      font-size: 20px;
      color: #4B8B6E;
      margin-bottom: 12px;
      font-weight: 700;
    }

    .step-card p {
      font-size: 15px;
      color: #4A4A4A;
      line-height: 1.5;
    }



    /* CTA Banner */
    .cta-banner {
      position: relative;
      z-index: 1;
      background: linear-gradient(135deg, #4B8B6E 0%, #6BAF92 100%);
      padding: 80px 80px;
      margin: 80px auto;
      max-width: 1400px;
      border-radius: 30px;
      text-align: center;
      box-shadow: 0 20px 60px rgba(75, 139, 110, 0.3);
    }

    .cta-banner h2 {
      font-size: 42px;
      color: #FFFFFF;
      font-weight: 800;
      margin-bottom: 20px;
    }

    .cta-banner p {
      font-size: 20px;
      color: rgba(255, 255, 255, 0.95);
      margin-bottom: 35px;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }

    .cta-banner .cta-button {
      background: #FFFFFF;
      color: #4B8B6E;
      font-size: 20px;
      padding: 20px 50px;
    }

    .cta-banner .cta-button:hover {
      background: #E8C547;
      color: #FFFFFF;
    }

    /* Footer */
    footer {
      background: #4B8B6E;
      color: #FFFFFF;
      padding: 60px 80px 30px;
      position: relative;
      z-index: 1;
    }

    .footer-content {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1.5fr;
      gap: 50px;
      max-width: 1400px;
      margin: 0 auto 40px;
    }

    .footer-section h3 {
      font-size: 22px;
      margin-bottom: 20px;
      color: #E8C547;
      font-weight: 700;
    }

    .footer-section p {
      font-size: 15px;
      line-height: 1.8;
      color: rgba(255, 255, 255, 0.9);
      margin-bottom: 20px;
    }

    .footer-links {
      list-style: none;
      padding: 0;
    }

    .footer-links li {
      margin-bottom: 12px;
    }

    .footer-links a {
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
      font-size: 15px;
      transition: all 0.3s;
      display: inline-block;
    }

    .footer-links a:hover {
      color: #E8C547;
      transform: translateX(5px);
    }

    .social-icons {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }

    .social-icons a {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-size: 18px;
      transition: all 0.3s;
    }

    .social-icons a:hover {
      background: #E8C547;
      transform: translateY(-3px);
    }

    .newsletter-form {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .newsletter-form input {
      flex: 1;
      padding: 12px 20px;
      border: none;
      border-radius: 25px;
      font-size: 14px;
      outline: none;
    }

    .newsletter-form button {
      padding: 12px 25px;
      background: #E8C547;
      color: #4B8B6E;
      border: none;
      border-radius: 25px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
    }

    .newsletter-form button:hover {
      background: #F4D77C;
      transform: translateY(-2px);
    }

    .footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      padding-top: 25px;
      text-align: center;
      color: rgba(255, 255, 255, 0.8);
      font-size: 14px;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .main {
        flex-direction: column;
        padding: 40px 40px;
      }

      .left-text h1 {
        font-size: 38px;
      }

      .illustration-container {
        max-width: 400px;
      }

      .features-section {
        padding: 60px 40px;
      }

      .feature-cards {
        grid-template-columns: 1fr;
        gap: 25px;
      }

      .steps-container {
        grid-template-columns: repeat(2, 1fr);
      }

      .steps-container::before {
        display: none;
      }



      .footer-content {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 768px) {
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

      .left-text h1 {
        font-size: 32px;
      }

      .left-text p {
        font-size: 16px;
      }

      .main {
        padding: 30px 20px;
      }

      .stats-container {
        flex-direction: column;
      }

      .features-section, .how-it-works {
        padding: 40px 20px;
      }

      .section-title h2 {
        font-size: 32px;
      }

      .section-title p {
        font-size: 16px;
      }

      .steps-container {
        grid-template-columns: 1fr;
      }

      .cta-banner {
        padding: 50px 30px;
      }

      .cta-banner h2 {
        font-size: 32px;
      }

      .cta-banner p {
        font-size: 16px;
      }

      footer {
        padding: 40px 30px 20px;
      }

      .footer-content {
        grid-template-columns: 1fr;
        gap: 35px;
      }
    }
  </style>
  <script>
    // Scroll animations
    document.addEventListener('DOMContentLoaded', function() {
      // Fade-in on scroll
      const fadeElements = document.querySelectorAll('.fade-in');
      
      const fadeInObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
          }
        });
      }, { threshold: 0.1 });

      fadeElements.forEach(element => {
        fadeInObserver.observe(element);
      });

      // Header scroll effect
      const header = document.querySelector('header');
      window.addEventListener('scroll', () => {
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
      }

      // Animated counter for statistics
      const statNumbers = document.querySelectorAll('.stat-number');
      
      const countUp = (element) => {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            element.textContent = target + (element.getAttribute('data-suffix') || '');
            clearInterval(timer);
          } else {
            element.textContent = Math.floor(current) + (element.getAttribute('data-suffix') || '');
          }
        }, 16);
      };

      const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            entry.target.classList.add('counted');
            countUp(entry.target);
          }
        });
      }, { threshold: 0.5 });

      statNumbers.forEach(stat => {
        statsObserver.observe(stat);
      });
    });
  </script>
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

  <!-- Main Content -->
  <div class="main">
    <div class="left-content">
      <div class="left-text">
        <h1>Don't let your programming skills be your fate, <span class="highlight">count on us to elevate.</span></h1>
        <p>Where we ensure personalized learning paths, real-time progress tracking, and data-driven insights to help you master programming concepts and achieve your coding goals with confidence.</p>
        
        <div>
          <a href="login.php" class="cta-button">
            Get Started <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="right-content">
      <div class="illustration-container">
        <!-- You can replace this with DESIGN1.png or create isometric elements -->
        <div class="isometric-elements">
          <div class="iso-element">
            <i class="fas fa-laptop-code"></i>
          </div>
          <div class="iso-element">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="iso-element">
            <i class="fas fa-brain"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Features Section -->
  <section class="features-section fade-in">
    <div class="section-title">
      <h2>Why Choose SkillSync?</h2>
      <p>Discover how our platform helps you master programming skills with cutting-edge tools and personalized learning experiences.</p>
    </div>

    <div class="feature-cards">
      <!-- Feature Card 1 -->
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <h3>Track Your Progress</h3>
        <p>Monitor your learning journey with detailed analytics and insights. See your improvement over time with visual progress tracking.</p>
        <a href="login.php" class="feature-link">
          Learn More <i class="fas fa-arrow-right"></i>
        </a>
      </div>

      <!-- Feature Card 2 -->
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-brain"></i>
        </div>
        <h3>Personalized Learning</h3>
        <p>Get customized learning paths tailored to your skill level and goals. Our AI-driven recommendations adapt to your pace.</p>
        <a href="login.php" class="feature-link">
          Learn More <i class="fas fa-arrow-right"></i>
        </a>
      </div>

      <!-- Feature Card 3 -->
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-code"></i>
        </div>
        <h3>Practice Coding</h3>
        <p>Master programming with hands-on coding exercises and real-world projects. Build your portfolio while you learn.</p>
        <a href="login.php" class="feature-link">
          Learn More <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="how-it-works fade-in">
    <div class="section-title">
      <h2>How It Works</h2>
      <p>Get started with SkillSync in four simple steps and transform your programming journey.</p>
    </div>

    <div class="steps-container">
      <!-- Step 1 -->
      <div class="step-card">
        <div class="step-number">1</div>
        <h3>Sign Up</h3>
        <p>Create your free account and set your learning goals.</p>
      </div>

      <!-- Step 2 -->
      <div class="step-card">
        <div class="step-number">2</div>
        <h3>Take Assessment</h3>
        <p>Complete our initial assessment to determine your skill level.</p>
      </div>

      <!-- Step 3 -->
      <div class="step-card">
        <div class="step-number">3</div>
        <h3>Learn & Practice</h3>
        <p>Follow your personalized path with videos, exercises, and projects.</p>
      </div>

      <!-- Step 4 -->
      <div class="step-card">
        <div class="step-number">4</div>
        <h3>Track Progress</h3>
        <p>Monitor your growth with detailed analytics and insights.</p>
      </div>
    </div>
  </section>

  <!-- CTA Banner -->
  <section class="cta-banner fade-in">
    <h2>Ready to Start Your Programming Journey?</h2>
    <p>Join thousands of students who are mastering programming skills with SkillSync. Start learning today with personalized paths, expert guidance, and real-time progress tracking.</p>
    <a href="register.php" class="cta-button">
      Join SkillSync Today <i class="fas fa-rocket"></i>
    </a>
  </section>

  <!-- Footer -->
  <footer>
    <div class="footer-content">
      <!-- About Section -->
      <div class="footer-section">
        <h3>About SkillSync</h3>
        <p>SkillSync is your ultimate platform for mastering programming skills. We provide personalized learning paths, real-time progress tracking, and expert guidance to help you achieve your coding goals.</p>
        <div class="social-icons">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-section">
        <h3>Quick Links</h3>
        <ul class="footer-links">
          <li><a href="Homepage.php">Home</a></li>
          <li><a href="about.php">About Us</a></li>
          <li><a href="services.php">Services</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="login.php">Login</a></li>
        </ul>
      </div>

      <!-- Resources -->
      <div class="footer-section">
        <h3>Resources</h3>
        <ul class="footer-links">
          <li><a href="#">Learning Materials</a></li>
          <li><a href="#">Practice Problems</a></li>
          <li><a href="#">Video Tutorials</a></li>
          <li><a href="#">FAQs</a></li>
          <li><a href="#">Support</a></li>
        </ul>
      </div>

      <!-- Newsletter -->
      <div class="footer-section">
        <h3>Stay Updated</h3>
        <p>Subscribe to our newsletter for the latest updates, tips, and resources.</p>
        <form class="newsletter-form" onsubmit="return false;">
          <input type="email" placeholder="Your email address" required>
          <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2025 SkillSync. All rights reserved. | <a href="#" style="color: #E8C547; text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: #E8C547; text-decoration: none;">Terms of Service</a></p>
    </div>
  </footer>

</body>
</html>
