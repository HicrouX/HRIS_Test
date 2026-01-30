<?php
// api/admin/get_all_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);

$start = !empty($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end   = !empty($_GET['end'])   ? $_GET['end']   : date('Y-m-t');

// Logic: Are we viewing "COACHES" only, or a specific "TEAM"?
$filter_mode = $_GET['filter_mode'] ?? 'COACHES'; 
$target_id   = $_GET['coach_id'] ?? null; // This is the ID of the selected coach

try {
    // Base Query: Get Attendance + Employee Info + Role
    $sql = "SELECT 
                a.attendance_id, 
                a.attendance_date, 
                a.attendance_status, 
                e.first_name, 
                e.last_name,
                u.role_id
            FROM attendance a
            JOIN employees e ON a.employee_id = e.employee_id
            LEFT JOIN users u ON e.employee_id = u.employee_id ";

    // --- MODE 1: Default View -> Show Coaches Only ---
    if ($filter_mode === 'COACHES') {
        // "I want Master Attendance shown first coaches"
        $sql .= " WHERE u.role_id = 2 AND a.attendance_date BETWEEN :start AND :end";
    } 
    // --- MODE 2: Team View -> Show Employees under the Selected Coach ---
    elseif ($filter_mode === 'TEAM' && $target_id) {
        // "Click one of the coach, dropdown and see the display of his team"
        // We join team_members -> team_cluster to find employees whose team is managed by this coach
        $sql .= " JOIN team_members tm ON e.employee_id = tm.employee_id
                  JOIN team_cluster tc ON tm.team_id = tc.team_id
                  WHERE tc.coach_id = :coach_id AND a.attendance_date BETWEEN :start AND :end";
    }
    // --- MODE 3: Fallback (Show All) ---
    else {
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