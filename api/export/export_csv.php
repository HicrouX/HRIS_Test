<?php
// api/export/export_csv.php

// 1. Safe Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔴 FIX: Correct Relative Paths based on your folder structure
// Go up one level (..) from 'export' to 'api', then into 'config'
require_once '../config/db.php'; 
require_once '../middleware/auth.php'; 

// 2. Security Check
if (!isset($_SESSION['role_id'])) {
    die("Access Denied");
}

$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['employee_id'];

// 3. Get Parameters
$mode = $_GET['mode'] ?? 'MY'; // MY, TEAM, ALL
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

// 4. Prepare Filename & Headers
$filename = "attendance_export_{$mode}_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// CSV Column Headers
fputcsv($output, ['Employee ID', 'Name', 'Role', 'Date', 'Time In', 'Time Out', 'Total Hours', 'Status']);

try {
    $sql = "";
    $params = [];

    // 5. Build Query Based on Mode
    if ($mode === 'ALL' && ($role_id == 3 || $role_id == 4)) {
        // --- MASTER EXPORT (Admins Only) ---
        $sql = "SELECT 
                    e.employee_id, 
                    CONCAT(e.first_name, ' ', e.last_name) as full_name,
                    r.role_name,
                    a.attendance_date,
                    t.time_in,
                    t.time_out,
                    ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours,
                    a.attendance_status
                FROM attendance a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN users u ON e.employee_id = u.employee_id
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC, e.last_name ASC";
        $params = [$start, $end];

    } elseif ($mode === 'TEAM' && ($role_id == 2 || $role_id == 3 || $role_id == 4)) {
        // --- TEAM EXPORT (Coaches & Admins) ---
        $target_coach = $_GET['coach_id'] ?? $user_id;

        $sql = "SELECT 
                    e.employee_id, 
                    CONCAT(e.first_name, ' ', e.last_name) as full_name,
                    'Team Member' as role_name,
                    a.attendance_date,
                    t.time_in,
                    t.time_out,
                    ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours,
                    a.attendance_status
                FROM attendance a
                JOIN employees e ON a.employee_id = e.employee_id
                JOIN team_members tm ON e.employee_id = tm.employee_id
                JOIN team_cluster tc ON tm.team_id = tc.team_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE tc.coach_id = ? AND a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC";
        $params = [$target_coach, $start, $end];

    } else {
        // --- MY ATTENDANCE (Everyone) ---
        $sql = "SELECT 
                    e.employee_id, 
                    CONCAT(e.first_name, ' ', e.last_name) as full_name,
                    'Self' as role_name,
                    a.attendance_date,
                    t.time_in,
                    t.time_out,
                    ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours,
                    a.attendance_status
                FROM attendance a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE a.employee_id = ? AND a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC";
        $params = [$user_id, $start, $end];
    }

    // 6. Execute and Output
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['time_in'] = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '--:--';
        $row['time_out'] = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--:--';
        fputcsv($output, $row);
    }

} catch (Exception $e) {
    fputcsv($output, ['Error', $e->getMessage()]);
}

fclose($output);
exit;
?>