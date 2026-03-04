<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);
$acting_emp_id = $_SESSION['employee_id'];
$data = json_decode(file_get_contents("php://input"));

try {
    $pdo->beginTransaction();
    $status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

    $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ?, approved_by = ? WHERE ot_id = ?");
    $stmt->execute([$status, $acting_emp_id, $data->ot_id]);

    if ($status === 'Approved') {
        $get_ot = $pdo->prepare("SELECT employee_id, start_time, end_time, ot_type FROM overtime_requests WHERE ot_id = ?");
        $get_ot->execute([$data->ot_id]);
        $ot = $get_ot->fetch();

        if ($ot) {
            $date_str = date('Y-m-d', strtotime($ot['start_time']));
            $dashboard_status = 'Overtime'; 

            $syncAtt = $pdo->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, attendance_status) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE attendance_status = ?");
            $syncAtt->execute([$ot['employee_id'], $date_str, $dashboard_status, $dashboard_status]);

            $attendance_id = $pdo->lastInsertId();
            if (!$attendance_id) {
                $getAttId = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
                $getAttId->execute([$ot['employee_id'], $date_str]);
                $attendance_id = $getAttId->fetchColumn();
            }

            // Need user_id for time_logs
            $getUserId = $pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $getUserId->execute([$ot['employee_id']]);
            $user_id = $getUserId->fetchColumn();

            $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                   VALUES (?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE time_in = ?, time_out = ?");
            $syncTime->execute([
                $ot['employee_id'], $user_id, $attendance_id, $ot['start_time'], $ot['end_time'], $date_str,
                $ot['start_time'], $ot['end_time']
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Overtime Processed."]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>