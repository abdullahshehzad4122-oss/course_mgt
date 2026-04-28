<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access - only Instructors and Admins can create assignments
if ($_SESSION['role_id'] > 3) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = trim($_POST['due_date']);
    
    $errors = [];
    if ($course_id <= 0) $errors[] = "Please select a course";
    if (empty($title)) $errors[] = "Assignment title is required";
    if (empty($due_date)) $errors[] = "Due date is required";
    
    if (empty($errors)) {
        try {
            $query = "INSERT INTO assignments (course_id, title, description, due_date, created_by)
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $course_id,
                $title,
                $description,
                $due_date,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Assignment created successfully";
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log("Assignment creation error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Get courses for dropdown
// If instructor, only show their courses. If admin, show all active courses.
if ($_SESSION['role_id'] == 3) {
    $course_query = "SELECT course_id, course_code, course_name FROM courses WHERE instructor_id = ? ORDER BY course_code";
    $course_stmt = $pdo->prepare($course_query);
    $course_stmt->execute([$_SESSION['user_id']]);
} else {
    $course_query = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
    $course_stmt = $pdo->query($course_query);
}
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Create New Assignment</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/assignments/create_assignment.php') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Assignment Details</h3>
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
                            
                            <form method="POST" action="create_assignment.php">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="course_id">Course *</label>
                                        <select class="form-control" id="course_id" name="course_id" required>
                                            <option value="">-- Select Course --</option>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>" 
                                                <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="title">Assignment Title *</label>
                                        <input type="text" class="form-control" id="title" 
                                               name="title" placeholder="Enter assignment title" 
                                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description">Description & Instructions</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" 
                                                  placeholder="Enter detailed instructions for the students."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="due_date">Due Date & Time *</label>
                                        <input type="datetime-local" class="form-control" id="due_date" 
                                               name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>" required>
                                    </div>
                                    
                                    <small class="text-danger">* Required fields</small>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-warning">Create Assignment</button>
                                    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-secondary">Cancel</a>
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
                                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Be specific with titles</li>
                                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Clearly explain all requirements</li>
                                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Review date before submitting</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
