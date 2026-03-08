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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>IoT Node Dashboard - Shine Guard Hulo</title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">
<style>
<?php include 'assets/style.css'; ?>
:root { 
    --theme-color: <?php echo $theme_color; ?>; 

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

.panel h2 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.firebase-status {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}
.firebase-status.connected {
    background: #22c55e;
    box-shadow: 0 0 10px #22c55e;
}
.firebase-status.disconnected {
    background: #ef4444;
    box-shadow: 0 0 10px #ef4444;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.sensor-card {
    background: linear-gradient(135deg, var(--surface), var(--surface-2));
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow);
    padding: 24px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.sensor-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.sensor-value {
    font-size: 40px;
    font-weight: 800;
    color: var(--text-primary);
    margin: 12px 0;
    line-height: 1.1;
}
.sensor-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.control-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
  background: var(--surface-2);
  color: var(--text-secondary);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.65rem 1.6rem;
  cursor: pointer;
  transition: all .15s;
  white-space: nowrap;
}

.btn:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
  color: var(--text-primary);
}

.btn.primary {
  background: var(--blue);
  color: #fff;
  border: none;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
}

.btn.primary:hover {
  background: #2563eb;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.5);
  border: none;
}

.btn.danger {
  background: var(--red);
  color: #fff;
  border: none;
  box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4);
}

