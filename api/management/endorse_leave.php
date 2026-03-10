<?php
// api/management/endorse_leave.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2]); // Coach Only

$data = json_decode(file_get_contents("php://input"));

if (empty($data->leave_id) || empty($data->coach_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required IDs."]);
    exit;
}

try {
    // FIX: Translate Coach Employee ID to User ID for 'reviewed_by' column
    $findUser = $pdo->prepare("SELECT user_id FROM users WHERE employee_id = ?");
    $findUser->execute([$data->coach_id]);
    $userRow = $findUser->fetch();

    if (!$userRow) {
        throw new Exception("Coach record not found.");
    }

    // FIX: Strictly set status to 'Endorsed' to move it to the Admin queue
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'Endorsed', reviewed_by = ? WHERE leave_id = ? AND status = 'Pending'");
    $stmt->execute([$userRow['user_id'], $data->leave_id]);

    echo json_encode(["success" => "Leave endorsed and forwarded to Admin."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>