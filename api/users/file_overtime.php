<?php
// api/users/file_overtime.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->employee_id) || empty($data->start_time) || empty($data->end_time) || empty($data->purpose)) {
    http_response_code(400);
    echo json_encode(["error" => "Please fill in all fields."]);
    exit;
}

try {
    $start = new DateTime($data->start_time);
    $end = new DateTime($data->end_time);
    
    // 1. Basic Validation
    if ($end <= $start) {
        echo json_encode(["error" => "End time must be after start time."]);
        exit;
    }

    // 2. Calculate Duration of THIS Request (in minutes)
    $diff = $start->diff($end);
    $new_minutes = ($diff->h * 60) + $diff->i;

    // 3. Check Daily Limit (Sum of existing requests + new request)
    $date_str = $start->format('Y-m-d');
    
    $check_sql = "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_existing_mins 
                  FROM overtime_requests 
                  WHERE employee_id = ? 
                  AND DATE(start_time) = ? 
                  AND status != 'Denied'"; // Ignore denied requests
                  
    $stmt_check = $pdo->prepare($check_sql);
    $stmt_check->execute([$data->employee_id, $date_str]);
    $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    $existing_minutes = $row['total_existing_mins'] ? (int)$row['total_existing_mins'] : 0;
    $total_minutes = $existing_minutes + $new_minutes;

    // 120 minutes = 2 Hours
    if ($total_minutes > 120) {
        $remaining = 120 - $existing_minutes;
        if ($remaining < 0) $remaining = 0;
        echo json_encode(["error" => "Overtime limit exceeded. You have used $existing_minutes mins today. You can only file $remaining more minutes. Max is 2 hours/day."]);
        exit;
    }

    // 4. Insert Request
    $sql = "INSERT INTO overtime_requests (employee_id, ot_type, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data->employee_id,
        $data->ot_type,
        $data->start_time,
        $data->end_time,
        $data->purpose
    ]);

    echo json_encode(["success" => "Overtime request submitted successfully."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>