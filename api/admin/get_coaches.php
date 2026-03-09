<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);

try {
    $sql = "SELECT e.employee_id, e.first_name, e.last_name
            FROM employees e
            JOIN users u ON e.user_id = u.user_id
            WHERE u.role_id = 3
            ORDER BY e.first_name ASC";            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>