<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access - Admins only
if ($_SESSION['role_id'] > 2) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// ── SELF-HEALING SCHEMA UPDATE ──────────────────────────────────────────────
try {
    // 1. Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `dept_id`     int(11)      NOT NULL AUTO_INCREMENT,
        `dept_name`   varchar(100) NOT NULL,
        `dept_code`   varchar(20)  NOT NULL DEFAULT '',
        `description` text         DEFAULT NULL,
        `created_at`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`dept_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Add missing columns if they don't exist
    $existingCols = array_column($pdo->query("SHOW COLUMNS FROM departments")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    if (!in_array('dept_code', $existingCols)) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN dept_code varchar(20) NOT NULL DEFAULT '' AFTER dept_name");
    }
    
    if (!in_array('description', $existingCols)) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN description text DEFAULT NULL AFTER dept_code");
    }

    // 3. Add unique indexes if missing
    $indexes = array_column($pdo->query("SHOW INDEX FROM departments")->fetchAll(PDO::FETCH_ASSOC), 'Key_name');
    if (!in_array('uq_dept_code', $indexes)) {
        try { $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY uq_dept_code (dept_code)"); } catch (PDOException $e) {}
    }
    if (!in_array('uq_dept_name', $indexes)) {
        try { $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY uq_dept_name (dept_name)"); } catch (PDOException $e) {}
    }

    // 4. Ensure sidebar menu is registered
    // Check for parent group (allow either NULL or 0 for parent_id in check)
    $parentCheck = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_title = 'Departments' AND (parent_id IS NULL OR parent_id = 0)");
    $parentCheck->execute();
    $parent_id = $parentCheck->fetchColumn();

    if (!$parent_id) {
        $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, '#', 0, ?)")
            ->execute(['Departments', 'fas fa-building']);
        $parent_id = $pdo->lastInsertId();
    }

    // Check for child page
    $pageUrl = 'modules/departments/manage_departments.php';
    $childCheck = $pdo->prepare("SELECT page_id FROM sys_pages WHERE page_url = ?");
    $childCheck->execute([$pageUrl]);
    $child_page_id = $childCheck->fetchColumn();

    if (!$child_page_id) {
        $pdo->prepare("INSERT INTO sys_pages (page_title, page_url, parent_id, icon_class) VALUES (?, ?, ?, ?)")
            ->execute(['Manage Departments', $pageUrl, $parent_id, 'far fa-circle']);
        $child_page_id = $pdo->lastInsertId();
    }

    // Assign access to roles 1, 2
    $ra = $pdo->prepare("INSERT IGNORE INTO role_access (role_id, page_id, can_view) VALUES (?, ?, 1)");
    foreach ([1, 2] as $role) {
        $ra->execute([$role, $parent_id]);
        $ra->execute([$role, $child_page_id]);
    }

} catch (PDOException $e) {
    error_log("Department schema auto-fix failed: " . $e->getMessage());
    // Continue anyway; main logic will catch if query fails
}
// ─────────────────────────────────────────────────────────────────────────────

$errors = [];

// ── CREATE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $dept_name = trim($_POST['dept_name']);
    $dept_code = strtoupper(trim($_POST['dept_code']));
    $description = trim($_POST['description'] ?? '');

    if (empty($dept_name)) $errors[] = "Department name is required.";
    if (empty($dept_code)) $errors[] = "Department code is required.";

    if (empty($errors)) {
        // Duplicate check
        $chk = $pdo->prepare("SELECT 1 FROM departments WHERE dept_code = ? OR dept_name = ?");
        $chk->execute([$dept_code, $dept_name]);
        if ($chk->fetchColumn()) {
            $errors[] = "A department with that name or code already exists.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("INSERT INTO departments (dept_name, dept_code, description) VALUES (?, ?, ?)")
                ->execute([$dept_name, $dept_code, $description ?: null]);
            $_SESSION['success'] = "Department '$dept_name' created successfully.";
            header('Location: manage_departments.php');
            exit;
        } catch (PDOException $e) {
            error_log("Dept create error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// ── UPDATE ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $edit_id   = (int)$_POST['dept_id'];
    $dept_name = trim($_POST['dept_name']);
    $dept_code = strtoupper(trim($_POST['dept_code']));
    $description = trim($_POST['description'] ?? '');

    if (empty($dept_name)) $errors[] = "Department name is required.";
    if (empty($dept_code)) $errors[] = "Department code is required.";

    if (empty($errors)) {
        // Duplicate check (exclude current)
        $chk = $pdo->prepare("SELECT 1 FROM departments WHERE (dept_code = ? OR dept_name = ?) AND dept_id != ?");
        $chk->execute([$dept_code, $dept_name, $edit_id]);
        if ($chk->fetchColumn()) {
            $errors[] = "Another department with that name or code already exists.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE departments SET dept_name=?, dept_code=?, description=? WHERE dept_id=?")
                ->execute([$dept_name, $dept_code, $description ?: null, $edit_id]);
            $_SESSION['success'] = "Department updated successfully.";
            header('Location: manage_departments.php');
            exit;
        } catch (PDOException $e) {
            error_log("Dept update error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_SESSION['role_id'] == 1) {
    $del_id = (int)$_GET['delete'];
    try {
        // Check if the department has courses linked to it
        $course_count = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE dept_id = ?");
        $course_count->execute([$del_id]);
        if ($course_count->fetchColumn() > 0) {
            $_SESSION['error'] = "Cannot delete this department — it has courses assigned to it. Reassign those courses first.";
        } else {
            $pdo->prepare("DELETE FROM departments WHERE dept_id = ?")->execute([$del_id]);
            $_SESSION['success'] = "Department deleted successfully.";
        }
    } catch (PDOException $e) {
        error_log("Dept delete error: " . $e->getMessage());
        $_SESSION['error'] = "Could not delete department: " . $e->getMessage();
    }
    header('Location: manage_departments.php');
    exit;
}

// ── FETCH EDIT TARGET ────────────────────────────────────────────────────────
$edit_dept = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $e = $pdo->prepare("SELECT * FROM departments WHERE dept_id = ?");
    $e->execute([(int)$_GET['edit']]);
    $edit_dept = $e->fetch(PDO::FETCH_ASSOC);
}

// ── FETCH ALL DEPARTMENTS (with course count) ────────────────────────────────
$departments = $pdo->query(
    "SELECT d.*, COUNT(c.course_id) AS course_count
     FROM departments d
     LEFT JOIN courses c ON c.dept_id = d.dept_id
     GROUP BY d.dept_id
     ORDER BY d.dept_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manage Departments</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Departments</li>
                    </ol>
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

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <div class="row">
                <!-- ── FORM (Create / Edit) ─────────────────────────────── -->
                <div class="col-md-4">
                    <div class="card card-<?= $edit_dept ? 'warning' : 'primary' ?> card-outline" style="box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); border-top: 3px solid <?= $edit_dept ? '#f6c23e' : '#4e73df' ?>;">
                        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2">
                            <h3 class="card-title font-weight-bold" style="color: <?= $edit_dept ? '#f6c23e' : '#4e73df' ?>;">
                                <i class="fas fa-<?= $edit_dept ? 'edit' : 'plus-circle' ?> mr-2"></i>
                                <?= $edit_dept ? 'Edit Department' : 'New Department' ?>
                            </h3>
                        </div>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mx-3 mt-3 mb-0">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="manage_departments.php<?= $edit_dept ? '?edit=' . $edit_dept['dept_id'] : '' ?>">
                            <input type="hidden" name="action" value="<?= $edit_dept ? 'edit' : 'create' ?>">
                            <?php if ($edit_dept): ?>
                            <input type="hidden" name="dept_id" value="<?= $edit_dept['dept_id'] ?>">
                            <?php endif; ?>

                            <div class="card-body">
                                <div class="form-group mb-4">
                                    <label for="dept_name" class="text-secondary font-weight-bold" style="font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase;">Department Name <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-university text-primary"></i></span>
                                        </div>
                                        <input type="text" class="form-control border-left-0 pl-0" id="dept_name" name="dept_name"
                                               placeholder="e.g. Computer Science"
                                               value="<?= htmlspecialchars($edit_dept['dept_name'] ?? $_POST['dept_name'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="dept_code" class="text-secondary font-weight-bold" style="font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase;">Department Code <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-tag text-success"></i></span>
                                        </div>
                                        <input type="text" class="form-control border-left-0 pl-0" id="dept_code" name="dept_code"
                                               placeholder="e.g. CS" maxlength="20"
                                               style="text-transform:uppercase; font-family: monospace; font-size: 1.1rem; letter-spacing: 2px;"
                                               value="<?= htmlspecialchars($edit_dept['dept_code'] ?? $_POST['dept_code'] ?? '') ?>" required>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle mr-1"></i>Short unique code, e.g. CS, MATH, ENG</small>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="description" class="text-secondary font-weight-bold" style="font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase;">Description</label>
                                    <textarea class="form-control shadow-sm p-3" id="description" name="description"
                                              rows="4" placeholder="Optional description of the department..."><?= htmlspecialchars($edit_dept['description'] ?? $_POST['description'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="card-footer bg-transparent border-top-0 pb-4 pt-2">
                                <button type="submit" class="btn btn-<?= $edit_dept ? 'warning text-dark font-weight-bold' : 'primary' ?> btn-block rounded-pill shadow-sm py-2">
                                    <i class="fas fa-save mr-2"></i>
                                    <?= $edit_dept ? 'Save Changes' : 'Create Department' ?>
                                </button>
                                <?php if ($edit_dept): ?>
                                <a href="manage_departments.php" class="btn btn-light btn-block rounded-pill text-secondary font-weight-bold shadow-sm mt-3 border">Cancel Editing</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ── DEPARTMENT LIST ─────────────────────────────────── -->
                <div class="col-md-8">
                    <div class="card" style="box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);">
                        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold text-dark m-0">
                                <i class="fas fa-building text-info mr-2"></i> All Departments
                            </h3>
                            <div class="card-tools m-0">
                                <span class="badge badge-info rounded-pill px-3 py-2 font-weight-bold" style="font-size: 0.85rem;"><?= count($departments) ?> <span class="font-weight-normal">Total</span></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($departments)): ?>
                            <div class="p-5 text-center text-muted bg-light rounded-bottom">
                                <i class="fas fa-folder-open fa-4x mb-4 text-black-50 opacity-25 d-block"></i>
                                <h4 class="font-weight-light">No departments created yet.</h4>
                                <p>Use the form on the left to add your first department.</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-secondary">
                                        <tr>
                                            <th class="border-top-0 border-bottom-0 pl-4 py-3" style="width: 50px;">#</th>
                                            <th class="border-top-0 border-bottom-0 py-3">Code</th>
                                            <th class="border-top-0 border-bottom-0 py-3">Department Name</th>
                                            <th class="border-top-0 border-bottom-0 py-3">Description</th>
                                            <th class="border-top-0 border-bottom-0 py-3 text-center">Courses</th>
                                            <th class="border-top-0 border-bottom-0 pr-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departments as $i => $dept): ?>
                                        <tr class="<?= ($edit_dept && $edit_dept['dept_id'] == $dept['dept_id']) ? 'bg-warning-light' : '' ?>" style="transition: background-color 0.2s;">
                                            <td class="pl-4 text-muted font-weight-bold"><?= $i + 1 ?></td>
                                            <td>
                                                <span class="badge badge-light border text-dark px-2 py-1" style="font-family: monospace; font-size: 0.9rem; letter-spacing: 1px;">
                                                    <i class="fas fa-tag text-success mr-1 opacity-50"></i><?= htmlspecialchars($dept['dept_code']) ?>
                                                </span>
                                            </td>
                                            <td class="font-weight-bold text-dark" style="font-size: 1.05rem;">
                                                <?= htmlspecialchars($dept['dept_name']) ?>
                                            </td>
                                            <td class="text-secondary" style="max-width:250px;">
                                                <?php if($dept['description']): ?>
                                                    <div class="text-truncate" title="<?= htmlspecialchars($dept['description']) ?>">
                                                        <?= htmlspecialchars($dept['description']) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-black-50 font-italic">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-<?= $dept['course_count'] > 0 ? 'primary' : 'secondary opacity-50' ?> rounded-pill px-3 py-1">
                                                    <?= $dept['course_count'] ?> course<?= $dept['course_count'] != 1 ? 's' : '' ?>
                                                </span>
                                            </td>
                                            <td class="pr-4 text-right">
                                                <a href="manage_departments.php?edit=<?= $dept['dept_id'] ?>"
                                                   class="btn btn-sm btn-outline-warning rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 5px;" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <?php if ($_SESSION['role_id'] == 1): ?>
                                                    <?php if ($dept['course_count'] == 0): ?>
                                                    <a href="manage_departments.php?delete=<?= $dept['dept_id'] ?>"
                                                       class="btn btn-sm btn-outline-danger rounded-circle shadow-sm ml-1" style="width: 32px; height: 32px; padding: 5px;"
                                                       onclick="return confirm('Permanently delete department \'<?= htmlspecialchars($dept['dept_name'], ENT_QUOTES) ?>\'?')"
                                                       title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-secondary rounded-circle shadow-sm ml-1 opacity-50" style="width: 32px; height: 32px; padding: 5px;" disabled
                                                            title="Cannot delete — has courses assigned">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div><!-- /.row -->

        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
