<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    require_once 'db_connect.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Get user info from login_credentials
    $stmt = $conn->prepare("SELECT * FROM login_credentials WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if student record already exists
        $checkStmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            // Create student record
            $insertStmt = $conn->prepare("
                INSERT INTO students (user_id, email, assessment_data, assessment_details, created_at)
                VALUES (?, ?, NULL, NULL, NOW())
            ");
            $insertStmt->bind_param("is", $user_id, $user['email']);
            
            if ($insertStmt->execute()) {
                $student_id = $insertStmt->insert_id;
                $_SESSION['student_id'] = $student_id;
                
                echo "<h2 style='color: green;'>✓ Student record created successfully!</h2>";
                echo "<p>Student ID: $student_id</p>";
                echo "<p><a href='pre_test.php?subject=OOP&onboarding=1'>Go to Pre-Test</a></p>";
                echo "<p><a href='debug_database.php'>Back to Debug Page</a></p>";
            } else {
                echo "<h2 style='color: red;'>✗ Error creating student record</h2>";
                echo "<p>" . $insertStmt->error . "</p>";
            }
            
            $insertStmt->close();
        } else {
            $student = $checkResult->fetch_assoc();
            $_SESSION['student_id'] = $student['id'];
            echo "<h2 style='color: orange;'>⚠ Student record already exists</h2>";
            echo "<p>Student ID: " . $student['id'] . "</p>";
            echo "<p><a href='pre_test.php?subject=OOP&onboarding=1'>Go to Pre-Test</a></p>";
            echo "<p><a href='debug_database.php'>Back to Debug Page</a></p>";
        }
        
        $checkStmt->close();
    } else {
        echo "<h2 style='color: red;'>✗ User not found in login_credentials</h2>";
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo "<h2 style='color: red;'>✗ Invalid request</h2>";
    echo "<p><a href='debug_database.php'>Go to Debug Page</a></p>";
}
?>