.btn.danger:hover {
  background: #dc2626;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.5);
  border: none;
}
.btn.active {
    border: 2px solid #22c55e;
    box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
    font-weight: 700;
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
        <h1>🔥 FIREBASE IOT DASHBOARD</h1>
        <p>Real-time monitoring of SG-NODE2 (ESP32) via Firebase</p>
    </div>

    <div class="panel">
        <h2>📊 Real-time Sensor Data</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="sensor-card">
                <div class="sensor-label">💡 LDR Data</div>
                <div class="sensor-value" id="ldrData">--</div>
                <small class="sensor-label">Raw Value</small>
            </div>
            
            <div class="sensor-card">
                <div class="sensor-label">🌡️ Temperature</div>
                <div class="sensor-value" id="temperature">--</div>
                <small class="sensor-label">°C</small>
            </div>
            
            <div class="sensor-card">
                <div class="sensor-label">⚡ Voltage</div>
                <div class="sensor-value" id="voltage">--</div>
                <small class="sensor-label">Volts</small>
            </div>
            
            <div class="sensor-card">
                <div class="sensor-label">💧 Humidity</div>
                <div class="sensor-value" id="humidity">--</div>
                <small class="sensor-label">%</small>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>🎛️ Light Control (SG-NODE2)</h2>

        <div style="background: var(--surface-2); padding: 16px; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--border);">
            <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--text-secondary);">
                Current Mode: <span id="currentMode" style="color: var(--text-primary); font-weight: 800;">AUTO</span>
            </h3>
            <div style="display: flex; gap: 8px;">
                <button onclick="confirmFirebaseCommand('setMode', 0)" class="btn" id="btnAuto" style="flex: 1;">
                    🤖 AUTO Mode
                </button>
                <button onclick="confirmFirebaseCommand('setMode', 1)" class="btn primary" id="btnForceOn" style="flex: 1;">
                    💡 FORCE ON Mode
                </button>
                <button onclick="confirmFirebaseCommand('setMode', 2)" class="btn danger" id="btnForceOff" style="flex: 1;">
                    🌙 FORCE OFF Mode
                </button>
            </div>
            <p style="font-size: 11px; color: var(--text-secondary); margin-top: 8px;">
                ⚠️ In AUTO mode, the device controls the light based on sensors. Use FORCE modes for manual control.
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div>
                <h3 style="font-size: 16px; margin-bottom: 12px;">Power Control</h3>
                <div id="commandStatus" style="display: none; padding: 8px; background: #fef3c7; border-radius: 8px; margin-bottom: 12px; font-size: 13px; color: #92400e;">
                    ⏳ Processing command, please wait...
                </div>
                <div class="control-buttons">
                    <button onclick="controlLight('ON', 100)" class="btn primary">
                        🌕 Full (100%)
                    </button>
                    <button onclick="controlLight('ON', 75)" class="btn">
                        🌔 High (75%)
                    </button>
                    <button onclick="controlLight('ON', 50)" class="btn">
                        � Medium (50%)
                    </button>
                    <button onclick="controlLight('ON', 30)" class="btn">
                        🌒 Low (30%)
                    </button>
                    <button onclick="confirmFirebaseCommand('controlLight', 'OFF', 0)" class="btn danger" style="margin-top: 8px; width: 100%;">
                        ⚫ Turn OFF
                    </button>
                </div>
                
                <div style="margin-top: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Custom Level: <span id="brightnessValue">75</span>%
                    </label>
                    <input type="range" id="brightnessSlider" min="0" max="100" value="70" 
                           style="width: 100%;" oninput="updateBrightnessDisplay(this.value)">
                    <button onclick="setBrightness()" class="btn" style="margin-top: 8px; width: 100%;">
                        Apply Brightness
                    </button>
                </div>
            </div>
            
            <div>
                <h3 style="font-size: 16px; margin-bottom: 12px;">Current Status</h3>
                <div class="sensor-card">
                    <div class="sensor-label">Light Status</div>
                    <div id="lightStatus" style="font-size: 24px; margin: 12px 0;">
                        <span class="badge">Checking...</span>
                    </div>
                    <div class="sensor-label">Brightness</div>
                    <div id="currentBrightness" style="font-size: 32px; font-weight: 800; margin: 8px 0;">--%</div>
                </div>
            </div>
            
        </div>
        
    </div>

    <div class="panel">
        <h2>💊 Health & Diagnostics</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 20px;">
            <div class="panel" style="background: var(--surface-2); border: 1px solid var(--border); box-shadow: none; margin-bottom: 0px; padding: 1.2rem;">
                <strong style="color: var(--text-secondary);">Lamp Status:</strong>
                <span class="badge" id="lampStatus">--</span>
            </div>
            <div class="panel" style="background: var(--surface-2); border: 1px solid var(--border); box-shadow: none; margin-bottom: 0px; padding: 1.2rem;">
                <strong style="color: var(--text-secondary);">Relay Status:</strong>
                <span class="badge" id="relayStatus">--</span>
            </div>
            <div class="panel" style="background: var(--surface-2); border: 1px solid var(--border); box-shadow: none; margin-bottom: 0px; padding: 1.2rem;">
                <strong style="color: var(--text-secondary);">Env. Temperature:</strong>
                <span class="badge" id="envTempStatus">--</span>
            </div>
            <div class="panel" style="background: var(--surface-2); border: 1px solid var(--border); box-shadow: none; margin-bottom: 0px; padding: 1.2rem;">
                <strong style="color: var(--text-secondary);">Env. Humidity:</strong>
                <span class="badge" id="envHumidityStatus">--</span>
            </div>
            <div class="panel" style="background: var(--surface-2); border: 1px solid var(--border); box-shadow: none; margin-bottom: 0px; padding: 1.2rem;">
                <strong style="color: var(--text-secondary);">Fault Counter:</strong>
                <span id="lampFaultCounter" style="font-weight: 600;">--</span>
            </div>
            <div class="panel" style="background: var(--surface-2); border: 1px solid var(--border); box-shadow: none; margin-bottom: 0px; padding: 1.2rem;">
                <strong style="color: var(--text-secondary);">Relay Toggles:</strong>
                <span id="relayToggleCount" style="font-weight: 600;">--</span>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>📝 Control Log</h2>
        <div id="controlLog" style="max-height: 300px; overflow-y: auto; background: var(--surface-2); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); font-family: monospace; font-size: 13px;">
            <div style="color: var(--text-muted);">Waiting for activity...</div>
        </div>
    </div>
       
    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="margin-bottom: 8px;">📡 Connection Status</h2>
                <p style="margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="firebase-status connected" id="firebaseStatus"></span>
                    <span id="statusText" style="font-weight: 600;">Connecting to Firebase...</span>
                </p>
                <p style="font-size: 13px; color: var(--text-secondary); margin-top: 6px;">
                    Node: <strong>SG-NODE2</strong> → MySQL: <strong>SL-001</strong>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button onclick="syncNow()" class="btn primary">🔄 Sync Now</button>
                <button onclick="refreshData()" class="btn">🔃 Refresh</button>
            </div>
        </div>
    </div>
</main>
</div>

