<?php
// api/middleware/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verifyAccess($allowed_roles) {
    // FIX: Prioritize Session for Production Security
    $role_id = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;

    // OPTIONAL: Keep Header support ONLY if Session is empty (for testing)
    if ($role_id === 0) {
        foreach ($_SERVER as $key => $value) {
            if (strpos(strtoupper($key), 'USER_ROLE') !== false) {
                $role_id = intval($value);
                break;
            }
        }
    }

    if ($role_id === 0 || !in_array($role_id, $allowed_roles)) {
        header('Content-Type: application/json');
        http_response_code(403); 
        echo json_encode([
            "error" => "Unauthorized access.",
            "roles_allowed" => $allowed_roles,
            "your_role" => $role_id
        ]);
        exit; 
    }
}
?>