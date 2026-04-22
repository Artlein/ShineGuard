<?php
require_once 'dbconnect.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'acknowledge') {
    checkCsrf();
    if (!canDo('acknowledge_alerts')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
    $alert_id = intval($_POST['alert_id']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE alerts SET status = 'Acknowledged', acknowledged_at = NOW(), acknowledged_by = ? WHERE alert_id = ?");
    $stmt->bind_param("ii", $user_id, $alert_id);
    if ($stmt->execute()) {
        logActivity($conn, $user_id, 'Alert', "Acknowledged alert #$alert_id");
    }
    $stmt->close();
    header("Location: alerts.php?success=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_work_order') {
    checkCsrf();
    if (!canDo('create_work_orders')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
    $light_id = intval($_POST['light_id']);
    $alert_id = !empty($_POST['alert_id']) ? intval($_POST['alert_id']) : null;
    $action_taken = sanitize($_POST['action_taken']);
    $full_description = sanitize($_POST['full_description']);
    $admin_password = $_POST['admin_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (!isRecentlyAuthorized()) {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        if (!$user_data || !password_verify($admin_password, $user_data['password_hash'])) {
            header('Location: alerts.php?error=invalid_password');
            exit();
        }
        setAuthorized();
    }
    
    $stmt = $conn->prepare("INSERT INTO maintenance_logs (light_id, alert_id, user_id, action_taken, notes, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
    $stmt->bind_param("iiiss", $light_id, $alert_id, $user_id, $action_taken, $full_description);
    if ($stmt->execute()) {
        logActivity($conn, $user_id, 'Work Order', "Created scheduled work order for light #$light_id");
    }
    $stmt->close();
    header("Location: alerts.php?success=wo");
    exit();
}

$alerts_query = "SELECT a.*, s.node_name, s.location,
                         u.full_name AS ack_by_name
                  FROM alerts a
                  LEFT JOIN streetlights s ON a.light_id = s.light_id
                  LEFT JOIN users u ON u.user_id = a.acknowledged_by
                  ORDER BY
                    FIELD(a.status,'Open','Acknowledged','Resolved'),
                    a.created_at DESC";
$alerts = $conn->query($alerts_query);

$lights = $conn->query("SELECT light_id, node_name FROM streetlights ORDER BY node_name");

if (!isset($theme_color)) {
    $theme_color = '#10b981';
    $tc_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($tc_result && $tc_row = $tc_result->fetch_assoc()) {
        $theme_color = $tc_row['config_value'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>Alerts - Shine Guard Hulo</title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">

<style>
    
<?php include 'assets/style.css'; ?>

:root { 
    --theme-color: <?php echo $theme_color; ?>;
    --accent: <?php echo $theme_color; ?>;
    --surface: var(--panel);
    --surface-2: var(--muted);
    --text-primary: var(--text);
    --text-secondary: var(--dim);
    --text-muted: #64748b;
    --border: var(--border);
    --radius: 16px;
    --radius-sm: 10px;
    --shadow: 0 4px 20px var(--shadow);
    --blue: #3b82f6;
    --blue-dim: rgba(59, 130, 246, 0.1);
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

body {
  background: var(--bg);
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
}

.main-content {
  padding: 2.2rem 2.6rem;
  font-family: 'Inter', sans-serif;
  background: var(--bg);
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

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 1.4rem;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
  text-decoration: none;
  gap: 8px;
}

.btn-sm {
  padding: 0.35rem 0.8rem;
  font-size: 0.75rem;
  border-radius: 6px;
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

.badge-high   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.badge-medium { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }
.badge-low    { background:#fefce8; color:#ca8a04; border:1px solid #fde68a; }

.badge-open         { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.badge-acknowledged { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.badge-resolved     { background:#f8fafc; color:#64748b; border:1px solid #cbd5e1; }

.detail-row { display:none; }
.detail-row.open { display:table-row; }
.detail-row td  { background:#f8fafc; padding:.8rem 1.1rem; font-size:.82rem; color:#475569; border-bottom:1px solid #e2e8f0; }
.detail-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.6rem 1.6rem; }
.detail-item strong { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:.15rem; }
.detail-item span   { font-weight:600; color:#1e293b; }
.expand-btn { cursor:pointer; font-size:1rem; transition:transform .2s; display:inline-block; }
.expand-btn.open { transform:rotate(90deg); }
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
       <center> <h1>🚨 Alerts & Maintenance</h1>
        <p>Predictive maintenance alerts and work orders</p>
    </div></center> 
    <div class="panel" style="background: linear-gradient(to right, #ffffff, #f8fafc); border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); padding: 24px; margin-top: 24px; border: 1px solid #e2e8f0; border-top: 5px solid #ef4444;">
        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="background: #fee2e2; color: #ef4444; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    🚨
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 700;">Recent Alerts</h2>
                    <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #64748b;">Review and manage system warnings</p>
                </div>
            </div>
            <?php if (canDo('create_work_orders')): ?>
            <button class="btn primary" onclick="document.getElementById('woModal').style.display='flex'" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.5); transition: all 0.2s;">
                + New Work Order
            </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:32px;"></th>
                        <th>#</th>
                        <th>Severity</th>
                        <th>Type</th>
                        <th>Node</th>
                        <th>Location</th>
                        <th>Description</th>
                        <th>RUL</th>
                        <th>Status</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $now = new DateTime();
                    while($alert = $alerts->fetch_assoc()):
                        
                        $sev = strtolower($alert['severity']);
                        $sevIcon = $sev === 'high' ? '🔴' : ($sev === 'medium' ? '🟠' : '🟡');

                        $statKey = strtolower(str_replace(' ', '_', $alert['status']));

                        $created = new DateTime($alert['created_at']);
                        $diff = $now->diff($created);
                        if ($diff->days >= 1)       $age = $diff->days . 'd ago';
                        elseif ($diff->h >= 1)      $age = $diff->h . 'h ago';
                        elseif ($diff->i >= 1)      $age = $diff->i . 'm ago';
                        else                        $age = 'Just now';

                        $desc = htmlspecialchars($alert['description']);
                        $shortDesc = mb_strlen($desc) > 70 ? mb_substr($desc, 0, 67) . '…' : $desc;

                        $rowId = 'detail-' . $alert['alert_id'];
                    ?>
                    
                    <tr onclick="toggleDetail('<?php echo $rowId; ?>', this)" style="cursor:pointer;">
                        <td style="text-align:center; color:var(--dim);">
                            <span class="expand-btn" id="btn-<?php echo $rowId; ?>">▶</span>
                        </td>
                        <td style="font-weight:700; color:var(--dim);">#<?php echo $alert['alert_id']; ?></td>
                        <td>
                            <span class="badge <?php echo $alert['severity'] === 'High' ? 'fail' : ($alert['severity'] === 'Medium' ? 'warning' : 'ok'); ?>"><?php echo $alert['severity']; ?></span>
                        </td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($alert['alert_type']); ?></td>
                        <td><strong><?php echo htmlspecialchars($alert['node_name'] ?? 'System/Other'); ?></strong></td>
                        <td style="color:var(--dim); font-size:.82rem;"><?php echo htmlspecialchars($alert['location'] ?? 'N/A'); ?></td>
                        <td style="max-width:260px;">
                            <span title="<?php echo $desc; ?>" style="font-size:.82rem; color:var(--text);"><?php echo $shortDesc; ?></span>
                        </td>
                        <td style="white-space:nowrap; font-size:.82rem;">
                            <?php if ($alert['rul_estimate']): ?>
                                <span class="badge warning"><?php echo htmlspecialchars($alert['rul_estimate']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo ($alert['status'] === 'Open' || $alert['status'] === 'Active') ? 'fail' : ($alert['status'] === 'Acknowledged' ? 'warning' : 'ok'); ?>"><?php echo $alert['status']; ?></span>
                        </td>
                        <td style="white-space:nowrap; font-size:.82rem; color:var(--dim);"><?php echo $age; ?></td>
                        <td onclick="event.stopPropagation();">
                            <?php if (($alert['status'] === 'Open' || $alert['status'] === 'Active') && canDo('acknowledge_alerts')): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="acknowledge">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['alert_id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <button type="submit" class="btn primary btn-sm" style="background: #10b981; border: none; padding: 5px 10px; font-size: 0.75rem; color: white;">
                                    ✓ Acknowledge
                                </button>
                            </form>
                            <?php elseif ($alert['status'] === 'Open' || $alert['status'] === 'Active'): ?>
                            <span style="font-size:0.75rem; color:#64748b;">— View only</span>
                            <?php elseif ($alert['status'] === 'Acknowledged'): ?>
                            <span class="badge warning" style="font-size:.7rem;">✓ Acknowledged</span>
                            <?php else: ?>
                            <span class="badge ok" style="font-size:.7rem;">✓ Resolved</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr class="detail-row" id="<?php echo $rowId; ?>">
                        <td colspan="11">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <strong>Full Description</strong>
                                    <span><?php echo $desc; ?></span>
                                </div>
                                <div class="detail-item">
                                    <strong>Created At</strong>
                                    <span><?php echo date('M d, Y  H:i:s', strtotime($alert['created_at'])); ?></span>
                                </div>
                                <?php if ($alert['acknowledged_at']): ?>
                                <div class="detail-item">
                                    <strong>Acknowledged At</strong>
                                    <span><?php echo date('M d, Y  H:i:s', strtotime($alert['acknowledged_at'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <strong>Acknowledged By</strong>
                                    <span><?php echo htmlspecialchars($alert['ack_by_name'] ?? 'Unknown'); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($alert['resolved_at']): ?>
                                <div class="detail-item">
                                    <strong>Resolved At</strong>
                                    <span><?php echo date('M d, Y  H:i:s', strtotime($alert['resolved_at'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <strong>Light ID</strong>
                                    <span>SL-<?php echo str_pad($alert['light_id'], 3, '0', STR_PAD_LEFT); ?></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>

<?php 

$alerts_js = [];
$alerts->data_seek(0);
while($a = $alerts->fetch_assoc()) {
    $alerts_js[$a['alert_id']] = [
        'light_id' => $a['light_id'],
        'alert_type' => $a['alert_type'],
        'description' => $a['description']
    ];
}

$alerts->data_seek(0);
?>

<div id="woModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="modal-content" style="background-color: #fff; padding: 32px; border-radius: 16px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative; border: 1px solid #e2e8f0;">
        <span class="close" onclick="document.getElementById('woModal').style.display='none'" style="position: absolute; right: 24px; top: 20px; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</span>
        <h2 style="margin-top: 0; margin-bottom: 24px; color: #0f172a; font-size: 1.5rem; display: flex; align-items: center; gap: 8px;">
            <span>🔧</span> New Work Order
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="new_work_order">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.875rem; color: #475569;">Link to Alert (Recommended)</label>
                <select name="alert_id" id="alertSelect" style="width: 100%; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1rem; color: #1e293b; background: #fff; outline: none; transition: border-color 0.2s;" onchange="autoFillAlertData(this.value)" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    <option value="">-- Start by selecting an alert --</option>
                    <?php 
                    while($a = $alerts->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $a['alert_id']; ?>">Alert #<?php echo $a['alert_id']; ?> - <?php echo htmlspecialchars($a['alert_type']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.875rem; color: #475569;">Target Streetlight <span style="color: #ef4444;">*</span></label>
                <select name="light_id" id="lightSelect" required style="width: 100%; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1rem; color: #1e293b; background: #f8fafc; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    <option value="">Select a streetlight...</option>
                    <?php while($l = $lights->fetch_assoc()): ?>
                        <option value="<?php echo $l['light_id']; ?>"><?php echo htmlspecialchars($l['node_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.875rem; color: #475569;">Action Plan <span style="color: #ef4444;">*</span></label>
                <input type="text" name="action_taken" id="actionTakenInput" required placeholder="e.g. Inspect relay, Replace bulb..." style="width: 100%; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1rem; color: #1e293b; background: #f8fafc; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
            </div>
            
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1.5px solid #e2e8f0;">
                <label style="display: block; font-weight: 700; margin-bottom: 6px; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Full Description</label>
                <div id="fullDescriptionDisplay" style="font-size: 0.95rem; color: #1e293b; font-weight: 600; line-height: 1.5;">
                    <span style="color: #cbd5e1; font-weight: 400; font-style: italic;">Select an alert to view its full description...</span>
                </div>
                <input type="hidden" name="full_description" id="fullDescriptionInput" value="">
            </div>
            
            <div id="woModalPasswordContainer" style="margin-bottom: 28px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.875rem; color: #0f172a;">🔐 Administrator Password <span style="color: #ef4444;">*</span></label>
                <input type="password" name="admin_password" id="adminPasswordInput" placeholder="Enter password to confirm creation" style="width: 100%; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1rem; color: #1e293b; background: #fff; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('woModal').style.display='none'" class="btn">Cancel</button>
                <button type="submit" class="btn primary">Create Order</button>
            </div>
        </form>
    </div>
</div>

<?php $alerts_js = $alerts_js ?? []; ?>
<script>
const alertData = <?php echo json_encode($alerts_js); ?>;

function openWorkOrderModal(alertId = null) {
    const modal = document.getElementById('woModal');
    const pwdContainer = document.getElementById('woModalPasswordContainer');
    const pwdInput = document.getElementById('adminPasswordInput');
    const btn = modal.querySelector('button[type="submit"]');

    if (isAuthorized) {
        if (pwdContainer) pwdContainer.style.display = 'none';
        if (pwdInput) pwdInput.required = false;
        btn.innerHTML = '🔧 Create Order (Authorized)';
    } else {
        if (pwdContainer) pwdContainer.style.display = 'block';
        if (pwdInput) pwdInput.required = true;
        btn.innerHTML = 'Create Order';
    }

    if (alertId) {
        document.getElementById('alertSelect').value = alertId;
        autoFillAlertData(alertId);
    }
    
    modal.style.display = 'flex';
}

function autoFillAlertData(alertId) {
    if (!alertId || !alertData[alertId]) {

        document.getElementById('lightSelect').value = '';
        document.getElementById('actionTakenInput').value = '';
        document.getElementById('fullDescriptionInput').value = '';
        document.getElementById('fullDescriptionDisplay').innerHTML = '<span style="color: #cbd5e1; font-weight: 400; font-style: italic;">Select an alert to view its full description...</span>';
        return;
    }

    document.getElementById('lightSelect').value = alertData[alertId].light_id;

    const alertType = alertData[alertId].alert_type;
    const desc = alertData[alertId].description;
    
    document.getElementById('actionTakenInput').value = `Investigate & Resolve: [${alertType}]`;
    document.getElementById('fullDescriptionInput').value = desc; // for form POST
    document.getElementById('fullDescriptionDisplay').textContent = desc; // visual display

    const actionInput = document.getElementById('actionTakenInput');
    const descDisplay = document.getElementById('fullDescriptionDisplay');
    
    actionInput.style.background = '#f0fdf4';
    descDisplay.style.color = '#3b82f6';
    setTimeout(() => { 
        actionInput.style.background = '#f8fafc'; 
        descDisplay.style.color = '#1e293b'; 
    }, 800);
}

function toggleDetail(rowId, mainRow) {
    const detail = document.getElementById(rowId);
    const btn    = document.getElementById('btn-' + rowId);
    const isOpen = detail.classList.contains('open');
    detail.classList.toggle('open', !isOpen);
    btn.classList.toggle('open', !isOpen);
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const alertId = urlParams.get('id');
    if (alertId) {
        const rowId = 'detail-' + alertId;
        const detailRow = document.getElementById(rowId);
        if (detailRow) {
            // Find the main row (the one before it)
            const mainRow = detailRow.previousElementSibling;
            if (mainRow) {
                setTimeout(() => {
                    mainRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    toggleDetail(rowId, mainRow);
                    mainRow.style.background = 'rgba(59, 130, 246, 0.1)';
                    setTimeout(() => {
                        mainRow.style.background = '';
                    }, 3000);
                }, 500);
            }
        }
    }
});
</script>

</body>
</html>
