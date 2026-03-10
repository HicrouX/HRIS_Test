<?php
// FILE: login.php
session_start();

/**
 * 2. Define Test Accounts (Organized by Strict Hierarchy)
 * Standardized passwords to 'pass123' for demo purposes.
 */
$test_accounts = [
    // --- SUPER ADMINISTRATOR (Role 4) ---
    'super_admin' => [
        'password' => 'pass123',
        'role_id'  => 4,
        'emp_id'   => 103,
        'name'     => 'Super Admin'
    ],

    // --- ADMINISTRATORS (Role 3) ---
    'admin2' => [
        'password' => 'pass123',
        'role_id'  => 3,
        'emp_id'   => 104,
        'name'     => 'Sarah Admin'
    ],
    'admin3' => [
        'password' => 'pass123',
        'role_id'  => 3,
        'emp_id'   => 105,
        'name'     => 'Bruce Admin'
    ],

    // --- COACHES (Role 2) ---
    'coach_user' => [
        'password' => 'pass123',
        'role_id'  => 2,
        'emp_id'   => 102,
        'name'     => 'Charina Vargas'
    ],
    'coach_bravo' => [
        'password' => 'pass123',
        'role_id'  => 2,
        'emp_id'   => 106,
        'name'     => 'Bravo Coach'
    ],
    'coach_charlie' => [
        'password' => 'pass123',
        'role_id'  => 2,
        'emp_id'   => 107,
        'name'     => 'Charlie Coach'
    ],

    // --- EMPLOYEES (Role 1) ---
    'employee_user' => [ 'password' => 'pass123', 'role_id' => 1, 'emp_id' => 101, 'name' => 'John Doe' ],
    'alice'         => [ 'password' => 'pass123', 'role_id' => 1, 'emp_id' => 108, 'name' => 'Alice Wonder' ],
    'bob'           => [ 'password' => 'pass123', 'role_id' => 1, 'emp_id' => 109, 'name' => 'Bob Builder' ]
];

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($test_accounts[$username]) && $test_accounts[$username]['password'] === $password) {
        $_SESSION['role_id'] = (int)$test_accounts[$username]['role_id'];
        $_SESSION['employee_id'] = $test_accounts[$username]['emp_id'];
        $_SESSION['user_name'] = $test_accounts[$username]['name'];

        /**
         * 4. FIXED REDIRECTION LOGIC
         * Strict equality (===) ensures Role 3 never reaches the Super Admin Dashboard.
         */
        if ($_SESSION['role_id'] === 4) {
            header("Location: super_admin_dashboard.php");
        } elseif ($_SESSION['role_id'] === 3) {
            header("Location: admin_dashboard.php");
        } elseif ($_SESSION['role_id'] === 2) {
            header("Location: coach_dashboard.php");
        } else {
            header("Location: employee_dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid username or password!";
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
        .test-hint { font-size: 11px; color: #777; margin-top: 25px; border-top: 1px solid #eee; padding-top: 15px; line-height: 1.6; max-height: 180px; overflow-y: auto; }
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
        <input type="text" name="username" placeholder="Username" required autocomplete="off">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Log In</button>
    </form>

    <div class="test-hint">
        <div class="role-group">
            <span class="role-title">🔒 Super Admin (Pass: pass123)</span>
            • <code>super_admin</code>
        </div>
        <div class="role-group">
            <span class="role-title">🛡️ Administrators (Pass: pass123)</span>
            • <code>admin2</code> | <code>admin3</code>
        </div>
        <div class="role-group">
            <span class="role-title">📋 Coaches (Pass: pass123)</span>
            • <code>coach_user</code> | <code>coach_bravo</code> | <code>coach_charlie</code>
        </div>
        <div class="role-group">
            <span class="role-title">👤 Employees (Pass: pass123)</span>
            • <code>employee_user</code> | <code>alice</code> | <code>bob</code>
        </div>
    </div>
</div>

</body>
</html>