<?php
/**
 * Dynamic Sidebar - Presentation Layer
 * Implements Section 7 and Section 8 (sys_pages table)
 */
global $pdo; // CRITICAL FIX: Access global $pdo

// Initialize menu builder
require_once __DIR__ . '/../core/MenuBuilder.php';
$menuBuilder = new MenuBuilder($pdo);
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="<?= BASE_URL ?>/dashboard.php" class="brand-link text-center py-3">
    <?php 
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_name = 'app_logo'");
    $logo = $stmt->fetchColumn() ?: 'logo.png';
    ?>
    <span class="brand-text font-weight-bold" style="font-size: 1.2rem; letter-spacing: 0.05em;">
        <i class="fas fa-graduation-cap mr-2"></i>
        <?php 
        $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_name = 'app_name'");
        echo htmlspecialchars($stmt->fetchColumn() ?: 'Course Mgmt');
        ?>
    </span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <?php
        // Fetch username again since $user from navbar isn't guaranteed here depending on include order
        $sidebar_user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
        $sidebar_user_stmt->execute([$_SESSION['user_id']]);
        $sidebar_username = $sidebar_user_stmt->fetchColumn() ?: 'User';
        ?>
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($sidebar_username) ?>&background=fff&color=4e73df&rounded=true" 
             class="img-circle elevation-2" alt="User Image" style="width: 2.5rem; height: 2.5rem; border: 2px solid rgba(255,255,255,0.2);">
      </div>
      <div class="info ml-2">
        <a href="<?= BASE_URL ?>/profile.php" class="d-block font-weight-bold text-white" style="line-height: 1.2;">
            <?= htmlspecialchars($sidebar_username) ?>
            <br>
            <small class="text-white-50 font-weight-normal"><i class="fas fa-circle text-success" style="font-size: 8px;"></i> Online</small>
        </a>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2 text-sm">
      <?= $menuBuilder->renderMenu() ?>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>