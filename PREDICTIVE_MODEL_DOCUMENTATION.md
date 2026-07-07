# Predictive Model & Recommendation Engine Documentation

## Overview

The SkillSync pre-assessment system now includes an **intelligent predictive model** that analyzes student performance and provides personalized, actionable learning recommendations.

---

## 🎯 Core Concept

**Assessment → Analysis → Prediction → Personalized Recommendations**

The system evaluates performance across three dimensions:
1. **Quiz Questions** (Theoretical Knowledge)
2. **Simulation Tasks** (Problem-Solving Application)  
3. **Hands-On Activities** (Practical Coding Skills)

---

## 📊 How It Works

### 1. **Overall Progress Analysis**

**Threshold:** 75% overall score to advance to next class level (115/150 points)

```
Current Score: 42%
Progress to Next Level: 56% (42/75 * 100)
Status: Keep Going! 📈
```

**If score ≥ 75%:**
- ✅ Student is ready to advance
- Message: "Congratulations! You've reached the threshold to advance."
- Advice: Continue strengthening weak areas for mastery

**If score < 75%:**
- 📈 Student needs improvement
- Message: "Keep working to reach the 75% threshold."
- Advice: Focus on recommended areas below

---

### 2. **Topic-Level Analysis**

Each topic is evaluated individually:

**Strong Topics (≥60%):**
- Listed in "You Excel In" section
- Displayed with green indicators

**Weak Topics (<60%):**
- Listed in "Areas to Improve" section
- Displayed with yellow warning indicators
- Each gets a **customized learning path**

---

### 3. **Question Type Performance Analysis**

The system analyzes each question type separately:

#### A. **Quiz Questions (Theoretical Knowledge)**

**Threshold:** <60% = Needs Attention

**If weak in Quiz Questions:**
- **Diagnosis:** Gaps in theoretical knowledge
- **Recommendation:** Modular Learning Approach
- **Action Plan:**
  1. Break concepts into smaller chunks
  2. Review learning materials systematically
  3. Create concept maps to visualize relationships
  4. Summarize key concepts in own words
- **Resources:**
  - 📚 Study Learning Materials
  - 📄 Read Documentation
  - ✏️ Practice Quizzes

**Example Scenario:**
```
Quiz Performance: 45%
→ System detects weak theoretical foundation
→ Recommends modular learning strategy
→ Provides direct links to reading materials
```

---

#### B. **Simulation Questions (Problem-Solving)**

**Threshold:** <60% = Needs Attention

**If weak in Simulation Questions:**
- **Diagnosis:** Difficulty applying concepts to real scenarios
- **Recommendation:** Visual & Interactive Learning
- **Action Plan:**
  1. Watch video tutorials with step-by-step solutions
  2. Visit simulation playground for safe practice
  3. Analyze sample code and trace execution
  4. Work through guided examples first
- **Resources:**
  - 🎥 Watch Video Tutorials
  - 🎮 Go to Simulation Playground
  - 💻 Interactive Examples

**Example Scenario:**
```
Simulation Performance: 38%
→ System detects weak application skills
→ Recommends video tutorials and simulations
→ Provides links to playground and examples
```

---

#### C. **Hands-On Activities (Practical Coding)**

**Threshold:** <60% = Needs Attention

**If weak in Hands-On Activities:**
- **Diagnosis:** Insufficient coding practice
- **Recommendation:** Active Coding Practice
- **Action Plan:**
  1. Complete enhancement process activities
  2. Practice in coding environment daily
  3. Start simple, gradually increase complexity
  4. Review and refactor code regularly
- **Resources:**
  - 🔧 Enhancement Activities
  - ⌨️ Coding Practice
  - 📐 Hands-on Projects

**Example Scenario:**
```
Hands-On Performance: 30%
→ System detects weak practical skills
→ Recommends coding practice environment
→ Provides links to enhancement activities
```

---

### 4. **Topic-Specific Learning Map**

For each **weak topic** (<60%), the system generates a customized learning path based on the **weakest area** within that topic.

