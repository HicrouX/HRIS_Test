<?php
// api/management/resolve_dispute.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Verify Coach Access (Role 2)
verifyAccess([2]); 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->dispute_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters."]);
    exit;
}

// Logic: Coach APPROVES (Finalizes) or DENIES
$status = ($data->action === 'APPROVE') ? 'Approved' : 'Denied';

try {
    $pdo->beginTransaction();

    // 1. Update Dispute Table Status
    $stmt = $pdo->prepare("UPDATE attendance_disputes SET status = ? WHERE dispute_id = ?");
    $stmt->execute([$status, $data->dispute_id]);

    // 2. IF APPROVED: Update the Main Attendance Table
    if ($status === 'Approved' && !empty($data->new_status)) {
        // Get the dispute details to find Employee & Date
        $get_disp = $pdo->prepare("SELECT employee_id, dispute_date FROM attendance_disputes WHERE dispute_id = ?");
        $get_disp->execute([$data->dispute_id]);
        $dispute = $get_disp->fetch();

        if ($dispute) {
            // Upsert: Update if exists, Insert if not
            $sync = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE attendance_status = ?");
            $sync->execute([$dispute['employee_id'], $dispute['dispute_date'], $data->new_status, $data->new_status]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => "Dispute has been " . strtolower($status) . " and attendance updated."]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>