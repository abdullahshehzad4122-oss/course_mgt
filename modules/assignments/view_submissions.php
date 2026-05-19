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

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$assignment_id) {
    header('Location: view_assignments.php');
    exit;
}

// Fetch assignment info
$stmt = $pdo->prepare("SELECT a.*, c.course_code, c.course_name, c.instructor_id 
                       FROM assignments a 
                       JOIN courses c ON a.course_id = c.course_id 
                       WHERE a.assignment_id = ?");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die("Assignment not found.");
}

// Security: If instructor, verify they own the course
if ($_SESSION['role_id'] == 3 && $assignment['instructor_id'] != $_SESSION['user_id']) {
    die("Access denied. You are not the instructor for this course.");
}

// Fetch submissions roster
$query = "
    SELECT u.user_id, u.username, 
           s.submission_id, s.file_path, s.status, s.submission_date, 
           g.grade_id, g.marks_obtained, g.feedback
    FROM enrollments e
    JOIN users u ON e.student_id = u.user_id
    LEFT JOIN submissions s ON e.student_id = s.student_id AND s.assignment_id = ?
    LEFT JOIN grades g ON s.submission_id = g.submission_id
    WHERE e.course_id = ? AND e.status = 'active'
    ORDER BY u.username ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$assignment_id, $assignment['course_id']]);
$roster = $stmt->fetchAll(PDO::FETCH_ASSOC);

$due_date = strtotime($assignment['due_date']);
$is_past_due = time() > $due_date;

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Submissions Viewer</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="view_assignments.php">Assignments</a></li>
                        <li class="breadcrumb-item active">Grade Submissions</li>
                    </ol>
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

            <!-- Assignment Info Panel -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="text-primary font-weight-bold"><?= htmlspecialchars($assignment['title']) ?></h4>
                            <p class="text-muted"><i class="fas fa-book border-right pr-2 mr-2"></i> <?= htmlspecialchars($assignment['course_code']) ?> - <?= htmlspecialchars($assignment['course_name']) ?></p>
                            <p><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                        </div>
                        <div class="col-md-4 text-md-right border-left">
                            <h5 class="font-weight-bold mb-1">Due Date</h5>
                            <p class="<?= $is_past_due ? 'text-danger font-weight-bold' : '' ?>">
                                <?= date('F j, Y, g:i a', $due_date) ?>
                            </p>
                            <h5 class="font-weight-bold mb-1 mt-3">Total Submissions</h5>
                            <h3 class="text-success m-0"><?= count(array_filter($roster, fn($r) => !empty($r['submission_id']))) ?> / <?= count($roster) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roster Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark">
                    <h3 class="card-title">Class Roster & Grading</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 15%">Student</th>
                                <th style="width: 15%">Status</th>
                                <th style="width: 20%">File Submitted</th>
                                <th style="width: 50%">Grade & Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $row): 
                                $status_badge = 'secondary';
                                $status_text = 'Pending';
                                
                                if ($row['status'] == 'submitted') {
                                    $status_badge = 'primary';
                                    $status_text = 'Submitted';
                                } elseif ($row['status'] == 'graded') {
                                    $status_badge = 'success';
                                    $status_text = 'Graded';
                                } elseif ($is_past_due && !$row['submission_id']) {
                                    $status_badge = 'danger';
                                    $status_text = 'Missing';
                                }
                            ?>
                            <tr>
                                <td class="font-weight-bold align-middle">
                                    <i class="fas fa-user-circle text-muted mr-1"></i> <?= htmlspecialchars($row['username']) ?>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-<?= $status_badge ?> px-2 py-1"><?= $status_text ?></span>
                                    <?php if ($row['submission_date']): ?>
                                        <div class="small text-muted mt-1"><?= date('M d, h:i A', strtotime($row['submission_date'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-center">
                                    <?php if ($row['submission_id']): ?>
                                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="Download File">
                                            <i class="fas fa-file-download mr-1"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted font-italic">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle bg-light">
                                    <?php if ($row['submission_id']): ?>
                                        <form action="grade_submission.php" method="POST" class="d-flex w-100">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                                            <input type="hidden" name="submission_id" value="<?= $row['submission_id'] ?>">
                                            
                                            <div class="input-group input-group-sm mr-2" style="width: 100px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-marker"></i></span>
                                                </div>
                                                <input type="number" class="form-control" name="marks_obtained" placeholder="Mark" 
                                                       value="<?= htmlspecialchars($row['marks_obtained'] ?? '') ?>" required min="0" max="100">
                                            </div>
                                            
                                            <div class="input-group input-group-sm flex-grow-1 mr-2">
                                                <input type="text" class="form-control" name="feedback" placeholder="Feedback/Comments..." 
                                                       value="<?= htmlspecialchars($row['feedback'] ?? '') ?>">
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm <?= ($row['status'] == 'graded') ? 'btn-success' : 'btn-primary' ?> shadow-sm">
                                                <?= ($row['status'] == 'graded') ? 'Update' : 'Save' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="text-muted text-center small">Cannot grade without submission</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($roster)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No active students enrolled in this course.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
