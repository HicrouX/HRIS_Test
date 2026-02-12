<?php
// api/users/file_dispute.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->employee_id) || empty($data->date) || empty($data->reason)) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all fields."]);
    exit;
}

try {
    // Check duplicates
    $check = $pdo->prepare("SELECT dispute_id FROM attendance_disputes WHERE employee_id = ? AND dispute_date = ?");
    $check->execute([$data->employee_id, $data->date]);
    if ($check->rowCount() > 0) {
        echo json_encode(["error" => "You already filed a dispute for this date."]);
        exit;
    }

    $sql = "INSERT INTO attendance_disputes (employee_id, dispute_date, reason) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data->employee_id, $data->date, $data->reason]);

    echo json_encode(["success" => "Dispute submitted successfully."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>