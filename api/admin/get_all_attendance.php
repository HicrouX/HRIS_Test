<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);

try {
    $sql = "SELECT 
                e.employee_id, e.first_name, e.last_name, u.role_id, u.user_id,
                a.attendance_date as latest_date,
                a.attendance_status as latest_status,
                a.attendance_id as latest_id
            FROM employees e
            JOIN users u ON e.user_id = u.user_id
            LEFT JOIN attendance_logs a ON e.employee_id = a.employee_id 
                AND a.attendance_id = (SELECT MAX(attendance_id) FROM attendance_logs WHERE employee_id = e.employee_id)
            WHERE u.role_id IN (2, 3)
            ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>