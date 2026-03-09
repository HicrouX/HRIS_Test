<?php
// FILE: super_admin_dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header("Content-Type: text/html; charset=UTF-8");
require_once 'api/config/db.php'; 
require_once 'api/middleware/auth.php';

// STRICT ACCESS CONTROL: Only Super Admin (Role 4)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php"); exit;
}

$user_id = $_SESSION['employee_id'];
$api_base_url = "http://localhost/hris_official/api"; 

// --- 1. DELETE ATTENDANCE RECORD (Transactional) ---
if (isset($_GET['delete_id'])) {
    try {
        $pdo->beginTransaction();
        
        // Step 1: Delete associated time logs first to prevent foreign key errors
        $stmt = $pdo->prepare("DELETE FROM time_logs WHERE attendance_id = ?");
        $stmt->execute([$_GET['delete_id']]);
        
        // Step 2: Delete the attendance record
        $stmt = $pdo->prepare("DELETE FROM attendance_logs WHERE attendance_id = ?");
        $stmt->execute([$_GET['delete_id']]);
        
        $pdo->commit();
        header("Location: super_admin_dashboard.php?msg=Deleted"); exit;
    } catch (Exception $e) { 
        $pdo->rollBack(); 
    }
}

// --- 2. FULL OVERRIDE (Edit Time & Status) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_full_edit'])) {
    $id = $_POST['attendance_id'];
    $status = $_POST['status'];
    // Handle empty strings from time inputs
    $t_in = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
    $t_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
    
    try {
        $pdo->beginTransaction();

        // Step 1: Update Status in 'attendance' table
        $stmt = $pdo->prepare("UPDATE attendance_logs SET attendance_status = ? WHERE attendance_id = ?");
        $stmt->execute([$status, $id]);

        // Step 2: Update or Insert into 'time_logs' table
        $check = $pdo->prepare("SELECT time_log_id FROM time_logs WHERE attendance_id = ?");
        $check->execute([$id]);
        
        if ($check->rowCount() > 0) {
            // Update existing log
            $stmt = $pdo->prepare("UPDATE time_logs SET time_in = ?, time_out = ? WHERE attendance_id = ?");
            $stmt->execute([$t_in, $t_out, $id]);
        } else if ($t_in || $t_out) {
            // Create a new log if one didn't exist (e.g., changing Absent to Present)
            $infoStmt = $pdo->prepare("SELECT employee_id, attendance_date FROM attendance_logs WHERE attendance_id = ?");
            $infoStmt->execute([$id]);
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($info) {
                $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, attendance_id, time_in, time_out, log_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$info['employee_id'], $id, $t_in, $t_out, $info['attendance_date']]);
            }
        }

        $pdo->commit();
        header("Location: super_admin_dashboard.php?msg=Updated"); exit;
    } catch (Exception $e) { 
        $pdo->rollBack(); 
        $error = $e->getMessage(); 
    }
}

