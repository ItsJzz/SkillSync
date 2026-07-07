-- Create table to store student progress history at each level
-- This preserves their achievements before promotion

CREATE TABLE IF NOT EXISTS student_progress_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    level VARCHAR(50) NOT NULL,
    overall_score DECIMAL(5,2),
    progress_percentage DECIMAL(5,2),
    assessment_data JSON,
    activity_scores JSON,
    post_test_scores JSON,
    achievements JSON,
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    INDEX idx_student_level (student_id, level)
);
