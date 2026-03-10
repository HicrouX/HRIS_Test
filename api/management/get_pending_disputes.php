<?php
// FILE: api/management/get_pending_disputes.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Coaches (2), Admins (3), Super Admins (4)
verifyAccess([2, 3, 4]); 

$coach_id = isset($_GET['coach_id']) ? $_GET['coach_id'] : 0;

try {
    $sql = "SELECT 
                d.dispute_id,
                d.employee_id,
                d.dispute_date,
                d.dispute_type,
                d.reason,
                d.status,
                d.remarks,  -- <--- FIXED: Added this column
                e.first_name, 
                e.last_name,
                r.role_name
            FROM attendance_disputes d
            JOIN employees e ON d.employee_id = e.employee_id
            LEFT JOIN users u ON e.employee_id = u.employee_id
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN team_members tm ON e.employee_id = tm.employee_id
            LEFT JOIN team_cluster tc ON tm.team_id = tc.team_id
            WHERE 
                d.status = 'Pending' 
                AND d.employee_id != ? 
                AND (
                    tc.coach_id = ?        
                    OR u.role_id = 2       
                    OR u.role_id = 3       
                )
            ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coach_id, $coach_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>