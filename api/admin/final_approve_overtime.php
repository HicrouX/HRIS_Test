<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);
$acting_user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"));

try {
    $pdo->beginTransaction();
    $status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

    $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ?, approved_by = ? WHERE ot_id = ?");
    $stmt->execute([$status, $acting_user_id, $data->ot_id]);

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

            // Check for existing time log
            $checkTime = $pdo->prepare("SELECT time_log_id FROM time_logs WHERE employee_id = ? AND log_date = ?");
            $checkTime->execute([$ot['employee_id'], $date_str]);
            $existing_time_log_id = $checkTime->fetchColumn();

            if ($existing_time_log_id) {
                $syncTime = $pdo->prepare("UPDATE time_logs SET user_id = ?, attendance_id = ?, time_in = ?, time_out = ? WHERE time_log_id = ?");
                $syncTime->execute([$user_id, $attendance_id, $ot['start_time'], $ot['end_time'], $existing_time_log_id]);
            } else {
                $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $syncTime->execute([
                    $ot['employee_id'], $user_id, $attendance_id, $ot['start_time'], $ot['end_time'], $date_str
                ]);
            }
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