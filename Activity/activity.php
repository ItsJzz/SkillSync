<?php
// activity.php
session_start();
require_once __DIR__ . "/../db_connect.php"; // adjust if needed

// Require login
if (!isset($_SESSION['user_id'])) {
    die("⚠️ You must be logged in to play this activity.");
}
$userId = $_SESSION['user_id'];

// Get topic_id & level
$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$level   = isset($_GET['level']) ? intval($_GET['level']) : 1;

if ($topicId <= 0 || $level <= 0) {
    die("⚠️ Invalid request.");
}

// Load JSON
$jsonFile = __DIR__ . "/activities.json";
if (!file_exists($jsonFile)) {
    die("⚠️ activities.json not found.");
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data) {
    die("⚠️ Invalid JSON format.");
}

// Find topic
if (!isset($data[$topicId])) {
    die("⚠️ Topic not found.");
}
$topicData = $data[$topicId];
$instructions = $topicData['instructions'] ?? [];

// Find the instruction for this level
$currentInstruction = null;
foreach ($instructions as $inst) {
    if (isset($inst['level']) && $inst['level'] == $level) {
        $currentInstruction = $inst;
        break;
    } elseif (!isset($inst['level']) && array_search($inst, $instructions) + 1 == $level) {
        // Old format compatibility
        $currentInstruction = $inst;
        break;
    }
}

if (!$currentInstruction) {
    die("⚠️ Activity not found for level $level.");
}

// Handle variant selection
if (isset($currentInstruction['variants'])) {
    // New format: randomly select a variant
    $variants = $currentInstruction['variants'];
    $variantIndex = rand(0, count($variants) - 1);
    $current = $variants[$variantIndex];
    $variantCount = count($variants);
} else {
    // Old format: use instruction directly
    $current = $currentInstruction;
    $variantCount = 1;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($current["title"]) ?> - SkillSync</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
  min-height: 100vh;
  color: #2c3e50;
  padding: 20px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Header Card */
.header-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
  border: 2px solid rgba(107, 175, 146, 0.2);
}

.level-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  color: white;
  padding: 8px 20px;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 700;
  margin-bottom: 15px;
}

.activity-title {
  font-size: 2rem;
  color: #4B8B6E;
  font-weight: 800;
  margin-bottom: 15px;
  line-height: 1.3;
}

.activity-description {
  color: #6BAF92;
  font-size: 1.05rem;
  line-height: 1.7;
  margin-bottom: 15px;
}

.variant-info {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, rgba(244, 215, 124, 0.2), rgba(232, 197, 71, 0.2));
  color: #E8C547;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid rgba(232, 197, 71, 0.3);
}

.hint-box {
  background: linear-gradient(135deg, rgba(244, 215, 124, 0.15), rgba(232, 197, 71, 0.15));
  border: 2px solid rgba(232, 197, 71, 0.3);
  border-left: 6px solid #E8C547;
  padding: 20px;
  border-radius: 12px;
  margin-top: 20px;
}

.hint-box i {
  color: #E8C547;
  font-size: 1.2rem;
  margin-right: 10px;
}

.hint-text {
  color: #4B8B6E;
  font-weight: 500;
  line-height: 1.6;
}

/* Timer & Action Card */
.action-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 10px 40px rgba(75, 139, 110, 0.15);
  border: 2px solid rgba(107, 175, 146, 0.2);
}

.timer-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 25px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(107, 175, 146, 0.1), rgba(75, 139, 110, 0.1));
  border-radius: 15px;
  border: 2px solid rgba(107, 175, 146, 0.2);
}

.timer-display {
  display: flex;
  align-items: center;
  gap: 15px;
}

.timer-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.8rem;
  box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
}

.timer-text {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.timer-label {
  color: #6BAF92;
  font-size: 0.9rem;
  font-weight: 600;
}

.btn {
  padding: 14px 32px;
  border-radius: 12px;
  border: none;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: 'Poppins', sans-serif;
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.btn-start {
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  color: white;
  box-shadow: 0 5px 20px rgba(75, 139, 110, 0.3);
}

.btn-start:hover {
  background: linear-gradient(135deg, #6BAF92, #4B8B6E);
  transform: translateY(-3px);
  box-shadow: 0 8px 30px rgba(75, 139, 110, 0.4);
}

.btn-submit {
  background: linear-gradient(135deg, #6BAF92, #4B8B6E);
  color: white;
  box-shadow: 0 5px 20px rgba(107, 175, 146, 0.3);
}

.btn-submit:hover {
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  transform: translateY(-3px);
  box-shadow: 0 8px 30px rgba(107, 175, 146, 0.4);
}

.btn-back {
  background: #6c757d;
  color: white;
  box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
}

.btn-back:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

/* Code Editor */
.code-section {
  margin-top: 25px;
}

.code-label {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #4B8B6E;
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 15px;
}

#codeInput {
  width: 100%;
  min-height: 350px;
  font-family: 'Courier New', Consolas, monospace;
  font-size: 14px;
  padding: 20px;
  border-radius: 12px;
  border: 2px solid rgba(107, 175, 146, 0.3);
  background: #f8f9fa;
  color: #2c3e50;
  white-space: pre;
  tab-size: 4;
  resize: vertical;
  transition: all 0.3s ease;
}

#codeInput:focus {
  outline: none;
  border-color: #4B8B6E;
  box-shadow: 0 0 0 3px rgba(75, 139, 110, 0.1);
}

#codeInput:disabled {
  background: #e9ecef;
  cursor: not-allowed;
  opacity: 0.7;
}

/* Results Section */
#scoreResult {
  margin-top: 25px;
}

