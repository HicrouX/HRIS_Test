<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents("php://input"));

//Check for overlapping dates
$check = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? AND status != 'Denied' 
                        AND (start_date <= ? AND end_date >= ?)");
$check->execute([$data->employee_id, $data->end_date, $data->start_date]);

if ($check->rowCount() > 0) {
    echo json_encode(["error" => "You already have a pending or approved leave for these dates."]);
} else {
    $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason) VALUES (?,?,?,?,?)");
    $stmt->execute([$data->employee_id, $data->leave_type, $data->start_date, $data->end_date, $data->reason]);
    echo json_encode(["success" => "Request submitted."]);
}
?>