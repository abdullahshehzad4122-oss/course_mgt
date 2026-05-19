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

// Get Enrolled Courses
$stmt = $pdo->prepare("
    SELECT c.course_id, c.course_code, c.course_name, d.dept_name, u.username as instructor_name, e.enrollment_date
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    JOIN departments d ON c.dept_id = d.dept_id
    LEFT JOIN users u ON c.instructor_id = u.user_id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.course_name ASC
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.student-glass:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.2);
}
body { background-color: #f4f6f9 !important; }
.content-wrapper { background-color: transparent !important; }
.course-card { border-left: 5px solid #007bff; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">My Courses</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">My Courses</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <?php foreach ($courses as $course): ?>
                <div class="col-md-4">
                    <div class="card student-glass course-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title font-weight-bold text-primary w-100 border-bottom pb-2 mb-3">
                                <?= htmlspecialchars($course['course_code']) ?>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-dark font-weight-bold mt-2" style="font-size: 1.1rem;">
                                <?= htmlspecialchars($course['course_name']) ?>
                            </h6>
                            <p class="card-text text-muted small mt-3">
                                <i class="fas fa-building mr-2 text-secondary"></i> <?= htmlspecialchars($course['dept_name']) ?><br>
                                <i class="fas fa-chalkboard-teacher mr-2 text-secondary"></i> <?= htmlspecialchars($course['instructor_name'] ?: 'Not Assigned') ?><br>
                                <i class="fas fa-calendar-alt mr-2 text-secondary"></i> Enrolled: <?= date('M d, Y', strtotime($course['enrollment_date'])) ?>
                            </p>
                            <div class="mt-4">
                                <a href="<?= BASE_URL ?>/student/my_assignments.php?course_id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary shadow-sm rounded-pill px-3">Assignments</a>
                                <a href="<?= BASE_URL ?>/student/my_attendance.php?course_id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-info shadow-sm rounded-pill px-3">Attendance</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($courses)): ?>
                <div class="col-12">
                    <div class="alert alert-info student-glass text-center p-5">
                        <i class="fas fa-info-circle fa-3x mb-3 text-info"></i>
                        <h4>You are not enrolled in any active courses yet.</h4>
                        <p>Contact your administrator if you believe this is a mistake.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
