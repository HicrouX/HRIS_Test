<?php
// FILE: employee_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role_id'] == 2) { header("Location: coach_dashboard.php"); exit; }
if ($_SESSION['role_id'] >= 3) { header("Location: admin_dashboard.php"); exit; }

require_once 'api/middleware/auth.php';
verifyAccess([1]); 

$api_base_url = "http://localhost/hris_official/api"; 
$emp_id = $_SESSION['employee_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>iREPLY - Employee Portal</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #1a1a1a; --text-gray: #666; }
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
        .filter-bar { background: #f8f9fa; padding: 15px 40px; border-bottom: 1px solid #eee; display: flex; gap: 15px; }
        select { padding: 8px 15px; border-radius: 6px; border: 1px solid #ddd; font-size: 13px; background: white; }
        .table-wrapper { overflow-x: auto; padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th { text-align: left; padding: 18px 10px; font-size: 12px; color: #888; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 18px 10px; font-size: 14px; color: #444; border-bottom: 1px solid #f9f9f9; }
        .status-pill { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; margin-left: 10px; text-decoration: none; }
        .form-container { padding: 40px; max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 15px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        .submit-btn { width: 100%; padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); transition: 0.3s; margin-top: 10px; }
        .readonly-field { background: #eee; color: #777; cursor: not-allowed; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div style="font-size: 11px; font-weight: bold; margin-bottom: 25px; color: var(--text-gray); text-align: center;">ID: <?php echo $emp_id; ?></div>
        <a class="nav-item nav-active" onclick="toggleView('attendance-view', this)"><span>Attendance</span></a>
        <a class="nav-item" onclick="toggleView('my-requests-view', this)"><span>My Requests</span></a>
        <a class="nav-item" onclick="toggleView('request-view', this)"><span>Filing Center</span></a>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;"><span>Log Out</span></a>
    </div>

    <div class="main-content">
        <div id="attendance-view" class="content-view view-active">
            <div class="view-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <button class="export-btn" onclick="exportData()">Export Excel</button>
                    <h2 style="margin:0; font-weight: 500;">Attendance History</h2>
                </div>
                <div class="date-range-container">
                    <input type="date" id="range_start" class="date-input-small" onchange="loadMyAttendance()">
                    <span style="opacity: 0.5;">to</span>
                    <input type="date" id="range_end" class="date-input-small" onchange="loadMyAttendance()">
                </div>
            </div>
            <div class="table-wrapper">
                <table><thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Work Hours</th></tr></thead><tbody id="attendanceLogs"></tbody></table>
            </div>
        </div>

        <div id="my-requests-view" class="content-view">
            <div class="view-header" style="background: #8e44ad;">
                <h2 style="margin:0; font-weight: 500;">My Request Status</h2>
                <button class="export-btn" onclick="loadMyRequests()">Refresh</button>
            </div>
            <div class="table-wrapper">
                <h3 style="color:#666;">Leave Requests</h3>
                <table><thead><tr><th>Type</th><th>Date Range</th><th>Reason</th><th>Status</th><th>Filed On</th><th>Endorsed By</th><th>Approved By</th></tr></thead><tbody id="myLeaveLogs"></tbody></table>
                <h3 style="color:#666;">Overtime Requests</h3>
                <table><thead><tr><th>Type</th><th>Time Range</th><th>Purpose</th><th>Status</th><th>Filed On</th><th>Endorsed By</th><th>Approved By</th></tr></thead><tbody id="myOTLogs"></tbody></table>
                <h3 style="color:#666;">My Disputes</h3>
                <table><thead><tr><th>Type</th><th>Date</th><th>Reason</th><th>Status</th><th>Filed On</th></tr></thead><tbody id="myDisputeLogs"></tbody></table>
            </div>
        </div>

        <div id="request-view" class="content-view">
            <div class="view-header" style="background: var(--accent-blue);"><h2 style="margin:0; font-weight: 500;">Filing Center</h2></div>
            <div class="form-container">
                <div class="form-card">
                    <h3 style="margin-top:0; color: var(--primary-blue);">File Leave</h3>
                    <form id="leaveForm" class="form-grid">
                        <select id="l_type" class="form-input"><option value="Sick Leave">Sick Leave</option><option value="Vacation Leave">Vacation Leave</option></select>
                        <input type="date" id="l_start" required><input type="date" id="l_end" required>
                        <textarea id="l_reason" placeholder="Reason..." rows="3" required></textarea>
                        <button type="button" class="submit-btn" onclick="submitRequest('leave')">Submit Leave</button>
                    </form>
                </div>
                
                <div class="form-card" style="border-left-color: #27ae60;">
                    <h3 style="margin-top:0; color: #27ae60;">File Overtime</h3>
                    <form id="otForm" class="form-grid">
                        <select id="ot_type" class="form-input"><option value="Regular Overtime">Regular Overtime</option><option value="Duty on Rest Day">Duty on Rest Day</option></select>
                        <input type="datetime-local" id="ot_start" required><input type="datetime-local" id="ot_end" required>
                        <textarea id="ot_purpose" placeholder="Purpose..." rows="2" required></textarea>
                        <button type="button" class="submit-btn" style="background:#27ae60;" onclick="submitRequest('ot')">Submit OT</button>
                    </form>
                </div>

                <div class="form-card" style="border-left-color: #e74c3c; grid-column: span 2;">
                    <h3 style="margin-top:0; color: #e74c3c;">Attendance Dispute</h3>
                    <p style="font-size:12px; color:#666;">Forgot to time out? Marked absent incorrectly? File a dispute here.</p>
                    <form id="disputeForm" class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <input type="text" value="Cluster: Auto-Detected" class="readonly-field" readonly>
                        <input type="text" value="Coach: Auto-Detected" class="readonly-field" readonly>
                        <select id="d_type" required><option value="" disabled selected>Select Dispute Type</option><option>Forgot Time In/Out</option><option>System Error</option><option>Official Business</option><option>Incorrect Status</option><option>Breaktime</option><option>Lunch Break</option></select>
                        <div style="grid-column: span 2;">
                            <label style="font-size:12px; font-weight:bold;">Date of Incident:</label>
                            <input type="date" id="d_date" required>
                        </div>
                        <textarea id="d_reason" placeholder="Explain why the attendance status is wrong..." rows="3" required style="grid-column: span 2;"></textarea>
                        <button type="button" class="submit-btn" style="background:#e74c3c; grid-column: span 2;" onclick="submitRequest('dispute')">Submit Dispute</button>
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
            if(viewId === 'my-requests-view') loadMyRequests();
        }

        async function loadMyAttendance() {
            const start = document.getElementById('range_start').value;
            const end = document.getElementById('range_end').value;
            const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${EMP_ID}&start_date=${start}&end_date=${end}`);
            const data = await res.json();
            document.getElementById("attendanceLogs").innerHTML = data.map(row => `<tr><td>${row.attendance_date}</td><td>${row.time_in || '--'}</td><td>${row.time_out || '--'}</td><td><span class="status-pill status-${row.attendance_status.replace(/\s/g,'')}">${row.attendance_status}</span></td><td>${row.total_hours || '0.00'}</td></tr>`).join('');
        }

        async function loadMyRequests() {
            const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${EMP_ID}`);
            const data = await res.json();
            const leaves = data.filter(item => item.type === 'Leave');
            const overtime = data.filter(item => item.type === 'Overtime');
            const disputes = data.filter(item => item.type === 'Dispute');
            const getEndorser = (item) => item.coach_first ? `<span style="color:#d35400; font-weight:600;">${item.coach_first} ${item.coach_last}</span>` : '<span style="color:#ccc;">-</span>';
            const getApprover = (item) => item.admin_first ? `<span style="color:#27ae60; font-weight:600;">${item.admin_first} ${item.admin_last}</span>` : '<span style="color:#ccc;">-</span>';
            const renderRow = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}<br>${item.end_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td><td>${getEndorser(item)}</td><td>${getApprover(item)}</td></tr>`;
            const renderDisp = (item) => `<tr><td>${item.sub_type}</td><td>${item.start_date}</td><td>${item.reason}</td><td><span class="status-pill status-${item.status}">${item.status}</span></td><td>${item.created_at}</td></tr>`;
            
            document.getElementById("myLeaveLogs").innerHTML = leaves.map(renderRow).join('');
            document.getElementById("myOTLogs").innerHTML = overtime.map(renderRow).join('');
            document.getElementById("myDisputeLogs").innerHTML = disputes.map(renderDisp).join('');
        }

        async function submitRequest(type) {
            let endpoint, payload, formId;
            if (type === 'leave') {
                endpoint = '/users/file_leave.php'; formId = 'leaveForm';
                payload = { employee_id: EMP_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1: 1, agreement_2: 1 };
            } else if (type === 'ot') {
                // 🔥 NEW 2-HOUR LIMIT VALIDATION
                const start = new Date(document.getElementById('ot_start').value);
                const end = new Date(document.getElementById('ot_end').value);
                const diffMs = end - start;
                const diffHrs = diffMs / (1000 * 60 * 60);
                if (diffHrs > 2) { alert("⚠️ Cannot submit: Overtime is limited to 2 hours per request."); return; }

                endpoint = '/users/file_overtime.php'; formId = 'otForm';
                payload = { employee_id: EMP_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1: 1, agreement_2: 1 };
            } else if (type === 'dispute') {
                endpoint = '/users/file_dispute.php'; formId = 'disputeForm';
                // 🆕 New Dropdown Value Included
                payload = { employee_id: EMP_ID, date: document.getElementById('d_date').value, dispute_type: document.getElementById('d_type').value, reason: document.getElementById('d_reason').value };
            }

            try {
                const res = await fetch(`${API}${endpoint}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const result = await res.json();
                alert(result.success || result.error);
                if(result.success) document.getElementById(formId).reset();
            } catch (err) { alert("Submission failed."); }
        }
        
        function exportData() {
            const start = document.getElementById('range_start').value;
            const end = document.getElementById('range_end').value;
            window.location.href = `${API}/export/export_excel.php?mode=MY&start=${start}&end=${end}`;
        }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value = s; document.getElementById('range_end').value = e;
            loadMyAttendance();
        };
    </script>
</body>
</html>