<?php
session_start();
require_once 'db_connect.php';

// Support both session variables for compatibility
if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Use user_id as primary, student_id as fallback
$student_id = $_SESSION['user_id'] ?? $_SESSION['student_id'];

// Check if user has assessment data - try both id and user_id columns (using mysqli)
$stmt = $conn->prepare("SELECT assessment_data, assessment_details FROM students WHERE id = ? OR user_id = ? LIMIT 1");
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student || empty($student['assessment_data'])) {
    // For demo purposes, create sample data if no assessment exists
    $sampleData = [
        'format' => 'scalable',
        'total_score' => 89,
        'max_total_score' => 150,
        'overall_percentage' => 59.3,
        'topic_scores' => [
            13 => [
                'name' => 'Classes and Objects',
                'quiz_correct' => 4,
                'quiz_total' => 8,
                'simulation_correct' => 5,
                'simulation_total' => 8,
                'hands_on_score' => 8,
                'hands_on_max' => 10,
                'percentage' => 68.0
            ],
            14 => [
                'name' => 'Inheritance',
                'quiz_correct' => 3,
                'quiz_total' => 8,
                'simulation_correct' => 4,
                'simulation_total' => 8,
                'hands_on_score' => 6,
                'hands_on_max' => 10,
                'percentage' => 52.0
            ],
            15 => [
                'name' => 'Polymorphism',
                'quiz_correct' => 5,
                'quiz_total' => 8,
                'simulation_correct' => 6,
                'simulation_total' => 8,
                'hands_on_score' => 9,
                'hands_on_max' => 10,
                'percentage' => 80.0
            ],
            16 => [
                'name' => 'Encapsulation',
                'quiz_correct' => 2,
                'quiz_total' => 8,
                'simulation_correct' => 3,
                'simulation_total' => 8,
                'hands_on_score' => 5,
                'hands_on_max' => 10,
                'percentage' => 40.0
            ],
            17 => [
                'name' => 'Abstraction',
                'quiz_correct' => 6,
                'quiz_total' => 8,
                'simulation_correct' => 7,
                'simulation_total' => 8,
                'hands_on_score' => 10,
                'hands_on_max' => 10,
                'percentage' => 92.0
            ]
        ]
    ];
    
    // Store sample data for demo (using mysqli)
    $sampleJson = json_encode($sampleData);
    $stmt = $conn->prepare("UPDATE students SET assessment_data = ? WHERE id = ? OR user_id = ?");
    $stmt->bind_param("sii", $sampleJson, $student_id, $student_id);
    $stmt->execute();
    $stmt->close();
    
    $assessmentData = $sampleData;
    $assessmentDetails = [];
}

// Parse assessment data
$assessmentData = json_decode($student['assessment_data'], true);
$assessmentDetails = json_decode($student['assessment_details'], true);

// Check if this is new scalable format or legacy format
$isScalableFormat = isset($assessmentData['format']) && $assessmentData['format'] === 'scalable';

