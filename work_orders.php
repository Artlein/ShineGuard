<?php
require_once 'dbconnect.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_work_order'])) {
    if (!canDo('create_work_orders')) {
        include __DIR__ . '/includes/access_denied_ui.php';
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
    if (!canDo('update_work_orders')) {
        include __DIR__ . '/includes/access_denied_ui.php';
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
                         LEFT JOIN streetlights s ON a.light_id = s.light_id 
                         WHERE a.status = 'Open' AND a.severity IN ('High', 'Medium') 
                         ORDER BY a.severity DESC, a.created_at ASC";
$pending_alerts = $conn->query($pending_alerts_query);

$active_work_orders = "SELECT ml.*, s.node_name, s.location, s.latitude, s.longitude, u.full_name as technician_name, a.description as alert_description 
                       FROM maintenance_logs ml 
                       INNER JOIN streetlights s ON ml.light_id = s.light_id 
                       INNER JOIN users u ON ml.user_id = u.user_id 
                       LEFT JOIN alerts a ON ml.alert_id = a.alert_id 
                       WHERE ml.status IN ('Scheduled', 'In Progress') 
                       ORDER BY ml.maintenance_date ASC";
$active_orders = $conn->query($active_work_orders);

$completed_orders = "SELECT ml.*, s.node_name, s.location, s.latitude, s.longitude, u.full_name as technician_name 
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

<link rel="stylesheet" href="assets/style.css">
<?php
if (!isset($theme_color)) {
    $theme_color = '#10b981';
    $tc_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($tc_result && $tc_row = $tc_result->fetch_assoc()) {
        $theme_color = $tc_row['config_value'];
    }
}
?>
<style>:root { --theme-color: <?php echo htmlspecialchars($theme_color); ?>; }</style>
<link rel="stylesheet" href="assets/css/work_orders.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    /* Force button colors to bypass any stylesheet caching/specificity issues */
    .btn-sm.update {
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }
    .btn-sm.info {
        background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        color: white !important;
        border-color: #2563eb !important;
    }
    .btn-sm.update:hover {
        background: linear-gradient(135deg, #4f46e5, #4338ca) !important;
    }
    .btn-sm.info:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
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
    <div style="background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(59,130,246,0.2)); border: 1px solid rgba(59,130,246,0.2); border-top: 5px solid #3b82f6; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">📋</div>
        <div style="font-size: 28px; font-weight: 800; color: #3b82f6; margin-bottom: 4px;"><?php echo $stats['scheduled']; ?></div>
        <div style="color: #3b82f6; font-size: 14px; font-weight: 600;">Scheduled Work Orders</div>
    </div>
    <div style="background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.2)); border: 1px solid rgba(245,158,11,0.2); border-top: 5px solid #f59e0b; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">🔧</div>
        <div style="font-size: 28px; font-weight: 800; color: #f59e0b; margin-bottom: 4px;"><?php echo $stats['in_progress']; ?></div>
        <div style="color: #f59e0b; font-size: 14px; font-weight: 600;">In Progress</div>
    </div>
    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.2)); border: 1px solid rgba(16,185,129,0.2); border-top: 5px solid #10b981; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">✓</div>
        <div style="font-size: 28px; font-weight: 800; color: #10b981; margin-bottom: 4px;"><?php echo $stats['completed_week']; ?></div>
        <div style="color: #10b981; font-size: 14px; font-weight: 600;">Completed This Week</div>
    </div>
    <div style="background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.2)); border: 1px solid rgba(239,68,68,0.2); border-top: 5px solid #ef4444; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="font-size: 36px; margin-bottom: 8px;">🚨</div>
        <div style="font-size: 28px; font-weight: 800; color: #ef4444; margin-bottom: 4px;"><?php echo $stats['critical_alerts']; ?></div>
        <div style="color: #ef4444; font-size: 14px; font-weight: 600;">Critical Alerts Pending</div>
    </div>
</div>

<div class="panel" style="border-top: 5px solid #ef4444;">
    <h2>🚨 Pending Alerts - Create Work Orders</h2>
    <p style="margin-bottom: 16px; color: var(--dim);">These alerts need maintenance work orders to be created.</p>
    
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
                <td><strong><?php echo $alert['node_name'] ?? 'System'; ?></strong></td>
                <td><?php echo $alert['location'] ?? 'N/A'; ?></td>
                <td style="max-width: 300px;"><?php echo substr($alert['description'], 0, 60); ?>...</td>
                <td><?php echo $alert['rul_estimate'] ?? 'N/A'; ?></td>
                <td><?php echo date('M d, H:i', strtotime($alert['created_at'])); ?></td>
                <td>
                    <?php if (canDo('create_work_orders')): ?>
                    <button onclick="showWorkOrderForm(<?php echo $alert['alert_id']; ?>, <?php echo $alert['light_id']; ?>, '<?php echo addslashes($alert['description']); ?>', '<?php echo $alert['node_name']; ?>')" class="btn primary" style="white-space: nowrap;">
                        Create Work Order
                    </button>
                    <?php
        else: ?>
                    <span style="font-size:0.8rem;color:#94a3b8;font-weight:600;">View only</span>
                    <?php
        endif; ?>
                </td>
            </tr>
            <?php
    endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php
else: ?>
    <div style="text-align: center; padding: 60px 20px; background: rgba(16,185,129,0.1); border-radius: 8px;">
        <div style="font-size: 48px; margin-bottom: 16px;">✓</div>
        <p style="color: #10b981; font-size: 18px; font-weight: 600; margin: 0;">No pending alerts! All critical issues have work orders.</p>
    </div>
    <?php
endif; ?>
</div>

<div class="panel" style="border-top: 5px solid #3b82f6;">
    <h2>🔧 Active Work Orders</h2>
    
    <?php if ($active_orders->num_rows > 0): ?>
    <div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>WO</th> 
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
                <td><strong>WO #<?php echo $order['log_id']; ?></strong></td>
                <td><strong><?php echo $order['node_name']; ?></strong></td>
                <td><?php echo $order['location']; ?></td>
                <td style="max-width: 250px;"><?php echo substr($order['action_taken'], 0, 50); ?>...</td>
                <td><?php echo $order['technician_name']; ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($order['maintenance_date'])); ?></td>
                <td><span class="badge <?php echo $order['status'] === 'In Progress' ? 'warning' : 'ok'; ?>"><?php echo $order['status']; ?></span></td>
                <td style="white-space: nowrap;">
                    <?php if (canDo('update_work_orders')): ?>
                    <button onclick="showUpdateForm(<?php echo $order['log_id']; ?>)" class="btn-sm update">Update</button>
                    <?php
        endif; ?>
                    <button onclick="viewDetails(<?php echo $order['log_id']; ?>, '<?php echo addslashes($order['action_taken']); ?>', '<?php echo addslashes($order['notes'] ?? ''); ?>', '<?php echo addslashes($order['node_name']); ?>', '<?php echo addslashes($order['location']); ?>', <?php echo floatval($order['latitude']); ?>, <?php echo floatval($order['longitude']); ?>)" class="btn-sm info">Details</button>
                </td>
            </tr>
            <?php
    endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php
else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
        <p style="color: #64748b; font-size: 16px; margin: 0;">No active work orders at this time.</p>
    </div>
    <?php
endif; ?>
</div>

<div class="panel" style="border-top: 5px solid #10b981;">
    <h2>✓ Recently Completed (Last 30 Days)</h2>
    
    <?php if ($completed->num_rows > 0): ?>
    <div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>WO</th> 
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
                <td><strong>WO #<?php echo $order['log_id']; ?></strong></td>
                <td><?php echo $order['node_name']; ?></td>
                <td style="max-width: 200px;"><?php echo substr($order['action_taken'], 0, 40); ?>...</td>
                <td><?php echo $order['technician_name']; ?></td>
                <td><?php echo date('M d, Y', strtotime($order['maintenance_date'])); ?></td>
                <td><?php echo $order['completion_time'] ?? 'N/A'; ?></td>
                <td><?php echo $order['parts_replaced'] ?? 'None'; ?></td>
            </tr>
            <?php
    endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php
else: ?>
    <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
        <p style="color: #64748b; font-size: 16px; margin: 0;">No completed work orders in the last 30 days.</p>
    </div>
    <?php
endif; ?>
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
            
            <div id="alertInfoBox" style="background: var(--muted); padding: 14px 18px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 24px; display: none;">
                <div style="font-size: 14px; color: var(--text); line-height: 1.6;">
                    <div style="margin-bottom: 6px;"><strong style="color: var(--blue);">Node:</strong> <span id="infoNode" style="font-weight: 600;"></span></div>
                    <div><strong style="color: var(--blue);">Issue:</strong> <span id="infoIssue" style="opacity: 0.9;"></span></div>
                </div>
            </div>
            
            <div class="form-group">
                <label style="color: var(--text);">Action to be Taken</label>
                <textarea id="action_taken" name="action_taken" required rows="4" placeholder="Describe the maintenance action to be performed..." style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; background: var(--input-bg); color: var(--text); resize: vertical;"></textarea>
            </div>
            
            <div class="form-group">
                <label style="color: var(--text);">Additional Notes</label>
                <textarea name="notes" rows="3" placeholder="Any additional notes or special instructions..." style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; background: var(--input-bg); color: var(--text); resize: vertical;"></textarea>
            </div>
            
            <div class="form-group">
                <label style="color: var(--text);">Scheduled Date & Time</label>
                <input type="datetime-local" name="scheduled_date" required value="<?php echo date('Y-m-d\TH:i'); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--input-bg); color: var(--text);">
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal('workOrderModal')" class="btn">Cancel</button>
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
            
            <div class="form-group">
                <label style="color: var(--text);">Status*</label>
                <select name="status" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--input-bg); color: var(--text);">
                    <option value="Scheduled">Scheduled</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label style="color: var(--text);">Parts Replaced</label>
                <input type="text" name="parts_replaced" placeholder="e.g., LED lamp, relay, sensor" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--input-bg); color: var(--text);">
            </div>
            
            <div class="form-group">
                <label style="color: var(--text);">Completion Time (minutes)</label>
                <input type="number" name="completion_time" placeholder="e.g., 45" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--input-bg); color: var(--text);">
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal('updateModal')" class="btn">Cancel</button>
                <button type="submit" class="btn primary">Update Work Order</button>
            </div>
        </form>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content" style="max-width: 780px;">
        <span class="close" onclick="closeModal('detailsModal')">&times;</span>
        <h2 style="margin-top: 0;">📋 Work Order Details</h2>
        <div id="detailsContent" style="line-height: 1.8;"></div>
        <div id="wo-map-wrapper" style="margin-top: 20px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); display: none;">
            <div style="background: var(--muted); padding: 10px 14px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px;">
                <span>📍</span> Node Location Map
            </div>
            <div id="wo-map" style="height: 220px; width: 100%;"></div>
        </div>
        <div style="margin-top: 16px; text-align: right;">
            <button onclick="closeModal('detailsModal')" class="btn">Close</button>
        </div>
    </div>
