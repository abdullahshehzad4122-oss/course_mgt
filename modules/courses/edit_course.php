<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access - Admin or Super Admin
if ($_SESSION['role_id'] > 2) {
    header('Location: view_courses.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid course ID.";
    header('Location: view_courses.php');
    exit;
}

$course_id = (int)$_GET['id'];

// Get existing course data
$query = "SELECT * FROM courses WHERE course_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    $_SESSION['error'] = "Course not found.";
    header('Location: view_courses.php');
    exit;
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $dept_id = (int)$_POST['dept_id'];
    $instructor_id = (int)$_POST['instructor_id'];
    
    // Server-side validation
    if (empty($course_code)) $errors[] = "Course code is required";
    if (empty($course_name)) $errors[] = "Course name is required";
    if ($dept_id <= 0) $errors[] = "Invalid department selected";
    
    // Check for duplicate course code (excluding the current course)
    $check_query = "SELECT 1 FROM courses WHERE course_code = ? AND course_id != ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$course_code, $course_id]);
    if ($check_stmt->fetchColumn()) {
        $errors[] = "Course code already exists in another course";
    }
    
    if (empty($errors)) {
        try {
            $update_query = "UPDATE courses 
                            SET course_code = ?, course_name = ?, dept_id = ?, instructor_id = ? 
                            WHERE course_id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                $course_code,
                $course_name,
                $dept_id,
                $instructor_id ?: null,
                $course_id
            ]);
            
            $_SESSION['success'] = "Course updated successfully";
            header('Location: view_courses.php');
            exit;
        } catch (PDOException $e) {
            error_log("Course update error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Get departments for dropdown
$dept_query = "SELECT * FROM departments ORDER BY dept_name";
$dept_stmt = $pdo->query($dept_query);
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get instructors for dropdown (role_id = 3)
$instructor_query = "SELECT user_id, username FROM users WHERE role_id = 3 ORDER BY username";
$instructor_stmt = $pdo->query($instructor_query);
$instructors = $instructor_stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Course</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/courses/edit_course.php') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Course Information</h3>
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
                            
                            <form method="POST" action="edit_course.php?id=<?= $course_id ?>">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="course_code">Course Code *</label>
                                        <input type="text" class="form-control" id="course_code" 
                                               name="course_code" placeholder="Enter course code" 
                                               value="<?= htmlspecialchars($_POST['course_code'] ?? $course['course_code']) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="course_name">Course Name *</label>
                                        <input type="text" class="form-control" id="course_name" 
                                               name="course_name" placeholder="Enter course name" 
                                               value="<?= htmlspecialchars($_POST['course_name'] ?? $course['course_name']) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="dept_id">Department *</label>
                                        <select class="form-control" id="dept_id" name="dept_id" required>
                                            <option value="">-- Select Department --</option>
                                            <?php foreach ($departments as $dept): ?>
                                            <?php $selected_dept = $_POST['dept_id'] ?? $course['dept_id']; ?>
                                            <option value="<?= $dept['dept_id'] ?>" 
                                                <?= ($selected_dept == $dept['dept_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['dept_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="instructor_id">Instructor</label>
                                        <select class="form-control" id="instructor_id" name="instructor_id">
                                            <option value="">-- Select Instructor (Optional) --</option>
                                            <?php foreach ($instructors as $instructor): ?>
                                            <?php $selected_inst = $_POST['instructor_id'] ?? $course['instructor_id']; ?>
                                            <option value="<?= $instructor['user_id'] ?>" 
                                                <?= ($selected_inst == $instructor['user_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($instructor['username']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <small class="text-danger">* Required fields</small>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <a href="view_courses.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
