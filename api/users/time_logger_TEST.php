<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents("php://input"));
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

try {
    $pdo->beginTransaction();

    // Fetch schedule for real-time comparison
    $sched = $pdo->prepare("SELECT schedule_start FROM schedules WHERE employee_id = ? AND work_day = DAYNAME(?)");
    $sched->execute([$data->employee_id, $current_date]);
    $row = $sched->fetch();

    // Determine status: Late vs Present
    $status = ($row && $current_time > $row['schedule_start']) ? 'Late' : 'Present';

    // 1. Create Attendance record
    $att = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, attendance_status) VALUES (?, ?, ?)");
    $att->execute([$data->employee_id, $current_date, $status]);
    $attendance_id = $pdo->lastInsertId();

    // 2. Log Time In
    $log = $pdo->prepare("INSERT INTO time_logs (employee_id, attendance_id, time_in, log_date) VALUES (?, ?, NOW(), ?)");
    $log->execute([$data->employee_id, $attendance_id, $current_date]);

    $pdo->commit();
    echo json_encode(["success" => "Absorbed into attendance module", "current_status" => $status]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => "Absorption failed."]);
}
?>