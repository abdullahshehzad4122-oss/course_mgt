<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';


// Enforce access control (Section 9.5)
checkAccess();

// Verify application layer readiness
if (!tableExists($pdo, 'courses')) {
    $_SESSION['setup_warning'] = "Courses module not initialized. Contact administrator.";
    header('Location: ../../dashboard.php');
    exit;
}

// Handle course deletion (Admin only)
if ($_SESSION['role_id'] <= 2 && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    
    try {
        // Check if course has enrollments
        $enrollment_check = $pdo->prepare("SELECT 1 FROM enrollments WHERE course_id = ?");
        $enrollment_check->execute([$course_id]);
        
        if ($enrollment_check->fetchColumn()) {
            $_SESSION['error'] = "Cannot delete course with active enrollments";
        } else {
            // Delete course
            $delete_query = "DELETE FROM courses WHERE course_id = ?";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->execute([$course_id]);
            
            $_SESSION['success'] = "Course deleted successfully";
            logAccessViolation('delete_course', 'success');
        }
    } catch (PDOException $e) {
        error_log("Course deletion error: " . $e->getMessage());
        $_SESSION['error'] = "Database error. Please try again.";
    }
    
    header('Location: view_courses.php');
    exit;
}

// Get filter parameters
$dept_id = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$instructor_id = isset($_GET['instructor']) ? (int)$_GET['instructor'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query dynamically based on role
$params = [];
$conditions = [];

// Role-based data access (Section 9.5)
if ($_SESSION['role_id'] == 3) { // Instructor
    $conditions[] = "instructor_id = ?";
    $params[] = $_SESSION['user_id'];
} elseif ($_SESSION['role_id'] == 4) { // Student
    $conditions[] = "course_id IN (SELECT course_id FROM enrollments WHERE student_id = ?)";
    $params[] = $_SESSION['user_id'];
}

// Apply filters
if ($dept_id > 0) {
    $conditions[] = "dept_id = ?";
    $params[] = $dept_id;
}

if ($instructor_id > 0) {
    $conditions[] = "instructor_id = ?";
    $params[] = $instructor_id;
}

if (!empty($search)) {
    $conditions[] = "(course_code LIKE ? OR course_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Build WHERE clause
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM courses $where";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_courses = $count_stmt->fetchColumn();

// Pagination
$per_page = 10;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $per_page;

// Get courses with pagination
$query = "SELECT c.*, d.dept_name, u.username as instructor_name 
          FROM courses c
          LEFT JOIN departments d ON c.dept_id = d.dept_id
          LEFT JOIN users u ON c.instructor_id = u.user_id
          $where
          ORDER BY course_code
          LIMIT $per_page OFFSET $offset";
          
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for filter
$dept_query = "SELECT * FROM departments ORDER BY dept_name";
$dept_stmt = $pdo->query($dept_query);
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get instructors for filter
$instructor_query = "SELECT user_id, username FROM users WHERE role_id = 3 ORDER BY username";
$instructor_stmt = $pdo->query($instructor_query);
$instructors = $instructor_stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>
<!-- Page-specific CSS for DataTables -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.13.4/css/dataTables.bootstrap4.min.css">
<?php
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Course Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Courses</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="card-title">Course List</h3>
                                    <?php if ($_SESSION['role_id'] <= 2): ?>
                                    <a href="create_course.php" class="btn btn-primary">
                                        <i class="fas fa-plus mr-1"></i> Create New Course
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <!-- Filters -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <select class="form-control" id="filter_dept">
                                            <option value="0">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['dept_id'] ?>" 
                                                <?= ($dept_id == $dept['dept_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['dept_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <select class="form-control" id="filter_instructor">
                                            <option value="0">All Instructors</option>
                                            <?php foreach ($instructors as $instructor): ?>
                                            <option value="<?= $instructor['user_id'] ?>" 
                                                <?= ($instructor_id == $instructor['user_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($instructor['username']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="search" 
                                                   placeholder="Search courses..." value="<?= htmlspecialchars($search) ?>">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="search_btn">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <button class="btn btn-outline-secondary w-100" id="reset_filters">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Course Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="courses_table">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Course Name</th>
                                                <th>Department</th>
                                                <th>Instructor</th>
                                                <th>Enrollments</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courses as $course): 
                                                // Get enrollment count
                                                $enroll_query = "SELECT COUNT(*) FROM enrollments WHERE course_id = ?";
                                                $enroll_stmt = $pdo->prepare($enroll_query);
                                                $enroll_stmt->execute([$course['course_id']]);
                                                $enrollment_count = $enroll_stmt->fetchColumn();
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($course['course_code']) ?></strong></td>
                                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                                <td><?= htmlspecialchars($course['dept_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($course['instructor_name'] ?? 'Unassigned') ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-info"><?= $enrollment_count ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $enrollment_count > 0 ? 'success' : 'warning' ?>">
                                                        <?= $enrollment_count > 0 ? 'Active' : 'Pending' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view_course.php?id=<?= $course['course_id'] ?>" 
                                                           class="btn btn-sm btn-info" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($_SESSION['role_id'] <= 2 || 
                                                                 ($_SESSION['role_id'] == 3 && $_SESSION['user_id'] == $course['instructor_id'])): ?>
                                                        <a href="edit_course.php?id=<?= $course['course_id'] ?>" 
                                                           class="btn btn-sm btn-primary" title="Edit Course">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($_SESSION['role_id'] <= 2 && $enrollment_count == 0): ?>
                                                        <a href="view_courses.php?delete=<?= $course['course_id'] ?>" 
                                                           class="btn btn-sm btn-danger delete-course" 
                                                           title="Delete Course" 
                                                           onclick="return confirm('Are you sure you want to delete this course?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_courses > $per_page): ?>
                                <div class="d-flex justify-content-between mt-3">
                                    <div>
                                        Showing <?= min($offset + 1, $total_courses) ?> to 
                                        <?= min($offset + $per_page, $total_courses) ?> 
                                        of <?= $total_courses ?> courses
                                    </div>
                                    
                                    <nav>
                                        <ul class="pagination">
                                            <?php $prev_page = max(1, $current_page - 1); ?>
                                            <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="view_courses.php?page=<?= $prev_page ?>&dept=<?= $dept_id ?>&instructor=<?= $instructor_id ?>&search=<?= urlencode($search) ?>">
                                                    Previous
                                                </a>
                                            </li>
                                            
                                            <?php 
                                            $total_pages = ceil($total_courses / $per_page);
                                            $start_page = max(1, $current_page - 2);
                                            $end_page = min($total_pages, $current_page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): 
                                            ?>
                                            <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                                <a class="page-link" href="view_courses.php?page=<?= $i ?>&dept=<?= $dept_id ?>&instructor=<?= $instructor_id ?>&search=<?= urlencode($search) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                            <?php endfor; ?>
                                            
                                            <?php $next_page = min($total_pages, $current_page + 1); ?>
                                            <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="view_courses.php?page=<?= $next_page ?>&dept=<?= $dept_id ?>&instructor=<?= $instructor_id ?>&search=<?= urlencode($search) ?>">
                                                    Next
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>

<!-- Page specific scripts -->
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#courses_table').DataTable({
        "paging": false,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": false,
        "autoWidth": false,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": 6 }
        ]
    });
    
    // Filter functionality
    $('#filter_dept, #filter_instructor').change(function() {
        applyFilters();
    });
    
    $('#search_btn').click(function() {
        applyFilters();
    });
    
    $('#search').keypress(function(e) {
        if (e.which == 13) { // Enter key
            applyFilters();
        }
    });
    
    $('#reset_filters').click(function() {
        $('#filter_dept').val('0');
        $('#filter_instructor').val('0');
        $('#search').val('');
        applyFilters();
    });
    
    function applyFilters() {
        const dept = $('#filter_dept').val();
        const instructor = $('#filter_instructor').val();
        const search = $('#search').val().trim();
        
        let url = 'view_courses.php';
        const params = [];
        
        if (dept != 0) params.push('dept=' + dept);
        if (instructor != 0) params.push('instructor=' + instructor);
        if (search) params.push('search=' + encodeURIComponent(search));
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        window.location.href = url;
    }
});
</script>