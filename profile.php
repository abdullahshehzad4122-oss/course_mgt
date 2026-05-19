<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Security check independent of sys_pages
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (password_verify($current, $hash)) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            if ($update->execute([$new_hash, $user_id])) {
                $message = "Password updated successfully!";
            } else {
                $error = "Database error updating password.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT u.username, r.role_name 
                       FROM users u 
                       JOIN sys_roles r ON u.role_id = r.role_id 
                       WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_student = ($_SESSION['role_id'] == 4);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper <?= $is_student ? 'bg-light' : '' ?>" style="<?= $is_student ? 'background-color: #f4f6f9 !important;' : '' ?>">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold">Profile Management</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content mt-2">
        <div class="container-fluid">
            
            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <strong><i class="fas fa-check-circle mr-2"></i>Success!</strong> <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <strong><i class="fas fa-exclamation-triangle mr-2"></i>Error!</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- User Identity Card -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 <?= $is_student ? '' : 'card-primary card-outline' ?>" style="<?= $is_student ? 'background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border-radius: 15px;' : '' ?>">
                        <div class="card-body box-profile text-center py-5">
                            <div class="text-center mb-3">
                                <img class="profile-user-img img-fluid img-circle shadow"
                                     src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=4e73df&color=fff&size=150"
                                     alt="User profile picture" style="border: 4px solid #fff;">
                            </div>
                            <h3 class="profile-username font-weight-bold tracking-wide"><?= htmlspecialchars($user['username']) ?></h3>
                            <p class="text-muted mb-4"><span class="badge badge-primary px-3 py-2" style="font-size: 0.9rem;"><?= htmlspecialchars(ucfirst($user['role_name'])) ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Password Change Form -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0" style="<?= $is_student ? 'background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border-radius: 15px;' : '' ?>">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0" style="<?= $is_student ? 'background: transparent !important;' : '' ?>">
                            <h4 class="card-title font-weight-bold text-dark"><i class="fas fa-lock mr-2 text-primary"></i> Change Password</h4>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="profile.php">
                                <div class="form-group mb-4">
                                    <label class="text-secondary font-weight-bold">Current Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-key text-muted"></i></span>
                                        </div>
                                        <input type="password" name="current_password" class="form-control border-left-0 pl-0" placeholder="Enter current password" required>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group mb-4 mt-4">
                                    <label class="text-secondary font-weight-bold">New Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-lock text-muted"></i></span>
                                        </div>
                                        <input type="password" name="new_password" class="form-control border-left-0 pl-0" placeholder="Minimum 6 characters" required>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <label class="text-secondary font-weight-bold">Confirm New Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-check-circle text-muted"></i></span>
                                        </div>
                                        <input type="password" name="confirm_password" class="form-control border-left-0 pl-0" placeholder="Repeat new password" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 font-weight-bold shadow-sm">
                                    Update Security Settings <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
