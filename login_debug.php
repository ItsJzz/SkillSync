<?php
session_start();

// TEMPORARY DEBUG LOGIN - DELETE THIS FILE AFTER FIXING!
// This version shows detailed debug information about what's happening during login
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Debug Login - SkillSync</title>
  <link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <style>
    .debug { background: #f8f9fa; border: 2px solid #dee2e6; padding: 15px; margin: 10px 0; font-family: monospace; }
    .debug h4 { margin-top: 0; color: #495057; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
  </style>
</head>
<body>
  <div class="login-popup" style="margin:50px auto;max-width:600px;">
    <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:10px;">
      <h1 style="margin:0;font-size:23px;">DEBUG Login to SkillSync</h1>
      <img src="LOGO.png" alt="Logo" style="width:60px;height:60px;">
    </div>
    <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;">
      ⚠️ This is a DEBUG version. DELETE this file after fixing the issue!
    </p>
    <form method="POST" action="login_debug.php">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" class="btn">Login (Debug Mode)</button>
    </form>
    <div class="footer">
      Don't have an account? <a href="register.php">Register</a>
    </div>
    <div style="text-align:center; margin-top:10px;">
      <a href="Homepage.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
    </div>
  </div>

  <?php
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $email = $_POST['email'];
      $password = $_POST['password'];

      echo "<div class='debug'>";
      echo "<h4>🔍 LOGIN DEBUG INFORMATION:</h4>";
      echo "<p><strong>Email entered:</strong> " . htmlspecialchars($email) . "</p>";
      echo "<p><strong>Password length:</strong> " . strlen($password) . " characters</p>";
      echo "<hr>";

      // Database connection
      require_once 'db_connect.php';
      
      echo "<p class='success'>✓ Database connected</p>";

      // Use prepared statement to fetch user with role
      $stmt = $conn->prepare("SELECT id, username, password, role FROM login_credentials WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();

      echo "<p><strong>Query executed, rows found:</strong> " . $stmt->num_rows . "</p>";

      if ($stmt->num_rows > 0) {
          $stmt->bind_result($id, $username, $hashedPassword, $role);
          $stmt->fetch();

          echo "<p class='success'>✓ User found in database</p>";
          echo "<p><strong>User ID:</strong> $id</p>";
          echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
          echo "<p><strong>Role:</strong> " . htmlspecialchars($role) . "</p>";
          echo "<p><strong>Password hash (first 40 chars):</strong> " . substr($hashedPassword, 0, 40) . "...</p>";
          echo "<p><strong>Password hash length:</strong> " . strlen($hashedPassword) . " characters</p>";
          echo "<p><strong>Hash format:</strong> " . (substr($hashedPassword, 0, 4) === '$2y$' ? '✓ Bcrypt' : '✗ Unknown') . "</p>";
          echo "<hr>";

          // Check if password is empty
          if (empty($hashedPassword)) {
              echo "<p class='error'>✗ ERROR: Password hash is EMPTY in database!</p>";
              echo "<p>This user needs password reset.</p>";
          } else {
              echo "<p><strong>Testing password_verify()...</strong></p>";
              
              // Test password verification
              $verifyResult = password_verify($password, $hashedPassword);
              
              echo "<p><strong>password_verify() returned:</strong> " . var_export($verifyResult, true) . "</p>";
              
              if ($verifyResult === true) {
                  echo "<p class='success'>✓ ✓ ✓ PASSWORD MATCH!</p>";
                  
                  // Login successful - password verified correctly
                  $_SESSION['user_id'] = $id;
                  $_SESSION['username'] = $username;
                  $_SESSION['email'] = $email;
                  $_SESSION['role'] = $role;

                  echo "<p class='success'>✓ Session variables set</p>";
                  echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
                  echo "<p><strong>\$_SESSION['user_id']:</strong> " . $_SESSION['user_id'] . "</p>";
                  echo "<p><strong>\$_SESSION['username']:</strong> " . $_SESSION['username'] . "</p>";
                  echo "<p><strong>\$_SESSION['role']:</strong> " . $_SESSION['role'] . "</p>";
                  echo "<hr>";

                  // Check user role and redirect accordingly
                  if ($role === 'admin') {
                      echo "<p class='success'>User is ADMIN - would redirect to admin/admin_dashboard.php</p>";
                      echo "<p><a href='admin/admin_dashboard.php' class='btn'>Go to Admin Dashboard</a></p>";
                  } else {
                      // Student login - check pre-assessment completion
                      $preAssessStmt = $conn->prepare("SELECT completed_preassessment FROM login_credentials WHERE id = ?");
                      $preAssessStmt->bind_param("i", $id);
                      $preAssessStmt->execute();
                      $preAssessResult = $preAssessStmt->get_result()->fetch_assoc();
                      $preAssessStmt->close();

                      echo "<p><strong>Completed pre-assessment:</strong> " . $preAssessResult['completed_preassessment'] . "</p>";

                      if ($preAssessResult['completed_preassessment'] == 0) {
                          echo "<p class='success'>New student - would redirect to pre_assessment_onboarding.php</p>";
                          echo "<p><a href='pre_assessment_onboarding.php' class='btn'>Go to Pre-Assessment</a></p>";
                      } else {
                          echo "<p class='success'>Existing student - would redirect to student_dashboard.php</p>";
                          echo "<p><a href='student_dashboard.php' class='btn'>Go to Student Dashboard</a></p>";
                      }
                  }
                  
                  echo "<p style='background: #d4edda; padding: 10px; margin-top: 20px;'><strong>✓ LOGIN SUCCESSFUL!</strong> Click the button above to continue.</p>";
                  
              } elseif ($verifyResult === false) {
                  echo "<p class='error'>✗ ✗ ✗ PASSWORD MISMATCH!</p>";
                  echo "<p class='error'>The password you entered does NOT match the hash in the database.</p>";
                  
                  // Try to provide more info
                  echo "<hr>";
                  echo "<p><strong>Troubleshooting:</strong></p>";
                  echo "<p>Creating a NEW hash of the entered password: " . substr(password_hash($password, PASSWORD_DEFAULT), 0, 40) . "...</p>";
                  echo "<p>If you just registered, the password hash might not have been saved correctly.</p>";
              } else {
                  echo "<p class='error'>⚠️ UNEXPECTED: password_verify() returned something other than true/false!</p>";
                  echo "<p>Returned value: " . var_export($verifyResult, true) . "</p>";
              }
          }
      } else {
          echo "<p class='error'>✗ Email not found in database!</p>";
          echo "<p>No user exists with email: <strong>" . htmlspecialchars($email) . "</strong></p>";
      }

      $stmt->close();
      $conn->close();
      
      echo "</div>";
  }
  ?>
</body>
</html>