// --- 3. EDIT RECORD LOADER (Fixed Join) ---
$edit_mode = false; $edit_record = null;
if (isset($_GET['edit_id'])) {
    // Left Join time_logs to ensure we get data even if the log is missing
    $sql = "SELECT a.*, e.first_name, e.last_name, t.time_in, t.time_out 
            FROM attendance_logs a 
            JOIN employees e ON a.employee_id = e.employee_id 
            LEFT JOIN time_logs t ON a.attendance_id = t.attendance_id 
            WHERE a.attendance_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['edit_id']]);
    $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_record) $edit_mode = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>iREPLY - Super Admin Pro</title>
    <style>
        :root { --primary: #1e4d8c; --danger: #e74c3c; --success: #27ae60; --warning: #f1c40f; --accent: #3498db; --bg-main: #121212; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--bg-main); overflow: hidden; }
        
        .sidebar { width: 260px; background: #fff; padding: 25px; display: flex; flex-direction: column; border-right: 1px solid #ddd; }
        .logo { color: var(--primary); font-weight: 800; font-size: 26px; margin-bottom: 20px; text-align: center; }
        
        .nav-item { padding: 12px 18px; margin: 3px 0; border-radius: 10px; cursor: pointer; color: #555; font-size: 13.5px; text-decoration: none; display: flex; align-items: center; transition: 0.2s; }
        .nav-item:hover { background: #f0f2f5; color: var(--accent); }
        .nav-active { background: var(--warning); color: #000 !important; font-weight: bold; }
        
        /* ✅ SCROLLING FIX FOR TABS APPLIED HERE */
        .main-content { flex: 1; min-height: 0; background: #fff; margin: 15px; border-radius: 15px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .view-content { display: none; flex-direction: column; flex: 1; min-height: 0; overflow-y: auto; }
        .active-view { display: flex; }
        
        .header { background: var(--primary); color: white; padding: 20px 35px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 25px 35px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f4f6f8; padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; color: #666; border-bottom: 2px solid #eee; cursor: pointer; position: relative; }
        th:hover { background: #ebedef; }
        th::after { content: ' ⬍'; font-size: 10px; color: #ccc; position: absolute; right: 5px; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 12.5px; color: #333; }
        tr:hover { background: #fafafa; }

        .pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .status-Present { background: #e8f5e9; color: #2e7d32; }
        .status-Endorsed { background: #e3f2fd; color: #3498db; }
        .status-Pending { background: #fff3e0; color: #e67e22; }
        .status-Absent { background: #ffebee; color: #c62828; }
        .status-Late { background: #fff3e0; color: #e65100; }
        .status-Overtime { background: #e0f2f1; color: #00695c; }
        .status-OnLeave { background: #e1f5fe; color: #0277bd; }

        /* --- COACH HIGHLIGHT (Role 2) - Dark Blue --- */
        .coach-pill {
            background-color: #002D62; /* Dark Blue */
            color: white;              /* White Text */
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .coach-pill:hover { opacity: 0.9; }

        .btn { padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: 11px; text-decoration: none; transition: 0.2s; }
        .btn-edit { background: var(--accent); color: white; }
        .btn-delete { background: var(--danger); color: white; margin-left: 5px; }
        .btn-export { background: var(--success); color: white; }
        .btn-filter { background: #2c3e50; color: white; margin-right: 5px; }
        .btn-refresh { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); padding: 5px 12px; border-radius: 15px; }
        .btn-refresh:hover { background: rgba(255,255,255,0.4); }
        
        .search-input { padding: 8px 15px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; width: 140px; font-size: 12px; }
        .search-input option { color: #333; }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-card { background: white; padding: 30px; border-radius: 12px; width: 450px; box-shadow: 0 15px 40px rgba(0,0,0,0.4); }
        .modal-wide { width: 1000px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; }
        .close-x { float: right; font-size: 24px; cursor: pointer; color: #999; line-height: 1; margin-top: -10px; margin-right: -10px;}
        .close-x:hover { color: #333; }

        /* ✅ SCROLLING FIX FOR MODALS APPLIED HERE */
        .modal-body { flex: 1; overflow-y: auto; min-height: 0; }

        .form-card { background: #fafbfc; padding: 25px; border-radius: 8px; border-left: 5px solid var(--accent); margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        textarea { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 13px; grid-column: span 2; }
        .readonly-field { background: #eee; cursor: not-allowed; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .submit-btn { padding: 10px; width: 100%; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; background: var(--accent); }

        /* --- MODAL FORM STYLES (Horizontal) --- */
        .modal-row { display: flex; align-items: center; margin-bottom: 12px; }
        .modal-row label { width: 90px; font-size: 13px; font-weight: 600; color: #444; }
        .modal-input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; font-family: inherit; }
        .modal-actions { display: flex; gap: 10px; margin-top: 25px; }
        .btn-save-modal { background: #3498db; color: white; flex: 2; padding: 10px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-save-modal:hover { background: #2980b9; }
        .btn-cancel-modal { background: #f0f2f5; color: #333; flex: 1; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold; font-size: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; cursor: pointer; }
        .btn-cancel-modal:hover { background: #e4e6eb; }

        /* --- FILTER GRID STYLES --- */
        .filter-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; }
        .filter-option { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #333; cursor: pointer; }
        .filter-option input { cursor: pointer; }

        /* --- DATE INPUTS FOR HISTORY MODAL --- */
        .date-filter-input {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            color: #333;
            outline: none;
            background: white;
        }
        
        /* --- HISTORY FILTER DROPDOWN --- */
        .history-filter-container { position: relative; display: inline-block; }
        .history-filter-dropdown {
            display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ddd; padding: 10px; border-radius: 5px; 
            z-index: 10000; width: 170px; box-shadow: 0 8px 16px rgba(0,0,0,0.15); text-align: left; margin-top: 5px;
        }
        .history-filter-dropdown label { display: block; margin-bottom: 5px; font-size: 12px; cursor: pointer; padding: 4px; }
        .history-filter-dropdown label:hover { background-color: #f5f5f5; }

        /* --- ACTIVE FILTER TAGS --- */
        .filter-tag {
            background: #e3f2fd; color: #0d47a1; padding: 4px 8px; border-radius: 12px; font-size: 11px; display: flex; align-items: center; gap: 5px; border: 1px solid #90caf9; margin-top: 2px;
        }
        .filter-tag span { cursor: pointer; font-weight: bold; color: #c62828; margin-left: 2px; }
        .filter-tag span:hover { color: #b71c1c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">iREPLY SYSTEM</div>
        <nav>
            <a href="#" class="nav-item nav-active" onclick="switchView('master-attendance-view', this)">📊 Master Attendance</a>
            <a href="#" class="nav-item" onclick="switchView('management-view', this)">👥 Management Hierarchy</a>
            <a href="#" class="nav-item" onclick="switchView('approvals', this)">✅ Final Sign-offs</a>
            <a href="#" class="nav-item" onclick="switchView('disputes-view', this)">⚠️ Resolution Center</a>
            <a href="#" class="nav-item" onclick="switchView('history-view', this)">📜 Global Request Logs</a>
            
            <a href="logout.php" class="nav-item" style="color:var(--danger); margin-top:40px;">🚪 System Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div id="master-attendance-view" class="view-content active-view">
            <div class="header">
                <h2>📊 Master Attendance</h2>
                <div style="display:flex; gap:10px; align-items:center;">
                    <select id="master_role" class="search-input">
                        <option value="">All Roles</option>
                        <option value="2">Admin</option>
                        <option value="3">Coach</option>
                        <option value="4">Employee</option>
                    </select>
                    <input type="date" id="master_start" class="search-input">
                    <input type="date" id="master_end" class="search-input">
                    <button class="btn btn-edit" onclick="loadMasterAttendance()">View</button>
                    <input type="text" id="masterSearch" class="search-input" placeholder="🔍 Search name..." onkeyup="filterTable('masterTable', this.value)">
                </div>
            </div>
            <div class="container">
                <table id="masterTable">
                    <thead><tr><th onclick="sortTable('masterTable',0)">Employee ⬍</th><th onclick="sortTable('masterTable',1)">Role ⬍</th><th onclick="sortTable('masterTable',2)">Date ⬍</th><th>In</th><th>Out</th><th>Hrs</th><th>Status</th></tr></thead>
                    <tbody id="masterBody"></tbody>
                </table>
            </div>
        </div>

        <div id="management-view" class="view-content">
            <div class="header">
                <div>
                    <button id="backBtn" onclick="resetToLeaders()" style="display:none; cursor:pointer; background:rgba(255,255,255,0.2); border:none; padding:5px 10px; color:white; border-radius:15px; font-size:11px;">⬅ Back to Coaches</button>
                    <h2 id="hierarchyTitle">Management Hierarchy</h2>
                </div>
            </div>
            <div class="container">
                <table id="hierarchyTable">
                    <thead><tr><th onclick="sortTable('hierarchyTable',0)">Personnel ⬍</th><th onclick="sortTable('hierarchyTable',1)">Last Active ⬍</th><th onclick="sortTable('hierarchyTable',2)">Status ⬍</th><th>Logs</th></tr></thead>
                    <tbody id="hierarchyBody"></tbody>
                </table>
            </div>
        </div>

        <div id="approvals" class="view-content">
            <div class="header"><h2>✅ Final Sign-offs</h2></div>
            <div class="container">
                <h3>Leaves</h3>
                <table id="leaveTable"><thead><tr><th onclick="sortTable('leaveTable',0)">Employee ⬍</th><th onclick="sortTable('leaveTable',1)">Type ⬍</th><th onclick="sortTable('leaveTable',2)">Dates ⬍</th><th onclick="sortTable('leaveTable',3)">Reason ⬍</th><th onclick="sortTable('leaveTable',4)">Coach ⬍</th><th>Review</th></tr></thead><tbody id="leaveQueue"></tbody></table>
                <h3 style="margin-top:40px;">Overtime</h3>
                <table id="otTable"><thead><tr><th onclick="sortTable('otTable',0)">Employee ⬍</th><th onclick="sortTable('otTable',1)">Type ⬍</th><th onclick="sortTable('otTable',2)">Time ⬍</th><th onclick="sortTable('otTable',3)">Purpose ⬍</th><th onclick="sortTable('otTable',4)">Coach ⬍</th><th>Review</th></tr></thead><tbody id="otQueue"></tbody></table>
            </div>
        </div>

        <div id="disputes-view" class="view-content">
            <div class="header"><h2>⚠️ Resolution Center (Disputes)</h2></div>
            <div class="container">
                <table id="disputeTable">
                    <thead><tr><th onclick="sortTable('disputeTable',0)">Employee ⬍</th><th onclick="sortTable('disputeTable',1)">Date ⬍</th><th onclick="sortTable('disputeTable',2)">Type ⬍</th><th onclick="sortTable('disputeTable',3)">Reason ⬍</th><th onclick="sortTable('disputeTable',4)">Remarks ⬍</th><th onclick="sortTable('disputeTable',5)">Status ⬍</th><th>Review</th></tr></thead>
                    <tbody id="disputeQueue"></tbody>
                </table>
            </div>
        </div>

        <div id="history-view" class="view-content">
            <div class="header">
                <h2>📜 Global Request Logs</h2>
                <div style="display:flex; gap:10px;">
                    <select id="hist_category" class="search-input">
                        <option value="ALL">All Categories</option>
                        <option value="Leave">Leave</option>
                        <option value="Overtime">Overtime</option>
                        <option value="Dispute">Dispute</option>
                    </select>
                    <input type="date" id="hist_start" class="search-input">
                    <input type="date" id="hist_end" class="search-input">
                    <button class="btn btn-edit" onclick="loadRequestHistory()">Filter</button>
                    <button class="btn btn-export" onclick="exportRequestHistory()">📂 Export Logs</button>
                </div>
            </div>
            <div class="container"><table id="globalHistoryTable"><thead><tr><th onclick="sortTable('globalHistoryTable',0)">Employee ⬍</th><th onclick="sortTable('globalHistoryTable',1)">Category ⬍</th><th onclick="sortTable('globalHistoryTable',2)">Type ⬍</th><th onclick="sortTable('globalHistoryTable',3)">Status ⬍</th><th onclick="sortTable('globalHistoryTable',4)">Filed On ⬍</th></tr></thead><tbody id="historyBody"></tbody></table></div>
        </div>
    </div>

    <div id="filterModal" class="overlay" style="display:none;">
        <div class="modal-card" style="width: 350px;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:10px;">
                <h3 style="margin:0;">Filter by Status</h3>
                <span class="close-x" onclick="closeFilterModal()">×</span>
            </div>
            
            <div class="filter-grid">
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Present"> Present</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Absent"> Absent</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Late"> Late</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Tardy"> Tardy</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="On Leave"> On Leave</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Overtime"> Overtime</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Undertime"> Undertime</label>
                <label class="filter-option"><input type="checkbox" class="status-filter" value="Duty on Rest Day"> Duty on Rest Day</label>
            </div>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <div style="display:flex; gap:10px;">
                <button class="btn-save-modal" onclick="applyFilters()">Apply Filters</button>
                <button class="btn-cancel-modal" onclick="clearFilters()">Clear All</button>
            </div>
        </div>
    </div>

    <div id="historyModal" class="overlay" style="display:none;">
        <div class="modal-card modal-wide">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:15px;">
                <h3 id="modalTitle" style="margin:0; font-size:20px;">Employee logs</h3>
                
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="date" id="hist_mod_start" class="date-filter-input">
                    <span style="font-size:13px; color:#555;">to</span>
                    <input type="date" id="hist_mod_end" class="date-filter-input">
                    <button class="submit-btn" style="width: auto; padding: 5px 15px;" onclick="fetchMemberHistory()">Filter</button>
                    
                    <div class="history-filter-container" style="margin-left: 10px;">
                        <button onclick="toggleHistoryFilterMenu()" style="background:#2c3e50; color:white; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; font-size:12px; cursor:pointer;">Filter Status ⇩</button>
                        <div id="historyFilterMenu" class="history-filter-dropdown">
                            <label><input type="checkbox" class="hist-status-cb" value="Present" onchange="applyHistoryFilters()"> Present</label>
                            <label><input type="checkbox" class="hist-status-cb" value="Absent" onchange="applyHistoryFilters()"> Absent</label>
                            <label><input type="checkbox" class="hist-status-cb" value="Late" onchange="applyHistoryFilters()"> Late</label>
                            <label><input type="checkbox" class="hist-status-cb" value="Tardy" onchange="applyHistoryFilters()"> Tardy</label>
                            <label><input type="checkbox" class="hist-status-cb" value="On Leave" onchange="applyHistoryFilters()"> On Leave</label>
                            <label><input type="checkbox" class="hist-status-cb" value="Overtime" onchange="applyHistoryFilters()"> Overtime</label>
                            <label><input type="checkbox" class="hist-status-cb" value="Undertime" onchange="applyHistoryFilters()"> Undertime</label>
                            <label><input type="checkbox" class="hist-status-cb" value="Duty on Rest Day" onchange="applyHistoryFilters()"> Duty on Rest Day</label>
                        </div>
                    </div>
                    
                    <div id="activeHistoryFilters" style="display:flex; gap:5px; align-items:center; flex-wrap:wrap;"></div>

                    <button onclick="exportIndividualExcel()" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; font-size:12px; cursor:pointer; margin-left:10px;">📂 Individual Export</button>
                    <span class="close-x" onclick="closeModal()" style="margin-left:15px; font-size:24px;">×</span>
                </div>
            </div>
            
            <div class="modal-body">
                <table id="modalHistTable" class="history-table">
                    <thead>
                        <tr style="background:#f9fafb; text-transform:uppercase; font-size:11px; color:#777;">
                            <th onclick="sortTable('modalHistTable',0)">Date ⬍</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Break In</th>
                            <th>Break Out</th>
                            <th>Lunch</th>
                            <th>Status</th>
                            <th onclick="sortTable('modalHistTable',7)">Work Hours ⬍</th>
                        </tr>
                    </thead>
                    <tbody id="modalHistoryBody"></tbody>
                </table>
            </div>
            <div style="padding-top:15px; border-top:1px solid #eee; text-align:right; font-size:14px;">
                <strong>Total Period Hours: <span id="totalHrs">0.00</span></strong>
            </div>
        </div>
    </div>

    <?php if ($edit_mode && $edit_record): ?>
    <div class="overlay">
        <div class="modal-card" style="width: 400px; padding: 25px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <h3 style="margin:0 0 20px 0; font-size:18px;">System Override</h3>
                <span class="close-x" onclick="window.location.href='super_admin_dashboard.php'">×</span>
            </div>
            
            <p style="font-size:13px; margin-bottom: 20px;">
                Personnel: <strong><?php echo htmlspecialchars($edit_record['first_name'] . ' ' . $edit_record['last_name']); ?></strong>
            </p>

            <form method="POST">
                <input type="hidden" name="attendance_id" value="<?php echo $edit_record['attendance_id']; ?>">
                <?php 
                    $val_in = isset($edit_record['time_in']) ? date('H:i', strtotime($edit_record['time_in'])) : '';
                    $val_out = isset($edit_record['time_out']) ? date('H:i', strtotime($edit_record['time_out'])) : '';
                ?>
                
                <div class="modal-row">
                    <label>Time In</label>
                    <input type="time" name="time_in" value="<?php echo $val_in; ?>" class="modal-input">
                </div>
                
                <div class="modal-row">
                    <label>Time Out</label>
                    <input type="time" name="time_out" value="<?php echo $val_out; ?>" class="modal-input">
                </div>
                
                <div class="modal-row">
                    <label>Status</label>
                    <select name="status" class="modal-input">
                        <option value="Present" <?php echo $edit_record['attendance_status'] == 'Present' ? 'selected' : ''; ?>>Present</option>
                        <option value="Late" <?php echo $edit_record['attendance_status'] == 'Late' ? 'selected' : ''; ?>>Late</option>
                        <option value="Absent" <?php echo $edit_record['attendance_status'] == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="On Leave" <?php echo $edit_record['attendance_status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                        <option value="Overtime" <?php echo $edit_record['attendance_status'] == 'Overtime' ? 'selected' : ''; ?>>Overtime</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" name="save_full_edit" class="btn-save-modal">💾 Update Database</button>
                    <a href="super_admin_dashboard.php" class="btn-cancel-modal">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div id="disputeModal" class="overlay" style="display:none;">
        <div class="modal-card">
            <span class="close-x" onclick="closeModal()">×</span>
            <h3>Resolve Dispute</h3>
            <div id="disputeInfo" style="font-size:13px; margin-bottom:15px; background:#f0f4f8; padding:10px; border-radius:6px;"></div>
            
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Action:</label>
            <select id="disputeAction" style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;" onchange="toggleDisputeFields()">
                <option value="APPROVE">Approve (Modify Attendance)</option>
                <option value="DENY">Deny (No Changes)</option>
            </select>
            
            <div id="approvalFields">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Correction:</label>
                <select id="resStatus" style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                    <option value="Present">Present</option>
                    <option value="Late">Late</option>
                    <option value="Absent">Absent</option>
                    <option value="Overtime">Overtime</option>
                    <option value="On Leave">On Leave</option>
                </select>
                <div style="display:flex; gap:10px; margin-bottom:10px;">
                    <div style="flex:1;">In: <input type="time" id="resIn" style="width:100%; padding:8px; box-sizing:border-box;"></div>
                    <div style="flex:1;">Out: <input type="time" id="resOut" style="width:100%; padding:8px; box-sizing:border-box;"></div>
                </div>
            </div>

            <textarea id="resRemarks" placeholder="Internal remarks..." style="width:100%; padding:10px; box-sizing:border-box;"></textarea>
            <input type="hidden" id="resId">
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button onclick="confirmDispute()" class="btn btn-edit" style="flex:2;">Confirm & Settle</button>
                <button onclick="closeModal()" class="btn" style="flex:1; background:#eee; color:#333;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        const API = "<?php echo $api_base_url; ?>";
        const MY_ID = "<?php echo $user_id; ?>";
        let viewingMemberId = null;
        let globalMasterData = []; 
        let activeStatusFilters = [];

        async function loadMasterAttendance() {
            const start = document.getElementById('master_start').value;
            const end = document.getElementById('master_end').value;
            const role = document.getElementById('master_role').value;
            const res = await fetch(`${API}/admin/get_master_attendance.php?start_date=${start}&end_date=${end}&role=${role}`);
            globalMasterData = await res.json();
            renderMasterTable();
        }

        function renderMasterTable() {
            const tbody = document.getElementById('masterBody');
            if (!tbody) return;
            tbody.innerHTML = globalMasterData
                .filter(d => activeStatusFilters.length === 0 || activeStatusFilters.includes(d.attendance_status))
                .map(d => `
                    <tr>
                        <td><strong>${d.first_name} ${d.last_name}</strong></td>
                        <td>${d.role_name}</td>
                        <td>${d.attendance_date || "-"}</td>
                        <td>${d.time_in ? d.time_in.substring(11,16) : "--:--"}</td>
                        <td>${d.time_out ? d.time_out.substring(11,16) : "--:--" }</td>
                        <td>${d.total_hours || "0.00"}</td>
                        <td><span class="pill status-${(d.attendance_status || "Absent").replace(/\s/g,"")}">${d.attendance_status || "Absent"}</span></td>
                    </tr>
                `).join("");
        }

        function filterTable(tid, val) {
            let filter = val.toLowerCase();
            let rows = document.getElementById(tid).tBodies[0].rows;
            Array.from(rows).forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(filter) ? "" : "none";
            });
        }

        // ✅ FIXED UNIVERSAL SORTING FUNCTION (Handles Text, Dates, and Numbers perfectly)
        function sortTable(tid, n) {
            let table = document.getElementById(tid), tbody = table.tBodies[0], rows = Array.from(tbody.rows);
            let asc = table.getAttribute('data-asc') === 'true';
            
            rows.sort((a, b) => {
                let v1 = a.cells[n].innerText.trim();
                let v2 = b.cells[n].innerText.trim();

                // Number parsing
                let num1 = parseFloat(v1.replace(/[^0-9.-]+/g,""));
                let num2 = parseFloat(v2.replace(/[^0-9.-]+/g,""));
                
                // Date parsing
                let date1 = Date.parse(v1);
                let date2 = Date.parse(v2);

                if (!isNaN(date1) && !isNaN(date2) && v1.includes('-')) {
                    // Sort as Dates
                    return asc ? date1 - date2 : date2 - date1;
                } else if (!isNaN(num1) && !isNaN(num2) && !isNaN(v1.charAt(0))) {
                    // Sort as Numbers
                    return asc ? num1 - num2 : num2 - num1;
                } else {
                    // Sort as String (Fallback)
                    return asc ? v1.localeCompare(v2) : v2.localeCompare(v1);
                }
            });
            
            rows.forEach(r => tbody.appendChild(r));
            table.setAttribute('data-asc', !asc);
        }

        // ⚠️ Master Control Switch View Logic Removed
        function switchView(viewId, btn) {
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('nav-active'));
            document.getElementById(viewId).classList.add('active-view');
            btn.classList.add('nav-active');
            if(viewId === 'disputes-view') loadDisputes();
            if(viewId === 'approvals') loadApprovals();
            if(viewId === 'management-view') loadHierarchy();
            if(viewId === 'history-view') loadRequestHistory();
        }

        // --- FILTER MODAL LOGIC (MASTER DASHBOARD) ---
        function openFilterModal() { document.getElementById('filterModal').style.display = 'flex'; }
        function closeFilterModal() { document.getElementById('filterModal').style.display = 'none'; }
        
        function applyFilters() {
            const checkboxes = document.querySelectorAll('.status-filter:checked');
            activeStatusFilters = Array.from(checkboxes).map(cb => cb.value);
            closeFilterModal();
            renderMasterTable();
        }

        function clearFilters() {
            document.querySelectorAll('.status-filter').forEach(cb => cb.checked = false);
            activeStatusFilters = [];
            closeFilterModal();
            renderMasterTable();
        }

        // --- HISTORY MODAL FILTER LOGIC ---
        function toggleHistoryFilterMenu() {
            const menu = document.getElementById('historyFilterMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        function applyHistoryFilters() {
            const checkedBoxes = document.querySelectorAll('.hist-status-cb:checked');
            const selectedStatuses = Array.from(checkedBoxes).map(cb => cb.value.trim());
            const tagsContainer = document.getElementById('activeHistoryFilters');
            tagsContainer.innerHTML = ''; 
            
            selectedStatuses.forEach(status => {
                tagsContainer.innerHTML += `<div class="filter-tag">${status} <span onclick="removeHistoryFilter('${status}')">✖</span></div>`;
            });

            // FILTER TABLE ROWS
            const tableBody = document.getElementById('modalHistoryBody');
            const rows = tableBody.getElementsByTagName('tr');
            let totalHours = 0;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const statusCell = row.cells[6]; 
                if (!statusCell) continue;
                const statusText = statusCell.textContent.trim();
                
                if (selectedStatuses.length === 0 || selectedStatuses.includes(statusText)) {
                    row.style.display = '';
                    totalHours += parseFloat(row.cells[7].textContent) || 0;
                } else {
                    row.style.display = 'none';
                }
            }
            document.getElementById('totalHrs').innerText = totalHours.toFixed(2);
        }

        function removeHistoryFilter(statusToRemove) {
            const checkbox = Array.from(document.querySelectorAll('.hist-status-cb')).find(cb => cb.value === statusToRemove);
            if (checkbox) { checkbox.checked = false; applyHistoryFilters(); }
        }
        
        window.onclick = function(event) {
            if (!event.target.closest('.history-filter-container')) {
                const menu = document.getElementById('historyFilterMenu');
                if (menu && menu.style.display === 'block') menu.style.display = 'none';
            }
        }

        async function loadDisputes() { 
            const res = await fetch(`${API}/management/get_pending_disputes.php?coach_id=${MY_ID}`); 
            const data = await res.json(); 
            document.getElementById('disputeQueue').innerHTML = data.map(d => `
                <tr>
                    <td><strong>${d.first_name} ${d.last_name}</strong></td>
                    <td>${d.dispute_date}</td>
                    <td>${d.dispute_type || 'N/A'}</td>
                    <td>"${d.reason}"</td>
                    <td><i style="color:gray;">${d.remarks || 'No remarks'}</i></td>
                    <td><span class="pill status-${d.status}">${d.status}</span></td>
                    <td><button class="btn btn-edit" onclick="openDispute(${d.dispute_id}, '${d.first_name}', '${d.dispute_date}')">🔍 Review</button></td>
                </tr>`).join(''); 
        }

        // --- OPEN DISPUTE MODAL ---
        function openDispute(id, name, date) { 
            document.getElementById('resId').value = id; 
            document.getElementById('disputeInfo').innerText = `Resolving for: ${name} (${date})`;
            document.getElementById('disputeAction').value = "APPROVE";
            toggleDisputeFields();
            document.getElementById('disputeModal').style.display = 'flex'; 
        }

        function toggleDisputeFields() {
            const action = document.getElementById('disputeAction').value;
            document.getElementById('approvalFields').style.display = (action === 'APPROVE') ? 'block' : 'none';
        }

        // --- CONFIRM DISPUTE (APPROVE/DENY) ---
        async function confirmDispute() { 
            const id = document.getElementById('resId').value;
            const action = document.getElementById('disputeAction').value;
            const remarks = document.getElementById('resRemarks').value;
            
            let payload = { dispute_id: id, action: action, remarks: remarks };
            
            if (action === 'APPROVE') {
                payload.new_status = document.getElementById('resStatus').value;
                payload.time_in = document.getElementById('resIn').value;
                payload.time_out = document.getElementById('resOut').value;
            }

            await fetch(`${API}/management/resolve_dispute.php`, { method:'POST', body:JSON.stringify(payload) }); 
            closeModal(); 
            loadDisputes(); 
        }

        async function loadApprovals() { 
            const [l, o] = await Promise.all([fetch(`${API}/admin/get_endorsed_leaves.php`), fetch(`${API}/admin/get_endorsed_ot.php`)]); 
            const leaves = await l.json(), ots = await o.json(); 
            document.getElementById('leaveQueue').innerHTML = leaves.map(i => `<tr><td><strong>${i.first_name} ${i.last_name}</strong></td><td><span class="status-pill status-Endorsed">${i.leave_type || i.ot_type || 'Unknown'}</span></td><td>${i.start_date||i.start_time}</td><td>"${i.reason||i.purpose}"</td><td style="font-weight:bold; color:#e67e22;">${i.endorser_name||'-'}</td><td><span class="action-icon" style="color:green;" onclick="finalApprove(${i.leave_id||i.ot_id}, 'leave', 'APPROVE')">✔</span> <span class="action-icon" style="color:red;" onclick="finalApprove(${i.leave_id||i.ot_id}, 'leave', 'DENY')">❌</span></td></tr>`).join('') || `<tr><td colspan='6' style='text-align:center; color:#999;'>No pending items</td></tr>`; 
            document.getElementById('otQueue').innerHTML = ots.map(i => `<tr><td><strong>${i.first_name} ${i.last_name}</strong></td><td><span class="status-pill status-Endorsed">${i.leave_type || i.ot_type || 'Unknown'}</span></td><td>${i.start_date||i.start_time}</td><td>"${i.reason||i.purpose}"</td><td style="font-weight:bold; color:#e67e22;">${i.endorser_name||'-'}</td><td><span class="action-icon" style="color:green;" onclick="finalApprove(${i.leave_id||i.ot_id}, 'ot', 'APPROVE')">✔</span> <span class="action-icon" style="color:red;" onclick="finalApprove(${i.leave_id||i.ot_id}, 'ot', 'DENY')">❌</span></td></tr>`).join('') || `<tr><td colspan='6' style='text-align:center; color:#999;'>No pending items</td></tr>`; 
        }

        async function loadHierarchy() { 
            const res = await fetch(`${API}/admin/get_all_attendance.php`); 
            const data = await res.json(); 
            document.getElementById("hierarchyBody").innerHTML = data.map(log => { 
                let name = `<strong>${log.first_name} ${log.last_name}</strong>`;
                if (log.role_id == 3) { 
                     name = `<span class="coach-pill" onclick="viewTeam(${log.employee_id}, '${log.first_name}')">👤 ${log.first_name} (Coach)</span>`;
                }
                return `<tr><td>${name}</td><td>${log.latest_date||'-'}</td><td><span class="pill status-${(log.latest_status||'').replace(/\s/g,'')}">${log.latest_status||'Inactive'}</span></td><td><button class="btn btn-edit" onclick="openHistory(${log.employee_id}, '${log.first_name}')">👁️ logs</button></td></tr>`; 
            }).join(''); 
        }

        // --- UPDATED EXPORT FUNCTIONS ---
        function exportHierarchy() {
            window.location.href = `${API}/export/export_excel.php?mode=ROSTER`;
        }

        function exportRequestHistory() {
            const start = document.getElementById('hist_start').value;
            const end = document.getElementById('hist_end').value;
            const cat = document.getElementById('hist_category').value;
            window.location.href = `${API}/export/export_excel.php?mode=HISTORY&start_date=${start}&end_date=${end}&category=${cat}`;
        }
        
        function exportIndividualExcel() {
            if(!viewingMemberId) return;
            const start = document.getElementById('hist_mod_start').value;
            const end = document.getElementById('hist_mod_end').value;
            window.location.href = `${API}/export/export_excel.php?mode=INDIVIDUAL&employee_id=${viewingMemberId}&start_date=${start}&end_date=${end}`;
        }

        async function loadRequestHistory() { 
            const start = document.getElementById('hist_start').value;
            const end = document.getElementById('hist_end').value;
            const cat = document.getElementById('hist_category').value;
            
            const res = await fetch(`${API}/admin/get_request_history.php?start_date=${start}&end_date=${end}&category=${cat}`); 
            const data = await res.json(); 
            document.getElementById('historyBody').innerHTML = data.map(i => `<tr><td>${i.employee_name}</td><td><span class="pill" style="background:#eee; color:#333;">${i.category}</span></td><td>${i.type}</td><td><span class=\"pill status-${i.status}\">${i.status}</span></td><td>${i.created_at}</td></tr>`).join(''); 
        }

        async function finalApprove(id, t, a) { await fetch(`${API}/admin/final_approve_${t==='leave'?'leave':'overtime'}.php`, { method: 'POST', body: JSON.stringify({ [t+'_id']: id, action: a }) }); loadApprovals(); }

        async function openHistory(empId, name) { 
            viewingMemberId = empId; 
            document.getElementById('modalTitle').innerText = `${name} - Logs`; 
            const d = new Date(), s = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0]; 
            document.getElementById('hist_mod_start').value = s; 
            document.getElementById('hist_mod_end').value = e; 
            document.querySelectorAll('.hist-status-cb').forEach(cb => cb.checked = false);
            document.getElementById('historyFilterMenu').style.display = 'none';
            document.getElementById('activeHistoryFilters').innerHTML = ''; 
            fetchMemberHistory(); 
            document.getElementById('historyModal').style.display = 'flex'; 
        }
        
        async function fetchMemberHistory() { const res = await fetch(`${API}/users/get_my_attendance.php?employee_id=${viewingMemberId}&start_date=${document.getElementById('hist_mod_start').value}&end_date=${document.getElementById('hist_mod_end').value}`); const data = await res.json(); let total = 0; document.getElementById('modalHistoryBody').innerHTML = data.map(r => { total += parseFloat(r.total_hours || 0); return `<tr><td>${r.date}</td><td>${r.time_in||'--:--'}</td><td>${r.time_out||'--:--'}</td><td>${r.break_in||'--:--'}</td><td>${r.break_out||'--:--'}</td><td>${r.lunch_break||'0'}</td><td><span class="pill status-${(r.status||'').replace(/\s/g,'')}">${r.status}</span></td><td>${r.total_hours}</td></tr>`; }).join(''); document.getElementById('totalHrs').innerText = total.toFixed(2); applyHistoryFilters(); }
        
        function closeModal() { document.querySelectorAll('.overlay').forEach(m => m.style.display = 'none'); }

        async function viewTeam(coachId, coachName) { document.getElementById('backBtn').style.display = 'inline-block'; document.getElementById('hierarchyTitle').innerText = `Team: ${coachName}`; const res = await fetch(`${API}/management/get_team_attendance.php?coach_id=${coachId}`); const data = await res.json(); document.getElementById("hierarchyBody").innerHTML = data.map(log => `<tr><td>${log.first_name} ${log.last_name}</td><td>${log.latest_date||'-'}</td><td><span class="pill status-${(log.latest_status||'').replace(/\s/g,'')}">${log.latest_status||'Inactive'}</span></td><td><button class="btn btn-edit" onclick="openHistory(${log.employee_id}, '${log.first_name}')">👁️ logs</button></td></tr>`).join(''); }
        function resetToLeaders() { document.getElementById('backBtn').style.display = 'none'; document.getElementById('hierarchyTitle').innerText = "Management Hierarchy"; loadHierarchy(); }

        window.onload = function() {
            const d = new Date(), s = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0], e = d.toISOString().split('T')[0];
            document.getElementById('hist_start').value = s; document.getElementById('hist_end').value = e;
            document.getElementById('master_start').value = s; 
            document.getElementById('master_end').value = e;
            loadMasterAttendance(); // Set Master Attendance as default view
        };
    </script>
</body>
</html>