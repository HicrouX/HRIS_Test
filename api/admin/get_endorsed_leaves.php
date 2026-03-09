<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([1, 2]);

try {
    // Fetches leaves that are 'Endorsed' (Reviewed by a Coach)
    // Joins with employees to get applicant name
    // Joins with employees again (via users) to get the endorser's name
    $sql = "SELECT 
                lr.*, 
                e.first_name, 
                e.last_name,
                CONCAT(endorser_e.first_name, ' ', endorser_e.last_name) AS endorser_name
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            LEFT JOIN users endorser_u ON lr.reviewed_by = endorser_u.user_id
            LEFT JOIN employees endorser_e ON endorser_u.user_id = endorser_e.user_id
            WHERE lr.status = 'Endorsed'
            ORDER BY lr.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>