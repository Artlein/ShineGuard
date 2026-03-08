<?php
require_once 'dbconnect.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_work_order'])) {
    if ($_SESSION['role'] === 'System Observer') {
        header('Location: work_orders.php?error=unauthorized');
        exit();
    }
    $alert_id = intval($_POST['alert_id']);
    $light_id = intval($_POST['light_id']);
    $action_taken = $conn->real_escape_string($_POST['action_taken']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $scheduled_date = $_POST['scheduled_date'];

    $stmt = $conn->prepare("INSERT INTO maintenance_logs (light_id, alert_id, user_id, action_taken, notes, maintenance_date, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')");
    $stmt->bind_param("iiisss", $light_id, $alert_id, $_SESSION['user_id'], $action_taken, $notes, $scheduled_date);
    $stmt->execute();

    $conn->query("UPDATE alerts SET status = 'Acknowledged', acknowledged_at = NOW(), acknowledged_by = {$_SESSION['user_id']} 
                  WHERE alert_id = $alert_id");
    
    logActivity($conn, $_SESSION['user_id'], 'Work Order Created', "Work order created for alert #$alert_id");
    header('Location: work_orders.php?success=created');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if ($_SESSION['role'] === 'System Observer') {
        header('Location: work_orders.php?error=unauthorized');
        exit();
    }
    $log_id = intval($_POST['log_id']);
    $status = $_POST['status'];
    $parts = isset($_POST['parts_replaced']) ? $conn->real_escape_string($_POST['parts_replaced']) : null;
    $completion_time = isset($_POST['completion_time']) ? intval($_POST['completion_time']) : null;
    
    $stmt = $conn->prepare("UPDATE maintenance_logs SET status = ?, parts_replaced = ?, completion_time = ? 
                           WHERE log_id = ?");
    $stmt->bind_param("ssii", $status, $parts, $completion_time, $log_id);
    $stmt->execute();

    if ($status === 'Completed') {
        $conn->query("UPDATE alerts SET status = 'Resolved', resolved_at = NOW() 
                      WHERE alert_id = (SELECT alert_id FROM maintenance_logs WHERE log_id = $log_id)");

        $conn->query("UPDATE streetlights SET status = 'Active' 
                      WHERE light_id = (SELECT light_id FROM maintenance_logs WHERE log_id = $log_id)");
    }
    
    logActivity($conn, $_SESSION['user_id'], 'Work Order Updated', "Work order #$log_id status changed to $status");
    header('Location: work_orders.php?success=updated');
    exit();
}

$pending_alerts_query = "SELECT a.*, s.node_name, s.location, s.status as light_status 
                         FROM alerts a 
                         INNER JOIN streetlights s ON a.light_id = s.light_id 
                         WHERE a.status = 'Open' AND a.severity IN ('High', 'Medium') 
                         ORDER BY a.severity DESC, a.created_at ASC";
$pending_alerts = $conn->query($pending_alerts_query);

$active_work_orders = "SELECT ml.*, s.node_name, s.location, u.full_name as technician_name, a.description as alert_description 
                       FROM maintenance_logs ml 
                       INNER JOIN streetlights s ON ml.light_id = s.light_id 
                       INNER JOIN users u ON ml.user_id = u.user_id 
                       LEFT JOIN alerts a ON ml.alert_id = a.alert_id 
                       WHERE ml.status IN ('Scheduled', 'In Progress') 
                       ORDER BY ml.maintenance_date ASC";
$active_orders = $conn->query($active_work_orders);

$completed_orders = "SELECT ml.*, s.node_name, s.location, u.full_name as technician_name 
                     FROM maintenance_logs ml 
                     INNER JOIN streetlights s ON ml.light_id = s.light_id 
                     INNER JOIN users u ON ml.user_id = u.user_id 
                     WHERE ml.status = 'Completed' AND ml.maintenance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                     ORDER BY ml.maintenance_date DESC";
$completed = $conn->query($completed_orders);

$stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM maintenance_logs WHERE status = 'Scheduled') as scheduled,
    (SELECT COUNT(*) FROM maintenance_logs WHERE status = 'In Progress') as in_progress,
    (SELECT COUNT(*) FROM maintenance_logs WHERE status = 'Completed' AND maintenance_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as completed_week,
    (SELECT COUNT(*) FROM alerts WHERE status = 'Open' AND severity = 'High') as critical_alerts")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>Work Orders - Shine Guard Hulo</title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">

<style>
<?php include 'assets/style.css'; ?>

:root { 
    --theme-color: <?php echo $theme_color; ?>; 
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
}

:root {
  --bg:           #f0f4f8;
  --surface:      #ffffff;
  --surface-2:    #f7f9fc;
  --border:       #e4e9f0;
  --border-light: #edf1f7;
  --text-primary: #1a2035;
  --text-secondary: #6b7a99;
  --text-muted:   #a0aec0;
  --accent:       #e53e3e;
  --green:        #22c55e;
  --green-dim:    #f0fdf4;
  --green-border: #bbf7d0;
  --red:          #ef4444;
  --red-dim:      #fef2f2;
  --red-border:   #fecaca;
  --blue:         #3b82f6;
  --blue-dim:     #eff6ff;
  --radius:       16px;
  --radius-sm:    10px;
  --shadow:       0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.05);
  --shadow-md:    0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
}

.main-content {
  padding: 2.2rem 2.6rem;
  font-family: 'Inter', sans-serif;
}

.page-header {
  text-align: center;
  margin-bottom: 2.4rem;
  padding-bottom: 0;
}

.page-header h1 {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--text-primary);
  margin-bottom: 0.3rem;
  text-transform: uppercase;
}

.page-header p {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 400;
}

.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 1.8rem 2rem;
  margin-bottom: 1.6rem;
  position: relative;
  overflow: hidden;
}

.panel.panel-create { border-top: 3px solid #3b82f6; }
.panel.panel-list   { border-top: 3px solid #22c55e; }

.panel h2 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem 1.6rem;
  margin-bottom: 1.4rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-secondary);
  letter-spacing: 0.02em;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="time"] {
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-family: 'Inter', sans-serif;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.6rem 0.9rem;
  transition: border-color .15s, box-shadow .15s;
  outline: none;
  width: 100%;
}

.form-group input:focus {
  border-color: var(--blue);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}

.form-group input::placeholder {
  color: var(--text-muted);
  font-weight: 400;
}

.form-days-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 0.6rem;
}

.days-grid {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.day-chip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 0.38rem 0.8rem;
  cursor: pointer;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--text-secondary);
  transition: all .15s;
  user-select: none;
}

