<?php
// api/users/get_my_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-USER-ROLE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Allow All Roles
verifyAccess([1, 2, 3, 4]);

$employee_id = $_GET['employee_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (!$employee_id) { echo json_encode([]); exit; }

try {
    // JOIN with time_logs to get Time In/Out
    $sql = "SELECT 
                a.attendance_date, 
                a.attendance_status,
                t.time_in, 
                t.time_out,
                -- Calculate Hours Difference
                ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours
            FROM attendance a
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            WHERE a.employee_id = :eid 
            AND a.attendance_date BETWEEN :start AND :end
            ORDER BY a.attendance_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':eid' => $employee_id,
        ':start' => $start_date,
        ':end' => $end_date
    ]);
    
    // Format times to look nice (e.g., "08:00 AM")
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as &$row) {
        $row['time_in'] = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '--:--';
        $row['time_out'] = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--:--';
    }

    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}
?>