<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';
verifyAccess(3); // Ensure role is Admin

$id = $_GET['attendance_id'];

// Prevent deletion of records with active time logs
$checkLogs = $pdo->prepare("SELECT time_log_id FROM time_logs WHERE attendance_id = ?");
$checkLogs->execute([$id]);

if ($checkLogs->rowCount() > 0) {
    http_response_code(400);
    echo json_encode(["error" => "Cannot delete. This record has associated Time Logs."]);
} else {
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE attendance_id = ?");
    $stmt->execute([$id]);
    echo json_encode(["success" => "Record safely removed."]);
}
?>