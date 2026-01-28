<?php
require_once '../config/db.php';
$coach_id = $_GET['coach_id'];
$sql = "SELECT a.*, e.first_name, e.last_name 
        FROM attendance a
        JOIN employees e ON a.employee_id = e.employee_id
        JOIN team_members tm ON e.employee_id = tm.employee_id
        JOIN team_cluster tc ON tm.team_id = tc.team_id
        WHERE tc.coach_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$coach_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>