.day-chip:has(input:checked) {
  background: var(--blue-dim);
  border-color: var(--blue);
  color: var(--blue);
}

.day-chip input[type="checkbox"] {
  accent-color: var(--blue);
  width: 13px; height: 13px;
  cursor: pointer;
}

.btn-primary {
  background: var(--text-primary);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  padding: 0.65rem 1.6rem;
  cursor: pointer;
  transition: background .15s, transform .1s, box-shadow .15s;
  box-shadow: var(--shadow);
}

.btn-primary:hover {
  background: #2d3748;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

thead tr {
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
}

thead th {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-secondary);
  padding: 0.85rem 1.1rem;
  text-align: left;
  white-space: nowrap;
}

tbody tr {
  border-bottom: 1px solid var(--border-light);
  transition: background .1s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }

tbody td {
  padding: 0.9rem 1.1rem;
  color: var(--text-primary);
  font-weight: 500;
  vertical-align: middle;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  padding: 0.28rem 0.7rem;
  border-radius: 30px;
  white-space: nowrap;
}

.badge.ok {
  background: var(--green-dim);
  color: var(--green);
  border: 1px solid var(--green-border);
}

.badge.ok::before  { content: '●'; font-size: 0.5rem; }

.badge.fail {
  background: var(--red-dim);
  color: var(--red);
  border: 1px solid var(--red-border);
}

.badge.fail::before { content: '●'; font-size: 0.5rem; }

.actions-cell { display: flex; gap: 0.5rem; }

.btn-sm {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 7px;
  color: var(--text-secondary);
  font-family: 'Inter', sans-serif;
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.32rem 0.72rem;
  cursor: pointer;
  transition: all .13s;
  white-space: nowrap;
}

.btn-sm:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
  color: var(--text-primary);
}

.btn-sm.danger:hover {
  background: var(--red-dim);
  border-color: var(--red-border);
  color: var(--red);
}
</style>
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>
<main class="main-content">
    
<div class="page-header">
    <br>
    <br>
  <center> <h1>🔧 Work Orders & Maintenance</h1>
    <p>Manage maintenance work orders and track completion</p>
