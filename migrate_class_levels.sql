-- Migration: Set class_level for all existing students
-- Run this ONCE to ensure all students have a proper class_level in assessment_details

-- Set class_level to 'Beginner' for all students who don't have it set
UPDATE students 
SET assessment_details = JSON_SET(
    COALESCE(assessment_details, '{}'),
    '$.class_level', 'Beginner'
)
WHERE JSON_EXTRACT(assessment_details, '$.class_level') IS NULL
   OR assessment_details IS NULL
   OR assessment_details = '';

-- For students who already have assessment_details but no class_level
UPDATE students 
SET assessment_details = JSON_SET(assessment_details, '$.class_level', 'Beginner')
WHERE JSON_EXTRACT(assessment_details, '$.class_level') IS NULL
  AND assessment_details IS NOT NULL 
  AND assessment_details != '';

-- Verify the update
SELECT 
    id,
    SUBSTRING(email, 1, 20) as email,
    JSON_EXTRACT(assessment_details, '$.class_level') as class_level,
    JSON_EXTRACT(assessment_details, '$.progress_to_next') as progress
FROM students
ORDER BY id;
