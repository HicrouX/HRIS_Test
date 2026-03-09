<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';
verifyAccess([1, 2]); // Admin or Super Admin

$id = $_GET['attendance_id'] ?? 0;

try {
    // Check for associated Break Logs first
    $checkBreaks = $pdo->prepare("SELECT break_log_id FROM break_logs WHERE attendance_id = ?");
    $checkBreaks->execute([$id]);

    if ($checkBreaks->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Cannot delete. This record has associated Break Logs."]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM attendance_logs WHERE attendance_id = ?");
    $stmt->execute([$id]);
    echo json_encode(["success" => "Record safely removed."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Deletion failed: " . $e->getMessage()]);
}
?>