</div>

<script>
function showWorkOrderForm(alertId, lightId, description, nodeName) {
    document.getElementById('alert_id').value = alertId;
    document.getElementById('light_id').value = lightId;
    
    // Auto-fill suggested action based on alert description
    const actionField = document.getElementById('action_taken');
    let suggestedAction = "Conduct general inspection and verify hardware status.";
    
    if (description) {
        const desc = description.toLowerCase();
        if (desc.includes("temperature") || desc.includes("heat")) {
            suggestedAction = "Inspect cooling system, check for circuit overheating, and verify thermal sensors.";
        } else if (desc.includes("voltage") || desc.includes("power")) {
            suggestedAction = "Check power supply unit, terminal wiring connections, and voltage regulator stability.";
        } else if (desc.includes("current") || desc.includes("amp")) {
            suggestedAction = "Check for short circuits, measure load efficiency, and inspect for damaged components.";
        } else if (desc.includes("offline") || desc.includes("communication") || desc.includes("network")) {
            suggestedAction = "Verify network connectivity, reset communication module, and check antenna signal strength.";
        } else if (desc.includes("dimming") || desc.includes("brightness")) {
            suggestedAction = "Calibrate dimming controller, verify lux sensor accuracy, and check LED driver.";
        } else if (desc.includes("failure") || desc.includes("dead")) {
            suggestedAction = "Replace bulb/LED unit and inspect the ballast/driver for faults.";
        }
    }
    
    if (actionField) actionField.value = suggestedAction;
    
    const infoNode = document.getElementById('infoNode');
    const infoIssue = document.getElementById('infoIssue');
    const infoBox = document.getElementById('alertInfoBox');
    
    if (infoNode) infoNode.textContent = nodeName;
    if (infoIssue) infoIssue.textContent = description;
    if (infoBox) infoBox.style.display = 'block';
    
    document.getElementById('workOrderModal').classList.add('open');
}

