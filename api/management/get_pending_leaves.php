<?php
// api/management/get_pending_leaves.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
// ⚠️ Allow the dashboard to send the Role ID
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Verify Coach Access (Role 2)
verifyAccess([2]);

// Get the User ID passed from the dashboard
$coach_id = isset($_GET['user_id']) ? $_GET['user_id'] : die(json_encode(["error" => "Missing User ID"]));

try {
    // ⚠️ This query requires the 'created_at' column we just added!
    $sql = "SELECT 
                lr.leave_id, 
                lr.leave_type, 
                lr.start_date, 
                lr.end_date, 
                lr.reason, 
                lr.created_at,
                e.first_name, 
                e.last_name
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            JOIN team_members tm ON e.employee_id = tm.employee_id
            JOIN team_cluster tc ON tm.team_id = tc.team_id
            WHERE lr.status = 'Pending' 
            AND tc.coach_id = ?
            ORDER BY lr.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coach_id]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>