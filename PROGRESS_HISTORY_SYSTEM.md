# Progress History & Backup System

## Overview
When a student passes the level promotion test and advances to the next level, their progress is **automatically backed up** before being reset. This allows students to review their achievements at each level.

## How It Works

### 1. **Automatic Backup on Promotion**
When a student passes the level promotion test (score ≥ 77%):

1. ✅ **All current progress is backed up** to `student_progress_history` table
2. ✅ **Class level is updated** (e.g., Beginner → Intermediate)
3. ✅ **Progress is reset to 0%** for fresh start
4. ✅ **Activity scores are cleared** (but backed up)
5. ✅ **Post-test attempts are cleared** (but backed up)

### 2. **What Gets Backed Up**

For each level completion, the system saves:

| Data | Description |
|------|-------------|
| **Level** | The level they completed (Beginner, Intermediate) |
| **Overall Score** | Average score across all topics |
| **Progress Percentage** | How far they progressed (usually 100%+) |
| **Assessment Data** | Full JSON of all topic scores |
| **Activity Scores** | All hands-on activity completions |
| **Post-Test Scores** | All post-assessment attempts |
| **Achievements** | Summary of accomplishments |
| **Promotion Date** | When they advanced to next level |
| **Promotion Score** | Score achieved on promotion test |

### 3. **Viewing Progress History**

Students can view their complete learning journey:

📍 **Location**: Sidebar → "Progress History"  
🔗 **URL**: `progress_history.php`

**What Students See**:
- Timeline of all completed levels
- Overall score for each level
- Activities completed count
- Promotion test scores
- Dates of completion
- Visual badges for each level

## Database Structure

### Table: `student_progress_history`

```sql
CREATE TABLE student_progress_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    level VARCHAR(50) NOT NULL,
    overall_score DECIMAL(5,2),
    progress_percentage DECIMAL(5,2),
    assessment_data JSON,           -- Full topic scores backup
    activity_scores JSON,            -- All activity completions
    post_test_scores JSON,           -- All post-test attempts
    achievements JSON,               -- Summary of accomplishments
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    INDEX idx_student_level (student_id, level)
);
```

## Implementation Details

### File: `save_level_promotion_test.php`

**Process Flow**:

```
1. Student passes promotion test
   ↓
2. BACKUP PHASE
   - Fetch current assessment_data
   - Fetch all activity_scores
   - Fetch all post_test_attempts
   - Calculate overall_score
   - Create achievements summary
   - INSERT into student_progress_history
   ↓
3. UPDATE PHASE
   - Update class_level in assessment_details
   - Set progress_to_next = 0
   - Record promotion_date
   ↓
4. RESET PHASE
   - DELETE activity_scores (already backed up)
   - DELETE post_test_attempts (already backed up)
   - SET topic scores to 0 in assessment_data
   ↓
5. Redirect to dashboard with fresh 0% progress
```

## Benefits

### For Students:
✅ **Track Progress**: See how far they've come  
✅ **Motivation**: Review past achievements  
✅ **Reference**: Look back at previous level scores  
✅ **Portfolio**: Build a learning portfolio  

### For System:
✅ **Data Preservation**: No data loss on reset  
✅ **Analytics**: Analyze student progression patterns  
✅ **Audit Trail**: Complete history of student journey  
✅ **Reporting**: Generate progress reports  

## Example Student Journey

### Timeline View:

```
🌱 Beginner Level (Completed Oct 1, 2025)
   - Overall Score: 82%
   - Activities: 15 completed
   - Promotion Score: 78.5%
   ↓
🚀 Intermediate Level (Current)
   - Overall Score: 0%
   - Activities: 0 completed
   - Progress: Starting fresh!
```

## Setup Instructions

### 1. Create the History Table

```bash
mysql -u root skillsync < create_progress_history_table.sql
```

Or run in phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS student_progress_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    level VARCHAR(50) NOT NULL,
    overall_score DECIMAL(5,2),
    progress_percentage DECIMAL(5,2),
    assessment_data JSON,
    activity_scores JSON,
    post_test_scores JSON,
    achievements JSON,
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    INDEX idx_student_level (student_id, level)
);
```

### 2. Files Involved

- ✅ `create_progress_history_table.sql` - Table creation
- ✅ `save_level_promotion_test.php` - Backup logic (updated)
- ✅ `progress_history.php` - View history page
- ✅ `student_dashboard.php` - Added sidebar link

### 3. Testing

1. Take and pass a level promotion test
2. Check that progress is reset to 0%
3. Click "Progress History" in sidebar
4. Verify your previous level data is displayed

## Query Examples

### View a student's history:

```sql
SELECT 
    level,
    overall_score,
    progress_percentage,
    JSON_EXTRACT(achievements, '$.activities_completed') as activities,
    JSON_EXTRACT(achievements, '$.promotion_score') as promotion_score,
    promoted_at
FROM student_progress_history
WHERE student_id = 2
ORDER BY promoted_at DESC;
```

### Get total activities completed across all levels:

```sql
SELECT 
    student_id,
    SUM(JSON_EXTRACT(achievements, '$.activities_completed')) as total_activities
FROM student_progress_history
GROUP BY student_id;
```

## Future Enhancements

Potential additions:
- 📊 Download progress report as PDF
- 🏆 Achievement badges for milestones
- 📈 Visual progress timeline chart
- 📧 Email summary when promoted
- 🎯 Compare progress across levels
- 👥 Anonymous comparison with peers

---

**Status**: ✅ Fully Implemented  
**Date**: October 2, 2025  
**Version**: 1.0
