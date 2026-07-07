<?php
session_start();

// Mark onboarding as completed
$_SESSION['onboarding_completed'] = true;

// Return success response
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Onboarding completed']);
?>