.result-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 25px;
  border: 2px solid rgba(107, 175, 146, 0.2);
  margin-top: 20px;
}

.score-display {
  text-align: center;
  padding: 30px;
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  color: white;
  border-radius: 15px;
  margin-bottom: 25px;
}

.score-number {
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 10px;
}

.score-label {
  font-size: 1.1rem;
  opacity: 0.9;
}

.requirements-section h3 {
  color: #4B8B6E;
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.requirements-list {
  list-style: none;
  padding: 0;
}

.requirements-list li {
  padding: 12px 20px;
  margin-bottom: 10px;
  border-radius: 10px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.3s ease;
}

.requirements-list li i {
  font-size: 1.2rem;
}

.requirements-list li.ok {
  background: linear-gradient(135deg, rgba(75, 139, 110, 0.1), rgba(107, 175, 146, 0.1));
  border: 2px solid rgba(75, 139, 110, 0.3);
  color: #4B8B6E;
}

.requirements-list li.fail {
  background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(192, 57, 43, 0.1));
  border: 2px solid rgba(231, 76, 60, 0.3);
  color: #e74c3c;
}

.error-message {
  background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(192, 57, 43, 0.15));
  border: 2px solid rgba(231, 76, 60, 0.3);
  border-left: 6px solid #e74c3c;
  color: #e74c3c;
  padding: 20px;
  border-radius: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.post-test-banner {
  background: linear-gradient(135deg, #4B8B6E, #6BAF92);
  color: white;
  padding: 30px;
  border-radius: 15px;
  text-align: center;
  margin-top: 25px;
  box-shadow: 0 10px 40px rgba(75, 139, 110, 0.3);
}

.post-test-banner h3 {
  font-size: 1.8rem;
  margin-bottom: 15px;
  color: white;
}

.post-test-banner p {
  margin-bottom: 20px;
  font-size: 1.1rem;
  opacity: 0.95;
}

.post-test-btn {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  padding: 12px 30px;
  border-radius: 25px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  font-weight: 700;
  transition: all 0.3s ease;
}

.post-test-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.6);
  transform: translateY(-2px);
}

.back-button-container {
  text-align: center;
  margin-top: 30px;
}

/* Responsive */
@media (max-width: 768px) {
  .activity-title {
    font-size: 1.5rem;
  }
  
  .timer-section {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  
  .timer-text {
    font-size: 2rem;
  }
  
  #codeInput {
    min-height: 250px;
    font-size: 12px;
  }
}
</style>
</head>
<body>
<div class="container">
  <!-- Header Card -->
  <div class="header-card">
    <span class="level-badge">
      <i class="fas fa-layer-group"></i>
      Level <?= $level ?>
    </span>
    
    <h1 class="activity-title"><?= htmlspecialchars($current["title"]) ?></h1>
    
    <p class="activity-description">
      <?= htmlspecialchars($current["description"] ?? "") ?>
    </p>
    
    <?php if ($variantCount > 1): ?>
      <span class="variant-info">
        <i class="fas fa-dice"></i>
        Variant <?= $variantIndex + 1 ?> of <?= $variantCount ?>
      </span>
    <?php endif; ?>
    
    <?php if (!empty($current["hint"])): ?>
      <div class="hint-box">
        <div style="display: flex; align-items: flex-start; gap: 10px;">
          <i class="fas fa-lightbulb"></i>
          <div>
            <strong style="color: #E8C547; display: block; margin-bottom: 8px;">Hint:</strong>
            <span class="hint-text"><?= htmlspecialchars($current["hint"]) ?></span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Timer & Action Card -->
  <div class="action-card">
    <div class="timer-section">
      <div class="timer-display">
        <div class="timer-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div>
          <div id="timer" class="timer-text">5:00</div>
          <div class="timer-label">Time Remaining</div>
        </div>
      </div>
      <button id="btnStart" class="btn btn-start">
        <i class="fas fa-play"></i>
        Start Challenge
      </button>
    </div>

    <div class="code-section">
      <label class="code-label">
        <i class="fas fa-code"></i>
        Your Code:
      </label>
      <textarea id="codeInput" disabled><?= htmlspecialchars($current["skeleton"]) ?></textarea>
    </div>
    
    <div style="text-align: center; margin-top: 25px;">
      <button id="btnSubmit" class="btn btn-submit" style="display:none;">
        <i class="fas fa-check-circle"></i>
        Submit Solution
      </button>
    </div>
    
    <div id="scoreResult"></div>
  </div>

  <div class="back-button-container">
    <a href="activity_list.php?topic_id=<?= $topicId ?>">
      <button class="btn btn-back">
        <i class="fas fa-arrow-left"></i>
        Back to Activity List
      </button>
    </a>
  </div>
