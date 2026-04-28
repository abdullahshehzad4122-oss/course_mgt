<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain');
// Check role_access for duplicates per role+page combo
$stmt = $pdo->query("SELECT role_id, page_id, COUNT(*) as cnt FROM role_access GROUP BY role_id, page_id HAVING cnt > 1");
$dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Duplicates in role_access:\n";
print_r($dups);
