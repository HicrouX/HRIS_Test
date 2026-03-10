<?php
// api/admin/get_endorsed_ot.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

try {
    // 🚀 UPDATED: Fetch OT Details + Coach Name
    $sql = "SELECT 
                o.ot_id,
                o.ot_type,
                o.start_time,
                o.end_time,
                o.purpose,
                o.created_at,
                e.first_name, 
                e.last_name,
                -- Find the Coach based on the Employee's Team
                CONCAT(coach.first_name, ' ', coach.last_name) as endorser_name
            FROM overtime_requests o
            JOIN employees e ON o.employee_id = e.employee_id
            -- Join Team structure to find the Coach
            LEFT JOIN team_members tm ON e.employee_id = tm.employee_id
            LEFT JOIN team_cluster tc ON tm.team_id = tc.team_id
            LEFT JOIN employees coach ON tc.coach_id = coach.employee_id
            WHERE o.status = 'Endorsed' 
            ORDER BY o.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>