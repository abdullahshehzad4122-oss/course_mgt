<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
checkAccess();

// Get dashboard statistics (Section 11.6)
$stats = [];
switch($_SESSION['role_id']) {
    case 1: // Super Admin
    case 2: // Admin
        $stats['total_courses'] = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $stats['total_students'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 4")->fetchColumn();
        $stats['pending_enrollments'] = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'")->fetchColumn();
        $stats['total_departments'] = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
        // Count courses that have enrolled students but no attendance recorded for today
        $stats['attendance_pending'] = $pdo->query("
            SELECT COUNT(DISTINCT c.course_id)
            FROM courses c
            JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
            WHERE c.course_id NOT IN (
                SELECT DISTINCT course_id FROM attendance WHERE session_date = CURDATE()
            )
        ")->fetchColumn();
        break;
        
    case 3: // Instructor
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['my_courses'] = $stmt->fetchColumn();
        
        // Count instructor's courses that have enrolled students but no attendance for today
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.course_id)
            FROM courses c
            JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
            WHERE c.instructor_id = ?
            AND c.course_id NOT IN (
                SELECT DISTINCT course_id FROM attendance WHERE session_date = CURDATE()
            )
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['attendance_pending'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE created_by = ? AND due_date > NOW()");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['assignments_due'] = $stmt->fetchColumn();
        break;
        
    case 4: // Student
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['enrolled_courses'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments a 
                                                     JOIN submissions s ON a.assignment_id = s.assignment_id
                                                     WHERE s.student_id = ? AND s.status = 'pending'");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['pending_assignments'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT AVG(marks_obtained) FROM grades g
                                               JOIN submissions s ON g.submission_id = s.submission_id
                                               WHERE s.student_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['average_grade'] = $stmt->fetchColumn();
        break;
}

// Get recent activity (for Activity Log)
$activity_query = "SELECT a.*, u.username, r.role_name 
                  FROM access_logs a
                  JOIN users u ON a.user_id = u.user_id
                  JOIN sys_roles r ON u.role_id = r.role_id
                  ORDER BY timestamp DESC LIMIT 10";
$activity_stmt = $pdo->prepare($activity_query);
$activity_stmt->execute();
$activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enrollment distribution (for Chart.js)
$enrollment_query = "SELECT d.dept_name, COUNT(e.enrollment_id) as count
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.course_id
                    JOIN departments d ON c.dept_id = d.dept_id
                    GROUP BY d.dept_name";
$enrollment_stmt = $pdo->prepare($enrollment_query);
$enrollment_stmt->execute();
$enrollments = $enrollment_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$dept_names = [];
$enrollment_counts = [];
foreach ($enrollments as $row) {
    $dept_names[] = $row['dept_name'];
    $enrollment_counts[] = $row['count'];
}
?>
<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar.php';
?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Info Boxes -->
                <div class="row">
                    <?php if ($_SESSION['role_id'] <= 2): ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon text-white rounded-lg elevation-1 m-2 h-75" style="background: linear-gradient(135deg, #36b9cc 0%, #17a2b8 100%);"><i class="fas fa-book"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text text-uppercase text-muted font-weight-bold" style="font-size: 0.8rem; letter-spacing: 0.05em;">Total Courses</span>
                                <span class="info-box-number text-dark" style="font-size: 1.5rem;"><?= $stats['total_courses'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role_id'] <= 2): ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon text-white rounded-lg elevation-1 m-2 h-75" style="background: linear-gradient(135deg, #1cc88a 0%, #28a745 100%);"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text text-uppercase text-muted font-weight-bold" style="font-size: 0.8rem; letter-spacing: 0.05em;">Students</span>
                                <span class="info-box-number text-dark" style="font-size: 1.5rem;"><?= $stats['total_students'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role_id'] <= 2): ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <a href="<?= BASE_URL ?>/modules/enrollment/view_enrollments.php?status=pending" class="text-decoration-none dashboard-info-link">
                        <div class="info-box mb-3">
                            <span class="info-box-icon text-white rounded-lg elevation-1 m-2 h-75" style="background: linear-gradient(135deg, #f6c23e 0%, #ffc107 100%);"><i class="fas fa-user-graduate"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text text-uppercase text-muted font-weight-bold" style="font-size: 0.8rem; letter-spacing: 0.05em;">Pending Enrollments</span>
                                <span class="info-box-number text-dark" style="font-size: 1.5rem;"><?= $stats['pending_enrollments'] ?></span>
                            </div>
                        </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role_id'] <= 3): ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <a href="<?= BASE_URL ?>/modules/attendance/mark_attendance.php?pending=1" class="text-decoration-none dashboard-info-link">
                        <div class="info-box mb-3">
                            <span class="info-box-icon text-white rounded-lg elevation-1 m-2 h-75" style="background: linear-gradient(135deg, #e74a3b 0%, #dc3545 100%);"><i class="fas fa-calendar-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text text-uppercase text-muted font-weight-bold" style="font-size: 0.8rem; letter-spacing: 0.05em;">Attendance Pending</span>
                                <span class="info-box-number text-dark" style="font-size: 1.5rem;"><?= $stats['attendance_pending'] ?? 0 ?></span>
                            </div>
                        </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- /.row -->

                <!-- Main Dashboard Content -->
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Enrollment Distribution Chart -->
                        <div class="card mb-4 pb-2">
                            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-chart-pie text-primary mr-2"></i>Enrollment Distribution</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="enrollmentChart" height="250"></canvas>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card">
                            <div class="card-header bg-transparent border-0 pt-4 pb-2">
                                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-history text-success mr-2"></i>Recent Activity</h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($activities as $activity): ?>
                                    <li class="list-group-item">
                                        <a href="#" class="text-primary"><?= htmlspecialchars($activity['username']) ?></a> 
                                        <span class="text-muted">[<?= htmlspecialchars($activity['role_name']) ?>]</span>
                                        <span class="float-right text-sm text-muted">
                                            <?= date('M d, h:i A', strtotime($activity['timestamp'])) ?>
                                        </span>
                                        <br>
                                        <small>
                                            <?= htmlspecialchars($activity['page']) ?> - 
                                            <span class="badge badge-<?= $activity['access_type'] === 'success' ? 'success' : 'danger' ?>">
                                                <?= htmlspecialchars(ucfirst($activity['access_type'])) ?>
                                            </span>
                                        </small>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- /.Left Column -->

                    <!-- Right Column -->
                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card mb-4">
                            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-bolt text-warning mr-2"></i>Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($_SESSION['role_id'] <= 2): ?>
                                    <div class="col-6 mb-3">
                                        <a href="<?= BASE_URL ?>/modules/courses/create_course.php" class="btn btn-primary btn-block shadow-sm">
                                            <i class="fas fa-plus-circle mr-1"></i> New Course
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role_id'] <= 2): ?>
                                    <div class="col-6 mb-3">
                                        <a href="<?= BASE_URL ?>/modules/enrollment/enroll_student.php" class="btn btn-success btn-block shadow-sm">
                                            <i class="fas fa-user-plus mr-1"></i> Enroll Student
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role_id'] <= 3): ?>
                                    <div class="col-6 mb-3">
                                        <a href="<?= BASE_URL ?>/modules/attendance/mark_attendance.php" class="btn btn-info btn-block shadow-sm">
                                            <i class="fas fa-calendar-check mr-1"></i> Attendance
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role_id'] <= 3): ?>
                                    <div class="col-6 mb-3">
                                        <a href="<?= BASE_URL ?>/modules/assignments/create_assignment.php" class="btn btn-warning btn-block shadow-sm text-dark">
                                            <i class="fas fa-tasks mr-1"></i> Assignment
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Deadlines - FIXED: Table existence check -->
<?php if ($_SESSION['role_id'] <= 3): ?>
<div class="card mb-4">
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-clock text-danger mr-2"></i>Upcoming Deadlines</h3>
    </div>
    <div class="card-body p-0 mt-2">
        <ul class="list-group list-group-flush border-top-0">
            <?php 
            // CRITICAL FIX: Verify table exists before querying
            $table_check = $pdo->query("SHOW TABLES LIKE 'assignments'");
            $table_exists = $table_check->rowCount() > 0;
            
            $deadlines = [];
            if ($table_exists) {
                try {
                    $deadline_query = "SELECT a.title, a.due_date, c.course_name 
                                      FROM assignments a
                                      JOIN courses c ON a.course_id = c.course_id
                                      WHERE a.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                                      ORDER BY a.due_date ASC
                                      LIMIT 5";
                    $deadline_stmt = $pdo->prepare($deadline_query);
                    $deadline_stmt->execute();
                    $deadlines = $deadline_stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Dashboard error: " . $e->getMessage());
                    $deadlines = [];
                }
            }
            
            foreach ($deadlines as $deadline): ?>
            <li class="list-group-item border-left-0 border-right-0" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fc'" onmouseout="this.style.backgroundColor=''">
                <span class="float-right badge badge-danger rounded-pill px-2 py-1">
                    <?= date('M d', strtotime($deadline['due_date'])) ?>
                </span>
                <h6 class="mb-1 font-weight-bold text-dark"><?= htmlspecialchars($deadline['title']) ?></h6>
                <small class="text-muted"><i class="fas fa-book-open mr-1"></i><?= htmlspecialchars($deadline['course_name']) ?></small>
            </li>
            <?php endforeach; ?>
            
            <?php if (empty($deadlines)): ?>
            <li class="list-group-item text-center text-muted py-4 border-0">
                <?php if (!$table_exists): ?>
                    <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block text-warning opacity-50"></i>
                    Assignments module not initialized
                <?php else: ?>
                    <div class="p-3 bg-light rounded-lg">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50 d-block"></i>
                        No upcoming deadlines!
                    </div>
                <?php endif; ?>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

                        <!-- System Status -->
                        <div class="card">
                            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-server text-secondary mr-2"></i>System Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Database</span>
                                    <span class="badge badge-success rounded-pill px-2">Connected</span>
                                </div>
                                <div class="progress mb-4 rounded-pill" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Storage</span>
                                    <span class="text-dark font-weight-bold" style="font-size: 0.8rem;"><?= round(disk_free_space(".") / 1073741824, 1) ?>GB / 10GB</span>
                                </div>
                                <div class="progress mb-4 rounded-pill" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: <?= min(90, round(disk_free_space(".") / 1073741824 * 10)) ?>%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Security</span>
                                    <span class="badge badge-primary rounded-pill px-2 py-1"><i class="fas fa-shield-alt mr-1"></i>Up to Date</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.Right Column -->
                </div>
                <!-- /.row -->
            </div>
            <!--/. container-fluid -->
        </section>
        <!-- /.Main content -->
    </div>
    <!-- /.content-wrapper -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- REQUIRED SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.dashboard-info-link .info-box {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}
.dashboard-info-link:hover .info-box {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important;
}
</style>

<script>
// Initialize Chart.js
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($dept_names) ?>,
            datasets: [{
                data: <?= json_encode($enrollment_counts) ?>,
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d',
                    '#17a2b8', '#343a40', '#fd7e14', '#e83e8c', '#6610f2'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed} enrollments`;
                        }
                    }
                }
            }
        }
    });
    
    // Success notification (for demo)
    <?php if (isset($_GET['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Operation Successful',
        text: '<?= htmlspecialchars($_GET['success']) ?>',
        timer: 3000,
        showConfirmButton: false
    });
    <?php endif; ?>
});
</script>
</body>
</html>