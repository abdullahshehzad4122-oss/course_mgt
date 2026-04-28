<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access (Section 9.5)
checkAccess('edit');

// Verify application layer readiness (Section 6.3)
if (!tableExists($pdo, 'departments') || !tableExists($pdo, 'users')) {
    $_SESSION['setup_warning'] = "Required tables missing. Contact administrator.";
    header('Location: view_courses.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs (Section 10 security)
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $dept_id = (int)$_POST['dept_id'];
    $instructor_id = (int)$_POST['instructor_id'];
    
    // Server-side validation (Section 10)
    $errors = [];
    if (empty($course_code)) $errors[] = "Course code is required";
    if (empty($course_name)) $errors[] = "Course name is required";
    if ($dept_id <= 0) $errors[] = "Invalid department selected";
    
    // Check for duplicate course code
    $check_query = "SELECT 1 FROM courses WHERE course_code = ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$course_code]);
    if ($check_stmt->fetchColumn()) {
        $errors[] = "Course code already exists";
    }
    
    if (empty($errors)) {
        try {
            // Create course (Section 8 schema compliance)
            $query = "INSERT INTO courses (course_code, course_name, dept_id, instructor_id)
                     VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $course_code,
                $course_name,
                $dept_id,
                $instructor_id ?: null
            ]);
            
            // Log success (Section 10 security)
            logAccessViolation('create_course', 'success');
            
            // Redirect with success message
            $_SESSION['success'] = "Course created successfully";
            header('Location: view_courses.php');
            exit;
        } catch (PDOException $e) {
            error_log("Course creation error: " . $e->getMessage());
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
                        <h1 class="m-0">Create New Course</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="view_courses.php">Courses</a></li>
                            <li class="breadcrumb-item active">Create Course</li>
                        </ol>
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
                            <div class="alert alert-danger">
                                <h5><i class="icon fas fa-ban"></i> Validation Errors</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="create_course.php">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="course_code">Course Code *</label>
                                        <input type="text" class="form-control" id="course_code" 
                                               name="course_code" placeholder="Enter course code" 
                                               value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>" required>
                                        <small class="form-text text-muted">Example: CS101, MATH202</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="course_name">Course Name *</label>
                                        <input type="text" class="form-control" id="course_name" 
                                               name="course_name" placeholder="Enter course name" 
                                               value="<?= htmlspecialchars($_POST['course_name'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="dept_id">Department *</label>
                                        <select class="form-control" id="dept_id" name="dept_id" required>
                                            <option value="">-- Select Department --</option>
                                            <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['dept_id'] ?>" 
                                                <?= (isset($_POST['dept_id']) && $_POST['dept_id'] == $dept['dept_id']) ? 'selected' : '' ?>>
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
                                            <option value="<?= $instructor['user_id'] ?>" 
                                                <?= (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['user_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($instructor['username']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input" type="checkbox" id="active" name="active" checked>
                                            <label for="active" class="custom-control-label">Active Course</label>
                                        </div>
                                    </div>
                                    
                                    <small class="text-danger">* Required fields</small>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Create Course</button>
                                    <a href="view_courses.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Guidelines</h3>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Course code must be unique</li>
                                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Use standard department codes</li>
                                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Instructors must have role "Instructor"</li>
                                    <li class="mb-2"><i class="fas fa-info-circle text-primary mr-2"></i> Active courses appear in enrollment</li>
                                </ul>
                                
                                <div class="alert alert-info mt-3">
                                    <h5><i class="icon fas fa-info"></i> Academic Policy</h5>
                                    <p>All courses must be approved by the Academic Council before activation.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>