<?php
// api/management/endorse_leave.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow Super Admin (1), Admin (2), Coach (3)
verifyAccess([1, 2, 3]);

// The 'acting coach' is the currently logged-in user
$acting_user_id = $_SESSION['user_id']; 
$acting_role_id = $_SESSION['role_id'];

$data = json_decode(file_get_contents("php://input"));

if (empty($data->leave_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing Leave ID."]);
    exit;
}

try {
    // Check the role of the requester
    $stmt = $pdo->prepare("SELECT u.role_id FROM leave_requests lr
                           JOIN employees e ON lr.employee_id = e.employee_id
                           JOIN users u ON e.user_id = u.user_id
                           WHERE lr.leave_id = ?");
    $stmt->execute([$data->leave_id]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requester) {
        http_response_code(404);
        echo json_encode(["error" => "Leave request not found."]);
        exit;
    }

    // If requester is a Coach (role 3) and acting user is also a Coach (role 3)
    if ($requester['role_id'] == 3 && $acting_role_id == 3) {
        http_response_code(403);
        echo json_encode(["error" => "Coaches cannot endorse other coaches' requests. Only Admins can do this."]);
        exit;
    }

    // Update status to 'Endorsed' and record the reviewer ID
    // We only update if the current status is 'Pending' to prevent double-processing
    $stmt = $pdo->prepare("UPDATE leave_requests 
                           SET status = 'Endorsed', reviewed_by = ? 
                           WHERE leave_id = ? AND status = 'Pending'");
    
    $stmt->execute([$acting_user_id, $data->leave_id]);

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