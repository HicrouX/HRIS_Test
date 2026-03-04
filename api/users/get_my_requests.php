<?php
// api/users/get_my_requests.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

$id = $_GET['employee_id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(["error" => "ID required"]); exit; }

try {
    $leaves = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
    $leaves->execute([$id]);
    
    $ot = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? ORDER BY created_at DESC");
    $ot->execute([$id]);

    echo json_encode(["leaves" => $leaves->fetchAll(PDO::FETCH_ASSOC), "overtime" => $ot->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) { echo json_encode(["error" => $e->getMessage()]); }
?>