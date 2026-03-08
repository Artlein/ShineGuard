<?php
require_once 'dbconnect.php';
requireLogin();

if (isset($_GET['proxy_stream']) && isset($_GET['cam'])) {
    $cam_id = intval($_GET['cam']);
    $stmt = $conn->prepare("SELECT stream_url FROM cameras WHERE camera_id = ? LIMIT 1");
    $stmt->bind_param("i", $cam_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || empty($row['stream_url'])) { http_response_code(404); exit(); }
    $url = $row['stream_url'];

    session_write_close();
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['ngrok-skip-browser-warning: true']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
        $lower = strtolower(trim($header));
        if (strpos($lower, 'content-type') === 0 ||
            strpos($lower, 'content-length') === 0 ||
            strpos($lower, 'boundary') !== false) {
            header(rtrim($header));
        }
        return strlen($header);
    });
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        echo $data;
        flush();
        ob_flush();
        return strlen($data);
    });
    curl_exec($ch);
    curl_close($ch);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $camera_id = intval($_POST['camera_id'] ?? 0);
    
    if ($action === 'update_settings' && $camera_id > 0) {
        $nvr_ip = $_POST['nvr_ip'];
        $nvr_port = intval($_POST['nvr_port']);
        $channel = intval($_POST['channel']);
        $username = $_POST['username'];
        $password = $_POST['password'];
        $stream_type = $_POST['stream_type'];
        $protocol = $_POST['protocol'];
        
        $stmt = $conn->prepare("UPDATE cameras SET nvr_ip = ?, nvr_port = ?, channel = ?, username = ?, password = ?, stream_type = ?, protocol = ? WHERE camera_id = ?");
        $stmt->bind_param("siissssi", $nvr_ip, $nvr_port, $channel, $username, $password, $stream_type, $protocol, $camera_id);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Camera Config', "Updated settings for Camera #$camera_id");
            header('Location: cctv.php?success=settings_updated');
            exit();
        }
    }

    if ($action === 'add_camera') {
        $camera_name = $_POST['new_camera_name'] ?? '';
        $location = $_POST['new_location'] ?? '';
        $nvr_ip = $_POST['new_nvr_ip'] ?? '';
        $nvr_port = intval($_POST['new_nvr_port'] ?? 554);
        $channel = intval($_POST['new_channel'] ?? 1);
        $username = $_POST['new_username'] ?? 'admin';
        $password = $_POST['new_password'] ?? '';
        $stream_type = $_POST['new_stream_type'] ?? 'main';
        $protocol = $_POST['new_protocol'] ?? 'rtsp';
        $status = $_POST['new_status'] ?? 'Online';

        if (!empty($camera_name) && !empty($location) && !empty($nvr_ip)) {
            $stmt = $conn->prepare("INSERT INTO cameras (camera_name, location, nvr_ip, nvr_port, channel, username, password, stream_type, protocol, status, installation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->bind_param("sssiisssss", $camera_name, $location, $nvr_ip, $nvr_port, $channel, $username, $password, $stream_type, $protocol, $status);
            
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Camera Added', "Added new camera: $camera_name");
                header('Location: cctv.php?success=camera_added');
                exit();
            }
        }
    }

    if ($action === 'delete_camera' && $camera_id > 0) {
        
        $conn->query("DELETE FROM camera_snapshots WHERE camera_id = $camera_id");
        $conn->query("DELETE FROM camera_events WHERE camera_id = $camera_id");
        $conn->query("DELETE FROM cctv_footage WHERE camera_id = $camera_id");
        
        $stmt = $conn->prepare("DELETE FROM cameras WHERE camera_id = ?");
        $stmt->bind_param("i", $camera_id);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Camera Deleted', "Deleted camera ID: $camera_id");
            header('Location: cctv.php?success=camera_deleted');
            exit();
        }
    }
}

$cameras_query = "SELECT * FROM cameras ORDER BY camera_id";
$cameras_result = $conn->query($cameras_query);

