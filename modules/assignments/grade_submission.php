<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

// Only Instructors and Admins
if ($_SESSION['role_id'] > 3) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = (int)$_POST['assignment_id'];
    $submission_id = (int)$_POST['submission_id'];
    $marks = isset($_POST['marks_obtained']) ? (float)$_POST['marks_obtained'] : null;
    $feedback = trim($_POST['feedback'] ?? '');

    if (!$submission_id || !$assignment_id || $marks === null) {
        $_SESSION['error'] = "Missing grading information.";
        header('Location: view_submissions.php?id=' . $assignment_id);
        exit;
    }

    // Verify assignment and authorization
    $stmt = $pdo->prepare("SELECT a.assignment_id, c.instructor_id 
                           FROM assignments a 
                           JOIN courses c ON a.course_id = c.course_id 
                           WHERE a.assignment_id = ?");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        $_SESSION['error'] = "Assignment not found.";
        header('Location: view_assignments.php');
        exit;
    }

    if ($_SESSION['role_id'] == 3 && $assignment['instructor_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = "You are not authorized to grade this assignment.";
        header('Location: view_submissions.php?id=' . $assignment_id);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if grade exists
        $check = $pdo->prepare("SELECT grade_id FROM grades WHERE submission_id = ?");
        $check->execute([$submission_id]);
        $grade_id = $check->fetchColumn();

        if ($grade_id) {
            // Update existing grade
            $pdo->prepare("UPDATE grades SET marks_obtained = ?, feedback = ? WHERE grade_id = ?")->execute([$marks, $feedback, $grade_id]);
        } else {
            // Insert new grade
            $pdo->prepare("INSERT INTO grades (submission_id, marks_obtained, feedback) VALUES (?, ?, ?)")->execute([$submission_id, $marks, $feedback]);
        }

        // Mark submission as graded
        $pdo->prepare("UPDATE submissions SET status = 'graded' WHERE submission_id = ?")->execute([$submission_id]);

        $pdo->commit();
        $_SESSION['success'] = "Grade saved successfully!";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to save grade due to database error.";
    }

    header('Location: view_submissions.php?id=' . $assignment_id);
    exit;
} else {
    header('Location: view_assignments.php');
    exit;
}
