<?php
/**
 * Secure Authentication Processor
 * Implements FR-1 with PDO prepared statements (Section 10 security requirement)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

// Sanitize inputs
$username = trim($_POST['username']);
$password = $_POST['password'];

// Validate input
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username and password required";
    header('Location: index.php');
    exit;
}

// Database query with prepared statement (Section 10)
$query = "SELECT u.user_id, u.role_id, u.password_hash, r.role_name 
          FROM users u
          JOIN sys_roles r ON u.role_id = r.role_id
          WHERE u.username = ?";
          
$stmt = $pdo->prepare($query);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify user exists and password matches
if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = "Invalid username or password";
    header('Location: index.php');
    exit;
} elseif ($user['role_id'] == 4) {
    $_SESSION['login_error'] = "Students must use the dedicated Student Portal to log in.";
    header('Location: index.php');
    exit;
}

// Start secure session
session_regenerate_id(true);
$_SESSION = [
    'user_id' => $user['user_id'],
    'role_id' => $user['role_id'],
    'role_name' => $user['role_name'],
    'logged_in' => true,
    'last_activity' => time()
];

// Log successful login (Section 10 security)
$log_query = "INSERT INTO access_logs (user_id, page, access_type, timestamp) 
              VALUES (?, 'login', 'success', NOW())";
$log_stmt = $pdo->prepare($log_query);
$log_stmt->execute([$user['user_id']]);

header('Location: dashboard.php');
exit;