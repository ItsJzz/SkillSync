-- Fix students table to add missing columns for assessment data
-- Run this script to add the required columns

-- Check if students table exists, if not create it
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `assessment_data` longtext DEFAULT NULL,
  `assessment_details` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add assessment_data column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `assessment_data` LONGTEXT NULL AFTER `year_level`;

-- Add assessment_details column if it doesn't exist
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `assessment_details` LONGTEXT NULL AFTER `assessment_data`;

-- Verify the structure
DESCRIBE students;
