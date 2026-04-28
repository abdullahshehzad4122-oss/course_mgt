<?php
/**
 * Secure Logout Implementation
 * Clears all session data as per Section 10 security requirements
 */
session_start();

// Log logout action
if (isset($_SESSION['user_id'])) {
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
header('Location: index.php');
exit;