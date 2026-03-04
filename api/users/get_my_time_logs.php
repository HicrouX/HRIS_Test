<?php
// api/users/get_my_time_logs.php
require_once '../config/db.php';
$id = $_GET['employee_id'];
// Pulling from time_logs table
$stmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs WHERE employee_id = ? ORDER BY log_date DESC");
$stmt->execute([$id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>