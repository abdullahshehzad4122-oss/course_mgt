<?php
/**
 * Project-Wide Utility Functions
 * Implements Section 10: Maintainability through clean architecture
 */
require_once __DIR__ . '/breadcrumb.php';

/**
 * Check if a database table exists
 * @param PDO $pdo Database connection
 * @param string $table Table name to check
 * @return bool True if table exists
 */
function tableExists($pdo, $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    return $stmt->rowCount() > 0;
}