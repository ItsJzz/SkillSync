-- Simple Coding Practice Table
-- Tracks which problems users have completed in each language

CREATE TABLE IF NOT EXISTS coding_practice_completed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    problem_id INT NOT NULL,
    language VARCHAR(20) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (user_id, problem_id, language),
    FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Drop old tables if they exist
DROP TABLE IF EXISTS coding_practice_submissions;
DROP TABLE IF EXISTS coding_practice_progress;
DROP TABLE IF EXISTS coding_practice_stats;
