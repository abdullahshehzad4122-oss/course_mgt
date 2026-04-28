<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enforce role-based access - Super Admins only
if ($_SESSION['role_id'] != 1) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $role_name = trim($_POST['role_name']);
    
    $errors = [];
    if (empty($role_name)) $errors[] = "Role name is required";
    
    // Check for duplicate
    $check_stmt = $pdo->prepare("SELECT 1 FROM sys_roles WHERE role_name = ?");
    $check_stmt->execute([$role_name]);
    if ($check_stmt->fetchColumn()) {
        $errors[] = "A role with that name already exists.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sys_roles (role_name) VALUES (?)");
            $stmt->execute([$role_name]);
            $_SESSION['success'] = "Role created successfully.";
            header('Location: create_role.php');
            exit;
        } catch (PDOException $e) {
            error_log("Role creation error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Fetch existing roles
$stmt = $pdo->query("SELECT * FROM sys_roles ORDER BY role_id");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Roles</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/users/create_role.php') ?>
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
                
                <div class="row">
                    <div class="col-md-5">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Create New Role</h3>
                            </div>
                            
                            <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mx-3 mt-3">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="create_role.php">
                                <input type="hidden" name="action" value="create">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="role_name">Role Name *</label>
                                        <input type="text" class="form-control" id="role_name" name="role_name" 
                                               placeholder="e.g. Dean, Assistant" required>
                                    </div>
                                    <small class="text-muted"><i class="fas fa-info-circle"></i> Basic roles (1: Super Admin, 2: Admin, 3: Instructor, 4: Student) are built-in.</small>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Create Role</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header bg-dark">
                                <h3 class="card-title">Existing Roles</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Role ID</th>
                                            <th>Role Name</th>
                                            <th>System Default</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><?= $role['role_id'] ?></td>
                                            <td><?= htmlspecialchars($role['role_name']) ?></td>
                                            <td>
                                                <?php if ($role['role_id'] <= 4): ?>
                                                <span class="badge badge-success">Yes</span>
                                                <?php else: ?>
                                                <span class="badge badge-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
