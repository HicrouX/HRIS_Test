<?php
require_once '../config/db.php';
$employee_id = $_GET['employee_id'];
$stmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs WHERE employee_id = ? ORDER BY log_date DESC");
$stmt->execute([$employee_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>