<?php
// =========================================================
// 1. SESSION & AUTHENTICATION
// =========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix for "Source Code" display issue
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

// =========================================================
// 2. LOGIC: HANDLE SAVING (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_status'])) {
    $id = $_POST['attendance_id'];
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE attendance SET attendance_status = ? WHERE attendance_id = ?");
        $stmt->execute([$status, $id]);
        
        // Refresh page to clear edit mode
        header("Location: admin_dashboard.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// =========================================================
// 3. LOGIC: PREPARE EDIT DATA (GET)
// =========================================================
// We do NOT exit here. We just fetch data to show in the overlay later.
$edit_mode = false;
$edit_record = null;

if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name 
                           FROM attendance a 
                           JOIN employees e ON a.employee_id = e.employee_id 
                           WHERE a.attendance_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_record) {
        $edit_mode = true;
    }
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
        
        /* SIDEBAR */
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 40px; }
        .user-info { font-size: 11px; font-weight: bold; margin-bottom: 25px; color: #27ae60; text-align: center; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; text-decoration: none; display: block; }
        .nav-item:hover { background: #f0f4f8; color: var(--primary-blue); }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        .logout-btn { color: #e74c3c !important; font-weight: bold; }
        
        /* MAIN CONTENT */
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }
        .header { background: var(--primary-blue); color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 30px 40px; }
        
        /* TABLES */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px 10px; font-size: 11px; color: #888; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 15px 10px; border-bottom: 1px solid #f9f9f9; font-size: 13px; color: #333; }
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 10px; }
        
        /* COLORS */
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Endorsed { background: #e3f2fd; color: #3498db; }
        .status-Approved { background: #e8f5e9; color: #27ae60; }
        .status-Denied { background: #ffebee; color: #e74c3c; }

        /* CONTROLS */
        .date-input-small { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 5px; border-radius: 4px; font-size: 13px; }
        .search-box { padding: 8px 12px; border-radius: 20px; border: none; font-size: 13px; width: 200px; margin-right: 15px; outline: none; }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; margin-left: 10px; display: inline-block; text-decoration: none; }
        .action-icon { cursor: pointer; font-size: 16px; margin-right: 10px; text-decoration: none; display: inline-block; }
        
        /* FORMS */
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; }
        textarea { grid-column: span 2; }
        .submit-btn { padding: 12px; width: 100%; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); }

        /* --- OVERLAY STYLES (THIS MAKES THE BACKGROUND VISIBLE) --- */
        .overlay {
            display: <?php echo $edit_mode ? 'flex' : 'none'; ?>;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Transparent Black */
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(2px);
        }
        .modal-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 400px;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { font-size: 18px; font-weight: bold; color: #1e4d8c; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <input type="hidden" id="admin_id" value="<?php echo $user_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="user-info">Administrator: <?php echo htmlspecialchars($user_name); ?></div>
        
        <div class="nav-item nav-active" onclick="switchView('master-view', this)">Master Attendance</div>
        <div class="nav-item" onclick="switchView('approvals-view', this)">Final Approvals</div>
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
                        <button id="backBtn" onclick="resetToLeaders()" style="display:none; cursor:pointer; background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.4); padding:5px 10px; color:white; border-radius:15px;">⬅ Back</button>
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

        <div id="my-requests-view" class="view-content">
            <div class="header" style="background: #8e44ad;">
                <h2 style="margin:0;">My Request Status</h2>
                <button class="export-btn" onclick="loadMyRequests()">🔄 Refresh</button>
            </div>
            <div class="container">
                <h3 style="color:#666;">My Leave Requests</h3>
                <table>
                    <thead><tr><th>Type</th><th>Date Range</th><th>Reason</th><th>Status</th><th>Filed On</th></tr></thead>
                    <tbody id="myLeaveLogs"></tbody>
                </table>

                <h3 style="color:#666; margin-top:40px;">My Overtime Requests</h3>
                <table>
                    <thead><tr><th>Type</th><th>Time Range</th><th>Purpose</th><th>Status</th><th>Filed On</th></tr></thead>
                    <tbody id="myOTLogs"></tbody>
                </table>
            </div>
        </div>

        <div id="admin-filing" class="view-content">
            <div class="header" style="background: #3498db;"><h2>Filing Center</h2></div>
            <div class="container">
                <div class="form-card">
                    <h3 style="margin-top:0; color: #1e4d8c;">📝 File Leave Application</h3>
                    <form id="leaveForm" class="form-grid">
                        <select id="l_type"><option value="Sick Leave">Sick Leave</option><option value="Vacation Leave">Vacation Leave</option></select>
                        <div></div>
                        <input type="date" id="l_start" required>
                        <input type="date" id="l_end" required>
                        <textarea id="l_reason" placeholder="Reason..." rows="3"></textarea>
                        <button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit Leave</button>
                    </form>
                </div>
                <div class="form-card" style="border-left: 5px solid #27ae60;">
                    <h3 style="margin-top:0; color: #27ae60;">⏰ Overtime Request</h3>
                    <form id="otForm" class="form-grid">
                        <select id="ot_type"><option value="Regular Overtime">Regular Overtime</option><option value="Duty on Rest Day">Duty on Rest Day</option></select>
                        <div></div>
                        <input type="datetime-local" id="ot_start"> 
                        <input type="datetime-local" id="ot_end">
                        <textarea id="ot_purpose" placeholder="Purpose..." rows="2"></textarea>
                        <button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit OT</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="my-attendance" class="view-content">
            <div class="header">
                <h2>Attendance History</h2>
                <div style="display:flex; align-items:center;">
                    <input type="date" id="my_start" class="date-input-small" onchange="loadMyAttendance()">
                    <span style="margin:0 5px; opacity:0.7;">to</span>
                    <input type="date" id="my_end" class="date-input-small" onchange="loadMyAttendance()">
                    <button class="export-btn" onclick="exportData('MY')">📂 Export Mine</button>
                </div>
            </div>
            <div class="container">
                <table>
                    <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th><th>Hrs</th></tr></thead>
                    <tbody id="myAttendanceBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($edit_mode && $edit_record): ?>
    <div class="overlay">
        <div class="modal-box">
            <div class="modal-header">✏️ Edit Attendance</div>
            
            <div style="background:#f4f9ff; padding:15px; border-radius:5px; margin-bottom:20px; font-size:14px; color:#333;">
                <strong>Employee:</strong> <?php echo htmlspecialchars($edit_record['first_name'] . ' ' . $edit_record['last_name']); ?><br>
                <strong>Date:</strong> <?php echo $edit_record['attendance_date']; ?>
            </div>

            <form method="POST" action="admin_dashboard.php">
                <input type="hidden" name="attendance_id" value="<?php echo $edit_record['attendance_id']; ?>">
                
                <label style="display:block; margin-bottom:5px; font-weight:bold;">New Status:</label>
                <select name="status" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:20px;">
                    <?php 
                    $statuses = ['Present','Absent','Late','Overtime','Undertime','On Leave','Duty on Rest Day'];
                    foreach($statuses as $s) {
                        $sel = ($edit_record['attendance_status'] == $s) ? 'selected' : '';
                        echo "<option value='$s' $sel>$s</option>";
                    }
                    ?>
                </select>

                <button type="submit" name="save_status" style="width:100%; padding:10px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">💾 Save Changes</button>
                <a href="admin_dashboard.php" style="display:block; width:100%; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

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
            if(viewId === 'my-requests-view') loadMyRequests();
            if(viewId === 'my-attendance') loadMyAttendance();
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

            try {
                const res = await fetch(url);
                const data = await res.json();
                const tbody = document.getElementById("masterLogsBody");

                if(data.length === 0) { 
                    tbody.innerHTML = `<tr><td colspan='4' style='text-align:center; padding:20px; color:#999;'>No records found.</td></tr>`; 
                    return; 
                }

                tbody.innerHTML = data.map(log => {
                    const statusColor = log.attendance_status === 'Present' ? '#27ae60' : (log.attendance_status === 'Absent' ? '#e74c3c' : '#f39c12');
                    let nameDisplay = `<strong>${log.first_name} ${log.last_name}</strong>`;
                    if (currentMode === 'COACHES') {
                        nameDisplay = `<span class="clickable-name" style="color:#1e4d8c; cursor:pointer;" onclick="viewTeam(${log.employee_id}, '${log.first_name}')">${log.first_name} ${log.last_name}</span>`;
                    }
                    
                    // EDIT BUTTON: Uses ?edit_id to trigger the PHP overlay
                    return `
                    <tr>
                        <td>${nameDisplay}</td>
                        <td>${log.attendance_date}</td>
                        <td><span class="status-pill" style="color:${statusColor}">${log.attendance_status}</span></td>
                        <td>
                            <a href="admin_dashboard.php?edit_id=${log.attendance_id}" class="action-icon" title="Edit">✏️</a>
                            <span class="action-icon" style="color:#e74c3c;" onclick="deleteRecord(${log.attendance_id})">🗑️</span>
                        </td>
                    </tr>`;
                }).join('');
                filterTable('masterTable', 'adminSearch');
            } catch(e) { console.error("Error loading table:", e); }
        }

        function viewTeam(coachId, coachName) { 
            currentMode = 'TEAM'; 
            currentCoachId = coachId; 
            document.getElementById('viewLabel').innerText = `Team ${coachName}`; 
            refreshTable(); 
        }

        function resetToLeaders() { 
            currentMode = 'COACHES'; 
            currentCoachId = null; 
            refreshTable(); 
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

        async function deleteRecord(id) {
            if(!confirm("Permanently delete?")) return;
            await fetch(`${API}/admin/delete_attendance.php?attendance_id=${id}`);
            refreshTable();
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

        async function loadApprovals() {
            const [leaveRes, otRes] = await Promise.all([fetch(`${API}/admin/get_endorsed_leaves.php`), fetch(`${API}/admin/get_endorsed_ot.php`)]);
            renderQueue(await leaveRes.json(), 'adminLeaveQueue', 'leave');
            renderQueue(await otRes.json(), 'adminOTQueue', 'ot');
        }

        function renderQueue(items, id, type) {
            document.getElementById(id).innerHTML = items.length ? items.map(item => `
                <tr>
                    <td><strong>${item.first_name} ${item.last_name}</strong></td>
                    <td>${item.start_date || item.start_time}</td>
                    <td style="font-style:italic; color:#666;">"${item.reason || item.purpose}"</td>
                    <td>
                        <span class="action-icon" style="color:#27ae60;" onclick="finalApprove(${item.leave_id || item.ot_id}, '${type}', 'APPROVE')">✔️</span>
                        <span class="action-icon" style="color:#e74c3c;" onclick="finalApprove(${item.leave_id || item.ot_id}, '${type}', 'DENY')">❌</span>
                    </td>
                </tr>`).join('') : `<tr><td colspan='4' style='color:#999;'>No pending items</td></tr>`;
        }

        async function finalApprove(id, type, action) {
            const actionText = action === 'APPROVE' ? "Approve" : "Deny";
            if(!confirm(`Are you sure you want to ${actionText} this request?`)) return;
            const endpoint = type === 'leave' ? '/admin/final_approve_leave.php' : '/admin/final_approve_overtime.php';
            await fetch(`${API}${endpoint}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ [type + '_id']: id, action: action }) });
            loadApprovals();
        }

        async function loadMyRequests() {
            try {
                const res = await fetch(`${API}/users/get_my_requests.php?employee_id=${MY_ID}`);
                const data = await res.json();
                
                const leaveBody = document.getElementById("myLeaveLogs");
                if (data.leaves.length === 0) {
                    leaveBody.innerHTML = "<tr><td colspan='5'>No leave requests found.</td></tr>";
                } else {
                    leaveBody.innerHTML = data.leaves.map(l => `
                        <tr>
                            <td>${l.leave_type}</td>
                            <td>${l.start_date} to ${l.end_date}</td>
                            <td>${l.reason}</td>
                            <td><span class="status-pill status-${l.status}">${l.status}</span></td>
                            <td>${l.created_at}</td>
                        </tr>`).join('');
                }

                const otBody = document.getElementById("myOTLogs");
                if (data.overtime.length === 0) {
                    otBody.innerHTML = "<tr><td colspan='5'>No overtime requests found.</td></tr>";
                } else {
                    otBody.innerHTML = data.overtime.map(o => `
                        <tr>
                            <td>${o.ot_type}</td>
                            <td>${o.start_time} to ${o.end_time}</td>
                            <td>${o.purpose}</td>
                            <td><span class="status-pill status-${o.status}">${o.status}</span></td>
                            <td>${o.created_at}</td>
                        </tr>`).join('');
                }
            } catch(e) { console.error(e); }
        }

        async function loadMyAttendance() {
            const start = document.getElementById('my_start').value;
            const end = document.getElementById('my_end').value;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${MY_ID}&start_date=${start}&end_date=${end}`);
            const data = await res.json();
            document.getElementById('myAttendanceBody').innerHTML = data.map(row => `<tr><td>${row.attendance_date}</td><td>${row.time_in || '--:--'}</td><td>${row.time_out || '--:--'}</td><td>${row.attendance_status}</td><td>${row.total_hours || '0.00'} hrs</td></tr>`).join('');
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
            if(result.success) { document.getElementById(formId).reset(); loadMyRequests(); }
        }

        window.onload = function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), 0, 1);
            const formatDate = (d) => d.toISOString().split('T')[0];
            
            document.getElementById('r_start').value = formatDate(firstDay);
            document.getElementById('r_end').value = formatDate(today);
            document.getElementById('my_start').value = formatDate(firstDay);
            document.getElementById('my_end').value = formatDate(today);
            
            refreshTable(); 
            loadApprovals();
        }
    </script>
</body>
</html>