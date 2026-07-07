<?php
// Admin role setup script
require_once 'db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Setting up admin role...\n";

// Check if role column exists
$checkColumn = "SHOW COLUMNS FROM login_credentials LIKE 'role'";
$result = $conn->query($checkColumn);

if ($result->num_rows == 0) {
    // Add role column
    $addColumn = "ALTER TABLE login_credentials ADD COLUMN role ENUM('student', 'admin') DEFAULT 'student'";
    if ($conn->query($addColumn)) {
        echo "✓ Added role column to login_credentials table\n";
    } else {
        echo "✗ Error adding role column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Role column already exists\n";
}

// Check if admin user exists
$checkAdmin = "SELECT * FROM login_credentials WHERE username = 'admin'";
$result = $conn->query($checkAdmin);

if ($result->num_rows == 0) {
    // Insert admin user
    $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
    $insertAdmin = "INSERT INTO login_credentials (username, email, password, role) VALUES ('admin', 'admin@skillsync.com', '$adminPassword', 'admin')";
    
    if ($conn->query($insertAdmin)) {
        echo "✓ Created admin user (username: admin, password: admin)\n";
    } else {
        echo "✗ Error creating admin user: " . $conn->error . "\n";
    }
} else {
    echo "✓ Admin user already exists\n";
}

// Create user_profiles table if it doesn't exist
$createProfilesTable = "
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100),
    nickname VARCHAR(50),
    bio TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES login_credentials(id) ON DELETE CASCADE
)";

if ($conn->query($createProfilesTable)) {
    echo "✓ Created/verified user_profiles table\n";
} else {
    echo "✗ Error creating user_profiles table: " . $conn->error . "\n";
}

$conn->close();
echo "\nAdmin setup completed successfully!\n";
echo "You can now login with:\n";
echo "Username: admin\n";
echo "Password: admin\n";
?>