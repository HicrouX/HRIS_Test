<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]);
$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

// NEW: Allow passing a specific coach_id (which is a user_id)
$target_coach_id = isset($_GET['coach_id']) ? $_GET['coach_id'] : $acting_user_id;

// If a coach_id was passed, we need to handle if it's an employee_id or user_id.
// Based on the dashboard code, it's often passed as the employee_id from get_all_attendance.
// However, the hierarchy query now returns user_id.
// Let's check if we need to resolve it.

try {
    // If the passed coach_id is an employee_id, we should find its user_id.
    // But for now, let's assume it might be either and handle it robustly.
    
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
            WHERE (c.user_id = ? OR c.cluster_id = (SELECT cluster_id FROM cluster_members WHERE employee_id = ? LIMIT 1))
            AND (u.role_id != 3 OR ? != 3)
            ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$target_coach_id, $target_coach_id, $acting_role_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['time_in'] = ($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '-';
        $row['time_out'] = ($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '-';
    }
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}