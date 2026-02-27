<?php
// FILE: api/users/get_my_requests.php
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

if (!isset($_GET['employee_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Employee ID is required"]);
    exit;
}

$emp_id = $_GET['employee_id'];

try {
    // 1. Fetch Leaves
    $stmt1 = $pdo->prepare("SELECT leave_type, start_date, end_date, reason, status, created_at 
                            FROM leave_requests 
                            WHERE employee_id = ? 
                            ORDER BY created_at DESC");
    $stmt1->execute([$emp_id]);
    $leaves = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Overtime
    $stmt2 = $pdo->prepare("SELECT ot_type, start_time, end_time, purpose, status, created_at 
                            FROM overtime_requests 
                            WHERE employee_id = ? 
                            ORDER BY created_at DESC");
    $stmt2->execute([$emp_id]);
    $ot = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Disputes (Optional, but good for completeness if you use this endpoint later)
    $stmt3 = $pdo->prepare("SELECT dispute_type, dispute_date, reason, status, remarks, created_at 
                            FROM attendance_disputes 
                            WHERE employee_id = ? 
                            ORDER BY created_at DESC");
    $stmt3->execute([$emp_id]);
    $disputes = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "leaves" => $leaves, 
        "overtime" => $ot,
        "disputes" => $disputes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>