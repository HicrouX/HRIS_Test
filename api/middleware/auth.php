<?php
// api/middleware/auth.php

// Ensure UTF-8 header to prevent character encoding issues
header("Content-Type: text/html; charset=UTF-8");

// 🔴 SAFE SESSION START: Start Session ONLY if it's not already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * verifyAccess
 * Checks if the current logged-in user has one of the required roles.
 * * @param array $allowed_roles Array of integers representing allowed role IDs (e.g., [1, 2])
 */
function verifyAccess($allowed_roles) {
    // 1. Check Role from Session
    // Default to 0 if no role is set
    $role_id = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;

    // 2. If valid role found, check if it's in the list of allowed IDs
    if ($role_id !== 0 && in_array($role_id, $allowed_roles)) {
        return; // Access Granted - allow the script to continue
    }

    // 3. Unauthorized Access: Return JSON Error and stop execution
    // We switch to JSON content type for the error response
    header('Content-Type: application/json');
    http_response_code(403); 
    
    echo json_encode([
        "status" => "error",
        "error" => "Unauthorized access. You do not have permission to view this resource.",
        "debug_info" => [
            "your_role" => $role_id, // Helps developers see what role the server detected
            "required_roles" => $allowed_roles // Shows which roles were actually needed
        ]
    ]);
    exit; 
}
?>