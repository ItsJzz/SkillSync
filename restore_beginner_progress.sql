-- Manual restoration of your actual Beginner journey progress
-- This will update your history to show your real progress before promotion

-- Your actual Beginner stats based on the screenshot you showed earlier:
-- Overall Score: 86%
-- Progress: 100% (you reached promotion test)
-- Promotion Test Score: 78.0% (first attempt)

-- Update the first history entry (the one with 86%)
UPDATE student_progress_history
SET 
    overall_score = 86.0,
    progress_percentage = 100.0,
    achievements = JSON_SET(
        achievements,
        '$.activities_completed', 0,
        '$.post_tests_taken', 5,
        '$.promotion_score', 78.0,
        '$.original_overall_score', 86.0,
        '$.note', 'Completed Beginner level and passed promotion test'
    )
WHERE student_id = 2 
  AND level = 'Beginner'
  AND overall_score = 0
ORDER BY id ASC
LIMIT 1;

-- Delete duplicate entries (keep only the corrected one)
DELETE FROM student_progress_history
WHERE student_id = 2 
  AND level = 'Beginner'
  AND overall_score = 0
  AND id > (
    SELECT * FROM (
        SELECT MIN(id) FROM student_progress_history 
        WHERE student_id = 2 AND level = 'Beginner'
    ) as temp
  );

-- Verify the update
SELECT 
    id,
    level,
    overall_score,
    progress_percentage,
    JSON_EXTRACT(achievements, '$.promotion_score') as promotion_score,
    promoted_at
FROM student_progress_history
WHERE student_id = 2
ORDER BY promoted_at DESC;