</div></center> 

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">📋</div>
        <div style="font-size: 28px; font-weight: 800; color: #1e40af; margin-bottom: 4px;"><?php echo $stats['scheduled']; ?></div>
        <div style="color: #1e40af; font-size: 14px; font-weight: 600;">Scheduled Work Orders</div>
    </div>
    <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">🔧</div>
        <div style="font-size: 28px; font-weight: 800; color: #92400e; margin-bottom: 4px;"><?php echo $stats['in_progress']; ?></div>
        <div style="color: #92400e; font-size: 14px; font-weight: 600;">In Progress</div>
    </div>
    <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">✓</div>
        <div style="font-size: 28px; font-weight: 800; color: #065f46; margin-bottom: 4px;"><?php echo $stats['completed_week']; ?></div>
        <div style="color: #065f46; font-size: 14px; font-weight: 600;">Completed This Week</div>
    </div>
    <div style="background: linear-gradient(135deg, #fee2e2, #fecaca); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">🚨</div>
        <div style="font-size: 28px; font-weight: 800; color: #991b1b; margin-bottom: 4px;"><?php echo $stats['critical_alerts']; ?></div>
        <div style="color: #991b1b; font-size: 14px; font-weight: 600;">Critical Alerts Pending</div>
    </div>
</div>

<div class="panel">
    <h2>🚨 Pending Alerts - Create Work Orders</h2>
    <p style="margin-bottom: 16px; color: #64748b;">These alerts need maintenance work orders to be created.</p>
    
    <?php if ($pending_alerts->num_rows > 0): ?>
    <div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>Severity</th>
                <th>Node</th>
                <th>Location</th>
                <th>Issue</th>
                <th>RUL</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($alert = $pending_alerts->fetch_assoc()): ?>
            <tr>
                <td><span class="badge <?php echo $alert['severity'] === 'High' ? 'fail' : 'warning'; ?>"><?php echo $alert['severity']; ?></span></td>
                <td><strong><?php echo $alert['node_name']; ?></strong></td>
                <td><?php echo $alert['location']; ?></td>
                <td style="max-width: 300px;"><?php echo substr($alert['description'], 0, 60); ?>...</td>
                <td><?php echo $alert['rul_estimate'] ?? 'N/A'; ?></td>
                <td><?php echo date('M d, H:i', strtotime($alert['created_at'])); ?></td>
                <td>
                    <button onclick="showWorkOrderForm(<?php echo $alert['alert_id']; ?>, <?php echo $alert['light_id']; ?>, '<?php echo addslashes($alert['description']); ?>', '<?php echo $alert['node_name']; ?>')" class="btn primary" style="white-space: nowrap;">
                        Create Work Order
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f0fdf4; border-radius: 8px;">
        <div style="font-size: 48px; margin-bottom: 16px;">✓</div>
        <p style="color: #10b981; font-size: 18px; font-weight: 600; margin: 0;">No pending alerts! All critical issues have work orders.</p>
    </div>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>🔧 Active Work Orders</h2>
    
    <?php if ($active_orders->num_rows > 0): ?>
    <div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>WO #</th>
                <th>Node</th>
                <th>Location</th>
                <th>Action</th>
                <th>Technician</th>
                <th>Scheduled Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $active_orders->fetch_assoc()): ?>
            <tr>
                <td><strong style="color: #2563eb;">#<?php echo str_pad($order['log_id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                <td><strong><?php echo $order['node_name']; ?></strong></td>
                <td><?php echo $order['location']; ?></td>
                <td style="max-width: 250px;"><?php echo substr($order['action_taken'], 0, 50); ?>...</td>
                <td><?php echo $order['technician_name']; ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($order['maintenance_date'])); ?></td>
                <td><span class="badge <?php echo $order['status'] === 'In Progress' ? 'warning' : 'ok'; ?>"><?php echo $order['status']; ?></span></td>
                <td style="white-space: nowrap;">
                    <button onclick="showUpdateForm(<?php echo $order['log_id']; ?>)" class="btn" style="margin-right: 4px; background: #10b981; color: white; border-color: #10b981;">Update</button>
                    <button onclick="viewDetails(<?php echo $order['log_id']; ?>, '<?php echo addslashes($order['action_taken']); ?>', '<?php echo addslashes($order['notes'] ?? ''); ?>')" class="btn" style="background: #ef4444; color: white; border-color: #ef4444;">Details</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
        <p style="color: #64748b; font-size: 16px; margin: 0;">No active work orders at this time.</p>
    </div>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>✓ Recently Completed (Last 30 Days)</h2>
    
    <?php if ($completed->num_rows > 0): ?>
    <div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>WO #</th>
                <th>Node</th>
                <th>Action</th>
                <th>Technician</th>
                <th>Completed</th>
                <th>Time (min)</th>
                <th>Parts</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $completed->fetch_assoc()): ?>
            <tr>
                <td><strong style="color: #059669;">#<?php echo str_pad($order['log_id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                <td><?php echo $order['node_name']; ?></td>
                <td style="max-width: 200px;"><?php echo substr($order['action_taken'], 0, 40); ?>...</td>
                <td><?php echo $order['technician_name']; ?></td>
                <td><?php echo date('M d, Y', strtotime($order['maintenance_date'])); ?></td>
                <td><?php echo $order['completion_time'] ?? 'N/A'; ?></td>
                <td><?php echo $order['parts_replaced'] ?? 'None'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
        <p style="color: #64748b; font-size: 16px; margin: 0;">No completed work orders in the last 30 days.</p>
    </div>
    <?php endif; ?>
