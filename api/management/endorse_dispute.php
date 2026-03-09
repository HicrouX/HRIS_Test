<?php
// api/management/endorse_dispute.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Super Admins (1), Admins (2), Coaches (3)
verifyAccess([1, 2, 3]); 

$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

$data = json_decode(file_get_contents("php://input"));

if (empty($data->dispute_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters."]);
    exit;
}

try {
    // Check the role of the requester
    $stmt = $pdo->prepare("SELECT u.role_id FROM attendance_disputes ad
                           JOIN employees e ON ad.employee_id = e.employee_id
                           JOIN users u ON e.user_id = u.user_id
                           WHERE ad.dispute_id = ?");
    $stmt->execute([$data->dispute_id]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requester) {
        http_response_code(404);
        echo json_encode(["error" => "Dispute not found."]);
        exit;
    }

    // If requester is a Coach (role 3) and acting user is also a Coach (role 3)
    if ($requester['role_id'] == 3 && $acting_role_id == 3) {
        http_response_code(403);
        echo json_encode(["error" => "Coaches cannot endorse other coaches' requests. Only Admins can do this."]);
        exit;
    }

    // Map input actions to database Enum values
    $status = ($data->action === 'ENDORSE') ? 'Endorsed' : 'Denied';

    // Update the dispute status and include optional remarks
    $stmt = $pdo->prepare("UPDATE attendance_disputes SET status = ?, remarks = ? WHERE dispute_id = ?");
    $stmt->execute([$status, ($data->remarks ?? ''), $data->dispute_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => "Dispute status updated to " . $status]);
    } else {
        echo json_encode(["error" => "Request already processed or not found."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>