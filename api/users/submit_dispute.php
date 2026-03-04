<?php
// api/users/submit_dispute.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3, 4]); // Any role can file a dispute

$data = json_decode(file_get_contents("php://input"));

if (empty($data->dispute_date) || empty($data->reason) || empty($data->cluster_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields."]);
    exit;
}

try {
    // Insert into the attendance_disputes table you provided in your SQL
    $stmt = $pdo->prepare("INSERT INTO attendance_disputes 
        (employee_id, cluster_id, dispute_date, dispute_type, reason, status) 
        VALUES (?, ?, ?, ?, ?, 'Pending')");
    
    $stmt->execute([
        $_SESSION['employee_id'],
        $data->cluster_id, // This comes from the auto-detection
        $data->dispute_date,
        $data->dispute_type,
        $data->reason
    ]);

    echo json_encode(["success" => "Dispute submitted successfully."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>