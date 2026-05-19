<?php
require_once __DIR__ . '/config/db.php';

// First delete all role_id = 4 from role_access
$pdo->exec("DELETE FROM role_access WHERE role_id = 4");

// Insert student pages if they don't exist
$student_pages = [
    ['Student Dashboard', 'student/dashboard.php', 0, 'fas fa-tachometer-alt'],
    ['My Courses', 'student/my_courses.php', 0, 'fas fa-book'],
    ['My Assignments', 'student/my_assignments.php', 0, 'fas fa-tasks'],
    ['My Attendance', 'student/my_attendance.php', 0, 'fas fa-calendar-check']
];

foreach ($student_pages as $p) {
    $stmt = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_url = ?");
    $stmt->execute([$p[1]]);
    $page_id = $stmt->fetchColumn();

    if (!$page_id) {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, ?, ?, ?)");
        $stmt->execute($p);
        $page_id = $pdo->lastInsertId();
    }

    // Grant role_id 4 access to this page
    $stmt = $pdo->prepare("INSERT INTO role_access (role_id, page_id, can_view, can_edit) VALUES (4, ?, 1, 0) ON DUPLICATE KEY UPDATE can_view=1");
    $stmt->execute([$page_id]);
}

echo "Database successfully configured for the student portal.\n";
?>
