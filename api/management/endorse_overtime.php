<?php
// api/management/endorse_overtime.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2, 3, 4]);

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ot_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing Overtime ID."]);
    exit;
}

try {
    // Update status to 'Endorsed'
    // Note: The overtime_requests table in your SQL uses an ENUM('Pending','Endorsed','Approved','Denied')
    $stmt = $pdo->prepare("UPDATE overtime_requests 
                           SET status = 'Endorsed' 
                           WHERE ot_id = ? AND status = 'Pending'");

    if ($stmt->execute([$data->ot_id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Overtime Endorsed successfully."]);
        } else {
            echo json_encode(["error" => "Overtime request not found or already processed."]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>