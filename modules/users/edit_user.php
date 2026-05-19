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

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$user_id) {
    header('Location: manage_users.php');
    exit;
}

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$target_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target_user) {
    $_SESSION['error'] = "User not found.";
    header('Location: manage_users.php');
    exit;
}

// Prevent admins from editing Super Admin if they are not super admin
if ($_SESSION['role_id'] == 2 && $target_user['role_id'] == 1) {
    $_SESSION['error'] = "You cannot edit a Super Admin account.";
    header('Location: manage_users.php');
    exit;
}

$errors = [];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role_id = (int)$_POST['role_id'];
    $password = $_POST['password']; // optional

    if (empty($username)) $errors[] = "Username is required";
    if ($role_id <= 0) $errors[] = "Please select a role";

    // Check duplicate username
    $check_stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND user_id != ?");
    $check_stmt->execute([$username, $user_id]);
    if ($check_stmt->fetchColumn()) {
        $errors[] = "Username already exists for another user.";
    }

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ?, password_hash = ? WHERE user_id = ?");
                $stmt->execute([$username, $role_id, password_hash($password, PASSWORD_DEFAULT), $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ? WHERE user_id = ?");
                $stmt->execute([$username, $role_id, $user_id]);
            }
            $_SESSION['success'] = "User account updated successfully.";
            header('Location: manage_users.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error updating user.";
        }
    }
}

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
                    <h1 class="m-0">Edit User</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/users/manage_users.php">Users</a></li>
                        <li class="breadcrumb-item active">Edit User</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content">
        <div class="container-fluid">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                    <?php foreach($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Editing: <?= htmlspecialchars($target_user['username']) ?></h3>
                </div>
                <form method="POST" action="edit_user.php?id=<?= $user_id ?>">
                    <div class="card-body row">
                        <div class="col-md-4 form-group">
                            <label for="username">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? $target_user['username']) ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="role_id">Role *</label>
                            <select class="form-control" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <?php if ($_SESSION['role_id'] == 2 && $role['role_id'] == 1) continue; // Admin can't assign Super Admin ?>
                                    <option value="<?= $role['role_id'] ?>" <?= (($target_user['role_id'] == $role['role_id'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="password">New Password (Leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="***">
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-info">Update User</button>
                        <a href="manage_users.php" class="btn btn-default ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
