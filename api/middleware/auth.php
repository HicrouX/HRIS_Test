<?php
// api/middleware/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verifyAccess($allowed_roles) {
    $role_id = 0;
    $debug_source = "None";

    // -----------------------------------------------------------
    // STRATEGY 1: SCAN ALL SERVER HEADERS (The "Hunter" Method)
    // -----------------------------------------------------------
    // This finds the header even if the server renamed it to 
    // "REDIRECT_HTTP_X_USER_ROLE" or "X-User-Role" etc.
    foreach ($_SERVER as $key => $value) {
        // Look for any header key ending in "USER_ROLE"
        if (strpos(strtoupper($key), 'USER_ROLE') !== false) {
            $role_id = intval($value);
            $debug_source = "Found in Server Key: $key";
            break; // Stop looking, we found it
        }
    }

    // -----------------------------------------------------------
    // STRATEGY 2: APACHE HEADERS (Backup)
    // -----------------------------------------------------------
    if ($role_id === 0 && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtoupper($key) === 'X-USER-ROLE') {
                $role_id = intval($value);
                $debug_source = "Found in Apache Header: $key";
                break;
            }
        }
    }

    // -----------------------------------------------------------
    // STRATEGY 3: SESSION (Real Login)
    // -----------------------------------------------------------
    // Only use session if no header was found (Priority to Dashboard)
    if ($role_id === 0 && isset($_SESSION['role_id'])) {
        $role_id = $_SESSION['role_id'];
        $debug_source = "Session Cookie";
    }

    // -----------------------------------------------------------
    // DECISION & DEBUGGING
    // -----------------------------------------------------------
    if ($role_id === 0 || !in_array($role_id, $allowed_roles)) {
        header('Content-Type: application/json');
        http_response_code(403); 
        
        // This JSON response will tell you EXACTLY what PHP saw.
        // Check the "Network" tab > Response in your browser if this happens.
        echo json_encode([
            "error" => "Unauthorized access.",
            "debug_message" => "Auth failed.",
            "role_you_sent" => $role_id,
            "roles_allowed" => $allowed_roles,
            "how_we_found_it" => $debug_source,
            "server_keys_scanned" => array_keys($_SERVER) // Lists all keys so we can see what the header is actually named
        ]);
        exit; 
    }
}
?>