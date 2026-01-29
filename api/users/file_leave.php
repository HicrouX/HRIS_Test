<?php
// api/users/file_leave.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// ⚠️ IMPORTANT: 'X-USER-ROLE' is added here so the dashboard doesn't get blocked
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

// Handle Browser Preflight Check (The "OPTIONS" request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. IMPORT NECESSARY FILES
// These paths must be correct for verifyAccess() to work
require_once '../config/db.php';
require_once '../middleware/auth.php'; 

// 2. VERIFY PERMISSIONS
// This checks if the user is Role 1 (Employee)
verifyAccess([1]); 

// 3. GET DATA
$data = json_decode(file_get_contents("php://input"));

// 4. VALIDATION
if (
    empty($data->employee_id) || 
    empty($data->leave_type) || 
    empty($data->start_date) || 
    empty($data->end_date) || 
    empty($data->reason)
) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all required fields."]);
    exit;
}

// 5. INSERT INTO DATABASE
try {
    $sql = "INSERT INTO leave_requests 
            (employee_id, leave_type, start_date, end_date, reason, status, agreement_1, agreement_2) 
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    
    // Convert Javascript "true/false" to Database "1/0"
    $agree1 = !empty($data->agreement_1) ? 1 : 0;
    $agree2 = !empty($data->agreement_2) ? 1 : 0;

    if ($stmt->execute([
        $data->employee_id, 
        $data->leave_type, 
        $data->start_date, 
        $data->end_date, 
        $data->reason,
        $agree1, 
        $agree2
    ])) {
        echo json_encode(["success" => "Leave request submitted successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database insertion failed."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>