<?php
// api/management/review_leave.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Coach (2), Admin (3), Super Admin (4)
verifyAccess([2, 3, 4]); 

$data = json_decode(file_get_contents("php://input"));
$acting_emp_id = $_SESSION['employee_id']; // The ID of the person reviewing

try {
    $pdo->beginTransaction();

    // 1. Update Leave Status and record who reviewed/approved it
    // If Admin approves, we set approved_by. If Coach endorses, we set reviewed_by.
    $column = ($data->status === 'Approved') ? 'approved_by' : 'reviewed_by';
    
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, $column = ? WHERE leave_id = ?");
    $stmt->execute([$data->status, $acting_emp_id, $data->leave_id]);

    // 2. If approved, populate the attendance table for the entire duration
    if ($data->status === 'Approved') {
        $details = $pdo->prepare("SELECT employee_id, start_date, end_date FROM leave_requests WHERE leave_id = ?");
        $details->execute([$data->leave_id]);
        $leave = $details->fetch();

        if ($leave) {
            $start = new DateTime($leave['start_date']);
            $end = new DateTime($leave['end_date']);
            $end->modify('+1 day'); // Include the end date in the loop

            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end);

            $insertAtt = $pdo->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, attendance_status) 
                                   VALUES (?, ?, 'On Leave') 
                                   ON DUPLICATE KEY UPDATE attendance_status = 'On Leave'");

            // Note: time_logs needs user_id. For consistency, we'll try to get it from the employees record.
            $getUserId = $pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $getUserId->execute([$leave['employee_id']]);
            $user_id = $getUserId->fetchColumn();

            $syncTime = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, time_out, log_date) 
                                   VALUES (?, ?, ?, NULL, NULL, ?) 
                                   ON DUPLICATE KEY UPDATE time_in = NULL, time_out = NULL");

            foreach ($period as $date) {
                $date_str = $date->format('Y-m-d');
                $insertAtt->execute([$leave['employee_id'], $date_str]);
                
                $attendance_id = $pdo->lastInsertId();
                if (!$attendance_id) {
                    $getAttId = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
                    $getAttId->execute([$leave['employee_id'], $date_str]);
                    $attendance_id = $getAttId->fetchColumn();
                }

                $syncTime->execute([
                    $leave['employee_id'], $user_id, $attendance_id, $date_str
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Leave status updated to " . $data->status]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Transaction failed: " . $e->getMessage()]);
}
?>