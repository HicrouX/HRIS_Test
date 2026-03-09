<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2, 3]);
$acting_user_id = $_SESSION['user_id'];
$acting_role_id = $_SESSION['role_id'];

try {
    // We join with users table to check the role of the requester
    $sql = "SELECT lr.*, e.first_name, e.last_name, u.role_id as requester_role
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            JOIN users u ON e.user_id = u.user_id
            JOIN cluster_members cm ON e.employee_id = cm.employee_id
            JOIN clusters c ON cm.cluster_id = c.cluster_id
            WHERE lr.status = 'Pending' 
            AND c.user_id = ?
            AND (u.role_id != 3 OR ? != 3)
            ORDER BY lr.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$acting_user_id, $acting_role_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}