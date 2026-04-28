<?php
require_once __DIR__ . '/config/db.php';
session_start();
header('Content-Type: text/plain');

echo "Current Session Role ID: " . ($_SESSION['role_id'] ?? 'NOT LOGGED IN') . "\n\n";

// Dump ALL sys_pages
echo "--- ALL sys_pages entries ---\n";
$stmt = $pdo->query("SELECT * FROM sys_pages");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($pages)) {
    echo "NO PAGES FOUND IN sys_pages TABLE!\n";
} else {
    foreach ($pages as $p) {
        $parent = $p['parent_id'] ?? 'NULL';
        echo "[{$p['page_id']}] Parent:{$parent} | Title:{$p['page_title']} | URL:{$p['page_url']}\n";
        
        $ra_stmt = $pdo->prepare("SELECT role_id FROM role_access WHERE page_id = ? AND can_view = 1");
        $ra_stmt->execute([$p['page_id']]);
        $roles = $ra_stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "    Access Roles: " . (empty($roles) ? 'NONE' : implode(', ', $roles)) . "\n";
    }
}

echo "\n--- sys_pages Table Structure ---\n";
$cols = $pdo->query("DESCRIBE sys_pages")->fetchAll(PDO::FETCH_ASSOC);
echo str_pad("Field", 15) . " | " . str_pad("Type", 15) . " | " . str_pad("Null", 5) . " | " . str_pad("Default", 10) . "\n";
echo str_repeat("-", 55) . "\n";
foreach ($cols as $c) {
    echo str_pad($c['Field'], 15) . " | " . str_pad($c['Type'], 15) . " | " . str_pad($c['Null'], 5) . " | " . str_pad($c['Default'], 10) . "\n";
}


echo "\n--- All Roles ---\n";
$roles = $pdo->query("SELECT * FROM sys_roles")->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r) {
    echo "ID: {$r['role_id']} | Name: {$r['role_name']}\n";
}
?>
