<?php
require_once '../config/db.php';
$coach_id = $_GET['coach_id'];
$sql = "SELECT e.employee_id, e.first_name, e.last_name, e.position, e.employment_status 
        FROM employees e
        JOIN team_members tm ON e.employee_id = tm.employee_id
        JOIN team_cluster tc ON tm.team_id = tc.team_id
        WHERE tc.coach_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$coach_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>