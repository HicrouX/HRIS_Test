<?php
// api/admin/get_endorsed_ot.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Verify Admin (3) or Super Admin (4)
verifyAccess([3, 4]);

// Fetch 'Endorsed' requests
$sql = "SELECT 
            ot.ot_id, 
            ot.ot_type, 
            ot.start_time, 
            ot.end_time, 
            ot.purpose, 
            e.first_name, 
            e.last_name, 
            e.position
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.employee_id
        WHERE ot.status = 'Endorsed'
        ORDER BY ot.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>