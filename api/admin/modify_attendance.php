<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// FIX: Added security check for Admin/Super Admin
verifyAccess([3, 4]); 

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->status) && !empty($data->attendance_id)) {
    $stmt = $pdo->prepare("UPDATE attendance SET attendance_status = ? WHERE attendance_id = ?");
    $stmt->execute([$data->status, $data->attendance_id]);
    echo json_encode(["success" => "Attendance record updated by Admin."]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid data provided."]);
}
?>