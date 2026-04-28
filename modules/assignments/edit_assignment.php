<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Only Instructors and Admins
if ($_SESSION['role_id'] > 3) {
    header('Location: view_assignments.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: view_assignments.php');
    exit;
}

$assignment_id = (int)$_GET['id'];

// Fetch existing assignment - verify ownership for instructors
if ($_SESSION['role_id'] == 3) {
    $stmt = $pdo->prepare("SELECT a.*, c.course_code, c.course_name FROM assignments a 
                           JOIN courses c ON a.course_id = c.course_id 
                           WHERE a.assignment_id = ? AND c.instructor_id = ?");
    $stmt->execute([$assignment_id, $_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("SELECT a.*, c.course_code, c.course_name FROM assignments a 
                           JOIN courses c ON a.course_id = c.course_id 
                           WHERE a.assignment_id = ?");
    $stmt->execute([$assignment_id]);
}
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    $_SESSION['error'] = "Assignment not found or access denied.";
    header('Location: view_assignments.php');
    exit;
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id   = (int)$_POST['course_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date    = trim($_POST['due_date']);

    if ($course_id <= 0)   $errors[] = "Please select a course";
    if (empty($title))     $errors[] = "Assignment title is required";
    if (empty($due_date))  $errors[] = "Due date is required";

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("UPDATE assignments SET course_id=?, title=?, description=?, due_date=? WHERE assignment_id=?");
            $upd->execute([$course_id, $title, $description, $due_date, $assignment_id]);
            $_SESSION['success'] = "Assignment updated successfully.";
            header('Location: view_assignments.php');
            exit;
        } catch (PDOException $e) {
            error_log("Assignment update error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Get courses for dropdown
if ($_SESSION['role_id'] == 3) {
    $course_stmt = $pdo->prepare("SELECT course_id, course_code, course_name FROM courses WHERE instructor_id = ? ORDER BY course_code");
    $course_stmt->execute([$_SESSION['user_id']]);
} else {
    $course_stmt = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
}
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

// Format due_date for datetime-local input
$due_for_input = date('Y-m-d\TH:i', strtotime($assignment['due_date']));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Assignment</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/assignments/edit_assignment.php') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Edit: <?= htmlspecialchars($assignment['title']) ?></h3>
                            </div>

                            <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mx-3 mt-3">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <form method="POST" action="edit_assignment.php?id=<?= $assignment_id ?>">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="course_id">Course *</label>
                                        <select class="form-control" id="course_id" name="course_id" required>
                                            <option value="">-- Select Course --</option>
                                            <?php $selected_course = $_POST['course_id'] ?? $assignment['course_id']; ?>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>" <?= $selected_course == $course['course_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="title">Assignment Title *</label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               value="<?= htmlspecialchars($_POST['title'] ?? $assignment['title']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description & Instructions</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? $assignment['description']) ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="due_date">Due Date & Time *</label>
                                        <input type="datetime-local" class="form-control" id="due_date" name="due_date"
                                               value="<?= htmlspecialchars($_POST['due_date'] ?? $due_for_input) ?>" required>
                                    </div>

                                    <small class="text-danger">* Required fields</small>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-warning">Save Changes</button>
                                    <a href="view_assignments.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
