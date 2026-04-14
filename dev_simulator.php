<?php
require_once 'dbconnect.php';
requireLogin(['System Admin']);

$current_page = 'dev_simulator.php';
$theme_color = '#3b82f6';
if (isset($conn)) {
    $theme_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($theme_result && $row = $theme_result->fetch_assoc()) {
        $theme_color = $row['config_value'];
    }
}

// POST Handler for Simulations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sim_action'])) {
    checkCsrf();
    $light_id = intval($_POST['light_id']);
    $sim_action = $_POST['sim_action'];
    $user_id = $_SESSION['user_id'];

    $response = ['success' => false];

    switch ($sim_action) {
        case 'toggle_power':
            $new_state = $_POST['state'];
            $stmt = $conn->prepare("UPDATE streetlights SET power_state = ? WHERE light_id = ? AND is_virtual = 1");
            $stmt->bind_param("si", $new_state, $light_id);
            if ($stmt->execute()) {
                logActivity($conn, $user_id, '[SIMULATOR] Power Change', "Virtual Node #$light_id power set to $new_state");
                $response['success'] = true;
            }
            break;

        case 'set_dimming':
            $level = intval($_POST['level']);
            $stmt = $conn->prepare("UPDATE streetlights SET dimming_level = ? WHERE light_id = ? AND is_virtual = 1");
            $stmt->bind_param("ii", $level, $light_id);
            if ($stmt->execute()) {
                logActivity($conn, $user_id, '[SIMULATOR] Dimming Change', "Virtual Node #$light_id dimming set to $level%");
                $response['success'] = true;
            }
            break;

        case 'trigger_fault':
            $stmt = $conn->prepare("UPDATE streetlights SET status = 'Maintenance' WHERE light_id = ? AND is_virtual = 1");
            $stmt->bind_param("i", $light_id);
            if ($stmt->execute()) {
                logActivity($conn, $user_id, '[SIMULATOR] Critical Fault', "Virtual Node #$light_id injected with hardware failure");
                
                // Simulate Automated Work Order Generation
                $node_res = $conn->query("SELECT node_name FROM streetlights WHERE light_id = $light_id");
                $node = $node_res->fetch_assoc();
                $desc = "ALARM: Hardware failure detected in virtual node " . $node['node_name'];
                $action = "Replace virtual light module and reset sensor array";
                
                $wo_stmt = $conn->prepare("INSERT INTO alerts (light_id, alert_type, severity, description, status) VALUES (?, 'Fault', 'High', ?, 'Open')");
                $wo_stmt->bind_param("is", $light_id, $desc);
                $wo_stmt->execute();
                
                $response['success'] = true;
                $response['message'] = "Critical fault injected and alert generated.";
            }
            break;

        case 'reset_node':
            $stmt = $conn->prepare("UPDATE streetlights SET status = 'Active', power_state = 'ON', dimming_level = 70 WHERE light_id = ? AND is_virtual = 1");
            $stmt->bind_param("i", $light_id);
            if ($stmt->execute()) {
                logActivity($conn, $user_id, '[SIMULATOR] Node Reset', "Virtual Node #$light_id status restored to normal");
                $response['success'] = true;
            }
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$virtual_nodes = $conn->query("SELECT * FROM streetlights WHERE is_virtual = 1 ORDER BY light_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Developer Lab - ShineGuard Hulo</title>
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root { 
            --theme-color: <?php echo $theme_color; ?>;
            --glass: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
        }

        body { font-family: var(--font-main); }
        .main-content { padding: 2.2rem; background: #f8fafc; min-height: 100vh; }
        
        .lab-header { margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; }
        .lab-header h1 { font-size: 2.2rem; font-weight: 800; margin: 0; color: #0f172a; letter-spacing: -0.04em; }
        .lab-header p { color: #64748b; margin: 8px 0 0; font-size: 1.1rem; }

        .lab-badge {
            background: #eff6ff; color: #3b82f6;
            padding: 6px 16px; border-radius: 99px;
            font-size: 12px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.05em; border: 1px solid rgba(59, 130, 246, 0.2);
            display: inline-block; margin-bottom: 12px;
        }

        .node-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .node-card {
            background: var(--glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .node-card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }

        .node-card.fault { border-color: rgba(239, 68, 68, 0.3); background: rgba(254, 242, 242, 0.8); }

        .status-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .status-title h3 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a; }
        .status-title span { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; }

        .status-dot {
            width: 12px; height: 12px; border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 12px var(--success);
        }
        .status-dot.Maintenance { background: var(--danger); box-shadow: 0 0 12px var(--danger); }
        .status-dot.Inactive { background: var(--warning); box-shadow: 0 0 12px var(--warning); }

        .sim-group { margin-bottom: 20px; }
        .sim-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; display: block; }

        .sim-controls { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .btn-sim {
            padding: 10px; border-radius: 12px; border: 1.5px solid #e2e8f0;
            background: white; color: #0f172a; font-weight: 700; font-size: 13px;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-sim:hover { border-color: var(--theme-color); background: #f8fafc; }
        .btn-sim.active { background: var(--theme-color); color: white; border-color: var(--theme-color); }
        .btn-sim.danger { color: var(--danger); }
        .btn-sim.danger:hover { background: #fef2f2; border-color: var(--danger); }

        .slider-container { display: flex; align-items: center; gap: 12px; }
        input[type="range"] { flex: 1; accent-color: var(--theme-color); }
        .slider-val { font-size: 14px; font-weight: 800; color: #0f172a; min-width: 40px; text-align: right; }

        .fault-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(239, 68, 68, 0.05); display: <?php echo $log['status'] === 'Maintenance' ? 'block' : 'none'; ?>;
            pointer-events: none;
        }

        .dark-mode .lab-header h1 { color: white; }
        .dark-mode .node-card { background: rgba(30, 41, 59, 0.7); border-color: rgba(255,255,255,0.05); color: white; }
        .dark-mode .status-title h3 { color: white; }
        .dark-mode .btn-sim { background: #1a2234; border-color: #334155; color: #cbd5e1; }
        .dark-mode .slider-val { color: white; }

    </style>
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark-mode' : ''; ?>">
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/header.php'; ?>

        <main class="main-content">
            <div class="lab-header">
                <div>
                    <div class="lab-badge">Testing Laboratory</div>
                    <h1>🧪 Hardware Simulator</h1>
                    <p>Simulate hardware states and audit response logic</p>
                </div>
            </div>

            <div class="node-grid">
                <?php while($node = $virtual_nodes->fetch_assoc()): ?>
                <div class="node-card <?php echo $node['status'] === 'Maintenance' ? 'fault' : ''; ?>" id="node-<?php echo $node['light_id']; ?>">
                    <div class="status-header">
                        <div class="status-title">
                            <h3><?php echo htmlspecialchars($node['node_name']); ?></h3>
                            <span>ID: #<?php echo $node['light_id']; ?> &bull; <?php echo htmlspecialchars($node['location']); ?></span>
                        </div>
                        <div class="status-dot <?php echo $node['status']; ?>" id="dot-<?php echo $node['light_id']; ?>"></div>
                    </div>

                    <div class="sim-group">
                        <span class="sim-label">Hardware Relay Status</span>
                        <div class="sim-controls">
                            <button class="btn-sim sim-btn-on <?php echo $node['power_state'] === 'ON' ? 'active' : ''; ?>" onclick="simulateAction(<?php echo $node['light_id']; ?>, 'toggle_power', {state: 'ON'})">☀️ ON</button>
                            <button class="btn-sim sim-btn-off <?php echo $node['power_state'] === 'OFF' ? 'active' : ''; ?>" onclick="simulateAction(<?php echo $node['light_id']; ?>, 'toggle_power', {state: 'OFF'})">🌑 OFF</button>
                        </div>
                    </div>

                    <div class="sim-group">
                        <span class="sim-label">Virtual Photocell (Lux Simulation)</span>
                        <div class="slider-container">
                            <input type="range" min="0" max="100" value="<?php echo $node['dimming_level']; ?>" 
                                   oninput="updateSliderLabel(<?php echo $node['light_id']; ?>, this.value)"
                                   onchange="simulateAction(<?php echo $node['light_id']; ?>, 'set_dimming', {level: this.value})">
                            <span class="slider-val" id="val-<?php echo $node['light_id']; ?>"><?php echo $node['dimming_level']; ?>%</span>
                        </div>
                    </div>

                    <div class="sim-group" style="margin-top: 30px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px;">
                        <span class="sim-label">Failure Injection</span>
                        <div class="sim-controls">
                            <button class="btn-sim danger" onclick="simulateAction(<?php echo $node['light_id']; ?>, 'trigger_fault')">💥 Inject Fault</button>
                            <button class="btn-sim" onclick="simulateAction(<?php echo $node['light_id']; ?>, 'reset_node')">🔄 Restore</button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <script>
    function updateSliderLabel(id, val) {
        document.getElementById('val-' + id).textContent = val + '%';
    }

    async function simulateAction(lightId, action, params = {}) {
        const formData = new FormData();
        formData.append('sim_action', action);
        formData.append('light_id', lightId);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');
        
        for (const [key, value] of Object.entries(params)) {
            formData.append(key, value);
        }

        try {
            const response = await fetch('dev_simulator.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                // Flash success state or reload if needed
                if (action === 'trigger_fault' || action === 'reset_node') {
                    location.reload(); // Hard reload to update statuses and Work Order indications
                } else {
                    // Soft UI update
                    const card = document.getElementById('node-' + lightId);
                    if (action === 'toggle_power') {
                        const btns = card.querySelectorAll('.sim-group:first-of-type .btn-sim');
                        btns.forEach(b => b.classList.remove('active'));
                        if (params.state === 'ON') card.querySelector('.sim-btn-on').classList.add('active');
                        else card.querySelector('.sim-btn-off').classList.add('active');
                    }
                }
            } else {
                showAppAlert('Simulation failed: ' + (data.error || 'Unknown error'), 'error', 'Lab Module Error');
            }
        } catch (e) {
            console.error('Sim error:', e);
            showAppAlert('Connection error in lab module. Please check your network and session status.', 'error', 'System Connection Failure');
        }
    }
    </script>
</body>
</html>
