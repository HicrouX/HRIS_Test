<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]);
$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

try {
    // Join with users table to check role_id of the employee
    $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.position,
                   a.attendance_date, a.attendance_status, t.time_in, t.time_out,
                   u.role_id as member_role
            FROM employees e
            JOIN users u ON e.user_id = u.user_id
            JOIN cluster_members cm ON e.employee_id = cm.employee_id
            JOIN clusters c ON cm.cluster_id = c.cluster_id
            LEFT JOIN attendance_logs a ON e.employee_id = a.employee_id 
                 AND a.attendance_date = CURDATE()
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            WHERE c.user_id = ?
            AND (u.role_id != 3 OR ? != 3)
            ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$acting_user_id, $acting_role_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['time_in'] = ($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '-';
        $row['time_out'] = ($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '-';
    }
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}