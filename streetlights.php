<?php
require_once 'dbconnect.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_password') {
    ob_clean();
    header('Content-Type: application/json');
    $admin_password = $_POST['admin_password'] ?? '';
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if ($user_data && password_verify($admin_password, $user_data['password_hash'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

function getDimmingLabel($level) {
    $level = intval($level);
    if ($level <= 30)  return ['🌒 Low',     '#3b82f6', '#eff6ff'];
    if ($level <= 50)  return ['🌓 Medium',  '#8b5cf6', '#f5f3ff'];
    if ($level <= 75)  return ['🌔 High',    '#f59e0b', '#fffbeb'];
    return                    ['🌕 Full',    '#10b981', '#ecfdf5'];
}

require_once 'firebase_config.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!canDo('control_streetlights')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
    
    $action = $_POST['bulk_action'];
    $dimming = intval($_POST['dimming_level'] ?? 70);
    $admin_password = $_POST['bulk_admin_password'] ?? '';

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if (!$user_data || !password_verify($admin_password, $user_data['password_hash'])) {
        header('Location: streetlights.php?error=invalid_password');
        exit();
    }
    
    if ($action === 'ON') {
        $conn->query("UPDATE streetlights SET power_state = 'ON', dimming_level = $dimming");
        logActivity($conn, $_SESSION['user_id'], 'Bulk Control', "Turned all streetlights ON at $dimming%");

        $firebaseUpdate = [
            'mode' => 1, 
            'targetBrightness' => $dimming,
            'commandTimestamp' => round(microtime(true) * 1000)
        ];
        $url = FirebaseConfig::DATABASE_URL . '/SG-NODE2/Control.json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebaseUpdate));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_exec($ch);
        curl_close($ch);
        
    } elseif ($action === 'OFF') {
        $conn->query("UPDATE streetlights SET power_state = 'OFF'");
        logActivity($conn, $_SESSION['user_id'], 'Bulk Control', 'Turned all streetlights OFF');

        $firebaseUpdate = [
            'mode' => 2, 
            'targetBrightness' => 0,
            'commandTimestamp' => round(microtime(true) * 1000)
        ];
        $url = FirebaseConfig::DATABASE_URL . '/SG-NODE2/Control.json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebaseUpdate));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_exec($ch);
        curl_close($ch);
    }
    
    header('Location: streetlights.php?success=bulk_success');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['light_id'])) {
    if (!canDo('control_streetlights')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
    
    $light_id = intval($_POST['light_id']);
    $action = $_POST['action'];
    
    if ($action === 'toggle') {
        $power = $_POST['power_state'] === 'ON' ? 'OFF' : 'ON';
        $admin_password = $_POST['admin_password'] ?? '';

        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        if (!$user_data || !password_verify($admin_password, $user_data['password_hash'])) {
            header('Location: streetlights.php?error=invalid_password');
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE streetlights SET power_state = ? WHERE light_id = ?");
        $stmt->bind_param("si", $power, $light_id);
        $stmt->execute();

        $nodeQuery = $conn->prepare("SELECT node_name, dimming_level FROM streetlights WHERE light_id = ?");
        $nodeQuery->bind_param("i", $light_id);
        $nodeQuery->execute();
        $nodeResult = $nodeQuery->get_result();
        $nodeData = $nodeResult->fetch_assoc();

        if ($nodeData['node_name'] === 'SL-001') {
            $firebaseUpdate = [
                'mode' => ($power === 'ON') ? 1 : 2,
                'targetBrightness' => ($power === 'ON') ? $nodeData['dimming_level'] : 0,
                'commandTimestamp' => round(microtime(true) * 1000)
            ];
            $url = FirebaseConfig::DATABASE_URL . '/SG-NODE2/Control.json';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebaseUpdate));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_exec($ch);
            curl_close($ch);
        }
        
        logActivity($conn, $_SESSION['user_id'], 'Light Control', "Toggled light #$light_id to $power");
    }
    
    header('Location: streetlights.php');
    exit();
}

$streetlights_query = "SELECT * FROM streetlights ORDER BY node_name";
$streetlights_result = $conn->query($streetlights_query);

if (!$streetlights_result) {
    die("Query failed: " . $conn->error);
}

$diagnostic_message = '';
if (isset($_GET['diagnostic_id'])) {
    $diag_id = intval($_GET['diagnostic_id']);
    $diag_query = $conn->prepare("SELECT * FROM diagnostic_logs WHERE diagnostic_id = ?");
    $diag_query->bind_param("i", $diag_id);
    $diag_query->execute();
    $diag_result = $diag_query->get_result();
    if ($diag_data = $diag_result->fetch_assoc()) {
        $diagnostic_message = $diag_data;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Streetlights - ShineGuard</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

.page-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
}

.page-header p {
    color: var(--dim);
    font-size: 15px;
    margin: 0;
    font-weight: 500;
}

#map {
    width: 100%;
    height: 600px;
    border-radius: 12px;
    border: 3px solid var(--border);
    z-index: 1;
}

.map-view-toggle {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.view-btn {
    padding: 10px 20px;
    border: 2px solid var(--accent);
    background: var(--panel);
    color: var(--text);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.view-btn.active {
    background: var(--accent);
    color: white;
}

.view-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.hidden {
    display: none !important;
}

.leaflet-popup-content-wrapper {
    border-radius: 12px;
    padding: 0;
    font-family: 'Inter', sans-serif;
    min-height: 220px;
}

.leaflet-popup-content {
    margin: 24px 20px;
    font-size: 14px;
    text-align: center;
}

.popup-header {
    font-size: 18px;
    font-weight: 800;
    color: var(--accent);
    margin-bottom: 12px;
}

.popup-info {
    margin: 8px 0;
}

.popup-controls {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    justify-content: center;
}

.popup-btn {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s;
}

.popup-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.popup-btn.primary {
    background: #10b981;
    color: white;
}

.popup-btn.secondary {
    background: #3b82f6;
    color: white;
}

.diagnostic-result {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.diagnostic-item {
    padding: 15px;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    background: #f9fafb;
}

.diagnostic-item.pass {
    border-color: #10b981;
    background: #d1fae5;
}

.diagnostic-item.fail {
    border-color: #ef4444;
    background: #fee2e2;
}

.diagnostic-item strong {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #64748b;
}

.diagnostic-item .result {
    font-size: 20px;
    font-weight: 800;
}

.diagnostic-item.pass .result {
    color: #065f46;
}

.diagnostic-item.fail .result {
    color: #991b1b;
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

       <center><h1>💡 STREETLIGHT MANAGEMENT</h1>
        <p>Monitor and control all 32 streetlight nodes in Barangay Hulo, Mandaluyong City</p></center> 
    </div>





    <div class="map-view-toggle panel" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center; border-top: 5px solid #10b981;">
        <button class="view-btn active" onclick="switchView('map')">🗺️ Map View</button>
        <button class="view-btn" onclick="switchView('grid')">🔲 Grid View</button>
        <button class="view-btn" onclick="switchView('table')">📋 Table View</button>
    </div>

    <div id="mapViewPanel" class="panel">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="margin-bottom: 8px;">🗺️ Streetlight Location Map - Barangay Hulo</h2>
                <p style="color: var(--dim); font-size: 13px; margin: 0;">
                    📍 Click on any marker to view streetlight details and controls • Powered by OpenStreetMap
                </p>
            </div>
            
            <div style="display: flex; align-items: center;">
                <button onclick="centerMap()" class="btn" style="background: #10b981; color: white;">
                    🎯 Center Map
                </button>
            </div>
        </div>
        
        <div id="map"></div>
    </div>

    <div id="gridViewPanel" class="hidden panel">
        <h2>🗺️ Streetlight Network Grid</h2>
        <div class="grid-map">
            <?php 
            $streetlights_result->data_seek(0);
            while($light = $streetlights_result->fetch_assoc()): 
                $status_class = $light['power_state'] === 'ON' ? 'online' : 'offline';
            ?>
            <div class="node-card <?php echo $status_class; ?>" 
                 onclick="openNodeModal(<?php echo $light['light_id']; ?>)"
                 style="cursor: pointer;">
                <div class="status-dot"></div>
                <div style="font-size: 24px; margin-bottom: 8px;">💡</div>
                <div class="node-id"><?php echo htmlspecialchars($light['node_name']); ?></div>
                <?php [$label, $color, $bg] = getDimmingLabel($light['dimming_level']); ?>
                <small style="font-size: 10px; font-weight: 700; color: <?php echo $color; ?>; background: <?php echo $bg; ?>; padding: 2px 8px; border-radius: 10px;"><?php echo $label; ?></small>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="tableViewPanel" class="hidden panel">
        <h2>📋 Detailed List</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Node</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Power</th>
                        <th>Dimming</th>
                        <th>Installation</th>
                        <th style="position: sticky; right: 0; background: var(--panel); z-index: 2; border-left: 1px solid var(--border); text-align: center; padding-right: 20px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $streetlights_result->data_seek(0);
                    while($light = $streetlights_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($light['node_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($light['location']); ?></td>
                        <td><span class="badge <?php echo strtolower($light['status']); ?>"><?php echo $light['status']; ?></span></td>
                        <td><span class="badge <?php echo $light['power_state'] === 'ON' ? 'ok' : 'fail'; ?>"><?php echo $light['power_state']; ?></span></td>
                        <td><?php [$label, $color, $bg] = getDimmingLabel($light['dimming_level']); ?>
                            <span style="font-size: 0.75rem; font-weight: 700; color: <?php echo $color; ?>; background: <?php echo $bg; ?>; padding: 3px 10px; border-radius: 12px;"><?php echo $label; ?></span>
                        </td>
                        <td><?php echo $light['installation_date'] ? date('M d, Y', strtotime($light['installation_date'])) : 'N/A'; ?></td>
                        <td style="position: sticky; right: 0; background: var(--panel); z-index: 1; border-left: 1px solid var(--border); white-space: nowrap; text-align: center; padding: 8px 20px;">
                            <?php if (canDo('control_streetlights')): ?>
                                <button onclick="toggleLight(<?php echo $light['light_id']; ?>, '<?php echo $light['power_state']; ?>')" class="btn-sm" style="padding:6px 18px; <?php echo $light['power_state'] === 'ON' ? 'background:#ef4444;color:white;border-color:#ef4444;' : 'background:#10b981;color:white;border-color:#10b981;'; ?>">
                                    <?php echo $light['power_state'] === 'ON' ? '🔅 OFF' : '🔆 ON'; ?>
                                </button>
                                <button onclick="runDiagnostic(<?php echo $light['light_id']; ?>)" class="btn-sm" style="padding:6px 18px; background: #3b82f6; color:white; border-color:#3b82f6;">
                                    🔧 Test
                                </button>
                            <?php else: ?>
                                <span style="color: var(--dim); font-size: 0.8rem; font-weight: 700;">View Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (canDo('control_streetlights')): ?>
    <div class="panel" style="border-top: 5px solid #3b82f6;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #e0e7ff; color: #4338ca; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🎛️
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 700;">Bulk Control</h2>
                <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #64748b;">Manage all 32 streetlights simultaneously</p>
            </div>
        </div>
        
        <form id="bulkControlForm" method="POST" style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; background: #f1f5f9; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;" onsubmit="return validateBulkForm(this);">
            <div class="form-group" style="margin: 0; flex: 1; min-width: 300px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 10px;">Target Dimming Level</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="dimming_level" value="30" style="display:none;" id="dim_low">
                        <span class="dim-btn" onclick="setDimLevel(this, 30)">🌒 Low</span>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="dimming_level" value="50" style="display:none;" id="dim_medium" checked>
                        <span class="dim-btn active" onclick="setDimLevel(this, 50)">🌓 Medium</span>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="dimming_level" value="75" style="display:none;" id="dim_high">
                        <span class="dim-btn" onclick="setDimLevel(this, 75)">🌔 High</span>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="dimming_level" value="100" style="display:none;" id="dim_full">
                        <span class="dim-btn" onclick="setDimLevel(this, 100)">🌕 Full</span>
                    </label>
                </div>
                <small style="color: #94a3b8; display: block; margin-top: 8px;">Applicable only when turning lights ON</small>
            </div>
            
            <div style="display: flex; gap: 12px; margin-left: auto; align-items: center;">
                <button type="button" class="btn" onclick="openBulkModal('OFF')" style="background: #ef4444; color: white; border: none; padding: 12px 24px; font-weight: 600; border-radius: 8px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.5); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 8px -1px rgba(239, 68, 68, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(239, 68, 68, 0.5)'">
                    <span style="font-size: 1.25rem;"></span> Turn All OFF
                </button>
                <button type="button" class="btn" onclick="openBulkModal('ON')" style="background: #10b981; color: white; border: none; padding: 12px 24px; font-weight: 600; border-radius: 8px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.5); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 8px -1px rgba(16, 185, 129, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(16, 185, 129, 0.5)'">
                    <span style="font-size: 1.25rem;"></span> Turn All ON
                </button>
            </div>
            
            <input type="hidden" name="bulk_action" id="bulk_action_input">
            <input type="hidden" name="bulk_admin_password" id="hidden_bulk_admin_password">
        </form>
    </div>
    <?php endif; ?>


</main>
</div>

<form id="toggleForm" method="POST" style="display: none;">
    <input type="hidden" name="light_id" id="toggle_light_id">
    <input type="hidden" name="power_state" id="toggle_power_state">
    <input type="hidden" name="admin_password" id="toggle_admin_password">
    <input type="hidden" name="action" value="toggle">
</form>



<div id="nodeModal" class="modal">
    <div class="modal-content modal-spring">
        <h2 id="modalTitle">💡 Streetlight Control</h2>
        <div id="modalContent">
            <p style="text-align: center; color: var(--dim);">Loading...</p>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button onclick="closeModal()" class="btn">Close</button>
        </div>
    </div>
</div>

<div id="toggleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div id="toggleModalIcon" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">💡</div>
      <div>
        <div id="toggleModalTitle" style="font-size:1.1rem; font-weight:800; color:#0f172a;">Turn OFF Streetlight</div>
        <div id="toggleModalNode" style="font-size:0.8rem; color:#64748b; margin-top:2px;">Loading...</div>
      </div>
    </div>
    <p id="toggleModalDesc" style="font-size:0.875rem; color:#475569; margin-bottom:24px; line-height:1.6;">Are you sure you want to change the power state of this streetlight?</p>
    <div id="toggleModalDelayWarning" style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start; font-size: 0.85rem; color: #b45309;">
      <div style="font-size: 1.2rem; line-height: 1;">⏱️</div>
      <div><strong>Execution Delay:</strong> Please note there will be a 5-10 seconds delay for the command to fully execute on the streetlights.</div>
    </div>
    
    <div style="margin-bottom: 24px;">
        <label for="modalAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="modalAdminPassword" placeholder="Enter password to confirm" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="togglePasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeToggleModal()" class="btn">Cancel</button>
      <button id="toggleModalConfirmBtn" onclick="confirmToggle()" style="padding:10px 22px; border-radius:10px; border:none; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s;">Confirm</button>
    </div>
  </div>
</div>

<div id="bulkControlModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div id="bulkModalIcon" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">💡</div>
      <div>
        <div id="bulkModalTitle" style="font-size:1.1rem; font-weight:800; color:#0f172a;">Turn All Streetlights OFF</div>
        <div id="bulkModalSubtitle" style="font-size:0.8rem; color:#64748b; margin-top:2px;">Network-wide Command</div>
      </div>
    </div>
    <p id="bulkModalDesc" style="font-size:0.875rem; color:#475569; margin-bottom:24px; line-height:1.6;">Are you sure you want to turn off all 32 streetlights?</p>
    <div id="bulkModalDelayWarning" style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start; font-size: 0.85rem; color: #b45309;">
      <div style="font-size: 1.2rem; line-height: 1;">⏱️</div>
      <div><strong>Execution Delay:</strong> Please note there will be a 5-10 seconds delay for the command to fully execute on all physical nodes.</div>
    </div>
    
    <div style="margin-bottom: 24px;">
        <label for="bulkModalAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="bulkModalAdminPassword" placeholder="Enter password to confirm" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="bulkModalPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeBulkModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
      <button id="bulkModalConfirmBtn" onclick="confirmBulkAction()" style="padding:10px 22px; border-radius:10px; border:none; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s;">Confirm</button>
    </div>
  </div>
</div>

<div id="diagnosticModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background: rgba(59, 130, 246, 0.1); width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">🔧</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color: var(--text);">Run Self-Check Diagnostic</div>
        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;" id="diagNodeLabel">Loading...</div>
      </div>
    </div>
    <p style="font-size:0.875rem; color:#475569; margin-bottom:16px; line-height:1.6;">This will automatically run tests on the selected streetlight node. The following will be checked:</p>
    <div class="info-box">
      <div style="display:flex; flex-direction:column; gap:10px;">
        <div style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;"><span style="color:#10b981; font-size:1rem;">✓</span> Power Supply</div>
        <div style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;"><span style="color:#10b981; font-size:1rem;">✓</span> Sensor Functionality</div>
        <div style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;"><span style="color:#10b981; font-size:1rem;">✓</span> Network Connectivity</div>
        <div style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;"><span style="color:#10b981; font-size:1rem;">✓</span> Dimming Controls</div>
      </div>
    </div>
      
      <div style="margin-bottom: 24px; text-align: left;">
          <label for="diagAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
          <input type="password" id="diagAdminPassword" placeholder="Enter password to confirm test" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
          <div id="diagPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
      </div>

      <div style="display:flex; gap:12px; justify-content:flex-end;">
          <button onclick="closeDiagModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
          <button id="diagConfirmBtn" onclick="confirmDiagnostic()" style="padding:10px 22px; border-radius:10px; border:none; background:#3b82f6; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 12px rgba(59,130,246,0.35);" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">🔧 Run Test</button>
      </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const canControl = <?php echo canDo('control_streetlights') ? 'true' : 'false'; ?>;
function openBulkModal(action) {
    const modal = document.getElementById('bulkControlModal');
    const icon = document.getElementById('bulkModalIcon');
    const title = document.getElementById('bulkModalTitle');
    const desc = document.getElementById('bulkModalDesc');
    const btn = document.getElementById('bulkModalConfirmBtn');
    
    if (action === 'OFF') {
        icon.style.background = '#fef2f2';
        icon.textContent = '🔅';
        title.textContent = 'Turn All Streetlights OFF';
        desc.textContent = 'Are you sure you want to completely turn off all 32 streetlights?';
        btn.style.background = '#ef4444';
        btn.style.boxShadow = '0 4px 12px rgba(239,68,68,0.35)';
        btn.textContent = '🔅 Turn All OFF';
    } else {
        const dimmingLevel = document.querySelector('input[name="dimming_level"]:checked').value;
        icon.style.background = '#f0fdf4';
        icon.textContent = '🔆';
        title.textContent = 'Turn All Streetlights ON';
        desc.textContent = `Are you sure you want to turn on all 32 streetlights at ${dimmingLevel}% dimming level?`;
        btn.style.background = '#10b981';
        btn.style.boxShadow = '0 4px 12px rgba(16,185,129,0.35)';
        btn.textContent = '🔆 Turn All ON';
    }
    
    modal._action = action;
    modal.style.display = 'flex';
}

function closeBulkModal() {
    document.getElementById('bulkControlModal').style.display = 'none';
    const pwdInput = document.getElementById('bulkModalAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('bulkModalPasswordError').style.display = 'none';
    }
}

function confirmBulkAction() {
    const pwdInput = document.getElementById('bulkModalAdminPassword');
    const pwdError = document.getElementById('bulkModalPasswordError');
    const modal = document.getElementById('bulkControlModal');
    
    if (!pwdInput.value.trim()) {
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }

    document.getElementById('bulk_action_input').value = modal._action;
    document.getElementById('hidden_bulk_admin_password').value = pwdInput.value;

    document.getElementById('bulkControlForm').submit();
}

function setDimLevel(el, value) {

    document.querySelectorAll('[onclick^="setDimLevel"]').forEach(span => {
        span.style.borderColor = '#cbd5e1';
        span.style.background = 'white';
        span.style.color = '#64748b';
    });

    el.style.borderColor = '#3b82f6';
    el.style.background = '#eff6ff';
    el.style.color = '#3b82f6';

    el.previousElementSibling.checked = true;
}

function getDimmingLabel(level) {
    level = parseInt(level);
    if (level <= 30) return { label: '🌒 Low',     color: '#3b82f6', bg: '#eff6ff' };
    if (level <= 50) return { label: '🌓 Medium',  color: '#8b5cf6', bg: '#f5f3ff' };
    if (level <= 75) return { label: '🌔 High',    color: '#f59e0b', bg: '#fffbeb' };
    return                  { label: '🌕 Full',    color: '#10b981', bg: '#ecfdf5' };
}

const streetlights = <?php 
    $streetlights_result->data_seek(0);
    $lights_array = [];
    while($light = $streetlights_result->fetch_assoc()) {
        $lights_array[] = $light;
    }
    echo json_encode($lights_array);
?>;

let map;
let markers = [];

function initMap() {

    const huloCenter = [14.5794, 121.0359]; // Note: Leaflet uses [lat, lng]

    map = L.map('map').setView(huloCenter, 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    const greenIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: #10b981; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
    
    const redIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: #ef4444; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });

    streetlights.forEach((light, index) => {

        const lat = parseFloat(light.latitude) || (14.5794 + (Math.random() - 0.5) * 0.01);
        const lng = parseFloat(light.longitude) || (121.0359 + (Math.random() - 0.5) * 0.01);
        
        const markerIcon = light.power_state === 'ON' ? greenIcon : redIcon;
        
        const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);

        const popupContent = `
            <br>
            <div class="popup-header">💡 ${light.node_name}</div>
            <div class="popup-info"><strong>Location:</strong> ${light.location}</div>
            <div class="popup-info">
                <strong>Status:</strong> 
                <span class="badge ${light.power_state === 'ON' ? 'ok' : 'fail'}" style="font-size: 11px;">
                    ${light.power_state}
                </span>
            </div>
            <div class="popup-info">
                <strong>Dimming:</strong> 
                ${(() => { const d = getDimmingLabel(light.dimming_level); return `<span style="font-weight:700;color:${d.color};">${d.label}</span>`; })()}
            </div>
            ${canControl ? `
            <div class="popup-controls">
                <button onclick="toggleLight(${light.light_id}, '${light.power_state}')" class="popup-btn primary">
                    ${light.power_state === 'ON' ? '🔅 Turn OFF' : '🔆 Turn ON'}
                </button>
                <button onclick="runDiagnostic(${light.light_id})" class="popup-btn secondary">
                    🔧 Run Test
                </button>
            </div>
            ` : `
            <div style="margin-top: 15px; color: var(--dim); font-size: 0.8rem; font-weight: 600;">View Only</div>
            `}
        `;
        
        marker.bindPopup(popupContent, { maxWidth: 360 });
        markers.push(marker);
    });

    if (markers.length > 0) {
        var group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds(), {padding: [30, 30]});
    }
}

function centerMap() {
    if (map) {
        map.setView([14.5794, 121.0359], 16);
    }
}

function switchView(view) {

    document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    document.getElementById('mapViewPanel').classList.add('hidden');
    document.getElementById('gridViewPanel').classList.add('hidden');
    document.getElementById('tableViewPanel').classList.add('hidden');

    if (view === 'map') {
        document.getElementById('mapViewPanel').classList.remove('hidden');

        setTimeout(() => {
            if (map) {
                map.invalidateSize();
                centerMap();
            }
        }, 100);
    } else if (view === 'grid') {
        document.getElementById('gridViewPanel').classList.remove('hidden');
    } else if (view === 'table') {
        document.getElementById('tableViewPanel').classList.remove('hidden');
    }
}

function toggleLight(lightId, currentState) {
    const light = streetlights.find(l => l.light_id == lightId);
    const isTurningOff = currentState === 'ON';
    const modal = document.getElementById('toggleModal');
    const icon = document.getElementById('toggleModalIcon');
    const title = document.getElementById('toggleModalTitle');
    const desc = document.getElementById('toggleModalDesc');
    const btn = document.getElementById('toggleModalConfirmBtn');
    const nodeLabel = document.getElementById('toggleModalNode');

    nodeLabel.textContent = light ? `Node: ${light.node_name}` : `Node ID: ${lightId}`;

    if (isTurningOff) {
        icon.style.background = '#fef2f2';
        icon.textContent = '🔅';
        title.textContent = 'Turn OFF Streetlight';
        desc.textContent = 'This will power off the streetlight. It will remain off until manually turned back on.';
        btn.style.background = '#ef4444';
        btn.style.boxShadow = '0 4px 12px rgba(239,68,68,0.35)';
        btn.textContent = '🔅 Turn OFF';
    } else {
        icon.style.background = '#f0fdf4';
        icon.textContent = '🔆';
        title.textContent = 'Turn ON Streetlight';
        desc.textContent = 'This will power on the streetlight using the current dimming level setting.';
        btn.style.background = '#10b981';
        btn.style.boxShadow = '0 4px 12px rgba(16,185,129,0.35)';
        btn.textContent = '🔆 Turn ON';
    }

    modal._lightId = lightId;
    modal._currentState = currentState;
    modal.style.display = 'flex';
}

function confirmToggle() {
    const modal = document.getElementById('toggleModal');
    const pwdInput = document.getElementById('modalAdminPassword');
    const pwdError = document.getElementById('togglePasswordError');
    
    if (!pwdInput.value.trim()) {
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }
    
    document.getElementById('toggle_light_id').value = modal._lightId;
    document.getElementById('toggle_power_state').value = modal._currentState;
    document.getElementById('toggle_admin_password').value = pwdInput.value;
    
    modal.style.display = 'none';
    document.getElementById('toggleForm').submit();
}

function closeToggleModal() {
    document.getElementById('toggleModal').style.display = 'none';
    const pwdInput = document.getElementById('modalAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('togglePasswordError').style.display = 'none';
    }
}

document.getElementById('toggleModal').addEventListener('click', function(e) {
    if (e.target === this) closeToggleModal();
});

function runDiagnostic(lightId) {
    const light = streetlights.find(l => l.light_id == lightId);
    const modal = document.getElementById('diagnosticModal');
    document.getElementById('diagNodeLabel').textContent = light ? `Node: ${light.node_name}` : `Node ID: ${lightId}`;
    modal._lightId = lightId;
    modal.style.display = 'flex';
}

async function confirmDiagnostic() {
    const pwdInput = document.getElementById('diagAdminPassword');
    const pwdError = document.getElementById('diagPasswordError');
    const modal = document.getElementById('diagnosticModal');
    const btn = document.getElementById('diagConfirmBtn');
    
    if (!pwdInput.value.trim()) {
        pwdError.textContent = 'Password is required';
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }
    
    pwdError.style.display = 'none';
    btn.innerHTML = 'Testing...';
    btn.disabled = true;
    
    try {
        const formData = new URLSearchParams();
        formData.append('light_id', modal._lightId);
        formData.append('admin_password', pwdInput.value);
        
        // Save original HTML in case of password failure
        const inner = modal.querySelector('.modal-spring');
        const originalHTML = inner.innerHTML;
        
        // Show progress view
        inner.innerHTML = `
            <div style="text-align: center; padding: 20px 0;">
                <div style="font-size: 30px; margin-bottom: 10px;">⏳</div>
                <h3 style="margin: 0 0 10px 0; color: #0f172a;">Running Smart Diagnostics</h3>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Please wait while we test hardware and network telemetry...</p>
                
                <div style="text-align: left; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 13px; color: #334155;">
                    <div id="diag-step-1" style="margin-bottom: 8px;">[ ] Pinging IoT Node (Firebase)...</div>
                    <div id="diag-step-2" style="margin-bottom: 8px;">[ ] Reading Hardware Sensors...</div>
                    <div id="diag-step-3" style="margin-bottom: 8px;">[ ] Checking Relay States...</div>
                    <div id="diag-step-4" style="margin-bottom: 0;">[ ] Auditing Maintenance History...</div>
                </div>
            </div>
        `;
        
        await new Promise(r => setTimeout(r, 600));
        document.getElementById('diag-step-1').innerHTML = '<b>[✔] Pinging IoT Node (Firebase)... DONE</b>';
        await new Promise(r => setTimeout(r, 600));
        document.getElementById('diag-step-2').innerHTML = '<b>[✔] Reading Hardware Sensors... DONE</b>';
        await new Promise(r => setTimeout(r, 600));
        document.getElementById('diag-step-3').innerHTML = '<b>[✔] Checking Relay States... DONE</b>';
        await new Promise(r => setTimeout(r, 600));
        document.getElementById('diag-step-4').innerHTML = '<b>[✔] Auditing Maintenance History... DONE</b>';
        await new Promise(r => setTimeout(r, 400));
        
        const response = await fetch('api/run_diagnostic.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const res = data.results;
            
            let healthColor = res.health === 'Excellent' ? '#10b981' : (res.health === 'Warning' ? '#f59e0b' : '#ef4444');
            
            inner.innerHTML = `
                <div style="text-align: center; padding: 10px 0;">
                    <div style="background: ${healthColor}20; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                        <span style="font-size: 28px;">${res.health === 'Excellent' ? '✅' : (res.health === 'Warning' ? '⚠️' : '❌')}</span>
                    </div>
                    <h2 style="margin: 0 0 5px 0; color: #0f172a;">System Health: ${res.score}%</h2>
                    <div style="color: ${healthColor}; font-weight: 700; margin-bottom: 25px;">${res.health}</div>
                    
                    <div style="text-align: left; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 25px;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; background: ${res.network.status==='Pass'?'#f0fdf4':(res.network.status==='Warning'?'#fffbeb':'#fef2f2')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.network.status==='Pass'?'✅':(res.network.status==='Warning'?'⚠️':'❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Network Connection</div>
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.3;">${res.network.message}</div>
                            </div>
                        </div>
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; background: ${res.sensors.status==='Pass'?'#f0fdf4':(res.sensors.status==='Warning'?'#fffbeb':'#fef2f2')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.sensors.status==='Pass'?'✅':(res.sensors.status==='Warning'?'⚠️':'❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Hardware Sensors</div>
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.3;">${res.sensors.message}</div>
                            </div>
                        </div>
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; background: ${res.relay.status==='Pass'?'#f0fdf4':(res.relay.status==='Warning'?'#fffbeb':'#fef2f2')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.relay.status==='Pass'?'✅':(res.relay.status==='Warning'?'⚠️':'❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Relay State</div>
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.3;">${res.relay.message}</div>
                            </div>
                        </div>
                        <div style="padding: 12px 16px; display: flex; align-items: center; background: ${res.history.status==='Pass'?'#f0fdf4':(res.history.status==='Warning'?'#fffbeb':'#fef2f2')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.history.status==='Pass'?'✅':(res.history.status==='Warning'?'⚠️':'❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">History Check</div>
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.3;">${res.history.message}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display:flex; justify-content: center;">
                        <button onclick="window.location.reload()" style="padding:10px 24px; border-radius:10px; border:none; background:#0f172a; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer;">Close Report</button>
                    </div>
                </div>
            `;
            
        } else {
            inner.innerHTML = originalHTML;
            const restoredPwdInput = document.getElementById('diagAdminPassword');
            const restoredPwdError = document.getElementById('diagPasswordError');
            if (restoredPwdInput) {
                restoredPwdInput.value = '';
                restoredPwdInput.style.borderColor = '#ef4444';
            }
            if (restoredPwdError) {
                restoredPwdError.textContent = data.error || 'Diagnostic failed to run.';
                restoredPwdError.style.display = 'block';
            }
        }
    } catch(err) {
        console.error(err);
        window.location.reload();
    }
}

function closeDiagModal() {
    document.getElementById('diagnosticModal').style.display = 'none';

    const pwdInput = document.getElementById('diagAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('diagPasswordError').style.display = 'none';
        document.getElementById('diagConfirmBtn').innerHTML = '🔧 Run Test';
        document.getElementById('diagConfirmBtn').disabled = false;
    }
}

document.getElementById('diagnosticModal').addEventListener('click', function(e) {
    if (e.target === this) closeDiagModal();
});

function openNodeModal(lightId) {
    const light = streetlights.find(l => l.light_id == lightId);
    if (!light) return;
    
    const modal = document.getElementById('nodeModal');
    const title = document.getElementById('modalTitle');
    const content = document.getElementById('modalContent');
    
    title.textContent = `💡 ${light.node_name}`;
    
    content.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
            <div>
                <strong>Location:</strong><br>
                ${light.location}
            </div>
            <div>
                <strong>Status:</strong><br>
                <span class="badge ${light.status.toLowerCase()}">${light.status}</span>
            </div>
            <div>
                <strong>Power State:</strong><br>
                <span class="badge ${light.power_state === 'ON' ? 'ok' : 'fail'}">${light.power_state}</span>
            </div>
            <div>
                <strong>Dimming Level:</strong><br>
                ${(() => { const d = getDimmingLabel(light.dimming_level); return `<span style="font-size:0.8rem;font-weight:700;color:${d.color};background:${d.bg};padding:3px 10px;border-radius:12px;">${d.label}</span>`; })()}
            </div>
            <div>
                <strong>Coordinates:</strong><br>
                ${light.latitude || 'N/A'}, ${light.longitude || 'N/A'}
            </div>
            <div>
                <strong>Installation:</strong><br>
                ${light.installation_date || 'N/A'}
            </div>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            ${canControl ? `
            <button onclick="toggleLight(${light.light_id}, '${light.power_state}')" class="btn primary">
                ${light.power_state === 'ON' ? '🔅 Turn OFF' : '🔆 Turn ON'}
            </button>
            <button onclick="runDiagnostic(${light.light_id})" class="btn" style="background: #3b82f6; color: white;">
                🔧 Run Diagnostic Test
            </button>
            ` : `
            <div style="width: 100%; text-align: center; color: var(--dim); font-weight: 700;">View Only</div>
            `}
        </div>
    `;
    
    modal.classList.add('open');

    const mc = modal.querySelector('.modal-content');
    if (mc) { mc.classList.remove('modal-spring'); void mc.offsetWidth; mc.classList.add('modal-spring'); }
}

function closeModal() {
    document.getElementById('nodeModal').classList.remove('open');
}

function refreshIoTData() {
    fetch('firebase_control.php?action=status')
        .then(response => response.json())
        .then(data => {
            if (data.sensor) {
                document.getElementById('iot-temp').textContent = (data.sensor.temperature || '--') + '°C';
                document.getElementById('iot-ldr').textContent = data.sensor.ldrData || '--';
                document.getElementById('iot-voltage').textContent = (data.sensor.voltage ? data.sensor.voltage.toFixed(3) + ' V' : '-- V');
            }
            
            if (data.actuator) {
                const lightOn = data.actuator.lightOn;
                document.getElementById('iot-status').innerHTML = 
                    `<span class="badge ${lightOn ? 'ok' : 'fail'}">${lightOn ? 'ONLINE' : 'OFFLINE'}</span>`;
            }
        })
        .catch(error => {
            console.error('Error fetching Firebase data:', error);
        });
}

setInterval(refreshIoTData, 10000);

window.onload = function() {
    initMap();
    refreshIoTData();
};

document.getElementById('nodeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
</body>
</html>
<?php
$conn->close();
?>
