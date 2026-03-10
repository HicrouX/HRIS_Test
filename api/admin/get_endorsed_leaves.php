<?php
// api/admin/get_endorsed_leaves.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

try {
    // 🚀 UPDATED: Fetch Leave Details + Endorser Name
    $sql = "SELECT 
                l.leave_id,
                l.leave_type,
                l.start_date,
                l.end_date,
                l.reason,
                l.created_at,
                e.first_name, 
                e.last_name,
                -- Get the name of the Coach who endorsed it (reviewed_by)
                CONCAT(c_emp.first_name, ' ', c_emp.last_name) as endorser_name
            FROM leave_requests l
            JOIN employees e ON l.employee_id = e.employee_id
            -- Link 'reviewed_by' (User ID) -> Users Table -> Employees Table (Coach)
            LEFT JOIN users u ON l.reviewed_by = u.user_id
            LEFT JOIN employees c_emp ON u.employee_id = c_emp.employee_id
            WHERE l.status = 'Endorsed' 
            ORDER BY l.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>