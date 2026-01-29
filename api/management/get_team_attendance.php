<?php
// api/management/get_team_attendance.php
require_once '../config/db.php';

$coach_id = filter_input(INPUT_GET, 'coach_id', FILTER_SANITIZE_NUMBER_INT);

// COMPLEX JOIN: 
// 1. Get Attendance
// 2. Get Employee Names
// 3. Get Schedule (The Admin's Input) matching the attendance day
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
        JOIN team_members tm ON e.employee_id = tm.employee_id
        JOIN team_cluster tc ON tm.team_id = tc.team_id
        -- JOIN Schedule based on the day of the week (Mon, Tue, etc.)
        LEFT JOIN schedules s ON e.employee_id = s.employee_id 
             AND s.work_day = DAYNAME(a.attendance_date)
        WHERE tc.coach_id = ?
        ORDER BY a.attendance_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$coach_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>