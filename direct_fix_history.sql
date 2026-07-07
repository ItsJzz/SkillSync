-- DIRECT FIX: Delete everything and insert the correct data
-- This ensures clean data

-- Step 1: Delete all existing Beginner history for your account
DELETE FROM student_progress_history 
WHERE student_id = 2 AND level = 'Beginner';

-- Step 2: Insert one correct entry with your actual Beginner progress
INSERT INTO student_progress_history 
(student_id, level, overall_score, progress_percentage, assessment_data, activity_scores, post_test_scores, achievements, promoted_at)
VALUES (
    2,                    -- your student_id
    'Beginner',           -- level completed
    86.0,                 -- your actual overall score
    100.0,                -- you reached 100% to qualify for promotion
    '{}',                 -- assessment data (empty for now)
    '[]',                 -- activity scores (empty for now)
    '[]',                 -- post-test scores (empty for now)
    '{"activities_completed": 0, "post_tests_taken": 5, "promotion_score": 78.0, "promotion_test_date": "2025-10-02 09:30:00"}',
    '2025-10-02 09:30:00' -- approximate promotion time
);

-- Step 3: Verify the new entry
SELECT 
    id,
    level,
    overall_score,
    progress_percentage,
    JSON_EXTRACT(achievements, '$.promotion_score') as promotion_score,
    promoted_at
FROM student_progress_history
WHERE student_id = 2;
