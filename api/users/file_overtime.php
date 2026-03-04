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

// VALIDATION: Same Date and Max 2 Hours
$start = new DateTime($data->start_time);
$end = new DateTime($data->end_time);
$today = new DateTime('today');
$yesterday = (new DateTime('yesterday'))->format('Y-m-d');

if ($start->format('Y-m-d') !== $yesterday) {
    http_response_code(400);
    echo json_encode(["error" => "Overtime can only be filed for yesterday ($yesterday)."]);
    exit;
}

if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
    http_response_code(400);
    echo json_encode(["error" => "Overtime must start and end on the same date."]);
    exit;
}

$interval = $start->diff($end);
$hours = $interval->h + ($interval->i / 60);

if ($interval->invert || $hours <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid time range."]);
    exit;
}

if ($hours > 2) {
    http_response_code(400);
    echo json_encode(["error" => "Overtime is limited to a maximum of 2 hours."]);
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