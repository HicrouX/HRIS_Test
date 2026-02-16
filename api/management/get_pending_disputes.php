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
    // LOGIC:
    // 1. Fetch disputes from Team Members (Standard hierarchy).
    // 2. Fetch disputes from other COACHES (Role ID 2) for Peer Approval.
    // 3. Fetch disputes from ADMINS (Role ID 3) for Peer Approval.
    // 4. Exclude the viewer's OWN disputes (cannot approve self).
    
    $sql = "SELECT 
                d.dispute_id,
                d.employee_id,
                d.dispute_date,
                d.dispute_type,
                d.reason,
                d.status,
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
                    tc.coach_id = ?        -- Condition 1: Filer is in My Team
                    OR u.role_id = 2       -- Condition 2: Filer is a Coach (Peer Approval)
                    OR u.role_id = 3       -- Condition 3: Filer is an Admin (Peer Approval)
                )
            ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    // Execute with params: exclude_self (coach_id), match_team_coach (coach_id)
    $stmt->execute([$coach_id, $coach_id]); 
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>