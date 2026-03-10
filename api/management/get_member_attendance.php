<?php
// api/users/get_my_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3, 4]);

$employee_id = $_GET['employee_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (!$employee_id) { echo json_encode([]); exit; }

try {
    $sql = "SELECT 
                a.attendance_date, 
                a.attendance_status,
                t.time_log_id,
                t.time_in, 
                t.time_out,
                (SELECT MIN(break_start) FROM break_logs WHERE time_log_id = t.time_log_id) as break_in,
                (SELECT MAX(break_end) FROM break_logs WHERE time_log_id = t.time_log_id) as break_out,
                (SELECT SUM(total_break_minutes) FROM break_logs WHERE time_log_id = t.time_log_id) as total_break_minutes
            FROM attendance a
            LEFT JOIN time_logs t ON a.employee_id = t.employee_id AND a.attendance_date = DATE(t.log_date)
            WHERE a.employee_id = :eid 
            AND a.attendance_date BETWEEN :start AND :end
            ORDER BY a.attendance_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':eid' => $employee_id, ':start' => $start_date, ':end' => $end_date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed = [];
    foreach($data as $row) {
        $hours_worked = 0;
        $lunch_break = 0;
        
        $in_display = ($row['time_in'] && $row['time_in'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['time_in'])) : '-';
        $out_display = ($row['time_out'] && $row['time_out'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['time_out'])) : '-';
        $b_in_display = ($row['break_in']) ? date('h:i A', strtotime($row['break_in'])) : '-';
        $b_out_display = ($row['break_out']) ? date('h:i A', strtotime($row['break_out'])) : '-';

        if ($in_display != '-') {
            if ($out_display != '-') {
                $in = new DateTime($row['time_in']);
                $out = new DateTime($row['time_out']);
                $diff = $in->diff($out);
                $raw_hours = ($diff->h * 60 + $diff->i) / 60;

                if ($raw_hours > 5) { $lunch_break = 1.00; $raw_hours -= 1; }
                if (!empty($row['total_break_minutes'])) { $raw_hours -= ($row['total_break_minutes'] / 60); }

                $hours_worked = number_format(max(0, $raw_hours), 2);
            }
        }

        $processed[] = [
            'date' => $row['attendance_date'],
            'time_in' => $in_display,
            'time_out' => $out_display,
            'break_in' => $b_in_display,
            'break_out' => $b_out_display,
            'lunch_break' => number_format($lunch_break, 2),
            'status' => $row['attendance_status'],
            'total_hours' => $hours_worked
        ];
    }
    echo json_encode($processed);
} catch (Exception $e) { echo json_encode([]); }
?>