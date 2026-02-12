<?php
// api/management/endorse_dispute.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2]); 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->dispute_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters."]);
    exit;
}

$status = ($data->action === 'ENDORSE') ? 'Endorsed' : 'Denied';

try {
    $stmt = $pdo->prepare("UPDATE attendance_disputes SET status = ? WHERE dispute_id = ?");
    $stmt->execute([$status, $data->dispute_id]);
    echo json_encode(["success" => "Dispute " . $status]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>