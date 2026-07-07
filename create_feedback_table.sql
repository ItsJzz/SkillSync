-- Create feedback table for SkillSync
-- This table stores all student feedback including concerns, satisfaction, requests, bug reports, and feature requests

CREATE TABLE IF NOT EXISTS student_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    student_email VARCHAR(100) NOT NULL,
    feedback_type ENUM('concern', 'satisfaction', 'feature_request', 'bug_report', 'ui_improvement', 'general') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    rating INT DEFAULT NULL COMMENT 'Rating from 1-5 for satisfaction feedback',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'reviewed', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    admin_response TEXT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    responded_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES login_credentials(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_feedback_type (feedback_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample feedback (optional - remove after testing)
-- INSERT INTO student_feedback (student_id, student_name, student_email, feedback_type, subject, message, priority) 
-- VALUES (1, 'Sample Student', 'student@example.com', 'feature_request', 'Add dark mode', 'I would love to have a dark mode option for the platform', 'medium');