function showUpdateForm(logId) {
    document.getElementById('update_log_id').value = logId;
    document.getElementById('updateModal').classList.add('open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

let woMap = null;

function viewDetails(logId, action, notes, nodeName, location, lat, lng) {
    const detailsContent = document.getElementById('detailsContent');
    detailsContent.innerHTML = `
        <div style="background: var(--muted); padding: 14px 16px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 18px; display: flex; gap: 24px; flex-wrap: wrap;">
            <div><div style="font-size: 11px; font-weight: 700; color: var(--dim); text-transform: uppercase; letter-spacing: 0.5px;">Node</div><div style="font-weight: 800; color: var(--text); font-size: 16px;">${nodeName || '—'}</div></div>
            <div style="flex:1;"><div style="font-size: 11px; font-weight: 700; color: var(--dim); text-transform: uppercase; letter-spacing: 0.5px;">Location</div><div style="font-weight: 600; color: var(--text); font-size: 14px; opacity: 0.9;">${location || '—'}</div></div>
        </div>
        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--dim); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Action Taken</label>
            <div style="color: var(--text); padding: 14px 16px; background: var(--muted); border: 1px solid var(--border); border-radius: 10px; font-size: 14px; line-height: 1.7; opacity: 0.95;">${action}</div>
        </div>
        ${notes ? `
        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--dim); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Additional Notes</label>
            <div style="color: var(--text); padding: 14px 16px; background: var(--muted); border: 1px solid var(--border); border-radius: 10px; font-size: 14px; line-height: 1.7; opacity: 0.95;">${notes}</div>
        </div>` : ''}
    `;

    const mapWrapper = document.getElementById('wo-map-wrapper');

    if (lat && lng && lat !== 0 && lng !== 0) {
        mapWrapper.style.display = 'block';
        
        // Ensure the modal 'open' class is added first
        document.getElementById('detailsModal').classList.add('open');
        
        // Small delay to ensure modal is visible and dimensions are stable before initializing map
        setTimeout(() => {
            if (typeof L === 'undefined') {
                const mapDiv = document.getElementById('wo-map');
                if (mapDiv) mapDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: #ef4444;">Leaflet library not loaded. Check your connection.</div>';
                return;
            }

            try {
                if (woMap) {
                    try { woMap.remove(); } catch(err) { console.warn("Error removing old map:", err); }
                    woMap = null;
                }
                
                // Ensure the container is empty before initializing
                const mapDiv = document.getElementById('wo-map');
                if (mapDiv) {
                    mapDiv.innerHTML = '';
                    // Leaflet adds a class to initialized containers, remove it just in case
                    mapDiv.className = mapDiv.className.replace(/\bleaflet-container\b/g, '');
                } else {
                    // If the div was somehow lost, reconstruct the wrapper
                    mapWrapper.innerHTML = `
                        <div style="background: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569;">📍 Node Location Map</div>
                        <div id="wo-map" style="height: 220px; width: 100%;"></div>
                    `;
                }
                
                woMap = L.map('wo-map').setView([lat, lng], 17);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(woMap);
                
                const icon = L.divIcon({
                    className: '',
                    html: `<div style="background:#ef4444;width:28px;height:28px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 28]
                });
                
                L.marker([lat, lng], { icon })
                    .addTo(woMap)
                    .bindPopup(`<b>${nodeName}</b><br>${location}`, { maxWidth: 200 })
                    .openPopup();
                
                // Force a redraw after map is fully initialized
                setTimeout(() => {
                    if (woMap) woMap.invalidateSize();
                }, 100);
                
            } catch (e) {
                console.error("Map initialization failed:", e);
                const mapDiv = document.getElementById('wo-map');
                if (mapDiv) mapDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: #ef4444;">Failed to initialize map. Please try again.</div>';
            }
        }, 400); // Slightly longer delay for mobile/slower devices
    } else {
        mapWrapper.style.display = 'none';
        document.getElementById('detailsModal').classList.add('open');
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('open');
    }
}
</script>
</body>
</html>