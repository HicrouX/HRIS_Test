<?php
// api/middleware/auth.php
header("Content-Type: text/html; charset=UTF-8");
// 🔴 FIX: Start Session ONLY if it's not already running
if (session_status() === PHP_SESSION_NONE) {
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