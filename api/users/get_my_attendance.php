<?php
// api/users/get_my_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

$user_id = $_GET['employee_id'] ?? null;
$start = $_GET['start_date'] ?? date('Y-m-01');
$end = $_GET['end_date'] ?? date('Y-m-t');

if (!$user_id) { echo json_encode([]); exit; }

try {
    $sql = "SELECT 
                a.attendance_id, a.attendance_date, a.attendance_status as status,
                t.time_in, t.time_out,
                (SELECT MIN(bl.break_start) FROM break_logs bl JOIN time_logs tl ON bl.time_log_id = tl.time_log_id WHERE tl.attendance_id = a.attendance_id) as break_in,
                (SELECT MAX(bl.break_end) FROM break_logs bl JOIN time_logs tl ON bl.time_log_id = tl.time_log_id WHERE tl.attendance_id = a.attendance_id) as break_out,
                (SELECT SUM(bl.total_break_hour) FROM break_logs bl JOIN time_logs tl ON bl.time_log_id = tl.time_log_id WHERE tl.attendance_id = a.attendance_id) as total_break_hours
            FROM attendance_logs a
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            WHERE a.employee_id = ? AND a.attendance_date BETWEEN ? AND ?
            ORDER BY a.attendance_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $start, $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($rows as $r) {
        $hrs = 0;
        if ($r['time_in'] && $r['time_out']) {
            $diff = (strtotime($r['time_out']) - strtotime($r['time_in'])) / 3600;
            $lunch = ($diff > 5) ? 1 : 0;
            $hrs = $diff - $lunch - ($r['total_break_hours'] ?? 0);
        }
        $formatted[] = [
            "date" => $r['attendance_date'],
            "status" => $r['status'],
            "time_in" => $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '--',
            "time_out" => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '--',
            "break_in" => $r['break_in'] ? date('h:i A', strtotime($r['break_in'])) : '--',
            "break_out" => $r['break_out'] ? date('h:i A', strtotime($r['break_out'])) : '--',
            "total_hours" => number_format(max(0, $hrs), 2)
        ];
    }
    echo json_encode($formatted);
} catch (Exception $e) { echo json_encode(["error" => $e->getMessage()]); }
?>