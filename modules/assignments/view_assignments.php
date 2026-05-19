<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Only Instructors and Admins
if ($_SESSION['role_id'] > 3) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Verify ownership (Instructor can only delete their own)
    if ($_SESSION['role_id'] == 3) {
        $own = $pdo->prepare("SELECT 1 FROM assignments a JOIN courses c ON a.course_id = c.course_id WHERE a.assignment_id = ? AND c.instructor_id = ?");
        $own->execute([$del_id, $_SESSION['user_id']]);
        if (!$own->fetchColumn()) {
            $_SESSION['error'] = "You are not authorized to delete this assignment.";
            header('Location: view_assignments.php');
            exit;
        }
    }
    
    try {
        $pdo->prepare("DELETE FROM assignments WHERE assignment_id = ?")->execute([$del_id]);
        $_SESSION['success'] = "Assignment deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Could not delete assignment. It may have submissions.";
    }
    header('Location: view_assignments.php');
    exit;
}

// Build query based on role
if ($_SESSION['role_id'] == 3) {
    // Instructor sees only their courses' assignments
    $query = "SELECT a.*, c.course_code, c.course_name,
                     (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.assignment_id) as submission_count
              FROM assignments a
              JOIN courses c ON a.course_id = c.course_id
              WHERE c.instructor_id = ?
              ORDER BY a.due_date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
} else {
    // Admin sees all
    $query = "SELECT a.*, c.course_code, c.course_name,
                     (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.assignment_id) as submission_count
              FROM assignments a
              JOIN courses c ON a.course_id = c.course_id
              ORDER BY a.due_date DESC";
    $stmt = $pdo->query($query);
}
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Assignments</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/assignments/view_assignments.php') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Assignment List</h3>
                        <div class="card-tools">
                            <a href="create_assignment.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-plus mr-1"></i> Create New Assignment
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-bordered text-nowrap">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No assignments found. <a href="create_assignment.php">Create one now</a>.
                                    </td>
                                </tr>
                                <?php else: foreach ($assignments as $a):
                                    $due = strtotime($a['due_date']);
                                    $now = time();
                                    $overdue = $due < $now;
                                    $days_left = ceil(($due - $now) / 86400);
                                ?>
                                <tr>
                                    <td><span class="badge badge-dark"><?= htmlspecialchars($a['course_code']) ?></span></td>
                                    <td class="font-weight-bold"><?= htmlspecialchars($a['title']) ?></td>
                                    <td>
                                        <?= date('M d, Y h:i A', $due) ?>
                                        <?php if ($overdue): ?>
                                            <span class="badge badge-danger ml-1">Overdue</span>
                                        <?php elseif ($days_left <= 3): ?>
                                            <span class="badge badge-warning ml-1">Due soon</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($overdue): ?>
                                            <span class="badge badge-danger px-2 py-1">Closed</span>
                                        <?php else: ?>
                                            <span class="badge badge-success px-2 py-1">Open (<?= $days_left ?> day<?= $days_left != 1 ? 's' : '' ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="view_submissions.php?id=<?= $a['assignment_id'] ?>" class="badge badge-info p-2 shadow-sm" style="font-size: 0.9rem;" title="Grade Submissions">
                                            <?= $a['submission_count'] ?> <i class="fas fa-arrow-circle-right ml-1"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit_assignment.php?id=<?= $a['assignment_id'] ?>"
                                               class="btn btn-sm btn-primary" title="Edit Assignment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_assignments.php?delete=<?= $a['assignment_id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Delete this assignment? All submissions will also be removed.')"
                                               title="Delete Assignment">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
