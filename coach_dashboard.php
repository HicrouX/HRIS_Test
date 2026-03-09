<?php
// FILE: coach_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'api/config/db.php'; 
require_once 'api/middleware/auth.php';

// Verify Coach Access
if (!isset($_SESSION['role_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role_id'] == 4) { header("Location: employee_dashboard.php"); exit; }
if ($_SESSION['role_id'] == 2) { header("Location: admin_dashboard.php"); exit; }
if ($_SESSION['role_id'] == 1) { header("Location: super_admin_dashboard.php"); exit; }

verifyAccess([3]); // Coach Access Only

$api_base_url = "http://localhost/hris_official/api";

// FIXED: Ensure keys exist in session to prevent the "Undefined array key" warning
$coach_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 0;
$coach_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// FETCH COACH NAME
$coach_name = "Coach";
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $stmt->execute([$coach_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) { $coach_name = $user['first_name'] . ' ' . $user['last_name']; }
} catch (Exception $e) { /* Ignore */ }

// VITAL LOGIC: AUTO-DETECT CLUSTER ONLY FOR THE LOGGED-IN COACH
$my_cluster_id = 0;
$my_cluster_name = "No Cluster Detected";
try {
    // Search clusters table where user_id matches the logged-in coach
    $stmt = $pdo->prepare("SELECT cluster_id, name FROM clusters WHERE user_id = ? LIMIT 1");
    $stmt->execute([$coach_user_id]);
    $cluster_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cluster_info) {
        $my_cluster_id = $cluster_info['cluster_id'];
        $my_cluster_name = $cluster_info['name'];
    }
} catch (Exception $e) { /* Ignore */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Coach Dashboard</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #222; --text-gray: #666; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); overflow: hidden; }
        
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 20px; text-align: center; }
        .user-info { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .user-name { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 5px; text-transform: capitalize; }
        .user-id { font-size: 11px; color: #888; background: #f4f4f4; padding: 3px 10px; border-radius: 12px; display: inline-block; }

        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; text-decoration: none; display: block; }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        
        .main-content { flex: 1; min-height: 0; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .view-content { display: none; flex-direction: column; flex: 1; min-height: 0; overflow-y: auto; }
        .active-view { display: flex; }
        
        .header { background: var(--primary-blue); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px 40px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #f9f9f9; font-size: 12px; }
        th { cursor: pointer; background-color: #f8f9fa; font-weight: 600; color: #555; position: relative; }
        th:hover { background: #ebedef; }
        th::after { content: ' ⬍'; font-size: 10px; color: #ccc; position: absolute; right: 5px; }
        
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Approved { background: #e8f5e9; color: #2e7d32; }
        .status-Denied { background: #ffebee; color: #c62828; }
        .status-Endorsed { background: #e3f2fd; color: #1565c0; }
        .status-Present { background: #e8f5e9; color: #2e7d32; }
        .status-Late, .status-Tardy { background: #fff3e0; color: #e67e22; }
        .status-Absent { background: #ffebee; color: #c62828; }
        .status-OnLeave { background: #e3f2fd; color: #1565c0; } 
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 0; border-radius: 4px; width: 1000px; max-height: 90vh; display: flex; flex-direction: column; }
        .modal-header { background: #fff; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;}
        .modal-title { font-weight: bold; color: #333; font-size: 16px; }
        .close-btn, .close-x { font-size: 24px; cursor: pointer; color: #999; }
        .modal-body { flex: 1; overflow-y: auto; min-height: 0; }
        
        .history-table th { background: #eee; color: #333; font-weight: bold; font-size: 12px; border-bottom: 2px solid #ddd; padding: 10px; position: sticky; top: 0; z-index: 1; }
        .history-table td { background: #fff; color: #555; font-size: 12px; border-bottom: 1px solid #eee; padding: 10px; vertical-align: middle; }
        .clickable-name { color: #1e4d8c; font-weight: bold; cursor: pointer; text-decoration: underline; }
        .action-icon { cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; transition: 0.2s; }
        .action-icon:hover { transform: scale(1.2); }
        
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 8px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 13px; }
        textarea { grid-column: span 2; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); margin-top: 10px;}
        .readonly-field { background: #eee; color: #777; cursor: not-allowed; }
        
        .modal-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 450px; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        .search-box { padding: 6px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; outline: none; margin-left: 10px; background: #fff; }
        .export-btn { display: none; } /* NO EXPORT FOR COACH */
        
        .date-filter-input { padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; color: #333; outline: none; background: white; }

        .history-filter-container { position: relative; display: inline-block; }
        .history-filter-dropdown {
            display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ddd; padding: 10px; border-radius: 5px; 
            z-index: 10000; width: 160px; box-shadow: 0 8px 16px rgba(0,0,0,0.15); text-align: left; margin-top: 5px;
        }
        .history-filter-dropdown label { display: block; margin-bottom: 5px; font-size: 12px; cursor: pointer; padding: 4px; }
        .history-filter-dropdown label:hover { background-color: #f5f5f5; }

        .filter-tag { background: #e3f2fd; color: #0d47a1; padding: 4px 8px; border-radius: 12px; font-size: 11px; display: flex; align-items: center; gap: 5px; border: 1px solid #90caf9; margin-top: 2px; }
        .filter-tag span { cursor: pointer; font-weight: bold; color: #c62828; margin-left: 2px; }
        .clear-all-tag { background: #ffcdd2; color: #b71c1c; padding: 4px 8px; border-radius: 12px; font-size: 11px; display: flex; align-items: center; gap: 5px; border: 1px solid #ef9a9a; margin-top: 2px; cursor: pointer; font-weight: bold; }

        .stats-container { display: flex; gap: 15px; padding: 15px 40px 0 40px; flex-wrap: wrap; }
        .stat-badge { padding: 10px 15px; border-radius: 8px; font-weight: bold; font-size: 12px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .badge-present { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .badge-absent { background: #ffebee; color: #c62828; border-color: #ffcdd2; }
        .badge-late { background: #fff3e0; color: #e65100; border-color: #ffe0b2; }
        .badge-leave { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
    </style>
</head>
<body>
    <input type="hidden" id="coach_id" value="<?php echo $coach_id; ?>">
    <input type="hidden" id="current_view_id" value="">
    <input type="hidden" id="auto_cluster_id" value="<?php echo $my_cluster_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($coach_name); ?></div>
            <div class="user-id">ID: <?php echo htmlspecialchars($coach_id); ?></div>
        </div>
        <div class="nav-item nav-active" onclick="switchView('team-view', this)">Team Attendance</div>
        <div class="nav-item" onclick="switchView('manage-requests', this)">Manage Team Requests</div>
        <div class="nav-item" onclick="switchView('my-filing', this)">My Filing Center</div>
        <div class="nav-item" onclick="switchView('my-requests-view', this)">My Requests</div>
        <div class="nav-item" onclick="switchView('my-attendance', this)">My Attendance Records</div>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c; text-decoration:none;">Log Out</a>
    </div>

    <div class="main-content">
        <div id="team-view" class="view-content active-view">
            <div class="header">
                <h2>👥 Team Members</h2>
                <div style="display:flex; align-items:center;">
                    <button class="submit-btn" style="background:#f39c12; width: auto; padding: 6px 12px; margin-left: 10px;" onclick="loadAttendance()">Refresh</button>
                    <select id="teamStatusFilter" class="search-box" onchange="filterTeamTable()" style="width:150px;">
                        <option value="">Show All Statuses</option>
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Tardy">Tardy</option>
                        <option value="Absent">Absent</option>
                        <option value="On Leave">On Leave</option>
                    </select>
                    <input type="text" id="coachSearch" class="search-box" placeholder="Search Member..." onkeyup="filterTeamTable()">
                </div>
            </div>
            <div class="container">
                <table id="attendanceTable">
                    <thead>
                        <tr>
                            <th onclick="sortTable('attendanceTable', 0)">Employee Name ⬍</th>
                            <th onclick="sortTable('attendanceTable', 1)">Last Active Date ⬍</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th onclick="sortTable('attendanceTable', 4)">Current Status ⬍</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceLogs"></tbody>
                </table>
            </div>
        </div>

        <div id="manage-requests" class="view-content">
            <div class="header">
                <h2>📋 Pending Team Requests</h2>
                <button class="submit-btn" style="background:#f39c12; width: auto; padding: 6px 12px;" onclick="loadAllEndorsements()">Refresh </button>
            </div>
            <div class="container">
                <h3>Leaves (Endorsement Required)</h3>
                <table id="leaveEndorseTable">
                    <thead><tr onclick="sortTable('leaveEndorseTable', 0)"><th>Employee ⬍</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody id="coachLeaveList"></tbody>
                </table>
                
                <h3 style="margin-top:30px;">Overtime (Endorsement Required)</h3>
                <table id="otEndorseTable">
                    <thead><tr onclick="sortTable('otEndorseTable', 0)"><th>Employee ⬍</th><th>Purpose</th><th>Action</th></tr></thead>
                    <tbody id="coachOTList"></tbody>
                </table>
                
                <h3 style="margin-top:30px; color:#e74c3c;">Attendance Disputes (Settles Immediately)</h3>
                <table id="disputeEndorseTable">
                    <thead><tr onclick="sortTable('disputeEndorseTable', 0)"><th>Employee ⬍</th><th>Role</th><th>Type ⬍</th><th>Reason</th><th>Admin Remarks</th><th>Action</th></tr></thead>
                    <tbody id="coachDisputeList"></tbody>
                </table>
            </div>
        </div>

        <div id="my-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                    <div class="form-card">
                        <h3>📝 Leave</h3>
                        <form id="leaveForm" class="form-grid">
                            <select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select><div></div>
                            <input type="date" id="l_start"><input type="date" id="l_end">
                            <textarea id="l_reason" placeholder="Reason..."></textarea>
                            
                            <div style="background:#f4f6f8; padding:15px; border-radius:6px; font-size:11px; color:#555; border: 1px solid #eee; grid-column: span 2;">
                                <label style="display:flex; gap:8px; margin-bottom:10px; cursor:pointer; align-items:flex-start;">
                                    <input type="checkbox" id="l_agree1" style="width:auto; margin-top:2px;">
                                    <span>I confirm that the information submitted has undergone a thorough double-check process, ensuring its accuracy and reliability. <b style="color:red;">*</b></span>
                                </label>
                                <label style="display:flex; gap:8px; cursor:pointer; align-items:flex-start;">
                                    <input type="checkbox" id="l_agree2" style="width:auto; margin-top:2px;">
                                    <span>I understand that falsifying information is a serious offense, constituting fraud, and I acknowledge that engaging in such behavior can lead to severe consequences. <b style="color:red;">*</b></span>
                                </label>
                            </div>

                            <button type="button" class="submit-btn" style="grid-column: span 2;" onclick="submitRequest('leave')">Submit Leave</button>
                        </form>
                    </div>

                    <div class="form-card" style="border-left: 5px solid #27ae60;">
                        <h3>⏰ Overtime</h3>
                        <form id="otForm" class="form-grid">
                            <select id="ot_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select>
                            <div style="grid-column: span 1;"><label style="font-size:12px; font-weight:bold;">Date:</label><input type="date" id="ot_date" required></div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label style="font-size:11px; font-weight:bold;">Start Time:</label><input type="time" id="ot_start_time" required></div>
                                <div><label style="font-size:11px; font-weight:bold;">End Time:</label><input type="time" id="ot_end_time" required></div>
                            </div>
                            <textarea id="ot_purpose" placeholder="Purpose..."></textarea>
                            
                            <div style="background:#f4f6f8; padding:15px; border-radius:6px; font-size:11px; color:#555; border: 1px solid #eee; grid-column: span 2;">
                                <label style="display:flex; gap:8px; margin-bottom:10px; cursor:pointer; align-items:flex-start;">
                                    <input type="checkbox" id="ot_agree1" style="width:auto; margin-top:2px;">
                                    <span>I confirm that the information submitted has undergone a thorough double-check process, ensuring its accuracy and reliability. <b style="color:red;">*</b></span>
                                </label>
                                <label style="display:flex; gap:8px; cursor:pointer; align-items:flex-start;">
                                    <input type="checkbox" id="ot_agree2" style="width:auto; margin-top:2px;">
                                    <span>I understand that falsifying information is a serious offense, constituting fraud, and I acknowledge that engaging in such behavior can lead to severe consequences. <b style="color:red;">*</b></span>
                                </label>
                            </div>

                            <button type="button" class="submit-btn" style="background:#27ae60; grid-column: span 2;" onclick="submitRequest('ot')">Submit Overtime</button>
                        </form>
                    </div>
                </div>

                <div class="form-card" style="border-left: 5px solid #e74c3c;">
                    <h3 style="color: #e74c3c;">Attendance Dispute</h3>
                    <form id="disputeForm" class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="grid-column: span 2;">
                            <label style="font-size:12px; font-weight:bold;">Auto-Detected Cluster:</label>
                            <input type="text" value="<?php echo htmlspecialchars($my_cluster_name); ?>" class="readonly-field" readonly>
                        </div>
                        
                        <select id="d_type" required onchange="toggleTimeInput(this.value)">
                            <option value="" disabled selected>Select Dispute Type</option>
                            <option>Forgot Time In/Out</option>
                            <option>Official Business</option>
                            <option>Incorrect Status</option>
                            <option>System Error</option>
                        </select>
                        <div style="grid-column: span 2;"><label style="font-size:12px; font-weight:bold;">Date of Incident:</label><input type="date" id="d_date" required></div>
                        <div id="timeInputDiv" style="display:none; grid-column: span 2;">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed In:</label><input type="time" id="d_time_in"></div>
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed Out:</label><input type="time" id="d_time_out"></div>
                            </div>
                        </div>
                        <textarea id="d_reason" placeholder="Explain discrepancy..." rows="3" required style="grid-column: span 2;"></textarea>
                        <button type="button" class="submit-btn" style="background:#e74c3c; grid-column: span 2;" onclick="submitRequest('dispute')">Submit Dispute</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;"><h2>My Request Status</h2><button class="submit-btn" style="background:#fff; color:#333; width: auto; padding: 6px 12px;" onclick="loadMyRequests()">Refresh</button></div>
            <div class="container">
                <h3 style="color:#666;">Leave Requests</h3><table id="myLeaveTable"><thead><tr onclick="sortTable('myLeaveTable', 0)"><th>Type ⬍</th><th>Date Range ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Approver</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666; margin-top:20px;">Overtime Requests</h3><table id="myOTTable"><thead><tr onclick="sortTable('myOTTable', 0)"><th>Type ⬍</th><th>Time Range ⬍</th><th>Purpose</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Approver</th></tr></thead><tbody id="myOTLogs"></tbody></table>
                <h3 style="color:#666; margin-top:20px;">My Disputes</h3><table id="myDisputeTable"><thead><tr onclick="sortTable('myDisputeTable', 0)"><th>Type ⬍</th><th>Date ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Admin Remarks</th></tr></thead><tbody id="myDisputeLogs"></tbody></table>
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
                <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                    <select class="search-box" onchange="filterTable('myAttTable', this.value)" style="margin-left:0; width: 150px;">
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
                <span class="close-x" onclick="closeDisputeModal()">×</span>
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
                    <div style="flex:1;">Proposed In: <input type="time" id="finalTimeIn" style="width:100%; padding:8px; box-sizing:border-box;"></div>
                    <div style="flex:1;">Proposed Out: <input type="time" id="finalTimeOut" style="width:100%; padding:8px; box-sizing:border-box;"></div>
                </div>
            </div>

            <label style="font-weight:bold;">Remarks:</label>
            <textarea id="actionRemarks" rows="2" style="width:100%; padding:10px; box-sizing:border-box; margin-bottom:10px;"></textarea>
            <input type="hidden" id="currentDisputeId">
            
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button onclick="confirmDispute()" style="flex:2; padding:10px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Confirm & Settle</button>
                <button onclick="closeDisputeModal()" style="flex:1; padding:10px; background:#eee; color:#333; border:none; border-radius:5px; cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Employee History</div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <span class="close-btn" onclick="closeModal()">×</span>
                </div>
            </div>
            <div style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: flex-end;">
                <span style="font-size: 13px; color: #555; margin-right: 10px;">Filter Range:</span>
                <input type="date" id="hist_modal_start" class="search-box" style="width: 140px;">
                <input type="date" id="hist_modal_end" class="search-box" style="margin-left: 5px; width: 140px;">
                
                <div class="history-filter-container">
                    <button onclick="toggleHistoryFilterMenu()" style="background:#2c3e50; color:white; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; font-size:12px; cursor:pointer; margin-left:10px;">Filter Status ⇩</button>
                    <div id="historyFilterMenu" class="history-filter-dropdown">
                        <label><input type="checkbox" class="hist-status-cb" value="Present" onchange="applyHistoryFilters()"> Present</label>
                        <label><input type="checkbox" class="hist-status-cb" value="Absent" onchange="applyHistoryFilters()"> Absent</label>
                        <label><input type="checkbox" class="hist-status-cb" value="Late" onchange="applyHistoryFilters()"> Late</label>
                        <label><input type="checkbox" class="hist-status-cb" value="Tardy" onchange="applyHistoryFilters()"> Tardy</label>
                        <label><input type="checkbox" class="hist-status-cb" value="On Leave" onchange="applyHistoryFilters()"> On Leave</label>
                        <label><input type="checkbox" class="hist-status-cb" value="Overtime" onchange="applyHistoryFilters()"> Overtime</label>
                        <label><input type="checkbox" class="hist-status-cb" value="Undertime" onchange="applyHistoryFilters()"> Undertime</label>
                        <label><input type="checkbox" class="hist-status-cb" value="Duty on Rest Day" onchange="applyHistoryFilters()"> Duty on Rest Day</label>
                    </div>
                </div>
                
                <div id="activeHistoryFilters" style="display:flex; gap:5px; align-items:center; flex-wrap:wrap; margin-left:10px;"></div>
                <button class="submit-btn" style="width: auto; padding: 6px 15px; margin:0 0 0 10px;" onclick="filterMemberHistory()">Go</button>
            </div>
            
            <div class="modal-body">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Break In</th>
                            <th>Break Out</th>
                            <th>Lunch</th>
                            <th>Hours Worked</th>
                        </tr>
                    </thead>
                    <tbody id="modalHistoryBody"></tbody>
                </table>
            </div>
            
            <div style="padding:10px; background:#fff; text-align:right; border-top:1px solid #ddd; color:#999; font-size:11px;">Total Hours: <span id="totalHoursDisplay">0.00</span></div>
        </div>
    </div>

    <script>
        const API = "<?php echo $api_base_url; ?>";
        const COACH_ID = document.getElementById('coach_id').value;

        function switchView(viewId, btn) {
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('active-view');
            btn.classList.add('nav-active');
            if(viewId === 'team-view') loadAttendance();
            if(viewId === 'manage-requests') loadAllEndorsements();
            if(viewId === 'my-requests-view') loadMyRequests();
            if(viewId === 'my-attendance') loadMyAttendance();
        }

        function toggleTimeInput(val) { document.getElementById('timeInputDiv').style.display = val.includes('Forgot') ? 'block' : 'none'; }

        // ✅ CALCULATE HOURS & SUMMARY STATISTICS
        function updateTableSummaries(tid) {
            let sum = 0;
            let counts = { present: 0, absent: 0, late: 0, leave: 0 };
            
            const rows = Array.from(document.getElementById(tid).tBodies[0].rows);
            rows.forEach(r => {
                if (r.style.display !== 'none' && r.cells.length > 1) { 
                    let val = parseFloat(r.cells[r.cells.length - 1].innerText);
                    if (!isNaN(val)) sum += val;
                    
                    let status = r.cells[r.cells.length - 2].innerText.toLowerCase();
                    if (status.includes('present')) counts.present++;
                    else if (status.includes('absent')) counts.absent++;
                    else if (status.includes('late') || status.includes('tard')) counts.late++;
                    else if (status.includes('leave')) counts.leave++;
                }
            });
            
            if(document.getElementById('totalHoursSum')) document.getElementById('totalHoursSum').innerText = sum.toFixed(2);
            if(document.getElementById('countPresent')) document.getElementById('countPresent').innerText = counts.present;
            if(document.getElementById('countAbsent')) document.getElementById('countAbsent').innerText = counts.absent;
            if(document.getElementById('countLate')) document.getElementById('countLate').innerText = counts.late;
            if(document.getElementById('countLeave')) document.getElementById('countLeave').innerText = counts.leave;
        }

        function sortTable(tid, n) {
            let table = document.getElementById(tid), tbody = table.tBodies[0], rows = Array.from(tbody.rows);
            let asc = table.getAttribute('data-asc') === 'true';
            rows.sort((a,b) => {
                let v1 = a.cells[n].innerText.toLowerCase(), v2 = b.cells[n].innerText.toLowerCase();
                let num1 = parseFloat(v1.replace(/[^0-9.-]+/g,"")), num2 = parseFloat(v2.replace(/[^0-9.-]+/g,""));
                let date1 = Date.parse(v1), date2 = Date.parse(v2);

                if (!isNaN(date1) && !isNaN(date2) && v1.includes('-')) return asc ? date1 - date2 : date2 - date1;
                if (!isNaN(num1) && !isNaN(num2)) return asc ? num1 - num2 : num2 - num1;
                return asc ? v1.localeCompare(v2) : v2.localeCompare(v1);
            });
            rows.forEach(r => tbody.appendChild(r));
            table.setAttribute('data-asc', !asc);
        }
        
        function filterTeamTable() {
            let status = document.getElementById('teamStatusFilter').value.toLowerCase();
            let name = document.getElementById('coachSearch').value.toLowerCase();
            let rows = document.getElementById('attendanceLogs').rows;
            for (let r of rows) {
                let rName = r.cells[0].innerText.toLowerCase();
                let rStatus = r.cells[4].innerText.toLowerCase(); 
                let show = true;
                if (status && !rStatus.includes(status)) show = false;
                if (name && !rName.includes(name)) show = false;
                r.style.display = show ? '' : 'none';
            }
        }

        function filterTable(tid, val) {
            let filter = val.toLowerCase();
            let rows = document.getElementById(tid).tBodies[0].rows;
            Array.from(rows).forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
            if (tid === 'myAttTable') updateTableSummaries(tid);
        }

        async function loadAttendance() {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('attendanceLogs').innerHTML = data.length ? data.map(log => `
                <tr>
                    <td style="padding:15px;"><span class="clickable-name" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name} ${log.last_name}')">${log.first_name} ${log.last_name}</span></td>
                    <td>${log.attendance_date||'-'}</td>
                    <td>${log.time_in||'-'}</td>
                    <td>${log.time_out||'-'}</td>
                    <td><span class="status-pill status-${(log.attendance_status||'').replace(/\s/g,'')}">${log.attendance_status||'-'}</span></td>
                    <td><span class="action-icon" style="color:#3498db;" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name} ${log.last_name}')">👁️</span></td>
                </tr>`).join('') : '<tr><td colspan="6" style="text-align:center;">No records found.</td></tr>';
        }

        async function loadMyRequests() {
            const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${COACH_ID}`);
            const data = await res.json();
            if(data.error) return;
            
            const leaves = data.filter(item => item.type === 'Leave');
            const overtime = data.filter(item => item.type === 'Overtime');
            const disputes = data.filter(item => item.type === 'Dispute');
            
            document.getElementById("myLeaveLogs").innerHTML = leaves.length ? leaves.map(i => `<tr><td>${i.sub_type}</td><td>${i.start_date}<br>${i.end_date}</td><td>${i.reason}</td><td><span class="status-pill status-${i.status}">${i.status}</span></td><td>${i.created_at}</td><td>${i.approver || '-'}</td></tr>`).join('') : '<tr><td colspan="6" style="text-align:center;">No records</td></tr>';
            document.getElementById("myOTLogs").innerHTML = overtime.length ? overtime.map(i => `<tr><td>${i.sub_type}</td><td>${i.start_date}<br>${i.end_date}</td><td>${i.reason}</td><td><span class="status-pill status-${i.status}">${i.status}</span></td><td>${i.created_at}</td><td>${i.approver || '-'}</td></tr>`).join('') : '<tr><td colspan="6" style="text-align:center;">No records</td></tr>';
            document.getElementById("myDisputeLogs").innerHTML = disputes.length ? disputes.map(i => `<tr><td>${i.sub_type}</td><td>${i.start_date}</td><td>${i.reason}</td><td><span class="status-pill status-${i.status}">${i.status}</span></td><td>${i.created_at}</td><td style="color:blue; font-style:italic;">${i.remarks || '--'}</td></tr>`).join('') : '<tr><td colspan="6" style="text-align:center;">No records</td></tr>';
        }

        function loadAllEndorsements() { loadLeaves(); loadOT(); loadDisputes(); }

        async function loadLeaves() { const res = await fetch(`${API}/management/get_pending_leaves.php?user_id=${COACH_ID}`); const data = await res.json(); document.getElementById('coachLeaveList').innerHTML = data.length ? data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.reason}"</td><td><span class="action-icon" style="color:green;" onclick="endorse(${item.leave_id}, 'leave', 'ENDORSE')">✔</span></td></tr>`).join('') : '<tr><td colspan="3" style="text-align:center;">No pending leaves.</td></tr>'; }
        async function loadOT() { const res = await fetch(`${API}/management/get_pending_ot.php?user_id=${COACH_ID}`); const data = await res.json(); document.getElementById('coachOTList').innerHTML = data.length ? data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.purpose}"</td><td><span class="action-icon" style="color:green;" onclick="endorse(${item.ot_id}, 'ot', 'ENDORSE')">✔</span></td></tr>`).join('') : '<tr><td colspan="3" style="text-align:center;">No pending overtime.</td></tr>'; }
        
        async function loadDisputes() { 
            const res = await fetch(`${API}/management/get_pending_disputes.php?coach_id=${COACH_ID}`); 
            const data = await res.json(); 
            document.getElementById('coachDisputeList').innerHTML = data.length ? data.map(item => `
                <tr>
                    <td><strong>${item.first_name} ${item.last_name}</strong></td>
                    <td style="font-size:10px; color:#555;">${item.role_name || 'Employee'}</td>
                    <td>${item.dispute_type || 'General'}</td>
                    <td>${item.reason}</td>
                    <td><i style="color:gray;">${item.remarks || 'No remarks'}</i></td>
                    <td>
                        <button onclick="openDisputeModal(${item.dispute_id}, '${item.first_name}', '${item.dispute_date}')" style="background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Review & Settle</button> 
                    </td>
                </tr>`).join('') : '<tr><td colspan="6" style="text-align:center;">No pending disputes.</td></tr>'; 
        }

        function openDisputeModal(id, name, date) { 
            document.getElementById('currentDisputeId').value = id; 
            document.getElementById('disputeModalContent').innerText = `Resolving for: ${name} on ${date}`; 
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
            
            await fetch(`${API}/management/resolve_dispute.php`, { method: 'POST', body: JSON.stringify(payload) }); 
            closeDisputeModal(); loadDisputes(); 
        }
        
        async function endorse(id, type, act) { 
            let endpoint = type === 'leave' ? '/management/endorse_leave.php' : '/management/endorse_overtime.php'; 
            await fetch(`${API}${endpoint}`, { method: 'POST', body: JSON.stringify({ [type === 'leave' ? 'leave_id' : 'ot_id']: id, action: act, coach_id: COACH_ID }) }); 
            loadAllEndorsements();
        }
        
        async function submitRequest(type) { 
            let endpoint, payload, formId; 
            if (type === 'leave') { 
                if (!document.getElementById('l_agree1').checked || !document.getElementById('l_agree2').checked) { alert("⚠️ Agree to terms."); return; }
                endpoint = '/users/file_leave.php'; formId = 'leaveForm'; 
                payload = { leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, employee_id: COACH_ID }; 
            } else if (type === 'ot') { 
                if (!document.getElementById('ot_agree1').checked || !document.getElementById('ot_agree2').checked) { alert("⚠️ Agree to terms."); return; }
                endpoint = '/users/file_overtime.php'; formId = 'otForm';
                payload = { ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, employee_id: COACH_ID }; 
            } else if (type === 'dispute') { 
                endpoint = '/users/submit_dispute.php'; formId = 'disputeForm';
                payload = { cluster_id: document.getElementById('auto_cluster_id').value, dispute_date: document.getElementById('d_date').value, dispute_type: document.getElementById('d_type').value, reason: document.getElementById('d_reason').value, time_in: document.getElementById('d_time_in').value, time_out: document.getElementById('d_time_out').value, employee_id: COACH_ID }; 
            } 
            const res = await fetch(`${API}${endpoint}`, { method:'POST', body:JSON.stringify(payload) }); 
            const r = await res.json(); alert(r.success||r.error); 
            if(r.success) { document.getElementById(formId).reset(); loadMyRequests(); } 
        }
        
        async function loadMyAttendance() { 
            const s = document.getElementById('range_start').value, e = document.getElementById('range_end').value;
            const r = await fetch(`${API}/management/get_member_attendance.php?employee_id=${COACH_ID}&start_date=${s}&end_date=${e}`); 
            const d = await r.json(); 
            document.getElementById('myAttendanceBody').innerHTML = d.length ? d.map(x => `<tr><td>${x.date}</td><td>${x.time_in||'--'}</td><td>${x.time_out||'--'}</td><td>${x.break_in||'--'}</td><td>${x.break_out||'--'}</td><td><span class="status-pill status-${(x.status||'').replace(/\s/g,'')}">${x.status}</span></td><td>${x.total_hours||'0.00'}</td></tr>`).join('') : '<tr><td colspan="7">No records</td></tr>'; 
            updateTableSummaries('myAttTable');
        }

        async function viewMemberHistory(empId, name) {
            document.getElementById('modalTitle').innerText = `${name} - History`;
            document.getElementById('current_view_id').value = empId; 
            document.getElementById('historyModal').style.display = 'flex';
            filterMemberHistory();
        }

        async function filterMemberHistory() {
            const empId = document.getElementById('current_view_id').value;
            const res = await fetch(`${API}/management/get_member_attendance.php?employee_id=${empId}`);
            const data = await res.json();
            document.getElementById('modalHistoryBody').innerHTML = data.map(row => `<tr><td>${row.date}</td><td><span class="status-pill status-${(row.status||'').replace(/\s/g,'')}">${row.status}</span></td><td>${row.time_in}</td><td>${row.time_out}</td><td>${row.break_in}</td><td>${row.break_out}</td><td>${row.lunch_break}</td><td>${row.total_hours}</td></tr>`).join('');
        }

        function toggleHistoryFilterMenu() { const menu = document.getElementById('historyFilterMenu'); menu.style.display = menu.style.display === 'block' ? 'none' : 'block'; }
        function closeModal() { document.getElementById('historyModal').style.display = 'none'; }
        function applyHistoryFilters() { filterMemberHistory(); }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value=s; document.getElementById('range_end').value=e; 
            loadAttendance();
        };
    </script>
</body>
</html>