**Algorithm:**
```php
foreach weak topic:
    Calculate quiz_percentage, simulation_percentage, hands_on_percentage
    Identify weakest_area = minimum(quiz, simulation, hands_on)
    
    if weakest_area == 'theory':
        Learning Path → Review materials → Videos → Quizzes → Simulations
    
    elif weakest_area == 'application':
        Learning Path → Watch demos → Practice playground → Analyze code → Independent problems
    
    else: // 'practice'
        Learning Path → Complete activities → Enhancement → Projects → Code review
```

**Note:** Redundant "Areas for Improvement" section has been removed. The Topic-Specific Learning Map already provides comprehensive breakdown and recommendations.

**Example Output:**

**Topic: Classes and Objects (42%)**
- Quiz: 75% ✅
- Simulation: 50% ⚠️
- Hands-on: 20% ❌

**Weakest Area:** Hands-on (20%)

**Recommended Learning Path:**
1. Complete hands-on activities for Classes and Objects
2. Practice coding in the enhancement environment
3. Build small projects using these concepts
4. Review and optimize your code

---

## 🧠 Predictive Model Logic

### Performance Thresholds

| Score Range | Level | Status |
|-------------|-------|--------|
| 80-100% | Excellent | Strong ⭐ |
| 60-79% | Good | Adequate ✅ |
| 50-59% | Moderate | Needs Work ⚠️ |
| 0-49% | Weak | Needs Attention ❌ |

### Recommendation Triggers

```php
IF overall_percentage < 75:
    → Show "Progress to Next Level" alert
    → Display weak topics prominently
    → Generate aggressive recommendations

IF quiz_percentage < 60:
    → Trigger "Modular Learning" recommendation
    → Link to reading materials

IF simulation_percentage < 60:
    → Trigger "Visual Learning" recommendation
    → Link to videos and playground

IF hands_on_percentage < 60:
    → Trigger "Practical Coding" recommendation
    → Link to coding practice environments
```

---

## 📋 Data Structure

### Assessment Data Format
```json
{
    "format": "scalable",
    "total_score": 89,
    "max_total_score": 150,
    "overall_percentage": 59.3,
    "topic_scores": {
        "13": {
            "name": "Classes and Objects",
            "quiz_correct": 4,
            "quiz_total": 8,
            "simulation_correct": 5,
            "simulation_total": 8,
            "hands_on_score": 8,
            "hands_on_max": 10,
            "percentage": 68.0
        }
    }
}
```

### Generated Recommendations Structure
```php
$recommendations = [
    [
        'type' => 'quiz',
        'icon' => 'fa-book-reader',
        'title' => 'Strengthen Conceptual Understanding',
        'performance' => '45%',
        'status' => 'needs-attention',
        'description' => 'Your quiz performance indicates gaps...',
        'actions' => [...],
        'resources' => [...]
    ]
];
```

---

## 🎨 UI Components

### 1. Progress to Next Level Card
- **Visual:** Gradient background (purple/pink)
- **Content:** Overall score, progress bar, status message
- **Dynamic:** Changes color/message based on threshold

### 2. Learning Type Recommendations
- **Visual:** Card-based layout with icons
- **Content:** Performance percentage, action plan, resource links
- **Dynamic:** Only shows if performance <60% in that type

### 3. Topic-Specific Learning Map
- **Visual:** Gradient cards (orange/peach) with breakdown
- **Content:** Topic name, 3 performance bars, ordered learning path
- **Dynamic:** Only shows for weak topics (<60%)

---

## 🔗 Integration Points

### Database Tables
- **students:** Stores `assessment_data` (JSON)
- **questions:** Source for quiz/simulation questions
- **activities.json:** Source for hands-on activities

### PHP Files
- **pre_test_results.php:** Main recommendation engine (lines 168-332)
- **save_attempt.php:** Stores assessment data
- **pre_test.php:** Collects performance data

### External Resources
- `video_materials.php` - Video tutorials
- `Activity/simulation/` - Simulation playground
- `Enhancement.php` - Enhancement activities
- `coding_practice.php` - Coding practice environment
- `Activity/activity_list.php` - Activity library

