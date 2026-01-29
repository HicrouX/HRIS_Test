<?php
// api/admin/debug_leaves.php
require_once '../config/db.php';

echo "<h1>🕵️ Admin Leave Debugger</h1>";

try {
    // 1. Check Raw Table Data
    $stmt = $pdo->query("SELECT * FROM leave_requests");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>📊 Raw Data in 'leave_requests' table:</h3>";
    if (count($leaves) === 0) {
        echo "<p style='color:red'>Table is EMPTY. Did you truncate it?</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Emp ID</th><th>Status</th><th>Reviewer ID</th><th>Missing Employee?</th></tr>";

        foreach ($leaves as $leave) {
            // Check if Employee Exists
            $checkEmp = $pdo->prepare("SELECT count(*) FROM employees WHERE employee_id = ?");
            $checkEmp->execute([$leave['employee_id']]);
            $empExists = $checkEmp->fetchColumn();

            // Analyze Status
            $statusColor = ($leave['status'] == 'Endorsed') ? 'green' : 'red';
            $empColor = ($empExists > 0) ? 'black' : 'red';
            $empMsg = ($empExists > 0) ? "✅ Exists" : "❌ MISSING (Join will fail)";

            echo "<tr>";
            echo "<td>{$leave['leave_id']}</td>";
            echo "<td>{$leave['employee_id']}</td>";
            echo "<td style='color:$statusColor; font-weight:bold'>{$leave['status']}</td>";
            echo "<td>{$leave['reviewed_by']}</td>";
            echo "<td style='color:$empColor'>$empMsg</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h3>💡 Diagnosis:</h3>";
    echo "<ul>";
    echo "<li>If <b>Status</b> is 'Pending', the Coach hasn't endorsed it yet.</li>";
    echo "<li>If <b>Status</b> is 'Approved', the Admin already finished it.</li>";
    echo "<li>If <b>Missing Employee</b> is '❌ MISSING', the row is hidden because the employee ID doesn't match the employees table.</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "SQL Error: " . $e->getMessage();
}
?>