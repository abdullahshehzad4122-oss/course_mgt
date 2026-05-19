<?php
require 'config/db.php';
try {
    $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, icon_class) VALUES ('Submit Assignment', 'student/submit_assignment.php', 'fas fa-upload')")->execute();
    $page_id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO role_access (role_id, page_id, can_view) VALUES (4, ?, 1)")->execute([$page_id]);
    echo "Success!";
} catch(Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $page_stmt = $pdo->query("SELECT page_id FROM sys_pages WHERE page_url = 'student/submit_assignment.php'");
        $pid = $page_stmt->fetchColumn();
        if ($pid) {
            $pdo->query("INSERT IGNORE INTO role_access (role_id, page_id, can_view) VALUES (4, $pid, 1)");
            echo "Fixed duplicate manually.";
        }
    } else {
        echo "Error: " . $e->getMessage();
    }
}
