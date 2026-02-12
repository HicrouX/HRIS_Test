<?php
// FILE: coach_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'api/middleware/auth.php';
verifyAccess([2]); 

$api_base_url = "http://localhost/hris_official/api"; 
$coach_id = $_SESSION['employee_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Coach Dashboard</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #222; --text-gray: #666; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); overflow: hidden; }
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 40px; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        .header { background: var(--primary-blue); color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid #f9f9f9; font-size: 13px; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .action-btn { cursor: pointer; font-size: 16px; margin: 0 5px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 0; border-radius: 4px; width: 1000px; max-height: 90vh; display: flex; flex-direction: column; }
        .modal-header { background: #fff; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .modal-body { padding: 0; overflow-y: auto; }
        .close-btn { cursor: pointer; }
        .clickable-name { color: #1e4d8c; font-weight: bold; cursor: pointer; text-decoration: underline; }
        .history-table th { background: #eee; color: #333; font-weight: bold; font-size: 12px; border-bottom: 2px solid #ddd; padding: 12px; }
        .history-table td { background: #fff; color: #555; font-size: 12px; border-bottom: 1px solid #eee; padding: 12px; vertical-align: middle; }
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; }
        textarea { grid-column: span 2; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; margin-left: 10px; }
        .readonly-field { background: #eee; color: #777; cursor: not-allowed; }
        .modal-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 400px; animation: fadeIn 0.3s; }
    </style>
</head>
<body>
    <input type="hidden" id="coach_id" value="<?php echo $coach_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="nav-item nav-active" onclick="switchView('team-view', this)">Team Attendance</div>
        <div class="nav-item" onclick="switchView('manage-requests', this)">Manage Endorsements</div>
        <div class="nav-item" onclick="switchView('my-filing', this)">My Filing Center</div>
        <div class="nav-item" onclick="switchView('my-requests-view', this)">My Requests</div>
        <div class="nav-item" onclick="switchView('my-attendance', this)">My Attendance Records</div>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;">Log Out</a>
    </div>

    <div class="main-content">
        <div id="team-view" class="view-content active-view">
            <div class="header"><h2>👥 Team Members</h2><div style="display:flex; align-items:center;"><input type="text" id="coachSearch" class="search-box" placeholder="Search Member..." onkeyup="filterTable()" style="padding:8px; border-radius:15px; border:none; width:200px;"><button class="export-btn" onclick="exportData('TEAM')">Export Excel</button></div></div>
            <div class="container"><table id="attendanceTable"><thead><tr><th>Employee Name</th><th>Last Active Date</th><th>Current Status</th></tr></thead><tbody id="attendanceLogs"></tbody></table></div>
        </div>

        <div id="manage-requests" class="view-content">
            <div class="header"><h2>📋 Pending Requests</h2></div>
            <div class="container">
                <h3>Leaves</h3><table id="leaveTable"><thead><tr><th>Employee</th><th>Reason</th><th>Action</th></tr></thead><tbody id="coachLeaveList"></tbody></table>
                <h3 style="margin-top:30px;">Overtime</h3><table id="otTable"><thead><tr><th>Employee</th><th>Purpose</th><th>Action</th></tr></thead><tbody id="coachOTList"></tbody></table>
                <h3 style="margin-top:30px; color:#e74c3c;">Attendance Disputes</h3>
                <table id="disputeTable">
                    <thead><tr><th>Employee</th><th>Date</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody id="coachDisputeList"></tbody>
                </table>
            </div>
        </div>

        <div id="my-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div class="form-card"><h3>📝 Leave</h3><form id="leaveForm" class="form-grid"><select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select><div></div><input type="date" id="l_start"><input type="date" id="l_end"><textarea id="l_reason" placeholder="Reason..."></textarea><button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit</button></form></div>
                <div class="form-card" style="border-left: 5px solid #27ae60;"><h3>⏰ Overtime</h3><form id="otForm" class="form-grid"><select id="ot_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select><div></div><input type="datetime-local" id="ot_start"><input type="datetime-local" id="ot_end"><textarea id="ot_purpose" placeholder="Purpose..."></textarea><button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit</button></form></div>
                <div class="form-card" style="border-left: 5px solid #e74c3c;"><h3 style="color: #e74c3c;">Attendance Dispute</h3><form id="disputeForm" class="form-grid"><input type="text" value="Self-Filing" class="readonly-field" readonly><div></div><div style="grid-column: span 2;"><label style="font-size:12px; font-weight:bold;">Date of Incident:</label><input type="date" id="d_date"></div><textarea id="d_reason" placeholder="Explain the dispute..." rows="3"></textarea><button type="button" class="submit-btn" style="background:#e74c3c;" onclick="submitRequest('dispute')">Submit Dispute</button></form></div>
            </div>
        </div>

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;"><h2>My Request Status</h2><button class="export-btn" onclick="loadMyRequests()">Refresh</button></div>
            <div class="container">
                <h3 style="color:#666;">My Leave Requests</h3><table><thead><tr><th>Type</th><th>Date Range</th><th>Reason</th><th>Status</th><th>Filed On</th><th>Approved By</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666;">My Overtime Requests</h3><table><thead><tr><th>Type</th><th>Time Range</th><th>Purpose</th><th>Status</th><th>Filed On</th><th>Approved By</th></tr></thead><tbody id="myOTLogs"></tbody></table>
            </div>
        </div>

        <div id="my-attendance" class="view-content"><div class="header"><h2>Attendance History</h2><div style="display:flex;"><input type="date" id="range_start" onchange="loadMyAttendance()"><input type="date" id="range_end" style="margin-left:5px;" onchange="loadMyAttendance()"><button class="export-btn" onclick="exportData('MY')">Export Excel</button></div></div><div class="container"><table><thead><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th><th>Hrs</th></tr></thead><tbody id="myAttendanceBody"></tbody></table></div></div>
    </div>

    <div id="disputeModal" class="modal">
        <div class="modal-box">
            <div class="modal-header"><div class="modal-title">Resolve Dispute</div></div>
            <div id="disputeModalContent" style="padding:15px; font-size:14px; background:#f9f9f9; margin-bottom:10px;"></div>
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Set Correct Status:</label>
            <select id="newDisputeStatus" style="width:100%; padding:10px; margin-bottom:20px;"><option value="Present">Present</option><option value="Late">Late</option><option value="Absent">Absent</option><option value="Overtime">Overtime</option><option value="On Leave">On Leave</option><option value="Duty on Rest Day">Duty on Rest Day</option></select>
            <input type="hidden" id="currentDisputeId">
            <button onclick="confirmDispute()" style="width:100%; padding:10px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer;">Confirm & Approve</button>
            <a onclick="closeDisputeModal()" style="display:block; width:100%; text-align:center; margin-top:10px; cursor:pointer; color:#666;">Cancel</a>
        </div>
    </div>

    <div id="historyModal" class="modal"><div class="modal-content"><div class="modal-header"><div class="modal-title" id="modalTitle">Employee History</div><span class="close-btn" onclick="closeModal()">×</span></div><div class="modal-body"><table class="history-table"><thead><tr><th>Date</th><th>Status</th><th>Time In</th><th>Time Out</th><th>Lunch Break</th><th>Break Time</th><th>Hours Worked</th><th>Overtime</th></tr></thead><tbody id="modalHistoryBody"></tbody></table></div><div style="padding:10px; background:#fff; text-align:right; border-top:1px solid #ddd; color:#999; font-size:11px;">Total Hours: <span id="totalHoursDisplay">0.00</span></div></div></div>

    <script>
        const API = "<?php echo $api_base_url; ?>";
        const COACH_ID = document.getElementById('coach_id').value;

        function switchView(viewId, btn) {
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('active-view');
            btn.classList.add('nav-active');
            if(viewId === 'team-view') loadAttendance();
            if(viewId === 'manage-requests') { loadLeaves(); loadOT(); loadDisputes(); }
            if(viewId === 'my-requests-view') loadMyRequests();
            if(viewId === 'my-attendance') loadMyAttendance();
        }

        async function loadAttendance() {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('attendanceLogs').innerHTML = data.map(log => `<tr><td style="padding:15px;"><span class="clickable-name" onclick="viewMemberHistory(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name}</span></td><td>${log.latest_date||'-'}</td><td>${log.latest_status||'-'}</td></tr>`).join('');
        }

        async function loadLeaves() {
            const res = await fetch(`${API}/management/get_pending_leaves.php?user_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('coachLeaveList').innerHTML = data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.reason}"</td><td style="cursor:pointer; color:green;" onclick="endorse(${item.leave_id}, 'leave', 'ENDORSE')">✔</td><td style="cursor:pointer; color:red;" onclick="endorse(${item.leave_id}, 'leave', 'DENY')">❌</td></tr>`).join('');
        }
        async function loadOT() {
            const res = await fetch(`${API}/management/get_pending_ot.php?user_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('coachOTList').innerHTML = data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.purpose}"</td><td style="cursor:pointer; color:green;" onclick="endorse(${item.ot_id}, 'ot', 'ENDORSE')">✔</td><td style="cursor:pointer; color:red;" onclick="endorse(${item.ot_id}, 'ot', 'DENY')">❌</td></tr>`).join('');
        }
        
        async function loadDisputes() {
            const res = await fetch(`${API}/management/get_pending_disputes.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('coachDisputeList').innerHTML = data.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>${item.dispute_date}</td><td>${item.reason}</td><td><button onclick="openDisputeModal(${item.dispute_id}, '${item.first_name}', '${item.dispute_date}')" style="background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Review</button> <span class="action-btn" style="color:red; margin-left:10px;" onclick="denyDispute(${item.dispute_id})">❌</span></td></tr>`).join('');
        }

        function openDisputeModal(id, name, date) {
            document.getElementById('currentDisputeId').value = id;
            document.getElementById('disputeModalContent').innerText = `Resolving dispute for ${name} on ${date}. Select the correct status to update the database.`;
            document.getElementById('disputeModal').style.display = 'flex';
        }
        function closeDisputeModal() { document.getElementById('disputeModal').style.display = 'none'; }

        async function confirmDispute() {
            const id = document.getElementById('currentDisputeId').value;
            const newStatus = document.getElementById('newDisputeStatus').value;
            await fetch(`${API}/management/resolve_dispute.php`, { method: 'POST', body: JSON.stringify({ dispute_id: id, action: 'APPROVE', new_status: newStatus }) });
            closeDisputeModal(); loadDisputes();
        }

        async function denyDispute(id) {
            if(!confirm("Deny this dispute?")) return;
            await fetch(`${API}/management/resolve_dispute.php`, { method: 'POST', body: JSON.stringify({ dispute_id: id, action: 'DENY' }) });
            loadDisputes();
        }

        async function endorse(id, type, act) {
            if(!confirm(act + "?")) return;
            let endpoint = type === 'leave' ? '/management/endorse_leave.php' : '/management/endorse_overtime.php';
            let payload = { [type === 'leave' ? 'leave_id' : 'ot_id']: id, coach_id: COACH_ID, action: act };
            await fetch(`${API}${endpoint}`, { method: 'POST', body: JSON.stringify(payload) });
            if (type === 'leave') loadLeaves();
            if (type === 'ot') loadOT();
        }

        async function viewMemberHistory(empId, name) {
            document.getElementById('modalTitle').innerText = `${name} - History`;
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

        function filterTable() {
            const f=document.getElementById('coachSearch').value.toLowerCase(), r=document.getElementById('attendanceTable').getElementsByTagName("tr");
            for(let j=1;j<r.length;j++) r[j].style.display = r[j].innerText.toLowerCase().indexOf(f)>-1 ? "" : "none";
        }
        function exportData(mode) { window.location.href = `${API}/export/export_excel.php?mode=${mode}&start=${document.getElementById('range_start').value}&end=${document.getElementById('range_end').value}&coach_id=${COACH_ID}`; }
        async function loadMyAttendance() { const r = await fetch(`${API}/users/get_my_attendance.php?employee_id=${COACH_ID}&start_date=${document.getElementById('range_start').value}&end_date=${document.getElementById('range_end').value}`); const d = await r.json(); document.getElementById('myAttendanceBody').innerHTML = d.map(x => `<tr><td>${x.attendance_date}</td><td>${x.time_in||'--'}</td><td>${x.time_out||'--'}</td><td>${x.attendance_status}</td><td>${x.total_hours||0}</td></tr>`).join(''); }
        
        async function loadMyRequests() {
            const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${COACH_ID}`);
            const data = await res.json();
            const leaves = data.filter(item => item.type === 'Leave');
            const overtime = data.filter(item => item.type === 'Overtime');
            const getApprover = (item) => item.admin_first ? `<span style="color:#27ae60; font-weight:600;">${item.admin_first} ${item.admin_last}</span>` : '<span style="color:#ccc;">-</span>';
            const renderRow = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}<br>${item.end_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td><td>${getApprover(item)}</td></tr>`;
            document.getElementById("myLeaveLogs").innerHTML = leaves.map(renderRow).join('');
            document.getElementById("myOTLogs").innerHTML = overtime.map(renderRow).join('');
        }

        async function submitRequest(type) {
            let form = {};
            if (type === 'leave') form = { employee_id: COACH_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1:1, agreement_2:1 };
            else if (type === 'ot') {
                const start = new Date(document.getElementById('ot_start').value);
                const end = new Date(document.getElementById('ot_end').value);
                const diffMs = end - start;
                const diffHrs = diffMs / (1000 * 60 * 60);
                if (diffHrs > 2) { alert("Overtime cannot exceed 2 hours."); return; }
                form = { employee_id: COACH_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1:1, agreement_2:1 };
            }
            else if (type === 'dispute') form = { employee_id: COACH_ID, date: document.getElementById('d_date').value, reason: document.getElementById('d_reason').value };

            const endpoint = type === 'dispute' ? '/users/file_dispute.php' : `/users/file_${type}.php`;
            const res = await fetch(`${API}${endpoint}`, { method:'POST', body:JSON.stringify(form) });
            const r = await res.json(); alert(r.success||r.error); 
            if(r.success) { 
                if(type==='leave') document.getElementById('leaveForm').reset();
                if(type==='ot') document.getElementById('otForm').reset();
                if(type==='dispute') document.getElementById('disputeForm').reset();
                loadMyRequests(); 
            }
        }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value=s; document.getElementById('range_end').value=e; 
            loadAttendance();
        };
    </script>
</body>
</html>