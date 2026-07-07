-- First, let's see what's actually in your history table
SELECT 
    id,
    student_id,
    level,
    overall_score,
    progress_percentage,
    achievements,
    promoted_at
FROM student_progress_history
WHERE student_id = 2
ORDER BY id;

-- This will show us exactly what data we have
