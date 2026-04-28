<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain');

$stmt = $pdo->query("SELECT * FROM sys_pages");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pages as $page) {
    echo "{$page['page_id']} | {$page['page_title']} | {$page['page_url']} | {$page['parent_id']}\n";
}