<div id="securityModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div id="secModalIcon" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">💡</div>
      <div>
        <div id="secModalTitle" style="font-size:1.1rem; font-weight:800; color:#0f172a;">Confirm Action</div>
        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;">Firebase Control Command</div>
      </div>
    </div>
    <p id="secModalDesc" style="font-size:0.875rem; color:#475569; margin-bottom:24px; line-height:1.6;">Are you sure?</p>
    <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start; font-size: 0.85rem; color: #b45309;">
      <div style="font-size: 1.2rem; line-height: 1;">⏱️</div>
      <div><strong>Execution Delay:</strong> Please note there will be a 5-10 seconds delay for the command to fully execute on the physical nodes.</div>
    </div>
    
    <div style="margin-bottom: 24px;">
        <label for="secModalPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="secModalPassword" placeholder="Enter password to confirm" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="secModalError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeSecModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
      <button id="secModalConfirmBtn" onclick="confirmSecAction()" style="padding:10px 22px; border-radius:10px; border:none; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s; background:#3b82f6; box-shadow:0 4px 12px rgba(59,130,246,0.35);">Confirm</button>
    </div>
  </div>
</div>

<script type="module">

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
    const d = getDimmingLabel(value);
    document.getElementById('brightnessValue').textContent = `${value}% (${d.label})`;
}

function getDimmingLabel(level) {
    level = parseInt(level);
    if (level <= 30) return { label: 'Low',    icon: '🌒', color: '#3b82f6', bg: '#eff6ff' };
    if (level <= 50) return { label: 'Medium', icon: '🌓', color: '#8b5cf6', bg: '#f5f3ff' };
    if (level <= 75) return { label: 'High',   icon: '🌔', color: '#f59e0b', bg: '#fffbeb' };
    return                  { label: 'Full',   icon: '🌕', color: '#10b981', bg: '#ecfdf5' };
}

onValue(ref(db, NODE_BASE + "/Sensor"), (snapshot) => {
    const data = snapshot.val() || {};
    
    document.getElementById('ldrData').textContent = data.ldrData ?? '--';
    document.getElementById('voltage').textContent = data.voltage != null ? data.voltage.toFixed(3) : '--';
    document.getElementById('temperature').textContent = data.temperature != null ? data.temperature.toFixed(1) : '--';
    document.getElementById('humidity').textContent = data.humidity != null ? data.humidity.toFixed(1) : '--';
    
    document.getElementById('firebaseStatus').className = 'firebase-status connected';
    document.getElementById('statusText').textContent = 'Connected to Firebase';
}, (error) => {
    console.error("Sensor listener error:", error);
    document.getElementById('firebaseStatus').className = 'firebase-status disconnected';
    document.getElementById('statusText').textContent = 'Connection Error';
});

onValue(ref(db, NODE_BASE + "/Actuator"), (snapshot) => {
    const data = snapshot.val() || {};
    
    const isOn = !!data.lightOn;
    const brightness = data.brightnessPercent || 0;
    
    document.getElementById('lightStatus').innerHTML = 
        `<span class="badge ${isOn ? 'ok' : 'fail'}">${isOn ? 'ON' : 'OFF'}</span>`;
    
    const d = getDimmingLabel(brightness);
    document.getElementById('currentBrightness').innerHTML = 
        `<span style="color: ${d.color}">${d.icon} ${d.label}</span> <small style="font-size: 14px; color: #94a3b8; font-weight: 400;">(${brightness}%)</small>`;

    const slider = document.getElementById('brightnessSlider');
    if (slider && slider.value != brightness) {
        slider.value = brightness;
        document.getElementById('brightnessValue').textContent = brightness;
    }
}, (error) => {
    console.error("Actuator listener error:", error);
});

onValue(ref(db, NODE_BASE + "/Control/mode"), (snapshot) => {
    const mode = snapshot.val() ?? 0;
    
    let modeText = "AUTO";
    if (mode === 1) modeText = "FORCE ON";
    if (mode === 2) modeText = "FORCE OFF";
    
    document.getElementById('currentMode').textContent = modeText;

    document.getElementById('btnAuto').classList.remove('active');
    document.getElementById('btnForceOn').classList.remove('active');
    document.getElementById('btnForceOff').classList.remove('active');
    
    if (mode === 0) document.getElementById('btnAuto').classList.add('active');
    if (mode === 1) document.getElementById('btnForceOn').classList.add('active');
    if (mode === 2) document.getElementById('btnForceOff').classList.add('active');
}, (error) => {
    console.error("Control mode listener error:", error);
});

