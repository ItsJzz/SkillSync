<?php
// Debug script to check database and session state
session_start();

echo "<h2>Session Debug Info</h2>";
echo "<pre>";
echo "Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "Session student_id: " . (isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 'NOT SET') . "\n";
echo "All session data:\n";
print_r($_SESSION);
echo "</pre>";

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Debug Info</h2>";

// Check if students table exists
$result = $conn->query("SHOW TABLES LIKE 'students'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Students table exists</p>";
    
    // Check table structure
    $columns = $conn->query("DESCRIBE students");
    echo "<h3>Students Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        $highlight = in_array($col['Field'], ['assessment_data', 'assessment_details']) ? 'background: yellow;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if user exists in students table
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? OR user_id = ?");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>✓ User found in students table</p>";
            $student = $result->fetch_assoc();
            echo "<pre>";
            print_r($student);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>✗ User NOT found in students table</p>";
            echo "<p>Checking login_credentials table...</p>";
            
            $stmt2 = $conn->prepare("SELECT * FROM login_credentials WHERE id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            if ($result2->num_rows > 0) {
                echo "<p style='color: orange;'>⚠ User found in login_credentials but NOT in students table</p>";
                echo "<p><strong>SOLUTION:</strong> You need to create a student record for this user.</p>";
                
                $login = $result2->fetch_assoc();
                echo "<pre>";
                print_r($login);
                echo "</pre>";
                
                // Offer to create student record
                echo "<form method='post' action='create_student_record.php'>";
                echo "<input type='hidden' name='user_id' value='{$user_id}'>";
                echo "<button type='submit'>Create Student Record</button>";
                echo "</form>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ No user logged in (session user_id not set)</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Students table does NOT exist!</p>";
    echo "<p><strong>SOLUTION:</strong> Run the fix_students_table.sql script</p>";
}

// Check login_credentials table
$result = $conn->query("SHOW TABLES LIKE 'login_credentials'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ login_credentials table exists</p>";
} else {
    echo "<p style='color: red;'>✗ login_credentials table does NOT exist!</p>";
}

$conn->close();
?>
