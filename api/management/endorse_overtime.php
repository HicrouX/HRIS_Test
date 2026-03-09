<?php
// api/management/endorse_overtime.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]);

$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ot_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing Overtime ID."]);
    exit;
}

try {
    // Check the role of the requester
    $stmt = $pdo->prepare("SELECT u.role_id FROM overtime_requests orq
                           JOIN employees e ON orq.employee_id = e.employee_id
                           JOIN users u ON e.user_id = u.user_id
                           WHERE orq.ot_id = ?");
    $stmt->execute([$data->ot_id]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requester) {
        http_response_code(404);
        echo json_encode(["error" => "Overtime request not found."]);
        exit;
    }

    // If requester is a Coach (role 3) and acting user is also a Coach (role 3)
    if ($requester['role_id'] == 3 && $acting_role_id == 3) {
        http_response_code(403);
        echo json_encode(["error" => "Coaches cannot endorse other coaches' requests. Only Admins can do this."]);
        exit;
    }

    // Update status to 'Endorsed'
    // Note: The overtime_requests table in your SQL uses an ENUM('Pending','Endorsed','Approved','Denied')
    $stmt = $pdo->prepare("UPDATE overtime_requests 
                           SET status = 'Endorsed', approved_by = ? 
                           WHERE ot_id = ? AND status = 'Pending'");

    if ($stmt->execute([$acting_user_id, $data->ot_id])) {
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