</div>

<script>
let countdown;
let timeLeft = 300;
let started = false;
let level = <?= $level ?>;
let topicId = <?= $topicId ?>;

const timerEl = document.getElementById("timer");
const btnStart = document.getElementById("btnStart");
const btnSubmit = document.getElementById("btnSubmit");
const scoreResult = document.getElementById("scoreResult");
const codeInput = document.getElementById("codeInput");

function updateTimer() {
  let min = Math.floor(timeLeft / 60);
  let sec = timeLeft % 60;
  timerEl.textContent = `⏳ ${min}:${sec.toString().padStart(2, '0')}`;
}

btnStart.addEventListener("click", () => {
  if (started) return;
  started = true;
  btnStart.style.display = "none";
  codeInput.disabled = false;
  btnSubmit.style.display = "inline-block";
  updateTimer();
  countdown = setInterval(() => {
    timeLeft--;
    updateTimer();
    if (timeLeft <= 0) {
      clearInterval(countdown);
      finishActivity();
    }
  }, 1000);
});

btnSubmit.addEventListener("click", finishActivity);

function finishActivity() {
  let elapsed = 300 - timeLeft;
  let score = calculateScore(elapsed);
  let code = codeInput.value;
  let reqCheck = checkRequirements(code);

  if (reqCheck.includes("✘")) {
    scoreResult.innerHTML = `
      <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
          <strong>Requirements Not Met!</strong><br>
          <span style="font-size: 0.9rem;">Please fix your code to meet all requirements before submitting.</span>
        </div>
      </div>
      ${reqCheck}
    `;
    return;
  }

  clearInterval(countdown);
  let minutes = Math.floor(elapsed / 60);
  let seconds = elapsed % 60;
  
  scoreResult.innerHTML = `
    <div class="result-card">
      <div class="score-display">
        <div class="score-number">${score}</div>
        <div class="score-label">Points Earned</div>
        <div style="margin-top: 10px; font-size: 1rem;">
          <i class="fas fa-stopwatch"></i> Completed in ${minutes}m ${seconds}s
        </div>
      </div>
      ${reqCheck}
    </div>
  `;

  // Save to DB
  fetch("save_progress.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `topic_id=${topicId}&level=${level}&score=${score}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.post_test_eligible) {
      scoreResult.innerHTML += `
        <div class="post-test-banner">
          <i class="fas fa-trophy" style="font-size: 3rem; margin-bottom: 15px;"></i>
          <h3>🎉 Congratulations!</h3>
          <p>You've completed all 5 levels! You're now eligible to take the post-test.</p>
          <a href="topic_post_test.php?topic_id=${topicId}" class="post-test-btn">
            <i class="fas fa-graduation-cap"></i>
            Take Post-Test Now
          </a>
        </div>
      `;
    }
  })
  .catch(error => console.log('Error:', error));

  setTimeout(() => {
    window.location.href = `activity_list.php?topic_id=${topicId}&done=${level}&score=${score}`;
  }, 6000);
}

function calculateScore(elapsed) {
  if (elapsed <= 30) return 100;
  if (elapsed <= 60) return 80;
  if (elapsed <= 120) return 60;
  if (elapsed <= 180) return 40;
  if (elapsed <= 300) return 20;
  return 0;
}

function checkRequirements(code) {
  let patterns = <?= json_encode($current["requirements"] ?? []) ?>;
  let resultHTML = `
    <div class="requirements-section">
      <h3>
        <i class="fas fa-clipboard-check"></i>
        Requirements Check
      </h3>
      <ul class="requirements-list">
  `;

  for (let req in patterns) {
    let pat = patterns[req];
    let regex;
    let m = pat.match(/^\/(.+)\/([a-z]*)$/i);
    if (m) {
      try { regex = new RegExp(m[1], m[2]); }
      catch (e) { regex = new RegExp(m[1]); }
    } else {
      try { regex = new RegExp(pat); }
      catch (e) { regex = new RegExp(pat.replace(/\\/g, "\\\\")); }
    }
    if (regex.test(code)) {
      resultHTML += `<li class="ok"><i class="fas fa-check-circle"></i> ${req}</li>`;
    } else {
      resultHTML += `<li class="fail"><i class="fas fa-times-circle"></i> ${req}</li>`;
    }
  }
  resultHTML += "</ul></div>";
  return resultHTML;
}
</script>
</body>
</html>
