<?php
/**
 * Login Debug Tester
 * Use this to test if password_verify is working correctly on Hostinger
 * DELETE THIS FILE after testing for security!
 */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Debug Test</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .warning { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
  </style>
</head>
<body>
  <h1>🔍 Login Debug Tester</h1>
  <p class="warning">⚠️ <strong>SECURITY WARNING:</strong> Delete this file after testing!</p>

  <form method="POST">
    <p><strong>Test Login:</strong></p>
    <input type="email" name="test_email" placeholder="Email" required style="padding: 8px; width: 300px;">
    <input type="password" name="test_password" placeholder="Password" required style="padding: 8px; width: 300px;">
    <button type="submit" style="padding: 8px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">Test Login</button>
  </form>

  <?php
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['test_email'])) {
      $testEmail = $_POST['test_email'];
      $testPassword = $_POST['test_password'];
      
      echo "<hr><h2>🔬 Debug Results:</h2>";
      
      // PHP Version Info
      echo "<h3>1. PHP Environment:</h3>";
      echo "<table>";
      echo "<tr><th>Item</th><th>Value</th></tr>";
      echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
      echo "<tr><td>password_hash available</td><td>" . (function_exists('password_hash') ? '✓ Yes' : '✗ No') . "</td></tr>";
      echo "<tr><td>password_verify available</td><td>" . (function_exists('password_verify') ? '✓ Yes' : '✗ No') . "</td></tr>";
      echo "</table>";
      
      // Database connection test
      echo "<h3>2. Database Connection:</h3>";
      require_once 'db_connect.php';
      
      if ($conn) {
          echo "<p class='success'>✓ Database connected successfully</p>";
          
          // Fetch user data
          echo "<h3>3. User Lookup:</h3>";
          $stmt = $conn->prepare("SELECT id, username, email, password, role FROM login_credentials WHERE email = ?");
          $stmt->bind_param("s", $testEmail);
          $stmt->execute();
          $stmt->store_result();
          
          if ($stmt->num_rows > 0) {
              echo "<p class='success'>✓ User found in database</p>";
              
              $stmt->bind_result($id, $username, $email, $hashedPassword, $role);
              $stmt->fetch();
              
              echo "<table>";
              echo "<tr><th>Field</th><th>Value</th></tr>";
              echo "<tr><td>ID</td><td>" . htmlspecialchars($id) . "</td></tr>";
              echo "<tr><td>Username</td><td>" . htmlspecialchars($username) . "</td></tr>";
              echo "<tr><td>Email</td><td>" . htmlspecialchars($email) . "</td></tr>";
              echo "<tr><td>Role</td><td>" . htmlspecialchars($role) . "</td></tr>";
              echo "<tr><td>Password Hash (first 40 chars)</td><td>" . htmlspecialchars(substr($hashedPassword, 0, 40)) . "...</td></tr>";
              echo "<tr><td>Password Hash Length</td><td>" . strlen($hashedPassword) . " chars</td></tr>";
              echo "<tr><td>Hash Format</td><td>" . (substr($hashedPassword, 0, 4) === '$2y$' ? '✓ Bcrypt ($2y$)' : '✗ Unknown format') . "</td></tr>";
              echo "</table>";
              
              // Password verification test
              echo "<h3>4. Password Verification Test:</h3>";
              echo "<p><strong>Testing password:</strong> " . str_repeat('*', strlen($testPassword)) . " (" . strlen($testPassword) . " characters)</p>";
              
              $verifyResult = password_verify($testPassword, $hashedPassword);
              
              if ($verifyResult === true) {
                  echo "<p class='success'>✓ ✓ ✓ PASSWORD MATCH! password_verify() returned TRUE</p>";
                  echo "<p class='success'>This password is CORRECT for this user.</p>";
              } elseif ($verifyResult === false) {
                  echo "<p class='error'>✗ ✗ ✗ PASSWORD MISMATCH! password_verify() returned FALSE</p>";
                  echo "<p class='error'>This password is INCORRECT for this user.</p>";
              } else {
                  echo "<p class='error'>⚠️ Unexpected result from password_verify(): " . var_export($verifyResult, true) . "</p>";
              }
              
              // Additional verification with manual hash test
              echo "<h3>5. Manual Hash Test:</h3>";
              $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
              echo "<p>New hash of entered password: " . substr($newHash, 0, 40) . "...</p>";
              echo "<p>If you use this password to register a NEW account, it should work.</p>";
              
          } else {
              echo "<p class='error'>✗ User NOT found in database</p>";
              echo "<p>Email '<strong>" . htmlspecialchars($testEmail) . "</strong>' does not exist in login_credentials table.</p>";
          }
          
          $stmt->close();
          $conn->close();
          
      } else {
          echo "<p class='error'>✗ Database connection failed</p>";
      }
  }
  ?>

  <hr>
  <h3>🎯 What to Check:</h3>
  <ol>
    <li>Make sure PHP version is 5.5 or higher (for password_hash/verify)</li>
    <li>Password hash should be 60 characters and start with $2y$</li>
    <li>password_verify() should return TRUE for correct password, FALSE for incorrect</li>
    <li>If verify returns FALSE for a password you know is correct, the hash in DB might be corrupted</li>
  </ol>

  <p class="warning">⚠️ <strong>Remember to DELETE this file after testing!</strong></p>
</body>
</html>
