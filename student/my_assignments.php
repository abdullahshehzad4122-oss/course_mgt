<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header("HTTP/1.0 403 Forbidden");
    exit("Access denied. Students only.");
}

checkAccess();

$user_id = $_SESSION['user_id'];
$filter_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;

// Build query
$query = "
    SELECT a.assignment_id, a.title, a.description, a.due_date, c.course_code, c.course_name,
           s.submission_id, s.status as submission_status, s.submission_date,
           g.marks_obtained, g.feedback
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = :user_id1
    LEFT JOIN grades g ON s.submission_id = g.submission_id
    WHERE e.student_id = :user_id2 AND e.status = 'active'
";

$params = [':user_id1' => $user_id, ':user_id2' => $user_id];

if ($filter_course_id) {
    $query .= " AND c.course_id = :course_id";
    $params[':course_id'] = $filter_course_id;
}

$query .= " ORDER BY a.due_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.student-glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}
body { background-color: #f4f6f9 !important; }
.content-wrapper { background-color: transparent !important; }
.custom-table {
    border-radius: 15px;
    overflow: hidden;
}
.custom-table th { border-top: none; background: rgba(0,0,0,0.03); }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">My Assignments</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">My Assignments</li>
                    </ol>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show student-glass mb-0 mt-3" role="alert">
                <strong><i class="fas fa-check-circle mr-2"></i>Success!</strong> <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main content -->
    <section class="content mt-3">
        <div class="container-fluid">
            <div class="card student-glass border-0">
                <div class="card-body p-0 custom-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Assignment</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $a): ?>
                                    <?php 
                                        $is_past_due = strtotime($a['due_date']) < time();
                                        $status_badge = 'secondary';
                                        $status_text = 'Pending';
                                        
                                        if ($a['submission_status'] == 'submitted') {
                                            $status_badge = 'primary';
                                            $status_text = 'Submitted';
                                        } elseif ($a['submission_status'] == 'graded') {
                                            $status_badge = 'success';
                                            $status_text = 'Graded';
                                        } elseif ($is_past_due && !$a['submission_id']) {
                                            $status_badge = 'danger';
                                            $status_text = 'Missing';
                                        }
                                    ?>
                                <tr>
                                    <td class="align-middle font-weight-bold">
                                        <?= htmlspecialchars($a['course_code']) ?>
                                    </td>
                                    <td class="align-middle">
                                        <strong><?= htmlspecialchars($a['title']) ?></strong>
                                    </td>
                                    <td class="align-middle <?= $is_past_due && !$a['submission_id'] ? 'text-danger font-weight-bold' : '' ?>">
                                        <?= date('M d, Y h:i A', strtotime($a['due_date'])) ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-<?= $status_badge ?> px-2 py-1"><?= $status_text ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if ($a['marks_obtained'] !== null): ?>
                                            <span class="badge badge-success px-2 py-1" style="font-size: 0.9rem;"><?= htmlspecialchars($a['marks_obtained']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php if (!$a['submission_id'] && !$is_past_due): ?>
                                            <a href="submit_assignment.php?id=<?= $a['assignment_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">Submit</a>
                                        <?php elseif (!$a['submission_id'] && $is_past_due): ?>
                                            <button class="btn btn-sm btn-secondary rounded-pill px-3 shadow-sm" disabled>Locked</button>
                                        <?php else: ?>
                                            <a href="submit_assignment.php?id=<?= $a['assignment_id'] ?>" class="btn btn-sm btn-info rounded-pill px-3 shadow-sm">View Details</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 opacity-50 text-secondary"></i><br>
                                        <h5>No assignments found.</h5>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
