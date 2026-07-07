-- Simple fix: Update your Beginner history to show actual progress

-- Update the history entry to show your real Beginner journey
UPDATE student_progress_history
SET 
    overall_score = 86.0,
    progress_percentage = 100.0
WHERE student_id = 2 
  AND level = 'Beginner'
LIMIT 1;

-- If you have duplicates, delete the extras and keep only one
DELETE h1 FROM student_progress_history h1
INNER JOIN student_progress_history h2 
WHERE 
    h1.student_id = h2.student_id
    AND h1.level = h2.level
    AND h1.id > h2.id
    AND h1.student_id = 2
    AND h1.level = 'Beginner';

-- Check the result
SELECT 
    level,
    overall_score,
    progress_percentage,
    JSON_EXTRACT(achievements, '$.promotion_score') as promotion_score,
    promoted_at
FROM student_progress_history
WHERE student_id = 2;
