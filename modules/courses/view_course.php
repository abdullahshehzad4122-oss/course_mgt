<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check role access
checkAccess();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid course ID.";
    header('Location: view_courses.php');
    exit;
}

$course_id = (int)$_GET['id'];

// Get course info
$query = "SELECT c.*, d.dept_name, u.username as instructor_name 
          FROM courses c
          LEFT JOIN departments d ON c.dept_id = d.dept_id
          LEFT JOIN users u ON c.instructor_id = u.user_id
          WHERE c.course_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    $_SESSION['error'] = "Course not found.";
    header('Location: view_courses.php');
    exit;
}

// Get enrollment count
$enroll_stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
$enroll_stmt->execute([$course_id]);
$enrollment_count = $enroll_stmt->fetchColumn();

// Get enrolled students
$student_stmt = $pdo->prepare("
    SELECT u.username, e.status, e.enrollment_date 
    FROM enrollments e 
    JOIN users u ON e.student_id = u.user_id 
    WHERE e.course_id = ? 
    ORDER BY u.username
");
$student_stmt->execute([$course_id]);
$students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Course Details</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/courses/view_course.php') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <h3 class="profile-username text-center"><?= htmlspecialchars($course['course_code']) ?></h3>
                                <p class="text-muted text-center"><?= htmlspecialchars($course['course_name']) ?></p>

                                <ul class="list-group list-group-unbordered mb-3">
                                    <li class="list-group-item">
                                        <b>Department</b> <a class="float-right"><?= htmlspecialchars($course['dept_name'] ?? 'N/A') ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Instructor</b> <a class="float-right"><?= htmlspecialchars($course['instructor_name'] ?? 'Unassigned') ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Enrolled Students</b> <a class="float-right"><?= $enrollment_count ?></a>
                                    </li>
                                </ul>

                                <a href="view_courses.php" class="btn btn-primary btn-block"><b>Back to Courses</b></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item"><a class="nav-link active" href="#students" data-toggle="tab">Enrolled Students</a></li>
                                </ul>
                            </div>
                            <div class="card-body p-0">
                                <div class="tab-content">
                                    <div class="active tab-pane" id="students">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Enrollment Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($students)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No students currently enrolled.</td>
                                                </tr>
                                                <?php else: foreach($students as $student): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($student['username']) ?></td>
                                                    <td><?= date('M d, Y', strtotime($student['enrollment_date'])) ?></td>
                                                    <td><span class="badge badge-<?= $student['status'] == 'active' ? 'success' : 'secondary' ?>"><?= ucfirst(htmlspecialchars($student['status'])) ?></span></td>
                                                </tr>
                                                <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
