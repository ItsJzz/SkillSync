<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #e6f3ff; padding: 15px; border: 1px solid #99ccff; border-radius: 5px; }
        .error { background: #ffe6e6; padding: 15px; border: 1px solid #ff9999; border-radius: 5px; }
        .success { background: #e6ffe6; padding: 15px; border: 1px solid #99ff99; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Session and Login Status Check</h2>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="success">
            <h3>✅ User is logged in</h3>
            <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?></p>
            <p><strong>Session Data:</strong></p>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
    <?php else: ?>
        <div class="error">
            <h3>❌ User is NOT logged in</h3>
            <p>The ML assessment requires a logged-in user. Please log in first.</p>
            <p><a href="login.php">Go to Login Page</a></p>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Database Connection Test</h3>
        <?php
        try {
            require_once 'db_connect.php';
            if ($conn->connect_error) {
                echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
            } else {
                echo "<p style='color: green;'>✅ Database connection successful</p>";
                
                // Check if subjects table has data
                $result = $conn->query("SELECT code FROM subjects LIMIT 3");
                if ($result && $result->num_rows > 0) {
                    echo "<p style='color: green;'>✅ Subjects table has data:</p>";
                    echo "<ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li>" . $row['code'] . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Subjects table is empty or missing</p>";
                }
                
                $conn->close();
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="info">
        <h3>Upload Directory Check</h3>
        <?php
        $upload_dir = "uploads/ml_assessments/";
        if (is_dir($upload_dir)) {
            echo "<p style='color: green;'>✅ Upload directory exists: $upload_dir</p>";
            if (is_writable($upload_dir)) {
                echo "<p style='color: green;'>✅ Upload directory is writable</p>";
            } else {
                echo "<p style='color: red;'>❌ Upload directory is not writable</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Upload directory does not exist: $upload_dir</p>";
            if (mkdir($upload_dir, 0777, true)) {
                echo "<p style='color: green;'>✅ Created upload directory</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create upload directory</p>";
            }
        }
        ?>
    </div>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="info">
            <h3>Quick Login for Testing</h3>
            <p>You can quickly log in for testing purposes:</p>
            <form method="post" action="login.php">
                <p>
                    <label>Username:</label><br>
                    <input type="text" name="username" value="admin" required>
                </p>
                <p>
                    <label>Password:</label><br>
                    <input type="password" name="password" value="admin" required>
                </p>
                <p>
                    <button type="submit">Login</button>
                </p>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>