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

$query = "
    SELECT a.session_date, a.status, c.course_code, c.course_name 
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.student_id = :user_id
";
$params = [':user_id' => $user_id];

if ($filter_course_id) {
    $query .= " AND c.course_id = :course_id";
    $params[':course_id'] = $filter_course_id;
}

$query .= " ORDER BY a.session_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total = count($attendance_records);
$present = 0; $absent = 0; $late = 0;
foreach($attendance_records as $r) {
    if ($r['status'] == 'present') $present++;
    elseif ($r['status'] == 'absent') $absent++;
    elseif ($r['status'] == 'late') $late++;
}

$attendance_rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

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
.custom-table th { border-top: none; background: rgba(0,0,0,0.03); }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">My Attendance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">My Attendance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="card student-glass text-center border-0 mb-4">
                        <div class="card-body py-5">
                            <!-- Chart Canvas -->
                            <div style="position: relative; height: 150px; width: 150px; margin: 0 auto;">
                                <canvas id="attendanceChart"></canvas>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <h3 class="mb-0 font-weight-bold text-dark"><?= $attendance_rate ?>%</h3>
                                </div>
                            </div>
                            <h5 class="mt-4 font-weight-bold">Overall Rate</h5>
                            <p class="text-muted small">Present or Late</p>
                            
                            <hr class="mt-4 mb-3">
                            <ul class="list-unstyled text-left px-3">
                                <li class="mb-2"><i class="fas fa-circle text-success mr-2"></i> Present: <strong class="float-right"><?= $present ?></strong></li>
                                <li class="mb-2"><i class="fas fa-circle text-warning mr-2"></i> Late: <strong class="float-right"><?= $late ?></strong></li>
                                <li><i class="fas fa-circle text-danger mr-2"></i> Absent: <strong class="float-right"><?= $absent ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card student-glass border-0">
                        <div class="card-body p-0" style="border-radius: 15px; overflow: hidden;">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 custom-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Course</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td class="align-middle font-weight-bold">
                                                <?= date('D, M d, Y', strtotime($record['session_date'])) ?>
                                            </td>
                                            <td class="align-middle">
                                                <i class="fas fa-book-open mr-2 text-muted"></i> <?= htmlspecialchars($record['course_code']) ?> - <?= htmlspecialchars($record['course_name']) ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($record['status'] == 'present'): ?>
                                                    <span class="badge badge-success px-3 py-2 rounded-pill"><i class="fas fa-check mr-1"></i> Present</span>
                                                <?php elseif ($record['status'] == 'absent'): ?>
                                                    <span class="badge badge-danger px-3 py-2 rounded-pill"><i class="fas fa-times mr-1"></i> Absent</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning px-3 py-2 rounded-pill"><i class="fas fa-clock mr-1"></i> Late</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-5 text-muted">
                                                <i class="far fa-calendar-times fa-3x mb-3 opacity-50 text-secondary"></i><br>
                                                <h5>No attendance records found.</h5>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($total > 0): ?>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Late', 'Absent'],
            datasets: [{
                data: [<?= $present ?>, <?= $late ?>, <?= $absent ?>],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '75%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return ` ${context.label}: ${context.raw}`; }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
