<?php
// api/admin/get_endorsed_leaves.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]); 

try {
    // UPDATED SQL: 
    // 1. Joins 'users' table as 'u_app' to check the APPLICANT's role.
    // 2. WHERE clause includes 'Pending' requests IF the applicant is Admin(3) or Super Admin(4).
    $sql = "SELECT 
                lr.leave_id, lr.leave_type, lr.start_date, lr.end_date, 
                lr.reason, lr.status, lr.created_at,
                e.first_name, e.last_name,
                c.first_name as coach_name
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            LEFT JOIN users u_app ON e.employee_id = u_app.employee_id
            LEFT JOIN users u_rev ON lr.reviewed_by = u_rev.user_id
            LEFT JOIN employees c ON u_rev.employee_id = c.employee_id
            WHERE 
                lr.status IN ('Endorsed', 'Approved', 'Denied') 
                OR 
                (lr.status = 'Pending' AND u_app.role_id IN (3, 4))
            ORDER BY lr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>