<?php
// api/management/view_attendance_log.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Verify access
verifyAccess([1, 2, 3]);

$role_id = $_SESSION['role_id'];
$acting_user_id = $_SESSION['user_id']; // Using user_id for cluster ownership

try {
    // Base Query: Fetches attendance and dynamic schedule based on the day of the week
    $sql = "SELECT 
                a.attendance_id, 
                a.attendance_date, 
                a.attendance_status,
                t.time_in,
                t.time_out,
                e.first_name, 
                e.last_name, 
                e.position,
                s.start_time as schedule_start, 
                s.end_time as schedule_end
            FROM attendance_logs a
            JOIN employees e ON a.attendance_id = e.employee_id
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            LEFT JOIN schedules s ON e.employee_id = s.employee_id 
                 AND s.day_of_week = DAYNAME(a.attendance_date)";

    if ($role_id == 3) {
        // COACH VIEW: Filter by cluster managed by the logged-in user
        $sql .= " JOIN cluster_members cm ON e.employee_id = cm.employee_id
                  JOIN clusters c ON cm.cluster_id = c.cluster_id
                  WHERE c.user_id = ? 
                  ORDER BY a.attendance_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$acting_user_id]);
    } else {
        // ADMIN VIEW: See everything
        $sql .= " ORDER BY a.attendance_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format output times for cleaner frontend display
    foreach ($data as &$row) {
        $row['time_in'] = ($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '-';
        $row['time_out'] = ($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '-';
        $row['shift'] = ($row['schedule_start']) ? 
                        date('h:i A', strtotime($row['schedule_start'])) . " - " . date('h:i A', strtotime($row['schedule_end'])) : 
                        'No Schedule';
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>