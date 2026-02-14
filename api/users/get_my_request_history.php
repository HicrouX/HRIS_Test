<?php
// api/users/get_my_request_history.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

$emp_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : 0;

if ($emp_id == 0) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT 
                'Leave' as type,
                lr.leave_type as sub_type,
                lr.start_date,
                lr.end_date,
                lr.reason,
                lr.status,
                lr.created_at,
                c_emp.first_name as coach_first,
                c_emp.last_name as coach_last,
                a_emp.first_name as admin_first,
                a_emp.last_name as admin_last
            FROM leave_requests lr
            LEFT JOIN users c_user ON lr.reviewed_by = c_user.user_id
            LEFT JOIN employees c_emp ON c_user.employee_id = c_emp.employee_id
            LEFT JOIN employees a_emp ON lr.approved_by = a_emp.employee_id
            WHERE lr.employee_id = ?
            
            UNION ALL
            
            SELECT 
                'Overtime' as type,
                ot.ot_type as sub_type,
                ot.start_time as start_date,
                ot.end_time as end_date,
                ot.purpose as reason,
                ot.status,
                ot.created_at,
                NULL as coach_first, 
                NULL as coach_last,
                a_emp.first_name as admin_first,
                a_emp.last_name as admin_last
            FROM overtime_requests ot
            LEFT JOIN employees a_emp ON ot.approved_by = a_emp.employee_id
            WHERE ot.employee_id = ?

            UNION ALL

            SELECT 
                'Dispute' as type,
                d.dispute_type as sub_type,
                d.dispute_date as start_date,
                d.dispute_date as end_date,
                d.reason,
                d.status,
                d.created_at,
                NULL as coach_first,
                NULL as coach_last,
                NULL as admin_first,
                NULL as admin_last
            FROM attendance_disputes d
            WHERE d.employee_id = ?
            
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$emp_id, $emp_id, $emp_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>