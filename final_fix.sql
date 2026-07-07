-- Step 1: Show current data with all columns
SELECT 
    id,
    student_id,
    level,
    overall_score,
    progress_percentage,
    achievements
FROM student_progress_history
WHERE student_id = 2;

-- Step 2: Delete ALL Beginner entries
DELETE FROM student_progress_history 
WHERE student_id = 2 AND level = 'Beginner';

-- Step 3: Insert ONE clean entry with correct data
INSERT INTO student_progress_history 
(student_id, level, overall_score, progress_percentage, assessment_data, activity_scores, post_test_scores, achievements)
VALUES (
    2,
    'Beginner',
    86,
    100,
    '{}',
    '[]',
    '[]',
    '{"activities_completed": 0, "post_tests_taken": 5, "promotion_score": 86}'
);

-- Step 4: Verify
SELECT * FROM student_progress_history WHERE student_id = 2;
