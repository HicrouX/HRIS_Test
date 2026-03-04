<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([2, 3, 4]);
$acting_user_id = $_SESSION['user_id']; // The ID from users table acting as coach

try {
    $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.position, e.employment_status 
            FROM employees e
            JOIN cluster_members cm ON e.employee_id = cm.employee_id
            JOIN clusters c ON cm.cluster_id = c.cluster_id
            WHERE c.user_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$acting_user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>