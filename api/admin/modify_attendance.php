<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents("php://input")); // For PUT requests
$stmt = $pdo->prepare("UPDATE attendance SET attendance_status = ? WHERE attendance_id = ?");
$stmt->execute([$data->status, $data->attendance_id]);
echo json_encode(["message" => "Attendance record updated"]);
?>