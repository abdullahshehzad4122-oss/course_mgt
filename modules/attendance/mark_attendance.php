<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access - only Instructors and Admins can mark attendance
if ($_SESSION['role_id'] > 3) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark') {
    $course_id = (int)$_POST['course_id'];
    $session_date = trim($_POST['session_date']);
    $attendance_data = $_POST['attendance'] ?? [];
    
    $errors = [];
    if ($course_id <= 0) $errors[] = "Please select a course";
    if (empty($session_date)) $errors[] = "Session date is required";
    if (empty($attendance_data)) $errors[] = "No attendance data provided";
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Delete existing attendance for this course and date to avoid duplicates
            $del_stmt = $pdo->prepare("DELETE FROM attendance WHERE course_id = ? AND session_date = ?");
            $del_stmt->execute([$course_id, $session_date]);
            
            // Insert new attendance records
            $insert_query = "INSERT INTO attendance (course_id, student_id, session_date, status, recorded_by) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_query);
            
            foreach ($attendance_data as $student_id => $status) {
                $insert_stmt->execute([
                    $course_id,
                    $student_id,
                    $session_date,
                    $status,
                    $_SESSION['user_id']
                ]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Attendance marked successfully";
            
            // Redirect to avoid form resubmission
            header("Location: mark_attendance.php?course_id=$course_id&session_date=$session_date");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Attendance error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
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

// If course is selected, fetch enrolled students
$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0);
$selected_date = isset($_GET['session_date']) ? $_GET['session_date'] : (isset($_POST['session_date']) ? $_POST['session_date'] : date('Y-m-d'));
$show_pending = isset($_GET['pending']);

$students = [];
$existing_attendance = [];
$pending_courses = [];

if ($show_pending && empty($selected_course_id)) {
    $pending_query = "
        SELECT DISTINCT c.course_id, c.course_code, c.course_name
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
        WHERE c.course_id NOT IN (
            SELECT DISTINCT course_id FROM attendance WHERE session_date = CURDATE()
        )
    ";
    if ($_SESSION['role_id'] == 3) {
        $pending_query .= " AND c.instructor_id = ?";
        $pending_stmt = $pdo->prepare($pending_query);
        $pending_stmt->execute([$_SESSION['user_id']]);
    } else {
        $pending_stmt = $pdo->query($pending_query);
    }
    $pending_courses = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($selected_course_id > 0) {
    // Fetch students
    $student_query = "SELECT u.user_id, u.username 
                     FROM enrollments e
                     JOIN users u ON e.student_id = u.user_id
                     WHERE e.course_id = ? AND e.status != 'dropped'
                     ORDER BY u.username";
    $student_stmt = $pdo->prepare($student_query);
    $student_stmt->execute([$selected_course_id]);
    $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch existing attendance if any
    $att_query = "SELECT student_id, status FROM attendance WHERE course_id = ? AND session_date = ?";
    $att_stmt = $pdo->prepare($att_query);
    $att_stmt->execute([$selected_course_id, $selected_date]);
    
    while ($row = $att_stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_attendance[$row['student_id']] = $row['status'];
    }
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
                        <h1 class="m-0">Mark Attendance</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/attendance/mark_attendance.php') ?>
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
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Select Course & Date</h3>
                    </div>
                    
                    <form method="GET" action="mark_attendance.php">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Course *</label>
                                        <select class="form-control" name="course_id" required onchange="this.form.submit()">
                                            <option value="">-- Select Course --</option>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>" <?= $selected_course_id == $course['course_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Session Date *</label>
                                        <input type="date" class="form-control" name="session_date" 
                                               value="<?= htmlspecialchars($selected_date) ?>" required onchange="this.form.submit()">
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4 pt-2">
                                    <button type="submit" class="btn btn-info w-100">Load Roster</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($show_pending && empty($selected_course_id)): ?>
                <div class="card card-warning card-outline mb-4 shadow-sm border-warning">
                    <div class="card-header bg-white">
                        <h3 class="card-title text-warning font-weight-bold"><i class="fas fa-exclamation-triangle mr-2"></i>Action Required: Attendance Due Today</h3>
                    </div>
                    <div class="card-body p-0 border-top-0">
                        <?php if (empty($pending_courses)): ?>
                            <div class="p-4 text-center text-success bg-light">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-75"></i>
                                All clear! No courses need attendance marked for today.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush border-top-0">
                                <?php foreach ($pending_courses as $pc): ?>
                                    <a href="mark_attendance.php?course_id=<?= $pc['course_id'] ?>&session_date=<?= date('Y-m-d') ?>" class="list-group-item list-group-item-action border-left-0 border-right-0 d-flex justify-content-between align-items-center" style="transition: all 0.2s;" onmouseover="this.className='list-group-item list-group-item-action border-left-0 border-right-0 d-flex justify-content-between align-items-center bg-light'" onmouseout="this.className='list-group-item list-group-item-action border-left-0 border-right-0 d-flex justify-content-between align-items-center'">
                                        <div>
                                            <span class="badge badge-secondary mr-2 px-2 py-1"><?= htmlspecialchars($pc['course_code']) ?></span>
                                            <strong class="text-dark"><?= htmlspecialchars($pc['course_name']) ?></strong>
                                        </div>
                                        <span class="btn btn-sm btn-outline-warning text-dark font-weight-bold rounded-pill px-3 shadow-sm" style="background-color: #ffc107; border-color: #ffc107;"><i class="fas fa-hand-pointer mr-1"></i> Mark Now</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($selected_course_id > 0): ?>
                <div class="card">
                    <div class="card-header bg-dark">
                        <h3 class="card-title">Class Roster</h3>
                    </div>
                    
                    <form method="POST" action="mark_attendance.php">
                        <input type="hidden" name="action" value="mark">
                        <input type="hidden" name="course_id" value="<?= $selected_course_id ?>">
                        <input type="hidden" name="session_date" value="<?= htmlspecialchars($selected_date) ?>">
                        
                        <div class="card-body p-0">
                            <?php if (empty($students)): ?>
                                <div class="p-4 text-center text-muted">No students enrolled in this course.</div>
                            <?php else: ?>
                            <table class="table table-striped table-bordered text-center align-middle">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): 
                                        $sid = $student['user_id'];
                                        $current_status = $existing_attendance[$sid] ?? 'present'; // Default to present
                                    ?>
                                    <tr>
                                        <td class="text-left align-middle font-weight-bold"><?= htmlspecialchars($student['username']) ?></td>
                                        
                                        <td class="bg-success text-white" style="cursor: pointer;" onclick="document.getElementById('att_<?= $sid ?>_p').checked = true;">
                                            <div class="custom-control custom-radio">
                                                <input class="custom-control-input" type="radio" 
                                                    id="att_<?= $sid ?>_p" name="attendance[<?= $sid ?>]" value="present"
                                                    <?= $current_status === 'present' ? 'checked' : '' ?>>
                                                <label for="att_<?= $sid ?>_p" class="custom-control-label"></label>
                                            </div>
                                        </td>
                                        
                                        <td class="bg-danger text-white" style="cursor: pointer;" onclick="document.getElementById('att_<?= $sid ?>_a').checked = true;">
                                            <div class="custom-control custom-radio">
                                                <input class="custom-control-input" type="radio" 
                                                    id="att_<?= $sid ?>_a" name="attendance[<?= $sid ?>]" value="absent"
                                                    <?= $current_status === 'absent' ? 'checked' : '' ?>>
                                                <label for="att_<?= $sid ?>_a" class="custom-control-label"></label>
                                            </div>
                                        </td>
                                        
                                        <td class="bg-warning text-dark" style="cursor: pointer;" onclick="document.getElementById('att_<?= $sid ?>_l').checked = true;">
                                            <div class="custom-control custom-radio">
                                                <input class="custom-control-input" type="radio" 
                                                    id="att_<?= $sid ?>_l" name="attendance[<?= $sid ?>]" value="late"
                                                    <?= $current_status === 'late' ? 'checked' : '' ?>>
                                                <label for="att_<?= $sid ?>_l" class="custom-control-label"></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-lg" <?= empty($students) ? 'disabled' : '' ?>>
                                <i class="fas fa-save mr-2"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
