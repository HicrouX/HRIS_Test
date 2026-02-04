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
    <meta charset="UTF-8">
    <title>Coach Dashboard</title>
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
        
        /* Status Colors */
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Endorsed { background: #e3f2fd; color: #3498db; }
        .status-Approved { background: #e8f5e9; color: #27ae60; }
        .status-Denied { background: #ffebee; color: #e74c3c; }

        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; }
        textarea { grid-column: span 2; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; margin-left: 10px; }
    </style>
</head>
<body>
    <input type="hidden" id="coach_id" value="<?php echo $coach_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="nav-item nav-active" onclick="switchView('team-view', this)">Team Attendance</div>
        <div class="nav-item" onclick="switchView('manage-requests', this)">Manage Endorsements</div>
        
        <div class="nav-item" onclick="switchView('my-requests-view', this)">My Requests</div>
        
        <div class="nav-item" onclick="switchView('my-filing', this)">My Filing Center</div>
        <div class="nav-item" onclick="switchView('my-attendance', this)">My Attendance Records</div>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;">Log Out</a>
    </div>

    <div class="main-content">
        <div id="team-view" class="view-content active-view">
            <div class="header">
                <h2>👥 Team Cluster Attendance</h2>
                <div style="display:flex; align-items:center;">
                    <input type="text" id="coachSearch" class="search-box" placeholder="Search..." onkeyup="filterTable()" style="padding:8px; border-radius:15px; border:none; width:200px;">
                    <input type="date" id="t_start" style="margin-left:15px;">
                    <input type="date" id="t_end" style="margin-left:5px;">
                    <button class="export-btn" onclick="exportData('TEAM')">📂 Export</button>
                </div>
            </div>
            <div class="container"><table id="attendanceTable"><thead><tr><th>Employee</th><th>Date</th><th>Status</th></tr></thead><tbody id="attendanceLogs"></tbody></table></div>
        </div>

        <div id="manage-requests" class="view-content">
            <div class="header"><h2>📋 Pending Endorsements</h2></div>
            <div class="container">
                <h3>Leaves</h3><table id="leaveTable"><thead><tr><th>Employee</th><th>Reason</th><th>Action</th></tr></thead><tbody id="coachLeaveList"></tbody></table>
                <h3 style="margin-top:30px;">Overtime</h3><table id="otTable"><thead><tr><th>Employee</th><th>Purpose</th><th>Action</th></tr></thead><tbody id="coachOTList"></tbody></table>
            </div>
        </div>

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;">
                <h2 style="margin:0;">My Request Status</h2>
                <button class="export-btn" onclick="loadMyRequests()">🔄 Refresh</button>
            </div>
            <div class="container">
                <h3 style="color:#666;">My Leave Requests</h3>
                <table><thead><tr><th>Type</th><th>Date Range</th><th>Reason</th><th>Status</th><th>Filed On</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666; margin-top:40px;">My Overtime Requests</h3>
                <table><thead><tr><th>Type</th><th>Time Range</th><th>Purpose</th><th>Status</th><th>Filed On</th></tr></thead><tbody id="myOTLogs"></tbody></table>
            </div>
        </div>

        <div id="my-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div class="form-card"><h3>📝 Leave</h3><form id="leaveForm" class="form-grid"><select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select><div></div><input type="date" id="l_start"><input type="date" id="l_end"><textarea id="l_reason" placeholder="Reason..."></textarea><button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit</button></form></div>
                <div class="form-card" style="border-left: 5px solid #27ae60;"><h3>⏰ Overtime</h3><form id="otForm" class="form-grid"><select id="ot_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select><div></div><input type="datetime-local" id="ot_start"><input type="datetime-local" id="ot_end"><textarea id="ot_purpose" placeholder="Purpose..."></textarea><button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit</button></form></div>
            </div>
        </div>

        <div id="my-attendance" class="view-content">
            <div class="header"><h2>Attendance History</h2><div style="display:flex;"><input type="date" id="range_start" onchange="loadMyAttendance()"><input type="date" id="range_end" style="margin-left:5px;" onchange="loadMyAttendance()"><button class="export-btn" onclick="exportData('MY')">📂 Export Mine</button></div></div>
            <div class="container"><table><thead><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th><th>Hrs</th></tr></thead><tbody id="myAttendanceBody"></tbody></table></div>
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
            if(viewId === 'manage-requests') { loadLeaves(); loadOT(); }
            if(viewId === 'my-requests-view') loadMyRequests();
            if(viewId === 'my-attendance') loadMyAttendance();
        }

        // 1. TEAM
        async function loadAttendance() {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('attendanceLogs').innerHTML = data.map(log => `<tr><td><strong>${log.first_name} ${log.last_name}</strong></td><td>${log.attendance_date}</td><td>${log.attendance_status}</td></tr>`).join('');
        }
        function filterTable() {
            const f=document.getElementById('coachSearch').value.toLowerCase(), r=document.getElementById('attendanceTable').getElementsByTagName("tr");
            for(let j=1;j<r.length;j++) r[j].style.display = r[j].innerText.toLowerCase().indexOf(f)>-1 ? "" : "none";
        }

        // 2. ENDORSEMENTS
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
        async function endorse(id, type, act) {
            if(!confirm(act + "?")) return;
            const endpoint = type === 'leave' ? '/management/endorse_leave.php' : '/management/endorse_overtime.php';
            await fetch(`${API}${endpoint}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ [type + '_id']: id, coach_id: COACH_ID, action: act }) });
            type === 'leave' ? loadLeaves() : loadOT();
        }

        // 3. MY REQUESTS
        async function loadMyRequests() {
            try {
                const res = await fetch(`${API}/users/get_my_requests.php?employee_id=${COACH_ID}`);
                const data = await res.json();
                document.getElementById("myLeaveLogs").innerHTML = data.leaves.length ? data.leaves.map(l => `<tr><td>${l.leave_type}</td><td>${l.start_date} to ${l.end_date}</td><td>${l.reason}</td><td><span class="status-pill status-${l.status}">${l.status}</span></td><td>${l.created_at}</td></tr>`).join('') : "<tr><td colspan='5'>No leaves.</td></tr>";
                document.getElementById("myOTLogs").innerHTML = data.overtime.length ? data.overtime.map(o => `<tr><td>${o.ot_type}</td><td>${o.start_time} to ${o.end_time}</td><td>${o.purpose}</td><td><span class="status-pill status-${o.status}">${o.status}</span></td><td>${o.created_at}</td></tr>`).join('') : "<tr><td colspan='5'>No OT.</td></tr>";
            } catch(e) { console.error(e); }
        }

        // 4. COMMON
        function exportData(mode) { window.location.href = `${API}/export/export_csv.php?mode=${mode}&start=${document.getElementById(mode==='TEAM'?'t_start':'range_start').value}&end=${document.getElementById(mode==='TEAM'?'t_end':'range_end').value}&coach_id=${COACH_ID}`; }
        async function loadMyAttendance() { const r = await fetch(`${API}/users/get_my_attendance.php?employee_id=${COACH_ID}&start_date=${document.getElementById('range_start').value}&end_date=${document.getElementById('range_end').value}`); const d = await r.json(); document.getElementById('myAttendanceBody').innerHTML = d.map(x => `<tr><td>${x.attendance_date}</td><td>${x.time_in||'--'}</td><td>${x.time_out||'--'}</td><td>${x.attendance_status}</td><td>${x.total_hours||0}</td></tr>`).join(''); }
        
        async function submitRequest(type) {
            const form = type === 'leave' ? { employee_id: COACH_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1:1, agreement_2:1 } : { employee_id: COACH_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1:1, agreement_2:1 };
            const res = await fetch(`${API}/users/file_${type}.php`, { method:'POST', body:JSON.stringify(form) });
            const r = await res.json(); alert(r.success||r.error); if(r.success) { document.getElementById(type+'Form').reset(); loadMyRequests(); }
        }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value=s; document.getElementById('range_end').value=e; document.getElementById('t_start').value=s; document.getElementById('t_end').value=e;
            loadAttendance();
        };
    </script>
</body>
</html>