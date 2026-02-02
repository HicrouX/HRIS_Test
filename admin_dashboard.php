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
if ($_SESSION['role_id'] == 2) { 
    header("Location: coach_dashboard.php"); 
    exit; 
}

// 4. Proceed as Admin
require_once 'api/middleware/auth.php';
verifyAccess([3, 4]); 

$api_base_url = "http://localhost/hris_official/api"; 
$user_name = $_SESSION['user_name'] ?? 'Administrator';
$user_id = $_SESSION['employee_id']; 
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
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; text-decoration: none; }
        .nav-item:hover { background: #f0f4f8; color: var(--primary-blue); }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        .logout-btn { color: #e74c3c !important; font-weight: bold; }
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        .header { background: var(--primary-blue); color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 30px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px 10px; font-size: 11px; color: #888; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 15px 10px; border-bottom: 1px solid #f9f9f9; font-size: 13px; color: #333; }
        .action-icon { cursor: pointer; font-size: 16px; margin-right: 10px; transition: 0.2s; }
        .date-input-small { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 5px; border-radius: 4px; font-size: 13px; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
        .badge-coach { background: #fff3e0; color: #e65100; font-size: 10px; padding: 2px 6px; border-radius: 4px; border: 1px solid #ffe0b2; margin-left: 5px; }
        .badge-admin { background: #e8f5e9; color: #2e7d32; font-size: 10px; padding: 2px 6px; border-radius: 4px; border: 1px solid #c8e6c9; margin-left: 5px; }
        .clickable-name { color: #1e4d8c; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .clickable-name:hover { text-decoration: underline; color: #27ae60; }
        .back-btn { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); padding: 5px 15px; border-radius: 20px; color: white; cursor: pointer; font-size: 12px; display: none; align-items: center; gap: 5px; }
        .back-btn:hover { background: rgba(255,255,255,0.3); }
        .search-box { padding: 8px 12px; border-radius: 20px; border: none; font-size: 13px; width: 200px; margin-right: 15px; outline: none; }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; margin-left: 10px; text-decoration: none; }
        .export-btn:hover { background: #219150; }
        
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
        textarea { grid-column: span 2; }
        .submit-btn { grid-column: span 2; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); transition: 0.3s; }
        .submit-btn:hover { background: var(--primary-blue); }
    </style>
</head>
<body>
    <input type="hidden" id="admin_id" value="<?php echo $user_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="user-info">Administrator: <?php echo htmlspecialchars($user_name); ?></div>
        
        <div class="nav-item nav-active" onclick="switchView('master-view', this)">Master Attendance</div>
        <div class="nav-item" onclick="switchView('approvals-view', this)">Final Approvals</div>
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
                        <button id="backBtn" class="back-btn" onclick="resetToLeaders()">⬅ Back</button>
                        <h2 style="margin:0; font-size:18px;">📊 Master Attendance</h2>
                    </div>
                    <span style="font-size:11px; opacity:0.8; margin-top:5px; display:block;">
                        Viewing: <span id="viewLabel" style="font-weight:bold; color:#FFC107;">Coaches & Admins</span>
                    </span>
                </div>
                <div style="display:flex; align-items:center;">
                    <input type="text" id="adminSearch" class="search-box" placeholder="🔍 Search name..." onkeyup="filterTable('masterTable', 'adminSearch')">
                    <input type="date" id="r_start" class="date-input-small" onchange="refreshTable()">
                    <span style="font-size:12px; opacity:0.7; margin:0 5px;">to</span>
                    <input type="date" id="r_end" class="date-input-small" onchange="refreshTable()">
                    <button class="export-btn" onclick="exportData('ALL')">📂 Export All</button>
                </div>
            </div>
            <div class="container">
                <table id="masterTable">
                    <thead><tr><th>Personnel</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="masterLogsBody"></tbody>
                </table>
            </div>
        </div>

        <div id="approvals-view" class="view-content">
            <div class="header"><h2>✅ Final Sign-offs</h2></div>
            <div class="container">
                <h3>Leaves</h3>
                <table id="adminLeaveTable"><thead><tr><th>Employee</th><th>Date Range</th><th>Reason</th><th>Action</th></tr></thead><tbody id="adminLeaveQueue"></tbody></table>
                <h3 style="margin-top:20px;">Overtime</h3>
                <table id="adminOTTable"><thead><tr><th>Employee</th><th>Date/Time</th><th>Purpose</th><th>Action</th></tr></thead><tbody id="adminOTQueue"></tbody></table>
            </div>
        </div>

        <div id="admin-filing" class="view-content">
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
                <div style="display:flex; align-items:center;">
                    <input type="date" id="my_start" class="date-input-small" onchange="loadMyAttendance()">
                    <span style="margin:0 5px; opacity:0.7; font-size:12px;">to</span>
                    <input type="date" id="my_end" class="date-input-small" onchange="loadMyAttendance()">
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
        const MY_ID = document.getElementById('admin_id').value;
        let currentMode = 'COACHES'; 
        let currentCoachId = null;   

        function switchView(viewId, btn) {
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('active-view');
            btn.classList.add('nav-active');
            if(viewId === 'master-view') refreshTable();
            if(viewId === 'approvals-view') loadApprovals();
            if(viewId === 'my-attendance') loadMyAttendance();
        }

        function exportData(mode) {
            let start, end;
            if (mode === 'ALL') {
                start = document.getElementById('r_start').value;
                end = document.getElementById('r_end').value;
            } else {
                start = document.getElementById('my_start').value;
                end = document.getElementById('my_end').value;
            }
            window.location.href = `${API}/export/export_csv.php?mode=${mode}&start=${start}&end=${end}`;
        }

        async function loadMyAttendance() {
            const start = document.getElementById('my_start').value;
            const end = document.getElementById('my_end').value;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${MY_ID}&start_date=${start}&end_date=${end}`);
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
                payload = { employee_id: MY_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1: 1, agreement_2: 1 };
            } else {
                payload = { employee_id: MY_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1: 1, agreement_2: 1 };
            }

            const res = await fetch(`${API}${endpoint}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await res.json();
            alert(result.success || result.error);
            if(result.success) { document.getElementById(formId).reset(); loadApprovals(); }
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

        async function refreshTable() {
            const start = document.getElementById('r_start').value;
            const end = document.getElementById('r_end').value;
            let url = `${API}/admin/get_all_attendance.php?start=${start}&end=${end}`;

            if (currentMode === 'COACHES') {
                url += `&filter_mode=COACHES`;
                document.getElementById('viewLabel').innerText = "Coaches & Admins";
                document.getElementById('backBtn').style.display = 'none';
            } else if (currentMode === 'TEAM') {
                url += `&filter_mode=TEAM&coach_id=${currentCoachId}`;
                document.getElementById('backBtn').style.display = 'inline-block';
            }

            const res = await fetch(url);
            const data = await res.json();
            const tbody = document.getElementById("masterLogsBody");

            if(data.length === 0) { tbody.innerHTML = `<tr><td colspan='4' style='text-align:center; padding:20px; color:#999;'>No records found.</td></tr>`; return; }

            tbody.innerHTML = data.map(log => {
                const statusColor = log.attendance_status === 'Present' ? '#27ae60' : (log.attendance_status === 'Absent' ? '#e74c3c' : '#f39c12');
                let nameDisplay = `<strong>${log.first_name} ${log.last_name}</strong>`;
                if (currentMode === 'COACHES') {
                    let badge = log.role_id == 2 ? '<span class="badge-coach">Coach</span>' : (log.role_id >= 3 ? '<span class="badge-admin">Admin</span>' : '');
                    nameDisplay = `<span class="clickable-name" onclick="viewTeam(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name}</span> ${badge}`;
                }
                return `<tr><td>${nameDisplay}</td><td>${log.attendance_date}</td><td><span class="status-pill" style="color:${statusColor}">${log.attendance_status}</span></td><td><span class="action-icon" onclick="modifyRecord(${log.attendance_id})">✏️</span><span class="action-icon" style="color:#e74c3c;" onclick="deleteRecord(${log.attendance_id})">🗑️</span></td></tr>`;
            }).join('');
            filterTable('masterTable', 'adminSearch');
        }

        function viewTeam(coachId, coachName) { currentMode = 'TEAM'; currentCoachId = coachId; document.getElementById('viewLabel').innerText = `Team ${coachName}`; refreshTable(); }
        function resetToLeaders() { currentMode = 'COACHES'; currentCoachId = null; refreshTable(); }

        window.onload = function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), 0, 1);
            const formatDate = (d) => d.toISOString().split('T')[0];
            document.getElementById('r_start').value = formatDate(firstDay);
            document.getElementById('r_end').value = formatDate(today);
            document.getElementById('my_start').value = formatDate(firstDay);
            document.getElementById('my_end').value = formatDate(today);
            refreshTable(); loadApprovals();
        };

        async function loadApprovals() {
            const [leaveRes, otRes] = await Promise.all([fetch(`${API}/admin/get_endorsed_leaves.php`), fetch(`${API}/admin/get_endorsed_ot.php`)]);
            renderQueue(await leaveRes.json(), 'adminLeaveQueue', 'leave');
            renderQueue(await otRes.json(), 'adminOTQueue', 'ot');
        }

        function renderQueue(items, id, type) {
            document.getElementById(id).innerHTML = items.length ? items.map(item => `<tr><td><strong>${item.first_name} ${item.last_name}</strong></td><td>${item.start_date || item.start_time}</td><td style="font-style:italic; color:#666;">"${item.reason || item.purpose}"</td><td><span class="action-icon" style="color:#27ae60;" onclick="finalApprove(${item.leave_id || item.ot_id}, '${type}')">✔️</span></td></tr>`).join('') : `<tr><td colspan='4' style='color:#999;'>No pending items</td></tr>`;
        }

        async function finalApprove(id, type) {
            if(!confirm("Sign-off on this request?")) return;
            const endpoint = type === 'leave' ? '/admin/final_approve_leave.php' : '/admin/final_approve_overtime.php';
            await fetch(`${API}${endpoint}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ [type + '_id']: id, action: 'APPROVE' }) });
            loadApprovals();
        }

        async function deleteRecord(id) {
            if(!confirm("Permanently delete?")) return;
            await fetch(`${API}/admin/delete_attendance.php?attendance_id=${id}`);
            refreshTable();
        }
    </script>
</body>
</html>