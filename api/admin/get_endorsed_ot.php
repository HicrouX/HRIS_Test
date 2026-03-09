<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);

try {
    $sql = "SELECT 
                o.*, e.first_name, e.last_name,
                CONCAT(coach.first_name, ' ', coach.last_name) as endorser_name
            FROM overtime_requests o
            JOIN employees e ON o.employee_id = e.employee_id
            LEFT JOIN cluster_members cm ON e.employee_id = cm.employee_id
            LEFT JOIN clusters c ON cm.cluster_id = c.cluster_id
            LEFT JOIN users u_coach ON c.user_id = u_coach.user_id
            LEFT JOIN employees coach ON u_coach.user_id = coach.user_id
            WHERE o.status = 'Endorsed' 
            ORDER BY o.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>