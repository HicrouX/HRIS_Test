<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]); 
$current_admin_emp_id = $_SESSION['employee_id'];

try {
    $sql = "SELECT d.*, e.first_name, e.last_name, r.role_name
            FROM attendance_disputes d
            JOIN employees e ON d.employee_id = e.employee_id
            JOIN users u ON e.user_id = u.user_id
            JOIN roles r ON u.role_id = r.role_id
            WHERE d.status IN ('Pending', 'Endorsed')
            AND d.employee_id != ? 
            ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_admin_emp_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>