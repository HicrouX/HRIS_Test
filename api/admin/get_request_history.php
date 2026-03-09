<?php
// api/admin/get_request_history.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../middleware/auth.php';

// Restricted to Super Admin (1) and Admin (2)
verifyAccess([1, 2]);

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-t');
$status     = isset($_GET['status'])     ? $_GET['status']     : 'ALL';
$category   = isset($_GET['category'])   ? $_GET['category']   : 'ALL';

try {
    $results = [];

    // 1. LEAVE QUERY
    if ($category === 'ALL' || $category === 'Leave') {
        $sql = "SELECT 
                    l.leave_id as id,
                    'Leave' as category,
                    l.leave_type as type,
                    l.start_date as date_start,
                    l.end_date as date_end,
                    l.reason as details,
                    l.status,
                    l.created_at,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name
                FROM leave_requests l
                JOIN employees e ON l.employee_id = e.employee_id
                WHERE (l.start_date BETWEEN ? AND ?)";
        
        $params = [$start_date, $end_date];
        if ($status !== 'ALL') { $sql .= " AND l.status = ?"; $params[] = $status; }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 2. OVERTIME QUERY
    if ($category === 'ALL' || $category === 'Overtime') {
        $sql = "SELECT 
                    o.ot_id as id,
                    'Overtime' as category,
                    o.ot_type as type,
                    o.start_time as date_start,
                    o.end_time as date_end,
                    o.purpose as details,
                    o.status,
                    o.created_at,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name
                FROM overtime_requests o
                JOIN employees e ON o.employee_id = e.employee_id
                WHERE (DATE(o.start_time) BETWEEN ? AND ?)";

        $params = [$start_date, $end_date];
        if ($status !== 'ALL') { $sql .= " AND o.status = ?"; $params[] = $status; }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 3. DISPUTE QUERY
    if ($category === 'ALL' || $category === 'Dispute') {
        $sql = "SELECT 
                    d.dispute_id as id,
                    'Dispute' as category,
                    d.dispute_type as type,
                    d.dispute_date as date_start,
                    d.dispute_date as date_end,
                    d.reason as details,
                    d.status,
                    d.created_at,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name
                FROM attendance_disputes d
                JOIN employees e ON d.employee_id = e.employee_id
                WHERE (d.dispute_date BETWEEN ? AND ?)";

        $params = [$start_date, $end_date];
        if ($status !== 'ALL') { $sql .= " AND d.status = ?"; $params[] = $status; }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Sort combined results by created_at DESC
    usort($results, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "History Retrieval Failed: " . $e->getMessage()]);
}
?>