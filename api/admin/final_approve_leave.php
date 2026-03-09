<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);
$acting_user_id = $_SESSION['user_id']; 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->leave_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid parameters."]);
    exit;
}

try {
    $pdo->beginTransaction();
    $status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

    // Update with the admin's user_id
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, approved_by = ? WHERE leave_id = ?");
    $stmt->execute([$status, $acting_user_id, $data->leave_id]);

    if ($status === 'Approved') {
        $get_leave = $pdo->prepare("SELECT employee_id, start_date, end_date FROM leave_requests WHERE leave_id = ?");
        $get_leave->execute([$data->leave_id]);
        $leave = $get_leave->fetch();

        if ($leave) {
            $start = new DateTime($leave['start_date']);
            $end = new DateTime($leave['end_date']);
            $end->modify('+1 day'); 

            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end);

            $getUserId = $pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $getUserId->execute([$leave['employee_id']]);
            $user_id = $getUserId->fetchColumn();

            foreach ($period as $date) {
                $date_str = $date->format('Y-m-d');
                $syncAtt = $pdo->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, attendance_status) 
                                       VALUES (?, ?, 'On Leave') 
                                       ON DUPLICATE KEY UPDATE attendance_status = 'On Leave'");
                $syncAtt->execute([$leave['employee_id'], $date_str]);

                // Get the attendance_id (whether new or existing)
                $getAttId = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
                $getAttId->execute([$leave['employee_id'], $date_str]);
                $attendance_id = $getAttId->fetchColumn();

                // Check for existing time log
                $checkTime = $pdo->prepare("SELECT time_log_id FROM time_logs WHERE employee_id = ? AND log_date = ?");
                $checkTime->execute([$leave['employee_id'], $date_str]);
                $existing_time_log_id = $checkTime->fetchColumn();

                if ($existing_time_log_id) {
                    $syncTime = $pdo->prepare("UPDATE time_logs SET user_id = ?, attendance_id = ?, time_in = NULL, time_out = NULL WHERE time_log_id = ?");
                    $syncTime->execute([$user_id, $attendance_id, $existing_time_log_id]);
                } else {
                    $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                           VALUES (?, ?, ?, NULL, NULL, ?)");
                    $syncTime->execute([$leave['employee_id'], $user_id, $attendance_id, $date_str]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Leave $status successfully."]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>