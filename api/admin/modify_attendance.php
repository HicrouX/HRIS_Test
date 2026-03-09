<?php
// api/admin/modify_attendance.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Security check: Only Admin/Super Admin can override attendance
verifyAccess([1, 2]); 

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->status) && !empty($data->attendance_id)) {
    try {
        // Prepare the update for the attendance table
        $stmt = $pdo->prepare("UPDATE attendance_logs 
                               SET attendance_status = ? 
                               WHERE attendance_id = ?");
        
        $stmt->execute([$data->status, $data->attendance_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Attendance record manually updated by Administrator."]);
        } else {
            echo json_encode(["error" => "No changes made. Record may not exist or status is identical."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Update Failed: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid data. Both attendance_id and new status are required."]);
}
?>