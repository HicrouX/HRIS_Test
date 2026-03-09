<?php
// api/management/get_member_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3, 4]);

$employee_id = $_GET['employee_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (!$employee_id) {
    echo json_encode(["error" => "Employee ID is required."]);
    exit;
}

try {
    // Query based on your SQL schema: attendance table contains time_in/time_out
    // Sub-queries calculate break data from the break_logs table
    $sql = "SELECT 
                a.attendance_id,
                a.attendance_date, 
                a.attendance_status,
                t.time_in, 
                t.time_out,
                (SELECT MIN(bl.break_start) FROM break_logs bl JOIN time_logs tl ON bl.time_log_id = tl.time_log_id WHERE tl.attendance_id = a.attendance_id) as break_in,
                (SELECT MAX(bl.break_end) FROM break_logs bl JOIN time_logs tl ON bl.time_log_id = tl.time_log_id WHERE tl.attendance_id = a.attendance_id) as break_out,
                (SELECT SUM(bl.total_break_hour) FROM break_logs bl JOIN time_logs tl ON bl.time_log_id = tl.time_log_id WHERE tl.attendance_id = a.attendance_id) as total_break_hours
            FROM attendance_logs a
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            WHERE a.employee_id = :eid 
            AND a.attendance_date BETWEEN :start AND :end
            ORDER BY a.attendance_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':eid' => $employee_id, ':start' => $start_date, ':end' => $end_date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed = [];
    foreach($rows as $row) {
        $hours_worked = 0;
        $lunch_deduction = 0;
        
        // Format times for readability
        $in_fmt = ($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '-';
        $out_fmt = ($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '-';

        if ($row['time_in'] && $row['time_out']) {
            $start = new DateTime($row['time_in']);
            $end = new DateTime($row['time_out']);
            $diff = $start->diff($end);
            
            // Convert difference to decimal hours
            $raw_hours = ($diff->h * 60 + $diff->i) / 60;

            // Business Logic: If working > 5 hours, subtract 1 hour for lunch if no break logs exist
            if ($raw_hours > 5 && empty($row['total_break_hours'])) {
                $lunch_deduction = 1.00;
                $raw_hours -= 1;
            } elseif (!empty($row['total_break_hours'])) {
                $raw_hours -= $row['total_break_hours'];
            }

            $hours_worked = number_format(max(0, $raw_hours), 2);
        }

        $processed[] = [
            'date' => $row['attendance_date'],
            'time_in' => $in_fmt,
            'time_out' => $out_fmt,
            'break_in' => ($row['break_in']) ? date('h:i A', strtotime($row['break_in'])) : '-',
            'break_out' => ($row['break_out']) ? date('h:i A', strtotime($row['break_out'])) : '-',
            'status' => $row['attendance_status'],
            'total_hours' => $hours_worked,
            'lunch_break' => number_format($lunch_deduction, 2),
            'lunch_deduction' => number_format($lunch_deduction, 2)
        ];
    }
    echo json_encode($processed);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>