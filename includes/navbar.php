<?php
/**
 * Top Navigation Bar - Presentation Layer
 * Implements AdminLTE structure per Section 12
 */
global $pdo;

// Get user info
$user_query = "SELECT u.username, r.role_name 
               FROM users u
               JOIN sys_roles r ON u.role_id = r.role_id
               WHERE u.user_id = ?";
$stmt = $pdo->prepare($user_query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 pb-2 pt-2">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link bg-light rounded-circle text-primary mx-2" data-widget="pushmenu" href="#" role="button" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>
    
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Theme Toggle -->
        <li class="nav-item">
            <a class="nav-link text-secondary" id="theme-toggle" href="#" role="button" title="Toggle Theme" style="transition: all 0.3s; font-size: 1.1rem; padding-top: 0.6rem;">
                <i class="fas fa-moon" id="theme-icon"></i>
            </a>
        </li>
        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown" style="padding-top: 0.25rem; padding-bottom: 0.25rem;">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=4e73df&color=fff&rounded=true" 
                     class="user-image img-circle elevation-1 mr-2" alt="User Image" style="width: 35px; height: 35px;">
                <span class="d-none d-md-inline font-weight-bold text-dark mr-1"><?= htmlspecialchars($user['username']) ?></span>
                <span class="badge badge-primary rounded-pill ml-1" style="font-size: 0.70rem; padding: 0.3rem 0.5rem;"><?= htmlspecialchars($user['role_name']) ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right border-0 shadow-lg rounded-lg mt-2">
                <!-- User image -->
                <div class="bg-primary text-center p-4 rounded-top" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=fff&color=4e73df&rounded=true&size=128" 
                         class="img-circle elevation-2 mb-2" alt="User Image" style="width: 80px; height: 80px; border: 3px solid rgba(255,255,255,0.5);">
                    <p class="text-white mb-0 font-weight-bold" style="font-size: 1.1rem;">
                        <?= htmlspecialchars($user['username']) ?>
                    </p>
                    <small class="text-white-50"><?= htmlspecialchars(ucfirst($user['role_name'])) ?></small>
                </div>
                <!-- Menu Footer-->
                <div class="user-footer d-flex justify-content-between p-3 bg-light rounded-bottom">
                    <a href="<?= BASE_URL ?>/profile.php" class="btn btn-outline-primary btn-flat rounded-pill px-3 shadow-sm">
                        <i class="fas fa-user-circle mr-1"></i> Profile
                    </a>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-flat rounded-pill px-3 shadow-sm">
                        <i class="fas fa-sign-out-alt mr-1"></i> Sign out
                    </a>
                </div>
            </div>
        </li>
    </ul>
</nav>