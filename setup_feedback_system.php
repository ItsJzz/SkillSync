<?php
// Quick Database Setup for Feedback System
require_once 'db_connect.php';

echo "<h2>SkillSync Feedback System - Database Setup</h2>";
echo "<hr>";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'student_feedback'");

if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "<p style='color: green;'>✅ <strong>student_feedback</strong> table already exists!</p>";
    
    // Get table info
    $countQuery = $conn->query("SELECT COUNT(*) as total FROM student_feedback");
    $count = $countQuery->fetch_assoc()['total'];
    echo "<p>📊 Total feedback entries: <strong>$count</strong></p>";
    
    echo "<p><a href='feedback.php'>Go to Student Feedback Page →</a></p>";
    echo "<p><a href='admin/view_feedback.php'>Go to Admin Feedback Management →</a></p>";
    
} else {
    echo "<p style='color: orange;'>⚠️ <strong>student_feedback</strong> table does NOT exist yet.</p>";
    echo "<p>Click the button below to create it:</p>";
    
    if (isset($_POST['create_table'])) {
        // Create the table
        $sql = "CREATE TABLE IF NOT EXISTS student_feedback (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green; padding: 15px; background: #d4edda; border-radius: 5px;'>";
            echo "✅ <strong>SUCCESS!</strong> The student_feedback table has been created successfully!";
            echo "</p>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li><a href='feedback.php'>Test Student Feedback Page →</a></li>";
            echo "<li><a href='admin/view_feedback.php'>Test Admin Feedback Management →</a></li>";
            echo "</ol>";
        } else {
            echo "<p style='color: red; padding: 15px; background: #f8d7da; border-radius: 5px;'>";
            echo "❌ <strong>ERROR:</strong> " . $conn->error;
            echo "</p>";
        }
    } else {
        echo "<form method='post'>";
        echo "<button type='submit' name='create_table' style='padding: 15px 30px; background: #27ae60; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
        echo "🚀 Create student_feedback Table";
        echo "</button>";
        echo "</form>";
    }
}

echo "<hr>";
echo "<h3>System Information:</h3>";
echo "<ul>";
echo "<li><strong>Database:</strong> " . $conn->server_info . "</li>";
echo "<li><strong>Database Name:</strong> skillsync</li>";
echo "<li><strong>Connection:</strong> <span style='color: green;'>✅ Active</span></li>";
echo "</ul>";

$conn->close();
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2 {
    color: #2c3e50;
}
a {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
}
a:hover {
    text-decoration: underline;
}
</style>
