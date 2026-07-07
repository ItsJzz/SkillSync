<?php
// Simple test to see what register.php is actually saving
require_once 'db_connect.php';

echo "<h2>Recent Registrations Debug</h2>";
echo "<p>Showing last 5 registered accounts and what was saved:</p>";

$query = "SELECT id, username, email, password, created_at FROM login_credentials ORDER BY id DESC LIMIT 5";
$result = $conn->query($query);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Password (first 40 chars)</th>
<th>Password Length</th>
<th>Password IS Empty?</th>
<th>Password = Email?</th>
<th>Password = Username?</th>
<th>Created At</th>
</tr>";

while ($row = $result->fetch_assoc()) {
    $pwd = $row['password'];
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($pwd, 0, 40)) . "...</td>";
    echo "<td>" . strlen($pwd) . "</td>";
    echo "<td style='color:" . (empty($pwd) ? 'red' : 'green') . ";'>" . (empty($pwd) ? 'YES - PROBLEM!' : 'No') . "</td>";
    echo "<td style='color:" . ($pwd === $row['email'] ? 'red' : 'green') . ";'>" . ($pwd === $row['email'] ? 'YES - BUG!' : 'No') . "</td>";
    echo "<td style='color:" . ($pwd === $row['username'] ? 'red' : 'green') . ";'>" . ($pwd === $row['username'] ? 'YES - BUG!' : 'No') . "</td>";
    echo "<td>" . ($row['created_at'] ?? 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Possible Issues:</h3>";
echo "<ul>";
echo "<li>If 'Password IS Empty' shows YES - password field is blank</li>";
echo "<li>If 'Password = Email' shows YES - email is being saved in password field (column order bug)</li>";
echo "<li>If 'Password = Username' shows YES - username is being saved in password field (column order bug)</li>";
echo "</ul>";

$conn->close();
?>
