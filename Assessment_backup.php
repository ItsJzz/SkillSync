<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>SkillSync - Assessment</title>
  <link rel="shortcut icon" href="LOGO.png" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      background-color: #f7f9fb;
      color: #2c3e50;
    }

    .sidebar {
      width: 240px;
      background-color: #ffffff;
      border-right: 1px solid #e0e0e0;
      height: 100vh;
      padding: 20px 0;
      position: fixed;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .sidebar-content a {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #2c3e50;
      padding: 12px 20px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
    }
    .sidebar-content a:hover,
    .sidebar-content a.active {
      background: linear-gradient(135deg, #27ae60, #2ecc71);
      color: white;
      border-radius: 0 25px 25px 0;
      margin-right: 10px;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }
    .sidebar .logo img {
      width: 50px; height: 50px; border-radius: 50%;
    }
    .sidebar .logo h2 {
      font-size: 18px; color: #27ae60; margin-top: 10px;
    }

    .student-info {
      text-align: center;
      padding: 20px;
      font-size: 14px;
    }
    .student-info img {
      width: 40px; height: 40px; border-radius: 50%; margin-bottom: 5px;
    }

    .main-content {
      margin-left: 240px;
      padding: 40px;
      width: calc(100% - 240px);
    }

    h2 { color: #27ae60; margin-bottom: 20px; }

    select, button {
      width: 100%;
      padding: 12px;
      margin-top: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    button {
      background-color: #27ae60;
      color: white;
      border: none;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }
    button:disabled {
      background-color: #a0d6a7;
      cursor: not-allowed;
    }

    /* Popup Panel */
    .panel {
      display: none;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      animation: fadeIn 0.3s ease-in-out;
    }
    .panel.active { display: block; }
    .panel-nav {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    canvas {
      max-width: 100%;
      height: 280px !important;
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
        <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
        <a href="assessment.php"><i class="fas fa-file-alt"></i> Assessment</a>
        <a href="progress.php"><i class="fas fa-chart-line"></i> Progress</a>
        <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
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
    
    <!-- Step 1: Introduction -->
    <div id="panel1" class="panel active">
      <h2>Welcome to the Assessment</h2>
      <p>
        This assessment is designed to evaluate your current skills, identify areas where you may need improvement, 
        and provide tailored recommendations such as modular lessons and video resources. 
        Your performance will be scored to track progress effectively.
      </p>
      <div class="panel-nav">
        <span></span>
        <button onclick="nextPanel(2)">Next</button>
      </div>
    </div>

    <!-- Step 2: Chart Overview -->
    <div id="panel2" class="panel">
      <h2>Overview of Skills (OOP1)</h2>
      <p>This chart shows an overview of topics under OOP1 and your current performance (sample data shown).</p>
      
      <div style="display: flex; gap: 30px; align-items: flex-start; margin-top: 20px;">
        <!-- Left: Chart -->
        <div style="flex: 1; max-width: 400px; height: 280px;">
          <canvas id="skillsChart"></canvas>
        </div>

        <!-- Right: Recommendations -->
        <div style="flex: 1;">
          <h3 style="margin-bottom: 10px; color: #27ae60;">Recommendations</h3>
          <ul style="list-style: disc; margin-left: 20px; line-height: 1.6;">
            <li>
              <strong>Encapsulation (Score: 50%)</strong> – Review video tutorials on encapsulation.
              <div style="background: #ffe5e5; color: #c0392b; padding: 4px 8px; border-radius: 4px; font-size: 13px; display: inline-block; margin-top: 4px;">
                ⚠ Needs Improvement
              </div>
            </li>
            <li>
              <strong>Polymorphism (Score: 60%)</strong> – Practice short quizzes and exercises.
              <div style="background: #ffe5e5; color: #c0392b; padding: 4px 8px; border-radius: 4px; font-size: 13px; display: inline-block; margin-top: 4px;">
                ⚠ Needs Improvement
              </div>
            </li>
            <li><strong>Classes & Objects (Score: 65%)</strong> – Do intermediate coding tasks.</li>
            <li><strong>Inheritance (Score: 70%)</strong> – Keep practicing applied projects.</li>
            <li><strong>Introduction to OOP Concepts (Score: 80%)</strong> – You’re doing well! Proceed to advanced modules.</li>
          </ul>
        </div>
      </div>

      <div class="panel-nav">
        <button onclick="prevPanel(1)">Back</button>
        <button onclick="nextPanel(3)">Next</button>
      </div>
    </div>

    <!-- Step 3: Subject Selection -->
    <div id="panel3" class="panel">
      <h2>Step 3: Choose Subject</h2>
      <select id="subjectSelect">
        <option value="" disabled selected>Select Subject</option>
        <option value="OOP1">OOP1</option>
        <option value="OOP2">OOP2</option>
        <option value="WEB1">WEB1</option>
        <option value="WEB2">WEB2</option>
      </select>

      <button id="startTestBtn" disabled>Start Pre-Test</button>

      <div class="panel-nav">
        <button onclick="prevPanel(2)">Back</button>
      </div>
    </div>

  </div>

  <script>
    const subjectSelect = document.getElementById('subjectSelect');
    const startTestBtn = document.getElementById('startTestBtn');

    subjectSelect.addEventListener('change', () => {
      startTestBtn.disabled = !subjectSelect.value;
    });

    startTestBtn.addEventListener('click', () => {
      const subject = subjectSelect.value;
      if (subject) {
        window.location.href = `pre_test.php?subject=${subject}`;
      }
    });

    function nextPanel(id) {
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      document.getElementById('panel' + id).classList.add('active');
    }
    function prevPanel(id) {
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      document.getElementById('panel' + id).classList.add('active');
    }

    // Chart.js Example Data for OOP1 Topics
    const ctx = document.getElementById('skillsChart').getContext('2d');
    const scores = [80, 65, 50, 70, 60];
    const skillsChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [
          'Introduction to OOP Concepts',
          'Classes and Objects',
          'Encapsulation',
          'Inheritance',
          'Polymorphism'
        ],
        datasets: [{
          label: 'Sample Score (%)',
          data: scores,
          backgroundColor: scores.map(score => score < 60 ? '#e74c3c' : '#27ae60')
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    });
  </script>
</body>
</html>
