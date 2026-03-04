<?php
// api/users/file_overtime.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->employee_id) || empty($data->start_time) || empty($data->end_time) || empty($data->purpose)) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all fields."]);
    exit;
}

try {
    $sql = "INSERT INTO overtime_requests (employee_id, ot_type, start_time, end_time, purpose, status, agreement_1, agreement_2) VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data->employee_id, $data->ot_type, $data->start_time, $data->end_time, $data->purpose,
        (!empty($data->agreement_1) ? 1 : 0), (!empty($data->agreement_2) ? 1 : 0)
    ]);
    echo json_encode(["success" => "Overtime request submitted."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>