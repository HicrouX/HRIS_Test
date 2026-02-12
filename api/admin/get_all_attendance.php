<?php
// api/admin/get_all_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);

try {
    // 🚀 ROSTER MODE: Select Unique Admins & Coaches
    // This query finds the person first, then looks up their LAST attendance record
    $sql = "SELECT 
                e.employee_id, 
                e.first_name, 
                e.last_name,
                u.role_id,
                -- Get the Date of the most recent log (Any year)
                (SELECT attendance_date 
                 FROM attendance 
                 WHERE employee_id = e.employee_id 
                 ORDER BY attendance_date DESC LIMIT 1) as latest_date,
                -- Get the Status of the most recent log
                (SELECT attendance_status 
                 FROM attendance 
                 WHERE employee_id = e.employee_id 
                 ORDER BY attendance_date DESC LIMIT 1) as latest_status,
                -- Get the ID (for the Edit pencil)
                (SELECT attendance_id 
                 FROM attendance 
                 WHERE employee_id = e.employee_id 
                 ORDER BY attendance_date DESC LIMIT 1) as latest_id
            FROM employees e
            JOIN users u ON e.employee_id = u.employee_id
            WHERE u.role_id IN (2, 3, 4) -- 2=Coach, 3=Admin, 4=Super Admin
            ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode([]);
}
?>