<?php
// api/users/file_leave.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php'; 

verifyAccess([1, 2, 3, 4]); 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->employee_id) || empty($data->leave_type) || empty($data->start_date) || empty($data->end_date) || empty($data->reason)) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all required fields."]);
    exit;
}

try {
    $sql = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status, agreement_1, agreement_2) VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data->employee_id, $data->leave_type, $data->start_date, $data->end_date, $data->reason,
        (!empty($data->agreement_1) ? 1 : 0), (!empty($data->agreement_2) ? 1 : 0)
    ]);
    echo json_encode(["success" => "Leave request submitted successfully."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>