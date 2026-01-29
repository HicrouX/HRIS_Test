<?php
// api/admin/final_approve_leave.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// ⚠️ VITAL: Allow the dashboard to send the Admin Role ID
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

// Handle Preflight (Browser Check)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

require_once '../config/db.php';
require_once '../middleware/auth.php';

// 1. Verify User is Admin (Role 3) or Super Admin (Role 4)
verifyAccess([3, 4]);

$data = json_decode(file_get_contents("php://input"));

// Safety Check: Ensure data exists before using it
if (empty($data->leave_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid parameters."]);
    exit;
}

$action = $data->action; // 'APPROVE' or 'DENY'
$leave_id = $data->leave_id;

try {
    $pdo->beginTransaction();

    // 2. Determine New Status
    $status = ($action === 'APPROVE') ? 'Approved' : 'Denied';

    // 3. Update Leave Request Table
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE leave_id = ?");
    $stmt->execute([$status, $leave_id]);

    // 4. COMPLEX SYNC: Only if Approved
    if ($status === 'Approved') {
        // Fetch leave details to get the dates and employee ID
        $get_leave = $pdo->prepare("SELECT employee_id, start_date, end_date FROM leave_requests WHERE leave_id = ?");
        $get_leave->execute([$leave_id]);
        $leave = $get_leave->fetch();

        if ($leave) {
            $current_date = strtotime($leave['start_date']);
            $end_date = strtotime($leave['end_date']);

            // Loop through every day of the leave range
            while ($current_date <= $end_date) {
                $date_str = date('Y-m-d', $current_date);

                // A. Insert 'On Leave' into ATTENDANCE table
                // This ensures the dashboard says "Status: ON LEAVE"
                $sync = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) 
                                       VALUES (?, ?, 'On Leave') 
                                       ON DUPLICATE KEY UPDATE attendance_status = 'On Leave'");
                $sync->execute([$leave['employee_id'], $date_str]);

                // B. CLEANUP: Delete Time Logs
                // This ensures the dashboard Time column is BLANK (no 9AM-6PM)
                $clean_logs = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND log_date = ?");
                $clean_logs->execute([$leave['employee_id'], $date_str]);

                // Move to next day
                $current_date = strtotime("+1 day", $current_date);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Leave Approved. Dashboard updated to show 'On Leave' with blank time."]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Approval failed: " . $e->getMessage()]);
}
?>