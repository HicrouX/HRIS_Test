<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);
$acting_emp_id = $_SESSION['employee_id']; 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->leave_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid parameters."]);
    exit;
}

try {
    $pdo->beginTransaction();
    $status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

    // Update with the admin's employee_id
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, approved_by = ? WHERE leave_id = ?");
    $stmt->execute([$status, $acting_emp_id, $data->leave_id]);

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

                $attendance_id = $pdo->lastInsertId();
                if (!$attendance_id) {
                    $getAttId = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
                    $getAttId->execute([$leave['employee_id'], $date_str]);
                    $attendance_id = $getAttId->fetchColumn();
                }

                $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                       VALUES (?, ?, ?, NULL, NULL, ?) 
                                       ON DUPLICATE KEY UPDATE time_in = NULL, time_out = NULL");
                $syncTime->execute([$leave['employee_id'], $user_id, $attendance_id, $date_str]);
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