<?php
// ==========================================
// CONFIGURATION
// ==========================================
// ⚠️ IMPORTANT: Change this to match your XAMPP folder name!
// If your folder is "HRIS_OFFICIAL", use: "http://localhost/HRIS_OFFICIAL/api"
$api_base_url = "http://localhost/hris_official/api"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS Master Workflow Tester (Debug Mode)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        
        /* ZONES */
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #ccc; }
        .zone-employee { border-color: #2196F3; } /* Blue */
        .zone-coach { border-color: #FFC107; }    /* Yellow */
        .zone-admin { border-color: #4CAF50; }    /* Green */

        h2 { margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        h3 { font-size: 14px; margin-top: 20px; color: #666; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }

        /* FORMS */
        label { display: block; font-size: 12px; font-weight: bold; margin-top: 10px; color: #444; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:focus, select:focus { border-color: #2196F3; outline: none; }
        
        button { width: 100%; padding: 10px; margin-top: 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; color: white; transition: 0.2s; }
        button:active { transform: scale(0.98); }
        
        .btn-blue { background: #2196F3; } .btn-blue:hover { background: #1976D2; }
        .btn-yellow { background: #FFC107; color: #333; } .btn-yellow:hover { background: #FFA000; }
        .btn-green { background: #4CAF50; } .btn-green:hover { background: #388E3C; }

        /* LIST ITEMS */
        .item-list { margin-top: 10px; max-height: 400px; overflow-y: auto; padding-right: 5px; }
        .item-card { background: #f9f9f9; border: 1px solid #e0e0e0; padding: 12px; margin-bottom: 8px; border-radius: 4px; position: relative; }
        .tag { font-size: 10px; padding: 3px 8px; border-radius: 10px; float: right; font-weight: bold; text-transform: uppercase; }
        .tag-leave { background: #E3F2FD; color: #1565C0; }
        .tag-ot { background: #FFF3E0; color: #E65100; }
        
        .btn-sm { padding: 6px; font-size: 11px; margin-top: 8px; width: auto; float: right; }
        
        .msg { font-size: 13px; margin-top: 10px; text-align: center; min-height: 20px; font-weight: bold; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 5px; border-radius: 4px; }
        .error { color: #c62828; background: #ffebee; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>

<h1>🚀 HRIS Master Workflow Tester (Debug Mode)</h1>

<div class="grid-container">

    <div class="card zone-employee">
        <h2>🧑‍💼 Employee Zone</h2>
        <label>Logged in Employee ID</label>
        <input type="number" id="emp_id" value="101">

        <h3>📄 File Leave</h3>
        <form id="leaveForm">
            <label>Leave Type</label>
            <select id="l_type"><option>Sick Leave</option><option>Vacation Leave</option></select>
            <div style="display:flex; gap:10px">
                <div style="flex:1"><label>Start</label><input type="date" id="l_start"></div>
                <div style="flex:1"><label>End</label><input type="date" id="l_end"></div>
            </div>
            <label>Reason</label>
            <input type="text" id="l_reason" placeholder="e.g. Flu">
            <label style="display:flex; align-items:center; gap:5px; font-weight:normal;">
                <input type="checkbox" id="l_agree1" checked style="width:auto; margin:0;"> I confirm accuracy
            </label>
            <label style="display:flex; align-items:center; gap:5px; font-weight:normal;">
                <input type="checkbox" id="l_agree2" checked style="width:auto; margin:0;"> I understand fraud policy
            </label>
            <button type="button" class="btn-blue" onclick="fileLeave()">Submit Leave</button>
        </form>
        <div id="leaveMsg" class="msg"></div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px dashed #ccc;">

        <h3>⏰ File Overtime</h3>
        <form id="otForm">
            <label>OT Type</label>
            <select id="o_type"><option>Regular Overtime</option><option>Duty on Rest Day</option></select>
            <label>Coach Name</label>
            <input type="text" id="o_coach" value="Charina Vargas">
            <label>Supervisor</label>
            <input type="text" id="o_sup" value="Mr. Supervisor">
            <label>Email</label>
            <input type="email" id="o_email" value="test@ireply.com">
            <div style="display:flex; gap:10px">
                <div style="flex:1"><label>Start (Time)</label><input type="datetime-local" id="o_start"></div>
                <div style="flex:1"><label>End (Time)</label><input type="datetime-local" id="o_end"></div>
            </div>
            <label>Purpose</label>
            <input type="text" id="o_purpose" placeholder="Urgent task">
            <label style="display:flex; align-items:center; gap:5px; font-weight:normal;">
                <input type="checkbox" id="o_agree1" checked style="width:auto; margin:0;"> I confirm accuracy
            </label>
            <label style="display:flex; align-items:center; gap:5px; font-weight:normal;">
                <input type="checkbox" id="o_agree2" checked style="width:auto; margin:0;"> I understand fraud policy
            </label>
            <button type="button" class="btn-blue" onclick="fileOT()">Submit Overtime</button>
        </form>
        <div id="otMsg" class="msg"></div>
    </div>

    <div class="card zone-coach">
        <h2>🧢 Coach Zone</h2>
        <label>Logged in Coach ID</label>
        <input type="number" id="coach_id" value="102">
        <p style="font-size:11px; color:#666">Role 2 (Team Leader)</p>

        <h3>📅 Pending Leaves</h3>
        <button class="btn-yellow" onclick="loadCoachLeaves()">🔄 Refresh Leaves</button>
        <div id="coachLeaveList" class="item-list"></div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px dashed #ccc;">

        <h3>⏰ Pending Overtime</h3>
        <button class="btn-yellow" onclick="loadCoachOT()">🔄 Refresh OT</button>
        <div id="coachOTList" class="item-list"></div>
    </div>

    <div class="card zone-admin">
        <h2>🛡️ Admin Zone</h2>
        <label>Logged in Admin ID</label>
        <input type="number" id="admin_id" value="103">
        <p style="font-size:11px; color:#666">Role 3 (Final Approval)</p>

        <h3>✅ Endorsed Leaves</h3>
        <button class="btn-green" onclick="loadAdminLeaves()">🔄 Load Endorsed Leaves</button>
        <div id="adminLeaveList" class="item-list"></div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px dashed #ccc;">

        <h3>✅ Endorsed Overtime</h3>
        <button class="btn-green" onclick="loadAdminOT()">🔄 Load Endorsed OT</button>
        <div id="adminOTList" class="item-list"></div>
    </div>

</div>

<script>
    // WE USE PHP TO SET THE API URL DYNAMICALLY
    const API = "<?php echo $api_base_url; ?>"; 

    console.log("🚀 Dashboard Loaded. API Base URL:", API);

    // ==========================================
    // 1. EMPLOYEE FUNCTIONS
    // ==========================================
    async function fileLeave() {
        const data = {
            employee_id: document.getElementById('emp_id').value,
            leave_type: document.getElementById('l_type').value,
            start_date: document.getElementById('l_start').value,
            end_date: document.getElementById('l_end').value,
            reason: document.getElementById('l_reason').value,
            agreement_1: document.getElementById('l_agree1').checked,
            agreement_2: document.getElementById('l_agree2').checked
        };
        console.log("📤 Sending Leave Data:", data);
        await postData(`${API}/users/file_leave.php`, data, 'leaveMsg', 1);
    }

    async function fileOT() {
        const data = {
            employee_id: document.getElementById('emp_id').value,
            ot_type: document.getElementById('o_type').value,
            coach_name: document.getElementById('o_coach').value,
            supervisor: document.getElementById('o_sup').value,
            email: document.getElementById('o_email').value,
            start_time: document.getElementById('o_start').value,
            end_time: document.getElementById('o_end').value,
            purpose: document.getElementById('o_purpose').value,
            agreement_1: document.getElementById('o_agree1').checked,
            agreement_2: document.getElementById('o_agree2').checked
        };
        console.log("📤 Sending OT Data:", data);
        await postData(`${API}/users/file_overtime.php`, data, 'otMsg', 1);
    }

    // ==========================================
    // 2. COACH FUNCTIONS
    // ==========================================
    async function loadCoachLeaves() {
        const uid = document.getElementById('coach_id').value;
        const url = `${API}/management/get_pending_leaves.php?user_id=${uid}`;
        console.log("🔄 Fetching Coach Leaves from:", url);
        
        const res = await fetchWithDebug(url, { headers: {'X-USER-ROLE': '2'} });
        if(res) renderList(res, 'coachLeaveList', 'leave', 'ENDORSE', 2);
    }

    async function loadCoachOT() {
        const uid = document.getElementById('coach_id').value;
        const url = `${API}/management/get_pending_ot.php?user_id=${uid}`;
        console.log("🔄 Fetching Coach OT from:", url);

        const res = await fetchWithDebug(url, { headers: {'X-USER-ROLE': '2'} });
        if(res) renderList(res, 'coachOTList', 'ot', 'ENDORSE', 2);
    }

    async function endorseItem(id, type) {
        if(!confirm(`Endorse this ${type} request?`)) return;
        const coachId = document.getElementById('coach_id').value;
        const endpoint = type === 'leave' ? '/management/endorse_leave.php' : '/management/endorse_overtime.php';
        const payload = type === 'leave' ? { leave_id: id, coach_id: coachId } : { ot_id: id, coach_id: coachId };
        
        console.log(`👍 Endorsing ${type}:`, payload);
        await postData(`${API}${endpoint}`, payload, null, 2);
        
        // Refresh list automatically
        type === 'leave' ? loadCoachLeaves() : loadCoachOT(); 
    }

    // ==========================================
    // 3. ADMIN FUNCTIONS
    // ==========================================
    async function loadAdminLeaves() {
        const url = `${API}/admin/get_endorsed_leaves.php`;
        console.log("🔄 Fetching Admin Leaves from:", url);
        const res = await fetchWithDebug(url, { headers: {'X-USER-ROLE': '3'} });
        if(res) renderList(res, 'adminLeaveList', 'leave', 'APPROVE', 3);
    }

    async function loadAdminOT() {
        const url = `${API}/admin/get_endorsed_ot.php`;
        console.log("🔄 Fetching Admin OT from:", url);
        const res = await fetchWithDebug(url, { headers: {'X-USER-ROLE': '3'} });
        if(res) renderList(res, 'adminOTList', 'ot', 'APPROVE', 3);
    }

    async function approveItem(id, type) {
        if(!confirm(`Approve this ${type} request?`)) return;
        const endpoint = type === 'leave' ? '/admin/final_approve_leave.php' : '/admin/final_approve_overtime.php';
        const payload = type === 'leave' ? { leave_id: id, action: 'APPROVE' } : { ot_id: id, action: 'APPROVE' };
        
        console.log(`✅ Approving ${type}:`, payload);
        await postData(`${API}${endpoint}`, payload, null, 3);
        
        // Refresh list
        type === 'leave' ? loadAdminLeaves() : loadAdminOT();
    }

    // ==========================================
    // DEBUG HELPERS
    // ==========================================
    
    // Helper to fetch GET requests with logging
    async function fetchWithDebug(url, options) {
        try {
            const res = await fetch(url, options);
            const text = await res.text(); // Get raw text first
            console.log(`📥 RAW RESPONSE (${url}):`, text);

            try {
                return JSON.parse(text); // Try to parse as JSON
            } catch (e) {
                console.error("❌ JSON Parse Failed. Server probably returned HTML error.");
                alert("Server Error: Check Console for 'RAW RESPONSE'");
                return null;
            }
        } catch (e) {
            console.error("❌ Network Error:", e);
            alert("Network Error: Check Console");
            return null;
        }
    }

    // Helper to POST data with logging
    async function postData(url, data, msgId, role) {
        console.log(`➡️ POSTing to: ${url}`);
        
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-USER-ROLE': role },
                body: JSON.stringify(data)
            });
            
            // 1. Get Status Code
            console.log(`⬅️ Status Code: ${res.status} ${res.statusText}`);

            // 2. Get Raw Text (Important for debugging PHP errors)
            const responseText = await res.text();
            console.log("📥 RAW RESPONSE BODY:", responseText);

            // 3. Try parsing JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error("❌ JSON PARSE ERROR. The server sent HTML instead of JSON.");
                console.error("This usually means a PHP Fatal Error or a 404 URL.");
                
                if(msgId) {
                    const el = document.getElementById(msgId);
                    el.innerText = "Error: Server returned invalid data (Check Console)";
                    el.className = "msg error";
                } else {
                    alert("Critical Error: Server returned non-JSON data. Check Console.");
                }
                return;
            }

            // 4. Handle Logic based on JSON result
            if(msgId) {
                const el = document.getElementById(msgId);
                el.innerText = result.error || result.success;
                el.className = "msg " + (result.error ? "error" : "success");
                console.log("📝 UI Message Updated:", result);
            } else {
                alert(result.error || result.success);
            }

        } catch (e) { 
            console.error("❌ FETCH FAILED:", e); 
            if(msgId) {
                const el = document.getElementById(msgId);
                el.innerText = "Network Error: See Console";
                el.className = "msg error";
            } else {
                alert("Network Error. Check Console."); 
            }
        }
    }

    function renderList(items, containerId, type, actionLabel, role) {
        const container = document.getElementById(containerId);
        container.innerHTML = "";
        
        if(!Array.isArray(items) || items.length === 0) { 
            container.innerHTML = "<div style='padding:10px; text-align:center; color:#999; font-style:italic;'>No items found</div>"; 
            return; 
        }

        items.forEach(item => {
            const id = type === 'leave' ? item.leave_id : item.ot_id;
            const title = type === 'leave' ? item.leave_type : item.ot_type;
            const date = type === 'leave' ? `${item.start_date} to ${item.end_date}` : `${item.start_time}`;
            const desc = type === 'leave' ? item.reason : item.purpose;
            
            const btnClass = role === 2 ? 'btn-yellow' : 'btn-green';
            const clickFunc = role === 2 ? `endorseItem(${id}, '${type}')` : `approveItem(${id}, '${type}')`;

            container.innerHTML += `
                <div class="item-card">
                    <span class="tag tag-${type}">${title}</span>
                    <strong style="display:block; margin-bottom:4px;">${item.first_name} ${item.last_name}</strong>
                    <div style="font-size:11px; color:#555;">📅 ${date}</div>
                    <div style="font-size:11px; color:#555; margin-top:2px;">📝 "${desc}"</div>
                    <button class="btn-sm ${btnClass}" onclick="${clickFunc}">${actionLabel}</button>
                    <div style="clear:both"></div>
                </div>
            `;
        });
    }
</script>

</body>
</html>