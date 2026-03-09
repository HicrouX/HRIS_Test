<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]);
$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

try {
    $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.position, e.employment_status 
            FROM employees e
            JOIN users u ON e.user_id = u.user_id
            JOIN cluster_members cm ON e.employee_id = cm.employee_id
            JOIN clusters c ON cm.cluster_id = c.cluster_id
            WHERE c.user_id = ?
            AND (u.role_id != 3 OR ? != 3)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$acting_user_id, $acting_role_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>