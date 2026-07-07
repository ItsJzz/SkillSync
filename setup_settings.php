<?php
// Database Setup for Settings Columns
// Run this file ONCE to add the required columns to your database

require_once 'db_connect.php';

echo "<h2>SkillSync - Settings Database Setup</h2>";
echo "<p>Adding required columns to login_credentials table...</p>";

// Add theme_preference column
$sql1 = "ALTER TABLE login_credentials ADD COLUMN theme_preference VARCHAR(10) DEFAULT 'light'";
try {
    if ($conn->query($sql1) === TRUE) {
        echo "<p style='color: green;'>✓ Successfully added 'theme_preference' column</p>";
    }
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p style='color: orange;'>⚠ Column 'theme_preference' already exists (skipped)</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'theme_preference': " . $e->getMessage() . "</p>";
    }
}

// Add email_notifications column
$sql2 = "ALTER TABLE login_credentials ADD COLUMN email_notifications TINYINT(1) DEFAULT 1";
try {
    if ($conn->query($sql2) === TRUE) {
        echo "<p style='color: green;'>✓ Successfully added 'email_notifications' column</p>";
    }
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p style='color: orange;'>⚠ Column 'email_notifications' already exists (skipped)</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'email_notifications': " . $e->getMessage() . "</p>";
    }
}

// Update existing records to have default values
$sql3 = "UPDATE login_credentials SET theme_preference = 'light' WHERE theme_preference IS NULL";
try {
    $conn->query($sql3);
    echo "<p style='color: green;'>✓ Updated existing records with default theme</p>";
} catch (Exception $e) {
    // Ignore errors here
}

$sql4 = "UPDATE login_credentials SET email_notifications = 1 WHERE email_notifications IS NULL";
try {
    $conn->query($sql4);
    echo "<p style='color: green;'>✓ Updated existing records with default notifications</p>";
} catch (Exception $e) {
    // Ignore errors here
}

echo "<hr>";
echo "<h3 style='color: green;'>✅ Setup Complete!</h3>";
echo "<p>You can now use the settings page.</p>";
echo "<p><a href='settings.php' style='background: linear-gradient(135deg, #4B8B6E, #6BAF92); color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; display: inline-block; margin-top: 10px;'>Go to Settings Page</a></p>";
echo "<p style='color: #999; font-size: 0.9rem; margin-top: 30px;'>Note: You can delete this file (setup_settings.php) after running it once.</p>";

$conn->close();
?>

<style>
body {
    font-family: 'Poppins', 'Arial', sans-serif;
    max-width: 700px;
    margin: 50px auto;
    padding: 30px;
    background: linear-gradient(135deg, #FFFFFF 0%, #F9F9F6 25%, #6BAF92 75%, #4B8B6E 100%);
    min-height: 100vh;
}
h2 {
    color: #4B8B6E;
    font-size: 2rem;
    margin-bottom: 10px;
}
h3 {
    margin-top: 20px;
}
p {
    line-height: 1.8;
    font-size: 1.1rem;
}
hr {
    border: none;
    border-top: 2px solid #6BAF92;
    margin: 30px 0;
}
</style>
