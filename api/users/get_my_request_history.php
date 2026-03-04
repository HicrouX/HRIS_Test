<?php
// api/users/get_my_request_history.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

$user_id = $_GET['employee_id'] ?? 0;
if ($user_id == 0) { echo json_encode([]); exit; }

try {
    $sql = "SELECT 'Leave' as type, lr.leave_type as sub_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, lr.remarks,
            rev_e.first_name as coach_first, rev_e.last_name as coach_last, app_e.first_name as admin_first, app_e.last_name as admin_last
            FROM leave_requests lr
            LEFT JOIN users rev_u ON lr.reviewed_by = rev_u.user_id
            LEFT JOIN employees rev_e ON rev_u.user_id = rev_e.user_id
            LEFT JOIN users app_u ON lr.approved_by = app_u.user_id
            LEFT JOIN employees app_e ON app_u.user_id = app_e.user_id
            WHERE lr.employee_id = ?
            UNION ALL
            SELECT 'Overtime' as type, ot.ot_type as sub_type, ot.start_time as start_date, ot.end_time as end_date, ot.purpose as reason, ot.status, ot.created_at, ot.remarks,
            NULL as coach_first, NULL as coach_last, app_e.first_name as admin_first, app_e.last_name as admin_last
            FROM overtime_requests ot
            LEFT JOIN users app_u ON ot.approved_by = app_u.user_id
            LEFT JOIN employees app_e ON app_u.user_id = app_e.user_id
            WHERE ot.employee_id = ?
            UNION ALL
            SELECT 'Dispute' as type, d.dispute_type as sub_type, d.dispute_date as start_date, d.dispute_date as end_date, d.reason, d.status, d.created_at, d.remarks,
            NULL, NULL, NULL, NULL FROM attendance_disputes d WHERE d.employee_id = ?
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo json_encode(["error" => $e->getMessage()]); }
?>