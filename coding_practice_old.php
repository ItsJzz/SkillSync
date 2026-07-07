<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get user info
$userStmt = $conn->prepare("SELECT username FROM login_credentials WHERE id = ?");
$userStmt->bind_param("i", $student_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coding Practice - SkillSync</title>
    <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background-color: #f7f9fb; color: #2c3e50; }
        
        /* Sidebar Styles */
        .sidebar { width: 240px; background-color: #ffffff; border-right: 1px solid #e0e0e0; height: 100vh; padding: 20px 0; position: fixed; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar-content a { display: flex; align-items: center; gap: 10px; color: #2c3e50; padding: 12px 20px; text-decoration: none; font-weight: 500; transition: all 0.3s; }
        .sidebar-content a:hover, .sidebar-content a.active { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 0 25px 25px 0; margin-right: 10px; }
        .sidebar .logo { text-align: center; margin-bottom: 20px; }
        .sidebar .logo img { width: 50px; height: 50px; border-radius: 50%; }
        .sidebar .logo h2 { font-size: 18px; color: #27ae60; margin-top: 10px; }
        .student-info { text-align: center; padding: 20px; font-size: 14px; }
        .student-info img { width: 40px; height: 40px; border-radius: 50%; margin-bottom: 5px; }

        /* Main Content */
        .main-content { margin-left: 240px; padding: 30px; width: calc(100% - 240px); min-height: 100vh; }
        .page-header { text-align: center; margin-bottom: 30px; }
        .page-title { font-size: 2.2rem; color: #2c3e50; margin-bottom: 10px; }
        .page-subtitle { color: #6c757d; font-size: 1.1rem; margin-bottom: 20px; }

        /* Practice Container */
        .practice-container { max-width: 1400px; margin: 0 auto; }
        .practice-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 20px; margin-bottom: 30px; text-align: center; }
        .practice-stats { display: flex; justify-content: center; gap: 40px; margin-top: 20px; flex-wrap: wrap; }
        .stat { text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; display: block; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }

        /* Controls */
        .controls { display: flex; gap: 20px; margin-bottom: 30px; align-items: center; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-success { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
        .btn-warning { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.2); }

        .language-selector { padding: 8px 16px; border: 2px solid #ddd; border-radius: 20px; background: white; font-weight: 500; }

        /* Difficulty Filter */
        .difficulty-filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .difficulty-btn { 
            padding: 10px 20px; 
            border: 2px solid transparent; 
            border-radius: 20px; 
            background: white; 
            color: #6c757d; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            font-size: 14px;
        }
        .difficulty-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .difficulty-btn.active { color: white; }
        .difficulty-btn.easy { border-color: #27ae60; }
        .difficulty-btn.easy.active { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .difficulty-btn.medium { border-color: #f39c12; }
        .difficulty-btn.medium.active { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .difficulty-btn.intermediate { border-color: #3498db; }
        .difficulty-btn.intermediate.active { background: linear-gradient(135deg, #3498db, #2980b9); }
        .difficulty-btn.hard { border-color: #e74c3c; }
        .difficulty-btn.hard.active { background: linear-gradient(135deg, #e74c3c, #c0392b); }

        /* Progress Section */
        .progress-section { 
            background: white; 
            border-radius: 15px; 
            padding: 25px; 
            margin-bottom: 30px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
        }
        .progress-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .progress-card { 
            background: linear-gradient(135deg, #f8f9fa, #e9ecef); 
            border-radius: 12px; 
            padding: 20px; 
            text-align: center; 
            border: 2px solid #e9ecef; 
            transition: all 0.3s ease; 
        }
        .progress-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .progress-card.completed { border-color: #27ae60; background: linear-gradient(135deg, #d4edda, #c3e6cb); }
        .progress-number { font-size: 2rem; font-weight: bold; margin-bottom: 10px; }
        .progress-label { font-size: 0.9rem; color: #6c757d; margin-bottom: 5px; }
        .progress-difficulty { font-weight: bold; font-size: 1.1rem; }

        /* Coding Area */
        .coding-area { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        
        .problem-panel, .code-panel { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        
        .panel-header { font-size: 1.3rem; font-weight: bold; color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .problem-content { line-height: 1.6; }
        .problem-title { font-size: 1.1rem; font-weight: bold; color: #27ae60; margin-bottom: 15px; }
        .problem-description { margin-bottom: 20px; }
        .problem-examples { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .example-title { font-weight: bold; margin-bottom: 10px; }
        .code-example { background: #2c3e50; color: #fff; padding: 10px; border-radius: 4px; font-family: 'Courier New', monospace; margin: 5px 0; }

        .code-editor { border: 2px solid #e9ecef; border-radius: 8px; overflow: hidden; }
        .CodeMirror { height: 400px; font-size: 14px; }

        /* Results Panel */
        .results-panel { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .test-results { margin-top: 20px; }
        .test-case { padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #ddd; }
        .test-case.passed { background: #d4edda; border-left-color: #27ae60; }
        .test-case.failed { background: #f8d7da; border-left-color: #e74c3c; }
        .test-case-header { font-weight: bold; margin-bottom: 8px; }

        /* Leaderboard */
        .leaderboard-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .leaderboard-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .leaderboard-table th, .leaderboard-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .leaderboard-table th { background: #f8f9fa; font-weight: bold; }
        .rank-badge { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .rank-badge.gold { background: linear-gradient(135deg, #f1c40f, #f39c12); }
        .rank-badge.silver { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }
        .rank-badge.bronze { background: linear-gradient(135deg, #d35400, #e67e22); }

        /* Loading and Messages */
        .loading { text-align: center; padding: 40px; color: #6c757d; }
        .message { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Responsive */
        @media (max-width: 1024px) {
            .coding-area { grid-template-columns: 1fr; }
            .controls { justify-content: center; }
            .practice-stats { gap: 20px; }
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
                <a href="progress_history.php"><i class="fas fa-history"></i> Progress History</a>
                <a href="video_materials.php"><i class="fas fa-book-open"></i> Learning Materials</a>
                <a href="Enhancement.php"><i class="fas fa-tasks"></i> Enhancement Process</a>
                <a href="recommendations.php"><i class="fas fa-lightbulb"></i> Recommendations</a>
                <a href="coding_practice.php" class="active"><i class="fas fa-code"></i> Coding Practice</a>
                <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="Homepage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="student-info">
            <img src="student.jpg" alt="Student">
            <div><strong><?= htmlspecialchars($user['username'] ?? 'Student') ?></strong></div>
            <div>student@email.com</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="practice-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-code"></i> Coding Practice Playground</h1>
                <p class="page-subtitle">Sharpen your skills with randomized coding challenges</p>
            </div>

            <!-- Practice Stats Header -->
            <div class="practice-header">
                <h2><i class="fas fa-trophy"></i> Your Coding Stats</h2>
                <div class="practice-stats">
                    <div class="stat">
                        <span class="stat-number" id="totalSolved">0</span>
                        <span class="stat-label">Problems Solved</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number" id="bestScore">0</span>
                        <span class="stat-label">Best Score</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number" id="currentRank">-</span>
                        <span class="stat-label">Leaderboard Rank</span>
                    </div>
                </div>
            </div>

            <!-- Difficulty Filters & Progress Overview -->
            <div class="progress-section">
                <div class="panel-header">
                    <i class="fas fa-chart-line"></i> Learning Progression
                </div>
                <div class="difficulty-filters">
                    <button class="difficulty-btn easy active" onclick="setDifficulty('Easy')">
                        <i class="fas fa-seedling"></i> Easy
                    </button>
                    <button class="difficulty-btn medium" onclick="setDifficulty('Medium')">
                        <i class="fas fa-fire"></i> Medium
                    </button>
                    <button class="difficulty-btn intermediate" onclick="setDifficulty('Intermediate')">
                        <i class="fas fa-bolt"></i> Intermediate
                    </button>
                    <button class="difficulty-btn hard" onclick="setDifficulty('Hard')">
                        <i class="fas fa-skull"></i> Hard
                    </button>
                </div>
                <div class="progress-grid" id="progressGrid">
                    <!-- Progress cards will be loaded here -->
                </div>
            </div>

            <!-- Controls -->
            <div class="controls">
                <button class="btn btn-primary" onclick="getRandomProblem()">
                    <i class="fas fa-random"></i> Random Challenge
                </button>
                <button class="btn btn-primary" onclick="getProblemByDifficulty()">
                    <i class="fas fa-target"></i> <span id="difficultyBtnText">Easy</span> Challenge
                </button>
                <button class="btn btn-success" onclick="runCode()">
                    <i class="fas fa-play"></i> Run Code
                </button>
                <button class="btn btn-warning" onclick="submitSolution()">
                    <i class="fas fa-check"></i> Submit Solution
                </button>
                <select class="language-selector" id="languageSelect" onchange="changeLanguage()">
                    <option value="javascript">JavaScript</option>
                    <option value="python">Python</option>
                    <option value="java">Java</option>
                    <option value="cpp">C++</option>
                </select>
            </div>

            <!-- Coding Area -->
            <div class="coding-area">
                <!-- Problem Panel -->
                <div class="problem-panel">
                    <div class="panel-header">
                        <i class="fas fa-puzzle-piece"></i> Problem
                    </div>
                    <div class="problem-content" id="problemContent">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i> Click "Random Challenge" to start!
                        </div>
                    </div>
                </div>

                <!-- Code Panel -->
                <div class="code-panel">
                    <div class="panel-header">
                        <i class="fas fa-edit"></i> Code Editor
                    </div>
                    <div class="code-editor">
                        <textarea id="codeEditor" placeholder="// Your code here..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Results Panel -->
            <div class="results-panel">
                <div class="panel-header">
                    <i class="fas fa-terminal"></i> Test Results
                </div>
                <div id="resultsContent">
                    <p class="message info">Run your code to see test results here.</p>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="leaderboard-section">
                <div class="panel-header">
                    <i class="fas fa-trophy"></i> Leaderboard
                </div>
                <div id="leaderboardContent">
                    <div class="message info">
                        <i class="fas fa-info-circle"></i> Leaderboard will be available once you start solving problems!
                        <p style="margin-top: 10px; font-size: 0.9em;">Complete coding challenges to compete with other students.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let codeEditor;
        let currentProblem = null;
        let currentLanguage = 'javascript';
        let currentDifficulty = 'easy';
        let progressData = {};
        let allProblems = [];

        // Initialize CodeMirror editor
        document.addEventListener('DOMContentLoaded', async function() {
            codeEditor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                lineNumbers: true,
                mode: 'javascript',
                theme: 'monokai',
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 2,
                tabSize: 2,
                lineWrapping: true
            });

            // Load initial data
            await loadProblems();
            loadUserStats();
            loadProgressData();
        });

        // Load problems from JSON file
        async function loadProblems() {
            try {
                const response = await fetch('coding_problems.json');
                const data = await response.json();
                allProblems = data.problems;
                console.log('Loaded', allProblems.length, 'coding problems');
            } catch (error) {
                console.error('Error loading problems:', error);
                showMessage('error', 'Failed to load coding problems');
            }
        }

        // Set difficulty level
        function setDifficulty(difficulty) {
            currentDifficulty = difficulty;
            
            // Update UI
            document.querySelectorAll('.difficulty-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.difficulty-btn.${difficulty.toLowerCase()}`).classList.add('active');
            document.getElementById('difficultyBtnText').textContent = difficulty;
            
            // Update progress display
            loadProgressData();
        }

        // Get problem by current difficulty
        function getProblemByDifficulty() {
            const filteredProblems = allProblems.filter(p => p.difficulty.toLowerCase() === currentDifficulty.toLowerCase());
            
            if (filteredProblems.length === 0) {
                showMessage('error', `No problems found for difficulty: ${currentDifficulty}`);
                return;
            }
            
            // Get random problem from filtered list
            const randomIndex = Math.floor(Math.random() * filteredProblems.length);
            currentProblem = filteredProblems[randomIndex];
            displayProblem(currentProblem);
            loadSkeleton(currentProblem);
        }

        // Load progress data
        function loadProgressData() {
            // Count problems by difficulty
            const difficulties = ['easy'];
            const difficultyColors = {
                'easy': '#27ae60'
            };

            const progressGrid = document.getElementById('progressGrid');
            progressGrid.innerHTML = difficulties.map(diff => {
                const totalProblems = allProblems.filter(p => p.difficulty.toLowerCase() === diff.toLowerCase()).length;
                
                return `
                    <div class="progress-card" style="border-color: ${difficultyColors[diff]}">
                        <div class="progress-number" style="color: ${difficultyColors[diff]}">0/${totalProblems}</div>
                        <div class="progress-label">Problems Available</div>
                        <div class="progress-difficulty" style="color: ${difficultyColors[diff]}">${diff.charAt(0).toUpperCase() + diff.slice(1)}</div>
                        <div style="margin-top: 10px; font-size: 0.9rem;">
                            <div>Start practicing to track progress!</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Get random coding problem
        function getRandomProblem() {
            if (allProblems.length === 0) {
                showMessage('error', 'No problems loaded yet. Please wait...');
                return;
            }
            
            // Get random problem from all problems
            const randomIndex = Math.floor(Math.random() * allProblems.length);
            currentProblem = allProblems[randomIndex];
            displayProblem(currentProblem);
            loadSkeleton(currentProblem);
        }

        // Display problem in the problem panel
        function displayProblem(problem) {
            const langData = problem.languages[currentLanguage];
            const testCases = langData.testCases || [];
            
            const content = `
                <div class="problem-title">${problem.title}</div>
                <div class="problem-description">${problem.description}</div>
                <div class="problem-examples">
                    <div class="example-title">Test Cases:</div>
                    ${testCases.slice(0, 2).map(tc => `
                        <div class="code-example">Input: ${tc.input}</div>
                        <div class="code-example">Expected: ${tc.expected}</div>
                        <div style="margin-top: 5px; opacity: 0.8; margin-bottom: 10px;">${tc.description}</div>
                    `).join('')}
                </div>
                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                    <strong><i class="fas fa-lightbulb"></i> Hint:</strong> ${problem.hint}
                </div>
                <div style="margin-top: 15px;">
                    <strong>Difficulty:</strong> <span style="color: ${getDifficultyColor(problem.difficulty)}">${problem.difficulty}</span>
                    <span style="margin-left: 15px;"><strong>Language:</strong> ${currentLanguage.toUpperCase()}</span>
                </div>
            `;
            document.getElementById('problemContent').innerHTML = content;
        }

        // Load code skeleton
        function loadSkeleton(problem) {
            const langData = problem.languages[currentLanguage];
            const skeleton = langData.skeleton || '// Start coding here...';
            codeEditor.setValue(skeleton);
        }

        // Change programming language
        function changeLanguage() {
            const select = document.getElementById('languageSelect');
            currentLanguage = select.value;
            
            const modes = {
                'javascript': 'javascript',
                'python': 'python',
                'java': 'text/x-java',
                'cpp': 'text/x-c++src'
            };
            
            codeEditor.setOption('mode', modes[currentLanguage]);
            
            // Reload skeleton for new language if problem is loaded
            if (currentProblem) {
                loadSkeleton(currentProblem);
                displayProblem(currentProblem);
            }
        }

        // Run code (client-side simulation for now)
        function runCode() {
            if (!currentProblem) {
                showMessage('error', 'Please select a problem first!');
                return;
            }

            const code = codeEditor.getValue();
            if (!code.trim()) {
                showMessage('error', 'Please write some code first!');
                return;
            }

            showMessage('info', 'Code execution is simulated. Submit your solution to test against all test cases!');
            
            // Show test cases
            const langData = currentProblem.languages[currentLanguage];
            const testCases = langData.testCases || [];
            
            const html = `
                <div class="message info">
                    <strong>Test Cases Preview</strong>
                    <p>Submit your solution to run actual tests!</p>
                </div>
                ${testCases.map((tc, index) => `
                    <div class="test-case">
                        <div class="test-case-header">
                            <i class="fas fa-flask"></i> Test Case ${index + 1}
                        </div>
                        <div>Input: ${tc.input}</div>
                        <div>Expected Output: ${tc.expected}</div>
                        <div style="opacity: 0.7;">${tc.description}</div>
                    </div>
                `).join('')}
            `;
            
            document.getElementById('resultsContent').innerHTML = html;
        }

        // Submit solution for scoring
        function submitSolution() {
            if (!currentProblem) {
                showMessage('error', 'Please select a problem first!');
                return;
            }

            const code = codeEditor.getValue();
            if (!code.trim()) {
                showMessage('error', 'Please write some code first!');
                return;
            }

            // For now, show a success message
            // In a real implementation, you would send this to a backend for execution
            showMessage('success', 'Solution submitted! In the full version, this would execute your code against test cases.');
            
            const langData = currentProblem.languages[currentLanguage];
            const testCases = langData.testCases || [];
            
            // Simulate results
            const html = `
                <div class="message success">
                    <strong>Submission Received!</strong>
                    <p>Your code has been saved. Full execution will be available in the complete version.</p>
                </div>
                ${testCases.map((tc, index) => `
                    <div class="test-case passed">
                        <div class="test-case-header">
                            <i class="fas fa-check"></i> Test Case ${index + 1}: Pending Execution
                        </div>
                        <div>Input: ${tc.input}</div>
                        <div>Expected: ${tc.expected}</div>
                    </div>
                `).join('')}
            `;
            
            document.getElementById('resultsContent').innerHTML = html;
        }

        // Display test results
        function displayResults(data) {
            let html = '';
            
            if (data.results && data.results.length > 0) {
                html = data.results.map((result, index) => `
                    <div class="test-case ${result.passed ? 'passed' : 'failed'}">
                        <div class="test-case-header">
                            <i class="fas ${result.passed ? 'fa-check' : 'fa-times'}"></i>
                            Test Case ${index + 1}: ${result.passed ? 'PASSED' : 'FAILED'}
                        </div>
                        <div>Input: ${result.input}</div>
                        <div>Expected: ${result.expected}</div>
                        <div>Got: ${result.actual}</div>
                        ${result.error ? `<div style="color: #e74c3c;">Error: ${result.error}</div>` : ''}
                    </div>
                `).join('');
                
                const passedCount = data.results.filter(r => r.passed).length;
                const totalCount = data.results.length;
                
                html = `
                    <div class="message ${passedCount === totalCount ? 'success' : 'error'}">
                        <strong>Results: ${passedCount}/${totalCount} test cases passed</strong>
                        ${data.score ? ` | Score: ${data.score} points` : ''}
                    </div>
                    ${html}
                `;
            } else {
                html = '<p class="message error">No test results available.</p>';
            }
            
            document.getElementById('resultsContent').innerHTML = html;
        }

        // Load user statistics
        function loadUserStats() {
            // Set default stats for now
            document.getElementById('totalSolved').textContent = '0';
            document.getElementById('bestScore').textContent = '0';
            document.getElementById('currentRank').textContent = '-';
        }

        // Helper functions
        function getDifficultyColor(difficulty) {
            const colors = {
                'easy': '#27ae60',
                'Easy': '#27ae60',
                'Medium': '#f39c12',
                'Intermediate': '#3498db',
                'Hard': '#e74c3c'
            };
            return colors[difficulty] || '#6c757d';
        }

        function showMessage(type, message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = message;
            
            // Insert at top of results panel
            const resultsContent = document.getElementById('resultsContent');
            resultsContent.insertBefore(messageDiv, resultsContent.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 5000);
        }
    </script>
</body>
</html>