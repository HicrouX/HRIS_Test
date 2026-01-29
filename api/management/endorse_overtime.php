<?php
// api/management/endorse_overtime.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2]); // Coach Only

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ot_id) || empty($data->coach_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing ID."]);
    exit;
}

// Update status to 'Endorsed'
$stmt = $pdo->prepare("UPDATE overtime_requests 
                       SET status = 'Endorsed' 
                       WHERE ot_id = ? AND status = 'Pending'");

if ($stmt->execute([$data->ot_id])) {
    echo json_encode(["success" => "Overtime Endorsed. Sent to Admin."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database error."]);
}
?>