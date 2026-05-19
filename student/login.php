<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 4) {
        header('Location: dashboard.php');
    } else {
        header('Location: ../dashboard.php');
    }
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
            $login_error = "Invalid username or password.";
        } elseif ($user['role_id'] != 4) {
             $login_error = "This portal is for students only. Staff must use the main login portal.";
        } else {
            session_regenerate_id(true);
            $_SESSION = [
                'user_id'       => $user['user_id'],
                'role_id'       => $user['role_id'],
                'role_name'     => $user['role_name'],
                'logged_in'     => true,
                'last_activity' => time()
            ];
            $log_query = "INSERT INTO access_logs (user_id, page, access_type, timestamp) VALUES (?, 'student_login', 'success', NOW())";
            $log_stmt = $pdo->prepare($log_query);
            $log_stmt->execute([$user['user_id']]);
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .student-glass {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        
        .brand-icon {
            font-size: 3rem;
            color: #2a5298;
            margin-bottom: 10px;
        }

        h2 {
            color: #1e293b;
            font-weight: 800;
            margin-bottom: 5px;
        }
        p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
            font-size: 0.9rem;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #2a5298;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .alert {
            background: #fef2f2;
            color: #ef4444;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .register-link a {
            color: #2a5298;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="student-glass">
        <div class="text-center mb-4">
            <i class="fas fa-user-graduate brand-icon"></i>
            <h2>Student Portal</h2>
            <p>Sign in to access your courses and assignments</p>
        </div>

        <?php if ($login_error): ?>
        <div class="alert mb-4">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($login_error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
        <div class="alert mb-4" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
            <i class="fas fa-check-circle"></i> Registration successful! You may now log in.
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Username</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" placeholder="Student ID or Username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="btn-submit">Sign In <i class="fas fa-arrow-right ml-2"></i></button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        <div class="register-link" style="margin-top:10px;">
            <a href="../index.php" style="color:#94a3b8; font-weight:400; font-size:0.8rem;"><i class="fas fa-shield-alt"></i> Staff Login</a>
        </div>
    </div>
</div>

</body>
</html>
