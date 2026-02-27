<?php
// api/middleware/auth.php
header("Content-Type: text/html; charset=UTF-8");

// 🔴 FIX: Configure Session for Cross-Domain (Vercel + HelioHost)
if (session_status() === PHP_SESSION_NONE) {
    // Only set these if using HTTPS
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                 (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Set your HelioHost domain here if needed
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => 'None'
    ]);
    session_start();
}

function verifyAccess($allowed_roles) {
    // 1. Check Role from Session
    $role_id = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;

    // 2. If valid role found, check if it's allowed
    if ($role_id !== 0 && in_array($role_id, $allowed_roles)) {
        return; // Access Granted
    }

    // 3. If failed, return JSON Error and Stop
    header('Content-Type: application/json');
    http_response_code(403); 
    echo json_encode([
        "error" => "Unauthorized access.",
        "debug_role" => $role_id, // Helps debug what the server sees
        "allowed" => $allowed_roles
    ]);
    exit; 
}
?>