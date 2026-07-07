<?php
// Test feedback submission
session_start();

echo "<h2>Feedback Submission Test</h2>";
echo "<hr>";

// Check session
echo "<h3>1. Session Check:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
    echo "✅ Username: " . $_SESSION['username'] . "<br>";
    echo "✅ Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "❌ Not logged in<br>";
}

// Check database connection
echo "<h3>2. Database Connection:</h3>";
require_once 'db_connect.php';
if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error;
} else {
    echo "✅ Connected to database<br>";
}

// Check if table exists
echo "<h3>3. Table Check:</h3>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'student_feedback'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "✅ student_feedback table exists<br>";
    
    // Get table structure
    $structure = $conn->query("DESCRIBE student_feedback");
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ student_feedback table does NOT exist<br>";
}

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>4. Form Submission Test:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Try to insert
    if (isset($_SESSION['user_id'])) {
        $student_id = $_SESSION['user_id'];
        $student_name = $_SESSION['username'];
        $student_email = $_SESSION['email'] ?? 'test@email.com';
        $feedback_type = $_POST['feedback_type'] ?? 'general';
        $subject = $_POST['subject'] ?? 'Test Subject';
        $message = $_POST['message'] ?? 'Test Message';
        $priority = $_POST['priority'] ?? 'medium';
        
        $sql = "INSERT INTO student_feedback 
                (student_id, student_name, student_email, feedback_type, subject, message, priority, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $student_id, $student_name, $student_email, $feedback_type, $subject, $message, $priority);
        
        if ($stmt->execute()) {
            echo "✅ Feedback inserted successfully! ID: " . $stmt->insert_id . "<br>";
        } else {
            echo "❌ Insert failed: " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
}

echo "<hr>";
echo "<h3>Test Form:</h3>";
?>

<form method="POST">
    <label>Feedback Type:</label>
    <select name="feedback_type" required>
        <option value="concern">Concern</option>
        <option value="satisfaction">Satisfaction</option>
        <option value="general">General</option>
    </select><br><br>
    
    <label>Subject:</label>
    <input type="text" name="subject" value="Test Subject" required><br><br>
    
    <label>Message:</label>
    <textarea name="message" required>This is a test message to verify the feedback system is working properly.</textarea><br><br>
    
    <label>Priority:</label>
    <select name="priority">
        <option value="low">Low</option>
        <option value="medium" selected>Medium</option>
        <option value="high">High</option>
    </select><br><br>
    
    <button type="submit">Submit Test Feedback</button>
</form>

<hr>
<p><a href="feedback.php">← Back to Feedback Page</a></p>

<?php
$conn->close();
?>
