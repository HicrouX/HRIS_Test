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
if ($_SESSION['role_id'] == 2) { 
    header("Location: coach_dashboard.php"); 
    exit; 
}
if ($_SESSION['role_id'] >= 3) { 
    header("Location: admin_dashboard.php"); 
    exit; 
}

// 4. Proceed as Employee
require_once 'api/middleware/auth.php';
verifyAccess([1]); 

$api_base_url = "http://localhost/hris_official/api"; 
$emp_id = $_SESSION['employee_id'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iREPLY - Employee Portal</title>
    <style>
        :root {
            --primary-blue: #1e4d8c;
            --accent-blue: #3498db;
            --bg-dark: #1a1a1a;
            --text-gray: #666;
        }

        body { font-family: 'Segoe UI', Roboto, Helvetica, sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); color: #333; overflow: hidden; }
        
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; transition: all 0.3s; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: var(--text-gray); font-size: 14px; text-decoration: none; transition: 0.2s; }
        .nav-item:hover { background: #f0f4f8; color: var(--primary-blue); }
        .nav-active { background: var(--accent-blue); color: #fff !important; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); }
        
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; }
        .content-view { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .view-active { display: flex; }

        .view-header { background: var(--primary-blue); color: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .date-range-container { display: flex; align-items: center; gap: 10px; font-size: 13px; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px; }
        .date-input-small { background: transparent; border: none; color: white; font-size: 13px; cursor: pointer; outline: none; }
        .date-input-small::-webkit-calendar-picker-indicator { filter: invert(1); }
        .filter-bar { background: #f8f9fa; padding: 15px 40px; border-bottom: 1px solid #eee; display: flex; gap: 15px; }
        select { padding: 8px 15px; border-radius: 6px; border: 1px solid #ddd; font-size: 13px; background: white; }

        .table-wrapper { overflow-x: auto; padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th { text-align: left; padding: 18px 10px; font-size: 12px; color: #888; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 18px 10px; font-size: 14px; color: #444; border-bottom: 1px solid #f9f9f9; }
        .status-pill { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; margin-left: 10px; text-decoration: none; }
        .export-btn:hover { background: #219150; }

        .form-container { padding: 40px; max-width: 900px; }
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        textarea { grid-column: span 2; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; resize: vertical; }
        .submit-btn { grid-column: span 2; padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); transition: 0.3s; }
        .submit-btn:hover { background: var(--primary-blue); }

        /* Status Colors */
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Endorsed { background: #e3f2fd; color: #3498db; }
        .status-Approved { background: #e8f5e9; color: #27ae60; }
        .status-Denied { background: #ffebee; color: #e74c3c; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; padding: 20px 10px; align-items: center; }
            .logo, .nav-item span, .sidebar div:nth-child(2) { display: none; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div style="font-size: 11px; font-weight: bold; margin-bottom: 25px; color: var(--text-gray); text-align: center;">ID: <?php echo $emp_id; ?></div>
        
        <a class="nav-item nav-active" onclick="toggleView('attendance-view', this)"><span>Attendance</span></a>
        <a class="nav-item" onclick="toggleView('my-requests-view', this)"><span>My Requests</span></a>
        <a class="nav-item" onclick="toggleView('request-view', this)"><span>File Request</span></a>
        
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;"><span>Log Out</span></a>
    </div>

    <div class="main-content">
        
        <div id="attendance-view" class="content-view view-active">
            <div class="view-header">
                <h2 style="margin:0; font-weight: 500;">Attendance History</h2>
                <div class="date-range-container">
                    <input type="date" id="range_start" class="date-input-small" onchange="loadMyAttendance()">
                    <span style="opacity: 0.5;">to</span>
                    <input type="date" id="range_end" class="date-input-small" onchange="loadMyAttendance()">
                    <button class="export-btn" onclick="exportData()">📂 Export</button>
                </div>
            </div>

            <div class="filter-bar">
                <select id="nameSort" onchange="loadMyAttendance()">
                    <option value="ASC">Name A-Z</option>
                    <option value="DESC">Name Z-A</option>
                </select>
                <select id="timeFilter" onchange="loadMyAttendance()">
                    <option value="AM-PM">TIME AM - PM</option>
                    <option value="PM-AM">TIME PM - AM</option>
                </select>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Work Hours</th></tr></thead>
                    <tbody id="attendanceLogs"></tbody>
                </table>
            </div>
        </div>

        <div id="my-requests-view" class="content-view">
            <div class="view-header" style="background: #8e44ad;">
                <h2 style="margin:0; font-weight: 500;">My Request Status</h2>
                <button class="export-btn" onclick="loadMyRequests()">🔄 Refresh</button>
            </div>
            
            <div class="table-wrapper">
                <h3 style="color:#666;">Leave Requests</h3>
                <table>
                    <thead><tr><th>Type</th><th>Date Range</th><th>Reason</th><th>Status</th><th>Filed On</th></tr></thead>
                    <tbody id="myLeaveLogs"></tbody>
                </table>

                <h3 style="color:#666; margin-top:40px;">Overtime Requests</h3>
                <table>
                    <thead><tr><th>Type</th><th>Time Range</th><th>Purpose</th><th>Status</th><th>Filed On</th></tr></thead>
                    <tbody id="myOTLogs"></tbody>
                </table>
            </div>
        </div>

        <div id="request-view" class="content-view">
            <div class="view-header" style="background: var(--accent-blue);">
                <h2 style="margin:0; font-weight: 500;">Filing Center</h2>
            </div>
            
            <div class="form-container">
                <div class="form-card">
                    <h3 style="margin-top:0; color: var(--primary-blue);">📝 File Leave Application</h3>
                    <form id="leaveForm" class="form-grid">
                        <select id="l_type" class="form-input">
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                        </select>
                        <div style="visibility:hidden"></div>
                        <input type="date" id="l_start" required>
                        <input type="date" id="l_end" required>
                        <textarea id="l_reason" placeholder="Briefly explain your reason for leave..." rows="3" required></textarea>
                        <button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit Leave Application</button>
                    </form>
                </div>

                <div class="form-card" style="border-left-color: #27ae60;">
                    <h3 style="margin-top:0; color: #27ae60;">⏰ Overtime Request</h3>
                    <form id="otForm" class="form-grid">
                        <select id="ot_type" class="form-input">
                            <option value="Regular Overtime">Regular Overtime</option>
                            <option value="Duty on Rest Day">Duty on Rest Day</option>
                        </select>
                        <div></div>
                        <input type="datetime-local" id="ot_start" title="OT Start Time" required>
                        <input type="datetime-local" id="ot_end" title="OT End Time" required>
                        <textarea id="ot_purpose" placeholder="Purpose of overtime..." rows="2" required></textarea>
                        <button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit OT Request</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        const API = "<?php echo $api_base_url; ?>";
        const EMP_ID = "<?php echo $emp_id; ?>";

        function toggleView(viewId, navBtn) {
            document.querySelectorAll('.content-view').forEach(v => v.classList.remove('view-active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('view-active');
            navBtn.classList.add('nav-active');
            
            if(viewId === 'attendance-view') loadMyAttendance();
            if(viewId === 'my-requests-view') loadMyRequests(); // Load requests when tab is clicked
        }

        function exportData() {
            const start = document.getElementById('range_start').value;
            const end = document.getElementById('range_end').value;
            window.location.href = `${API}/export/export_csv.php?mode=MY&start=${start}&end=${end}`;
        }

        async function loadMyAttendance() {
            try {
                const start = document.getElementById('range_start').value;
                const end = document.getElementById('range_end').value;
                const sort = document.getElementById('nameSort').value;
                const time = document.getElementById('timeFilter').value;

                let url = `${API}/users/get_my_attendance.php?employee_id=${EMP_ID}&start_date=${start}&end_date=${end}&sort=${sort}&time_range=${time}`;
                const res = await fetch(url);
                const data = await res.json();
                const tbody = document.getElementById("attendanceLogs");
                
                if(data.length === 0) { tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No records found.</td></tr>"; return; }

                tbody.innerHTML = data.map(row => {
                    let statusStyle = "background:#e8f5e9; color:#2e7d32;"; 
                    if(row.attendance_status === 'Late') statusStyle = "background:#fff3e0; color:#e65100;";
                    if(row.attendance_status === 'On Leave') statusStyle = "background:#e3f2fd; color:#1565c0;";
                    if(row.attendance_status === 'Absent') statusStyle = "background:#ffebee; color:#c62828;";
                    return `<tr><td><strong>${row.attendance_date}</strong></td><td>${row.time_in || '--:--'}</td><td>${row.time_out || '--:--'}</td><td><span class="status-pill" style="${statusStyle}">${row.attendance_status}</span></td><td>${row.total_hours || '0.00'} hrs</td></tr>`;
                }).join('');
            } catch (err) { console.error("Error loading logs:", err); }
        }

        // NEW: Load My Requests (Leaves & OT)
        async function loadMyRequests() {
            try {
                const res = await fetch(`${API}/users/get_my_requests.php?employee_id=${EMP_ID}`);
                const data = await res.json();
                
                // Render Leaves
                const leaveBody = document.getElementById("myLeaveLogs");
                if (data.leaves.length === 0) {
                    leaveBody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No leave requests found.</td></tr>";
                } else {
                    leaveBody.innerHTML = data.leaves.map(l => `
                        <tr>
                            <td>${l.leave_type}</td>
                            <td>${l.start_date} to ${l.end_date}</td>
                            <td>${l.reason}</td>
                            <td><span class="status-pill status-${l.status}">${l.status}</span></td>
                            <td style="font-size:12px; color:#888;">${l.created_at}</td>
                        </tr>
                    `).join('');
                }

                // Render OT
                const otBody = document.getElementById("myOTLogs");
                if (data.overtime.length === 0) {
                    otBody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No overtime requests found.</td></tr>";
                } else {
                    otBody.innerHTML = data.overtime.map(o => `
                        <tr>
                            <td>${o.ot_type}</td>
                            <td>${o.start_time}<br>to ${o.end_time}</td>
                            <td>${o.purpose}</td>
                            <td><span class="status-pill status-${o.status}">${o.status}</span></td>
                            <td style="font-size:12px; color:#888;">${o.created_at}</td>
                        </tr>
                    `).join('');
                }
            } catch(e) { console.error(e); }
        }

        async function submitRequest(type) {
            const isLeave = type === 'leave';
            const endpoint = isLeave ? '/users/file_leave.php' : '/users/file_overtime.php';
            const formId = isLeave ? 'leaveForm' : 'otForm';
            
            const payload = isLeave ? {
                employee_id: EMP_ID,
                leave_type: document.getElementById('l_type').value,
                start_date: document.getElementById('l_start').value,
                end_date: document.getElementById('l_end').value,
                reason: document.getElementById('l_reason').value,
                agreement_1: 1, agreement_2: 1
            } : {
                employee_id: EMP_ID,
                ot_type: document.getElementById('ot_type').value,
                start_time: document.getElementById('ot_start').value,
                end_time: document.getElementById('ot_end').value,
                purpose: document.getElementById('ot_purpose').value,
                agreement_1: 1, agreement_2: 1
            };

            try {
                const res = await fetch(`${API}${endpoint}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                alert(result.success || result.error);
                if(result.success) {
                    document.getElementById(formId).reset();
                    // If user is on the request tab, reload it to show the new pending item
                    if(document.getElementById('my-requests-view').classList.contains('view-active')) {
                        loadMyRequests();
                    }
                }
            } catch (err) { alert("Submission failed. Check API."); }
        }
        
        window.onload = function() {
            // Set date inputs to this month
            const d = new Date(), y = d.getFullYear(), m = d.getMonth();
            const firstDay = new Date(y, m, 1).toISOString().split('T')[0];
            const today = d.toISOString().split('T')[0];
            
            document.getElementById('range_start').value = firstDay;
            document.getElementById('range_end').value = today;
            
            loadMyAttendance();
        };
    </script>
</body>
</html>