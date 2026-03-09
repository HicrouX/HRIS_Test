<?php
// api/management/review_leave.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Super Admin (1), Admin (2), Coach (3)
verifyAccess([1, 2, 3]); 

$data = json_decode(file_get_contents("php://input"));
$acting_user_id = $_SESSION['user_id']; // The ID of the person reviewing
$acting_role_id = $_SESSION['role_id'];

if (empty($data->leave_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing Leave ID."]);
    exit;
}

try {
    // Check the role of the requester
    $stmt = $pdo->prepare("SELECT u.role_id FROM leave_requests lr
                           JOIN employees e ON lr.employee_id = e.employee_id
                           JOIN users u ON e.user_id = u.user_id
                           WHERE lr.leave_id = ?");
    $stmt->execute([$data->leave_id]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requester) {
        http_response_code(404);
        echo json_encode(["error" => "Leave request not found."]);
        exit;
    }

    // If requester is a Coach (role 3) and acting user is also a Coach (role 3)
    if ($requester['role_id'] == 3 && $acting_role_id == 3) {
        http_response_code(403);
        echo json_encode(["error" => "Coaches cannot review other coaches' requests. Only Admins can do this."]);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Update Leave Status and record who reviewed/approved it
    // If Admin approves, we set approved_by. If Coach endorses, we set reviewed_by.
    $column = ($data->status === 'Approved') ? 'approved_by' : 'reviewed_by';
    
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, $column = ? WHERE leave_id = ?");
    $stmt->execute([$data->status, $acting_user_id, $data->leave_id]);

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