onValue(ref(db, NODE_BASE + "/Health"), (snapshot) => {
    const data = snapshot.val() || {};
    
    const statusClass = (status) => {
        if (!status || status === 'OK') return 'ok';
        if (status === 'Warning' || status === 'HighTempRisk' || status === 'MoistureRisk' || status === 'Overused') return 'warn';
        return 'fail';
    };
    
    document.getElementById('lampStatus').textContent = data.lampStatus || 'OK';
    document.getElementById('lampStatus').className = 'badge ' + statusClass(data.lampStatus);
    
    document.getElementById('relayStatus').textContent = data.relayStatus || 'OK';
    document.getElementById('relayStatus').className = 'badge ' + statusClass(data.relayStatus);
    
    document.getElementById('envTempStatus').textContent = data.envTempStatus || 'OK';
    document.getElementById('envTempStatus').className = 'badge ' + statusClass(data.envTempStatus);
    
    document.getElementById('envHumidityStatus').textContent = data.envHumidityStatus || 'OK';
    document.getElementById('envHumidityStatus').className = 'badge ' + statusClass(data.envHumidityStatus);
    
    document.getElementById('lampFaultCounter').textContent = data.lampFaultCounter || 0;
    document.getElementById('relayToggleCount').textContent = data.relayToggleCount || 0;
}, (error) => {
    console.error("Health listener error:", error);
});

let controlInProgress = false;

window.controlLight = function(power, brightness) {
    if (controlInProgress) {
        addLog(`⚠️ Please wait, previous command still processing...`, 'warn');
        return;
    }
    
    controlInProgress = true;
    document.getElementById('commandStatus').style.display = 'block';
    addLog(`Sending command: ${power} at ${brightness}%`);

    const controlData = {
        mode: power === 'ON' ? 1 : 2,  // 1=FORCE_ON, 2=FORCE_OFF
        targetBrightness: parseInt(brightness),
        commandTimestamp: Date.now()
    };
    
    update(ref(db, NODE_BASE + '/Control'), controlData)
        .then(() => {
            addLog(`✓ Command sent: ${power} at ${brightness}%`, 'success');

            setTimeout(() => {
                controlInProgress = false;
                document.getElementById('commandStatus').style.display = 'none';
            }, 1500);
        })
        .catch((error) => {
            addLog(`✗ Error: ${error.message}`, 'error');
            console.error("Control error:", error);
            controlInProgress = false;
            document.getElementById('commandStatus').style.display = 'none';
        });
}

window.setBrightness = function() {
    const brightness = parseInt(document.getElementById('brightnessSlider').value);
    addLog(`Setting brightness to ${brightness}%`);
    
    set(ref(db, NODE_BASE + "/Actuator/brightnessPercent"), brightness)
        .then(() => {
            addLog(`✓ Brightness set to ${brightness}%`, 'success');
        })
        .catch((error) => {
            addLog(`✗ Error: ${error.message}`, 'error');
            console.error("Brightness control error:", error);
        });
}

window.setMode = function(modeInt) {
    if (controlInProgress) {
        addLog(`⚠️ Please wait, previous command still processing...`, 'warn');
        return;
    }
    
    const modeNames = ['AUTO', 'FORCE ON', 'FORCE OFF'];
    addLog(`Changing mode to ${modeNames[modeInt]}...`);
    
    controlInProgress = true;
    
    set(ref(db, NODE_BASE + '/Control/mode'), modeInt)
        .then(() => {
            addLog(`✓ Mode changed to ${modeNames[modeInt]}`, 'success');

            return new Promise(resolve => setTimeout(resolve, 800));
        })
        .then(() => {
            controlInProgress = false;
        })
        .catch((error) => {
            addLog(`✗ Error: ${error.message}`, 'error');
            console.error("Mode control error:", error);
            controlInProgress = false;
        });
}

window.syncNow = function() {
    addLog('Initiating Firebase → MySQL sync...');
    
    fetch('firebase_sync.php?run=1')
        .then(response => response.text())
        .then(data => {
            addLog('✓ Sync completed', 'success');
            console.log('Sync output:', data);
        })
        .catch(error => {
            addLog(`✗ Sync error: ${error.message}`, 'error');
        });
}

window.refreshData = function() {
    addLog('Data is auto-updating in real-time', 'success');
}

