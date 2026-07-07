-- Create table to track level promotion test attempts
-- This ensures students must pass a test before moving to the next level

CREATE TABLE IF NOT EXISTS level_promotion_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    from_level ENUM('Beginner', 'Intermediate') NOT NULL,
    to_level ENUM('Intermediate', 'Expert') NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    passed BOOLEAN NOT NULL,
    test_data JSON,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    INDEX idx_student_level (student_id, from_level)
);
