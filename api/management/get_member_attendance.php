<?php
// api/management/get_member_attendance.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

$emp_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : 0;

if ($emp_id == 0) {
    echo json_encode([]);
    exit;
}

try {
    // Fetch History
    $sql = "SELECT 
                a.attendance_date,
                a.attendance_status,
                t.time_log_id,
                t.time_in,
                t.time_out,
                (SELECT SUM(total_break_minutes) FROM break_logs WHERE time_log_id = t.time_log_id) as total_break_mins
            FROM attendance a
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
            WHERE a.employee_id = ?
            ORDER BY a.attendance_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$emp_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed = [];
    foreach($data as $row) {
        $hours_worked = '0.00';
        $overtime = '0.00';
        $in_display = '-';
        $out_display = '-';
        $lunch_break = '0.00';
        $break_time = '0.00'; 

        if (!empty($row['time_in']) && $row['time_in'] != '0000-00-00 00:00:00') {
            $in = new DateTime($row['time_in']);
            $in_display = $in->format('M d, h:i A');

            if (!empty($row['time_out']) && $row['time_out'] != '0000-00-00 00:00:00') {
                $out = new DateTime($row['time_out']);
                $out_display = $out->format('M d, h:i A');
                
                $diff = $in->diff($out);
                $raw_hours = $diff->h + ($diff->i / 60);

                if ($raw_hours > 5) {
                    $lunch_break = '1.00';
                    $raw_hours -= 1; 
                }

                if ($row['total_break_mins'] > 0) {
                    $break_hours = $row['total_break_mins'] / 60;
                    $break_time = number_format($break_hours, 2);
                    $raw_hours -= $break_hours;
                }

                if ($raw_hours > 8) {
                    $overtime = number_format($raw_hours - 8, 2);
                    $raw_hours = 8;
                }
                
                $hours_worked = number_format($raw_hours, 2);
            }
        }

        $processed[] = [
            'date' => $row['attendance_date'],
            'status' => $row['attendance_status'],
            'time_in' => $in_display,
            'time_out' => $out_display,
            'lunch_break' => $lunch_break,
            'break_time' => $break_time,
            'hours' => $hours_worked,
            'overtime' => $overtime
        ];
    }

    echo json_encode($processed);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>