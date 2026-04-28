<?php
/**
 * Database Connection Configuration
 * FIXED: Creates GLOBAL $pdo variable required by Section 6.2 layer dependencies
 */
$host = 'localhost';
$dbname = 'course_mgt';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Define BASE_URL (Path Error Fix)
    if (!defined('BASE_URL')) {
        define('BASE_URL', '/course_mgt');
    }
    
    // CRITICAL FIX: Declare $pdo as GLOBAL
    global $pdo;
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Verify connection works
    $pdo->query("SELECT 1")->fetchColumn();
} catch (PDOException $e) {
    // Academic-compliant error (Section 10)
    die("Database connection failed. Please verify:
         - Database 'course_mgt' exists
         - XAMPP MySQL service is running
         - Credentials in config/db.php are correct
         (Error: " . $e->getMessage() . ")");
}
?>