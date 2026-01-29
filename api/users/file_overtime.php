<?php
// api/users/file_overtime.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// ⚠️ IMPORTANT: We added 'X-USER-ROLE' to the list below
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php'; // Import the security checker

verifyAccess([1]); // Allow Employee Only

$data = json_decode(file_get_contents("php://input"));

if (empty($data->employee_id) || empty($data->ot_type) || empty($data->start_time) || empty($data->end_time) || empty($data->purpose)) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all required fields."]);
    exit;
}

try {
    // Convert booleans to 1 or 0
    $agree1 = !empty($data->agreement_1) ? 1 : 0;
    $agree2 = !empty($data->agreement_2) ? 1 : 0;

    $sql = "INSERT INTO overtime_requests 
            (employee_id, ot_type, start_time, end_time, purpose, status, agreement_1, agreement_2) 
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data->employee_id, $data->ot_type, $data->start_time, $data->end_time, $data->purpose, $agree1, $agree2]);

    echo json_encode(["success" => "Overtime filed successfully."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>