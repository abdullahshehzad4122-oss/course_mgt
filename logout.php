<?php
/**
 * Secure Logout Implementation
 * Clears all session data as per Section 10 security requirements
 */
session_start();

$was_student = false;
// Log logout action
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 4) {
        $was_student = true;
    }
    require_once 'config/db.php';
    $log_query = "INSERT INTO access_logs (user_id, page, access_type, timestamp) 
                  VALUES (?, 'logout', 'success', NOW())";
    $log_stmt = $pdo->prepare($log_query);
    $log_stmt->execute([$_SESSION['user_id']]);
}

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

// Regenerate session ID
session_start();
session_regenerate_id(true);

// Redirect to login
if ($was_student) {
    header('Location: student/login.php');
} else {
    header('Location: index.php');
}
exit;