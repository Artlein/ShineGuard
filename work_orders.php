<?php
require_once 'dbconnect.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_work_order'])) {
    if (!canDo('create_work_orders')) {
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
    if (!canDo('update_work_orders')) {
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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLwg=" crossorigin=""></script>
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
                    <?php if (canDo('create_work_orders')): ?>
                    <button onclick="showWorkOrderForm(<?php echo $alert['alert_id']; ?>, <?php echo $alert['light_id']; ?>, '<?php echo addslashes($alert['description']); ?>', '<?php echo $alert['node_name']; ?>')" class="btn primary" style="white-space: nowrap;">
                        Create Work Order
                    </button>
                    <?php else: ?>
                    <span style="font-size:0.8rem;color:#94a3b8;font-weight:600;">View only</span>
                    <?php endif; ?>
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
                <th>WO 
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
                <td><strong style="color: #2563eb;">
                <td><strong><?php echo $order['node_name']; ?></strong></td>
                <td><?php echo $order['location']; ?></td>
                <td style="max-width: 250px;"><?php echo substr($order['action_taken'], 0, 50); ?>...</td>
                <td><?php echo $order['technician_name']; ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($order['maintenance_date'])); ?></td>
                <td><span class="badge <?php echo $order['status'] === 'In Progress' ? 'warning' : 'ok'; ?>"><?php echo $order['status']; ?></span></td>
                <td style="white-space: nowrap;">
                    <?php if (canDo('update_work_orders')): ?>
                    <button onclick="showUpdateForm(<?php echo $order['log_id']; ?>)" class="btn" style="margin-right: 4px; background: #10b981; color: white; border-color: #10b981;">Update</button>
                    <?php endif; ?>
                    <button onclick="viewDetails(<?php echo $order['log_id']; ?>, '<?php echo addslashes($order['action_taken']); ?>', '<?php echo addslashes($order['notes'] ?? ''); ?>', '<?php echo $order['node_name']; ?>', '<?php echo addslashes($order['location']); ?>', <?php echo floatval($order['latitude']); ?>, <?php echo floatval($order['longitude']); ?>)" class="btn" style="background: #ef4444; color: white; border-color: #ef4444;">Details</button>
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
                <th>WO 
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
                <td><strong style="color: #059669;">
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
    <div class="modal-content" style="max-width: 780px;">
        <span class="close" onclick="closeModal('detailsModal')">&times;</span>
        <h2 style="margin-top: 0;">📋 Work Order Details</h2>
        <div id="detailsContent" style="line-height: 1.8;"></div>
        <div id="wo-map-wrapper" style="margin-top: 16px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; display: none;">
            <div style="background: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569;">📍 Node Location Map</div>
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

let woMap = null;

function viewDetails(logId, action, notes, nodeName, location, lat, lng) {
    const detailsContent = document.getElementById('detailsContent');
    detailsContent.innerHTML = `
        <div style="background: #f8fafc; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 14px; display: flex; gap: 24px; flex-wrap: wrap;">
            <div><div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Node</div><div style="font-weight: 700; color: #0f172a; font-size: 15px;">${nodeName || '—'}</div></div>
            <div style="flex:1;"><div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Location</div><div style="font-weight: 600; color: #334155; font-size: 14px;">${location || '—'}</div></div>
        </div>
        <div style="margin-bottom: 14px;">
            <strong style="color: #1f2937; font-size: 13px;">Action Taken:</strong>
            <div style="color: #64748b; margin-top: 8px; padding: 12px; background: #f9fafb; border-radius: 6px; font-size: 14px; line-height: 1.6;">${action}</div>
        </div>
        ${notes ? `<div style="margin-bottom: 14px;"><strong style="color: #1f2937; font-size: 13px;">Additional Notes:</strong><div style="color: #64748b; margin-top: 8px; padding: 12px; background: #f9fafb; border-radius: 6px; font-size: 14px; line-height: 1.6;">${notes}</div></div>` : ''}
    `;

    const mapWrapper = document.getElementById('wo-map-wrapper');

    if (lat && lng && lat !== 0 && lng !== 0) {
        mapWrapper.style.display = 'block';
        // Small delay to ensure modal is visible before initializing map
        setTimeout(() => {
            if (woMap) {
                woMap.remove();
                woMap = null;
            }
            woMap = L.map('wo-map').setView([lat, lng], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
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
        }, 200);
    } else {
        mapWrapper.style.display = 'none';
    }

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