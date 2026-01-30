<?php
// 1. Start the session to store user data
session_start();

// 2. Define Test Accounts (Simulating Database)
// Role IDs: 1 = User, 2 = Coach, 3 = Admin
$test_accounts = [
    'employee_user' => [
        'password' => 'pass123',
        'role_id'  => 1,
        'emp_id'   => 101,
        'name'     => 'John Employee'
    ],
    'coach_user' => [
        'password' => 'coach123',
        'role_id'  => 2,
        'emp_id'   => 102,
        'name'     => 'Charina Coach'
    ],
    'admin_user' => [
        'password' => 'admin123',
        'role_id'  => 3,
        'emp_id'   => 103,
        'name'     => 'Admin Ken'
    ]
];

$error = "";

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($test_accounts[$username]) && $test_accounts[$username]['password'] === $password) {
        // LOGIN SUCCESS: Save data to SESSION
        $_SESSION['role_id'] = (int)$test_accounts[$username]['role_id'];
        $_SESSION['employee_id'] = $test_accounts[$username]['emp_id'];
        $_SESSION['user_name'] = $test_accounts[$username]['name'];

        // 4. REDIRECT BASED ON ROLE
        if ($_SESSION['role_id'] === 1) {
            header("Location: employee_dashboard.php");
        } elseif ($_SESSION['role_id'] === 2) {
            header("Location: coach_dashboard.php");
        } elseif ($_SESSION['role_id'] >= 3) {
            header("Location: admin_dashboard.php");
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
    <title>HRIS Login - Test Mode</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f7f6; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2196F3; border: none; color: white; font-weight: bold; cursor: pointer; border-radius: 4px; }
        .error-msg { color: red; font-size: 14px; text-align: center; }
        .test-hint { font-size: 11px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; line-height: 1.6; }
        code { background: #eee; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>HRIS Login</h2>
    
    <?php if($error): ?>
        <p class="error-msg"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="test-hint">
        <strong>Test Credentials (User / Pass):</strong><br>
        • Employee: <code>employee_user</code> / <code>pass123</code><br>
        • Coach: <code>coach_user</code> / <code>coach123</code><br>
        • Admin: <code>admin_user</code> / <code>admin123</code>
    </div>
</div>

</body>
</html>