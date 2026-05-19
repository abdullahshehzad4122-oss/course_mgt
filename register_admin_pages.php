<?php
require 'config/db.php';

function registerPage($pdo, $title, $url, $roles) {
    try {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, icon_class) VALUES (?, ?, 'fas fa-tasks')");
        $stmt->execute([$title, $url]);
        $page_id = $pdo->lastInsertId();
    } catch(Exception $e) {
        $stmt = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_url = ?");
        $stmt->execute([$url]);
        $page_id = $stmt->fetchColumn();
    }

    if ($page_id) {
        foreach ($roles as $role) {
            try {
                $pdo->prepare("INSERT INTO role_access (role_id, page_id, can_view) VALUES (?, ?, 1)")->execute([$role, $page_id]);
                echo "Added $url for role $role\n";
            } catch(Exception $e) {
                // Ignore duplicates
            }
        }
    }
}

registerPage($pdo, 'View Submissions', 'modules/assignments/view_submissions.php', [1, 2, 3]);
registerPage($pdo, 'Grade Submission', 'modules/assignments/grade_submission.php', [1, 2, 3]);
echo "Pages setup complete.\n";
