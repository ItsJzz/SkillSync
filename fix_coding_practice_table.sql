-- Fix: Remove foreign key constraint and recreate table
DROP TABLE IF EXISTS coding_practice_completed;

CREATE TABLE IF NOT EXISTS coding_practice_completed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    problem_id INT NOT NULL,
    language VARCHAR(20) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (user_id, problem_id, language),
    INDEX idx_user_language (user_id, language)
);