</div>

</main>
</div>

<div id="workOrderModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeModal('workOrderModal')">&times;</span>
        <h2 style="margin-top: 0;">📝 Create Work Order</h2>
        <form method="POST">
            <input type="hidden" name="create_work_order" value="1">
            <input type="hidden" name="alert_id" id="alert_id">
            <input type="hidden" name="light_id" id="light_id">
            
            <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bfdbfe;">
                <div style="margin-bottom: 8px;"><strong style="color: #1e40af;">Node:</strong> <span id="modal_node" style="color: #2563eb;"></span></div>
                <div><strong style="color: #1e40af;">Issue:</strong> <span id="modal_issue" style="color: #64748b;"></span></div>
            </div>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">Action to be Taken*</label>
            <textarea name="action_taken" required rows="4" placeholder="Describe the maintenance action to be performed..." style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; margin-bottom: 16px; resize: vertical;"></textarea>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">Additional Notes</label>
            <textarea name="notes" rows="3" placeholder="Any additional notes or special instructions..." style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; margin-bottom: 16px; resize: vertical;"></textarea>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">Scheduled Date & Time*</label>
            <input type="datetime-local" name="scheduled_date" required value="<?php echo date('Y-m-d\TH:i'); ?>" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 20px;">
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeModal('workOrderModal')" class="btn" style="background: #e5e7eb; color: #1f2937;">Cancel</button>
                <button type="submit" class="btn primary">Create Work Order</button>
            </div>
        </form>
    </div>
</div>

<div id="updateModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeModal('updateModal')">&times;</span>
        <h2 style="margin-top: 0;">🔄 Update Work Order Status</h2>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="log_id" id="update_log_id">
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">Status*</label>
            <select name="status" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 16px;">
                <option value="Scheduled">Scheduled</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">Parts Replaced</label>
            <input type="text" name="parts_replaced" placeholder="e.g., LED lamp, relay, sensor" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 16px;">
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">Completion Time (minutes)</label>
            <input type="number" name="completion_time" placeholder="e.g., 45" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 20px;">
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeModal('updateModal')" class="btn" style="background: #e5e7eb; color: #1f2937;">Cancel</button>
                <button type="submit" class="btn primary">Update Work Order</button>
            </div>
        </form>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close" onclick="closeModal('detailsModal')">&times;</span>
        <h2 style="margin-top: 0;">📋 Work Order Details</h2>
        <div id="detailsContent" style="line-height: 1.8;">
            
        </div>
        <div style="margin-top: 24px; text-align: right;">
            <button onclick="closeModal('detailsModal')" class="btn">Close</button>
        </div>
    </div>
</div>

<script>
function showWorkOrderForm(alertId, lightId, description, nodeName) {
    document.getElementById('alert_id').value = alertId;
    document.getElementById('light_id').value = lightId;
    document.getElementById('modal_node').textContent = nodeName;
    document.getElementById('modal_issue').textContent = description;
    document.getElementById('workOrderModal').classList.add('open');
}

function showUpdateForm(logId) {
    document.getElementById('update_log_id').value = logId;
    document.getElementById('updateModal').classList.add('open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

function viewDetails(logId, action, notes) {
    const detailsContent = document.getElementById('detailsContent');
    detailsContent.innerHTML = `
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 16px;">
            <div style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 12px;">Work Order #${String(logId).padStart(4, '0')}</div>
        </div>
        <div style="margin-bottom: 16px;">
            <strong style="color: #1f2937;">Action Taken:</strong>
            <div style="color: #64748b; margin-top: 8px; padding: 12px; background: #f9fafb; border-radius: 6px;">${action}</div>
        </div>
        ${notes ? `<div style="margin-bottom: 16px;">
            <strong style="color: #1f2937;">Additional Notes:</strong>
            <div style="color: #64748b; margin-top: 8px; padding: 12px; background: #f9fafb; border-radius: 6px;">${notes}</div>
        </div>` : ''}
    `;
    document.getElementById('detailsModal').classList.add('open');
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('open');
    }
}
</script>
</body>
</html>