if ($isScalableFormat) {
    // New scalable format
    $totalScore = $assessmentData['total_score'];
    $maxTotalScore = $assessmentData['max_total_score'];
    $overallPercentage = $assessmentData['overall_percentage'];
    $topicScores = $assessmentData['topic_scores'];
    
    // Calculate class level and progress
    $classLevel = $overallPercentage >= 77 ? 'Intermediate' : 'Beginner';
    $progressToNext = $classLevel === 'Beginner' ? ($overallPercentage / 77) * 100 : 100;
    
    // Calculate performance by question type
    $totalQuizScore = 0; $totalQuizMax = 0;
    $totalSimulationScore = 0; $totalSimulationMax = 0;
    $totalHandsOnScore = 0; $totalHandsOnMax = 0;
    
    foreach ($topicScores as $data) {
        // Get the actual counts
        $quizCorrect = isset($data['quiz_correct']) ? intval($data['quiz_correct']) : 0;
        $quizTotal = isset($data['quiz_total']) ? intval($data['quiz_total']) : 8;
        $simCorrect = isset($data['simulation_correct']) ? intval($data['simulation_correct']) : 0;
        $simTotal = isset($data['simulation_total']) ? intval($data['simulation_total']) : 8;
        $handsOnScore = isset($data['hands_on_score']) ? floatval($data['hands_on_score']) : 0;
        $handsOnMax = isset($data['hands_on_max']) ? floatval($data['hands_on_max']) : 10;
        
        // Calculate points (each question type is worth half of 20 points per topic)
        $totalQuizScore += $quizCorrect;
        $totalQuizMax += $quizTotal;
        $totalSimulationScore += $simCorrect;
        $totalSimulationMax += $simTotal;
        $totalHandsOnScore += $handsOnScore;
        $totalHandsOnMax += $handsOnMax;
    }
    
    // Calculate percentages (number correct / number total * 100)
    $quizPercentage = $totalQuizMax > 0 ? ($totalQuizScore / $totalQuizMax) * 100 : 0;
    $simulationPercentage = $totalSimulationMax > 0 ? ($totalSimulationScore / $totalSimulationMax) * 100 : 0;
    $handsOnPercentage = $totalHandsOnMax > 0 ? ($totalHandsOnScore / $totalHandsOnMax) * 100 : 0;
    
    // Debug output (uncomment to see raw data)
    if (isset($_GET['debug'])) {
        echo "<pre style='background:white;padding:20px;margin:20px;border:2px solid red;'>";
        echo "=== DEBUG INFO ===\n\n";
        echo "Quiz: $totalQuizScore / $totalQuizMax = " . round($quizPercentage, 2) . "%\n";
        echo "Simulation: $totalSimulationScore / $totalSimulationMax = " . round($simulationPercentage, 2) . "%\n";
        echo "Hands-on: $totalHandsOnScore / $totalHandsOnMax = " . round($handsOnPercentage, 2) . "%\n";
        echo "\nTotal Score: $totalScore / $maxTotalScore = $overallPercentage%\n";
        echo "\nTopic Scores (" . count($topicScores) . " topics):\n";
        print_r($topicScores);
        echo "\n\nRaw Assessment Data:\n";
        print_r($assessmentData);
        echo "</pre>";
    }
    
} else {
    // Legacy format fallback
    $totalScore = 0;
    $maxTotalScore = 150;
    $overallPercentage = 0;
    $topicScores = [];
    $quizPercentage = 0;
    $simulationPercentage = 0;
    $handsOnPercentage = 0;
    $classLevel = 'Beginner';
    $progressToNext = 0;
}

// Analyze strengths and weaknesses
$strongAreas = [];
$weakAreas = [];

foreach ($topicScores as $topicId => $data) {
    $percentage = $data['percentage'];
    $analysis = [
        'area' => $data['name'],
        'score' => round($percentage),
        'description' => ''
    ];
    
    if ($percentage >= 80) {
        $analysis['description'] = "You have excellent understanding of this topic. Continue building on this strength.";
        $strongAreas[] = $analysis;
    } elseif ($percentage < 60) {
        $analysis['description'] = "This area needs attention. Focus on fundamental concepts and practice exercises.";
        $weakAreas[] = $analysis;
    }
}

// ===================================================================
// PREDICTIVE MODEL & RECOMMENDATION ENGINE
// ===================================================================

// 1. Determine if ready for next class level (75% threshold = 115/150 points)
$readyForNextLevel = $overallPercentage >= 75;
$progressToNextLevel = min(100, ($overallPercentage / 75) * 100);

// 2. Identify weak topics (below 60%)
$weakTopics = [];
$strongTopics = [];
foreach ($topicScores as $topicId => $data) {
    if ($data['percentage'] < 60) {
        $weakTopics[] = [
            'id' => $topicId,
            'name' => $data['name'],
            'percentage' => $data['percentage'],
            'quiz_correct' => $data['quiz_correct'],
            'quiz_total' => $data['quiz_total'],
            'simulation_correct' => $data['simulation_correct'],
            'simulation_total' => $data['simulation_total'],
            'hands_on_score' => $data['hands_on_score'],
            'hands_on_max' => $data['hands_on_max']
        ];
    } else {
        $strongTopics[] = $data['name'];
    }
}

// 3. Analyze performance by question type and generate recommendations
$recommendations = [];

