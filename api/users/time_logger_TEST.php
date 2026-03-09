<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([4]); // Ensure it is an Employee

$data = json_decode(file_get_contents("php://input"));
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

try {
    // FIX: CHECK FOR EXISTING LOG FIRST (Prevention)
    $check = $pdo->prepare("SELECT attendance_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?");
    $check->execute([$data->employee_id, $current_date]);
    
    if ($check->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "You have already clocked in for today."]);
        exit;
    }

    $pdo->beginTransaction();

    // Fetch schedule for 'Late' detection
    $sched = $pdo->prepare("SELECT start_time FROM schedules WHERE employee_id = ? AND day_of_week = DAYNAME(?)");
    $sched->execute([$data->employee_id, $current_date]);
    $row = $sched->fetch();

    $status = ($row && $current_time > $row['start_time']) ? 'Late' : 'Present';

    $att = $pdo->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, attendance_status) VALUES (?, ?, ?)");
    $att->execute([$data->employee_id, $current_date, $status]);
    $attendance_id = $pdo->lastInsertId();

    // In official_hris_db.sql, time_logs has user_id, which we get from session or employees table.
    // For this test script, we assume session is available or we use a subquery/lookup.
    $user_id = $_SESSION['user_id'] ?? 0; 

    $log = $pdo->prepare("INSERT INTO time_logs (employee_id, user_id, attendance_id, time_in, log_date) VALUES (?, ?, ?, NOW(), ?)");
    $log->execute([$data->employee_id, $user_id, $attendance_id, $current_date]);

    $pdo->commit();
    echo json_encode(["success" => "Clock-in successful.", "status" => $status]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "System error: " . $e->getMessage()]);
}
?>