<?php
// api/management/endorse_leave.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Coach (2), Admin (3), Super Admin (4)
verifyAccess([2, 3, 4]);

// The 'acting coach' is the currently logged-in employee
$acting_emp_id = $_SESSION['employee_id']; 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->leave_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing Leave ID."]);
    exit;
}

try {
    // Update status to 'Endorsed' and record the reviewer ID
    // We only update if the current status is 'Pending' to prevent double-processing
    $stmt = $pdo->prepare("UPDATE leave_requests 
                           SET status = 'Endorsed', reviewed_by = ? 
                           WHERE leave_id = ? AND status = 'Pending'");
    
    $stmt->execute([$acting_emp_id, $data->leave_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => "Leave endorsed and forwarded to Admin."]);
    } else {
        echo json_encode(["error" => "Request already processed or not found."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>