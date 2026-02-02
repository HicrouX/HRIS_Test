<?php
// api/admin/get_endorsed_ot.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

verifyAccess([3, 4]);

try {
    // UPDATED SQL: Includes Pending OT from Admins/Super Admins
    $sql = "SELECT 
                ot.ot_id, 
                ot.ot_type, 
                ot.start_time, 
                ot.end_time, 
                ot.purpose, 
                ot.status,
                e.first_name, 
                e.last_name, 
                e.position
            FROM overtime_requests ot
            JOIN employees e ON ot.employee_id = e.employee_id
            LEFT JOIN users u_app ON e.employee_id = u_app.employee_id
            WHERE 
                ot.status IN ('Endorsed', 'Approved', 'Denied')
                OR
                (ot.status = 'Pending' AND u_app.role_id IN (3, 4))
            ORDER BY ot.start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>