// Quiz Questions Analysis (< 60% = low)
$quizPerformanceLevel = $quizPercentage >= 70 ? 'strong' : ($quizPercentage >= 50 ? 'moderate' : 'weak');
if ($quizPercentage < 60) {
    $recommendations[] = [
        'type' => 'quiz',
        'icon' => 'fa-book-reader',
        'title' => 'Strengthen Conceptual Understanding',
        'performance' => round($quizPercentage) . '%',
        'status' => 'needs-attention',
        'description' => 'Your quiz performance indicates gaps in theoretical knowledge.',
        'actions' => [
            'Focus on <strong>modular learning</strong> - break down concepts into smaller chunks',
            'Review learning materials and documentation for each topic',
            'Create concept maps to visualize relationships between OOP principles',
            'Take notes and summarize key concepts in your own words'
        ],
        'resources' => [
            ['icon' => 'fa-book', 'text' => 'Study Learning Materials', 'link' => 'video_materials.php'],
            ['icon' => 'fa-file-alt', 'text' => 'Read Documentation', 'link' => '#'],
            ['icon' => 'fa-pen', 'text' => 'Practice Quizzes', 'link' => 'pre_test.php']
        ]
    ];
}

// Simulation Questions Analysis (< 60% = low)
$simulationPerformanceLevel = $simulationPercentage >= 70 ? 'strong' : ($simulationPercentage >= 50 ? 'moderate' : 'weak');
if ($simulationPercentage < 60) {
    $recommendations[] = [
        'type' => 'simulation',
        'icon' => 'fa-desktop',
        'title' => 'Improve Problem-Solving Skills',
        'performance' => round($simulationPercentage) . '%',
        'status' => 'needs-attention',
        'description' => 'You need more practice applying concepts to real-world scenarios.',
        'actions' => [
            'Watch <strong>video tutorials</strong> showing step-by-step problem solving',
            'Visit the <strong>simulation playground</strong> to practice in a safe environment',
            'Analyze sample code and trace execution flow',
            'Work through guided examples before attempting independent problems'
        ],
        'resources' => [
            ['icon' => 'fa-video', 'text' => 'Watch Video Tutorials', 'link' => 'video_materials.php'],
            ['icon' => 'fa-gamepad', 'text' => 'Go to Simulation Playground', 'link' => 'Activity/simulation/'],
            ['icon' => 'fa-code', 'text' => 'Interactive Examples', 'link' => 'Activity/activity_list.php']
        ]
    ];
}

// Hands-On Activities Analysis (< 60% = low)
$handsOnPerformanceLevel = $handsOnPercentage >= 70 ? 'strong' : ($handsOnPercentage >= 50 ? 'moderate' : 'weak');
if ($handsOnPercentage < 60) {
    $recommendations[] = [
        'type' => 'hands-on',
        'icon' => 'fa-keyboard',
        'title' => 'Build Practical Coding Skills',
        'performance' => round($handsOnPercentage) . '%',
        'status' => 'needs-attention',
        'description' => 'You need more hands-on coding practice to solidify your skills.',
        'actions' => [
            'Complete the <strong>enhancement process</strong> activities for weak topics',
            'Practice in the <strong>coding practice environment</strong> daily',
            'Start with simple exercises and gradually increase complexity',
            'Review and refactor your code to learn best practices'
        ],
        'resources' => [
            ['icon' => 'fa-tools', 'text' => 'Enhancement Activities', 'link' => 'Enhancement.php'],
            ['icon' => 'fa-terminal', 'text' => 'Coding Practice', 'link' => 'coding_practice.php'],
            ['icon' => 'fa-project-diagram', 'text' => 'Hands-on Projects', 'link' => 'Activity/activity_list.php']
        ]
    ];
}

// 4. Generate topic-specific learning paths
$topicRecommendations = [];
foreach ($weakTopics as $topic) {
    $quizPerf = $topic['quiz_total'] > 0 ? ($topic['quiz_correct'] / $topic['quiz_total']) * 100 : 0;
    $simPerf = $topic['simulation_total'] > 0 ? ($topic['simulation_correct'] / $topic['simulation_total']) * 100 : 0;
    $handsOnPerf = $topic['hands_on_max'] > 0 ? ($topic['hands_on_score'] / $topic['hands_on_max']) * 100 : 0;
    
    $weakestArea = 'theory';
    $lowestScore = $quizPerf;
    if ($simPerf < $lowestScore) {
        $weakestArea = 'application';
        $lowestScore = $simPerf;
    }
    if ($handsOnPerf < $lowestScore) {
        $weakestArea = 'practice';
        $lowestScore = $handsOnPerf;
    }
    
    $learningPath = [];
    if ($weakestArea === 'theory') {
        $learningPath = [
            '1. Review ' . $topic['name'] . ' learning materials',
            '2. Watch introductory videos on core concepts',
            '3. Take practice quizzes to test understanding',
            '4. Move to simulation exercises once theory is solid'
        ];
    } elseif ($weakestArea === 'application') {
        $learningPath = [
            '1. Watch problem-solving demonstrations for ' . $topic['name'],
            '2. Practice in simulation playground with guidance',
            '3. Analyze sample code and predict outcomes',
            '4. Attempt similar problems independently'
        ];
    } else {
        $learningPath = [
            '1. Complete hands-on activities for ' . $topic['name'],
            '2. Practice coding in the enhancement environment',
            '3. Build small projects using these concepts',
            '4. Review and optimize your code'
        ];
    }
    
    $topicRecommendations[] = [
        'name' => $topic['name'],
        'percentage' => round($topic['percentage']),
        'weakest_area' => $weakestArea,
        'quiz_perf' => round($quizPerf),
        'sim_perf' => round($simPerf),
        'hands_on_perf' => round($handsOnPerf),
        'learning_path' => $learningPath
    ];
}

