<?php
// api/users/get_dispute_context.php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Verify user is logged in
verifyAccess([1, 2, 3, 4]);

$employee_id = $_SESSION['employee_id'];

try {
    // 1. Find the cluster the employee belongs to (via cluster_members)
    // 2. Find the Coach (the user_id who created/owns that cluster)
    // 3. Join with employees table to get the Coach's actual name
    $sql = "SELECT 
                c.cluster_id,
                c.name AS cluster_name,
                coach_emp.first_name AS coach_first,
                coach_emp.last_name AS coach_last
            FROM cluster_members cm
            JOIN clusters c ON cm.cluster_id = c.cluster_id
            JOIN employees coach_emp ON c.user_id = coach_emp.user_id
            WHERE cm.employee_id = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            "cluster_id" => $result['cluster_id'],
            "cluster_name" => $result['cluster_name'],
            "coach_name" => $result['coach_first'] . " " . $result['coach_last']
        ]);
    } else {
        echo json_encode([
            "cluster_name" => "Unassigned",
            "coach_name" => "None"
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>