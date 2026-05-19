<?php
require 'config/db.php';
// Set parent_id to NULL to hide from menu if that's acceptable, or -1 if NULL is not allowed
$pdo->query("UPDATE sys_pages SET parent_id = -1 WHERE page_url IN ('modules/assignments/view_submissions.php', 'modules/assignments/grade_submission.php')");
echo "Done";
