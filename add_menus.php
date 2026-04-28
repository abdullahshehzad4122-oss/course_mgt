<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain');

try {
    $pdo->beginTransaction();
    
    // Add "View Assignments" under Assignments (parent_id = 13)
    $check = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_url = 'modules/assignments/view_assignments.php'");
    $check->execute();
    if (!$check->fetchColumn()) {
        $ins = $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, ?, ?, ?)");
        $ins->execute(['View Assignments', 'modules/assignments/view_assignments.php', 13, 'far fa-circle']);
        $page_id = $pdo->lastInsertId();
        
        // Give access to roles 1, 2, 3
        $ra = $pdo->prepare("INSERT IGNORE INTO role_access (role_id, page_id, can_view) VALUES (?, ?, 1)");
        foreach ([1, 2, 3] as $role) {
            $ra->execute([$role, $page_id]);
        }
        echo "Added 'View Assignments' to sidebar (page_id=$page_id).\n";
    } else {
        echo "'View Assignments' already exists in sidebar.\n";
    }
    
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
