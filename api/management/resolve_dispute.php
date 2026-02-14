<?php
// api/management/resolve_dispute.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Coaches (2) & Admins (3, 4)
verifyAccess([2, 3, 4]); 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->dispute_id) || empty($data->action)) {
    http_response_code(400); echo json_encode(["error" => "Missing parameters."]); exit;
}

$status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';
$remarks = $data->remarks ?? ''; 
$time_in = $data->time_in ?? null;
$time_out = $data->time_out ?? null;

try {
    $pdo->beginTransaction();

    // 1. Update Dispute Status & Remarks
    $stmt = $pdo->prepare("UPDATE attendance_disputes SET status = ?, remarks = ? WHERE dispute_id = ?");
    $stmt->execute([$status, $remarks, $data->dispute_id]);

    // 2. IF APPROVED: Update Attendance & Time Logs
    if ($status === 'Approved') {
        // Get details (Employee & Date)
        $get_disp = $pdo->prepare("SELECT employee_id, dispute_date FROM attendance_disputes WHERE dispute_id = ?");
        $get_disp->execute([$data->dispute_id]);
        $dispute = $get_disp->fetch(PDO::FETCH_ASSOC);

        if ($dispute) {
            $emp_id = $dispute['employee_id'];
            $date = $dispute['dispute_date'];

            // A. Update Attendance Status
            if (!empty($data->new_status)) {
                $sync = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE attendance_status = ?");
                $sync->execute([$emp_id, $date, $data->new_status, $data->new_status]);
            }

            // B. Update Time Logs (Time In / Time Out) if provided
            if ($time_in && $time_out) {
                // Combine Date + Time
                $dt_in = date('Y-m-d H:i:s', strtotime("$date $time_in"));
                $dt_out = date('Y-m-d H:i:s', strtotime("$date $time_out"));

                // Upsert Time Log (Link by Employee + Date)
                $log_check = $pdo->prepare("SELECT time_log_id FROM time_logs WHERE employee_id = ? AND log_date = ?");
                $log_check->execute([$emp_id, $date]);
                
                if ($log_check->rowCount() > 0) {
                    $update_log = $pdo->prepare("UPDATE time_logs SET time_in = ?, time_out = ? WHERE employee_id = ? AND log_date = ?");
                    $update_log->execute([$dt_in, $dt_out, $emp_id, $date]);
                } else {
                    // Need to get attendance_id for linkage
                    $get_att = $pdo->prepare("SELECT attendance_id FROM attendance WHERE employee_id = ? AND attendance_date = ?");
                    $get_att->execute([$emp_id, $date]);
                    $att_id = $get_att->fetchColumn();

                    $insert_log = $pdo->prepare("INSERT INTO time_logs (employee_id, attendance_id, time_in, time_out, log_date) VALUES (?, ?, ?, ?, ?)");
                    $insert_log->execute([$emp_id, $att_id, $dt_in, $dt_out, $date]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Dispute settled as $status."]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); echo json_encode(["error" => $e->getMessage()]);
}
?>