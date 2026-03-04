<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2, 3, 4]); 
$data = json_decode(file_get_contents("php://input"));

$status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE attendance_disputes SET status = ?, remarks = ? WHERE dispute_id = ?");
    $stmt->execute([$status, ($data->remarks ?? ''), $data->dispute_id]);

    if ($status === 'Approved') {
        $get_disp = $pdo->prepare("SELECT employee_id, dispute_date FROM attendance_disputes WHERE dispute_id = ?");
        $get_disp->execute([$data->dispute_id]);
        $dispute = $get_disp->fetch();

        if ($dispute) {
            // 1. Update/Insert attendance record (Table name: attendance_logs)
            $syncAtt = $pdo->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, attendance_status) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE attendance_status = ?");
            
            $new_status = $data->new_status ?? 'Present';
            $syncAtt->execute([
                $dispute['employee_id'], $dispute['dispute_date'], $new_status, $new_status
            ]);
            
            // Get the attendance_id (whether new or existing)
            $getAttId = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
            $getAttId->execute([$dispute['employee_id'], $dispute['dispute_date']]);
            $attendance_id = $getAttId->fetchColumn();

            // 2. Update/Insert time logs record (Table name: time_logs)
            $dt_in = $data->time_in ? date('Y-m-d H:i:s', strtotime($dispute['dispute_date'] . " " . $data->time_in)) : null;
            $dt_out = $data->time_out ? date('Y-m-d H:i:s', strtotime($dispute['dispute_date'] . " " . $data->time_out)) : null;

            // Note: time_logs needs user_id. For consistency, we'll try to get it from the employees record.
            $getUserId = $pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $getUserId->execute([$dispute['employee_id']]);
            $user_id = $getUserId->fetchColumn();

            $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                   VALUES (?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE time_in = ?, time_out = ?");
            
            $syncTime->execute([
                $dispute['employee_id'], $user_id, $attendance_id, $dt_in, $dt_out, $dispute['dispute_date'],
                $dt_in, $dt_out
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Resolved."]);
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}