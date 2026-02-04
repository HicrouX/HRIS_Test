<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

try {
    // FIX: Added "WHERE status = 'Endorsed'"
    $sql = "SELECT o.*, e.first_name, e.last_name 
            FROM overtime_requests o
            JOIN employees e ON o.employee_id = e.employee_id
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