if (!$cameras_result) {
    die("Query failed: " . $conn->error);
}

$snapshots_count = $conn->query("SELECT COUNT(*) as count FROM camera_snapshots WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'] ?? 0;

$system_name = 'Shine Guard Hulo';
$organization_name = 'Barangay Hulo';
$config_result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('system_name', 'organization_name')");
if ($config_result) {
    while ($row = $config_result->fetch_assoc()) {
        if ($row['config_key'] == 'system_name') $system_name = $row['config_value'];
        if ($row['config_key'] == 'organization_name') $organization_name = $row['config_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>CCTV Cameras - Shine Guard Hulo</title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">
<style>

html, body {
    margin: 0 !important; padding: 0 !important;
    height: 100%; overflow-x: hidden;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
}

<?php include 'assets/style.css'; ?>

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white; border-radius: 16px;
    padding: 24px; border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    display: flex; align-items: center; gap: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative; overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px); 
    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
    border-color: #cbd5e1;
}

.stat-icon {
    font-size: 32px; flex-shrink: 0;
}

.stat-label { font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-value { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1; }

.panel {
    background: white; border: 1px solid #e2e8f0; border-radius: 16px;
    padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 2rem;
}

.panel h2 {
    font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 16px;
}

.badge {
    padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;
}
.badge.online { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.badge.offline { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.btn {
    padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 700;
    border: 1px solid #cbd5e1; background: white; cursor: pointer;
    transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    text-decoration: none; color: #475569;
}
.btn:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; transform: translateY(-1px); }
.btn.primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white; border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
}
.btn.primary:hover { 
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .main-content { margin-left: 0 !important; width: 100% !important; }
    .sidebar { transform: translateX(-100%); }
}

.camera-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.camera-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 3px solid var(--border);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.camera-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    border-color: var(--accent);
}

.camera-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border);
}

.camera-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--text);
}

.camera-preview {
    width: 100%;
    height: 240px;
    background: #000;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.camera-placeholder {
    color: #666;
    text-align: center;
    padding: 20px;
}

.camera-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 15px;
    font-size: 13px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    color: var(--dim);
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 4px;
}

.info-value {
    color: var(--text);
    font-weight: 600;
}

