<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Only instructors and admins can view attendance
if ($_SESSION['role_id'] > 3) {
    header('Location: ' . BASE_URL . '/dashboard.php');
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
$selected_date = isset($_GET['session_date']) ? $_GET['session_date'] : '';

$attendance_records = [];
$session_dates = [];

if ($selected_course_id > 0) {
    // Get all unique session dates for this course
    $date_stmt = $pdo->prepare("SELECT DISTINCT session_date FROM attendance WHERE course_id = ? ORDER BY session_date DESC");
    $date_stmt->execute([$selected_course_id]);
    $session_dates = $date_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!$selected_date && !empty($session_dates)) {
        $selected_date = $session_dates[0]; // Default to most recent
    }
    
    if ($selected_date) {
        // Get attendance records for selected course and date
        $att_stmt = $pdo->prepare("
            SELECT u.username, a.status, a.recorded_at,
                   recorded_by_u.username as recorded_by_name
            FROM attendance a
            JOIN users u ON a.student_id = u.user_id
            LEFT JOIN users recorded_by_u ON a.recorded_by = recorded_by_u.user_id
            WHERE a.course_id = ? AND a.session_date = ?
            ORDER BY u.username
        ");
        $att_stmt->execute([$selected_course_id, $selected_date]);
        $attendance_records = $att_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Calculate summary stats
$present = count(array_filter($attendance_records, fn($r) => $r['status'] === 'present'));
$absent  = count(array_filter($attendance_records, fn($r) => $r['status'] === 'absent'));
$late    = count(array_filter($attendance_records, fn($r) => $r['status'] === 'late'));
$total   = count($attendance_records);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Attendance Report</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/attendance/view_attendance.php') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                <!-- Filters -->
                <div class="card card-outline card-info mb-4">
                    <div class="card-body">
                        <form method="GET" action="view_attendance.php" class="form-inline flex-wrap">
                            <div class="form-group mr-3 mb-2">
                                <label class="mr-2">Course:</label>
                                <select class="form-control" name="course_id" onchange="this.form.submit()" style="min-width:280px;">
                                    <option value="0">-- Select Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>" <?= $selected_course_id == $course['course_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($selected_course_id > 0 && !empty($session_dates)): ?>
                            <div class="form-group mr-3 mb-2">
                                <label class="mr-2">Session Date:</label>
                                <select class="form-control" name="session_date" onchange="this.form.submit()">
                                    <?php foreach ($session_dates as $date): ?>
                                    <option value="<?= $date ?>" <?= $selected_date == $date ? 'selected' : '' ?>>
                                        <?= date('l, M d Y', strtotime($date)) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($selected_course_id > 0): ?>
                            <div class="mb-2">
                                <a href="mark_attendance.php?course_id=<?= $selected_course_id ?><?= $selected_date ? '&session_date='.$selected_date : '' ?>" class="btn btn-info">
                                    <i class="fas fa-edit mr-1"></i> Edit / Mark Attendance
                                </a>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <?php if ($selected_course_id > 0 && empty($session_dates)): ?>
                <div class="alert alert-warning">No attendance has been recorded for this course yet. <a href="mark_attendance.php?course_id=<?= $selected_course_id ?>">Mark attendance now</a>.</div>
                <?php endif; ?>

                <?php if ($selected_date && !empty($attendance_records)): ?>
                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Present</span>
                                <span class="info-box-number"><?= $present ?> / <?= $total ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Absent</span>
                                <span class="info-box-number"><?= $absent ?> / <?= $total ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Late</span>
                                <span class="info-box-number"><?= $late ?> / <?= $total ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Attendance Rate</span>
                                <span class="info-box-number"><?= $total > 0 ? round($present / $total * 100) : 0 ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="card">
                    <div class="card-header bg-dark">
                        <h3 class="card-title">Attendance for <?= date('l, F d Y', strtotime($selected_date)) ?></h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                    <th>Recorded At</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $index => $record): ?>
                                <?php
                                    $badge = 'secondary';
                                    if ($record['status'] === 'present') $badge = 'success';
                                    if ($record['status'] === 'absent')  $badge = 'danger';
                                    if ($record['status'] === 'late')    $badge = 'warning';
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="font-weight-bold"><?= htmlspecialchars($record['username']) ?></td>
                                    <td><span class="badge badge-<?= $badge ?> px-3 py-1"><?= ucfirst($record['status']) ?></span></td>
                                    <td><?= date('h:i A', strtotime($record['recorded_at'])) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($record['recorded_by_name'] ?? 'System') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
