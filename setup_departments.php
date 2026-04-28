<?php
/**
 * setup_departments.php
 * Run ONCE: creates/updates the `departments` table and adds the Departments
 * navigation entry to the sidebar (sys_pages + role_access).
 *
 * Visit: http://localhost/course_mgt/setup_departments.php
 */
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain');

try {
    $pdo->beginTransaction();

    // ── 1. Create departments table if it doesn't exist ──────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `dept_id`     int(11)      NOT NULL AUTO_INCREMENT,
        `dept_name`   varchar(100) NOT NULL,
        `dept_code`   varchar(20)  NOT NULL DEFAULT '',
        `description` text         DEFAULT NULL,
        `created_at`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`dept_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ departments table ready.\n";

    // ── 2. Add missing columns if the table already existed without them ──────
    $existingCols = array_column($pdo->query("SHOW COLUMNS FROM departments")->fetchAll(PDO::FETCH_ASSOC), 'Field');

    if (!in_array('dept_code', $existingCols)) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN dept_code varchar(20) NOT NULL DEFAULT '' AFTER dept_name");
        echo "✓ Added dept_code column.\n";
    } else {
        echo "  dept_code column already exists.\n";
    }

    if (!in_array('description', $existingCols)) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN description text DEFAULT NULL AFTER dept_code");
        echo "✓ Added description column.\n";
    } else {
        echo "  description column already exists.\n";
    }

    // ── 3. Add unique indexes if missing ──────────────────────────────────────
    $indexes = array_column($pdo->query("SHOW INDEX FROM departments")->fetchAll(PDO::FETCH_ASSOC), 'Key_name');
    $indexNames = $indexes;

    if (!in_array('uq_dept_code', $indexNames)) {
        try {
            $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY uq_dept_code (dept_code)");
            echo "✓ Added unique index on dept_code.\n";
        } catch (PDOException $e) {
            echo "  Skipped dept_code unique index (duplicate values may exist).\n";
        }
    }
    if (!in_array('uq_dept_name', $indexNames)) {
        try {
            $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY uq_dept_name (dept_name)");
            echo "✓ Added unique index on dept_name.\n";
        } catch (PDOException $e) {
            echo "  Skipped dept_name unique index (duplicate values may exist).\n";
        }
    }


    // ── 4. Find or create 'Departments' parent nav group ────────────────────
    $parentCheck = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_title = 'Departments' AND parent_id IS NULL");
    $parentCheck->execute();
    $parent_id = $parentCheck->fetchColumn();

    if (!$parent_id) {
        $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, '#', NULL, ?)")
            ->execute(['Departments', 'fas fa-building']);
        $parent_id = $pdo->lastInsertId();
        echo "✓ Created 'Departments' nav group (page_id=$parent_id).\n";
    } else {
        echo "  'Departments' nav group already exists (page_id=$parent_id).\n";
    }

    // ── 5. Add 'Manage Departments' child page ───────────────────────────────
    $pageUrl = 'modules/departments/manage_departments.php';
    $childCheck = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_url = ?");
    $childCheck->execute([$pageUrl]);
    $child_page_id = $childCheck->fetchColumn();

    if (!$child_page_id) {
        $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, ?, ?, ?)")
            ->execute(['Manage Departments', $pageUrl, $parent_id, 'far fa-circle']);
        $child_page_id = $pdo->lastInsertId();
        echo "✓ Added 'Manage Departments' page (page_id=$child_page_id).\n";
    } else {
        echo "  'Manage Departments' page already exists (page_id=$child_page_id).\n";
    }

    // ── 6. Grant access to Super Admin (1) and Admin (2) ────────────────────
    $ra = $pdo->prepare("INSERT IGNORE INTO role_access (role_id, page_id, can_view) VALUES (?, ?, 1)");
    foreach ([1, 2] as $role_id) {
        $ra->execute([$role_id, $child_page_id]);
        // Also grant access to parent group if exists
        $ra->execute([$role_id, $parent_id]);
    }
    echo "✓ Role access granted to Super Admin & Admin.\n";

    $pdo->commit();
    echo "\n✅ Setup complete! You can now visit: /course_mgt/modules/departments/manage_departments.php\n";
    echo "⚠️  Delete or restrict access to this file after running it.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
