<?php
// api/admin/final_approve_overtime.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]); // Admin Only

$data = json_decode(file_get_contents("php://input"));
$action = $data->action; // 'APPROVE' or 'DENY'
$ot_id = $data->ot_id;

try {
    $pdo->beginTransaction();

    $status = ($action === 'APPROVE') ? 'Approved' : 'Denied';

    // 1. Update Request Status
    $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ? WHERE ot_id = ?");
    $stmt->execute([$status, $ot_id]);

    // 2. SYNC TO DASHBOARD (Only if Approved)
    if ($status === 'Approved') {
        // Get details to find the date and employee
        $get_ot = $pdo->prepare("SELECT employee_id, start_time, ot_type FROM overtime_requests WHERE ot_id = ?");
        $get_ot->execute([$ot_id]);
        $ot = $get_ot->fetch();

        if ($ot) {
            $date_str = date('Y-m-d', strtotime($ot['start_time']));

            // Map the specific OT type to a Dashboard Status
            // If it's "Duty on Rest Day", show that. Otherwise show "Overtime".
            $dashboard_status = ($ot['ot_type'] === 'Duty on Rest Day') ? 'Duty on Rest Day' : 'Overtime';

            // Update Attendance Table
            // We use ON DUPLICATE KEY UPDATE so if they are already 'Present', it switches to 'Overtime'
            $sync = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE attendance_status = ?");
            $sync->execute([$ot['employee_id'], $date_str, $dashboard_status, $dashboard_status]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Overtime Processed. Dashboard updated."]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
}
?>