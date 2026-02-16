<?php
// FILE: coach_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'api/config/db.php'; 
require_once 'api/middleware/auth.php';
verifyAccess([2]); // Coach Access Only

$api_base_url = "http://localhost/hris_official/api"; 
$coach_id = $_SESSION['employee_id'];

// FETCH COACH NAME
$coach_name = "Coach";
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $stmt->execute([$coach_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) { $coach_name = $user['first_name'] . ' ' . $user['last_name']; }
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

        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        
        .header { background: var(--primary-blue); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px 40px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #f9f9f9; font-size: 12px; }
        th { cursor: pointer; background-color: #f8f9fa; font-weight: 600; color: #555; position: relative; }
        th:hover { background: #ebedef; }
        
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Approved { background: #e8f5e9; color: #2e7d32; }
        .status-Denied { background: #ffebee; color: #c62828; }
        .status-Endorsed { background: #e3f2fd; color: #1565c0; }
        .status-Present { background: #e8f5e9; color: #2e7d32; }
        .status-Late, .status-Tardy { background: #fff3e0; color: #e67e22; }
        .status-Absent { background: #ffebee; color: #c62828; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 0; border-radius: 4px; width: 1000px; max-height: 90vh; display: flex; flex-direction: column; }
        .modal-header { background: #fff; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;}
        .modal-title { font-weight: bold; color: #333; }
        .close-x { font-size: 24px; cursor: pointer; color: #999; }
        
        .history-table th { background: #eee; color: #333; font-weight: bold; font-size: 12px; border-bottom: 2px solid #ddd; padding: 10px; }
        .history-table td { background: #fff; color: #555; font-size: 12px; border-bottom: 1px solid #eee; padding: 10px; vertical-align: middle; }
        .clickable-name { color: #1e4d8c; font-weight: bold; cursor: pointer; text-decoration: underline; }
        
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 8px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 13px; }
        textarea { grid-column: span 2; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); }
        
        .modal-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 450px; animation: fadeIn 0.3s; }
        
        .search-box { padding: 6px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; outline: none; margin-left: 10px; background: #fff; }
        .export-btn { background: #27ae60; border: none; padding: 6px 12px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 12px; margin-left: 10px; }
        
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
    </style>
</head>
<body>
    <input type="hidden" id="coach_id" value="<?php echo $coach_id; ?>">
    <input type="hidden" id="current_view_id" value="">

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
        <a href="logout.php" class="nav-item" style="color: #e74c3c;">Log Out</a>
    </div>

    <div class="main-content">
        <div id="team-view" class="view-content active-view">
            <div class="header">
                <h2>👥 Team Members</h2>
                <div style="display:flex; align-items:center;">
                    <button class="export-btn" style="background:#f39c12;" onclick="loadAttendance()">Refresh</button>
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
                <button class="export-btn" style="background:#f39c12;" onclick="loadAllEndorsements()">Refresh </button>
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
                    <thead><tr onclick="sortTable('disputeEndorseTable', 0)"><th>Employee ⬍</th><th>Role</th><th>Type ⬍</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody id="coachDisputeList"></tbody>
                </table>
            </div>
        </div>

        <div id="my-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                    <div class="form-card"><h3>📝 Leave</h3><form id="leaveForm" class="form-grid"><select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select><div></div><input type="date" id="l_start"><input type="date" id="l_end"><textarea id="l_reason" placeholder="Reason..."></textarea><button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit</button></form></div>
                    <div class="form-card" style="border-left: 5px solid #27ae60;"><h3>⏰ Overtime</h3><form id="otForm" class="form-grid"><select id="ot_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select><div></div><input type="datetime-local" id="ot_start"><input type="datetime-local" id="ot_end"><textarea id="ot_purpose" placeholder="Purpose..."></textarea><button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit</button></form></div>
                </div>
                <div class="form-card" style="border-left: 5px solid #e74c3c;">
                    <h3 style="color: #e74c3c;">Attendance Dispute</h3>
                    <form id="disputeForm" class="form-grid">
                        <input type="text" value="Self-Filing" class="readonly-field" readonly>
                        <select id="d_type" required onchange="toggleTimeInput(this.value)">
                            <option value="" disabled selected>Select Dispute Type</option>
                            <option>Forgot Time In/Out</option>
                            <option>System Error</option>
                            <option>Official Business</option>
                            <option>Incorrect Status</option>
                            <option>Breaktime</option>
                            <option>Lunch Break</option>
                        </select>
                        <div style="grid-column: span 2;"><label style="font-size:12px; font-weight:bold;">Date of Incident:</label><input type="date" id="d_date"></div>
                        <div id="timeInputDiv" style="display:none; grid-column: span 2;">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed In:</label><input type="time" id="d_time_in"></div>
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed Out:</label><input type="time" id="d_time_out"></div>
                            </div>
                        </div>
                        <textarea id="d_reason" placeholder="Explain discrepancy..." rows="3"></textarea>
                        <button type="button" class="submit-btn" style="background:#e74c3c;" onclick="submitRequest('dispute')">Submit Dispute</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;"><h2>My Request Status</h2><button class="export-btn" style="background:#fff; color:#333;" onclick="loadMyRequests()">Refresh</button></div>
            <div class="container">
                <h3 style="color:#666;">Leave Requests</h3><table id="myLeaveTable"><thead><tr onclick="sortTable('myLeaveTable', 0)"><th>Type ⬍</th><th>Date Range ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Approver</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666; margin-top:20px;">Overtime Requests</h3><table id="myOTTable"><thead><tr onclick="sortTable('myOTTable', 0)"><th>Type ⬍</th><th>Time Range ⬍</th><th>Purpose</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Approver</th></tr></thead><tbody id="myOTLogs"></tbody></table>
                <h3 style="color:#666; margin-top:20px;">My Disputes</h3><table id="myDisputeTable"><thead><tr onclick="sortTable('myDisputeTable', 0)"><th>Type ⬍</th><th>Date ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th></tr></thead><tbody id="myDisputeLogs"></tbody></table>
            </div>
        </div>

        <div id="my-attendance" class="view-content">
            <div class="header">
                <div style="display:flex; align-items:center; gap:10px;"><h2 style="margin:0;">Attendance History</h2></div>
                <div style="display:flex;"><input type="date" id="range_start" class="search-box" style="width:130px;" onchange="loadMyAttendance()"><input type="date" id="range_end" class="search-box" style="width:130px; margin-left:5px;" onchange="loadMyAttendance()"></div>
            </div>
            <div class="container">
                <div style="margin-bottom:10px;"><select class="search-box" onchange="filterTable('myAttTable', this.value)" style="margin-left:0; width: 150px;"><option value="">Show All Statuses</option><option value="Present">Present</option><option value="Late">Late</option><option value="Tardy">Tardy</option><option value="Absent">Absent</option><option value="Overtime">Overtime</option><option value="On Leave">On Leave</option><option value="Undertime">Undertime</option><option value="Duty on Rest Day">Duty on Rest Day</option></select></div>
                <table id="myAttTable"><thead><tr onclick="sortTable('myAttTable', 0)"><th>Date ⬍</th><th>In</th><th>Out</th><th>Break In</th><th>Break Out</th><th>Status ⬍</th><th>Hrs ⬍</th></tr></thead><tbody id="myAttendanceBody"></tbody></table>
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
                    <option value="Duty on Rest Day">Duty on Rest Day</option>
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
            <div class="modal-header"><div class="modal-title" id="modalTitle">Employee History</div><span class="close-x" onclick="closeModal()">×</span></div>
            <div style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: flex-end;">
                <span style="font-size: 13px; color: #555; margin-right: 10px;">Filter Range:</span><input type="date" id="hist_modal_start" class="search-box"><input type="date" id="hist_modal_end" class="search-box" style="margin-left: 5px;">
                
                <div class="history-filter-container">
                    <button onclick="toggleHistoryFilterMenu()" style="background:#2c3e50; color:white; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; font-size:12px; cursor:pointer;">Filter Status ⇩</button>
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

                <button class="export-btn" onclick="filterMemberHistory()" style="margin-left: 10px; padding: 6px 12px;">Go</button>
            </div>
            <div class="modal-body">
                <table class="history-table"><thead><tr><th>Date</th><th>Status</th><th>Time In</th><th>Time Out</th><th>Break In</th><th>Break Out</th><th>Lunch</th><th>Hours Worked</th></tr></thead><tbody id="modalHistoryBody"></tbody></table>
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
        }

        async function loadAttendance() {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('attendanceLogs').innerHTML = data.map(log => `
                <tr>
                    <td style="padding:15px;"><span class="clickable-name" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name} ${log.last_name}')">${log.first_name} ${log.last_name}</span></td>
                    <td>${log.latest_date||'-'}</td>
                    <td>${log.time_in||'-'}</td>
                    <td>${log.time_out||'-'}</td>
                    <td><span class="status-pill status-${(log.latest_status||'').replace(/\s/g,'')}">${log.latest_status||'-'}</span></td>
                    <td><span class="action-icon" style="color:#3498db;" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name}')">👁️</span></td>
                </tr>`).join('');
        }

        async function loadMyRequests() {
            const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${COACH_ID}`);
            const data = await res.json();
            const leaves = data.filter(item => item.type === 'Leave');
            const overtime = data.filter(item => item.type === 'Overtime');
            const disputes = data.filter(item => item.type === 'Dispute');
            const getApprover = (item) => item.admin_first ? `<span style="color:#27ae60; font-weight:600;">${item.admin_first} ${item.admin_last}</span>` : '<span style="color:#ccc;">-</span>';
            
            document.getElementById("myLeaveLogs").innerHTML = leaves.length ? leaves.map(i => `<tr><td>${i.sub_type}</td><td>${i.start_date}<br>${i.end_date}</td><td>${i.reason}</td><td><span class="status-pill status-${i.status}">${i.status}</span></td><td>${i.created_at}</td><td>${getApprover(i)}</td></tr>`).join('') : '<tr><td colspan="6">No records</td></tr>';
            document.getElementById("myOTLogs").innerHTML = overtime.length ? overtime.map(i => `<tr><td>${i.sub_type}</td><td>${i.start_date}<br>${i.end_date}</td><td>${i.reason}</td><td><span class="status-pill status-${i.status}">${i.status}</span></td><td>${i.created_at}</td><td>${getApprover(i)}</td></tr>`).join('') : '<tr><td colspan="6">No records</td></tr>';
            document.getElementById("myDisputeLogs").innerHTML = disputes.length ? disputes.map(i => `<tr><td>${i.sub_type}</td><td>${i.start_date}</td><td>${i.reason}</td><td><span class="status-pill status-${i.status}">${i.status}</span></td><td>${i.created_at}</td></tr>`).join('') : '<tr><td colspan="5">No records</td></tr>';
        }

        function loadAllEndorsements() { loadLeaves(); loadOT(); loadDisputes(); }

        async function loadLeaves() { const res = await fetch(`${API}/management/get_pending_leaves.php?user_id=${COACH_ID}`); const data = await res.json(); document.getElementById('coachLeaveList').innerHTML = data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.reason}"</td><td><span style="cursor:pointer; color:green;" onclick="endorse(${item.leave_id}, 'leave', 'ENDORSE')">✔</span> <span style="cursor:pointer; color:red; margin-left:10px;" onclick="endorse(${item.leave_id}, 'leave', 'DENY')">❌</span></td></tr>`).join(''); }
        async function loadOT() { const res = await fetch(`${API}/management/get_pending_ot.php?user_id=${COACH_ID}`); const data = await res.json(); document.getElementById('coachOTList').innerHTML = data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.purpose}"</td><td><span style="cursor:pointer; color:green;" onclick="endorse(${item.ot_id}, 'ot', 'ENDORSE')">✔</span> <span style="cursor:pointer; color:red; margin-left:10px;" onclick="endorse(${item.ot_id}, 'ot', 'DENY')">❌</span></td></tr>`).join(''); }
        
        async function loadDisputes() { 
            const res = await fetch(`${API}/management/get_pending_disputes.php?coach_id=${COACH_ID}`); 
            const data = await res.json(); 
            document.getElementById('coachDisputeList').innerHTML = data.map(item => `
                <tr>
                    <td><strong>${item.first_name} ${item.last_name}</strong></td>
                    <td style="font-size:10px; color:#555;">${item.role_name || 'Employee'}</td>
                    <td>${item.dispute_type || 'General'}</td>
                    <td>${item.reason}</td>
                    <td>
                        <button onclick="openDisputeModal(${item.dispute_id}, '${item.first_name}', '${item.dispute_date}')" style="background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Review & Settle</button> 
                    </td>
                </tr>`).join(''); 
        }

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
            // Hide the status and time inputs if Denying
            document.getElementById('approvalFields').style.display = (action === 'APPROVE') ? 'block' : 'none';
        }

        async function confirmDispute() { 
            const id = document.getElementById('currentDisputeId').value; 
            const action = document.getElementById('disputeAction').value; 
            const rem = document.getElementById('actionRemarks').value; 
            
            let payload = { dispute_id: id, action: action, remarks: rem };

            // Only attach new status/time if Approving
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
            loadDisputes(); 
        }
        
        async function endorse(id, type, act) { if(!confirm(act + "?")) return; let endpoint = type === 'leave' ? '/management/endorse_leave.php' : '/management/endorse_overtime.php'; await fetch(`${API}${endpoint}`, { method: 'POST', body: JSON.stringify({ [type === 'leave' ? 'leave_id' : 'ot_id']: id, coach_id: COACH_ID, action: act }) }); if (type === 'leave') loadLeaves(); if (type === 'ot') loadOT(); }
        
        async function submitRequest(type) { 
            let form = {}; 
            if (type === 'leave') form = { employee_id: COACH_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1:1, agreement_2:1 }; 
            else if (type === 'ot') { const s = new Date(document.getElementById('ot_start').value), e = new Date(document.getElementById('ot_end').value); if ((e - s) / 36e5 > 2) { alert("Max 2 hrs"); return; } form = { employee_id: COACH_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1:1, agreement_2:1 }; } 
            else if (type === 'dispute') { 
                let r = document.getElementById('d_reason').value; 
                if(document.getElementById('d_type').value.includes('Forgot')) {
                    r += " [Proposed In: "+document.getElementById('d_time_in').value+", Proposed Out: "+document.getElementById('d_time_out').value+"]"; 
                }
                form = { employee_id: COACH_ID, date: document.getElementById('d_date').value, dispute_type: document.getElementById('d_type').value, reason: r }; 
            } 
            const endpoint = type === 'dispute' ? '/users/file_dispute.php' : `/users/file_${type}.php`; 
            const res = await fetch(`${API}${endpoint}`, { method:'POST', body:JSON.stringify(form) }); 
            const r = await res.json(); alert(r.success||r.error); 
            if(r.success) { if(type==='leave') document.getElementById('leaveForm').reset(); if(type==='ot') document.getElementById('otForm').reset(); if(type==='dispute') document.getElementById('disputeForm').reset(); loadMyRequests(); } 
        }
        
        async function loadMyAttendance() { const r = await fetch(`${API}/users/get_my_attendance.php?employee_id=${COACH_ID}&start_date=${document.getElementById('range_start').value}&end_date=${document.getElementById('range_end').value}`); const d = await r.json(); document.getElementById('myAttendanceBody').innerHTML = d.map(x => `<tr><td>${x.date}</td><td>${x.time_in||'--'}</td><td>${x.time_out||'--'}</td><td>${x.break_in}</td><td>${x.break_out}</td><td><span class="status-pill status-${x.status.replace(/\s/g,'')}">${x.status}</span></td><td>${x.total_hours||0}</td></tr>`).join(''); }

        // --- HISTORY FILTER LOGIC ---
        async function viewMemberHistory(empId, name) {
            document.getElementById('modalTitle').innerText = `${name} - History`;
            document.getElementById('current_view_id').value = empId; 
            const d = new Date(), s = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('hist_modal_start').value = s; document.getElementById('hist_modal_end').value = e;
            document.querySelectorAll('.hist-status-cb').forEach(cb => cb.checked = false); 
            document.getElementById('historyFilterMenu').style.display = 'none';
            document.getElementById('activeHistoryFilters').innerHTML = ''; 
            filterMemberHistory();
            document.getElementById('historyModal').style.display = 'flex';
        }

        function toggleHistoryFilterMenu() { const menu = document.getElementById('historyFilterMenu'); menu.style.display = menu.style.display === 'block' ? 'none' : 'block'; }
        function applyHistoryFilters() {
            const checkedBoxes = document.querySelectorAll('.hist-status-cb:checked');
            const selectedStatuses = Array.from(checkedBoxes).map(cb => cb.value.trim());
            const tagsContainer = document.getElementById('activeHistoryFilters');
            tagsContainer.innerHTML = '';
            
            selectedStatuses.forEach(status => {
                tagsContainer.innerHTML += `<div class="filter-tag">${status} <span onclick="removeHistoryFilter('${status}')">✖</span></div>`;
            });
            if (selectedStatuses.length > 0) {
                tagsContainer.innerHTML += `<div class="clear-all-tag" onclick="clearHistoryFilters()">Clear All ✖</div>`;
            }
            filterMemberHistory(); 
        }

        async function filterMemberHistory() {
            const empId = document.getElementById('current_view_id').value;
            const start = document.getElementById('hist_modal_start').value;
            const end = document.getElementById('hist_modal_end').value;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${empId}&start_date=${start}&end_date=${end}`);
            const data = await res.json();
            
            const checkedBoxes = document.querySelectorAll('.hist-status-cb:checked');
            const selectedStatuses = Array.from(checkedBoxes).map(cb => cb.value.trim());
            
            let total = 0;
            const filteredData = data.filter(row => selectedStatuses.length === 0 || selectedStatuses.includes(row.status));

            document.getElementById('modalHistoryBody').innerHTML = filteredData.length ? filteredData.map(row => {
                total += parseFloat(row.total_hours || 0);
                return `<tr><td>${row.date}</td><td><span class="status-pill status-${row.status}">${row.status}</span></td><td>${row.time_in}</td><td>${row.time_out}</td><td>${row.break_in}</td><td>${row.break_out}</td><td>${row.lunch_break}</td><td>${row.total_hours}</td></tr>`;
            }).join('') : '<tr><td colspan="8" style="text-align:center">No records</td></tr>';
            document.getElementById('totalHoursDisplay').innerText = total.toFixed(2);
        }

        function removeHistoryFilter(val) { const cb = Array.from(document.querySelectorAll('.hist-status-cb')).find(c => c.value === val); if(cb) { cb.checked = false; applyHistoryFilters(); } }
        function clearHistoryFilters() { document.querySelectorAll('.hist-status-cb').forEach(cb => cb.checked = false); applyHistoryFilters(); }
        
        window.onclick = function(e) { if (!e.target.closest('.history-filter-container')) { const menu = document.getElementById('historyFilterMenu'); if(menu && menu.style.display === 'block') menu.style.display = 'none'; } }
        function closeModal() { document.getElementById('historyModal').style.display = 'none'; }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value=s; document.getElementById('range_end').value=e; 
            loadAttendance();
        };
    </script>
</body>
</html>