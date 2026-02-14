<?php
// FILE: api/admin/get_master_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Only Super Admin (Role 4) should access this list
verifyAccess([4]);

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$role_id    = isset($_GET['role'])       ? $_GET['role']       : '';

try {
    /**
     * 🚀 ROSTER LOGIC:
     * 1. Select FROM 'employees' (Primary Table) -> Gets EVERYONE.
     * 2. LEFT JOIN 'attendance' via a subquery -> Gets their LATEST record in the date range.
     */
    $sql = "SELECT 
                e.employee_id, 
                e.first_name, 
                e.last_name, 
                r.role_name,
                
                -- Attendance Data (Will be NULL if no record found)
                a.attendance_id,
                a.attendance_date,
                a.attendance_status,
                t.time_in,
                t.time_out,
                -- Calculate Work Hours
                ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours

            FROM employees e
            JOIN users u ON e.employee_id = u.employee_id
            JOIN roles r ON u.role_id = r.role_id
            
            -- Subquery: Find the SINGLE LATEST attendance_id for this person in the selected range
            LEFT JOIN (
                SELECT employee_id, MAX(attendance_id) as max_id
                FROM attendance
                WHERE attendance_date BETWEEN ? AND ?
                GROUP BY employee_id
            ) latest_ref ON e.employee_id = latest_ref.employee_id
            
            -- Join actual data using that specific ID
            LEFT JOIN attendance a ON latest_ref.max_id = a.attendance_id
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            
            WHERE 1=1";

    $params = [$start_date, $end_date];

    // Apply Role Filter to the Employee list
    if (!empty($role_id)) {
        $sql .= " AND u.role_id = ?";
        $params[] = $role_id;
    }

    $sql .= " ORDER BY e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>