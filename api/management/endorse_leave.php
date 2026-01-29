<?php
// api/management/endorse_leave.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2]); // Coach Only

$data = json_decode(file_get_contents("php://input"));

if (empty($data->leave_id) || empty($data->coach_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing ID."]);
    exit;
}

try {
    // 1. TRANSLATE ID: Convert "Coach Employee ID" (102) to "Coach User ID" (2)
    // The database requires a User ID for the 'reviewed_by' column.
    $findUser = $pdo->prepare("SELECT user_id FROM users WHERE employee_id = ?");
    $findUser->execute([$data->coach_id]);
    $userRow = $findUser->fetch();

    if (!$userRow) {
        http_response_code(404);
        echo json_encode(["error" => "Coach not found in Users table."]);
        exit;
    }
    
    $reviewer_user_id = $userRow['user_id'];

    // 2. UPDATE with the VALID User ID
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'Endorsed', reviewed_by = ? WHERE leave_id = ?");

    if ($stmt->execute([$reviewer_user_id, $data->leave_id])) {
        echo json_encode(["success" => "Leave Endorsed Successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database error."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>