// 5. Generate overall progress message
if ($readyForNextLevel) {
    $progressMessage = "Congratulations! You've reached the threshold to advance to the next class level.";
    $progressAdvice = "Continue strengthening your weak areas to ensure solid mastery before moving forward.";
} else {
    $progressMessage = "You're making progress! Keep working to reach the 75% threshold for the next class level.";
    $progressAdvice = "Focus on the recommended areas below to improve your overall performance.";
}

// Check if this is from onboarding
$isOnboarding = isset($_GET['onboarding']) && $_GET['onboarding'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Assessment Results - SkillSync</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(107, 175, 146, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px; 
            box-shadow: 0 20px 60px rgba(75, 139, 110, 0.3);
            overflow: hidden;
            border: 2px solid rgba(107, 175, 146, 0.3);
            position: relative;
            z-index: 1;
        }
        
        /* Header Section */
        .header {
            background: linear-gradient(135deg, #4B8B6E 0%, #6BAF92 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.3;
        }

        .header-content { 
            position: relative; 
            z-index: 1; 
        }

        .header h1 { 
            font-size: 2.5rem; 
            margin-bottom: 10px; 
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header .subtitle { 
            font-size: 1.2rem; 
            opacity: 0.95; 
            margin-bottom: 30px; 
        }
        
        /* Score Display */
        .score-display {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 40px;
            align-items: center;
            margin-top: 20px;
        }

        .total-score {
            text-align: center;
        }

        .score-number {
            font-size: 4rem;
            font-weight: bold;
            line-height: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .score-label {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-top: 5px;
        }
        
        /* Class Level Display */
        .class-level {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .class-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.95);
            color: #4B8B6E;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .progress-container {
            margin-top: 20px;
        }

        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            position: relative;
        }

        .progress-ring svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            fill: none;
            stroke-width: 8;
        }

        .progress-ring .progress-bg {
            stroke: rgba(255, 255, 255, 0.4);
        }

        .progress-ring .progress-bar {
            stroke: #E8C547;
            stroke-linecap: round;
            transition: stroke-dasharray 0.6s ease;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-label {
            text-align: center;
            margin-top: 15px;
            font-size: 1rem;
            opacity: 0.95;
        }
        
        /* Content Sections */
        .content {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.8rem;
            color: #4B8B6E;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
        }

        .section-title i {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);
        }
        
        /* Performance Analysis */
        .performance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: linear-gradient(135deg, rgba(249, 249, 246, 0.9), rgba(255, 255, 255, 0.95));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(75, 139, 110, 0.15);
            border: 2px solid rgba(107, 175, 146, 0.2);
            height: 400px;
            position: relative;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #4B8B6E;
            margin-bottom: 20px;
            text-align: center;
        }

        .chart-wrapper {
            height: calc(100% - 50px);
            position: relative;
        }
        
        /* Topic Performance */
        .topic-list {
            display: grid;
            gap: 15px;
        }

        .topic-item {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid #e74c3c;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .topic-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.2);
        }

        .topic-item.excellent { 
            border-left-color: #6BAF92;
            background: linear-gradient(135deg, rgba(107, 175, 146, 0.05), rgba(255, 255, 255, 0.98));
        }

        .topic-item.good { 
            border-left-color: #E8C547;
            background: linear-gradient(135deg, rgba(232, 197, 71, 0.05), rgba(255, 255, 255, 0.98));
        }

        .topic-item.needs-work { 
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.05), rgba(255, 255, 255, 0.98));
        }
        
        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .topic-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
        }

        .topic-percentage {
            font-size: 1.3rem;
            font-weight: bold;
        }

        .topic-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .breakdown-item {
            text-align: center;
            padding: 8px;
            background: rgba(107, 175, 146, 0.1);
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid rgba(107, 175, 146, 0.2);
        }

        .breakdown-value {
            font-weight: bold;
            color: #4B8B6E;
        }
        
        /* Weakness Analysis */
        .weakness-analysis {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(231, 76, 60, 0.3);
        }

        .strength-analysis {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.3);
        }

        .analysis-title {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .analysis-item {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 12px;
            padding: 15px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Learning Path */
        .learning-path {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.3);
        }

        .path-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .path-step {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(15px);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .path-step:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .step-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .step-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .step-description {
            font-size: 0.95rem;
            opacity: 0.95;
        }
        
        /* Action Buttons */
        .action-section {
            text-align: center;
            padding: 40px;
            background: linear-gradient(135deg, rgba(249, 249, 246, 0.9), rgba(255, 255, 255, 0.95));
            backdrop-filter: blur(10px);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #4B8B6E, #6BAF92);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(75, 139, 110, 0.35);
        }
        
        /* Recommendation Card Hover Effects */
        .recommendation-card {
            transition: transform 0.3s, box-shadow 0.3s;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }

        .recommendation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(75, 139, 110, 0.2) !important;
        }

        .resource-link {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .resource-link:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(232, 197, 71, 0.5) !important;
            background: linear-gradient(135deg, #d4b03d, #E8C547) !important;
        }

        .topic-map-card {
            transition: transform 0.3s, box-shadow 0.3s;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(107, 175, 146, 0.2);
        }

        .topic-map-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(75, 139, 110, 0.2) !important;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .score-display { grid-template-columns: 1fr; gap: 20px; }
            .performance-grid { grid-template-columns: 1fr; }
            .path-steps { grid-template-columns: 1fr; }
            .analysis-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 2rem; }
            .score-number { font-size: 3rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Score and Class Level -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> Pre-Assessment Results</h1>
                <p class="subtitle">Your comprehensive skill evaluation and learning roadmap</p>
                
                <div class="score-display">
                    <div class="total-score">
                        <div class="score-number"><?= $totalScore ?></div>
                        <div class="score-label">Total Score</div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">out of <?= $maxTotalScore ?> points</div>
                    </div>
                    
                    <div class="class-level">
                        <div class="class-badge">
                            <i class="fas fa-<?= $classLevel === 'Intermediate' ? 'star' : 'seedling' ?>"></i>
                            <?= $classLevel ?> Level
                        </div>
                        
                        <?php if ($classLevel === 'Beginner'): ?>
                        <div class="progress-container">
                            <div class="progress-ring">
                                <svg>
                                    <circle class="progress-bg" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress-bar" cx="60" cy="60" r="54" 
                                            stroke-dasharray="<?= $progressToNext * 3.39 ?> 339"
                                            stroke-dashoffset="0"></circle>
                                </svg>
                                <div class="progress-text"><?= round($progressToNext) ?>%</div>
                            </div>
                            <div class="progress-label">Progress to Intermediate</div>
                            <div style="font-size: 0.9rem; opacity: 0.8; margin-top: 10px;">
                                Need <?= $maxTotalScore * 0.77 - $totalScore ?> more points (115/150 required)
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="progress-container">
                            <div class="progress-ring">
                                <svg>
                                    <circle class="progress-bg" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress-bar" cx="60" cy="60" r="54" 
                                            stroke-dasharray="339 339"></circle>
                                </svg>
                                <div class="progress-text">100%</div>
                            </div>
                            <div class="progress-label">Intermediate Level Achieved!</div>
                            <div style="font-size: 0.9rem; opacity: 0.8; margin-top: 10px;">
                                Ready for advanced challenges
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="total-score">
                        <div class="score-number"><?= round($overallPercentage) ?>%</div>
                        <div class="score-label">Overall Performance</div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">
                            <?= $overallPercentage >= 77 ? 'Excellent!' : ($overallPercentage >= 60 ? 'Good Progress' : 'Needs Improvement') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <!-- Performance Analysis Section -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    Performance Analysis
                </h2>
                
                <div class="performance-grid">
                    <div class="chart-container">
                        <h3 class="chart-title">Score Distribution by Topic</h3>
                        <div class="chart-wrapper">
                            <canvas id="topicChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h3 class="chart-title">Question Type Performance</h3>
                        <div class="chart-wrapper">
                            <canvas id="typeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Topic Performance -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-tasks"></i>
                    Detailed Topic Performance
                </h2>
                
                <div class="topic-list">
                    <?php foreach ($topicScores as $topicId => $data): ?>
                    <?php 
                        $percentage = $data['percentage'];
                        $class = $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'needs-work');
                        $icon = $percentage >= 80 ? 'fa-star' : ($percentage >= 60 ? 'fa-thumbs-up' : 'fa-exclamation-triangle');
                    ?>
                    <div class="topic-item <?= $class ?>">
                        <div class="topic-header">
                            <div class="topic-name">
                                <i class="fas <?= $icon ?>"></i>
                                Topic <?= $topicId ?>: <?= htmlspecialchars($data['name']) ?>
                            </div>
                            <div class="topic-percentage <?= $class ?>"><?= round($percentage) ?>%</div>
                        </div>
                        
                        <div class="topic-breakdown">
                            <div class="breakdown-item">
                                <div class="breakdown-value"><?= $data['quiz_correct'] ?>/<?= $data['quiz_total'] ?></div>
                                <div>Quiz Questions</div>
                            </div>
                            <div class="breakdown-item">
                                <div class="breakdown-value"><?= $data['simulation_correct'] ?>/<?= $data['simulation_total'] ?></div>
                                <div>Simulation</div>
                            </div>
                            <div class="breakdown-item">
                                <div class="breakdown-value"><?= $data['hands_on_score'] ?>/<?= $data['hands_on_max'] ?></div>
                                <div>Hands-on</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- ===================================================================== -->
            <!-- PREDICTIVE MODEL & RECOMMENDATIONS SECTION -->
            <!-- ===================================================================== -->
            
            <!-- Overall Progress to Next Level -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-chart-line"></i>
                    Progress to Next Class Level
                </h2>
                
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; border-radius: 15px; color: white; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 30px; align-items: center;">
                        <div>
                            <h3 style="font-size: 1.8rem; margin-bottom: 10px;">
                                <?= $readyForNextLevel ? '🎉 Ready to Advance!' : '📈 Keep Going!' ?>
                            </h3>
                            <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 15px;">
                                <?= htmlspecialchars($progressMessage) ?>
                            </p>
                            <p style="font-size: 0.95rem; opacity: 0.8;">
                                <?= htmlspecialchars($progressAdvice) ?>
                            </p>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 3.5rem; font-weight: bold; margin-bottom: 5px;">
                                <?= round($overallPercentage) ?>%
                            </div>
                            <div style="font-size: 0.9rem; opacity: 0.8;">
                                Overall Score
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div style="margin-top: 20px; background: rgba(255,255,255,0.2); border-radius: 10px; height: 20px; overflow: hidden;">
                        <div style="background: white; height: 100%; width: <?= min(100, $progressToNextLevel) ?>%; transition: width 0.3s;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.85rem; opacity: 0.8;">
                        <span>Current: <?= round($overallPercentage) ?>%</span>
                        <span>Target: 75%</span>
                    </div>
                </div>
                
                <?php if (!empty($strongTopics)): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <h4 style="color: #155724; margin-bottom: 10px;">
                        <i class="fas fa-check-circle"></i> You Excel In:
                    </h4>
                    <p style="color: #155724;">
                        <?= implode(', ', $strongTopics) ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($weakTopics)): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 10px;">
                    <h4 style="color: #856404; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> Areas to Improve to Reach Next Level:
                    </h4>
                    <ul style="color: #856404; margin-left: 20px;">
                        <?php foreach ($weakTopics as $topic): ?>
                        <li style="margin-bottom: 5px;">
                            <strong><?= htmlspecialchars($topic['name']) ?></strong> (<?= round($topic['percentage']) ?>%)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Learning Type Recommendations (Based on Quiz/Simulation/Hands-on) -->
            <?php if (!empty($recommendations)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-lightbulb"></i>
                    Personalized Learning Recommendations
                </h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px; font-size: 1.05rem;">
                    Based on your performance patterns, here's your customized learning strategy:
                </p>
                
                <div style="display: grid; gap: 25px;">
                    <?php foreach ($recommendations as $rec): ?>
                    <div class="recommendation-card" style="background: white; border: 2px solid #e0e0e0; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #4B8B6E, #6BAF92); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(75, 139, 110, 0.3);">
                                <i class="fas <?= $rec['icon'] ?>" style="font-size: 24px; color: white;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h3 style="color: #2c3e50; margin-bottom: 5px; font-size: 1.4rem;">
                                    <?= htmlspecialchars($rec['title']) ?>
                                </h3>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #fee; color: #c33; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        Current: <?= $rec['performance'] ?>
                                    </span>
                                    <span style="color: #888; font-size: 0.9rem;">
                                        <?= ucfirst($rec['type']) ?> Performance
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <p style="color: #555; margin-bottom: 20px; font-size: 1.05rem;">
                            <?= htmlspecialchars($rec['description']) ?>
                        </p>
                        
                        <h4 style="color: #2c3e50; margin-bottom: 12px; font-size: 1.1rem;">
                            <i class="fas fa-tasks"></i> Action Plan:
                        </h4>
                        <ul style="margin-left: 20px; margin-bottom: 20px; color: #555; line-height: 1.8;">
                            <?php foreach ($rec['actions'] as $action): ?>
                            <li><?= $action ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h4 style="color: #2c3e50; margin-bottom: 12px; font-size: 1.1rem;">
                            <i class="fas fa-link"></i> Recommended Resources:
                        </h4>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <?php foreach ($rec['resources'] as $resource): ?>
                            <a href="<?= $resource['link'] ?>" class="resource-link" style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #E8C547, #F4D77C); color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 15px rgba(232, 197, 71, 0.3); transition: all 0.3s ease;">
                                <i class="fas <?= $resource['icon'] ?>"></i>
                                <?= htmlspecialchars($resource['text']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Topic-Specific Learning Paths -->
            <?php if (!empty($topicRecommendations)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-map-marked-alt"></i>
                    Topic-Specific Learning Map
                </h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px; font-size: 1.05rem;">
                    Follow these customized learning paths to improve your weak areas:
                </p>
                
                <div style="display: grid; gap: 20px;">
                    <?php foreach ($topicRecommendations as $topicRec): ?>
                    <div class="topic-map-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="color: #2c3e50; font-size: 1.4rem; margin: 0;">
                                <i class="fas fa-route"></i>
                                <?= htmlspecialchars($topicRec['name']) ?>
                            </h3>
                            <span style="background: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; color: #c33;">
                                <?= $topicRec['percentage'] ?>%
                            </span>
                        </div>
                        
                        <!-- Performance Breakdown -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                            <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: <?= $topicRec['quiz_perf'] >= 60 ? '#27ae60' : '#e74c3c' ?>;">
                                    <?= $topicRec['quiz_perf'] ?>%
                                </div>
                                <div style="font-size: 0.85rem; color: #555;">Quiz</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: <?= $topicRec['sim_perf'] >= 60 ? '#27ae60' : '#e74c3c' ?>;">
                                    <?= $topicRec['sim_perf'] ?>%
                                </div>
                                <div style="font-size: 0.85rem; color: #555;">Simulation</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: <?= $topicRec['hands_on_perf'] >= 60 ? '#27ae60' : '#e74c3c' ?>;">
                                    <?= $topicRec['hands_on_perf'] ?>%
                                </div>
                                <div style="font-size: 0.85rem; color: #555;">Hands-on</div>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px;">
                            <h4 style="color: #2c3e50; margin-bottom: 15px; font-size: 1.1rem;">
                                <i class="fas fa-graduation-cap"></i>
                                Recommended Learning Path:
                            </h4>
                            <ol style="margin-left: 20px; color: #555; line-height: 2;">
                                <?php foreach ($topicRec['learning_path'] as $step): ?>
                                <li><?= htmlspecialchars($step) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Removed redundant Strength/Weakness sections - Info already shown in Topic-Specific Learning Map -->
            
            <!-- Personalized Learning Path -->
            <div class="section">
                <div class="learning-path">
                    <div class="analysis-title">
                        <i class="fas fa-route"></i>
                        Your Personalized Learning Path
                    </div>
                    <p style="text-align: center; margin-bottom: 30px; font-size: 1.1rem; opacity: 0.9;">
                        Based on your assessment results, here's your recommended learning journey:
                    </p>
                    
                    <div class="path-steps">
                        <?php if ($classLevel === 'Beginner'): ?>
                        <div class="path-step">
                            <div class="step-icon"><i class="fas fa-play"></i></div>
                            <div class="step-title">Start with Basics</div>
                            <div class="step-description">
                                Focus on fundamental OOP concepts and strengthen your foundation in <?= !empty($weakAreas) ? implode(', ', array_column($weakAreas, 'area')) : 'core programming concepts' ?>
                            </div>
                        </div>
                        
                        <div class="path-step">
                            <div class="step-icon"><i class="fas fa-code"></i></div>
                            <div class="step-title">Practice Coding</div>
                            <div class="step-description">
                                Complete hands-on exercises and coding challenges to reinforce theoretical knowledge
                            </div>
                        </div>
                        
                        <div class="path-step">
                            <div class="step-icon"><i class="fas fa-graduation-cap"></i></div>
                            <div class="step-title">Advanced Topics</div>
                            <div class="step-description">
                                Once you reach 77% overall, advance to intermediate-level concepts and complex scenarios
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="path-step">
                            <div class="step-icon"><i class="fas fa-rocket"></i></div>
                            <div class="step-title">Advanced Challenges</div>
                            <div class="step-description">
                                Take on complex OOP design patterns and advanced programming concepts
                            </div>
                        </div>
                        
                        <div class="path-step">
                            <div class="step-icon"><i class="fas fa-users"></i></div>
                            <div class="step-title">Real-world Projects</div>
                            <div class="step-description">
                                Apply your skills to build comprehensive applications using OOP principles
                            </div>
                        </div>
                        
                        <div class="path-step">
                            <div class="step-icon"><i class="fas fa-crown"></i></div>
                            <div class="step-title">Master Level</div>
                            <div class="step-description">
                                Work on system design and help others learn OOP concepts
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-section">
            <h3 style="margin-bottom: 30px; color: #2c3e50;">Ready to Start Your Learning Journey?</h3>
            
            <?php if ($classLevel === 'Beginner'): ?>
            <a href="student_dashboard.php" class="btn btn-primary">
                <i class="fas fa-play"></i> Start Beginner Modules
            </a>
            <a href="recommendations.php" class="btn btn-success">
                <i class="fas fa-lightbulb"></i> View Detailed Recommendations
            </a>
            <?php else: ?>
            <a href="student_dashboard.php" class="btn btn-primary">
                <i class="fas fa-rocket"></i> Access Intermediate Content
            </a>
            <a href="progress.php" class="btn btn-success">
                <i class="fas fa-chart-line"></i> Track Your Progress
            </a>
            <?php endif; ?>
            
            <a href="pre_test.php" class="btn" style="background: #6c757d; color: white; margin-left: 20px;">
                <i class="fas fa-redo"></i> Retake Assessment
            </a>
        </div>
    </div>

    <script>
        // Chart.js configurations
        const topicData = {
            labels: [<?php 
                $labels = [];
                foreach ($topicScores as $topicId => $data) {
                    $labels[] = "'" . addslashes($data['name']) . "'";
                }
                echo implode(',', $labels);
            ?>],
            datasets: [{
                data: [<?php 
                    $percentages = [];
                    foreach ($topicScores as $data) {
                        $percentages[] = round($data['percentage'] ?? 0);
                    }
                    echo implode(',', $percentages);
                ?>],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        const typeData = {
            labels: ['Quiz Questions', 'Simulation Tasks', 'Hands-on Activities'],
            datasets: [{
                data: [
                    <?= round($quizPercentage ?? 0) ?>,
                    <?= round($simulationPercentage ?? 0) ?>,
                    <?= round($handsOnPercentage ?? 0) ?>
                ],
                backgroundColor: ['#36A2EB', '#FFCE56', '#4BC0C0'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };
        
        // Debug output
        console.log('Topic Data:', topicData);
        console.log('Type Data:', typeData);
        console.log('Quiz %:', <?= $quizPercentage ?? 0 ?>);
        console.log('Simulation %:', <?= $simulationPercentage ?? 0 ?>);
        console.log('Hands-on %:', <?= $handsOnPercentage ?? 0 ?>);

        // Initialize charts
        const topicCtx = document.getElementById('topicChart').getContext('2d');
        new Chart(topicCtx, {
            type: 'doughnut',
            data: topicData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                }
            }
        });

        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'bar',
            data: typeData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: function(value) { return value + '%'; } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Add animation to progress ring
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                setTimeout(() => {
                    progressBar.style.transition = 'stroke-dasharray 2s ease-in-out';
                }, 500);
            }
        });

        <?php if ($isOnboarding): ?>
        // Show onboarding completion message
        setTimeout(function() {
            if (confirm('🎉 Congratulations! You have completed your first pre-assessment. SkillSync now knows your skill level and can provide personalized recommendations. Click OK to continue to your dashboard!')) {
                window.location.href = 'student_dashboard.php';
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>