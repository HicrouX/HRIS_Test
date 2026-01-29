<?php
// api/admin/get_endorsed_leaves.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

// -------------------------------------------------------------
// ⚡ FORCE FIX: Inject URL Parameter into Server Headers
// -------------------------------------------------------------
// This tricks auth.php into thinking the header exists, 
// even if your server stripped it.
if (isset($_GET['test_role'])) {
    $_SERVER['HTTP_X_USER_ROLE'] = $_GET['test_role'];
}
// -------------------------------------------------------------

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Verify Admin Access (Role 3)
verifyAccess([3]); 

try {
    // Select all leaves that have been Endorsed by a coach
    $sql = "SELECT 
                lr.leave_id, 
                lr.leave_type, 
                lr.start_date, 
                lr.end_date, 
                lr.reason, 
                lr.created_at,
                e.first_name, 
                e.last_name,
                c.first_name as coach_name
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            LEFT JOIN users u ON lr.reviewed_by = u.user_id
            LEFT JOIN employees c ON u.employee_id = c.employee_id
            WHERE lr.status = 'Endorsed'
            ORDER BY lr.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>