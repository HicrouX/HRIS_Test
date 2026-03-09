<?php
// api/export/export_excel.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config/db.php'; 
require_once '../middleware/auth.php'; 

// Security Check
if (!isset($_SESSION['role_id'])) { die("Access Denied"); }

$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id']; // The Coach's User ID
$employee_id = $_SESSION['employee_id']; // The User's Employee ID

// Get Parameters
$mode  = $_GET['mode'] ?? 'MY'; 
$start = $_GET['start'] ?? date('Y-01-01'); 
$end   = $_GET['end'] ?? date('Y-12-31');
$target_id = $_GET['employee_id'] ?? 0;

// Prepare Filename
$filename = "attendance_{$mode}_" . date('Ymd') . ".xls";

// Headers to force Excel Download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<table border="1">';
echo '<tr style="background-color:#CCC; font-weight:bold;">
        <th>Employee ID</th>
        <th>Name</th>
        <th>Date</th>
        <th>Time In</th>
        <th>Time Out</th>
        <th>Total Hours</th>
        <th>Status</th>
      </tr>';

try {
    $sql = "";
    $params = [];

    // --- 1. ADMIN EXPORT (ALL) ---
    if ($mode === 'ALL' && ($role_id == 1 || $role_id == 2)) {
        $sql = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as full_name, 
                       a.attendance_date, t.time_in, t.time_out, a.attendance_status,
                       ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours
                FROM attendance_logs a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC, e.last_name ASC";
        $params = [$start, $end];

    // --- 2. SINGLE EMPLOYEE EXPORT (Admin/Coach Specific) ---
    } elseif ($mode === 'SINGLE' && ($role_id <= 3)) {
        $sql = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as full_name, 
                       a.attendance_date, t.time_in, t.time_out, a.attendance_status,
                       ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours
                FROM attendance_logs a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE a.employee_id = ? AND a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC";
        $params = [$target_id, $start, $end];

    // --- 3. TEAM EXPORT (Coach) ---
    } elseif ($mode === 'TEAM' && ($role_id <= 3)) {
        // Uses cluster_members and clusters from your SQL dump
        $sql = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as full_name, 
                       a.attendance_date, t.time_in, t.time_out, a.attendance_status,
                       ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours
                FROM attendance_logs a
                JOIN employees e ON a.employee_id = e.employee_id
                JOIN cluster_members cm ON e.employee_id = cm.employee_id
                JOIN clusters c ON cm.cluster_id = c.cluster_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE c.user_id = ? AND a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC";
        $params = [$user_id, $start, $end];

    // --- 4. PERSONAL EXPORT (Self) ---
    } else {
        $sql = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as full_name, 
                       a.attendance_date, t.time_in, t.time_out, a.attendance_status,
                       ROUND(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out) / 60, 2) as total_hours
                FROM attendance_logs a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id
                WHERE a.employee_id = ? AND a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC";
        $params = [$employee_id, $start, $end];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $grand_total_hours = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $in = ($row['time_in'] && $row['time_in'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['time_in'])) : '--:--';
        $out = ($row['time_out'] && $row['time_out'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['time_out'])) : '--:--';
        
        $hrs = is_numeric($row['total_hours']) ? (float)$row['total_hours'] : 0.00;
        $grand_total_hours += $hrs;

        echo "<tr>
                <td>{$row['employee_id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['attendance_date']}</td>
                <td>$in</td>
                <td>$out</td>
                <td>" . number_format($hrs, 2) . "</td>
                <td>{$row['attendance_status']}</td>
              </tr>";
    }

    echo '<tr style="background-color:#FFC107; font-weight:bold;">
            <td colspan="5" style="text-align:right;">GRAND TOTAL HOURS:</td>
            <td>' . number_format($grand_total_hours, 2) . '</td>
            <td></td>
          </tr>';

} catch (Exception $e) {
    echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>";
}

echo '</table>';
exit;