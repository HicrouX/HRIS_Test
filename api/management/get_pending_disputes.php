<?php
// api/management/get_pending_disputes.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2]); // Coach Only

$coach_id = isset($_GET['coach_id']) ? $_GET['coach_id'] : 0;

try {
    $sql = "SELECT 
                d.dispute_id,
                d.dispute_date,
                d.dispute_type,
                d.reason,
                d.status,
                e.first_name, 
                e.last_name
            FROM attendance_disputes d
            JOIN employees e ON d.employee_id = e.employee_id
            JOIN team_members tm ON e.employee_id = tm.employee_id
            JOIN team_cluster tc ON tm.team_id = tc.team_id
            WHERE d.status = 'Pending' 
            AND tc.coach_id = ? 
            AND d.employee_id != ? -- 🛑 SECURITY: Coach cannot approve own dispute
            ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coach_id, $coach_id]); // Pass coach_id twice
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode([]);
}
?>