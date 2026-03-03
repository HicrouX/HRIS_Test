<?php
// FILE: employee_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'api/config/db.php'; 
require_once 'api/middleware/auth.php';

// Verify Employee Access
if (!isset($_SESSION['role_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role_id'] == 2) { header("Location: coach_dashboard.php"); exit; }
if ($_SESSION['role_id'] >= 3) { header("Location: admin_dashboard.php"); exit; }

verifyAccess([1]); 

$api_base_url = "http://localhost/hris_official/api"; 
$emp_id = $_SESSION['employee_id'];

// FETCH REAL NAME
$emp_name = "Employee";
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $stmt->execute([$emp_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $emp_name = $user['first_name'] . ' ' . $user['last_name'];
    }
} catch (Exception $e) { /* Ignore */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>iREPLY - Employee Portal</title>
    <style>
        :root { --primary-blue: #1e4d8c; --accent-blue: #3498db; --bg-dark: #1a1a1a; --text-gray: #666; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-dark); color: #333; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary-blue); font-weight: bold; font-size: 26px; margin-bottom: 20px; text-align: center; }
        
        /* User Info */
        .user-info { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .user-name { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 5px; text-transform: capitalize; }
        .user-id { font-size: 11px; color: #888; background: #f4f4f4; padding: 3px 10px; border-radius: 12px; display: inline-block; }

        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; color: var(--text-gray); font-size: 14px; transition: 0.2s; text-decoration: none; display: block; }
        .nav-item:hover { background: #f0f4f8; color: var(--primary-blue); }
        .nav-active { background: var(--accent-blue); color: #fff !important; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); }
        
        /* Main Content - SCROLLING FIX APPLIED HERE */
        .main-content { flex: 1; min-height: 0; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .content-view { display: none; flex-direction: column; flex: 1; min-height: 0; overflow-y: auto; }
        .view-active { display: flex; }
        
        .view-header { background: var(--primary-blue); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .date-range-container { display: flex; align-items: center; gap: 10px; font-size: 13px; background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 8px; }
        .date-input-small { background: transparent; border: none; color: white; font-size: 13px; cursor: pointer; outline: none; }
        
        /* Compact Table */
        .table-wrapper { overflow-x: auto; padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; margin-top:10px; }
        th { text-align: left; padding: 8px 10px; font-size: 12px; color: #555; text-transform: uppercase; border-bottom: 2px solid #eee; cursor: pointer; background: #f8f9fa; font-weight: 600; position: relative; }
        th:hover { background: #ebedef; }
        th::after { content: ' ⬍'; font-size: 10px; color: #ccc; position: absolute; right: 5px; }
        td { padding: 8px 10px; font-size: 12px; color: #444; border-bottom: 1px solid #f9f9f9; }
        
        /* Status Pills */
        .status-pill { padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Approved { background: #e8f5e9; color: #2e7d32; }
        .status-Denied { background: #ffebee; color: #c62828; }
        .status-Endorsed { background: #e3f2fd; color: #1565c0; }
        .status-Tardy { background: #fff3e0; color: #e67e22; } 
        
        .export-btn { background: #27ae60; border: none; padding: 8px 15px; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; margin-left: 10px; text-decoration: none; }
        
        /* Forms */
        .form-container { padding: 40px; max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .form-card { background: #fafbfc; padding: 30px; border-radius: 8px; border-left: 5px solid var(--accent-blue); }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 15px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; box-sizing: border-box; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: white; background: var(--accent-blue); transition: 0.3s; margin-top: 10px; }
        .readonly-field { background: #eee; color: #777; cursor: not-allowed; }
        
        /* Filters */
        .search-box { padding: 6px 10px; border-radius: 6px; border: 1px solid #ddd; font-size: 12px; width: 200px; outline: none; margin-left:10px; color: black; }
        select.search-box { background: #fff; cursor: pointer; }

        .close-x { float: right; font-size: 24px; cursor: pointer; color: #999; line-height: 1; }
        .close-x:hover { color: #333; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">iREPLY</div>
        
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($emp_name); ?></div>
            <div class="user-id">ID: <?php echo htmlspecialchars($emp_id); ?></div>
        </div>

        <a class="nav-item nav-active" onclick="toggleView('attendance-view', this)"><span>Attendance</span></a>
        <a class="nav-item" onclick="toggleView('my-requests-view', this)"><span>My Requests</span></a>
        <a class="nav-item" onclick="toggleView('request-view', this)"><span>Filing Center</span></a>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;"><span>Log Out</span></a>
    </div>

    <div class="main-content">
        <div id="attendance-view" class="content-view view-active">
            <div class="view-header">
                <div style="display:flex; align-items:center;">
                    <h2 style="margin:0; font-weight: 500;">Attendance History</h2>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 15px; font-size: 13px; font-weight: bold;">
                    Total Hours: <span id="totalHoursSum">0.00</span>
                </div>
                <div class="date-range-container">
                    <input type="date" id="range_start" class="date-input-small" onchange="loadMyAttendance()">
                    <span style="opacity: 0.5;">to</span>
                    <input type="date" id="range_end" class="date-input-small" onchange="loadMyAttendance()">
                </div>
            </div>
            
            <div class="table-wrapper">
                <div style="margin-bottom:10px;">
                    <select class="search-box" onchange="filterTable('attTable', this.value)" style="margin-left:0; width: 150px;">
                        <option value="">Show All Statuses</option>
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Tardy">Tardy</option>
                        <option value="Overtime">Overtime</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Undertime">Undertime</option>
                        <option value="Absent">Absent</option>
                        <option value="Duty on Rest Day">Duty on Rest Day</option>
                    </select>
                </div>
                <table id="attTable">
                    <thead><tr onclick="sortTable('attTable', 0)"><th>Date ⬍</th><th>Time In</th><th>Time Out</th><th>Break In</th><th>Break Out</th><th>Lunch</th><th>Status</th><th>Work Hours</th></tr></thead>
                    <tbody id="attendanceLogs"></tbody>
                </table>
            </div>
        </div>

        <div id="my-requests-view" class="content-view">
            <div class="view-header" style="background: #8e44ad;">
                <h2 style="margin:0; font-weight: 500;">My Request Status</h2>
                <button class="export-btn" onclick="loadMyRequests()">Refresh</button>
            </div>
            <div class="table-wrapper">
                <h3 style="color:#666;">Leave Requests</h3>
                <table id="myLeaveTable">
                    <thead><tr onclick="sortTable('myLeaveTable', 0)"><th>Type ⬍</th><th>Date Range ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Endorsed By</th><th>Approved By</th></tr></thead>
                    <tbody id="myLeaveLogs"></tbody>
                </table>
                
                <h3 style="color:#666; margin-top:20px;">Overtime Requests</h3>
                <table id="myOTTable">
                    <thead><tr onclick="sortTable('myOTTable', 0)"><th>Type ⬍</th><th>Time Range ⬍</th><th>Purpose</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Endorsed By</th><th>Approved By</th></tr></thead>
                    <tbody id="myOTLogs"></tbody>
                </table>
                
                <h3 style="color:#666; margin-top:20px;">My Disputes</h3>
                <table id="myDisputeTable">
                    <thead><tr onclick="sortTable('myDisputeTable', 0)"><th>Type ⬍</th><th>Date ⬍</th><th>Reason</th><th>Status ⬍</th><th>Filed On ⬍</th><th>Admin Remarks</th></tr></thead>
                    <tbody id="myDisputeLogs"></tbody>
                </table>
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
                    <form id="disputeForm" class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <input type="text" value="Cluster: Auto-Detected" class="readonly-field" readonly>
                        <input type="text" value="Coach: Auto-Detected" class="readonly-field" readonly>
                        <select id="d_type" required onchange="toggleTimeInput(this.value)">
                            <option value="" disabled selected>Select Dispute Type</option>
                            <option>Forgot Time In/Out</option>
                            <option>System Error</option>
                            <option>Official Business</option>
                            <option>Incorrect Status</option>
                            <option>Breaktime</option>
                            <option>Lunch Break</option>
                        </select>
                        <div style="grid-column: span 2;"><label style="font-size:12px; font-weight:bold;">Date of Incident:</label><input type="date" id="d_date" required></div>
                        <div id="timeInputDiv" style="display:none; grid-column: span 2;">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed In:</label><input type="time" id="d_time_in"></div>
                                <div><label style="font-weight:bold; color:red; font-size:11px;">Proposed Out:</label><input type="time" id="d_time_out"></div>
                            </div>
                        </div>
                        <textarea id="d_reason" placeholder="Explain the discrepancy..." rows="3" required style="grid-column: span 2;"></textarea>
                        <button type="button" class="submit-btn" style="background: #e74c3c; grid-column: span 2;" onclick="submitRequest('dispute')">Submit Dispute</button>
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

        function toggleTimeInput(val) { document.getElementById('timeInputDiv').style.display = val.includes('Forgot') ? 'block' : 'none'; }

        function updateHoursSum(tid) {
            let sum = 0;
            const rows = Array.from(document.getElementById(tid).tBodies[0].rows);
            rows.forEach(r => {
                if (r.style.display !== 'none') {
                    let val = parseFloat(r.cells[7].innerText);
                    if (!isNaN(val)) sum += val;
                }
            });
            document.getElementById('totalHoursSum').innerText = sum.toFixed(2);
        }

        function filterTable(tid, val) {
            let filter = val.toLowerCase();
            let rows = document.getElementById(tid).tBodies[0].rows;
            Array.from(rows).forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
            updateHoursSum(tid);
        }

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

        async function loadMyAttendance() {
            const start = document.getElementById('range_start').value;
            const end = document.getElementById('range_end').value;
            try {
                const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${EMP_ID}&start_date=${start}&end_date=${end}`);
                const data = await res.json();
                document.getElementById("attendanceLogs").innerHTML = data.map(row => `<tr><td>${row.date}</td><td>${row.time_in}</td><td>${row.time_out}</td><td>${row.break_in}</td><td>${row.break_out}</td><td>${row.lunch_break}</td><td><span class="status-pill status-${row.status.replace(/\s/g,'')}">${row.status}</span></td><td>${row.total_hours}</td></tr>`).join('');
                updateHoursSum('attTable');
            } catch (err) {
                console.error("Attendance Error:", err);
            }
        }

        async function loadMyRequests() {
            try {
                const res = await fetch(`${API}/users/get_my_request_history.php?employee_id=${EMP_ID}`);
                const data = await res.json();
                if (data.error) { console.error("API Error:", data.error); return; }

                const leaves = data.filter(item => item.type === 'Leave');
                const overtime = data.filter(item => item.type === 'Overtime');
                const disputes = data.filter(item => item.type === 'Dispute');

                const getEndorser = (item) => item.coach_first ? 
                    `<span style="color:#d35400; font-weight:600;">${item.coach_first} ${item.coach_last}</span>` : 
                    '<span style="color:#ccc;">-</span>';

                const getApprover = (item) => item.admin_first ? 
                    `<span style="color:#27ae60; font-weight:600;">${item.admin_first} ${item.admin_last}</span>` : 
                    '<span style="color:#ccc;">-</span>';

                const renderRow = (item) => `
                    <tr>
                        <td>${item.sub_type}</td>
                        <td>${item.start_date}<br>${item.end_date}</td>
                        <td>${item.reason}</td>
                        <td><span class="status-pill status-${item.status}">${item.status}</span></td>
                        <td>${new Date(item.created_at).toLocaleDateString()}</td>
                        <td>${getEndorser(item)}</td>
                        <td>${getApprover(item)}</td>
                    </tr>`;

                const renderDisp = (item) => `
                    <tr>
                        <td>${item.sub_type}</td>
                        <td>${item.start_date}</td>
                        <td>${item.reason}</td>
                        <td><span class="status-pill status-${item.status}">${item.status}</span></td>
                        <td>${new Date(item.created_at).toLocaleDateString()}</td>
                        <td style="color:blue; font-style:italic;">${item.remarks || '--'}</td>
                    </tr>`;
                
                document.getElementById("myLeaveLogs").innerHTML = leaves.length ? leaves.map(renderRow).join('') : '<tr><td colspan="7" style="text-align:center">No records found</td></tr>';
                document.getElementById("myOTLogs").innerHTML = overtime.length ? overtime.map(renderRow).join('') : '<tr><td colspan="7" style="text-align:center">No records found</td></tr>';
                document.getElementById("myDisputeLogs").innerHTML = disputes.length ? disputes.map(renderDisp).join('') : '<tr><td colspan="6" style="text-align:center">No records found</td></tr>';
            } catch (err) {
                console.error("Requests Error:", err);
            }
        }

        async function submitRequest(type) {
            let endpoint, payload, formId;
            if (type === 'leave') {
                endpoint = '/users/file_leave.php'; formId = 'leaveForm';
                payload = { employee_id: EMP_ID, leave_type: document.getElementById('l_type').value, start_date: document.getElementById('l_start').value, end_date: document.getElementById('l_end').value, reason: document.getElementById('l_reason').value, agreement_1: 1, agreement_2: 1 };
            } else if (type === 'ot') {
                const start = new Date(document.getElementById('ot_start').value);
                const end = new Date(document.getElementById('ot_end').value);
                const diffMs = end - start;
                const diffHrs = diffMs / (1000 * 60 * 60);
                if (diffHrs > 2) { alert("⚠️ Cannot submit: Overtime is limited to 2 hours per request."); return; }
                endpoint = '/users/file_overtime.php'; formId = 'otForm';
                payload = { employee_id: EMP_ID, ot_type: document.getElementById('ot_type').value, start_time: document.getElementById('ot_start').value, end_time: document.getElementById('ot_end').value, purpose: document.getElementById('ot_purpose').value, agreement_1: 1, agreement_2: 1 };
            } else if (type === 'dispute') {
                endpoint = '/users/file_dispute.php'; formId = 'disputeForm';
                let reason = document.getElementById('d_reason').value;
                if(document.getElementById('d_type').value.includes('Forgot')) {
                    reason += " [Proposed In: "+document.getElementById('d_time_in').value+", Proposed Out: "+document.getElementById('d_time_out').value+"]";
                }
                payload = { employee_id: EMP_ID, date: document.getElementById('d_date').value, dispute_type: document.getElementById('d_type').value, reason: reason };
            }

            try {
                const res = await fetch(`${API}${endpoint}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const result = await res.json();
                alert(result.success || result.error);
                if(result.success) {
                    document.getElementById(formId).reset();
                    loadMyRequests(); 
                }
            } catch (err) { alert("Submission failed."); }
        }
        
        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), 0, 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('range_start').value = s; document.getElementById('range_end').value = e;
            loadMyAttendance();
            loadMyRequests();
        };
    </script>
</body>
</html>