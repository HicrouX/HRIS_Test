<?php
// 1. Safe Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Check Login
if (!isset($_SESSION['role_id'])) {
    header("Location: login.php");
    exit;
}

// 3. SMART REDIRECT
if ($_SESSION['role_id'] == 1) { 
    header("Location: employee_dashboard.php"); 
    exit; 
}
if ($_SESSION['role_id'] >= 3) { 
    header("Location: admin_dashboard.php"); 
    exit; 
}

// 4. Proceed as Coach
require_once 'api/middleware/auth.php';
verifyAccess([2]); 

$api_base_url = "http://localhost/hris_official/api"; 
$coach_id = $_SESSION['employee_id'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iREPLY - Coach Dashboard</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #222; --text-gray: #666; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); overflow: hidden; }
        
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 40px; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        
        .header { background: var(--primary-blue); color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid #f9f9f9; font-size: 13px; }
        .search-box { padding: 8px 12px; border-radius: 20px; border: none; font-size: 13px; width: 200px; outline: none; }
        
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
        textarea { grid-column: span 2; }
        .submit-btn { grid-column: span 2; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); transition: 0.3s; }
        .submit-btn:hover { background: var(--primary-blue); }
        .date-range-container { display: flex; align-items: center; gap: 10px; font-size: 13px; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px; }
        .date-input-small { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 5px; border-radius: 4px; font-size: 13px; cursor: pointer; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; margin-left: 10px; text-decoration: none; }
        .export-btn:hover { background: #219150; }
    </style>
</head>
<body>
    <input type="hidden" id="coach_id" value="<?php echo $coach_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="nav-item nav-active" onclick="switchView('team-view', this)">Team Attendance</div>
        <div class="nav-item" onclick="switchView('manage-requests', this)">Manage Endorsements</div>
        <div class="nav-item" onclick="switchView('my-filing', this)">My Filing Center</div>
        <div class="nav-item" onclick="switchView('my-attendance', this)">My Attendance Records</div>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c; text-decoration: none;">Log Out</a>
    </div>

    <div class="main-content">
        <div id="team-view" class="view-content active-view">
            <div class="header">
                <h2>👥 Team Cluster Attendance</h2>
                <div style="display:flex; align-items:center;">
                    <input type="text" id="coachSearch" class="search-box" placeholder="🔍 Search employee..." onkeyup="filterTable('attendanceTable', 'coachSearch')">
                    
                    <input type="date" id="t_start" class="date-input-small" style="margin-left:15px;">
                    <span style="color:white; margin:0 5px; font-size:12px;">to</span>
                    <input type="date" id="t_end" class="date-input-small">
                    
                    <button class="export-btn" onclick="exportData('TEAM')">📂 Export Team</button>
                </div>
            </div>
            <div class="container">
                <table id="attendanceTable">
                    <thead><tr><th>Employee</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody id="attendanceLogs"></tbody>
                </table>
            </div>
        </div>

        <div id="manage-requests" class="view-content">
            <div class="header"><h2>📋 Pending Endorsements</h2></div>
            <div class="container">
                <h3>Leaves</h3><table id="leaveTable"><thead><tr><th>Employee</th><th>Reason</th><th>Action</th></tr></thead><tbody id="coachLeaveList"></tbody></table>
                <h3 style="margin-top:30px;">Overtime</h3><table id="otTable"><thead><tr><th>Employee</th><th>Purpose</th><th>Action</th></tr></thead><tbody id="coachOTList"></tbody></table>
            </div>
        </div>

        <div id="my-filing" class="view-content">
            <div class="header" style="background: #3498db;">
                <h2 style="margin:0;">Filing Center</h2>
            </div>
            <div class="container">
                <div class="form-card">
                    <h3 style="margin-top:0; color: #1e4d8c;">📝 File Leave Application</h3>
                    <form id="leaveForm" class="form-grid">
                        <select id="l_type">
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                        </select>
                        <div></div>
                        <input type="date" id="l_start" required>
                        <input type="date" id="l_end" required>
                        <textarea id="l_reason" placeholder="Briefly explain your reason..." rows="3"></textarea>
                        <button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit Leave Application</button>
                    </form>
                </div>
                
                <div class="form-card" style="border-left-color: #27ae60;">
                    <h3 style="margin-top:0; color: #27ae60;">⏰ Overtime Request</h3>
                    <form id="otForm" class="form-grid">
                        <select id="ot_type">
                            <option value="Regular Overtime">Regular Overtime</option>
                            <option value="Duty on Rest Day">Duty on Rest Day</option>
                        </select>
                        <div></div>
                        <input type="datetime-local" id="ot_start"> 
                        <input type="datetime-local" id="ot_end">
                        <textarea id="ot_purpose" placeholder="Purpose of overtime..." rows="2"></textarea>
                        <button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit OT Request</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="my-attendance" class="view-content">
            <div class="header">
                <h2 style="margin:0;">Attendance History</h2>
                <div class="date-range-container">
                    <input type="date" id="range_start" class="date-input-small" onchange="loadMyAttendance()">
                    <span style="opacity: 0.5;">to</span>
                    <input type="date" id="range_end" class="date-input-small" onchange="loadMyAttendance()">
                    <button class="export-btn" onclick="exportData('MY')">📂 Export Mine</button>
                </div>
            </div>
            <div class="container">
                <table>
                    <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Work Hours</th></tr></thead>
                    <tbody id="myAttendanceBody"></tbody>
                </table>
            </div>
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
            if(viewId === 'my-attendance') loadMyAttendance();
        }

        function exportData(mode) {
            let start, end;
            if (mode === 'TEAM') {
                start = document.getElementById('t_start').value;
                end = document.getElementById('t_end').value;
            } else {
                start = document.getElementById('range_start').value;
                end = document.getElementById('range_end').value;
            }
            window.location.href = `${API}/export/export_csv.php?mode=${mode}&start=${start}&end=${end}&coach_id=${COACH_ID}`;
        }

        async function loadMyAttendance() {
            const start = document.getElementById('range_start').value;
            const end = document.getElementById('range_end').value;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${COACH_ID}&start_date=${start}&end_date=${end}`);
            const data = await res.json();
            const tbody = document.getElementById('myAttendanceBody');
            
            if(data.length === 0) { tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No records found.</td></tr>"; return; }

            tbody.innerHTML = data.map(row => {
                let statusStyle = "background:#e8f5e9; color:#2e7d32;"; 
                if(row.attendance_status === 'Late') statusStyle = "background:#fff3e0; color:#e65100;";
                if(row.attendance_status === 'On Leave') statusStyle = "background:#e3f2fd; color:#1565c0;";
                if(row.attendance_status === 'Absent') statusStyle = "background:#ffebee; color:#c62828;";
                return `<tr><td><strong>${row.attendance_date}</strong></td><td>${row.time_in || '--:--'}</td><td>${row.time_out || '--:--'}</td><td><span class="status-pill" style="${statusStyle}">${row.attendance_status}</span></td><td>${row.total_hours || '0.00'} hrs</td></tr>`;
            }).join('');
        }

        async function submitRequest(type) {
            const endpoint = type === 'leave' ? '/users/file_leave.php' : '/users/file_overtime.php';
            const formId = type === 'leave' ? 'leaveForm' : 'otForm';
            let payload = {};
            
            if(type === 'leave') {
                payload = { employee_id: COACH_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1: 1, agreement_2: 1 };
            } else {
                payload = { employee_id: COACH_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1: 1, agreement_2: 1 };
            }

            const res = await fetch(`${API}${endpoint}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await res.json();
            alert(result.success || result.error);
            if(result.success) document.getElementById(formId).reset();
        }

        function filterTable(tableId, inputId) {
            const input = document.getElementById(inputId);
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName("td")[0];
                if (nameCell) {
                    const txtValue = nameCell.textContent || nameCell.innerText;
                    rows[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        async function loadAttendance() {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('attendanceLogs').innerHTML = data.map(log => `
                <tr><td><strong>${log.first_name} ${log.last_name}</strong></td><td>${log.attendance_date}</td><td>${log.attendance_status}</td></tr>
            `).join('');
            filterTable('attendanceTable', 'coachSearch');
        }

        async function loadLeaves() {
            const res = await fetch(`${API}/management/get_pending_leaves.php?user_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('coachLeaveList').innerHTML = data.map(item => `
                <tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.reason}"</td><td onclick="endorse(${item.leave_id}, 'leave')" style="cursor:pointer;">✔</td></tr>
            `).join('');
        }

        async function loadOT() {
            const res = await fetch(`${API}/management/get_pending_ot.php?user_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('coachOTList').innerHTML = data.map(item => `
                <tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>"${item.purpose}"</td><td onclick="endorse(${item.ot_id}, 'ot')" style="cursor:pointer;">✔</td></tr>
            `).join('');
        }

        async function endorse(id, type) {
            const endpoint = type === 'leave' ? '/management/endorse_leave.php' : '/management/endorse_overtime.php';
            await fetch(`${API}${endpoint}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ [type + '_id']: id, coach_id: COACH_ID }) });
            type === 'leave' ? loadLeaves() : loadOT();
        }

        window.onload = function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), 0, 1);
            const formatDate = (d) => d.toISOString().split('T')[0];
            document.getElementById('range_start').value = formatDate(firstDay);
            document.getElementById('range_end').value = formatDate(today);
            document.getElementById('t_start').value = formatDate(firstDay);
            document.getElementById('t_end').value = formatDate(today);
            loadAttendance();
        };
    </script>
</body>
</html>