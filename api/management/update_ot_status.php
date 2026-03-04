<?php
// api/management/update_ot_status.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2, 3, 4]);

$data = json_decode(file_get_contents("php://input"));
$action = $data->action; 
$ot_id = $data->ot_id;
$acting_emp_id = $_SESSION['employee_id'];

// Map Action to DB Enum
$new_status = match($action) {
    'ENDORSE' => 'Endorsed',
    'APPROVE' => 'Approved',
    'DENY'    => 'Denied',
    default   => ''
};

if ($new_status && $ot_id) {
    try {
        $pdo->beginTransaction();

        // 1. Update the Request Status
        $sql = "UPDATE overtime_requests SET status = ?, approved_by = ? WHERE ot_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_status, ($new_status === 'Approved' ? $acting_emp_id : null), $ot_id]);

        // 2. Sync to Attendance Table if Approved
        if ($new_status === 'Approved') {
            $get_ot = $pdo->prepare("SELECT employee_id, user_id, start_time, end_time FROM overtime_requests WHERE ot_id = ?");
            $get_ot->execute([$ot_id]);
            $request = $get_ot->fetch();

            if ($request) {
                $ot_date = date('Y-m-d', strtotime($request['start_time']));
                
                // Table name: attendance_logs
                $syncAtt = $pdo->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, attendance_status) 
                                       VALUES (?, ?, 'Overtime') 
                                       ON DUPLICATE KEY UPDATE attendance_status = 'Overtime'");
                $syncAtt->execute([$request['employee_id'], $ot_date]);
                
                $attendance_id = $pdo->lastInsertId();
                if (!$attendance_id) {
                    $getAttId = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
                    $getAttId->execute([$request['employee_id'], $ot_date]);
                    $attendance_id = $getAttId->fetchColumn();
                }

                // Sync to time_logs
                $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                       VALUES (?, ?, ?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE time_in = ?, time_out = ?");
                $syncTime->execute([
                    $request['employee_id'], $request['user_id'], $attendance_id, 
                    $request['start_time'], $request['end_time'], $ot_date,
                    $request['start_time'], $request['end_time']
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(["success" => "OT status updated and synced."]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>