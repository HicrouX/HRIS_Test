<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

try {
    // FIX: Added "WHERE status = 'Endorsed'" so Approved items disappear
    $sql = "SELECT l.*, e.first_name, e.last_name 
            FROM leave_requests l
            JOIN employees e ON l.employee_id = e.employee_id
            WHERE l.status = 'Endorsed' 
            ORDER BY l.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>