<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// 1. Get User Details from Request
$role_id = $_GET['role_id'];       // 2=Coach, 3=Admin
$user_id = $_GET['user_id'];       // The logged-in user's ID

// 2. Base Query (Joins Schedules for the "9AM-6PM" format)
$sql = "SELECT 
            a.attendance_id, 
            a.attendance_date, 
            a.attendance_status,
            e.first_name, 
            e.last_name, 
            e.position,
            s.schedule_start, 
            s.schedule_end
        FROM attendance a
        JOIN employees e ON a.employee_id = e.employee_id
        LEFT JOIN schedules s ON e.employee_id = s.employee_id 
             AND s.work_day = DAYNAME(a.attendance_date)";

// 3. LOGIC SWITCH: Admin vs Coach
if ($role_id == 2) {
    // --- COACH VIEW (FILTERED) ---
    // Join team tables to restrict results
    $sql .= " JOIN team_members tm ON e.employee_id = tm.employee_id
              JOIN team_cluster tc ON tm.team_id = tc.team_id
              WHERE tc.coach_id = ? 
              ORDER BY a.attendance_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]); // Pass Coach ID to filter

} else {
    // --- ADMIN VIEW (ALL ACCESS) ---
    // No extra joins needed, just show everything
    $sql .= " ORDER BY a.attendance_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>