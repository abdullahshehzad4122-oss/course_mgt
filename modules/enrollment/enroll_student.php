<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access - Admins only for enrolling students globally
if ($_SESSION['role_id'] > 2) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $student_ids = $_POST['student_ids'] ?? [];
    
    $errors = [];
    if ($course_id <= 0) $errors[] = "Please select a course";
    if (empty($student_ids)) $errors[] = "Please select at least one student";
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $insert_query = "INSERT IGNORE INTO enrollments (course_id, student_id, status) VALUES (?, ?, 'active')";
            $insert_stmt = $pdo->prepare($insert_query);
            
            $enrolled_count = 0;
            foreach ($student_ids as $student_id) {
                // Check if already enrolled
                $check_stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
                $check_stmt->execute([$course_id, $student_id]);
                
                if (!$check_stmt->fetchColumn()) {
                    $insert_stmt->execute([$course_id, (int)$student_id]);
                    $enrolled_count++;
                }
            }
            
            $pdo->commit();
            
            if ($enrolled_count > 0) {
                $_SESSION['success'] = "Successfully enrolled $enrolled_count student(s).";
            } else {
                $_SESSION['error'] = "Selected students were already enrolled in this course.";
            }
            
            header('Location: view_enrollments.php?course_id=' . $course_id);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Enrollment error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Get courses for dropdown
$course_query = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
$course_stmt = $pdo->query($course_query);
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students (role_id = 4)
$student_query = "SELECT user_id, username FROM users WHERE role_id = 4 ORDER BY username";
$student_stmt = $pdo->query($student_query);
$students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Enroll Students</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/enrollment/enroll_student.php') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">Enrollment Form</h3>
                            </div>
                            
                            <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mx-3 mt-3">
                                <h5><i class="icon fas fa-ban"></i> Validation Errors</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="enroll_student.php">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="course_id">Course *</label>
                                        <select class="form-control" id="course_id" name="course_id" required>
                                            <option value="">-- Select Course --</option>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>" 
                                                <?= $selected_course_id == $course['course_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Select Students *</label>
                                        <div class="row border rounded p-3 ml-1 mr-1" style="max-height: 300px; overflow-y: auto;">
                                            <?php foreach ($students as $student): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input class="custom-control-input" type="checkbox" 
                                                           id="student_<?= $student['user_id'] ?>" 
                                                           name="student_ids[]" value="<?= $student['user_id'] ?>"
                                                           <?= (isset($_POST['student_ids']) && in_array($student['user_id'], $_POST['student_ids'])) ? 'checked' : '' ?>>
                                                    <label for="student_<?= $student['user_id'] ?>" class="custom-control-label">
                                                        <?= htmlspecialchars($student['username']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success">Enroll Selected Students</button>
                                    <a href="view_enrollments.php" class="btn btn-secondary">View Current Enrollments</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title"><i class="fas fa-info-circle"></i> Instructions</h3>
                            </div>
                            <div class="card-body">
                                <p>Select a course and one or more students to enroll them. By default, enrollments are marked as <strong>active</strong>.</p>
                                <p>Students already enrolled in the selected course will be ignored during the bulk operation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
