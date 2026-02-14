<?php
// api/management/get_team_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

$coach_id = isset($_GET['coach_id']) ? $_GET['coach_id'] : 0;

try {
    // 🚀 TEAM ROSTER: Select Unique Employees in the Team + Latest Attendance + Time Logs
    $sql = "SELECT 
                e.employee_id, 
                e.first_name, 
                e.last_name,
                e.position,
                a.attendance_date as latest_date,
                a.attendance_status as latest_status,
                t.time_in,
                t.time_out
            FROM employees e
            JOIN team_members tm ON e.employee_id = tm.employee_id
            JOIN team_cluster tc ON tm.team_id = tc.team_id
            -- Join with Attendance to get the latest record
            LEFT JOIN attendance a ON e.employee_id = a.employee_id 
                AND a.attendance_date = (
                    SELECT MAX(attendance_date) 
                    FROM attendance 
                    WHERE employee_id = e.employee_id
                )
            -- Join with Time Logs to get specific times for that date
            LEFT JOIN time_logs t ON e.employee_id = t.employee_id 
                AND t.log_date = a.attendance_date
            WHERE tc.coach_id = ?
            ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coach_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format times for display
    foreach ($data as &$row) {
        $row['time_in'] = ($row['time_in'] && $row['time_in'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['time_in'])) : '-';
        $row['time_out'] = ($row['time_out'] && $row['time_out'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['time_out'])) : '-';
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>