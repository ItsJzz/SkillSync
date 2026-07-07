<?php
/**
 * Fix correct_option column in questions table
 * Convert from full text answers to option letters (A, B, C)
 */

require_once 'db_connect.php';

echo "<h2>Fixing correct_option values in questions table</h2>";
echo "<pre>";

// Get all questions
$query = "SELECT id, correct_option, option_a, option_b, option_c FROM questions ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching questions: " . mysqli_error($conn));
}

$total = 0;
$fixed = 0;
$already_correct = 0;
$errors = [];

while ($row = mysqli_fetch_assoc($result)) {
    $total++;
    $id = $row['id'];
    $current_correct = $row['correct_option'];
    
    // If already A, B, or C, skip
    if (in_array($current_correct, ['A', 'B', 'C'])) {
        $already_correct++;
        echo "Question $id: Already correct ($current_correct)\n";
        continue;
    }
    
    // Determine which option matches the current correct_option text
    $new_correct = null;
    if ($row['option_a'] === $current_correct) {
        $new_correct = 'A';
    } elseif ($row['option_b'] === $current_correct) {
        $new_correct = 'B';
    } elseif ($row['option_c'] === $current_correct) {
        $new_correct = 'C';
    }
    
    if ($new_correct === null) {
        $errors[] = "Question $id: Could not match '$current_correct' to any option";
        echo "Question $id: ERROR - No matching option found\n";
        echo "  Current: $current_correct\n";
        echo "  Option A: {$row['option_a']}\n";
        echo "  Option B: {$row['option_b']}\n";
        echo "  Option C: {$row['option_c']}\n";
        continue;
    }
    
    // Update the correct_option
    $update = "UPDATE questions SET correct_option = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, 'si', $new_correct, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $fixed++;
        echo "Question $id: Fixed '$current_correct' → '$new_correct'\n";
    } else {
        $errors[] = "Question $id: Update failed - " . mysqli_error($conn);
        echo "Question $id: ERROR updating - " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "\n=== SUMMARY ===\n";
echo "Total questions: $total\n";
echo "Already correct: $already_correct\n";
echo "Fixed: $fixed\n";
echo "Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n=== ERRORS ===\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n</pre>";
echo "<p><a href='pre_test.php'>Go to Pre-Test</a> | <a href='pre_test_results.php'>View Results</a></p>";

mysqli_close($conn);
?>
