-- Manual reset script for students who were promoted but still show old progress
-- Run this if you need to manually reset a student's progress after promotion

-- Option 1: Reset specific student's progress (replace USER_ID with actual ID)
SET @student_user_id = 2; -- Change this to your student user_id

-- Reset assessment_data topic scores to 0
UPDATE students 
SET assessment_data = JSON_SET(
    assessment_data,
    '$.topic_scores.7.percentage', 0,
    '$.topic_scores.7.score', 0,
    '$.topic_scores.8.percentage', 0,
    '$.topic_scores.8.score', 0,
    '$.topic_scores.9.percentage', 0,
    '$.topic_scores.9.score', 0,
    '$.topic_scores.10.percentage', 0,
    '$.topic_scores.10.score', 0,
    '$.topic_scores.11.percentage', 0,
    '$.topic_scores.11.score', 0
)
WHERE user_id = @student_user_id OR id = @student_user_id;

-- Reset progress_to_next in assessment_details
UPDATE students
SET assessment_details = JSON_SET(
    assessment_details,
    '$.progress_to_next', 0
)
WHERE user_id = @student_user_id OR id = @student_user_id;

-- Clear activity scores
DELETE FROM student_activity_scores WHERE student_id = @student_user_id;

-- Clear post-test attempts
DELETE FROM user_post_test_attempts WHERE user_id = @student_user_id;

-- Verify the reset
SELECT 
    id,
    user_id,
    JSON_EXTRACT(assessment_details, '$.class_level') as class_level,
    JSON_EXTRACT(assessment_details, '$.progress_to_next') as progress,
    JSON_EXTRACT(assessment_data, '$.topic_scores.7.percentage') as topic_7_score,
    JSON_EXTRACT(assessment_data, '$.topic_scores.8.percentage') as topic_8_score
FROM students
WHERE user_id = @student_user_id OR id = @student_user_id;