.camera-controls {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.control-btn {
    flex: 1; min-width: 80px; padding: 10px 12px;
    border-radius: 8px; font-weight: 700; font-size: 13px;
    cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    color: white; border: none;
}

.control-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

.btn-play { background: linear-gradient(135deg, #10b981, #059669); }
.btn-snap { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.btn-settings { background: linear-gradient(135deg, #64748b, #475569); }
.btn-fullscreen { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }

.camera-card.fullscreen {
    position: fixed;
    top: 80px; 
    left: 280px; 
    right: 0; 
    bottom: 0;
    width: calc(100vw - 280px); 
    height: calc(100vh - 80px);
    z-index: 999;
    background: #ffffff;
    margin: 0; border-radius: 0; border: none;
    display: flex; flex-direction: column;
    padding: 24px; box-sizing: border-box;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .camera-card.fullscreen {
        left: 0;
        width: 100vw;
    }
}

.camera-card.fullscreen .camera-preview {
    flex: 1; height: auto; min-height: 50vh; margin-bottom: 20px;
}
.camera-card.fullscreen .camera-preview img {
    object-fit: contain !important;
    background: #000;
}

.settings-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.settings-modal.open {
    display: flex !important;
}

.settings-content {
    background: white;
    border-radius: 16px;
    padding: 30px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border);
}

.settings-header h2 {
    margin: 0;
    color: var(--text);
}

.close-btn {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--dim);
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover {
    color: var(--text);
}

.form-section {
    margin-bottom: 24px;
}

.form-section h3 {
    font-size: 14px;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 12px;
    text-transform: uppercase;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 6px;
}

.form-group input,
.form-group select {
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    background: #f8fafc;
    color: #0f172a;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    background: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.form-group input::placeholder {
    color: #94a3b8;
}
</style>
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>

<main class="main-content">

    <div class="page-header" style="text-align: center; padding-bottom: 2rem;">
        <h1>📹 CCTV Camera Monitoring</h1>
        <p style="margin-top: 5px;">Live feed and surveillance management for cameras across Barangay Hulo</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📹</div>
            <div class="stat-info">
                <div class="stat-label">Total Cameras</div>
                <div class="stat-value"><?php echo $cameras_result->num_rows; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-label">Online Cameras</div>
                <div class="stat-value"><?php 
                    $cameras_result->data_seek(0);
                    $online = 0;
                    while($cam = $cameras_result->fetch_assoc()) {
                        if($cam['status'] === 'Online') $online++;
                    }
                    echo $online;
                ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📸</div>
            <div class="stat-info">
                <div class="stat-label">Snapshots (24h)</div>
                <div class="stat-value" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                    <span><?php echo $snapshots_count; ?></span>
                    <button onclick="openGalleryModal()" style="background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe; border-radius:6px; padding:4px 10px; font-size:12px; font-weight:600; cursor:pointer;" onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#eff6ff'">View Gallery</button>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔴</div>
            <div class="stat-info">
                <div class="stat-label">Recording</div>
                <div class="stat-value" style="color: #ef4444;">Active</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">📹 Camera Feeds</h2>
            <button class="btn primary" onclick="openAddCameraModal()" style="padding: 10px 18px; font-size: 14px;">
                ➕ Add Camera
            </button>
        </div>
        <div class="camera-grid">
            <?php 
            $cameras_result->data_seek(0);
            while($camera = $cameras_result->fetch_assoc()): 
            ?>
            <div class="camera-card" id="camera-card-<?php echo $camera['camera_id']; ?>">
                <div class="camera-header">
                    <div>
                        <div class="camera-title"><?php echo htmlspecialchars($camera['camera_name']); ?></div>
                        <div style="font-size: 12px; color: var(--dim); margin-top: 4px;">
                            <?php echo htmlspecialchars($camera['location']); ?>
                        </div>
                    </div>
                    <span class="badge <?php echo strtolower($camera['status']); ?>">
                        <?php echo $camera['status']; ?>
                    </span>
                </div>

                <div class="camera-preview" id="preview-<?php echo $camera['camera_id']; ?>">
                    <?php if (!empty($camera['stream_url'])): ?>
                    <div class="camera-placeholder" id="placeholder-<?php echo $camera['camera_id']; ?>">
                        <i style="font-size: 48px;">📹</i>
                        <div>Click Play to view live feed</div>
                        <small style="color: #6ee7b7; margin-top: 8px; display: block; font-weight: 600;">
                            🟢 ESP32-CAM · ngrok stream
                        </small>
                    </div>
                    <?php else: ?>
                    <div class="camera-placeholder">
                        <i style="font-size: 48px;">📹</i>
                        <div>Click Play to start stream</div>
                        <small style="color: #999; margin-top: 8px; display: block;">
                            <?php echo $camera['nvr_ip'] ? "NVR: {$camera['nvr_ip']}:{$camera['nvr_port']}" : "Not configured"; ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="camera-info">
                    <div class="info-item">
                        <span class="info-label">RESOLUTION</span>
                        <span class="info-value"><?php echo $camera['resolution'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">FPS</span>
                        <span class="info-value"><?php echo $camera['fps'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">CHANNEL</span>
                        <span class="info-value">Ch <?php echo $camera['channel']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">STREAM</span>
                        <span class="info-value"><?php echo strtoupper($camera['stream_type']); ?></span>
                    </div>
                </div>

                <div class="camera-controls">
                    <?php if (!empty($camera['stream_url'])): ?>
                    <button class="control-btn btn-play" onclick="toggleStream(<?php echo $camera['camera_id']; ?>, this)">
                        ▶️ Play
                    </button>
                    <?php else: ?>
                    <button class="control-btn btn-play" onclick="openPlayModal('<?php echo addslashes($camera['camera_name']); ?>')">
                        ▶️ Play
                    </button>
                    <?php endif; ?>
                    <button class="control-btn btn-snap" onclick="takeSnapshot(<?php echo $camera['camera_id']; ?>, '<?php echo addslashes($camera['camera_name']); ?>', this)">
                        📸 Snapshot
                    </button>
                    <button class="control-btn btn-settings" onclick="openSettings(<?php echo $camera['camera_id']; ?>, <?php echo htmlspecialchars(json_encode($camera)); ?>)">
                        ⚙️ Settings
                    </button>
                    <button class="control-btn btn-fullscreen" onclick="toggleFullscreen(<?php echo $camera['camera_id']; ?>, this)">
                        ⛶ Maximize
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>
</div>

<div id="settingsModal" class="settings-modal">
    <div class="settings-content">
        <div class="settings-header">
            <h2>⚙️ Camera Settings</h2>
            <button class="close-btn" onclick="closeSettings()">&times;</button>
        </div>
        
        <form method="POST" id="settingsForm">
            <input type="hidden" name="action" value="update_settings">
            <input type="hidden" name="camera_id" id="settings_camera_id">
            
            <div class="form-section">
                <h3>🖥️ NVR Configuration</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>NVR IP Address</label>
                        <input type="text" name="nvr_ip" id="nvr_ip" placeholder="192.168.1.64" required>
                    </div>
                    <div class="form-group">
                        <label>RTSP Port</label>
                        <input type="number" name="nvr_port" id="nvr_port" value="554" required>
                    </div>
                    <div class="form-group">
                        <label>Channel Number</label>
                        <input type="number" name="channel" id="channel" min="1" max="32" required>
                    </div>
                    <div class="form-group">
                        <label>Protocol</label>
                        <select name="protocol" id="protocol">
                            <option value="rtsp">RTSP</option>
                            <option value="http">HTTP</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>🔐 Authentication</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="username" placeholder="admin" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>📹 Stream Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Stream Type</label>
                        <select name="stream_type" id="stream_type">
                            <option value="main">Main Stream (High Quality)</option>
                            <option value="sub">Sub Stream (Lower Bandwidth)</option>
                        </select>
                    </div>
                </div>
                <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-top: 12px; font-size: 13px;">
                    <strong>💡 Tip:</strong> Use Sub Stream for remote viewing to save bandwidth. Main Stream for recording.
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn" style="background: #fee2e2; color: #dc2626; border-color: #fca5a5;" onclick="openDeleteCameraModal()">
                    🗑️ Remove Camera
                </button>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn" onclick="closeSettings()">Cancel</button>
                    <button type="submit" class="btn primary">💾 Save Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="addCameraModal" class="settings-modal" onclick="if(event.target===this) closeAddCameraModal()">
    <div class="settings-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 20px; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">➕ Add New Camera</h2>
            <button type="button" onclick="closeAddCameraModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">×</button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_camera">
            
            <div class="form-section">
                <h3>📟 Camera Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Camera Name <span style="color: red;">*</span></label>
                        <input type="text" name="new_camera_name" placeholder="e.g. CAM-005" required>
                    </div>
                    <div class="form-group">
                        <label>Location / Assignment <span style="color: red;">*</span></label>
                        <input type="text" name="new_location" placeholder="e.g. Barangay Hall Back" required>
                    </div>
                    <div class="form-group">
                        <label>NVR IP Address <span style="color: red;">*</span></label>
                        <input type="text" name="new_nvr_ip" placeholder="192.168.1.x" required>
                    </div>
                    <div class="form-group">
                        <label>Channel Number</label>
                        <input type="number" name="new_channel" value="1" min="1">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>🔐 Connection Info</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="new_username" value="admin">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="new_password" placeholder="Leave empty if none">
                    </div>
                    <div class="form-group">
                        <label>RTSP Port</label>
                        <input type="number" name="new_nvr_port" value="554">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="new_status">
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn" onclick="closeAddCameraModal()">Cancel</button>
                <button type="submit" class="btn primary">➕ Add Camera</button>
            </div>
        </form>
    </div>
</div>

<div id="playModal" class="settings-modal" onclick="if(event.target===this) closeCCTVModal('playModal')">
    <div class="modal-spring settings-content" style="max-width: 420px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #d1fae5; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">▶️</div>
            <div>
                <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Live Stream</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="playModalCamName">Camera Name</div>
            </div>
        </div>
        <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
            The system is now connecting to the NVR RTSP stream. The live feed will be displayed in the camera preview window shortly.
        </p>
        <div style="display: flex; justify-content: flex-end;">
            <button onclick="closeCCTVModal('playModal')" class="btn primary" style="padding: 10px 24px;">Got it</button>
        </div>
    </div>
</div>

<div id="snapshotModal" class="settings-modal" onclick="if(event.target===this) closeCCTVModal('snapshotModal')">
    <div class="modal-spring settings-content" style="max-width: 420px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #eff6ff; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">📸</div>
            <div>
                <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Capture Snapshot</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="snapshotModalCamName">Camera Name</div>
            </div>
        </div>
        <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
            The current frame has been successfully captured and saved to the snapshot gallery. You can view it in the reports section.
        </p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button onclick="openGalleryModal(); closeCCTVModal('snapshotModal')" class="btn" style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;">View Gallery</button>
            <button onclick="closeCCTVModal('snapshotModal')" class="btn primary" style="background: #3b82f6; border: none;">Close</button>
        </div>
    </div>
</div>

<div id="snapshotGalleryModal" class="settings-modal" onclick="if(event.target===this) closeCCTVModal('snapshotGalleryModal')">
    <div class="modal-spring settings-content" style="max-width: 900px; width: 90%; max-height: 90vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #f5f3ff; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">🔐</div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Secure Snapshot Gallery</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">End-to-End Encrypted Storage</div>
                </div>
            </div>
            <button class="close-btn" onclick="closeCCTVModal('snapshotGalleryModal')">&times;</button>
        </div>

        <div id="galleryAuthGate" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; text-align: center; margin: auto; width: 100%; max-width: 400px;">
            <span style="font-size: 32px; display: block; margin-bottom: 12px;">🔒</span>
            <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 8px;">Authentication Required</h3>
            <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 20px;">
                These images are encrypted on disk. Please enter your administrator password to unlock and decrypt the gallery.
            </p>
            <div style="margin-bottom: 20px; text-align: left;">
                <label for="galleryAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">Administrator Password</label>
                <input type="password" id="galleryAdminPassword" placeholder="Enter password to decrypt" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139,92,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                <div id="galleryPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
            </div>
            <button id="galleryUnlockBtn" onclick="verifyGalleryPassword()" class="btn primary" style="width: 100%; background: #8b5cf6; border: none;">Unlock Gallery</button>
        </div>

        <div id="galleryContent" style="display: none; flex-grow: 1; overflow-y: auto; padding-right: 10px;">
            <div id="galleryGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                
            </div>
            <div id="galleryLoading" style="text-align: center; padding: 40px; color: #64748b; font-size: 0.9rem;">
                Loading encrypted snapshots...
            </div>
        </div>
    </div>
</div>

<div id="deleteCameraConfirmModal" class="settings-modal" onclick="if(event.target===this) closeCCTVModal('deleteCameraConfirmModal')">
    <div class="modal-spring settings-content" style="max-width: 420px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #fef2f2; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">🗑️</div>
            <div>
                <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Remove Camera?</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="deleteModalCamName">Camera ID</div>
            </div>
        </div>
        <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
            Are you sure you want to completely remove this camera and its footage history from the system? <strong>This action cannot be undone.</strong>
        </p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="closeCCTVModal('deleteCameraConfirmModal')" class="btn">Cancel</button>
            <button onclick="confirmDeleteCamera()" class="btn" style="background: #ef4444; color: white; border: none; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">🗑️ Yes, Remove</button>
        </div>
    </div>
</div>

<form id="deleteCameraForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete_camera">
    <input type="hidden" name="camera_id" id="delete_camera_id">
</form>

<script>
let currentCameraId = null;
let activeStreams = {};

function toggleStream(camId, btn) {
    const preview = document.getElementById('preview-' + camId);
    const placeholder = document.getElementById('placeholder-' + camId);

    if (activeStreams[camId]) {

        preview.querySelector('img')?.remove();
        placeholder.style.display = '';
        btn.textContent = '▶️ Play';
        activeStreams[camId] = false;
    } else {

        placeholder.style.display = 'none';
        const img = document.createElement('img');
        img.src = `cctv.php?proxy_stream=1&cam=${camId}&t=${Date.now()}`;
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:8px;';
        img.onerror = () => {
            img.remove();
            placeholder.style.display = '';
            placeholder.innerHTML = '<i style="font-size:48px;">⚠️</i><div style="color:#ef4444;font-weight:700;">Stream error<br><small>Check ESP32 power & ngrok</small></div>';
            btn.textContent = '▶️ Retry';
            activeStreams[camId] = false;
        };
        preview.appendChild(img);
        btn.textContent = '⏹ Stop';
        activeStreams[camId] = true;
    }
}

function takeSnapshot(camId, camName, btn) {
    const preview = document.getElementById('preview-' + camId);
    const img = preview.querySelector('img');
    
    if (!img) {
        showAppAlert('Please start the stream first to capture a snapshot.', 'warning');
        return;
    }

    if (!img.naturalWidth) {
        showAppAlert('Stream is still loading. Please wait a moment.', 'warning');
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Saving...';
    btn.disabled = true;
    
    const canvas = document.createElement('canvas');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    
    canvas.toBlob(blob => {
        const formData = new FormData();
        formData.append('camera_id', camId);
        formData.append('snapshot_image', blob, 'snapshot.jpg');
        
        fetch('api/api_save_snapshot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if (data.success) {
                document.getElementById('snapshotModalCamName').textContent = camName;
                openCCTVModal('snapshotModal');
            } else {
                showAppAlert('Failed to save snapshot: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error saving snapshot:', error);
            btn.innerHTML = originalText;
            btn.disabled = false;
            showAppAlert('Failed to connect to the server.', 'error');
        });
    }, 'image/jpeg', 0.9);
}

function openAddCameraModal() {
    document.getElementById('addCameraModal').classList.add('open');
}

function closeAddCameraModal() {
    document.getElementById('addCameraModal').classList.remove('open');
}

function deleteCamera() {
    if(currentCameraId) {
        document.getElementById('delete_camera_id').value = currentCameraId;
        document.getElementById('deleteCameraForm').submit();
    }
}

function toggleFullscreen(camId, btn) {
    const card = document.getElementById('camera-card-' + camId);
    if (!card) return;
    
    if (card.classList.contains('fullscreen')) {
        card.classList.remove('fullscreen');
        btn.innerHTML = '⛶ Maximize';
        document.body.style.overflow = '';
    } else {
        card.classList.add('fullscreen');
        btn.innerHTML = '🗕 Minimize';
        document.body.style.overflow = 'hidden';
    }
}

function openGalleryModal() {
    openCCTVModal('snapshotGalleryModal');
    document.getElementById('galleryAuthGate').style.display = 'block';
    document.getElementById('galleryContent').style.display = 'none';
    document.getElementById('galleryAdminPassword').value = '';
    document.getElementById('galleryPasswordError').style.display = 'none';
    document.getElementById('galleryGrid').innerHTML = '';
}

async function verifyGalleryPassword() {
    const pwdInput = document.getElementById('galleryAdminPassword');
    const pwdError = document.getElementById('galleryPasswordError');
    const btn = document.getElementById('galleryUnlockBtn');
    
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

        const response = await fetch('streetlights.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        if (data.success) {

            document.getElementById('galleryAuthGate').style.display = 'none';
            document.getElementById('galleryContent').style.display = 'flex';
            document.getElementById('galleryContent').style.flexDirection = 'column';
            loadEncryptedSnapshots();
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = 'Unlock Gallery';
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
}

async function loadEncryptedSnapshots() {
    const grid = document.getElementById('galleryGrid');
    const loader = document.getElementById('galleryLoading');
    
    grid.innerHTML = '';
    loader.style.display = 'block';
    
    try {
        const response = await fetch('api/api_get_snapshots_list.php');
        const snapshots = await response.json();
        
        loader.style.display = 'none';
        
        if (snapshots.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 40px;">No snapshots captured yet.</div>';
            return;
        }
        
        snapshots.forEach(snap => {
            const card = document.createElement('div');
            card.style.cssText = `
                background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s;
            `;

            const img = document.createElement('img');
            img.src = `api/get_snapshot.php?id=${snap.snapshot_id}`;
            img.style.cssText = 'width: 100%; height: 180px; object-fit: cover; display: block; border-bottom: 1px solid #e2e8f0; cursor: pointer;';
            img.loading = "lazy";

            img.onclick = () => window.open(`api/get_snapshot.php?id=${snap.snapshot_id}`, '_blank');
            
            const info = document.createElement('div');
            info.style.cssText = 'padding: 12px 14px;';
            info.innerHTML = `
                <div style="font-weight: 700; font-size: 0.85rem; color: #0f172a; margin-bottom: 4px;">Camera ${snap.camera_id}</div>
                <div style="font-size: 0.75rem; color: #64748b;">📅 ${new Date(snap.created_at).toLocaleString()}</div>
            `;
            
            card.appendChild(img);
            card.appendChild(info);
            card.onmouseover = () => card.style.transform = 'translateY(-2px)';
            card.onmouseout = () => card.style.transform = 'translateY(0)';
            
            grid.appendChild(card);
        });
        
    } catch(err) {
        console.error(err);
        loader.textContent = 'Failed to load snapshots. Please try again.';
        loader.style.color = '#ef4444';
    }
}

function openPlayModal(camName) {
    document.getElementById('playModalCamName').textContent = camName;
    openCCTVModal('playModal');
}

function openDeleteCameraModal() {

    document.getElementById('deleteModalCamName').textContent = `Camera ID: #${currentCameraId}`;
    openCCTVModal('deleteCameraConfirmModal');
}

function confirmDeleteCamera() {
    deleteCamera();
}

function openCCTVModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('open');

    const content = modal.querySelector('.modal-spring');
    if (content) {
        content.classList.remove('modal-spring');
        void content.offsetWidth;
        content.classList.add('modal-spring');
    }
}

function closeCCTVModal(id) {
    document.getElementById(id).classList.remove('open');
}
console.log('Camera page loaded');

function openSettings(cameraId, cameraData) {
    console.log('Opening settings for camera:', cameraId);
    console.log('Camera data:', cameraData);

    currentCameraId = cameraId;
    document.getElementById('settings_camera_id').value = cameraId;

    document.getElementById('nvr_ip').value = cameraData.nvr_ip || '';
    document.getElementById('nvr_port').value = cameraData.nvr_port || 554;
    document.getElementById('channel').value = cameraData.channel || 1;
    document.getElementById('username').value = cameraData.username || 'admin';
    document.getElementById('password').value = cameraData.password || '';
    document.getElementById('stream_type').value = cameraData.stream_type || 'main';
    document.getElementById('protocol').value = cameraData.protocol || 'rtsp';

    const modal = document.getElementById('settingsModal');
    modal.classList.add('open');
    console.log('Modal opened');
}

function closeSettings() {
    console.log('Closing settings modal');
    const modal = document.getElementById('settingsModal');
    modal.classList.remove('open');
}

document.getElementById('settingsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSettings();
    }
});

console.log('Settings modal element:', document.getElementById('settingsModal'));
console.log('Settings form element:', document.getElementById('settingsForm'));
</script>

<?php include 'assets/app_alert.php'; ?>
</body>
</html>
<?php
$conn->close();
?>
