<?php
// api/admin/get_endorsed_disputes.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);

try {
    $sql = "SELECT 
                d.dispute_id,
                d.dispute_date,
                d.reason,
                d.status,
                e.first_name, 
                e.last_name,
                e.employee_id
            FROM attendance_disputes d
            JOIN employees e ON d.employee_id = e.employee_id
            WHERE d.status = 'Endorsed' 
            ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode([]);
}
?>