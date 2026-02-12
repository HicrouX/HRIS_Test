<?php
// api/management/get_team_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

$coach_id = isset($_GET['coach_id']) ? $_GET['coach_id'] : 0;

try {
    // 🚀 TEAM ROSTER: Select Unique Employees in the Team
    $sql = "SELECT 
                e.employee_id, 
                e.first_name, 
                e.last_name,
                e.position,
                -- Latest Date
                (SELECT attendance_date 
                 FROM attendance 
                 WHERE employee_id = e.employee_id 
                 ORDER BY attendance_date DESC LIMIT 1) as latest_date,
                -- Latest Status
                (SELECT attendance_status 
                 FROM attendance 
                 WHERE employee_id = e.employee_id 
                 ORDER BY attendance_date DESC LIMIT 1) as latest_status
            FROM employees e
            JOIN team_members tm ON e.employee_id = tm.employee_id
            JOIN team_cluster tc ON tm.team_id = tc.team_id
            WHERE tc.coach_id = ?
            ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coach_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>