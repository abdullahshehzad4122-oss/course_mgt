<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain');

try {
    $pdo->beginTransaction();
    
    // Find all duplicate page titles - keep lowest page_id
    $stmt = $pdo->query("SELECT page_title, MIN(page_id) as keep_id FROM sys_pages GROUP BY page_title");
    $keep = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $keep[$row['page_title']] = $row['keep_id'];
    }
    
    // Get all pages except the ones to keep
    $stmt = $pdo->query("SELECT page_id, page_title FROM sys_pages");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $delete_ids = [];
    foreach ($all as $page) {
        if ($keep[$page['page_title']] != $page['page_id']) {
            $delete_ids[] = $page['page_id'];
        }
    }
    
    if (!empty($delete_ids)) {
        $in = implode(',', $delete_ids);
        // Delete role_access for duplicate pages first
        $pdo->query("DELETE FROM role_access WHERE page_id IN ($in)");
        // Then delete the duplicate pages
        $pdo->query("DELETE FROM sys_pages WHERE page_id IN ($in)");
        echo "Removed " . count($delete_ids) . " duplicate page(s).\n";
    } else {
        echo "No duplicates found.\n";
    }

    // Also add view_attendance to sys_pages if not exists
    $check = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_url = 'modules/attendance/view_attendance.php'");
    $check->execute();
    $existing = $check->fetchColumn();
    
    if (!$existing) {
        // Get parent attendance page_id
        $parent_stmt = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_title = 'Attendance' AND parent_id = 0");
        $parent_stmt->execute();
        $att_parent_id = $parent_stmt->fetchColumn();
        
        if ($att_parent_id) {
            $ins = $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, ?, ?, ?)");
            $ins->execute(['View Attendance', 'modules/attendance/view_attendance.php', $att_parent_id, 'far fa-circle']);
            $view_att_id = $pdo->lastInsertId();
            
            $ra = $pdo->prepare("INSERT IGNORE INTO role_access (role_id, page_id, can_view) VALUES (?, ?, 1)");
            foreach ([1, 2, 3] as $role) {
                $ra->execute([$role, $view_att_id]);
            }
            echo "Added 'View Attendance' to sidebar.\n";
        }
    } else {
        echo "View Attendance already in sidebar.\n";
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
