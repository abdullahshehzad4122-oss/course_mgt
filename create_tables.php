<?php
require_once __DIR__ . '/config/db.php';

try {
    // 1. assignments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `assignments` (
        `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text,
        `due_date` datetime NOT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`assignment_id`),
        KEY `course_id` (`course_id`),
        KEY `created_by` (`created_by`),
        CONSTRAINT `fk_assignments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created assignments table.\n";

    // 2. submissions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `submissions` (
        `submission_id` int(11) NOT NULL AUTO_INCREMENT,
        `assignment_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `submission_date` timestamp DEFAULT CURRENT_TIMESTAMP,
        `file_path` varchar(255) NOT NULL,
        `status` enum('pending','submitted','graded') DEFAULT 'pending',
        PRIMARY KEY (`submission_id`),
        KEY `assignment_id` (`assignment_id`),
        KEY `student_id` (`student_id`),
        CONSTRAINT `fk_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created submissions table.\n";

    // 3. grades table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `grades` (
        `grade_id` int(11) NOT NULL AUTO_INCREMENT,
        `submission_id` int(11) NOT NULL,
        `marks_obtained` decimal(5,2) NOT NULL,
        `feedback` text,
        `graded_by` int(11) NOT NULL,
        `graded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`grade_id`),
        KEY `submission_id` (`submission_id`),
        CONSTRAINT `fk_grades_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`submission_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created grades table.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
