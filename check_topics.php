<?php
require 'db_connect.php';
$result = $conn->query('SELECT id, name FROM topics ORDER BY id');
echo "<h2>All Topics in Database:</h2>";
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ' => ' . $row['name'] . '<br>';
}
?>
