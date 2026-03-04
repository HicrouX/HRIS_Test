<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2, 3, 4]);
$acting_user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT ot.*, e.first_name, e.last_name, e.position
            FROM overtime_requests ot
            JOIN employees e ON ot.employee_id = e.employee_id
            JOIN cluster_members cm ON e.employee_id = cm.employee_id
            JOIN clusters c ON cm.cluster_id = c.cluster_id
            WHERE ot.status = 'Pending' 
            AND c.user_id = ?
            ORDER BY ot.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$acting_user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}