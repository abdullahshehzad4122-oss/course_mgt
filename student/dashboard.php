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

// 1. Total Enrolled Courses
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$enrolled_courses = $stmt->fetchColumn();

// 2. Pending Assignments
$stmt = $pdo->prepare("
    SELECT COUNT(a.assignment_id) FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.student_id = ? AND e.status = 'active'
    AND a.assignment_id NOT IN (
        SELECT assignment_id FROM submissions WHERE student_id = ?
    )
");
$stmt->execute([$user_id, $user_id]);
$pending_assignments = $stmt->fetchColumn();

// 3. Average Grade
$stmt = $pdo->prepare("
    SELECT AVG(g.marks_obtained) FROM grades g
    JOIN submissions s ON g.submission_id = s.submission_id
    WHERE s.student_id = ?
");
$stmt->execute([$user_id]);
$avg_grade = $stmt->fetchColumn();
$avg_grade = $avg_grade ? number_format($avg_grade, 2) : 'N/A';

// 4. Upcoming Deadlines
$stmt = $pdo->prepare("
    SELECT a.title, a.due_date, c.course_name 
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.student_id = ? AND e.status = 'active'
    AND a.due_date >= NOW()
    AND a.assignment_id NOT IN (
        SELECT assignment_id FROM submissions WHERE student_id = ?
    )
    ORDER BY a.due_date ASC LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$deadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Recent Grades
$stmt = $pdo->prepare("
    SELECT a.title, g.marks_obtained, c.course_name 
    FROM grades g
    JOIN submissions s ON g.submission_id = s.submission_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE s.student_id = ?
    ORDER BY g.graded_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">Welcome Back! <span class="badge badge-primary align-top" style="font-size:0.4em;">Student</span></h1>
                    <p class="text-muted">Here's your academic overview.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box student-glass p-3 relative overflow-hidden">
                        <div class="inner text-dark">
                            <h3 style="font-size: 2.5rem; font-weight: 800;"><?= $enrolled_courses ?></h3>
                            <p class="font-weight-bold text-muted text-uppercase" style="letter-spacing: 1px;">Enrolled Courses</p>
                        </div>
                        <div class="icon" style="position: absolute; right: 20px; top: 20px; font-size: 60px; color: rgba(0,0,0,0.1);">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <a href="<?= BASE_URL ?>/student/my_courses.php" class="small-box-footer text-primary font-weight-bold mt-2 d-block">
                            View details <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-6">
                    <div class="small-box student-glass p-3 relative overflow-hidden">
                        <div class="inner text-dark">
                            <h3 style="font-size: 2.5rem; font-weight: 800; color: #dc3545;"><?= $pending_assignments ?></h3>
                            <p class="font-weight-bold text-muted text-uppercase" style="letter-spacing: 1px;">Pending Tasks</p>
                        </div>
                        <div class="icon" style="position: absolute; right: 20px; top: 20px; font-size: 60px; color: rgba(220,53,69,0.1);">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <a href="<?= BASE_URL ?>/student/my_assignments.php" class="small-box-footer text-primary font-weight-bold mt-2 d-block">
                            View pending <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-12">
                    <div class="small-box student-glass p-3 relative overflow-hidden">
                        <div class="inner text-dark">
                            <h3 style="font-size: 2.5rem; font-weight: 800; color: #28a745;"><?= $avg_grade ?></h3>
                            <p class="font-weight-bold text-muted text-uppercase" style="letter-spacing: 1px;">Average Grade</p>
                        </div>
                        <div class="icon" style="position: absolute; right: 20px; top: 20px; font-size: 60px; color: rgba(40,167,69,0.1);">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="mt-2 text-muted small"><i class="fas fa-info-circle mr-1"></i> Based on graded submissions</div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card student-glass border-0">
                        <div class="card-header bg-transparent border-0 pt-4 pb-2">
                            <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-clock text-danger mr-2"></i>Upcoming Deadlines</h3>
                        </div>
                        <div class="card-body mt-2">
                            <ul class="list-group list-group-flush border-top-0">
                                <?php foreach ($deadlines as $deadline): ?>
                                <li class="list-group-item bg-transparent px-0 border-bottom">
                                    <span class="float-right badge badge-danger rounded-pill px-2 py-1 shadow-sm">
                                        <?= date('M d, h:i A', strtotime($deadline['due_date'])) ?>
                                    </span>
                                    <h6 class="mb-1 font-weight-bold text-dark"><?= htmlspecialchars($deadline['title']) ?></h6>
                                    <small class="text-muted"><i class="fas fa-book-open mr-1"></i><?= htmlspecialchars($deadline['course_name']) ?></small>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($deadlines)): ?>
                                <li class="list-group-item bg-transparent text-center text-muted py-4 border-0">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50 d-block"></i>
                                    You're all caught up! No upcoming deadlines.
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card student-glass border-0">
                        <div class="card-header bg-transparent border-0 pt-4 pb-2">
                            <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-award text-warning mr-2"></i>Recent Grades</h3>
                        </div>
                        <div class="card-body mt-2">
                            <ul class="list-group list-group-flush border-top-0">
                                <?php foreach ($recent_grades as $grade): ?>
                                <li class="list-group-item bg-transparent px-0 border-bottom">
                                    <span class="float-right badge badge-success rounded-pill px-3 py-1 shadow-sm font-weight-bold" style="font-size: 0.9rem;">
                                        <?= htmlspecialchars($grade['marks_obtained']) ?>
                                    </span>
                                    <h6 class="mb-1 font-weight-bold text-dark"><?= htmlspecialchars($grade['title']) ?></h6>
                                    <small class="text-muted"><i class="fas fa-book-open mr-1"></i><?= htmlspecialchars($grade['course_name']) ?></small>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($recent_grades)): ?>
                                <li class="list-group-item bg-transparent text-center text-muted py-4 border-0">
                                    <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                    No graded assignments yet.
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
