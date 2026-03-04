<?php
// api/management/endorse_dispute.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Coaches (2), Admins (3), Super Admins (4)
verifyAccess([2, 3, 4]); 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->dispute_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters."]);
    exit;
}

// Map input actions to database Enum values
$status = ($data->action === 'ENDORSE') ? 'Endorsed' : 'Denied';

try {
    // Update the dispute status and include optional remarks
    $stmt = $pdo->prepare("UPDATE attendance_disputes SET status = ?, remarks = ? WHERE dispute_id = ?");
    $stmt->execute([$status, ($data->remarks ?? ''), $data->dispute_id]);
    echo json_encode(["success" => "Dispute status updated to " . $status]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>