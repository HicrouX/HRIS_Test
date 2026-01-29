<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

$data = json_decode(file_get_contents("php://input"));
$action = $data->action; // 'ENDORSE' or 'APPROVE' or 'DENY'
$ot_id = $data->ot_id;

// Logic Map
$new_status = '';
if ($action === 'ENDORSE') $new_status = 'Endorsed';
if ($action === 'APPROVE') $new_status = 'Approved';
if ($action === 'DENY')    $new_status = 'Denied';

if ($new_status && $ot_id) {
    try {
        $pdo->beginTransaction();

        // 1. Update the Request Status
        $sql = "UPDATE overtime_requests SET status = ? WHERE ot_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_status, $ot_id]);

        // 2. COMPLEX SYNC: If Approved, update the Main Attendance Dashboard (Image #4)
        if ($new_status === 'Approved') {
            // Get the details of the OT request
            $get_ot = $pdo->prepare("SELECT employee_id, start_time, ot_type FROM overtime_requests WHERE ot_id = ?");
            $get_ot->execute([$ot_id]);
            $request = $get_ot->fetch();

            if ($request) {
                // Extract the Date (YYYY-MM-DD) from the start_time
                $ot_date = date('Y-m-d', strtotime($request['start_time']));

                // Determine the Status Label for the Dashboard
                // Map specific OT types to the main 'Overtime' status or add new ENUMs
                $dashboard_status = 'Overtime'; 
                
                // If you want "Duty on Rest Day" to show specifically, ensure your ENUM supports it.
                // For now, we map it to 'Overtime' to fit your current schema.

                // Update the Attendance Table
                // We use INSERT ... ON DUPLICATE KEY UPDATE in case the attendance record doesn't exist yet
                $sync = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE attendance_status = ?");
                $sync->execute([$request['employee_id'], $ot_date, $dashboard_status, $dashboard_status]);
            }
        }

        $pdo->commit();
        echo json_encode(["success" => "Request updated and synced to Dashboard."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Update failed: " . $e->getMessage()]);
    }
}
?>