---

## 📈 Success Metrics

The system tracks:
1. **Overall Progress** (50% threshold)
2. **Topic Mastery** (60% per topic)
3. **Learning Type Balance** (quiz/simulation/hands-on)
4. **Improvement Trajectory** (comparing multiple attempts)

---

## 🚀 Future Enhancements

### Regression Analysis (Phase 2)
- Track improvement over multiple attempts
- Predict time to mastery
- Identify learning patterns

### Machine Learning Integration (Phase 3)
- Collaborative filtering (similar student patterns)
- Content-based recommendations
- Adaptive difficulty adjustment

### Gamification (Phase 4)
- Achievement badges
- Learning streaks
- Peer comparisons

---

## 🔧 Configuration

### Threshold Settings

Located in `pre_test_results.php`:

```php
// Lines 168-332

// Overall progress threshold
$readyForNextLevel = $overallPercentage >= 75;

// Topic weakness threshold
if ($data['percentage'] < 60) {
    $weakTopics[] = ...
}

// Question type thresholds
if ($quizPercentage < 60) { ... }
if ($simulationPercentage < 60) { ... }
if ($handsOnPercentage < 60) { ... }
```

**To adjust thresholds:**
1. Modify percentage values (currently 75% for overall, 60% for topics/types)
2. Update corresponding UI messages
3. Test with sample data

---

## 📝 Examples

### Example 1: Balanced Weak Performance

**Student Profile:**
- Overall: 42%
- Quiz: 45%
- Simulation: 40%
- Hands-On: 35%

**System Response:**
- ❌ Not ready for next level (needs 50%)
- Shows all 3 recommendation types
- Prioritizes hands-on (weakest)
- Generates learning paths for all weak topics

---

### Example 2: Strong Theory, Weak Practice

**Student Profile:**
- Overall: 58%
- Quiz: 80%
- Simulation: 75%
- Hands-On: 30%

**System Response:**
- ✅ Ready for next level (exceeds 50%)
- Only shows hands-on recommendation
- Acknowledges strengths in theory/simulation
- Focuses on practical coding exercises

---

### Example 3: Topic-Specific Weakness

**Student Profile:**
- Overall: 65%
- Topics:
  - Classes and Objects: 85%
  - Inheritance: 45%
  - Polymorphism: 70%

**System Response:**
- ✅ Ready for next level
- Highlights "Classes and Objects" as strength
- Generates custom learning path for "Inheritance"
- Shows breakdown: Quiz 60%, Simulation 40%, Hands-on 35%
- Recommends simulation practice (weakest area)

---

## 🐛 Troubleshooting

### Issue: Recommendations not showing

**Check:**
1. Assessment data exists in database
2. Performance scores calculated correctly
3. Thresholds are set appropriately
4. No PHP errors in `pre_test_results.php`

### Issue: Incorrect performance percentages

**Check:**
1. `assessment_data` JSON structure is correct
2. Quiz/simulation/hands-on values are present
3. Division by zero protection is working
4. Data types (int/float) are consistent

---

## 📚 References

- **Main Implementation:** `pre_test_results.php` lines 168-332 (logic) and 850-1050 (UI)
- **Data Collection:** `pre_test.php` lines 843-925
- **Data Storage:** `save_attempt.php` lines 60-76
- **Scoring Logic:** `SCORING_FIXES.md`

---

## 👥 User Experience Flow

1. **Student takes pre-test** → Answers questions partially/fully
2. **System calculates scores** → Quiz, simulation, hands-on breakdown
3. **Results page loads** → Shows overall score and charts
4. **Predictive model activates** → Analyzes weak areas
5. **Recommendations display** → Personalized action plans
6. **Student clicks resource link** → Directed to appropriate learning tool
7. **Student improves** → Retakes test, sees progress
8. **System adapts** → Updates recommendations based on new data

---

**Last Updated:** 2025-10-02  
**Version:** 1.0  
**Author:** SkillSync Development Team
