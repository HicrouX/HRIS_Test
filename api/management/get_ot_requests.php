<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Accept Coach Name or User Role to filter
$role_id = $_GET['role_id']; 
$coach_name = $_GET['coach_name'] ?? ''; // Passed from frontend for Coaches

if ($role_id == 2) { 
    // COACH VIEW: Show 'Pending' requests specifically for this Coach
    // Note: Matches the text "Charina Vargas" etc from your form
    $sql = "SELECT * FROM overtime_requests 
            WHERE status = 'Pending' AND coach_name = ? 
            ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coach_name]);

} elseif ($role_id == 3 || $role_id == 4) {
    // ADMIN VIEW: Show 'Endorsed' requests ready for final approval
    $sql = "SELECT * FROM overtime_requests 
            WHERE status = 'Endorsed' 
            ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} else {
    echo json_encode([]);
    exit;
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>