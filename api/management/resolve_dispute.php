<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]); 
$data = json_decode(file_get_contents("php://input"));
$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

if (empty($data->dispute_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing Dispute ID."]);
    exit;
}

try {
    // Check the role of the requester
    $stmt = $pdo->prepare("SELECT u.role_id FROM attendance_disputes ad
                           JOIN employees e ON ad.employee_id = e.employee_id
                           JOIN users u ON e.user_id = u.user_id
                           WHERE ad.dispute_id = ?");
    $stmt->execute([$data->dispute_id]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requester) {
        http_response_code(404);
        echo json_encode(["error" => "Dispute not found."]);
        exit;
    }

    // If requester is a Coach (role 3) and acting user is also a Coach (role 3)
    if ($requester['role_id'] == 3 && $acting_role_id == 3) {
        http_response_code(403);
        echo json_encode(["error" => "Coaches cannot resolve other coaches' requests. Only Admins can do this."]);
        exit;
    }

    $status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

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

            // Check if a time log already exists for this employee and date
            $checkTime = $pdo->prepare("SELECT time_log_id FROM time_logs WHERE employee_id = ? AND log_date = ?");
            $checkTime->execute([$dispute['employee_id'], $dispute['dispute_date']]);
            $existing_time_log_id = $checkTime->fetchColumn();

            // Note: time_logs needs user_id. For consistency, we'll try to get it from the employees record.
            $getUserId = $pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $getUserId->execute([$dispute['employee_id']]);
            $user_id = $getUserId->fetchColumn();

            if ($existing_time_log_id) {
                $syncTime = $pdo->prepare("UPDATE time_logs SET user_id = ?, attendance_id = ?, time_in = ?, time_out = ? WHERE time_log_id = ?");
                $syncTime->execute([$user_id, $attendance_id, $dt_in, $dt_out, $existing_time_log_id]);
            } else {
                $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) VALUES (?, ?, ?, ?, ?, ?)");
                $syncTime->execute([$dispute['employee_id'], $user_id, $attendance_id, $dt_in, $dt_out, $dispute['dispute_date']]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Resolved."]);
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}