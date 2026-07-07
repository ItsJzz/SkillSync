<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get admin info
$admin_id = $_SESSION['admin_id'];
$adminQuery = "SELECT * FROM admin_users WHERE id = ?";
$adminStmt = $conn->prepare($adminQuery);
$adminStmt->bind_param("i", $admin_id);
$adminStmt->execute();
$admin = $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'delete_student') {
            $student_id = intval($_POST['student_id']);
            
            // Begin transaction
            $conn->autocommit(FALSE);
            
            try {
                // Delete related records first
                $conn->query("DELETE FROM student_activity_scores WHERE student_id = $student_id");
                $conn->query("DELETE FROM student_tests WHERE student_id = $student_id");
                $conn->query("DELETE FROM student_video_progress WHERE student_id = $student_id");
                $conn->query("DELETE FROM user_profiles WHERE user_id = $student_id");
                $conn->query("DELETE FROM login_credentials WHERE id = $student_id");
                
                $conn->commit();
                $message = 'Student deleted successfully!';
                $message_type = 'success';
                
                // Log activity
                $activity = "Deleted student (ID: $student_id)";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $logQuery = "INSERT INTO admin_activity_logs (admin_id, activity, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("iss", $admin_id, $activity, $ip_address);
                $logStmt->execute();
                $logStmt->close();
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = 'Error deleting student: ' . $e->getMessage();
                $message_type = 'error';
            }
            
            $conn->autocommit(TRUE);
        }
        
        elseif ($action === 'reset_password') {
            $student_id = intval($_POST['student_id']);
            $new_password = password_hash('123456', PASSWORD_DEFAULT); // Default password
            
            $updateQuery = "UPDATE login_credentials SET password = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $new_password, $student_id);
            
            if ($updateStmt->execute()) {
                $message = 'Password reset successfully! New password: 123456';
                $message_type = 'success';
                
                // Log activity
                $activity = "Reset password for student (ID: $student_id)";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $logQuery = "INSERT INTO admin_activity_logs (admin_id, activity, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("iss", $admin_id, $activity, $ip_address);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $message = 'Error resetting password!';
                $message_type = 'error';
            }
            $updateStmt->close();
        }
    }
}

// Search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'username';
$order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

// Build query
$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = "WHERE lc.username LIKE ? OR lc.email LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam];
    $types = 'ss';
}

// Valid sort columns
$valid_sorts = ['username', 'email', 'created_at', 'avg_score', 'test_count'];
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'username';
}

// Get students with statistics
$studentsQuery = "
    SELECT 
        lc.id,
        lc.username,
        lc.email,
        lc.created_at,
        COALESCE(AVG(st.score), 0) as avg_score,
        COUNT(DISTINCT st.id) as test_count,
        COUNT(DISTINCT sas.id) as activity_count,
        MAX(st.attempt_date) as last_test_date
    FROM login_credentials lc
    LEFT JOIN student_tests st ON lc.id = st.student_id
    LEFT JOIN student_activity_scores sas ON lc.id = sas.student_id
    $whereClause
    GROUP BY lc.id, lc.username, lc.email, lc.created_at
    ORDER BY $sort_by $order
";

