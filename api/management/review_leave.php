<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';
verifyAccess(2); // Ensure role is Coach

$data = json_decode(file_get_contents("php://input"));
try {
    $pdo->beginTransaction();

    // Update Leave Status
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, reviewed_by = ? WHERE leave_id = ?");
    $stmt->execute([$data->status, $data->reviewed_by, $data->leave_id]);

    // If approved, auto-populate attendance table
    if ($data->status === 'Approved') {
        $details = $pdo->prepare("SELECT * FROM leave_requests WHERE leave_id = ?");
        $details->execute([$data->leave_id]);
        $leave = $details->fetch();

        $insertAtt = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) VALUES (?, ?, 'On Leave')");
        // Logic to loop through dates can be added here
        $insertAtt->execute([$leave['employee_id'], $leave['start_date']]);
    }

    $pdo->commit();
    echo json_encode(["success" => "Leave processed."]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => "Transaction failed."]);
}
?>