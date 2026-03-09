<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]); 
$acting_user_id = $_SESSION['user_id']; 
$acting_emp_id = $_SESSION['employee_id'];
$acting_role_id = $_SESSION['role_id'];

try {
    // Base SQL
    $sql = "SELECT d.*, e.first_name, e.last_name, r.role_name
            FROM attendance_disputes d
            JOIN employees e ON d.employee_id = e.employee_id
            JOIN users u ON e.user_id = u.user_id
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN clusters c ON d.cluster_id = c.cluster_id
            WHERE d.status = 'Pending' 
            AND d.employee_id != ?";

    // Visibility Logic
    if ($acting_role_id == 3) {
        // Coach: Only see disputes where they are the cluster owner AND requester is NOT a coach
        $sql .= " AND c.user_id = ? AND u.role_id != 3";
        $params = [$acting_emp_id, $acting_user_id];
    } else {
        // Admin/Super Admin: See all
        $params = [$acting_emp_id];
    }

    $sql .= " ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}