<?php
// MUST BE THE VERY FIRST LINE
session_start();
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
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: #222; overflow: hidden; }
        
        /* Sidebar Navigation */
        .sidebar { width: 240px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: #1e4d8c; font-weight: bold; font-size: 26px; margin-bottom: 40px; }
        .nav-item { padding: 12px 20px; margin: 5px 0; border-radius: 25px; cursor: pointer; color: #666; font-size: 14px; transition: 0.2s; }
        .nav-active { background: #FFC107; color: #000 !important; font-weight: bold; }
        
        /* Content Views */
        .main-content { flex: 1; background: #fff; margin: 15px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .view-content { display: none; flex-direction: column; height: 100%; overflow-y: auto; }
        .active-view { display: flex; }

        .header { background: #1e4d8c; color: white; padding: 25px 40px; }
        .container { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid #f9f9f9; font-size: 13px; }
        
        /* Filing Forms */
        .form-card { background: #fafbfc; padding: 25px; border-radius: 8px; border-left: 5px solid #FFC107; margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        .btn-submit { padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: #1e4d8c; grid-column: span 2; }
    </style>
</head>
<body>
    <input type="hidden" id="coach_id" value="<?php echo $coach_id; ?>">

    <div class="sidebar">
        <div class="logo">iREPLY</div>
        <div class="nav-item nav-active" onclick="switchView('team-view', this)">Team Attendance</div>
        <div class="nav-item" onclick="switchView('manage-requests', this)">Manage Endorsements</div>
        <div class="nav-item" onclick="switchView('my-filing', this)">My Filing Center</div>
        <div style="flex: 1;"></div>
        <a href="logout.php" class="nav-item" style="color: #e74c3c; text-decoration: none;">Log Out</a>
    </div>

    <div class="main-content">
        <div id="team-view" class="view-content active-view">
            <div class="header"><h2>👥 Team Cluster Attendance</h2></div>
            <div class="container"><table id="attendanceTable"><thead><tr><th>Employee</th><th>Date</th><th>Status</th></tr></thead><tbody id="attendanceLogs"></tbody></table></div>
        </div>

        <div id="manage-requests" class="view-content">
            <div class="header"><h2>📋 Pending Endorsements</h2></div>
            <div class="container">
                <h3>Leaves</h3><table id="leaveTable"><thead><tr><th>Employee</th><th>Reason</th><th>Action</th></tr></thead><tbody id="coachLeaveList"></tbody></table>
                <h3 style="margin-top:30px;">Overtime</h3><table id="otTable"><thead><tr><th>Employee</th><th>Purpose</th><th>Action</th></tr></thead><tbody id="coachOTList"></tbody></table>
            </div>
        </div>

        <div id="my-filing" class="view-content">
            <div class="header" style="background: #FFC107; color: #000;"><h2>📝 My Personal Filing</h2></div>
            <div class="container">
                <div class="form-card">
                    <h4>📄 File My Leave</h4>
                    <form id="myLeaveForm" class="form-grid">
                        <input type="date" id="l_start"> <input type="date" id="l_end">
                        <textarea id="l_reason" style="grid-column: span 2;" placeholder="Reason..." rows="2"></textarea>
                        <button type="button" class="btn-submit" onclick="submitPersonal('leave')">Submit Leave</button>
                    </form>
                </div>
                <div class="form-card" style="border-left-color: #27ae60;">
                    <h4 style="color:#27ae60;">⏰ File My Overtime</h4>
                    <form id="myOTForm" class="form-grid">
                        <input type="datetime-local" id="ot_start"> <input type="datetime-local" id="ot_end">
                        <textarea id="ot_purpose" style="grid-column: span 2;" placeholder="Purpose..." rows="2"></textarea>
                        <button type="button" class="btn-submit" style="background:#27ae60;" onclick="submitPersonal('ot')">Submit Overtime</button>
                    </form>
                </div>
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
        }

        async function loadAttendance() {
            const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${COACH_ID}`);
            const data = await res.json();
            document.getElementById('attendanceLogs').innerHTML = data.map(log => `
                <tr><td><strong>${log.first_name} ${log.last_name}</strong></td><td>${log.attendance_date}</td><td>${log.attendance_status}</td></tr>
            `).join('');
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

        async function submitPersonal(type) {
            alert("Personal " + type.toUpperCase() + " Request Submitted");
            document.getElementById(type === 'leave' ? 'myLeaveForm' : 'myOTForm').reset();
        }

        window.onload = loadAttendance;
    </script>
</body>
</html>