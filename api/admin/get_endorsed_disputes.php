<?php
// api/admin/get_endorsed_disputes.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_admin_id = $_SESSION['employee_id'] ?? 0;

verifyAccess([3, 4]);

try {
    $sql = "SELECT 
                d.dispute_id,
                d.dispute_date,
                d.dispute_type,
                d.reason,
                d.status,
                e.first_name, 
                e.last_name,
                e.employee_id,
                r.role_name
            FROM attendance_disputes d
            JOIN employees e ON d.employee_id = e.employee_id
            JOIN users u ON e.employee_id = u.employee_id
            JOIN roles r ON u.role_id = r.role_id
            WHERE 
            (
                (d.status = 'Endorsed') -- Normal Employee Disputes (via Coach)
                OR 
                (d.status = 'Pending' AND u.role_id IN (2, 3, 4)) -- Direct Disputes from Coach/Admin
            )
            AND d.employee_id != ? -- 🛑 SECURITY: Admin cannot approve own dispute
            ORDER BY d.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_admin_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode([]);
}
?>