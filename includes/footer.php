<?php
global $pdo;

$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_name = 'app_version'");
$app_version = $stmt->fetchColumn() ?: '1.0.0';
?>
<footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
        v<?= htmlspecialchars($app_version) ?>
    </div>
    <strong>Copyright &copy; <?= date('Y') ?> <a href="#">University Course Management</a>.</strong> All rights reserved.
</footer>
</div>

<!-- DIRECT CDN LINKS (Section 12 compliance) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- Theme Toggle Logic -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            const icon = document.getElementById('theme-icon');
            
            function updateIcon() {
                if (document.body.classList.contains('dark-mode')) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
            
            // Set initial icon
            updateIcon();
            
            themeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                document.body.classList.toggle('dark-mode');
                
                // Add a smooth transition effect to the body background only when toggling
                document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
                setTimeout(() => { document.body.style.transition = ''; }, 300);

                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
                updateIcon();
            });
        }
    });
</script>
</body>
</html>