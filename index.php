<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$login_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        $query = "SELECT u.user_id, u.role_id, u.password_hash, r.role_name 
                  FROM users u
                  JOIN sys_roles r ON u.role_id = r.role_id
                  WHERE u.username = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $login_error = "Invalid username or password. Please try again.";
        } else {
            session_regenerate_id(true);
            $_SESSION = [
                'user_id'       => $user['user_id'],
                'role_id'       => $user['role_id'],
                'role_name'     => $user['role_name'],
                'logged_in'     => true,
                'last_activity' => time()
            ];
            $log_query = "INSERT INTO access_logs (user_id, page, access_type, timestamp) VALUES (?, 'login', 'success', NOW())";
            $log_stmt = $pdo->prepare($log_query);
            $log_stmt->execute([$user['user_id']]);
            header('Location: dashboard.php');
            exit;
        }
    }
}

$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_name = 'app_name'");
$app_name = $stmt->fetchColumn() ?: 'Course Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; <?= htmlspecialchars($app_name) ?></title>
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #6c63ff;
            --primary-dark: #4f46e5;
            --primary-glow: rgba(108, 99, 255, 0.4);
            --dark: #0f172a;
            --darker: #0b0f19;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-light: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            height: 100vh;
            overflow: hidden;
            display: flex;
        }

        /* ── Left Panel: Form Area ── */
        .auth-panel {
            width: 100%;
            max-width: 550px;
            height: 100%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 4rem;
            position: relative;
            z-index: 10;
            box-shadow: 20px 0 40px rgba(0,0,0,0.03);
            overflow-y: auto;
        }

        @media (min-width: 1024px) {
            .auth-panel { width: 45%; max-width: 600px; }
        }

        .auth-header {
            margin-bottom: 2.5rem;
        }

        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 24px var(--primary-glow);
            animation: pulse-icon 3s infinite alternate;
        }

        @keyframes pulse-icon {
            0% { transform: scale(1); box-shadow: 0 8px 24px var(--primary-glow); }
            100% { transform: scale(1.05); box-shadow: 0 12px 32px var(--primary-glow); }
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Error Alert */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            color: #ef4444;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 2rem;
            animation: slideInDown 0.4s ease-out;
        }
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3.25rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: var(--text-dark);
            background: #f8fafc;
            border: 2px solid transparent;
            border-radius: 12px;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-control:hover {
            background: #f1f5f9;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.1);
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        .btn-eye {
            position: absolute;
            right: 1.25rem;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            transition: color 0.2s;
        }
        .btn-eye:hover {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 1.15rem;
            margin-top: 1rem;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px var(--primary-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px var(--primary-glow);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }

        .auth-footer {
            margin-top: 2.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* ── Right Panel: Educational Canvas ── */
        .showcase-panel {
            flex: 1;
            display: none;
            background: linear-gradient(135deg, #2e1065 0%, #4c1d95 35%, #1d4ed8 100%);
            position: relative;
            overflow: hidden;
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 1024px) {
            .showcase-panel { display: flex; }
        }

        /* Grid Background Pattern */
        .showcase-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.3;
        }

        /* Glowing Orbs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
        }
        .glow-orb-1 { width: 500px; height: 500px; background: rgba(139, 92, 246, 0.4); top: -10%; left: -10%; animation: pulse-slow 8s infinite alternate;}
        .glow-orb-2 { width: 400px; height: 400px; background: rgba(59, 130, 246, 0.4); bottom: -10%; right: -10%; animation: pulse-slow 10s infinite alternate-reverse;}

        @keyframes pulse-slow {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(20px, 30px); }
        }

        /* 3D Floating Elements Canvas */
        .scene3d {
            position: relative;
            width: 100%;
            height: 100%;
            perspective: 1200px;
            z-index: 2;
        }

        /* Glassmorphism Cards */
        .floating-card {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            color: #fff;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            transform-style: preserve-3d;
            animation: float-3d 6s ease-in-out infinite;
        }

        .fc-1 { top: 25%; left: 15%; animation-delay: 0s; width: 260px; }
        .fc-2 { top: 55%; right: 15%; animation-delay: -2s; width: 280px; }
        .fc-3 { bottom: 15%; left: 30%; animation-delay: -4s; width: 240px; }

        .fc-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        
        .fc-icon-purple { background: linear-gradient(135deg, #a855f7, #7e22ce); }
        .fc-icon-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .fc-icon-emerald { background: linear-gradient(135deg, #10b981, #047857); }

        .fc-text h4 { font-size: 0.95rem; font-weight: 700; margin-bottom: 0.25rem; }
        .fc-text p { font-size: 0.75rem; color: rgba(255,255,255,0.7); }

        /* Orbiting Elements */
        .orbit-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            border-radius: 50%;
            border: 1px dashed rgba(255,255,255,0.15);
            animation: spin 30s linear infinite;
        }

        .orbit-icon {
            position: absolute;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            /* Reverse spin to keep icons upright */
            animation: spin-reverse 30s linear infinite;
        }

        .oi-1 { top: -20px; left: 50%; margin-left: -20px; }
        .oi-2 { bottom: -20px; left: 50%; margin-left: -20px; }
        .oi-3 { top: 50%; left: -20px; margin-top: -20px; }
        .oi-4 { top: 50%; right: -20px; margin-top: -20px; }

        /* Center Showcase Text */
        .showcase-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
            width: 80%;
            z-index: 10;
        }

        .showcase-center h2 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .showcase-center p {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.8);
            font-weight: 400;
        }

        @keyframes float-3d {
            0% { transform: translateY(0) translateZ(0) rotateX(0) rotateY(0); }
            33% { transform: translateY(-15px) translateZ(20px) rotateX(2deg) rotateY(2deg); }
            66% { transform: translateY(10px) translateZ(-10px) rotateX(-2deg) rotateY(-2deg); }
            100% { transform: translateY(0) translateZ(0) rotateX(0) rotateY(0); }
        }

        @keyframes spin { 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        @keyframes spin-reverse { 100% { transform: rotate(-360deg); } }
        
        @media (max-width: 480px) {
            .auth-panel { padding: 2.5rem 2rem; }
            .auth-title { font-size: 1.75rem; }
        }
    </style>
</head>
<body>

<!-- Left Panel: Form -->
<div class="auth-panel">
    <div class="auth-header">
        <div class="brand-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h1 class="auth-title">Course Management System</h1>
        <p class="auth-subtitle">Sign in to your account to continue your learning journey.</p>
    </div>

    <?php if ($login_error): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= htmlspecialchars($login_error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="index.php" autocomplete="off" novalidate>
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-icon-wrapper">
                <input id="username" type="text" name="username" class="form-control" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                <i class="fas fa-user input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="input-icon-wrapper">
                <input id="password" type="password" name="password" class="form-control" placeholder="Enter your password" required>
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="btn-eye" id="togglePwd" aria-label="Toggle password">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            Sign In <i class="fas fa-arrow-right"></i>
        </button>
    </form>

    <div class="auth-footer">
        <i class="fas fa-shield-alt text-success"></i> Secured with enterprise-grade encryption
    </div>
</div>

<!-- Right Panel: Feature Showcase -->
<div class="showcase-panel">
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>
    
    <div class="scene3d">
        <!-- Orbiting Icons -->
        <div class="orbit-container">
            <div class="orbit-icon oi-1"><i class="fas fa-book-open"></i></div>
            <div class="orbit-icon oi-2"><i class="fas fa-award"></i></div>
            <div class="orbit-icon oi-3"><i class="fas fa-laptop-code"></i></div>
            <div class="orbit-icon oi-4"><i class="fas fa-user-graduate"></i></div>
        </div>

        <!-- Floating Cards -->
        <div class="floating-card fc-1">
            <div class="fc-icon fc-icon-purple"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="fc-text">
                <h4>Live Interactive Classes</h4>
                <p>Join sessions in real-time</p>
            </div>
        </div>
        
        <div class="floating-card fc-2">
            <div class="fc-icon fc-icon-blue"><i class="fas fa-tasks"></i></div>
            <div class="fc-text">
                <h4>Assignment Tracking</h4>
                <p>Never miss a deadline</p>
            </div>
        </div>
        
        <div class="floating-card fc-3">
            <div class="fc-icon fc-icon-emerald"><i class="fas fa-chart-line"></i></div>
            <div class="fc-text">
                <h4>Real-time Analytics</h4>
                <p>Monitor your progress visually</p>
            </div>
        </div>

        <!-- Center Text -->
        <div class="showcase-center">
            <h2>Empower Your<br>Educational Journey</h2>
            <p>A comprehensive management system built for modern institutions.<br>Everything you need in one powerful platform.</p>
        </div>
    </div>
</div>

<script>
    // Password visibility toggle
    const togglePwd = document.getElementById('togglePwd');
    const pwdField  = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');

    togglePwd.addEventListener('click', () => {
        const isHidden = pwdField.type === 'password';
        pwdField.type  = isHidden ? 'text' : 'password';
        eyeIcon.classList.toggle('fa-eye', !isHidden);
        eyeIcon.classList.toggle('fa-eye-slash', isHidden);
    });
</script>
</body>
</html>