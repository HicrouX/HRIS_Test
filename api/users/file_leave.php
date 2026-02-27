<?php
// api/users/file_leave.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

// 🔴 FIX: Safe Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php'; 

// 🔴 FIX: Allow All Roles (1=Emp, 2=Coach, 3=Admin, 4=Super)
verifyAccess([1, 2, 3, 4]); 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->employee_id) || empty($data->leave_type) || empty($data->start_date) || empty($data->end_date) || empty($data->reason)) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all required fields."]);
    exit;
}

try {
    $sql = "INSERT INTO leave_requests 
            (employee_id, leave_type, start_date, end_date, reason, status, agreement_1, agreement_2) 
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    
    $agree1 = !empty($data->agreement_1) ? 1 : 0;
    $agree2 = !empty($data->agreement_2) ? 1 : 0;

    if ($stmt->execute([$data->employee_id, $data->leave_type, $data->start_date, $data->end_date, $data->reason, $agree1, $agree2])) {
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