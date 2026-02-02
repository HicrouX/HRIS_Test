<?php
// api/admin/get_all_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);

$start = !empty($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end   = !empty($_GET['end'])   ? $_GET['end']   : date('Y-m-t');

$filter_mode = $_GET['filter_mode'] ?? 'COACHES'; 
$target_id   = $_GET['coach_id'] ?? null;

try {
    // 1. Base Query
    $sql = "SELECT 
                a.attendance_id, 
                a.attendance_date, 
                a.attendance_status, 
                e.first_name, 
                e.last_name,
                e.employee_id,
                u.role_id
            FROM attendance a
            JOIN employees e ON a.employee_id = e.employee_id
            LEFT JOIN users u ON e.employee_id = u.employee_id "; 

    // 2. Filter Logic
    if ($filter_mode === 'COACHES') {
        // UPDATE: Now shows Coaches (2), Admins (3), and Super Admins (4)
        $sql .= " WHERE u.role_id IN (2, 3, 4) AND a.attendance_date BETWEEN :start AND :end";
    } 
    elseif ($filter_mode === 'TEAM' && $target_id) {
        // Show Team Members (Drill down)
        $sql .= " JOIN team_members tm ON e.employee_id = tm.employee_id
                  JOIN team_cluster tc ON tm.team_id = tc.team_id
                  WHERE tc.coach_id = :coach_id AND a.attendance_date BETWEEN :start AND :end";
    }
    else {
        // Fallback
        $sql .= " WHERE a.attendance_date BETWEEN :start AND :end";
    }

    $sql .= " ORDER BY a.attendance_date DESC, e.last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', $start);
    $stmt->bindValue(':end', $end);

    if ($filter_mode === 'TEAM' && $target_id) {
        $stmt->bindValue(':coach_id', $target_id);
    }

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode([]);
}
?>