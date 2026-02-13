<?php
// FILE: admin_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header("Content-Type: text/html; charset=UTF-8");
require_once 'api/config/db.php'; 
require_once 'api/middleware/auth.php';

// Verify Admin Access
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 4)) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Administrator';
$user_id = $_SESSION['employee_id'];
$api_base_url = "http://localhost/hris_official/api"; 

// HANDLE SAVING (POST) for Manual Edit Attendance Overlay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_status'])) {
    $id = $_POST['attendance_id'];
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE attendance SET attendance_status = ? WHERE attendance_id = ?");
        $stmt->execute([$status, $id]);
        header("Location: admin_dashboard.php"); exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// LOGIC: PREPARE EDIT DATA (GET)
$edit_mode = false;
$edit_record = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name FROM attendance a JOIN employees e ON a.employee_id = e.employee_id WHERE a.attendance_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_record) $edit_mode = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iREPLY - Admin Dashboard</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #222; --text-gray: #666; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); overflow: hidden; }
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 40px; }
        .user-info { font-size: 11px; font-weight: bold; margin-bottom: 25px; color: #27ae60; text-align: center; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; text-decoration: none; display: block; }
        .nav-item:hover { background: #f0f4f8; color: var(--primary-blue); }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        .logout-btn { color: #e74c3c !important; font-weight: bold; }
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        .header { background: var(--primary-blue); color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 30px 40px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px 10px; font-size: 11px; color: #888; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 15px 10px; border-bottom: 1px solid #f9f9f9; font-size: 13px; color: #333; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Endorsed { background: #e3f2fd; color: #3498db; }
        .status-Approved { background: #e8f5e9; color: #27ae60; }
        .status-Denied { background: #ffebee; color: #e74c3c; }
        
        .date-input-small { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 5px; border-radius: 4px; font-size: 13px; }
        .search-box { padding: 8px 12px; border-radius: 20px; border: none; font-size: 13px; width: 200px; margin-right: 15px; outline: none; }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; margin-left: 10px; display: inline-block; text-decoration: none; }
        .action-icon { cursor: pointer; font-size: 16px; margin-right: 10px; text-decoration: none; display: inline-block; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 0; border-radius: 4px; width: 1000px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .modal-header { background: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .modal-title { font-size: 16px; font-weight: bold; color: #333; }
        .modal-body { padding: 0; overflow-y: auto; background: #f9f9f9; }
        .close-btn { cursor: pointer; font-size: 24px; color: #999; }
        
        .history-table th { background: #eee; color: #333; font-weight: bold; font-size: 12px; border-bottom: 2px solid #ddd; padding: 12px; }
        .history-table td { background: #fff; color: #555; font-size: 12px; border-bottom: 1px solid #eee; padding: 12px; vertical-align: middle; }
        
        .clickable-name { color: #1e4d8c; font-weight: bold; cursor: pointer; text-decoration: underline; }
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; }
        textarea { grid-column: span 2; }
        .submit-btn { padding: 12px; width: 100%; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); }
        
        .overlay { display: <?php echo $edit_mode ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
        .modal-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 400px; animation: fadeIn 0.3s; }
    </style>
</head>
<body>
    <input type="hidden" id="admin_id" value="<?php echo $user_id; ?>">
    <input type="hidden" id="current_view_id" value="">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="user-info">Administrator: <?php echo htmlspecialchars($user_name); ?></div>
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
                    <input type="text" id="adminSearch" class="search-box" placeholder="🔍 Search name..." onkeyup="filterTable('masterTable', 'adminSearch')">
                    <button class="export-btn" onclick="exportData('ALL')">📂 Export All Excel</button>
                </div>
            </div>
            <div class="container">
                <table id="masterTable">
                    <thead id="masterTableHead">
                        <tr><th>Personnel</th><th>Last Active</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="masterLogsBody"></tbody>
                </table>
            </div>
        </div>

        <div id="approvals-view" class="view-content">
            <div class="header"><h2>✅ Final Sign-offs</h2></div>
            <div class="container">
                <h3>Leaves</h3>
                <table id="adminLeaveTable"><thead><tr><th>Employee</th><th>Date Range</th><th>Reason</th><th>Endorsed By</th><th>Action</th></tr></thead><tbody id="adminLeaveQueue"></tbody></table>
                
                <h3 style="margin-top:40px;">Overtime</h3>
                <table id="adminOTTable"><thead><tr><th>Employee</th><th>Date/Time</th><th>Purpose</th><th>Endorsed By</th><th>Action</th></tr></thead><tbody id="adminOTQueue"></tbody></table>

                <h3 style="margin-top:40px; color:#e74c3c;">Attendance Disputes</h3>
                <table id="adminDisputeTable"><thead><tr><th>Employee</th><th>Type</th><th>Reason</th><th>Action</th></tr></thead><tbody id="adminDisputeQueue"></tbody></table>
            </div>
        </div>

        <div id="history-view" class="view-content">
            <div class="header">
                <h2 style="margin:0;">📜 Request History</h2>
                <div style="display:flex; gap:10px; align-items:center;">
                    <select id="hist_status" style="padding:8px; border-radius:5px;"><option value="ALL">All Status</option><option value="Pending">Pending</option><option value="Endorsed">Endorsed</option><option value="Approved">Approved</option><option value="Denied">Denied</option></select>
                    <input type="date" id="hist_start" class="date-input-small"><span style="color:white;">to</span><input type="date" id="hist_end" class="date-input-small"><button class="export-btn" onclick="loadRequestHistory()">Filter</button>
                </div>
            </div>
            <div class="container">
                <table id="historyTable">
                    <thead><tr><th>Employee</th><th>Type</th><th>Details</th><th>Date Range</th><th>Status</th><th>Filed On</th></tr></thead>
                    <tbody id="historyBody"></tbody>
                </table>
            </div>
        </div>

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;"><h2 style="margin:0;">My Request Status</h2><button class="export-btn" onclick="loadMyRequests()">🔄 Refresh</button></div>
            <div class="container">
                <h3 style="color:#666;">My Leave Requests</h3><table><thead><tr><th>Type</th><th>Date Range</th><th>Reason</th><th>Status</th><th>Filed On</th><th>Approved By</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666; margin-top:40px;">My Overtime Requests</h3><table><thead><tr><th>Type</th><th>Time Range</th><th>Purpose</th><th>Status</th><th>Filed On</th><th>Approved By</th></tr></thead><tbody id="myOTLogs"></tbody></table>
                <h3 style="color:#666; margin-top:40px;">My Disputes</h3><table><thead><tr><th>Type</th><th>Date</th><th>Reason</th><th>Status</th><th>Filed On</th></tr></thead><tbody id="myDisputeLogs"></tbody></table>
            </div>
        </div>

        <div id="admin-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div class="form-card"><h3>📝 Leave</h3><form id="leaveForm" class="form-grid"><select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select><div></div><input type="date" id="l_start"><input type="date" id="l_end"><textarea id="l_reason" placeholder="Reason..."></textarea><button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit</button></form></div>
                <div class="form-card" style="border-left: 5px solid #27ae60;"><h3>⏰ Overtime</h3><form id="otForm" class="form-grid"><select id="ot_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select><div></div><input type="datetime-local" id="ot_start"><input type="datetime-local" id="ot_end"><textarea id="ot_purpose" placeholder="Purpose..."></textarea><button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit</button></form></div>
                <div class="form-card" style="border-left: 5px solid #e74c3c;"><h3 style="color: #e74c3c;">Attendance Dispute</h3><form id="disputeForm" class="form-grid"><input type="text" value="Self-Filing" class="readonly-field" readonly style="background:#eee;"><select id="d_type" required><option value="" disabled selected>Select Dispute Type</option><option>Forgot Time In/Out</option><option>System Error</option><option>Official Business</option><option>Incorrect Status</option><option>Breaktime</option><option>Lunch Break</option></select><div style="grid-column: span 2;"><label style="font-weight:bold;">Date of Incident:</label><input type="date" id="d_date" required></div><textarea id="d_reason" placeholder="Explain..." rows="3"></textarea><button type="button" class="submit-btn" style="background:#e74c3c;" onclick="submitRequest('dispute')">Submit Dispute</button></form></div>
            </div>
        </div>

        <div id="my-attendance" class="view-content">
            <div class="header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <button class="export-btn" onclick="exportData('MY')">📂 Export Excel</button>
                    <h2 style="margin:0;">Attendance History</h2>
                </div>
                <div style="display:flex;"><input type="date" id="my_start" onchange="loadMyAttendance()"><input type="date" id="my_end" style="margin-left:5px;" onchange="loadMyAttendance()"></div>
            </div>
            <div class="container"><table><thead><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th><th>Hrs</th></tr></thead><tbody id="myAttendanceBody"></tbody></table></div>
        </div>
    </div>

    <div id="disputeModal" class="modal"><div class="modal-box" style="width: 400px;"><div class="modal-header">Resolution Decision</div><div id="disputeModalContent" style="padding:15px; font-size:14px; background:#f9f9f9; margin-bottom:10px;"></div><label style="display:block; margin-bottom:5px; font-weight:bold;">Correct Attendance Status:</label><select id="newDisputeStatus" style="width:100%; padding:10px; margin-bottom:20px;"><option value="Present">Present</option><option value="Late">Late</option><option value="Absent">Absent</option><option value="Overtime">Overtime</option><option value="On Leave">On Leave</option></select><input type="hidden" id="currentDisputeId"><button onclick="confirmDispute()" style="width:100%; padding:10px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer;">Confirm Update</button><a onclick="closeDisputeModal()" style="display:block; width:100%; text-align:center; margin-top:10px; cursor:pointer;">Cancel</a></div></div>
    
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Employee History</div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <button class="export-btn" onclick="exportCurrentModalUser()">Export Excel</button>
                    <span class="close-btn" onclick="closeModal()">×</span>
                </div>
            </div>
            <div class="modal-body"><table class="history-table"><thead><tr><th>Date</th><th>Status</th><th>Time In</th><th>Time Out</th><th>Lunch Break</th><th>Break Time</th><th>Hours Worked</th><th>Overtime</th></tr></thead><tbody id="modalHistoryBody"></tbody></table></div>
            <div style="padding:10px; background:#fff; text-align:right; border-top:1px solid #ddd; color:#999; font-size:11px;">Total Hours: <span id="totalHoursDisplay">0.00</span></div>
        </div>
    </div>

    <?php if ($edit_mode && $edit_record): ?>
    <div class="overlay"><div class="modal-box"><div class="modal-header">✏️ Edit Attendance</div><form method="POST" action="admin_dashboard.php"><input type="hidden" name="attendance_id" value="<?php echo $edit_record['attendance_id']; ?>"><select name="status" style="width:100%; padding:10px;"><option value="Present">Present</option><option value="Absent">Absent</option><option value="Late">Late</option></select><button type="submit" name="save_status" style="width:100%; padding:10px; background:#27ae60; color:white; margin-top:10px;">Save</button></form></div></div>
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

        async function refreshTable() {
            if (currentMode === 'TEAM') { loadTeamRoster(currentCoachId); return; }
            let url = `${API}/admin/get_all_attendance.php`;
            document.getElementById('viewLabel').innerText = "Coaches & Admins";
            document.getElementById('backBtn').style.display = 'none';
            document.getElementById('masterTableHead').innerHTML = `<tr><th>Personnel</th><th>Last Active</th><th>Status</th><th>Actions</th></tr>`;
            try {
                const res = await fetch(url); const data = await res.json();
                const tbody = document.getElementById("masterLogsBody");
                if(data.length === 0) { tbody.innerHTML = `<tr><td colspan='4' style='text-align:center;'>No records found.</td></tr>`; return; }
                tbody.innerHTML = data.map(log => {
                    let nameDisplay = `<strong>${log.first_name} ${log.last_name}</strong>`;
                    // 🔥 DOWNLOAD ICON (📥) ADDED HERE
                    let actions = `<span class="action-icon" style="color:#3498db;" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name}')">👁️</span>
                                   <span class="action-icon" style="color:#27ae60;" onclick="exportSingleEmployee(${log.employee_id})">📥</span>`;
                    
                    if (log.role_id == 2) nameDisplay = `<span class="clickable-name" onclick="viewTeam(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name} (Coach)</span>`;
                    if (log.latest_id) actions += `<a href="admin_dashboard.php?edit_id=${log.latest_id}" class="action-icon">✏️</a>`;
                    return `<tr><td>${nameDisplay}</td><td>${log.latest_date||'-'}</td><td><span class="status-pill status-${(log.latest_status||'').replace(/\s/g,'')}">${log.latest_status||'Inactive'}</span></td><td>${actions}</td></tr>`;
                }).join('');
                filterTable('masterTable', 'adminSearch');
            } catch(e) { console.error("Error:", e); }
        }

        async function viewTeam(coachId, coachName) {
            currentMode = 'TEAM'; currentCoachId = coachId;
            document.getElementById('viewLabel').innerText = `Team: ${coachName}`;
            document.getElementById('backBtn').style.display = 'inline-block';
            document.getElementById('masterTableHead').innerHTML = `<tr><th>Employee</th><th>Last Active</th><th>Status</th></tr>`;
            loadTeamRoster(coachId);
        }

        async function loadTeamRoster(coachId) {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${coachId}`);
            const data = await res.json();
            document.getElementById("masterLogsBody").innerHTML = data.map(log => `<tr><td><span class="clickable-name" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name}</span></td><td>${log.latest_date||'-'}</td><td>${log.latest_status||'-'}</td></tr>`).join('');
        }

        function resetToLeaders() { currentMode = 'COACHES'; currentCoachId = null; refreshTable(); }

        // --- APPROVALS ---
        async function loadApprovals() {
            const [leaveRes, otRes, dispRes] = await Promise.all([fetch(`${API}/admin/get_endorsed_leaves.php`), fetch(`${API}/admin/get_endorsed_ot.php`), fetch(`${API}/admin/get_endorsed_disputes.php`)]);
            renderQueue(await leaveRes.json(), 'adminLeaveQueue', 'leave');
            renderQueue(await otRes.json(), 'adminOTQueue', 'ot');
            renderDisputes(await dispRes.json());
        }

        function renderQueue(items, id, type) {
            document.getElementById(id).innerHTML = items.length ? items.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>${item.start_date||item.start_time}</td><td>"${item.reason||item.purpose}"</td><td style="font-weight:bold; color:#e67e22;">${item.endorser_name||'-'}</td><td><span class="action-icon" style="color:green;" onclick="finalApprove(${item.leave_id||item.ot_id}, '${type}', 'APPROVE')">✔</span> <span class="action-icon" style="color:red;" onclick="finalApprove(${item.leave_id||item.ot_id}, '${type}', 'DENY')">❌</span></td></tr>`).join('') : `<tr><td colspan='5' style='text-align:center; color:#999;'>No pending items</td></tr>`;
        }

        function renderDisputes(items) {
            document.getElementById('adminDisputeQueue').innerHTML = items.length ? items.map(d => `<tr><td><strong>${d.first_name} ${d.last_name}</strong></td><td>${d.dispute_type}</td><td>${d.reason}</td><td><button onclick="openDisputeModal(${d.dispute_id}, '${d.first_name}', '${d.dispute_date}')" style="background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Review</button> <span class="action-icon" style="color:red; margin-left:10px;" onclick="denyDispute(${d.dispute_id})">❌</span></td></tr>`).join('') : `<tr><td colspan='4' style='text-align:center; color:#999;'>No pending disputes</td></tr>`;
        }

        function openDisputeModal(id, name, date) {
            document.getElementById('currentDisputeId').value = id;
            document.getElementById('disputeModalContent').innerText = `Resolving dispute for ${name} on ${date}.`;
            document.getElementById('disputeModal').style.display = 'flex';
        }
        function closeDisputeModal() { document.getElementById('disputeModal').style.display = 'none'; }
        
        async function confirmDispute() {
            const id = document.getElementById('currentDisputeId').value;
            const newStatus = document.getElementById('newDisputeStatus').value;
            await fetch(`${API}/admin/resolve_dispute.php`, { method: 'POST', body: JSON.stringify({ dispute_id: id, action: 'APPROVE', new_status: newStatus }) });
            closeDisputeModal(); loadApprovals();
        }
        async function denyDispute(id) {
            if(!confirm("Deny this dispute?")) return;
            await fetch(`${API}/admin/resolve_dispute.php`, { method: 'POST', body: JSON.stringify({ dispute_id: id, action: 'DENY' }) });
            loadApprovals();
        }

        async function finalApprove(id, type, action) {
            if(!confirm(`${action} this request?`)) return;
            const endpoint = type === 'leave' ? '/admin/final_approve_leave.php' : '/admin/final_approve_overtime.php';
            await fetch(`${API}${endpoint}?admin_id=${MY_ID}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ [type + '_id']: id, action: action }) });
            loadApprovals();
        }

        async function loadRequestHistory() {
            const start = document.getElementById('hist_start').value, end = document.getElementById('hist_end').value, status = document.getElementById('hist_status').value;
            const res = await fetch(`${API}/admin/get_request_history.php?start_date=${start}&end_date=${end}&status=${status}`);
            const data = await res.json();
            document.getElementById('historyBody').innerHTML = data.length ? data.map(item => `<tr><td>${item.employee_name}</td><td>${item.category} (${item.type})</td><td>${item.details}</td><td>${item.date_start} to ${item.date_end}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td></tr>`).join('') : "<tr><td colspan='6' style='text-align:center;'>No records found.</td></tr>";
        }

        async function viewMemberHistory(empId, name) {
            document.getElementById('modalTitle').innerText = `${name} - History`;
            document.getElementById('current_view_id').value = empId; // Store for Export
            document.getElementById('historyModal').style.display = 'flex';
            const res = await fetch(`${API}/management/get_member_attendance.php?employee_id=${empId}`);
            const data = await res.json();
            const totalHours = data.reduce((acc, row) => {
                let hrs = parseFloat((row.hours || '0').toString().replace(/,/g, ''));
                return acc + (isNaN(hrs) ? 0 : hrs);
            }, 0);
            document.getElementById('totalHoursDisplay').innerText = totalHours.toFixed(2);
            document.getElementById('modalHistoryBody').innerHTML = data.map(row => `<tr><td>${row.date}</td><td><span class="status-pill status-${row.status}">${row.status}</span></td><td>${row.time_in}</td><td>${row.time_out}</td><td>${row.lunch_break}</td><td>${row.break_time}</td><td>${row.hours}</td><td>${row.overtime}</td></tr>`).join('');
        }
        function closeModal() { document.getElementById('historyModal').style.display = 'none'; }
        window.onclick = function(event) { if (event.target == document.getElementById('historyModal')) closeModal(); }

        function filterTable(tableId, inputId) {
            const input = document.getElementById(inputId), filter = input.value.toLowerCase(), rows = document.getElementById(tableId).getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) { 
                const cell = rows[i].getElementsByTagName("td")[0];
                if (cell) rows[i].style.display = cell.innerText.toLowerCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
        
        function exportData(mode) { window.location.href = `${API}/export/export_excel.php?mode=${mode}`; }
        function exportSingleEmployee(empId) { window.location.href = `${API}/export/export_excel.php?mode=SINGLE&employee_id=${empId}`; }
        function exportCurrentModalUser() { 
            const id = document.getElementById('current_view_id').value;
            if(id) exportSingleEmployee(id); 
        }

        async function loadMyRequests() {
            const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${MY_ID}`);
            const data = await res.json();
            const leaves = data.filter(item => item.type === 'Leave');
            const overtime = data.filter(item => item.type === 'Overtime');
            const disputes = data.filter(item => item.type === 'Dispute');
            const getApprover = (item) => item.admin_first ? `<span style="color:#27ae60; font-weight:600;">${item.admin_first} ${item.admin_last}</span>` : '<span style="color:#ccc;">-</span>';
            const renderRow = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}<br>${item.end_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td><td>${getApprover(item)}</td></tr>`;
            const renderDisp = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td></tr>`;
            
            document.getElementById("myLeaveLogs").innerHTML = leaves.map(renderRow).join('');
            document.getElementById("myOTLogs").innerHTML = overtime.map(renderRow).join('');
            document.getElementById("myDisputeLogs").innerHTML = disputes.map(renderDisp).join('');
        }

        async function loadMyAttendance() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('my_start').value = s; document.getElementById('my_end').value = e;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${MY_ID}&start_date=${s}&end_date=${e}`);
            const data = await res.json();
            document.getElementById('myAttendanceBody').innerHTML = data.map(row => `<tr><td>${row.attendance_date}</td><td>${row.time_in || '--:--'}</td><td>${row.time_out || '--:--'}</td><td>${row.attendance_status}</td><td>${row.total_hours || '0.00'} hrs</td></tr>`).join('');
        }
        
        async function submitRequest(type) {
            let endpoint, payload, formId;
            if (type === 'leave') {
                endpoint = '/users/file_leave.php'; formId = 'leaveForm';
                payload = { employee_id: MY_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1: 1, agreement_2: 1 };
            } else if (type === 'ot') {
                // 🔥 2-HOUR LIMIT CHECK
                const start = new Date(document.getElementById('ot_start').value);
                const end = new Date(document.getElementById('ot_end').value);
                const diffMs = end - start;
                const diffHrs = diffMs / (1000 * 60 * 60);
                if (diffHrs > 2) { alert("⚠️ Cannot submit: Overtime is limited to 2 hours per request."); return; }

                endpoint = '/users/file_overtime.php'; formId = 'otForm';
                payload = { employee_id: MY_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1: 1, agreement_2: 1 };
            } else if (type === 'dispute') {
                endpoint = '/users/file_dispute.php'; formId = 'disputeForm';
                // 🆕 New Dropdown Value Included
                payload = { employee_id: MY_ID, date: document.getElementById('d_date').value, dispute_type: document.getElementById('d_type').value, reason: document.getElementById('d_reason').value };
            }

            try {
                const res = await fetch(`${API}${endpoint}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const result = await res.json();
                alert(result.success || result.error);
                if(result.success) { document.getElementById(formId).reset(); loadMyRequests(); }
            } catch (err) { alert("Submission failed."); }
        }
        
        function exportData() {
            const start = document.getElementById('range_start').value;
            const end = document.getElementById('range_end').value;
            window.location.href = `${API}/export/export_excel.php?mode=MY&start=${start}&end=${end}`;
        }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            if(document.getElementById('hist_start')) { document.getElementById('hist_start').value = s; document.getElementById('hist_end').value = e; }
            refreshTable(); loadApprovals(); loadMyAttendance();
        };
    </script>
</body>
</html>