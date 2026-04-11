<?php
require_once 'dbconnect.php';
requireLogin(['System Admin', 'Maintenance Operator']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_password') {
    checkCsrf();
    ob_clean(); 
    header('Content-Type: application/json');
    $admin_password = $_POST['admin_password'] ?? '';
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if ($user_data && password_verify($admin_password, $user_data['password_hash'])) {
        setAuthorized();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

$theme_color = '#10b981';
$tc_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
if ($tc_result && $tc_row = $tc_result->fetch_assoc()) {
    $theme_color = $tc_row['config_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>IoT Intelligence - Shine Guard Hulo</title>
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">
    <style>
        <?php include 'assets/style.css'; ?>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root { 
            --theme-color: <?php echo $theme_color; ?>;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body { font-family: var(--font-main); background: #f8fafc; color: #0f172a; }
        .main-content { padding: 2rem; }

        /* Multi-Column Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            grid-template-areas: 
                "stats stats"
                "monitor controls"
                "health activity";
            gap: 24px;
            margin-top: 20px;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "stats"
                    "monitor"
                    "controls"
                    "health"
                    "activity";
            }
        }

        /* Glass Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover { transform: translateY(-4px); box-shadow: 0 12px 48px rgba(0,0,0,0.08); }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px; }

        /* Stats Bar */
        .stats-bar { grid-area: stats; display: flex; gap: 20px; align-items: center; justify-content: space-between; padding: 20px 32px; }
        .system-status { display: flex; align-items: center; gap: 12px; }
        .status-pill { background: #ecfdf5; color: #10b981; padding: 6px 16px; border-radius: 99px; font-size: 13px; font-weight: 700; border: 1px solid rgba(16,185,129,0.2); }

        /* Monitoring Section */
        .monitoring-section { grid-area: monitor; }
        .sensor-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .sensor-item { background: white; padding: 24px; border-radius: 20px; text-align: center; border: 1px solid #f1f5f9; position: relative; }
        .sensor-item .val { font-size: 2.2rem; font-weight: 800; color: #0f172a; margin: 10px 0; }
        .sensor-item .lbl { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .sensor-item .unit { font-size: 14px; color: #94a3b8; font-weight: 600; margin-left: 4px; }

        /* Controls Section */
        .controls-section { grid-area: controls; }
        .mode-toggle { display: grid; grid-template-columns: 1fr; gap: 8px; margin-bottom: 24px; }
        .btn-mode { 
            background: white; border: 1.5px solid #e2e8f0; padding: 14px; border-radius: 16px; 
            font-weight: 700; color: #475569; position: relative; transition: all 0.2s;
        }
        .btn-mode.active { background: var(--theme-color); color: white; border-color: var(--theme-color); box-shadow: 0 8px 20px rgba(59,130,246,0.2); }
        .btn-mode span { display: block; font-size: 10px; opacity: 0.8; font-weight: 600; margin-top: 2px; }

        /* Intensity Control */
        .intensity-slider-box { background: #f8fafc; padding: 20px; border-radius: 20px; border: 1px solid #e2e8f0; }
        .intensity-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .intensity-val { font-weight: 800; color: var(--theme-color); font-size: 1.2rem; }

        /* Health Area */
        .health-section { grid-area: health; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .health-item { background: white; padding: 20px; border-radius: 20px; display: flex; align-items: center; gap: 16px; border: 1px solid #f1f5f9; }
        .health-ico { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .health-info strong { display: block; font-size: 13px; color: #64748b; font-weight: 700; text-transform: uppercase; }
        .health-info span { font-weight: 800; font-size: 1rem; color: #0f172a; }

        /* Activity Feed */
        .activity-section { grid-area: activity; }
        .activity-feed { max-height: 480px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
        .feed-item { padding: 12px 16px; border-radius: 14px; background: white; border-left: 4px solid #cbd5e1; font-size: 13px; line-height: 1.5; }
        .feed-item .time { color: #94a3b8; font-size: 11px; font-weight: 700; margin-bottom: 4px; }
        .feed-item.success { border-left-color: #10b981; }
        .feed-item.error { border-left-color: #ef4444; }

        /* Badge Styles */
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; }
        .badge.ok { background: #ecfdf5; color: #059669; }
        .badge.warn { background: #fffbeb; color: #d97706; }
        .badge.fail { background: #fef2f2; color: #dc2626; }

        .dark-mode body { background: #0f172a; }
        .dark-mode .glass-card { background: rgba(30, 41, 59, 0.7); border-color: rgba(255,255,255,0.05); }
        .dark-mode .card-header h2, .dark-mode .sensor-item .val { color: white; }
        .dark-mode .sensor-item, .dark-mode .btn-mode, .dark-mode .health-item, .dark-mode .feed-item { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark-mode .intensity-slider-box { background: #111827; border-color: #334155; }
        .dark-mode .health-info span { color: white; }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark-mode' : ''; ?>">
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/header.php'; ?>

        <main class="main-content">
            <div class="glass-card stats-bar">
                <div class="system-status">
                    <div class="status-pill" id="statusPill">📡 CONNECTING...</div>
                    <div>
                        <h2 style="font-size: 1.2rem; margin: 0;">SG-NODE2 Intelligent Sync</h2>
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Real-time ESP32 Controller</span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button onclick="syncNow()" class="btn-sim primary" style="padding: 10px 20px; font-size: 13px;">🔄 Sync MySQL</button>
                    <button onclick="refreshData()" class="btn-sim" style="padding: 10px 20px; font-size: 13px;">🔃 Refresh</button>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Monitoring Area -->
                <div class="glass-card monitoring-section">
                    <div class="card-header">
                        <h2>📊 Live Telemetry</h2>
                        <span id="lastUpdated" style="font-size: 11px; color: #94a3b8; font-weight: 600;">UPDATING...</span>
                    </div>
                    <div class="sensor-grid">
                        <div class="sensor-item">
                            <div class="lbl">💡 Ambient Light</div>
                            <div class="val" id="ldrData">--</div>
                            <small class="lbl">Raw Photocell</small>
                        </div>
                        <div class="sensor-item">
                            <div class="lbl">🌡️ Temperature</div>
                            <div class="val"><span id="temperature">--</span><span class="unit">°C</span></div>
                            <small class="lbl">Precision Core</small>
                        </div>
                        <div class="sensor-item">
                            <div class="lbl">⚡ Line Voltage</div>
                            <div class="val"><span id="voltage">--</span><span class="unit">V</span></div>
                            <small class="lbl">Hardware Potential</small>
                        </div>
                        <div class="sensor-item">
                            <div class="lbl">💧 Air Humidity</div>
                            <div class="val"><span id="humidity">--</span><span class="unit">%</span></div>
                            <small class="lbl">Relative Moisture</small>
                        </div>
                    </div>
                </div>

                <!-- Controls Area -->
                <div class="glass-card controls-section">
                    <div class="card-header">
                        <h2>🎛️ Command Center</h2>
                    </div>
                    <div class="mode-toggle">
                        <button class="btn-mode" id="btnAuto" onclick="confirmFirebaseCommand('setMode', 0)">
                            🤖 AUTO Mode
                            <span>Environmental Logic Active</span>
                        </button>
                        <button class="btn-mode" id="btnForceOn" onclick="confirmFirebaseCommand('setMode', 1)">
                            💡 FORCE ON
                            <span>Manual Override Active</span>
                        </button>
                        <button class="btn-mode" id="btnForceOff" onclick="confirmFirebaseCommand('setMode', 2)">
                            🌙 FORCE OFF
                            <span>Safety Override Active</span>
                        </button>
                    </div>

                    <div class="intensity-slider-box">
                        <div class="intensity-header">
                            <span class="lbl" style="color: #64748b; font-size: 11px;">LIGHT INTENSITY</span>
                            <span class="intensity-val" id="brightnessValue">70%</span>
                        </div>
                        <input type="range" id="brightnessSlider" min="0" max="100" value="70" 
                               style="width: 100%; accent-color: var(--theme-color);" oninput="updateBrightnessDisplay(this.value)"
                               onchange="setBrightness(this.value)">
                        <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                            <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">MIN</span>
                            <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">MAX</span>
                        </div>
                    </div>
                </div>

                <!-- Health Area -->
                <div class="health-section">
                    <div class="glass-card health-item">
                        <div class="health-ico" style="background: #f0fdf4; color: #10b981;">💡</div>
                        <div class="health-info">
                            <strong>Lamp Status</strong>
                            <span id="lampStatus">--</span>
                        </div>
                    </div>
                    <div class="glass-card health-item">
                        <div class="health-ico" style="background: #eff6ff; color: #3b82f6;">⚙️</div>
                        <div class="health-info">
                            <strong>Relay Switch</strong>
                            <span id="relayStatus">--</span>
                        </div>
                    </div>
                    <div class="glass-card health-item">
                        <div class="health-ico" style="background: #fffbeb; color: #f59e0b;">🌡️</div>
                        <div class="health-info">
                            <strong>Thermal Risk</strong>
                            <span id="envTempStatus">--</span>
                        </div>
                    </div>
                    <div class="glass-card health-item">
                        <div class="health-ico" style="background: #fef2f2; color: #ef4444;">🚨</div>
                        <div class="health-info">
                            <strong>Faults</strong>
                            <span id="lampFaultCounter">0</span>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="glass-card activity-section">
                    <div class="card-header">
                        <h2>📝 Intelligence Feed</h2>
                    </div>
                    <div class="activity-feed" id="controlLog">
                        <div style="color: #94a3b8; font-size: 13px; text-align: center; padding: 20px;">Waiting for hardware events...</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Security Modal -->
    <div id="securityModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.8); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center;">
        <div class="glass-card" style="max-width:400px; width:90%; background: white;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div id="secModalIcon" style="width:48px; height:48px; border-radius:14px; background: #fef3c7; display:flex; align-items:center; justify-content:center; font-size:22px;">⚖️</div>
                <div>
                    <div id="secModalTitle" style="font-size:1.1rem; font-weight:800; color: #0f172a;">Confirm Action</div>
                    <div style="font-size:0.8rem; color: #64748b;">Hardware Propagation Check</div>
                </div>
            </div>
            <p id="secModalDesc" style="font-size:0.875rem; color: #475569; margin-bottom:24px; line-height: 1.6;">Are you sure?</p>
            
            <div id="pwdGroup">
                <label style="display:block; font-size:0.875rem; font-weight:600; color: #0f172a; margin-bottom:8px;">🔐 Authorization Required</label>
                <input type="password" id="secModalPassword" placeholder="Admin password" style="width:100%; padding:12px; border-radius:12px; border:1.5px solid #e2e8f0; margin-bottom: 20px;">
            </div>
            <div id="secModalError" style="color:var(--danger); font-size:0.75rem; margin-top:-12px; margin-bottom: 12px; display:none;">Invalid password</div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button onclick="closeSecModal()" class="btn-sim" style="background: #f8fafc;">Cancel</button>
                <button id="secModalConfirmBtn" onclick="confirmSecAction()" class="btn-sim primary" style="background: var(--theme-color);">Confirm</button>
            </div>
        </div>
    </div>

    <?php $isAuthorized = isRecentlyAuthorized() ? 'true' : 'false'; ?>
    <script type="module">
        const isAuthorized = <?php echo $isAuthorized; ?>;

        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.2/firebase-app.js";
        import { getDatabase, ref, onValue, set, update } from "https://www.gstatic.com/firebasejs/9.22.2/firebase-database.js";

        const firebaseConfig = {
            apiKey: "AIzaSyBM69Xh5_d2lhiwGEi1gz9OfNHBEyEYrSQ",
            authDomain: "sg-hulo.firebaseapp.com",
            databaseURL: "https://sg-hulo-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "sg-hulo",
            storageBucket: "sg-hulo.firebasestorage.app",
            messagingSenderId: "1098036753407",
            appId: "1:1098036753407:web:a0b564a0c18d11e9a52dca"
        };

        const app = initializeApp(firebaseConfig);
        const db = getDatabase(app);
        const NODE_BASE = "SG-NODE2";

        let logEntries = [];

        window.updateBrightnessDisplay = function(value) {
            document.getElementById('brightnessValue').textContent = value + '%';
        }

        // Listeners
        onValue(ref(db, NODE_BASE + "/Sensor"), (snapshot) => {
            const data = snapshot.val() || {};
            document.getElementById('ldrData').textContent = data.ldrData ?? '--';
            document.getElementById('voltage').textContent = data.voltage != null ? data.voltage.toFixed(2) : '--';
            document.getElementById('temperature').textContent = data.temperature != null ? data.temperature.toFixed(1) : '--';
            document.getElementById('humidity').textContent = data.humidity != null ? data.humidity.toFixed(1) : '--';
            
            const pill = document.getElementById('statusPill');
            pill.textContent = '📡 ONLINE';
            pill.style.background = '#ecfdf5';
            pill.style.color = '#10b981';
            document.getElementById('lastUpdated').textContent = 'LIVE • ' + new Date().toLocaleTimeString();
        });

        onValue(ref(db, NODE_BASE + "/Control/mode"), (snapshot) => {
            const mode = snapshot.val() ?? 0;
            const btns = ['btnAuto', 'btnForceOn', 'btnForceOff'];
            btns.forEach((id, idx) => {
                document.getElementById(id).classList.toggle('active', mode === idx);
            });
        });

        onValue(ref(db, NODE_BASE + "/Actuator/brightnessPercent"), (snapshot) => {
            const val = snapshot.val() ?? 70;
            document.getElementById('brightnessSlider').value = val;
            document.getElementById('brightnessValue').textContent = val + '%';
        });

        onValue(ref(db, NODE_BASE + "/Health"), (snapshot) => {
            const data = snapshot.val() || {};
            const items = ['lampStatus', 'relayStatus', 'envTempStatus'];
            items.forEach(id => {
                const val = data[id] || 'OK';
                const el = document.getElementById(id);
                el.textContent = val;
                el.className = 'badge ' + (val === 'OK' ? 'ok' : 'fail');
            });
            document.getElementById('lampFaultCounter').textContent = data.lampFaultCounter || 0;
        });

        // Actions
        window.setMode = function(mode) {
            addLog(`Propagating mode change: ${mode}`, 'info');
            set(ref(db, NODE_BASE + '/Control/mode'), mode)
                .then(() => addLog('✓ Mode synchronized', 'success'))
                .catch(err => addLog('✗ Sync failed: ' + err.message, 'error'));
        }

        window.setBrightness = function(val) {
            val = parseInt(val);
            addLog(`Propagating brightness: ${val}%`, 'info');
            set(ref(db, NODE_BASE + "/Actuator/brightnessPercent"), val)
                .then(() => addLog('✓ Brightness synchronized', 'success'))
                .catch(err => addLog('✗ Sync failed: ' + err.message, 'error'));
        }

        window.syncNow = function() {
            addLog('Requesting Firebase → MySQL sync...', 'info');
            fetch('firebase_sync.php?run=1')
                .then(() => addLog('✓ Local database updated', 'success'))
                .catch(() => addLog('✗ Local sync failed', 'error'));
        }

        window.refreshData = function() {
            location.reload();
        }

        // Security Logic
        window.confirmFirebaseCommand = function(actionType, param1, param2) {
            const modal = document.getElementById('securityModal');
            document.getElementById('pwdGroup').style.display = isAuthorized ? 'none' : 'block';
            modal._actionType = actionType;
            modal._param1 = param1;
            modal.style.display = 'flex';
        }

        window.closeSecModal = function() {
            document.getElementById('securityModal').style.display = 'none';
        }

        window.confirmSecAction = async function() {
            const modal = document.getElementById('securityModal');
            if (!isAuthorized) {
                const pwd = document.getElementById('secModalPassword').value;
                const response = await fetch('firebase_dashboard.php', {
                    method: 'POST',
                    body: new URLSearchParams({action: 'verify_password', admin_password: pwd})
                });
                const data = await response.json();
                if (!data.success) {
                    document.getElementById('secModalError').style.display = 'block';
                    return;
                }
            }
            
            if (modal._actionType === 'setMode') setMode(modal._param1);
            closeSecModal();
        }

        function addLog(message, type = 'info') {
            const feed = document.getElementById('controlLog');
            const item = document.createElement('div');
            item.className = `feed-item ${type}`;
            item.innerHTML = `<div class="time">${new Date().toLocaleTimeString()}</div>${message}`;
            feed.prepend(item);
        }
    </script>
</body>
</html>