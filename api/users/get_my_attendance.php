<?php
require_once '../config/db.php';
$employee_id = $_GET['employee_id']; // Passed from React session
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC");
$stmt->execute([$employee_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>