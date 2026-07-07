<?php
// Test if password hash matches empty string or other special cases
require_once 'db_connect.php';

echo "<h2>Password Hash Analysis for Problematic Accounts</h2>";

// Check specific accounts
$ids = [13, 18, 20, 21]; // The accounts you mentioned

foreach ($ids as $id) {
    $stmt = $conn->prepare("SELECT id, username, email, password FROM login_credentials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<h3>Account ID: {$id} - {$row['username']} ({$row['email']})</h3>";
        echo "<p><strong>Password Hash:</strong> " . htmlspecialchars($row['password']) . "</p>";
        echo "<p><strong>Hash Length:</strong> " . strlen($row['password']) . "</p>";
        
        $hash = $row['password'];
        
        // Test various inputs
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Test Input</th><th>password_verify() Result</th></tr>";
        
        $tests = [
            '' => 'Empty string',
            ' ' => 'Single space',
            'password' => 'password',
            '123456' => '123456',
            'a' => 'Single char "a"',
            'test' => 'test',
            $row['email'] => 'User email',
            $row['username'] => 'Username'
        ];
        
        foreach ($tests as $testPwd => $label) {
            $result = password_verify($testPwd, $hash);
            $color = $result ? 'red' : 'green';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($label) . "</td>";
            echo "<td style='color: {$color}; font-weight: bold;'>" . ($result ? 'TRUE - MATCHES!' : 'FALSE - No match') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Check if hash is valid bcrypt
        $info = password_get_info($hash);
        echo "<p><strong>Hash Info:</strong></p>";
        echo "<pre>" . print_r($info, true) . "</pre>";
        
        echo "<hr>";
    }
    $stmt->close();
}

$conn->close();

echo "<h3>Analysis:</h3>";
echo "<p>If any account shows TRUE for multiple different passwords, that hash is corrupted or was created incorrectly.</p>";
?>
