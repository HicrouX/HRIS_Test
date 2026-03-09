<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);

try {
    $sql = "SELECT 
                lr.leave_id as id, 'leave' as type, lr.leave_type as sub_type, 
                lr.start_date as date_info, lr.reason, lr.status, 
                e.first_name, e.last_name, e.employee_id
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            WHERE lr.status IN ('Endorsed', 'Approved', 'Denied')
            UNION ALL
            SELECT 
                ot.ot_id as id, 'ot' as type, ot.ot_type as sub_type, 
                ot.start_time as date_info, ot.purpose as reason, ot.status, 
                e.first_name, e.last_name, e.employee_id
            FROM overtime_requests ot
            JOIN employees e ON ot.employee_id = e.employee_id
            WHERE ot.status IN ('Endorsed', 'Approved', 'Denied')
            ORDER BY date_info DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>