<?php
session_start();
require_once 'api/middleware/auth.php';
verifyAccess([3, 4]); 
$api_base_url = "http://localhost/hris_official/api"; 
$user_name = $_SESSION['user_name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iREPLY - Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: #222; overflow: hidden; }
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: #1e4d8c; font-weight: bold; font-size: 26px; margin-bottom: 40px; }
        .user-info { font-size: 11px; font-weight: bold; margin-bottom: 25px; color: #27ae60; text-align: center; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: #666; font-size: 14px; transition: 0.2s; text-decoration: none; }
        .nav-item:hover { background: #f0f4f8; }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        .logout-btn { color: #e74c3c !important; font-weight: bold; }
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        .header { background: #1e4d8c; color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 30px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px 10px; font-size: 11px; color: #888; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 15px 10px; border-bottom: 1px solid #f9f9f9; font-size: 13px; color: #333; }
        .action-icon { cursor: pointer; font-size: 16px; margin-right: 10px; transition: 0.2s; }
        .date-input-small { background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; font-size: 13px; cursor: pointer; }
        .coach-select { padding: 8px; border-radius: 5px; border: none; font-size: 13px; color: #333; cursor: pointer; margin-right: 15px; font-weight: bold; }
        .badge-coach { background: #fff3e0; color: #e65100; font-size: 10px; padding: 2px 6px; border-radius: 4px; border: 1px solid #ffe0b2; margin-left: 5px; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="user-info">Administrator: <?php echo htmlspecialchars($user_name); ?></div>
        
        <div class="nav-item nav-active" onclick="switchView('master-view', this)">Master Attendance</div>
        <div class="nav-item" onclick="switchView('approvals-view', this)">Final Approvals</div>
        
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item logout-btn">Log Out</a>
    </div>

    <div class="main-content">
        <div id="master-view" class="view-content active-view">
            <div class="header">
                <div>
                    <h2 style="margin:0; font-size:18px;">📊 Master Attendance</h2>
                    <span style="font-size:11px; opacity:0.8;">Current View: <span id="viewLabel" style="font-weight:bold; color:#FFC107;">Coaches Only</span></span>
                </div>
                
                <div style="display:flex; align-items:center;">
                    <select id="viewFilter" class="coach-select" onchange="loadMasterLogs()">
                        <option value="COACHES" selected>⭐ View Coaches (Default)</option>
                        <option disabled>────── SELECT TEAM ──────</option>
                        </select>

                    <input type="date" id="r_start" class="date-input-small" onchange="loadMasterLogs()">
                    <span style="font-size:12px; opacity:0.7; margin:0 5px;">to</span>
                    <input type="date" id="r_end" class="date-input-small" onchange="loadMasterLogs()">
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
    </div>

    <script>
        const API = "<?php echo $api_base_url; ?>";

        function switchView(viewId, btn) {
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('active-view');
            btn.classList.add('nav-active');
            if(viewId === 'master-view') loadMasterLogs();
            if(viewId === 'approvals-view') loadApprovals();
        }

        // --- 1. Initialize Dropdown and Load Data ---
        async function loadCoachesAndInit() {
            try {
                // Set Dates
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                document.getElementById('r_start').value = firstDay.toISOString().split('T')[0];
                document.getElementById('r_end').value = today.toISOString().split('T')[0];

                // Fetch Coach Names for Dropdown
                const res = await fetch(`${API}/admin/get_coaches.php`);
                const coaches = await res.json();
                const select = document.getElementById('viewFilter');
                
                coaches.forEach(c => {
                    const opt = document.createElement('option');
                    // We store the COACH's EMPLOYEE ID in the value
                    opt.value = `TEAM_${c.employee_id}`; 
                    opt.innerText = `👥 Team ${c.first_name} ${c.last_name}`;
                    select.appendChild(opt);
                });
                
                // Initial Load (Default: Coaches Only)
                loadMasterLogs();
            } catch(e) { console.error(e); }
        }

        // --- 2. Master Attendance Logic ---
        async function loadMasterLogs() {
            const start = document.getElementById('r_start').value;
            const end = document.getElementById('r_end').value;
            const filterValue = document.getElementById('viewFilter').value;
            const viewLabel = document.getElementById('viewLabel');

            let url = `${API}/admin/get_all_attendance.php?start=${start}&end=${end}`;

            if (filterValue === 'COACHES') {
                url += `&filter_mode=COACHES`;
                viewLabel.innerText = "Coaches Only";
            } else if (filterValue.startsWith('TEAM_')) {
                const coachId = filterValue.split('_')[1];
                url += `&filter_mode=TEAM&coach_id=${coachId}`;
                
                // Update Label to show selected coach name
                const sel = document.getElementById('viewFilter');
                viewLabel.innerText = sel.options[sel.selectedIndex].text;
            }

            const res = await fetch(url);
            const data = await res.json();
            const tbody = document.getElementById("masterLogsBody");
            
            if(data.length === 0) {
                tbody.innerHTML = `<tr><td colspan='4' style='text-align:center; padding:20px; color:#999;'>No records found for this view.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(log => {
                const coachBadge = log.role_id == 2 ? '<span class="badge-coach">Coach</span>' : '';
                const statusColor = log.attendance_status === 'Present' ? '#27ae60' : (log.attendance_status === 'Absent' ? '#e74c3c' : '#f39c12');
                
                return `
                <tr>
                    <td><strong>${log.first_name} ${log.last_name}</strong> ${coachBadge}</td>
                    <td>${log.attendance_date}</td>
                    <td><span class="status-pill" style="color:${statusColor}">${log.attendance_status}</span></td>
                    <td>
                        <span class="action-icon" onclick="modifyRecord(${log.attendance_id})">✏️</span>
                        <span class="action-icon" style="color:#e74c3c;" onclick="deleteRecord(${log.attendance_id})">🗑️</span>
                    </td>
                </tr>`;
            }).join('');
        }

        async function loadApprovals() {
            const [leaveRes, otRes] = await Promise.all([
                fetch(`${API}/admin/get_endorsed_leaves.php`),
                fetch(`${API}/admin/get_endorsed_ot.php`)
            ]);
            renderQueue(await leaveRes.json(), 'adminLeaveQueue', 'leave');
            renderQueue(await otRes.json(), 'adminOTQueue', 'ot');
        }

        function renderQueue(items, id, type) {
            document.getElementById(id).innerHTML = items.length ? items.map(item => `
                <tr>
                    <td><strong>${item.first_name} ${item.last_name}</strong></td>
                    <td>${item.start_date || item.start_time}</td>
                    <td style="font-style:italic; color:#666;">"${item.reason || item.purpose}"</td>
                    <td><span class="action-icon" style="color:#27ae60;" onclick="finalApprove(${item.leave_id || item.ot_id}, '${type}')">✔️</span></td>
                </tr>`).join('') : `<tr><td colspan='4' style='color:#999;'>No pending items</td></tr>`;
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
            loadMasterLogs();
        }

        window.onload = loadCoachesAndInit;
    </script>
</body>
</html>