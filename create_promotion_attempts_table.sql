-- Create table for level promotion test attempts with detailed analysis
CREATE TABLE IF NOT EXISTS level_promotion_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    current_level VARCHAR(50) NOT NULL,
    target_level VARCHAR(50) NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    total_questions INT NOT NULL,
    correct_count INT NOT NULL,
    answers_data TEXT NOT NULL,
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_student_passed (student_id, passed),
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
