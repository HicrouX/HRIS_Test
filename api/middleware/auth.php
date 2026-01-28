<?php
// middleware/auth.php
function verifyAccess($required_role_id) {
    // In a real app, you'd check a JWT token or Session here
    $user_role = $_SERVER['HTTP_X_USER_ROLE'] ?? null; 
    
    if ($user_role != $required_role_id && $user_role != 4) { // 4 = Super Admin 
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized access."]);
        exit;
    }
}
?>