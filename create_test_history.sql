-- Manual backup script to create a history entry for testing
-- This simulates what would have been saved when you got promoted from Beginner to Intermediate

-- Replace @student_id with your actual user ID
SET @student_id = 2; -- Change this to your user_id

-- Get the current student data
SET @assessment_data = (SELECT assessment_data FROM students WHERE user_id = @student_id LIMIT 1);

-- Create a mock Beginner history entry
INSERT INTO student_progress_history 
(student_id, level, overall_score, progress_percentage, assessment_data, activity_scores, post_test_scores, achievements, promoted_at)
VALUES (
    @student_id,
    'Beginner',
    86.0,  -- Your approximate score when you completed Beginner
    100.0, -- You reached 100% to qualify for promotion
    @assessment_data,
    '[]',  -- Empty activities for now
    '[]',  -- Empty post-tests for now
    JSON_OBJECT(
        'activities_completed', 0,
        'post_tests_taken', 5,
        'promotion_score', 78.0,
        'promotion_test_date', NOW()
    ),
    '2025-10-02 09:00:00' -- Approximate time you got promoted
);

-- Verify the entry was created
SELECT 
    id,
    level,
    overall_score,
    progress_percentage,
    JSON_EXTRACT(achievements, '$.promotion_score') as promotion_score,
    promoted_at
FROM student_progress_history
WHERE student_id = @student_id;
