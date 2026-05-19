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

$register_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password) || empty($confirm)) {
        $register_error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $register_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $register_error = "Password must be at least 6 characters long.";
    } else {
        // Check for duplicates
        $check_stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->fetchColumn()) {
            $register_error = "This username is already taken. Please choose another.";
        } else {
            try {
                // Hardcode role_id = 4 (Student) to ensure strict separation
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, 4)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                
                header('Location: login.php?registered=1');
                exit;
            } catch (PDOException $e) {
                $register_error = "Registration failed due to a database error.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            max-width: 500px;
            padding: 20px;
        }

        .student-glass {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 1.5rem; }
        
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
        p { color: #64748b; font-size: 0.95rem; }

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
        input[type="text"]:focus, input[type="password"]:focus { border-color: #2a5298; }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); }

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
        .register-link a { color: #2a5298; font-weight: 600; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="student-glass">
        <div class="text-center mb-4">
            <i class="fas fa-user-plus brand-icon"></i>
            <h2>Join the Platform</h2>
            <p>Create your secure student account</p>
        </div>

        <?php if ($register_error): ?>
        <div class="alert mb-4">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($register_error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Username / Student ID</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" placeholder="e.g., john_doe" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="Min 6 characters" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <i class="fas fa-check-circle input-icon"></i>
                <input type="password" name="confirm_password" placeholder="Repeat password" required>
            </div>

            <button type="submit" class="btn-submit">Create Account <i class="fas fa-check ml-2"></i></button>
        </form>

        <div class="register-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</div>

</body>
</html>
