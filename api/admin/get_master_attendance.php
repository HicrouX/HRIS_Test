<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1]);

$start = $_GET['start_date'] ?? date('Y-m-d');
$end   = $_GET['end_date'] ?? date('Y-m-d');
$role  = $_GET['role'] ?? '';

try {
    $sql = "SELECT 
                e.employee_id, e.first_name, e.last_name, r.role_name,
                a.attendance_id, a.attendance_date, a.attendance_status, t.time_in, t.time_out,
                ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours
            FROM employees e
            JOIN users u ON e.user_id = u.user_id
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN attendance_logs a ON e.employee_id = a.employee_id 
                 AND a.attendance_date BETWEEN ? AND ?
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            WHERE u.role_id != 1";

    $params = [$start, $end];
    if (!empty($role)) {
        $sql .= " AND u.role_id = ?";
        $params[] = $role;
    }

    $sql .= " ORDER BY e.last_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>