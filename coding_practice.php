<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
require_once 'db_connect.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Poppins', sans-serif; 
            display: flex; 
            background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
            color: #2c3e50; 
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
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
            overflow-y: auto;
            box-shadow: 5px 0 20px rgba(75, 139, 110, 0.1);
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
        .sidebar-content a:hover, .sidebar-content a.active { 
            background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
            color: white; 
            border-radius: 0 25px 25px 0; 
            margin-right: 10px;
            box-shadow: 0 5px 15px rgba(75, 139, 110, 0.3);
        }
        .sidebar .logo { text-align: center; margin-bottom: 25px; }
        .sidebar .logo img { width: 60px; height: 60px; border-radius: 50%; box-shadow: 0 5px 15px rgba(75, 139, 110, 0.2); }
        .sidebar .logo h2 { font-size: 1.3rem; color: #4B8B6E; margin-top: 12px; font-weight: 700; }
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
        
        /* Header with Language Selector */
        .page-header { 
            background: linear-gradient(135deg, rgba(75, 139, 110, 0.95), rgba(107, 175, 146, 0.95)); 
            backdrop-filter: blur(10px);
            color: white; 
            padding: 50px; 
            border-radius: 25px; 
            margin-bottom: 35px; 
            text-align: center;
            box-shadow: 0 15px 40px rgba(75, 139, 110, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .page-title { 
            font-size: 3rem; 
            margin-bottom: 15px; 
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }
        .page-subtitle { 
            font-size: 1.2rem; 
            opacity: 1; 
            margin-bottom: 35px;
            font-weight: 500;
        }
        
        .language-selection { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin-top: 30px; 
            flex-wrap: wrap; 
        }
        .lang-btn { 
            padding: 15px 35px; 
            background: rgba(255,255,255,0.2); 
            border: 2px solid rgba(255,255,255,0.5); 
            color: white; 
            border-radius: 30px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s; 
            backdrop-filter: blur(10px);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .lang-btn:hover { 
            transform: translateY(-3px); 
            background: rgba(255,255,255,0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        .lang-btn.active { 
            background: white; 
            color: #4B8B6E; 
            border-color: white;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        /* Progress Section */
        .progress-section { max-width: 1400px; margin: 0 auto 40px; }
        .progress-header { 
            font-size: 2rem; 
            color: #2D5A47; 
            margin-bottom: 25px; 
            text-align: center;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .difficulty-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
        }
        
        .difficulty-card { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15); 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer; 
            border: 3px solid transparent;
        }
        .difficulty-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 45px rgba(75, 139, 110, 0.25); 
        }
        .difficulty-card.selected { 
            border-color: #4B8B6E; 
            box-shadow: 0 15px 45px rgba(75, 139, 110, 0.35);
            background: rgba(107, 175, 146, 0.1);
        }
        
        .difficulty-name { 
            font-size: 1.5rem; 
            font-weight: 700; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .difficulty-name.easy { color: #4B8B6E; }
        .difficulty-name.medium { color: #E8C547; }
        .difficulty-name.hard { color: #E8C547; }
        
        .progress-bar-container { 
            background: rgba(107, 175, 146, 0.15); 
            height: 35px; 
            border-radius: 20px; 
            overflow: hidden; 
            position: relative; 
            margin-bottom: 15px;
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        .progress-bar { 
            background: linear-gradient(90deg, #4B8B6E, #6BAF92); 
            height: 100%; 
            transition: width 0.5s ease; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-weight: 700; 
            font-size: 0.95rem; 
        }
        .progress-bar.medium { background: linear-gradient(90deg, #E8C547, #F4D77C); }
        .progress-bar.hard { background: linear-gradient(90deg, #F4D77C, #E8C547); }
        .progress-text { 
            font-size: 1rem; 
            color: #4B8B6E; 
            text-align: center;
            font-weight: 600;
        }

        /* Problems List */
        .problems-section { 
            max-width: 1400px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        .problems-header { 
            font-size: 1.8rem; 
            color: #2D5A47; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 12px;
            font-weight: 700;
        }
        .problems-list { display: grid; gap: 20px; }
        
        .problem-item { 
            padding: 25px; 
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.05), rgba(75, 139, 110, 0.05)); 
            border-radius: 15px; 
            border-left: 6px solid rgba(107, 175, 146, 0.3); 
            cursor: pointer; 
            transition: all 0.3s; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border: 2px solid rgba(107, 175, 146, 0.1);
        }
        .problem-item:hover { 
            transform: translateX(8px); 
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.12), rgba(75, 139, 110, 0.12));
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.15);
            border-left-color: #4B8B6E;
        }
        .problem-item.completed { 
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.15), rgba(75, 139, 110, 0.15)); 
            border-left-color: #4B8B6E;
            border-color: rgba(75, 139, 110, 0.3);
        }
        .problem-item.completed .check-icon { color: #4B8B6E; }
        
        .problem-info h3 { 
            font-size: 1.2rem; 
            margin-bottom: 8px; 
            color: #2D5A47;
            font-weight: 700;
        }
        .problem-meta { 
            font-size: 0.95rem; 
            color: #4B8B6E;
            font-weight: 600;
        }
        .check-icon { 
            font-size: 1.8rem; 
            color: rgba(107, 175, 146, 0.3);
            transition: all 0.3s;
        }

        /* Code Editor Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .modal-content { 
            background: white; 
            width: 90%; 
            max-width: 1400px; 
            max-height: 90vh; 
            overflow-y: auto; 
            border-radius: 25px; 
            box-shadow: 0 25px 70px rgba(75, 139, 110, 0.4);
            border: 3px solid rgba(107, 175, 146, 0.3);
        }
        .modal-header { 
            background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
            color: white; 
            padding: 30px 40px; 
            border-radius: 25px 25px 0 0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .modal-title { 
            font-size: 1.8rem; 
            font-weight: 800; 
        }
        .modal-close { 
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white; 
            font-size: 1.5rem; 
            cursor: pointer;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        .modal-body { padding: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 35px; max-height: 70vh; }
        
        .problem-panel { overflow-y: auto; max-height: 600px; padding-right: 15px; }
        .problem-title { 
            font-size: 1.5rem; 
            color: #2D5A47; 
            margin-bottom: 20px; 
            font-weight: 700; 
        }
        .problem-description { 
            line-height: 1.8; 
            margin-bottom: 25px; 
            color: #2D3E50;
            font-size: 1rem;
            font-weight: 400;
        }
        .test-cases-preview { 
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.08), rgba(75, 139, 110, 0.08)); 
            padding: 20px; 
            border-radius: 15px;
            border: 2px solid rgba(107, 175, 146, 0.2);
        }
        .test-case-item { 
            padding: 15px; 
            background: white; 
            margin: 12px 0; 
            border-radius: 10px; 
            border-left: 4px solid #6BAF92;
            box-shadow: 0 3px 10px rgba(75, 139, 110, 0.1);
        }
        
        .editor-panel { display: flex; flex-direction: column; max-height: 600px; }
        .CodeMirror { 
            height: 350px !important; 
            border: 3px solid rgba(107, 175, 146, 0.3); 
            border-radius: 15px; 
            font-size: 14px;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        #testResults { max-height: 200px; overflow-y: auto; }
        
        .modal-footer { 
            padding: 25px 40px; 
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.05), rgba(75, 139, 110, 0.05)); 
            border-radius: 0 0 25px 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border-top: 2px solid rgba(107, 175, 146, 0.2);
        }
        .btn { 
            padding: 14px 35px; 
            border: none; 
            border-radius: 30px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s; 
            font-size: 1.05rem;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-success { 
            background: linear-gradient(135deg, #4B8B6E, #6BAF92); 
            color: white;
            box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
        }
        .btn-secondary { 
            background: linear-gradient(135deg, #E8C547, #F4D77C); 
            color: #4B8B6E;
            box-shadow: 0 5px 20px rgba(232, 197, 71, 0.3);
        }
        .btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.25); 
        }

        .empty-state { text-align: center; padding: 80px 20px; color: #4B8B6E; }
        .empty-state i { 
            font-size: 5rem; 
            margin-bottom: 25px; 
            opacity: 0.5;
            color: #4B8B6E;
        }
        .empty-state p {
            font-size: 1.1rem;
            font-weight: 500;
            color: #3D6B54;
        }

        @media (max-width: 1024px) {
            .modal-body { grid-template-columns: 1fr; }
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; width: calc(100% - 200px); }
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
            <img src="profile.jpg" alt="Student">
            <div><strong><?= htmlspecialchars($user['username'] ?? 'Student') ?></strong></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header with Language Selection -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-code"></i> Coding Practice</h1>
            <p class="page-subtitle">Practice coding in your favorite programming language</p>
            
            <div class="language-selection">
                <button class="lang-btn active" data-lang="javascript" onclick="selectLanguage('javascript')">
                    <i class="fab fa-js-square"></i> JavaScript
                </button>
                <button class="lang-btn" data-lang="python" onclick="selectLanguage('python')">
                    <i class="fab fa-python"></i> Python
                </button>
                <button class="lang-btn" data-lang="java" onclick="selectLanguage('java')">
                    <i class="fab fa-java"></i> Java
                </button>
                <button class="lang-btn" data-lang="cpp" onclick="selectLanguage('cpp')">
                    <i class="fas fa-plus-square"></i> C++
                </button>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="progress-section">
            <h2 class="progress-header"><i class="fas fa-chart-bar"></i> Your Progress</h2>
            <div class="difficulty-cards" id="progressCards">
                <!-- Progress cards will be loaded here -->
            </div>
        </div>

        <!-- Problems List -->
        <div class="problems-section">
            <div class="problems-header">
                <i class="fas fa-list"></i>
                <span id="problemsTitle">Problems</span>
            </div>
            <div class="problems-list" id="problemsList">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading problems...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Code Editor Modal -->
    <div class="modal" id="editorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalProblemTitle">Problem</h3>
                <button class="modal-close" onclick="closeEditor()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="problem-panel">
                    <h4 class="problem-title" id="modalProblemName"></h4>
                    <div class="problem-description" id="modalProblemDesc"></div>
                    <div class="test-cases-preview" id="modalTestCases"></div>
                </div>
                <div class="editor-panel">
                    <textarea id="codeEditor"></textarea>
                    <div id="testResults" style="margin-top: 20px;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEditor()">Close</button>
                <button class="btn btn-success" onclick="runCode()">
                    <i class="fas fa-play"></i> Run & Test Code
                </button>
            </div>
        </div>
    </div>

    <script>
        let allProblems = [];
        let currentLanguage = 'javascript';
        let currentDifficulty = 'Easy';
        let currentProblem = null;
        let completedIds = [];
        let codeEditor = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize Code Editor
            codeEditor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                lineNumbers: true,
                mode: 'javascript',
                theme: 'monokai',
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                lineWrapping: true
            });

            // Load data
            await loadProblems();
            await loadProgress();
        });

        // Load problems from JSON
        async function loadProblems() {
            try {
                const response = await fetch('coding_problems.json');
                const data = await response.json();
                allProblems = data.problems;
                displayProblems();
            } catch (error) {
                console.error('Error loading problems:', error);
            }
        }

        // Select language
        function selectLanguage(lang) {
            currentLanguage = lang;
            
            // Update UI
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[data-lang="${lang}"]`).classList.add('active');
            
            // Update editor mode
            const modes = {
                'javascript': 'javascript',
                'python': 'python',
                'java': 'text/x-java',
                'cpp': 'text/x-c++src'
            };
            codeEditor.setOption('mode', modes[lang]);
            
            // If modal is open, update the skeleton code for new language
            if (currentProblem && document.getElementById('editorModal').classList.contains('show')) {
                const langData = currentProblem.languages[currentLanguage];
                if (langData) {
                    // Clear and update with delay to ensure proper rendering
                    codeEditor.setValue('');
                    setTimeout(() => {
                        codeEditor.setValue(langData.skeleton || '');
                        codeEditor.refresh();
                        updateTestCasesDisplay();
                    }, 10);
                }
            }
            
            // Reload progress and problems
            loadProgress();
            displayProblems();
        }

        // Load progress from API
        async function loadProgress() {
            try {
                const response = await fetch(`api/get_progress.php?language=${currentLanguage}`);
                const data = await response.json();
                
                console.log('Progress loaded for', currentLanguage, ':', data); // Debug log
                
                if (data.success) {
                    completedIds = data.completed_ids || [];
                    displayProgressCards(data.progress);
                    displayProblems(); // Refresh to show completed states
                }
            } catch (error) {
                console.error('Error loading progress:', error);
            }
        }

        // Display progress cards
        function displayProgressCards(progress) {
            const container = document.getElementById('progressCards');
            const difficulties = ['Easy', 'Medium', 'Hard'];
            
            let html = '';
            difficulties.forEach(diff => {
                const prog = progress[diff] || { completed: 0, total: 0 };
                const percentage = prog.total > 0 ? (prog.completed / prog.total * 100).toFixed(0) : 0;
                const diffClass = diff.toLowerCase();
                
                html += `
                    <div class="difficulty-card ${currentDifficulty === diff ? 'selected' : ''}" onclick="selectDifficulty('${diff}')">
                        <div class="difficulty-name ${diffClass}">
                            <i class="fas ${diff === 'Easy' ? 'fa-seedling' : diff === 'Medium' ? 'fa-fire' : 'fa-skull'}"></i>
                            ${diff}
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar ${diffClass}" style="width: ${percentage}%">
                                ${percentage}%
                            </div>
                        </div>
                        <div class="progress-text">${prog.completed} / ${prog.total} completed</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Select difficulty
        function selectDifficulty(difficulty) {
            currentDifficulty = difficulty;
            displayProblems();
            loadProgress(); // Refresh to update card selection
        }

        // Display problems
        function displayProblems() {
            const filtered = allProblems.filter(p => 
                p.difficulty.toLowerCase() === currentDifficulty.toLowerCase()
            );
            
            const container = document.getElementById('problemsList');
            document.getElementById('problemsTitle').textContent = `${currentDifficulty} Problems (${currentLanguage})`;
            
            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No problems found for this difficulty</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            filtered.forEach(problem => {
                const isCompleted = completedIds.includes(problem.id);
                html += `
                    <div class="problem-item ${isCompleted ? 'completed' : ''}" onclick="openProblem(${problem.id})">
                        <div class="problem-info">
                            <h3>${problem.title}</h3>
                            <div class="problem-meta">
                                <i class="fas fa-brain"></i> ${problem.difficulty} 
                                | <i class="fas fa-code"></i> ${currentLanguage}
                            </div>
                        </div>
                        <i class="fas ${isCompleted ? 'fa-check-circle' : 'fa-circle'} check-icon"></i>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Update test cases display
        function updateTestCasesDisplay() {
            if (!currentProblem) return;
            const langData = currentProblem.languages[currentLanguage];
            if (!langData) return;
            
            let testCasesHtml = '<h4 style="margin-bottom: 10px; color: #2D5A47; font-weight: 700;">Test Cases:</h4>';
            langData.testCases.forEach((tc, idx) => {
                testCasesHtml += `
                    <div class="test-case-item">
                        <strong style="color: #2D5A47;">Test ${idx + 1}:</strong> ${tc.description}<br>
                        <small style="color: #4B8B6E; font-weight: 500;">Input: <code>${tc.input}</code> → Expected Output: <code>${tc.expected}</code></small>
                    </div>
                `;
            });
            document.getElementById('modalTestCases').innerHTML = testCasesHtml;
        }

        // Open problem in editor
        function openProblem(problemId) {
            currentProblem = allProblems.find(p => p.id === problemId);
            if (!currentProblem) return;
            
            const langData = currentProblem.languages[currentLanguage];
            if (!langData) {
                alert('This problem is not available in ' + currentLanguage);
                return;
            }
            
            // Set modal content
            document.getElementById('modalProblemTitle').textContent = currentProblem.title;
            document.getElementById('modalProblemName').textContent = currentProblem.title;
            document.getElementById('modalProblemDesc').innerHTML = `
                <p>${currentProblem.description}</p>
                ${currentProblem.hint ? `<p><strong>💡 Hint:</strong> ${currentProblem.hint}</p>` : ''}
            `;
            
            // Show test cases
            updateTestCasesDisplay();
            
            // Clear previous test results
            document.getElementById('testResults').innerHTML = '';
            
            // Set code skeleton - IMPORTANT: Clear first then set
            codeEditor.setValue(''); // Clear old content
            setTimeout(() => {
                codeEditor.setValue(langData.skeleton || '');
                codeEditor.refresh(); // Force refresh to fix black screen
            }, 10);
            
            // Show modal
            document.getElementById('editorModal').classList.add('show');
            
            // Refresh editor after modal is visible
            setTimeout(() => {
                codeEditor.refresh();
            }, 100);
        }

        // Close editor
        function closeEditor() {
            document.getElementById('editorModal').classList.remove('show');
            currentProblem = null;
            // Refresh the problems list to show updated checkmarks
            displayProblems();
        }

        // Run and test code
        async function runCode() {
            if (!currentProblem) return;
            
            const code = codeEditor.getValue().trim();
            if (!code) {
                alert('Please write some code first!');
                return;
            }
            
            const langData = currentProblem.languages[currentLanguage];
            const testCases = langData.testCases || [];
            
            // Basic validation: check if code has return statement
            const hasReturn = code.includes('return');
            
            if (!hasReturn) {
                displayTestResults({
                    success: false,
                    message: '❌ Your code needs a return statement!',
                    passed: 0,
                    total: testCases.length,
                    details: []
                });
                return;
            }
            
            // Run actual test cases
            const testResults = runTestCases(code, langData, testCases);
            
            if (testResults.allPassed) {
                // Auto-mark as complete
                await markAsComplete();
                displayTestResults({
                    success: true,
                    message: '🎉 All tests passed! Problem marked as complete!',
                    passed: testResults.passed,
                    total: testResults.total,
                    details: testResults.details
                });
            } else {
                displayTestResults({
                    success: false,
                    message: '❌ Some tests failed. Keep trying!',
                    passed: testResults.passed,
                    total: testResults.total,
                    details: testResults.details
                });
            }
        }
        
        // Run test cases against user code
        function runTestCases(code, langData, testCases) {
            const results = {
                allPassed: true,
                passed: 0,
                total: testCases.length,
                details: []
            };
            
            testCases.forEach((testCase, index) => {
                try {
                    let actualOutput;
                    let passed = false;
                    
                    // Execute code based on language
                    if (currentLanguage === 'javascript') {
                        actualOutput = executeJavaScript(code, testCase);
                    } else if (currentLanguage === 'python') {
                        actualOutput = executePython(code, testCase);
                    } else if (currentLanguage === 'java') {
                        actualOutput = executeJava(code, testCase);
                    } else if (currentLanguage === 'cpp') {
                        actualOutput = executeCpp(code, testCase);
                    }
                    
                    // Compare output with expected
                    passed = compareResults(actualOutput, testCase.expected);
                    
                    if (passed) {
                        results.passed++;
                    } else {
                        results.allPassed = false;
                    }
                    
                    results.details.push({
                        testNumber: index + 1,
                        description: testCase.description,
                        input: testCase.input,
                        expected: testCase.expected,
                        actual: actualOutput,
                        passed: passed
                    });
                    
                } catch (error) {
                    results.allPassed = false;
                    results.details.push({
                        testNumber: index + 1,
                        description: testCase.description,
                        input: testCase.input,
                        expected: testCase.expected,
                        actual: 'Error: ' + error.message,
                        passed: false
                    });
                }
            });
            
            return results;
        }
        
        // Execute JavaScript code
        function executeJavaScript(code, testCase) {
            try {
                // Define the user's function in the current scope
                eval(code);
                
                // Execute the test case input (which is a complete function call like "sum(5, 3)")
                const result = eval(testCase.input);
                
                return result;
            } catch (error) {
                throw new Error(error.message);
            }
        }
        
        // Execute Python code (simulated - in reality you'd need a backend)
        function executePython(code, testCase) {
            // For Python, we'll need to send to backend
            // For now, return a simulated result
            throw new Error('Python execution requires backend support');
        }
        
        // Execute Java code (simulated - in reality you'd need a backend)
        function executeJava(code, testCase) {
            // For Java, we'll need to send to backend
            throw new Error('Java execution requires backend support');
        }
        
        // Execute C++ code (simulated - in reality you'd need a backend)
        function executeCpp(code, testCase) {
            // For C++, we'll need to send to backend
            throw new Error('C++ execution requires backend support');
        }
        
        // Compare actual result with expected result
        function compareResults(actual, expected) {
            // Convert expected to appropriate type (it's stored as string in JSON)
            let expectedValue = expected;
            
            // Try to parse expected as JSON if it's a string
            if (typeof expected === 'string') {
                try {
                    expectedValue = JSON.parse(expected);
                } catch (e) {
                    // Keep as string if not valid JSON
                    expectedValue = expected;
                }
            }
            
            // Handle different types of comparisons
            if (Array.isArray(expectedValue)) {
                if (!Array.isArray(actual)) return false;
                if (actual.length !== expectedValue.length) return false;
                
                // For arrays, compare elements (order may matter or not depending on problem)
                return JSON.stringify(actual.sort()) === JSON.stringify(expectedValue.sort());
            }
            
            if (typeof expectedValue === 'object' && expectedValue !== null) {
                return JSON.stringify(actual) === JSON.stringify(expectedValue);
            }
            
            // For primitive types, convert actual to string for comparison if expected is string
            if (typeof expected === 'string' && typeof expectedValue === 'string') {
                return String(actual) === expectedValue;
            }
            
            // Direct comparison
            return actual == expectedValue; // Use == for type coercion (e.g., "8" == 8)
        }
        
        // Display test results
        function displayTestResults(result) {
            const resultsDiv = document.getElementById('testResults');
            const passedAll = result.success && result.passed === result.total;
            
            let detailsHtml = '';
            if (result.details && result.details.length > 0) {
                detailsHtml = '<div style="margin-top: 15px;">';
                result.details.forEach(detail => {
                    const icon = detail.passed ? '✅' : '❌';
                    const bgColor = detail.passed ? '#e8f5e9' : '#ffebee';
                    const borderColor = detail.passed ? '#4caf50' : '#f44336';
                    
                    detailsHtml += `
                        <div style="padding: 10px; margin: 8px 0; border-radius: 8px; background: ${bgColor}; border-left: 4px solid ${borderColor}; font-size: 0.9rem;">
                            <strong>${icon} Test ${detail.testNumber}:</strong> ${detail.description}<br>
                            <small style="color: #666;">
                                Input: <code>${detail.input}</code><br>
                                Expected Output: <code>${detail.expected}</code><br>
                                ${!detail.passed ? `Your Output: <code>${typeof detail.actual === 'object' ? JSON.stringify(detail.actual) : detail.actual}</code>` : `Your Output: <code>${typeof detail.actual === 'object' ? JSON.stringify(detail.actual) : detail.actual}</code> ✓`}
                            </small>
                        </div>
                    `;
                });
                detailsHtml += '</div>';
            }
            
            resultsDiv.innerHTML = `
                <div style="padding: 15px; border-radius: 10px; background: ${passedAll ? '#d4edda' : '#f8d7da'}; border: 2px solid ${passedAll ? '#27ae60' : '#e74c3c'}; color: ${passedAll ? '#155724' : '#721c24'};">
                    <strong>${result.message}</strong><br>
                    <div style="margin-top: 10px; font-size: 1.1rem;">
                        Tests Passed: ${result.passed}/${result.total}
                    </div>
                </div>
                ${detailsHtml}
            `;
        }
        
        // Mark as complete (internal function)
        async function markAsComplete() {
            if (!currentProblem) return;
            
            console.log('Marking complete:', currentProblem.id, 'Language:', currentLanguage); // Debug
            
            try {
                const response = await fetch('api/mark_complete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        problem_id: currentProblem.id,
                        language: currentLanguage
                    })
                });
                
                const data = await response.json();
                
                console.log('Mark complete response:', data); // Debug
                
                if (data.success) {
                    // Immediately update the completedIds array
                    if (!completedIds.includes(currentProblem.id)) {
                        completedIds.push(currentProblem.id);
                    }
                    
                    // Refresh progress and problems list immediately
                    await loadProgress();
                    
                    // Close modal after showing success message
                    setTimeout(() => {
                        closeEditor();
                    }, 2000);
                }
            } catch (error) {
                console.error('Failed to mark as complete:', error);
            }
        }
    </script>
</body>
</html>
