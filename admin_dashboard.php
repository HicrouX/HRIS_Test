<?php
// FILE: admin_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: text/html; charset=UTF-8");

require_once 'api/config/db.php'; 
require_once 'api/middleware/auth.php';

// Verify Admin Access
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header("Location: login.php");
    exit;
}

$api_base_url = "http://localhost/hris_official/api"; 

$user_id = $_SESSION['employee_id'];

// FETCH REAL NAME
$admin_name = "Administrator";
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $admin_name = $user['first_name'] . ' ' . $user['last_name'];
    }
} catch (Exception $e) { /* Ignore */ }

// Edit Attendance Logic (Updating table name)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_status'])) {
    $id = $_POST['attendance_id'];
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE attendance_logs SET attendance_status = ? WHERE attendance_id = ?");
        $stmt->execute([$status, $id]);
        header("Location: admin_dashboard.php"); exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// Check for Edit Mode via GET
$edit_mode = false; $edit_record = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name FROM attendance_logs a JOIN employees e ON a.employee_id = e.employee_id WHERE a.attendance_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_record) $edit_mode = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>iREPLY - Admin Dashboard</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #222; --text-gray: #666; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); overflow: hidden; }
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 20px; text-align: center; }
        .user-info { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .user-name { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 5px; text-transform: capitalize; }
        .user-id { font-size: 11px; color: #888; background: #f4f4f4; padding: 3px 10px; border-radius: 12px; display: inline-block; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; text-decoration: none; display: block; }
        .nav-item:hover { background: #f0f4f8; color: var(--primary-blue); }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        .logout-btn { color: #e74c3c !important; font-weight: bold; }
        
        /* SCROLLING FIX APPLIED HERE */
        .main-content { flex: 1; min-height: 0; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .view-content { display: none; flex-direction: column; flex: 1; min-height: 0; overflow-y: auto; }
        .active-view { display: flex; }
        
        .header { background: var(--primary-blue); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #f9f9f9; font-size: 12px; }
        th { cursor: pointer; background-color: #f8f9fa; font-weight: 600; color: #555; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Endorsed { background: #e3f2fd; color: #3498db; }
        .status-Approved { background: #e8f5e9; color: #27ae60; }
        .status-Denied { background: #ffebee; color: #e74c3c; }
        .search-box { padding: 6px 10px; border-radius: 20px; border: none; font-size: 13px; width: 200px; margin-right: 15px; outline: none; border: 1px solid #ddd; }
        .action-icon { cursor: pointer; font-size: 18px; margin-right: 10px; text-decoration: none; display: inline-block; transition: 0.2s; }
        .action-icon:hover { transform: scale(1.2); }
        
        /* MODAL STYLES */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 0; border-radius: 4px; width: 1000px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; }
        .modal-header { background: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .modal-title { font-size: 16px; font-weight: bold; color: #333; }
        .modal-body { padding: 0; overflow-y: auto; background: #f9f9f9; }
        .close-btn { cursor: pointer; font-size: 24px; color: #999; }
        
        .history-table th { background: #eee; color: #333; font-weight: bold; font-size: 12px; border-bottom: 2px solid #ddd; padding: 12px; }
        .history-table td { background: #fff; color: #555; font-size: 12px; border-bottom: 1px solid #eee; padding: 12px; vertical-align: middle; }
        .clickable-name { color: #1e4d8c; font-weight: bold; cursor: pointer; text-decoration: underline; }
        
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 8px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 13px; }
        textarea { grid-column: span 2; }
        .submit-btn { padding: 10px; width: 100%; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); }
        .date-input-small { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 5px; border-radius: 4px; font-size: 13px; }
        
        .overlay { display: <?php echo $edit_mode ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
        .modal-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 450px; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        select.search-box { background: #fff; cursor: pointer; }

        /* ✅ Summary Stat Badges */
        .stats-container { display: flex; gap: 15px; padding: 15px 40px 0 40px; flex-wrap: wrap; }
        .stat-badge { padding: 10px 15px; border-radius: 8px; font-weight: bold; font-size: 12px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .badge-present { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .badge-absent { background: #ffebee; color: #c62828; border-color: #ffcdd2; }
        .badge-late { background: #fff3e0; color: #e65100; border-color: #ffe0b2; }
        .badge-leave { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
    </style>
</head>
<body>
    <input type="hidden" id="admin_id" value="<?php echo $user_id; ?>">
    <input type="hidden" id="current_view_id" value="">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
            <div class="user-id">ID: <?php echo htmlspecialchars($user_id); ?></div>
        </div>
        <div class="nav-item nav-active" onclick="switchView('master-view', this)">Master Attendance</div>
        <div class="nav-item" onclick="switchView('approvals-view', this)">Final Approvals</div>
        <div class="nav-item" onclick="switchView('history-view', this)">Request History</div>
        <div class="nav-item" onclick="switchView('my-requests-view', this)">My Requests</div>
        <div class="nav-item" onclick="switchView('admin-filing', this)">My Filing Center</div>
        <div class="nav-item" onclick="switchView('my-attendance', this)">My Attendance Records</div>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item logout-btn">Log Out</a>
    </div>

    <div class="main-content">
        <div id="master-view" class="view-content active-view">
            <div class="header">
                <div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button id="backBtn" onclick="resetToLeaders()" style="display:none; cursor:pointer; background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.4); padding:5px 10px; color:white; border-radius:15px; font-size:12px;">⬅ Back to Log</button>
                        <h2 style="margin:0; font-size:18px;">📊 Master Attendance</h2>
                    </div>
                    <span style="font-size:11px; opacity:0.8; margin-top:5px; display:block;">Viewing: <span id="viewLabel" style="font-weight:bold; color:#FFC107;">Coaches & Admins</span></span>
                </div>
                <div style="display:flex; align-items:center;">
                    <input type="text" id="adminSearch" class="search-box" placeholder="🔍 Search name..." onkeyup="filterTable('masterTable', this.value)">
                </div>
            </div>
            <div class="container"><table id="masterTable"><thead id="masterTableHead"><tr onclick="sortTable('masterTable', 0)"><th>Personnel ⬍</th><th>Last Active</th><th>Status</th><th>Actions</th></tr></thead><tbody id="masterLogsBody"></tbody></table></div>
        </div>

        <div id="approvals-view" class="view-content">
            <div class="header"><h2>✅ Final Sign-offs</h2></div>
            <div class="container">
                <h3>Leaves (Endorsed)</h3>
                <div style="margin-bottom:10px;">
                    <select class="search-box" onchange="filterTable('adminLeaveTable', this.value)" style="margin-left:0; width: 200px;">
                        <option value="">Show All Types</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Vacation Leave">Vacation Leave</option>
                        <option value="Emergency Leave">Emergency Leave</option>
                    </select>
                </div>
                <table id="adminLeaveTable">
                    <thead><tr onclick="sortTable('adminLeaveTable', 0)"><th>Employee ⬍</th><th>Type ⬍</th><th>Date Range</th><th>Reason</th><th>Endorsed By</th><th>Action</th></tr></thead>
                    <tbody id="adminLeaveQueue"></tbody>
                </table>
                
                <h3 style="margin-top:40px;">Overtime (Endorsed)</h3>
                <div style="margin-bottom:10px;">
                    <select class="search-box" onchange="filterTable('adminOTTable', this.value)" style="margin-left:0; width: 200px;">
                        <option value="">Show All Types</option>
                        <option value="Regular Overtime">Regular Overtime</option>
                        <option value="Duty on Rest Day">Duty on Rest Day</option>
                    </select>
                </div>
                <table id="adminOTTable">
                    <thead><tr onclick="sortTable('adminOTTable', 0)"><th>Employee ⬍</th><th>Type ⬍</th><th>Date/Time</th><th>Purpose</th><th>Endorsed By</th><th>Action</th></tr></thead>
                    <tbody id="adminOTQueue"></tbody>
                </table>

                <h3 style="margin-top:40px; color:#e74c3c;">Attendance Disputes (Immediate Settlement)</h3>
                <table id="adminDisputeTable">
                    <thead><tr onclick="sortTable('adminDisputeTable', 0)"><th>Employee ⬍</th><th>Role</th><th>Type ⬍</th><th>Reason</th><th>Admin Remarks</th><th>Action</th></tr></thead>
                    <tbody id="adminDisputeQueue"></tbody>
                </table>
            </div>
        </div>

        <div id="history-view" class="view-content">
            <div class="header">
                <h2 style="margin:0;">📜 Request History</h2>
                <div style="display:flex; gap:10px; align-items:center;"><select id="hist_status" style="padding:8px; border-radius:5px;"><option value="ALL">All Status</option><option value="Pending">Pending</option><option value="Endorsed">Endorsed</option><option value="Approved">Approved</option><option value="Denied">Denied</option></select><input type="date" id="hist_start" class="date-input-small"><span style="color:white;">to</span><input type="date" id="hist_end" class="date-input-small"><button class="submit-btn" style="width: auto; padding: 5px 15px;" onclick="loadRequestHistory()">Filter</button></div>
            </div>
            <div class="container"><table id="historyTable"><thead><tr onclick="sortTable('historyTable', 0)"><th>Employee ⬍</th><th>Type ⬍</th><th>Details</th><th>Date Range ⬍</th><th>Status ⬍</th><th>Filed On ⬍</th></tr></thead><tbody id="historyBody"></tbody></table></div>
        </div>

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;"><h2 style="margin:0;">My Request Status</h2><button class="submit-btn" style="width:auto; padding:5px 15px; background:rgba(255,255,255,0.2);" onclick="loadMyRequests()">🔄 Refresh</button></div>
            <div class="container">
                <h3 style="color:#666;">My Leave Requests</h3>
                <table id="myLeaveTable"><thead><tr onclick="sortTable('myLeaveTable', 0)"><th>Type ⬍</th><th>Date Range ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Approved By</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666; margin-top:40px;">My Overtime Requests</h3>
                <table id="myOTTable"><thead><tr onclick="sortTable('myOTTable', 0)"><th>Type ⬍</th><th>Time Range ⬍</th><th>Purpose</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Approved By</th></tr></thead><tbody id="myOTLogs"></tbody></table>
                <h3 style="color:#666; margin-top:40px;">My Disputes</h3>
                <table id="myDisputeTable"><thead><tr onclick="sortTable('myDisputeTable', 0)"><th>Type ⬍</th><th>Date ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Admin Remarks</th></tr></thead><tbody id="myDisputeLogs"></tbody></table>
            </div>
        </div>

        <div id="admin-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div class="form-card">
                    <h3>📝 Leave</h3>
                    <form id="leaveForm" class="form-grid">
                        <select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select>
                        <div></div>
                        <input type="date" id="l_start"><input type="date" id="l_end">
                        <textarea id="l_reason" placeholder="Reason..."></textarea>
                        
                        <div style="background:#f4f6f8; padding:15px; border-radius:6px; font-size:11px; color:#555; border: 1px solid #eee; grid-column: span 2;">
                            <label style="display:flex; gap:8px; margin-bottom:10px; cursor:pointer; align-items:flex-start;">
                                <input type="checkbox" id="l_agree1" style="width:auto; margin-top:2px;">
                                <span>I confirm that the information submitted has undergone a thorough double-check process, ensuring its accuracy and reliability, especially the email addresses, to the best of my knowledge and abilities. <b style="color:red;">*</b></span>
                            </label>
                            <label style="display:flex; gap:8px; cursor:pointer; align-items:flex-start;">
                                <input type="checkbox" id="l_agree2" style="width:auto; margin-top:2px;">
                                <span>I understand that falsifying information is a serious offense, constituting fraud, and I acknowledge that engaging in such behavior can lead to severe consequences, including termination of employment. <b style="color:red;">*</b></span>
                            </label>
                        </div>
                        
                        <button type="button" class="submit-btn" style="grid-column: span 2;" onclick="submitRequest('leave')">Submit</button>
                    </form>
                </div>
                
                <div class="form-card" style="border-left: 5px solid #27ae60;">
                    <h3>⏰ Overtime</h3>
                    <form id="otForm" class="form-grid">
                        <select id="ot_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select>
                        <div style="grid-column: span 1;"><label style="font-size:12px; font-weight:bold;">Date:</label><input type="date" id="ot_date" required></div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; grid-column: span 2;">
                            <div><label style="font-size:11px; font-weight:bold;">Start Time:</label><input type="time" id="ot_start_time" required></div>
                            <div><label style="font-size:11px; font-weight:bold;">End Time:</label><input type="time" id="ot_end_time" required></div>
                        </div>
                        <textarea id="ot_purpose" placeholder="Purpose..."></textarea>

                        <div style="background:#f4f6f8; padding:15px; border-radius:6px; font-size:11px; color:#555; border: 1px solid #eee; grid-column: span 2;">
                            <label style="display:flex; gap:8px; margin-bottom:10px; cursor:pointer; align-items:flex-start;">
                                <input type="checkbox" id="ot_agree1" style="width:auto; margin-top:2px;">
                                <span>I confirm that the information submitted has undergone a thorough double-check process, ensuring its accuracy and reliability, especially the email addresses, to the best of my knowledge and abilities. <b style="color:red;">*</b></span>
                            </label>
                            <label style="display:flex; gap:8px; cursor:pointer; align-items:flex-start;">
                                <input type="checkbox" id="ot_agree2" style="width:auto; margin-top:2px;">
                                <span>I understand that falsifying information is a serious offense, constituting fraud, and I acknowledge that engaging in such behavior can lead to severe consequences, including termination of employment. <b style="color:red;">*</b></span>
                            </label>
                        </div>

                        <button type="button" class="submit-btn" style="background:#27ae60; grid-column: span 2;" onclick="submitRequest('ot')">Submit</button>
                    </form>
                </div>                
                <div class="form-card" style="border-left: 5px solid #e74c3c;">
                    <h3 style="color: #e74c3c;">Attendance Dispute</h3>
                    <form id="disputeForm" class="form-grid">
                        <input type="text" value="Self-Filing" class="readonly-field" readonly style="background:#eee;">
                        <select id="d_type" required onchange="toggleTimeInput(this.value)">
                            <option value="" disabled selected>Select Dispute Type</option>
                            <option>Forgot Time In/Out</option>
                            <option>System Error</option>
                            <option>Official Business</option>
                            <option>Incorrect Status</option>
                            <option>Breaktime</option>
                            <option>Lunch Break</option>
                        </select>
                        <div style="grid-column: span 2;"><label style="font-weight:bold;">Date of Incident:</label><input type="date" id="d_date" required></div>
                        <div id="timeInputDiv" style="display:none; grid-column: span 2;">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed In:</label><input type="time" id="d_time_in"></div>
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed Out:</label><input type="time" id="d_time_out"></div>
                            </div>
                        </div>
                        <textarea id="d_reason" placeholder="Explain..." rows="3"></textarea>
                        <button type="button" class="submit-btn" style="background:#e74c3c; grid-column: span 2;" onclick="submitRequest('dispute')">Submit Dispute</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="my-attendance" class="view-content">
            <div class="header">
                <div style="display:flex; align-items:center; gap:10px;"><h2 style="margin:0;">Attendance History</h2></div>
                
                <div style="background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 15px; font-size: 13px; font-weight: bold; color: white;">
                    Total Hours: <span id="totalHoursSum">0.00</span>
                </div>
                <div style="display:flex;">
                    <input type="date" id="range_start" class="search-box" style="width:130px;" onchange="loadMyAttendance()">
                    <input type="date" id="range_end" class="search-box" style="width:130px; margin-left:5px;" onchange="loadMyAttendance()">
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-badge badge-present">Present: <span id="countPresent" style="font-size:16px;">0</span></div>
                <div class="stat-badge badge-absent">Absent: <span id="countAbsent" style="font-size:16px;">0</span></div>
                <div class="stat-badge badge-late">Late / Tardy: <span id="countLate" style="font-size:16px;">0</span></div>
                <div class="stat-badge badge-leave">On Leave: <span id="countLeave" style="font-size:16px;">0</span></div>
            </div>

            <div class="container">
                <div style="margin-bottom:10px;">
                    <select class="search-box" onchange="filterTable('myAttTable', this.value)" style="margin-left:0; width:150px;">
                        <option value="">Show All Statuses</option>
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Tardy">Tardy</option>
                        <option value="Absent">Absent</option>
                        <option value="Overtime">Overtime</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Undertime">Undertime</option>
                        <option value="Duty on Rest Day">Duty on Rest Day</option>
                    </select>
                </div>
                <table id="myAttTable">
                    <thead><tr onclick="sortTable('myAttTable', 0)"><th>Date ⬍</th><th>In</th><th>Out</th><th>Break In</th><th>Break Out</th><th>Status ⬍</th><th>Hrs ⬍</th></tr></thead>
                    <tbody id="myAttendanceBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="disputeModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title">Resolve Dispute (Immediate)</div>
                <span class="close-btn" onclick="closeDisputeModal()">×</span>
            </div>
            
            <div id="disputeModalContent" style="padding:15px; font-size:13px; background:#f9f9f9; border-radius:6px; margin:10px 0;"></div>
            
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Action:</label>
            <select id="disputeAction" style="width:100%; padding:10px; margin-bottom:10px;" onchange="toggleDisputeFields()">
                <option value="APPROVE">Approve (Modify Attendance)</option>
                <option value="DENY">Deny (No Changes)</option>
            </select>
            
            <div id="approvalFields">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Set Correct Status:</label>
                <select id="newDisputeStatus" style="width:100%; padding:10px; margin-bottom:10px;">
                    <option value="Present">Present</option>
                    <option value="Late">Late</option>
                    <option value="Absent">Absent</option>
                    <option value="Overtime">Overtime</option>
                    <option value="On Leave">On Leave</option>
                </select>
                <div style="display:flex; gap:10px; margin-bottom:10px;">
                    <div>Proposed In: <input type="time" id="finalTimeIn"></div>
                    <div>Proposed Out: <input type="time" id="finalTimeOut"></div>
                </div>
            </div>

            <label style="font-weight:bold;">Remarks:</label>
            <textarea id="actionRemarks" rows="2" style="width:100%; margin-bottom:10px;"></textarea>
            <input type="hidden" id="currentDisputeId">
            
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button onclick="confirmDispute()" style="flex:2; padding:10px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Confirm & Settle</button>
                <button onclick="closeDisputeModal()" style="flex:1; padding:10px; background:#eee; color:#333; border:none; border-radius:5px; cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>
    
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><div class="modal-title" id="modalTitle">Employee History</div><span class="close-btn" onclick="closeModal()">×</span></div>
            <div style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: flex-end;">
                <span style="font-size: 13px; color: #555; margin-right: 10px;">Filter Range:</span>
                <input type="date" id="hist_modal_start" class="search-box" style="width: 140px;">
                <input type="date" id="hist_modal_end" class="search-box" style="margin-left: 5px; width: 140px;">
                <button class="submit-btn" style="width: auto; padding: 5px 15px;" onclick="filterMemberHistory()">Filter</button>
            </div>
            <div class="modal-body">
                <table class="history-table">
                    <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Break In</th><th>Break Out</th><th>Lunch</th><th>Status</th><th>Work Hours</th></tr></thead>
                    <tbody id="modalHistoryBody"></tbody>
                </table>
            </div>
            <div style="padding:10px; background:#fff; text-align:right; border-top:1px solid #ddd; color:#999; font-size:11px;">Total Hours: <span id="totalHoursDisplay">0.00</span></div>
        </div>
    </div>

    <?php if ($edit_mode && $edit_record): ?>
    <div class="overlay">
        <div class="modal-box">
            <div class="modal-header">✏️ Edit Attendance</div>
            <form method="POST" action="admin_dashboard.php">
                <input type="hidden" name="attendance_id" value="<?php echo $edit_record['attendance_id']; ?>">
                <select name="status" style="width:100%; padding:10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 10px;">
                    <?php 
                    $statuses = ['Present','Absent','Late','Overtime','On Leave','Undertime','Duty on Rest Day','Tardy'];
                    foreach($statuses as $s) {
                        $sel = ($edit_record['attendance_status'] == $s) ? 'selected' : '';
                        echo "<option value='$s' $sel>$s</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="save_status" style="width:100%; padding:10px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer;">Save Changes</button>
                <a href="admin_dashboard.php" style="display:block; text-align:center; margin-top:10px; color:#666; font-size:12px; text-decoration:none;">Cancel</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const API = "<?php echo $api_base_url; ?>";
        const MY_ID = document.getElementById('admin_id').value;
        let currentMode = 'COACHES'; let currentCoachId = null;   

        function switchView(viewId, btn) {
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('active-view');
            btn.classList.add('nav-active');
            if(viewId === 'master-view') refreshTable();
            if(viewId === 'approvals-view') loadApprovals();
            if(viewId === 'history-view') loadRequestHistory();
            if(viewId === 'my-requests-view') loadMyRequests();
            if(viewId === 'my-attendance') loadMyAttendance();
        }

        function toggleTimeInput(val) { document.getElementById('timeInputDiv').style.display = val.includes('Forgot') ? 'block' : 'none'; }

        // ✅ NEW: CALCULATE HOURS & SUMMARY STATISTICS
        function updateTableSummaries(tid) {
            let sum = 0;
            let counts = { present: 0, absent: 0, late: 0, leave: 0 };
            
            const rows = Array.from(document.getElementById(tid).tBodies[0].rows);
            rows.forEach(r => {
                if (r.style.display !== 'none' && r.cells.length > 1) { // Skip empty state rows
                    // Read the last column for hours, second to last for status
                    let val = parseFloat(r.cells[r.cells.length - 1].innerText);
                    if (!isNaN(val)) sum += val;
                    
                    let status = r.cells[r.cells.length - 2].innerText.toLowerCase();
                    if (status.includes('present')) counts.present++;
                    else if (status.includes('absent')) counts.absent++;
                    else if (status.includes('late') || status.includes('tard')) counts.late++;
                    else if (status.includes('leave')) counts.leave++;
                }
            });
            
            // Update UI
            document.getElementById('totalHoursSum').innerText = sum.toFixed(2);
            document.getElementById('countPresent').innerText = counts.present;
            document.getElementById('countAbsent').innerText = counts.absent;
            document.getElementById('countLate').innerText = counts.late;
            document.getElementById('countLeave').innerText = counts.leave;
        }

        // SORTING & FILTERING
        function sortTable(tid, n) {
            let table = document.getElementById(tid), tbody = table.tBodies[0], rows = Array.from(tbody.rows);
            let asc = table.getAttribute('data-asc') === 'true';
            rows.sort((a,b) => {
                let v1 = a.cells[n].innerText.toLowerCase(), v2 = b.cells[n].innerText.toLowerCase();
                return asc ? v1.localeCompare(v2) : v2.localeCompare(v1);
            });
            rows.forEach(r => tbody.appendChild(r));
            table.setAttribute('data-asc', !asc);
        }

        function filterTable(tid, val) {
            let filter = val.toLowerCase();
            let rows = document.getElementById(tid).getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) { 
                let cell = rows[i].innerText.toLowerCase();
                rows[i].style.display = cell.indexOf(filter) > -1 ? "" : "none";
            }
            if (tid === 'myAttTable') updateTableSummaries(tid);
        }

        async function refreshTable() {
            if (currentMode === 'TEAM') { loadTeamRoster(currentCoachId); return; }
            let url = `${API}/admin/get_all_attendance.php`;
            document.getElementById('viewLabel').innerText = "Coaches & Admins";
            document.getElementById('backBtn').style.display = 'none';
            document.getElementById('masterTableHead').innerHTML = `<tr><th>Personnel ⬍</th><th>Last Active</th><th>Status</th><th>Actions</th></tr>`;
            try {
                const res = await fetch(url); const data = await res.json();
                const tbody = document.getElementById("masterLogsBody");
                if(data.length === 0) { tbody.innerHTML = `<tr><td colspan='4' style='text-align:center;'>No records found.</td></tr>`; return; }
                tbody.innerHTML = data.map(log => {
                    let nameDisplay = `<strong>${log.first_name} ${log.last_name}</strong>`;
                    let actions = `<span class="action-icon" style="color:#3498db;" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name}')">👁️</span>`;
                    if (log.role_id == 3) nameDisplay = `<span class="clickable-name" onclick="viewTeam(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name} (Coach)</span>`;
                    if (log.latest_id) actions += `<a href="admin_dashboard.php?edit_id=${log.latest_id}" class="action-icon">✏️</a>`;
                    return `<tr><td>${nameDisplay}</td><td>${log.latest_date||'-'}</td><td><span class="status-pill status-${(log.latest_status||'').replace(/\s/g,'')}">${log.latest_status||'Inactive'}</span></td><td>${actions}</td></tr>`;
                }).join('');
                filterTable('masterTable', document.getElementById('adminSearch').value);
            } catch(e) { console.error("Error:", e); }
        }

        async function viewTeam(coachId, coachName) {
            currentMode = 'TEAM'; currentCoachId = coachId;
            document.getElementById('viewLabel').innerText = `Team: ${coachName}`;
            document.getElementById('backBtn').style.display = 'inline-block';
            document.getElementById('masterTableHead').innerHTML = `<tr><th>Employee ⬍</th><th>Last Active</th><th>Status</th></tr>`;
            loadTeamRoster(coachId);
        }

        async function loadTeamRoster(coachId) {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${coachId}`);
            const data = await res.json();
            document.getElementById("masterLogsBody").innerHTML = data.map(log => `<tr><td><span class="clickable-name" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name}</span></td><td>${log.latest_date||'-'}</td><td>${log.latest_status||'-'}</td></tr>`).join('');
        }

        function resetToLeaders() { currentMode = 'COACHES'; currentCoachId = null; refreshTable(); }

        async function loadApprovals() {
            // Load Leaves & OT
            const [leaveRes, otRes] = await Promise.all([fetch(`${API}/admin/get_endorsed_leaves.php`), fetch(`${API}/admin/get_endorsed_ot.php`)]);
            renderQueue(await leaveRes.json(), 'adminLeaveQueue', 'leave');
            renderQueue(await otRes.json(), 'adminOTQueue', 'ot');

            // NEW: Load Pending Disputes for Immediate Resolution
            const dispRes = await fetch(`${API}/management/get_pending_disputes.php?coach_id=${MY_ID}`);
            const dispData = await dispRes.json();
            document.getElementById('adminDisputeQueue').innerHTML = dispData.map(item => `
                <tr>
                    <td><strong>${item.first_name} ${item.last_name}</strong></td>
                    <td style="font-size:10px; color:#555;">${item.role_name || 'Employee'}</td>
                    <td>${item.dispute_type || 'General'}</td>
                    <td>${item.reason}</td>
                    <td><i style="color:gray;">${item.remarks || 'No remarks'}</i></td>
                    <td>
                        <button onclick="openDisputeModal(${item.dispute_id}, '${item.first_name}', '${item.dispute_date}')" style="background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Review & Settle</button> 
                    </td>
                </tr>`).join('');
        }

        function renderQueue(items, id, type) {
            document.getElementById(id).innerHTML = items.length ? items.map(item => `
                <tr>
                    <td><strong>${item.first_name} ${item.last_name}</strong></td>
                    <td><span class="status-pill status-Endorsed">${item.leave_type || item.ot_type || 'Unknown'}</span></td>
                    <td>${item.start_date||item.start_time}</td>
                    <td>"${item.reason||item.purpose}"</td>
                    <td style="font-weight:bold; color:#e67e22;">${item.endorser_name||'-'}</td>
                    <td>
                        <span class="action-icon" style="color:green;" onclick="finalApprove(${item.leave_id||item.ot_id}, '${type}', 'APPROVE')">✔</span> 
                        <span class="action-icon" style="color:red;" onclick="finalApprove(${item.leave_id||item.ot_id}, '${type}', 'DENY')">❌</span>
                    </td>
                </tr>`).join('') : `<tr><td colspan='6' style='text-align:center; color:#999;'>No pending items</td></tr>`;
        }

        async function finalApprove(id, type, action) {
            if(!confirm(`${action} this request?`)) return;
            const endpoint = type === 'leave' ? '/admin/final_approve_leave.php' : '/admin/final_approve_overtime.php';
            await fetch(`${API}${endpoint}?admin_id=${MY_ID}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ [type + '_id']: id, action: action }) });
            loadApprovals();
        }

        // --- DISPUTE MODAL LOGIC ---
        function openDisputeModal(id, name, date) { 
            document.getElementById('currentDisputeId').value = id; 
            document.getElementById('disputeModalContent').innerText = `Resolving for: ${name} on ${date}`; 
            document.getElementById('disputeAction').value = "APPROVE";
            toggleDisputeFields();
            document.getElementById('disputeModal').style.display = 'flex'; 
        }

        function closeDisputeModal() { document.getElementById('disputeModal').style.display = 'none'; }
        
        function toggleDisputeFields() {
            const action = document.getElementById('disputeAction').value;
            document.getElementById('approvalFields').style.display = (action === 'APPROVE') ? 'block' : 'none';
        }

        async function confirmDispute() { 
            const id = document.getElementById('currentDisputeId').value; 
            const action = document.getElementById('disputeAction').value; 
            const rem = document.getElementById('actionRemarks').value; 
            
            let payload = { dispute_id: id, action: action, remarks: rem };

            if (action === 'APPROVE') {
                payload.new_status = document.getElementById('newDisputeStatus').value;
                payload.time_in = document.getElementById('finalTimeIn').value;
                payload.time_out = document.getElementById('finalTimeOut').value;
            }
            
            await fetch(`${API}/management/resolve_dispute.php`, { 
                method: 'POST', 
                body: JSON.stringify(payload) 
            }); 
            closeDisputeModal(); 
            loadApprovals(); 
        }
        // --------------------------------

        async function loadRequestHistory() {
            const start = document.getElementById('hist_start').value, end = document.getElementById('hist_end').value, status = document.getElementById('hist_status').value;
            const res = await fetch(`${API}/admin/get_request_history.php?start_date=${start}&end_date=${end}&status=${status}`);
            const data = await res.json();
            document.getElementById('historyBody').innerHTML = data.length ? data.map(item => `<tr><td>${item.employee_name}</td><td>${item.category} (${item.type})</td><td>${item.details}</td><td>${item.date_start} to ${item.date_end}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td></tr>`).join('') : "<tr><td colspan='6' style='text-align:center;'>No records found.</td></tr>";
        }

        async function viewMemberHistory(empId, name) {
            document.getElementById('modalTitle').innerText = `${name} - History`;
            document.getElementById('current_view_id').value = empId; 
            const d = new Date();
            const s = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0];
            const e = new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().split('T')[0];
            document.getElementById('hist_modal_start').value = s;
            document.getElementById('hist_modal_end').value = e;
            document.getElementById('historyModal').style.display = 'flex';
            filterMemberHistory();
        }

        async function filterMemberHistory() {
            const empId = document.getElementById('current_view_id').value;
            const start = document.getElementById('hist_modal_start').value;
            const end = document.getElementById('hist_modal_end').value;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${empId}&start_date=${start}&end_date=${end}`);
            const data = await res.json();
            let total = 0;
            document.getElementById('modalHistoryBody').innerHTML = data.length ? data.map(row => {
                total += parseFloat(row.total_hours || 0);
                return `<tr>
                    <td>${row.date}</td>
                    <td>${row.time_in || '--:--'}</td>
                    <td>${row.time_out || '--:--'}</td>
                    <td>${row.break_in || '--:--'}</td>
                    <td>${row.break_out || '--:--'}</td>
                    <td>${row.lunch_break || '0'}</td>
                    <td><span class="status-pill status-${(row.status||'').replace(/\s/g,'')}">${row.status}</span></td>
                    <td>${row.total_hours}</td>
                </tr>`;
            }).join('') : '<tr><td colspan="8" style="text-align:center">No records for this period</td></tr>';
            document.getElementById('totalHoursDisplay').innerText = total.toFixed(2);
        }

        function closeModal() { document.getElementById('historyModal').style.display = 'none'; }
        window.onclick = function(event) { if (event.target == document.getElementById('historyModal')) closeModal(); }

        async function loadMyRequests() {
            const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${MY_ID}`);
            const data = await res.json();
            const leaves = data.filter(item => item.type === 'Leave');
            const overtime = data.filter(item => item.type === 'Overtime');
            const disputes = data.filter(item => item.type === 'Dispute');
            const getApprover = (item) => item.admin_first ? `<span style="color:#27ae60; font-weight:600;">${item.admin_first} ${item.admin_last}</span>` : '<span style="color:#ccc;">-</span>';
            const renderRow = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}<br>${item.end_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td><td>${getApprover(item)}</td></tr>`;
            
            const renderDisp = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td><td style="color:blue; font-style:italic;">${item.remarks || '--'}</td></tr>`;
            
            document.getElementById("myLeaveLogs").innerHTML = leaves.length ? leaves.map(renderRow).join('') : '<tr><td colspan="6" style="text-align:center;">No records</td></tr>';
            document.getElementById("myOTLogs").innerHTML = overtime.length ? overtime.map(renderRow).join('') : '<tr><td colspan="6" style="text-align:center;">No records</td></tr>';
            document.getElementById("myDisputeLogs").innerHTML = disputes.length ? disputes.map(renderDisp).join('') : '<tr><td colspan="6" style="text-align:center;">No records</td></tr>';
        }

        async function loadMyAttendance() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            
            const startInput = document.getElementById('range_start').value || s;
            const endInput = document.getElementById('range_end').value || e;
            document.getElementById('range_start').value = startInput;
            document.getElementById('range_end').value = endInput;

            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${MY_ID}&start_date=${startInput}&end_date=${endInput}`);
            const data = await res.json();
            
            document.getElementById('myAttendanceBody').innerHTML = data.length ? data.map(row => `<tr><td>${row.date}</td><td>${row.time_in || '--:--'}</td><td>${row.time_out || '--:--'}</td><td>${row.break_in || '--:--'}</td><td>${row.break_out || '--:--'}</td><td><span class="status-pill status-${(row.status||'').replace(/\s/g,'')}">${row.status}</span></td><td>${row.total_hours || '0.00'}</td></tr>`).join('') : '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
            
            updateTableSummaries('myAttTable');
        }
        
        async function submitRequest(type) {
            let endpoint, payload, formId;
            
            if (type === 'leave') { 
                const agree1 = document.getElementById('l_agree1').checked ? 1 : 0;
                const agree2 = document.getElementById('l_agree2').checked ? 1 : 0;
                
                if (!agree1 || !agree2) {
                    alert("⚠️ You must check both agreement boxes before submitting.");
                    return;
                }

                endpoint = '/users/file_leave.php'; formId = 'leaveForm'; 
                payload = { 
                    employee_id: MY_ID, 
                    leave_type: document.getElementById('l_type').value, 
                    start_date: document.getElementById('l_start').value, 
                    end_date: document.getElementById('l_end').value, 
                    reason: document.getElementById('l_reason').value, 
                    agreement_1: agree1, 
                    agreement_2: agree2 
                }; 
                
            } else if (type === 'ot') {
                const agree1 = document.getElementById('ot_agree1').checked ? 1 : 0;
                const agree2 = document.getElementById('ot_agree2').checked ? 1 : 0;
                
                if (!agree1 || !agree2) {
                    alert("⚠️ You must check both agreement boxes before submitting.");
                    return;
                }

                const dateVal = document.getElementById('ot_date').value;
                const startVal = document.getElementById('ot_start_time').value;
                const endVal = document.getElementById('ot_end_time').value;
                
                if (!dateVal || !startVal || !endVal) { alert("⚠️ Please fill in all date and time fields."); return; }

                const startStr = `${dateVal} ${startVal}`;
                const endStr = `${dateVal} ${endVal}`;

                const start = new Date(startStr);
                const end = new Date(endStr);
                const diffMs = end - start;
                const diffHrs = diffMs / (1000 * 60 * 60);
                
                if (diffHrs <= 0) { alert("⚠️ Invalid time range: End time must be after start time."); return; }
                if (diffHrs > 2) { alert("⚠️ Cannot submit: Overtime is limited to 2 hours per request."); return; }

                endpoint = '/users/file_overtime.php'; formId = 'otForm'; 
                payload = { 
                    employee_id: MY_ID, 
                    ot_type: document.getElementById('ot_type').value, 
                    start_time: startStr, 
                    end_time: endStr, 
                    purpose: document.getElementById('ot_purpose').value, 
                    agreement_1: agree1, 
                    agreement_2: agree2 
                };
                
            } else if (type === 'dispute') {
                endpoint = '/users/file_dispute.php'; formId = 'disputeForm';
                let reason = document.getElementById('d_reason').value;
                if(document.getElementById('d_type').value.includes('Forgot')) {
                    reason += " [Proposed In: "+document.getElementById('d_time_in').value+", Proposed Out: "+document.getElementById('d_time_out').value+"]";
                }
                payload = { employee_id: MY_ID, date: document.getElementById('d_date').value, dispute_type: document.getElementById('d_type').value, reason: reason };
            }

            try { 
                const res = await fetch(`${API}${endpoint}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) }); 
                const result = await res.json(); alert(result.success || result.error); 
                if(result.success) { document.getElementById(formId).reset(); loadMyRequests(); } 
            } catch (err) { alert("Submission failed."); }
        }
        
        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value = s; document.getElementById('range_end').value = e;
            refreshTable(); loadApprovals(); loadMyAttendance();
        }
    </script>
</body>
</html>