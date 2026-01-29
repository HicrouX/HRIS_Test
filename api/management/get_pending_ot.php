<?php
// api/management/get_pending_ot.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// 1. Verify User is a Coach (Role 2)
verifyAccess([2]);

$coach_id = $_GET['user_id']; // Passed from React

// 2. Fetch 'Pending' Overtime Requests for this Coach's Team
$sql = "SELECT 
            ot.ot_id, 
            ot.ot_type, 
            ot.start_time, 
            ot.end_time, 
            ot.purpose, 
            ot.created_at,
            e.first_name, 
            e.last_name, 
            e.position
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.employee_id
        JOIN team_members tm ON e.employee_id = tm.employee_id
        JOIN team_cluster tc ON tm.team_id = tc.team_id
        WHERE ot.status = 'Pending' 
        AND tc.coach_id = ?
        ORDER BY ot.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$coach_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>