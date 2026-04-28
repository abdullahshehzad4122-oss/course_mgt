<?php
/**
 * Role-Based Access Control (RBAC) Implementation
 * FIXED: Uses GLOBAL $pdo as required by layered architecture (Section 6.2)
 */
function checkAccess($required_permission = 'view') {
    // CRITICAL FIX: Access GLOBAL $pdo
    global $pdo;
    
    // Get current page URL relative to the application root (Section 9.4)
    // This fixes access control for pages in subdirectories.
    $app_root_dir = str_replace('\\', '/', dirname(__DIR__)); // e.g., D:/xampp/htdocs/course_mgt
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']); // e.g., D:/xampp/htdocs
    
    $app_base_path = str_replace($doc_root, '', $app_root_dir); // e.g., /course_mgt
    
    // Verify session exists (Section 9.4)
    if (!isset($_SESSION['user_id'])) {
        header("Location: $app_base_path/index.php?error=not_logged_in");
        exit;
    }

    $script_path = $_SERVER['PHP_SELF']; // e.g., /course_mgt/modules/courses/view_courses.php

    // Remove base path and leading slash to get the relative URL for DB matching
    $current_page = ltrim(substr($script_path, strlen($app_base_path)), '/');
    
    // Database check (Section 8 role_access table)
    $query = "SELECT ra.can_view, ra.can_edit 
              FROM role_access ra
              JOIN sys_pages sp ON ra.page_id = sp.page_id
              WHERE ra.role_id = ? AND sp.page_url = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['role_id'], $current_page]);
    $access = $stmt->fetch(PDO::FETCH_ASSOC);

    // Permission validation (Section 9.4)
    if (!$access) {
        logAccessViolation($current_page);
        header("HTTP/1.0 403 Forbidden");
        exit("Access denied. Contact administrator.");
    }
    
    if ($required_permission === 'edit' && !$access['can_edit']) {
        logAccessViolation($current_page, 'edit attempt');
        header("HTTP/1.0 403 Forbidden");
        exit("Edit access denied.");
    }
}

function logAccessViolation($page, $type = 'view') {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) return;
    
    $log_query = "INSERT INTO access_logs (user_id, page, access_type, timestamp) 
                  VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($log_query);
    $stmt->execute([$_SESSION['user_id'], $page, $type]);
}
?>