$stmt = $conn->prepare($studentsQuery);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSync - Manage Students</title>
    <link rel="shortcut icon" href="LOGO.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2c3e50;
            min-height: 100vh;
        }

        /* Header */
        .admin-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-left img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .header-left h1 {
            font-size: 1.5rem;
            color: #27ae60;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-btn {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 30px;
        }

        .page-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 40px;
        }

        /* Message Alert */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        /* Controls */
        .controls {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #27ae60;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .sort-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .sort-select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            font-size: 0.9rem;
        }

        .sort-btn {
            padding: 10px 15px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6c757d;
        }

        .sort-btn:hover {
            background: #e9ecef;
        }

        .sort-btn.active {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
        }

        /* Students Table */
        .students-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .student-count {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table th,
        .students-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f8f9fa;
        }

        .students-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }

        .students-table tr:hover {
            background: rgba(39, 174, 96, 0.05);
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .student-details h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 1rem;
        }

        .student-details p {
            margin: 0;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .stats-cell {
            text-align: center;
        }

        .stat-number {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .score-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }

        .score-excellent { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .score-good { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .score-poor { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .score-none { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .action-btn.reset {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .action-btn.delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .sort-controls {
                justify-content: center;
            }

            .students-table th,
            .students-table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="header-left">
            <img src="LOGO.png" alt="SkillSync">
            <h1><i class="fas fa-users"></i> Manage Students</h1>
        </div>
        <div class="header-right">
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Student Management</h1>
        <p class="page-subtitle">Manage student accounts, view performance, and handle administrative tasks</p>

        <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search students by username or email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="sort-controls">
                <select id="sortSelect" class="sort-select">
                    <option value="username" <?= $sort_by === 'username' ? 'selected' : '' ?>>Username</option>
                    <option value="email" <?= $sort_by === 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Join Date</option>
                    <option value="avg_score" <?= $sort_by === 'avg_score' ? 'selected' : '' ?>>Avg Score</option>
                    <option value="test_count" <?= $sort_by === 'test_count' ? 'selected' : '' ?>>Test Count</option>
                </select>
                <button class="sort-btn <?= $order === 'ASC' ? 'active' : '' ?>" onclick="toggleOrder('ASC')">
                    <i class="fas fa-sort-alpha-down"></i>
                </button>
                <button class="sort-btn <?= $order === 'DESC' ? 'active' : '' ?>" onclick="toggleOrder('DESC')">
                    <i class="fas fa-sort-alpha-up"></i>
                </button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="students-container">
            <div class="table-header">
                <h3 class="table-title">All Students</h3>
                <div class="student-count"><?= count($students) ?> students</div>
            </div>
            
            <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="fas fa-user-graduate"></i>
                <h3>No students found</h3>
                <p>No students match your search criteria.</p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Join Date</th>
                            <th>Tests Taken</th>
                            <th>Activities</th>
                            <th>Avg Score</th>
                            <th>Last Test</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">
                                        <?= strtoupper(substr($student['username'], 0, 1)) ?>
                                    </div>
                                    <div class="student-details">
                                        <h4><?= htmlspecialchars($student['username']) ?></h4>
                                        <p><?= htmlspecialchars($student['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($student['created_at'])) ?></td>
                            <td>
                                <div class="stats-cell">
                                    <div class="stat-number"><?= $student['test_count'] ?></div>
                                    <div class="stat-label">tests</div>
                                </div>
                            </td>
                            <td>
                                <div class="stats-cell">
                                    <div class="stat-number"><?= $student['activity_count'] ?></div>
                                    <div class="stat-label">activities</div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $score = $student['avg_score'];
                                $scoreClass = 'score-none';
                                if ($score > 0) {
                                    if ($score >= 80) $scoreClass = 'score-excellent';
                                    elseif ($score >= 60) $scoreClass = 'score-good';
                                    else $scoreClass = 'score-poor';
                                }
                                ?>
                                <span class="score-badge <?= $scoreClass ?>">
                                    <?= $score > 0 ? number_format($score, 1) . '%' : 'N/A' ?>
                                </span>
                            </td>
                            <td>
                                <?= $student['last_test_date'] ? date('M j, Y', strtotime($student['last_test_date'])) : 'Never' ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Reset password to 123456?')">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                        <button type="submit" class="action-btn reset" title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this student permanently? This cannot be undone!')">
                                        <input type="hidden" name="action" value="delete_student">
                                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                        <button type="submit" class="action-btn delete" title="Delete Student">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');

        searchInput.addEventListener('input', function() {
            updateURL();
        });

        sortSelect.addEventListener('change', function() {
            updateURL();
        });

        function toggleOrder(newOrder) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('order', newOrder);
            window.location.href = currentUrl.toString();
        }

        function updateURL() {
            const currentUrl = new URL(window.location);
            
            if (searchInput.value.trim()) {
                currentUrl.searchParams.set('search', searchInput.value.trim());
            } else {
                currentUrl.searchParams.delete('search');
            }
            
            currentUrl.searchParams.set('sort', sortSelect.value);
            
            window.location.href = currentUrl.toString();
        }

        // Auto-submit search after typing stops
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(updateURL, 500);
        });
    </script>
</body>
</html>