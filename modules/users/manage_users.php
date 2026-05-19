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

// Handle form submission (Create User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $username = trim($_POST['username']);
    $role_id = (int)$_POST['role_id'];
    $password = $_POST['password'];
    
    $errors = [];
    if (empty($username)) $errors[] = "Username is required";
    if ($role_id <= 0) $errors[] = "Please select a role";
    if (empty($password)) $errors[] = "Password is required";
    
    // Check for duplicate
    $check_stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
    $check_stmt->execute([$username]);
    if ($check_stmt->fetchColumn()) {
        $errors[] = "Username already exists.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $role_id
            ]);
            $_SESSION['success'] = "User account '$username' created successfully.";
            header('Location: manage_users.php');
            exit;
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Handle Delete User
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if ($_SESSION['role_id'] == 1) { // Super Admin only for delete
        $del_id = (int)$_GET['delete'];
        if ($del_id != $_SESSION['user_id']) { // Can't delete self
            try {
                $pdo->beginTransaction();

                // 1. Remove enrollment records where user is a student
                $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?")->execute([$del_id]);

                // 2. Remove attendance records where user is a student
                $pdo->prepare("DELETE FROM attendance WHERE student_id = ?")->execute([$del_id]);

                // 3. Nullify instructor assignment on courses (keep the course, just unassign)
                $pdo->prepare("UPDATE courses SET instructor_id = NULL WHERE instructor_id = ?")->execute([$del_id]);

                // 4. Nullify recorded_by in attendance (keep attendance records, just clear recorder)
                $pdo->prepare("UPDATE attendance SET recorded_by = NULL WHERE recorded_by = ?")->execute([$del_id]);

                // 4.5. Remove user logs and submissions to prevent FK constraint failures
                $pdo->prepare("DELETE FROM access_logs WHERE user_id = ?")->execute([$del_id]);
                $pdo->prepare("DELETE FROM submissions WHERE student_id = ?")->execute([$del_id]);

                // 5. Finally delete the user
                $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$del_id]);

                $pdo->commit();
                $_SESSION['success'] = "User deleted successfully.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("User delete error: " . $e->getMessage());
                $_SESSION['error'] = "Could not delete user: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "You cannot delete your own account.";
        }
    } else {
        $_SESSION['error'] = "You do not have permission to delete users.";
    }
    header('Location: manage_users.php');
    exit;
}

// Fetch existing users
$stmt = $pdo->query("SELECT u.user_id, u.username, u.created_at, r.role_name 
                     FROM users u
                     JOIN sys_roles r ON u.role_id = r.role_id
                     ORDER BY r.role_id ASC, u.username ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch roles for dropdown
$role_stmt = $pdo->query("SELECT * FROM sys_roles ORDER BY role_id");
$roles = $role_stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Users</h1>
                    </div>
                    <div class="col-sm-6">
                        <?= generateBreadcrumbs($pdo, 'modules/users/manage_users.php') ?>
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
                
                <!-- Create User Collapse -->
                <div class="mb-4">
                    <button class="btn btn-success" type="button" data-toggle="collapse" data-target="#createUserForm">
                        <i class="fas fa-user-plus mr-1"></i> Add New User
                    </button>
                    
                    <div class="collapse mt-3 <?= !empty($errors) ? 'show' : '' ?>" id="createUserForm">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title">New User Details</h3>
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
                            
                            <form method="POST" action="manage_users.php">
                                <input type="hidden" name="action" value="create">
                                <div class="card-body row">
                                    <div class="col-md-4 form-group">
                                        <label for="username">Username *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 form-group">
                                        <label for="password">Initial Password *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 form-group">
                                        <label for="role_id">Role *</label>
                                        <select class="form-control" id="role_id" name="role_id" required>
                                            <option value="">-- Select Role --</option>
                                            <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role['role_id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($role['role_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-light">
                                    <button type="submit" class="btn btn-success">Create User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Users List -->
                <div class="card">
                    <div class="card-header bg-dark">
                        <h3 class="card-title">User Directory</h3>
                        <div class="card-tools">
                            <span class="badge badge-info"><?= count($users) ?> Users</span>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Member Since</th>
                                    <?php if ($_SESSION['role_id'] == 1): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user): 
                                    $badge_class = 'secondary';
                                    if ($user['role_name'] == 'Super Admin' || $user['role_name'] == 'Admin') $badge_class = 'danger';
                                    elseif ($user['role_name'] == 'Instructor') $badge_class = 'primary';
                                    elseif ($user['role_name'] == 'Student') $badge_class = 'success';
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="font-weight-bold">
                                        <i class="fas fa-user-circle text-muted mr-1"></i> 
                                        <?= htmlspecialchars($user['username']) ?>
                                    </td>
                                    <td><span class="badge badge-<?= $badge_class ?> px-2 py-1"><?= htmlspecialchars($user['role_name']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    
                                    <?php if ($_SESSION['role_id'] == 1): ?>
                                    <td>
                                        <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-info" title="Edit User"><i class="fas fa-edit"></i></a>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="manage_users.php?delete=<?= $user['user_id'] ?>" 
                                           class="btn btn-sm btn-danger ml-1"
                                           onclick="return confirm('Are you sure you want to permanently delete user <?= htmlspecialchars($user['username']) ?>?');"
                                           title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-secondary ml-1" disabled><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
<?php include __DIR__ . '/../../includes/footer.php'; ?>
