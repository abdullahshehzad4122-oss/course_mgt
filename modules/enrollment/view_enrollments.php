<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check role access
if ($_SESSION['role_id'] > 3) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle unenrollment
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if ($_SESSION['role_id'] <= 2) { // Only admins can unenroll
        $enrollment_id = (int)$_GET['delete'];
        try {
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            $_SESSION['success'] = "Student unenrolled successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error during unenrollment.";
        }
    } else {
        $_SESSION['error'] = "You do not have permission to unenroll students.";
    }
    
    $redirect = 'view_enrollments.php';
    if (isset($_GET['status'])) {
        $redirect .= '?status=' . urlencode($_GET['status']);
    } elseif (isset($_GET['course_id'])) {
        $redirect .= '?course_id=' . (int)$_GET['course_id'];
    }
    header('Location: ' . $redirect);
    exit;
}

// Update status
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['enrollment_id']) && isset($_POST['status'])) {
    if ($_SESSION['role_id'] <= 2) { // Only admins can update status
        try {
            $stmt = $pdo->prepare("UPDATE enrollments SET status = ? WHERE enrollment_id = ?");
            $stmt->execute([$_POST['status'], (int)$_POST['enrollment_id']]);
            $_SESSION['success'] = "Enrollment status updated.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update status.";
        }
    }
    
    $redirect = 'view_enrollments.php';
    if (isset($_GET['status'])) {
        $redirect .= '?status=' . urlencode($_GET['status']);
    } elseif (isset($_GET['course_id'])) {
        $redirect .= '?course_id=' . (int)$_GET['course_id'];
    }
    header('Location: ' . $redirect);
    exit;
}

// Get courses for dropdown
if ($_SESSION['role_id'] == 3) {
    $course_query = "SELECT course_id, course_code, course_name FROM courses WHERE instructor_id = ? ORDER BY course_code";
    $course_stmt = $pdo->prepare($course_query);
    $course_stmt->execute([$_SESSION['user_id']]);
} else {
    $course_query = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
    $course_stmt = $pdo->query($course_query);
}
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$enrollments = [];

if ($filter_status === 'pending') {
    // Show all pending enrollments across all courses
    $query = "SELECT e.enrollment_id, e.status, e.enrollment_date, u.username as student_name, 
                     c.course_code, c.course_name
              FROM enrollments e
              JOIN users u ON e.student_id = u.user_id
              JOIN courses c ON e.course_id = c.course_id
              WHERE e.status = 'pending'
              ORDER BY e.enrollment_date DESC, u.username";
    $stmt = $pdo->query($query);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($selected_course_id > 0) {
    $query = "SELECT e.enrollment_id, e.status, e.enrollment_date, u.username as student_name, c.course_code 
              FROM enrollments e
              JOIN users u ON e.student_id = u.user_id
              JOIN courses c ON e.course_id = c.course_id
              WHERE e.course_id = ?
              ORDER BY u.username";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$selected_course_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">View Enrollments</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/enrollment/view_enrollments.php') ?>
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
                
                <div class="card card-outline card-primary mb-4">
                    <div class="card-body">
                        <?php if ($filter_status === 'pending'): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-filter mr-1"></i> Showing all <strong>pending</strong> enrollments across all courses.
                            <a href="view_enrollments.php" class="float-right"><i class="fas fa-times"></i> Clear Filter</a>
                        </div>
                        <?php endif; ?>
                        <form method="GET" action="view_enrollments.php" class="form-inline">
                            <label class="mr-2" for="course_id">Select Course:</label>
                            <select class="form-control mr-2" name="course_id" id="course_id" onchange="this.form.submit()" style="min-width: 300px;">
                                <option value="0">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>" <?= $selected_course_id == $course['course_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <?php if ($_SESSION['role_id'] <= 2): ?>
                                <a href="enroll_student.php<?= $selected_course_id ? '?course_id='.$selected_course_id : '' ?>" class="btn btn-success ml-auto">
                                    <i class="fas fa-plus"></i> Enroll Student
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <?php if ($selected_course_id > 0 || $filter_status === 'pending'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php if ($filter_status === 'pending'): ?>
                                <i class="fas fa-clock text-warning mr-1"></i> All Pending Enrollments
                            <?php else: ?>
                                Enrolled Students
                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info"><?= count($enrollments) ?> <?= $filter_status === 'pending' ? 'Pending' : 'Students' ?></span>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <?php if ($filter_status === 'pending'): ?>
                                    <th>Course</th>
                                    <?php endif; ?>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                    <?php if ($_SESSION['role_id'] <= 2): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($enrollments)): ?>
                                <tr><td colspan="<?= $filter_status === 'pending' ? 5 : 4 ?>" class="text-center text-muted py-4">
                                    <?= $filter_status === 'pending' ? 'No pending enrollments found.' : 'No students enrolled in this course.' ?>
                                </td></tr>
                                <?php else: foreach ($enrollments as $row): 
                                    $badge_class = 'secondary';
                                    if ($row['status'] == 'active') $badge_class = 'success';
                                    if ($row['status'] == 'dropped') $badge_class = 'danger';
                                    if ($row['status'] == 'completed') $badge_class = 'primary';
                                ?>
                                <tr>
                                    <td class="align-middle font-weight-bold"><?= htmlspecialchars($row['student_name']) ?></td>
                                    <?php if ($filter_status === 'pending'): ?>
                                    <td class="align-middle">
                                        <span class="badge badge-light px-2 py-1"><?= htmlspecialchars($row['course_code']) ?></span>
                                        <?= htmlspecialchars($row['course_name'] ?? '') ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="align-middle"><?= date('M d, Y', strtotime($row['enrollment_date'])) ?></td>
                                    <td class="align-middle">
                                        <span class="badge badge-<?= $badge_class ?> px-2 py-1"><?= ucfirst(htmlspecialchars($row['status'])) ?></span>
                                    </td>
                                    
                                    <?php if ($_SESSION['role_id'] <= 2): ?>
                                    <td>
                                        <form method="POST" action="view_enrollments.php?<?= $filter_status === 'pending' ? 'status=pending' : 'course_id=' . $selected_course_id ?>" class="d-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            <select name="status" class="form-control form-control-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="active" <?= $row['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="dropped" <?= $row['status'] == 'dropped' ? 'selected' : '' ?>>Dropped</option>
                                                <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </form>
                                        
                                        <a href="view_enrollments.php?<?= $filter_status === 'pending' ? 'status=pending' : 'course_id=' . $selected_course_id ?>&delete=<?= $row['enrollment_id'] ?>" 
                                           class="btn btn-sm btn-danger ml-2" 
                                           onclick="return confirm('Are you sure you want to completely remove this enrollment? This cannot be undone.')"
                                           title="Remove Enrollment">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
