<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSync - Admin Login</title>
    <link rel="shortcut icon" href="LOGO.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .logo-section h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .logo-section p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:focus + .input-wrapper i {
            color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            color: #764ba2;
        }

        .security-notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.85rem;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="LOGO.png" alt="SkillSync Logo">
            <h1><i class="fas fa-shield-alt"></i> Admin Panel</h1>
            <p>SkillSync Administration</p>
        </div>

        <?php
        session_start();

        // Redirect if already logged in as admin
        if (isset($_SESSION['admin_id'])) {
            header("Location: admin_dashboard.php");
            exit();
        }

        $error_message = '';
        $success_message = '';

        if ($_POST && isset($_POST['login'])) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error_message = "Please enter both username and password.";
            } else {
                // Database connection
                require_once 'db_connect.php';
                
                if ($conn->connect_error) {
                    $error_message = "Database connection failed.";
                } else {
                    // Check admin credentials
                    $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM admin_users WHERE (username = ? OR email = ?) AND status = 'active'");
                    $stmt->bind_param("ss", $username, $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $admin = $result->fetch_assoc();
                        
                        // Verify password
                        if (password_verify($password, $admin['password'])) {
                            // Set session variables
                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_name'] = $admin['full_name'];
                            $_SESSION['admin_role'] = $admin['role'];
                            $_SESSION['admin_email'] = $admin['email'];

                            // Update last login
                            $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                            $updateStmt->bind_param("i", $admin['id']);
                            $updateStmt->execute();

                            // Log the login activity
                            $logStmt = $conn->prepare("INSERT INTO admin_activity_logs (admin_id, action, target_type, description, ip_address, user_agent) VALUES (?, 'login', 'system', 'Admin logged in', ?, ?)");
                            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            $logStmt->bind_param("iss", $admin['id'], $ip, $userAgent);
                            $logStmt->execute();

                            header("Location: admin_dashboard.php");
                            exit();
                        } else {
                            $error_message = "Invalid username or password.";
                        }
                    } else {
                        $error_message = "Invalid username or password.";
                    }
                    $stmt->close();
                    $conn->close();
                }
            }
        }
        ?>

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required placeholder="Enter your username or email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" name="login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In to Admin Panel
            </button>
        </form>

        <div class="back-link">
            <a href="Homepage.php"><i class="fas fa-arrow-left"></i> Back to Main Site</a>
        </div>

        <div class="security-notice">
            <i class="fas fa-info-circle"></i> This is a secure admin area. All activities are logged and monitored.
        </div>
    </div>
</body>
</html>