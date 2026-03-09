<?php
// FILE: login.php
session_start();
require_once 'api/config/db.php';

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['role_id'])) {
    if ($_SESSION['role_id'] === 1) {
        header("Location: super_admin_dashboard.php");
    } elseif ($_SESSION['role_id'] === 2) {
        header("Location: admin_dashboard.php");
    } elseif ($_SESSION['role_id'] === 3) {
        header("Location: coach_dashboard.php");
    } else {
        header("Location: employee_dashboard.php");
    }
    exit;
}

header("Content-Type: text/html; charset=UTF-8");
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['username'] ?? ''; 
    $password = $_POST['password'] ?? '';

    try {
        // Query database to match sample_data.sql
        $stmt = $pdo->prepare("SELECT u.user_id, u.role_id, u.password, e.employee_id, e.first_name, e.last_name 
                               FROM users u 
                               JOIN employees e ON u.user_id = e.user_id 
                               WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Note: sample_data.sql uses plain text 'pass123'
        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['role_id'] = (int)$user['role_id'];
            $_SESSION['employee_id'] = (int)$user['employee_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

            // Redirection logic based on Role ID
            if ($_SESSION['role_id'] === 1) {
                header("Location: super_admin_dashboard.php");
            } elseif ($_SESSION['role_id'] === 2) {
                header("Location: admin_dashboard.php");
            } elseif ($_SESSION['role_id'] === 3) {
                header("Location: coach_dashboard.php");
            } else {
                header("Location: employee_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password!";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iREPLY - HRIS Login</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f7f6; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 400px; }
        h2 { text-align: center; color: #1e4d8c; margin-top: 0; font-size: 28px; }
        input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 14px; background: #1e4d8c; border: none; color: white; font-weight: bold; cursor: pointer; border-radius: 6px; font-size: 16px; transition: 0.3s; }
        button:hover { background: #153a6b; }
        .error-msg { color: #e74c3c; font-size: 14px; text-align: center; background: #fdedec; padding: 10px; border-radius: 6px; border: 1px solid #fadbd8; }
        .test-hint { font-size: 11px; color: #777; margin-top: 25px; border-top: 1px solid #eee; padding-top: 15px; line-height: 1.6; max-height: 250px; overflow-y: auto; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 4px; font-family: 'Courier New', monospace; color: #c0392b; font-weight: bold; }
        .role-group { margin-bottom: 12px; }
        .role-title { font-weight: bold; color: #2c3e50; display: block; margin-bottom: 4px; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>iREPLY Login</h2>
    
    <?php if($error): ?>
        <p class="error-msg"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Email Address" required autocomplete="off">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Log In</button>
    </form>

    <div class="test-hint">
        <div style="margin-bottom:10px; color:#1e4d8c; font-weight:bold;">Test Credentials (Pass: pass123)</div>
        <div class="role-group">
            <span class="role-title">🔒 Super Admin</span>
            • <code>super@hris.com</code>
        </div>
        <div class="role-group">
            <span class="role-title">🛡️ Admin</span>
            • <code>admin@hris.com</code>
        </div>
        <div class="role-group">
            <span class="role-title">📋 Coaches</span>
            • <code>coach1@hris.com</code> | <code>coach2@hris.com</code>
        </div>
        <div class="role-group">
            <span class="role-title">👤 Employees</span>
            • <code>emp1@hris.com</code> to <code>emp5@hris.com</code>
        </div>
    </div>
</div>

</body>
</html>