window.confirmFirebaseCommand = function(actionType, param1, param2) {
    const modal = document.getElementById('securityModal');
    const icon = document.getElementById('secModalIcon');
    const title = document.getElementById('secModalTitle');
    const desc = document.getElementById('secModalDesc');
    const btn = document.getElementById('secModalConfirmBtn');
    
    if (actionType === 'setMode' && param1 === 0) {
        icon.style.background = '#eff6ff';
        icon.textContent = '🤖';
        title.textContent = 'AUTO Mode';
        desc.textContent = 'Are you sure you want to return the streetlight to AUTO Mode? This will allow sensors to control the light automatically.';
        btn.style.background = '#3b82f6';
        btn.style.boxShadow = '0 4px 12px rgba(59,130,246,0.35)';
        btn.textContent = '🤖 Confirm AUTO';
    } else if (actionType === 'setMode' && param1 === 1) {
        icon.style.background = '#f0fdf4';
        icon.textContent = '💡';
        title.textContent = 'FORCE ON Mode';
        desc.textContent = 'Are you sure you want to force the streetlight ON? This will override automatic sensor controls.';
        btn.style.background = '#10b981';
        btn.style.boxShadow = '0 4px 12px rgba(16,185,129,0.35)';
        btn.textContent = '💡 Confirm ON';
    } else if (actionType === 'setMode' && param1 === 2) {
        icon.style.background = '#fef2f2';
        icon.textContent = '🌙';
        title.textContent = 'FORCE OFF Mode';
        desc.textContent = 'Are you sure you want to force the streetlight OFF? This will override automatic sensor controls.';
        btn.style.background = '#ef4444';
        btn.style.boxShadow = '0 4px 12px rgba(239,68,68,0.35)';
        btn.textContent = '🌙 Confirm OFF';
    } else if (actionType === 'controlLight' && param1 === 'OFF') {
        icon.style.background = '#fef2f2';
        icon.textContent = '⚫';
        title.textContent = 'Turn Light OFF';
        desc.textContent = 'Are you sure you want to turn the light OFF immediately?';
        btn.style.background = '#ef4444';
        btn.style.boxShadow = '0 4px 12px rgba(239,68,68,0.35)';
        btn.textContent = '⚫ Confirm OFF';
    }
    
    modal._actionType = actionType;
    modal._param1 = param1;
    modal._param2 = param2;
    modal.style.display = 'flex';
};

window.closeSecModal = function() {
    document.getElementById('securityModal').style.display = 'none';
    const pwdInput = document.getElementById('secModalPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('secModalError').style.display = 'none';
        document.getElementById('secModalError').textContent = 'Password is required';
        document.getElementById('secModalConfirmBtn').innerHTML = 'Confirm';
        document.getElementById('secModalConfirmBtn').disabled = false;
    }
};

window.confirmSecAction = async function() {
    const pwdInput = document.getElementById('secModalPassword');
    const pwdError = document.getElementById('secModalError');
    const modal = document.getElementById('securityModal');
    const btn = document.getElementById('secModalConfirmBtn');
    
    if (!pwdInput.value.trim()) {
        pwdError.textContent = 'Password is required';
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }
    
    pwdError.style.display = 'none';
    btn.innerHTML = 'Verifying...';
    btn.disabled = true;

    try {
        const formData = new URLSearchParams();
        formData.append('action', 'verify_password');
        formData.append('admin_password', pwdInput.value);
        
        const response = await fetch('firebase_dashboard.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
        
        const data = await response.json();
        if (data.success) {
            closeSecModal();

            if (modal._actionType === 'setMode') {
                setMode(modal._param1);
            } else if (modal._actionType === 'controlLight') {
                controlLight(modal._param1, modal._param2);
            }
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = btn.textContent.replace('Verifying...', 'Confirm'); // revert text if needed, or simply hardcode:
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
};

function addLog(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    let color = '#64748b'; // default info color
    
    if (type === 'success') color = '#22c55e';
    else if (type === 'error') color = '#ef4444';
    else if (type === 'warn') color = '#f59e0b';
    
    logEntries.unshift(`<div style="color: ${color};">[${timestamp}] ${message}</div>`);
    
    if (logEntries.length > 50) {
        logEntries = logEntries.slice(0, 50);
    }
    
    document.getElementById('controlLog').innerHTML = logEntries.join('');
}

addLog('🔥 Firebase real-time connection established', 'success');
</script>
</body>
</html>