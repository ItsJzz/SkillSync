<?php
/**
 * Registration Debug Test
 * This will test if registration is saving passwords correctly
 * DELETE THIS FILE after testing!
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration Debug Test</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .warning { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
    input { padding: 8px; width: 100%; box-sizing: border-box; margin: 5px 0; }
    button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
  </style>
</head>
<body>
  <h1>🧪 Registration Debug Test</h1>
  <p class="warning">⚠️ This will create a TEST account. DELETE this file after testing!</p>

  <form method="POST">
    <h3>Create Test Account:</h3>
    <input type="text" name="test_name" placeholder="Test Name" required>
    <input type="email" name="test_email" placeholder="test@gmail.com" required>
    <input type="password" name="test_password" placeholder="Password (min 8 chars)" minlength="8" required>
    <button type="submit">Register Test Account</button>
  </form>

  <?php
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $testName = $_POST['test_name'];
      $testEmail = $_POST['test_email'];
      $testPassword = $_POST['test_password'];
      
      echo "<hr><h2>🔬 Registration Debug Results:</h2>";
      
      require_once 'db_connect.php';
      
      // Hash the password
      $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
      
      echo "<h3>1. Password Hashing Test:</h3>";
      echo "<table>";
      echo "<tr><th>Item</th><th>Value</th></tr>";
      echo "<tr><td>Original Password</td><td>" . htmlspecialchars($testPassword) . "</td></tr>";
      echo "<tr><td>Password Length</td><td>" . strlen($testPassword) . " characters</td></tr>";
      echo "<tr><td>Hashed Password (first 40 chars)</td><td>" . substr($hashedPassword, 0, 40) . "...</td></tr>";
      echo "<tr><td>Hash Length</td><td>" . strlen($hashedPassword) . " characters</td></tr>";
      echo "<tr><td>Hash Format</td><td>" . (substr($hashedPassword, 0, 4) === '$2y$' ? '✓ Bcrypt ($2y$)' : '✗ Wrong format') . "</td></tr>";
      echo "</table>";
      
      // Try to insert into database
      echo "<h3>2. Database Insert Test:</h3>";
      
      $stmt = $conn->prepare("INSERT INTO `login_credentials` (username, password, email, completed_preassessment) VALUES (?, ?, ?, 0)");
      $stmt->bind_param("sss", $testName, $hashedPassword, $testEmail);
      
      if ($stmt->execute()) {
          $insertedId = $stmt->insert_id;
          echo "<p class='success'>✓ Account created successfully! ID: $insertedId</p>";
          
          // Now fetch it back to verify
          echo "<h3>3. Verification - Reading Back from Database:</h3>";
          
          $verifyStmt = $conn->prepare("SELECT id, username, email, password FROM login_credentials WHERE id = ?");
          $verifyStmt->bind_param("i", $insertedId);
          $verifyStmt->execute();
          $verifyStmt->bind_result($id, $username, $email, $dbPassword);
          $verifyStmt->fetch();
          
          echo "<table>";
          echo "<tr><th>Field</th><th>Value</th></tr>";
          echo "<tr><td>ID</td><td>$id</td></tr>";
          echo "<tr><td>Username</td><td>" . htmlspecialchars($username) . "</td></tr>";
          echo "<tr><td>Email</td><td>" . htmlspecialchars($email) . "</td></tr>";
          echo "<tr><td>Password from DB (first 40 chars)</td><td>" . substr($dbPassword, 0, 40) . "...</td></tr>";
          echo "<tr><td>Password Length from DB</td><td>" . strlen($dbPassword) . " characters</td></tr>";
          echo "<tr><td>Matches what we tried to save?</td><td>" . ($dbPassword === $hashedPassword ? '✓ YES' : '✗ NO - PROBLEM!') . "</td></tr>";
          echo "</table>";
          
          $verifyStmt->close();
          
          // Test password verification
          echo "<h3>4. Password Verification Test:</h3>";
          echo "<p>Testing if password_verify works with the saved hash...</p>";
          
          $verifyResult = password_verify($testPassword, $dbPassword);
          
          if ($verifyResult === true) {
              echo "<p class='success'>✓ ✓ ✓ PASSWORD VERIFICATION WORKS!</p>";
              echo "<p class='success'>Login should work with this account.</p>";
          } else {
              echo "<p class='error'>✗ ✗ ✗ PASSWORD VERIFICATION FAILED!</p>";
              echo "<p class='error'>Something is wrong with how the password is being saved or retrieved!</p>";
          }
          
          // Test with wrong password
          echo "<h3>5. Wrong Password Test:</h3>";
          $wrongVerify = password_verify("wrongpassword123", $dbPassword);
          if ($wrongVerify === false) {
              echo "<p class='success'>✓ Correctly rejects wrong password</p>";
          } else {
              echo "<p class='error'>✗ BUG: Accepts wrong password!</p>";
          }
          
      } else {
          echo "<p class='error'>✗ Failed to insert: " . $stmt->error . "</p>";
      }
      
      $stmt->close();
      $conn->close();
      
      echo "<hr>";
      echo "<h3>🎯 Summary:</h3>";
      echo "<p>Check if password_verify returned TRUE in step 4. If not, there's a problem with:</p>";
      echo "<ul>";
      echo "<li>Database field size (must be at least VARCHAR(255))</li>";
      echo "<li>Character encoding issues</li>";
      echo "<li>Hash being truncated during storage</li>";
      echo "</ul>";
  }
  ?>

  <p class="warning">⚠️ <strong>Remember to DELETE this file and the test account after testing!</strong></p>
</body>
</html>
