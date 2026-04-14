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
    if (!$row || empty($row['stream_url'])) {
        http_response_code(404);
        exit();
    }
    $url = $row['stream_url'];

    // Log the surveillance access
    logActivity($conn, $_SESSION['user_id'], 'CCTV Stream', "Started live stream for Camera #$cam_id");

    session_write_close();

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['ngrok-skip-browser-warning: true']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
        $lower = strtolower(trim($header));
        if (
            strpos($lower, 'content-type') === 0 ||
            strpos($lower, 'content-length') === 0 ||
            strpos($lower, 'boundary') !== false
        ) {
            header(rtrim($header));
        }
        return strlen($header);
    });
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
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
        if (!canDo('manage_cctv')) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        $camera_ip = $_POST['camera_ip'];
        $camera_port = intval($_POST['camera_port']);
        $channel = intval($_POST['channel']);
        $username = $_POST['username'];
        $password = $_POST['password'];
        $stream_type = $_POST['stream_type'];
        $protocol = $_POST['protocol'];

        $stmt = $conn->prepare("UPDATE cameras SET camera_ip = ?, camera_port = ?, channel = ?, username = ?, password = ?, stream_type = ?, protocol = ? WHERE camera_id = ?");
        $stmt->bind_param("siissssi", $camera_ip, $camera_port, $channel, $username, $password, $stream_type, $protocol, $camera_id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Camera Config', "Updated settings for Camera #$camera_id");
            header('Location: cctv.php?success=settings_updated');
            exit();
        }
    }

    if ($action === 'add_camera') {
        if (!canDo('manage_cctv')) {
            include __DIR__ . '/includes/access_denied_ui.php';
            exit();
        }
        $camera_name = $_POST['new_camera_name'] ?? '';
        $location = $_POST['new_location'] ?? '';
        $camera_ip = $_POST['new_camera_ip'] ?? '';
        $camera_port = intval($_POST['new_camera_port'] ?? 554);
        $channel = intval($_POST['new_channel'] ?? 1);
        $username = $_POST['new_username'] ?? 'admin';
        $password = $_POST['new_password'] ?? '';
        $stream_type = $_POST['new_stream_type'] ?? 'main';
        $protocol = $_POST['new_protocol'] ?? 'rtsp';
        $status = $_POST['new_status'] ?? 'Online';

        if (!empty($camera_name) && !empty($location) && !empty($camera_ip)) {
            $stmt = $conn->prepare("INSERT INTO cameras (camera_name, location, camera_ip, camera_port, channel, username, password, stream_type, protocol, status, installation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->bind_param("sssiisssss", $camera_name, $location, $camera_ip, $camera_port, $channel, $username, $password, $stream_type, $protocol, $status);

            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Camera Added', "Added new camera: $camera_name");
                header('Location: cctv.php?success=camera_added');
                exit();
            }
        }
    }

    if ($action === 'delete_camera' && $camera_id > 0) {
        if (!canDo('manage_cctv')) {
            include __DIR__ . '/includes/access_denied_ui.php';
            exit();
        }
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
        if ($row['config_key'] == 'system_name')
            $system_name = $row['config_value'];
        if ($row['config_key'] == 'organization_name')
            $organization_name = $row['config_value'];
    }
}

$canManageCctv = canDo('manage_cctv');
$canTakeSnapshotPermission = canDo('take_snapshots');
$isObserver = (getUserRole() === 'System Observer');

// Pre-calculate technical telemetry
$cameras_result->data_seek(0);
$online = 0;
while ($cam = $cameras_result->fetch_assoc()) {
    if ($cam['status'] === 'Online')
        $online++;
}
$cameras_result->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CCTV Cameras - Shine Guard Hulo</title>
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">
    <style>
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
            overflow-x: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
        }

        <?php include 'assets/style.css'; ?>

        /* --- HIGH-FIDELITY CCTV DASHBOARD --- */
        .kpi-card {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            padding: 18px 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-width: 1px;
            height: auto;
            min-height: 100px;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px var(--shadow);
        }

        .kpi-card h3 {
            font-size: 24px;
            margin-bottom: 2px;
        }

        .kpi-card p {
            font-size: 11px;
            letter-spacing: 0.05em;
        }

        .kpi-card .kpi-icon {
            font-size: 24px;
            width: 44px;
            height: 44px;
            border-radius: 10px;
        }

        .kpi-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            transition: 0.6s;
            pointer-events: none;
        }

        .kpi-card:hover::after {
            left: 100%;
        }

        .kpi-trend {
            font-size: 9px;
            font-weight: 800;
            padding: 2px 5px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.4);
        }

        .kpi-action {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            color: var(--accent);
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .kpi-action:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
        }

        .status-indicator {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            margin-right: 3px;
        }

        .kpi-card.red .live-pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            box-shadow: 0 0 12px #ef4444;
            animation: kpi-pulse 2s infinite;
        }

        @keyframes kpi-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .control-deck {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            gap: 2rem;
        }

        /* Premium Camera Grid */
        .obs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 24px;
        }

        .obs-card {
            background: var(--panel);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 20px var(--shadow);
            display: flex;
            flex-direction: column;
        }

        .obs-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border-color: var(--accent);
        }

        .obs-preview {
            position: relative;
            width: 100%;
            height: 260px;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Refined Info HUD */
        .obs-hud {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .hud-tool {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: white;
            border: 1px solid var(--border);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 16px;
            box-shadow: 0 2px 5px var(--shadow);
        }

        .hud-tool:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
            border-color: var(--accent);
        }

        /* Absolute Fullscreen (Zero-Gap) Overhaul */
        .obs-card.fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            max-height: none !important;
            z-index: 100005 !important;
            background: #000000 !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            transform: none !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .obs-card.fullscreen .obs-preview {
            border-radius: 0 !important;
            height: 100% !important;
            width: 100% !important;
            flex: 1 !important;
            margin: 0 !important;
        }

        .obs-card.fullscreen .obs-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px 50px !important;
            margin: 0 !important;
            z-index: 10;
            color: white !important;
        }

        .obs-card.fullscreen .obs-info .obs-name,
        .obs-card.fullscreen .obs-info .obs-loc {
            color: white !important;
        }

        .obs-card.fullscreen .obs-info .hud-tool {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .focus-close {
            position: absolute;
            top: 40px;
            right: 40px;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #ef4444;
            color: white;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            cursor: pointer;
            z-index: 100010;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
            border: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .focus-close:hover {
            transform: scale(1.1) rotate(90deg);
            background: #dc2626;
        }

        .obs-card.fullscreen .focus-close {
            display: flex;
        }

        .hud-tool.active-focus {
            background: #ef4444 !important;
            color: white !important;
            border-color: #ef4444 !important;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4) !important;
        }

        .focus-close {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #ef4444;
            color: white;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            z-index: 100005;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            border: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .focus-close:hover {
            transform: scale(1.1) rotate(90deg);
            background: #dc2626;
        }

        .obs-card.fullscreen .focus-close {
            display: flex;
        }

        .hud-tool.active-focus {
            background: #ef4444 !important;
            color: white !important;
            border-color: #ef4444 !important;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4) !important;
        }


        .obs-status-pill {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            color: white;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 5;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent);
            animation: blink 2s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.4;
                transform: scale(0.8);
            }
        }

        .obs-info {
            padding: 20px;
        }

        .obs-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .obs-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
            margin: 0;
        }

        .obs-loc {
            font-size: 13px;
            color: var(--dim);
            margin-top: 2px;
        }

        .obs-spec-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .spec-item {
            display: flex;
            flex-direction: column;
        }

        .spec-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .spec-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
        }

        .obs-card.fullscreen {
            position: fixed;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 85vw !important;
            height: 85vh !important;
            z-index: 100001 !important;
            background: var(--panel) !important;
            color: var(--text) !important;
            margin: 0 !important;
            border-radius: 24px !important;
            border: 1px solid var(--border) !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 24px !important;
            box-sizing: border-box !important;
            box-shadow: 0 0 0 100vmax rgba(15, 23, 42, 0.8),
                0 30px 60px -12px rgba(0, 0, 0, 0.6) !important;
        }

        .dark-mode .obs-card.fullscreen {
            background: #1e293b !important;
        }

        @media (max-width: 768px) {
            .obs-card.fullscreen {
                width: 100vw !important;
                height: 100vh !important;
                border-radius: 0 !important;
            }
        }

        .obs-card.fullscreen .obs-preview {
            flex: 1;
            height: auto;
            min-height: 50vh;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
        }

        .obs-card.fullscreen .obs-preview img {
            object-fit: contain !important;
            background: #000;
        }

        .obs-card.fullscreen .obs-name,
        .obs-card.fullscreen .spec-value {
            color: var(--text) !important;
        }

        .settings-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .settings-modal.open {
            display: flex !important;
        }

        .settings-content {
            background: var(--panel);
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow);
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
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--muted);
            color: var(--text);
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            background: var(--panel);
            border-color: var(--accent);
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

            <!-- Upgrade KPIs in old stats-grid container -->
            <div class="kpi-grid" style="margin-bottom: 2rem; gap: 16px;">
                <div class="kpi-card green">
                    <div class="kpi-icon">📹</div>
                    <div class="kpi-data" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3><?php echo $cameras_result->num_rows; ?></h3>
                            <span class="kpi-trend" style="color: #166534;">ACTIVE</span>
                        </div>
                        <p>TOTAL NODES</p>
                    </div>
                </div>

                <div class="kpi-card blue">
                    <div class="kpi-icon">📡</div>
                    <div class="kpi-data" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3><?php echo $online; ?></h3>
                            <span class="kpi-trend" style="color: #1e40af;">98.2% UPTIME</span>
                        </div>
                        <p>ACTIVE SIGNALS</p>
                    </div>
                </div>

                <div class="kpi-card purple">
                    <div class="kpi-icon">📸</div>
                    <div class="kpi-data" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3><?php echo $snapshots_count; ?></h3>
                            <button onclick="openGalleryModal()" class="kpi-action">GALLERY ↗</button>
                        </div>
                        <p>SNAPSHOT CACHE</p>
                    </div>
                </div>

                <div class="kpi-card red">
                    <div class="kpi-icon">
                        <div class="live-pulse"></div>
                    </div>
                    <div class="kpi-data" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3 style="color: #dc2626;">LIVE</h3>
                            <span class="kpi-trend" style="background:rgba(239,68,68,0.1); color:#991b1b;">ACTIVE</span>
                        </div>
                        <p>SURVEILLANCE MODE</p>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 20px;">
                    <h2 style="margin: 0;">📹 Camera Monitoring Matrix</h2>
                    <?php if (canDo('manage_cctv')): ?>
                        <button class="btn primary" onclick="openAddCameraModal()"
                            style="padding: 10px 18px; font-size: 14px;">
                            ➕ Add Camera
                        </button>
                    <?php endif; ?>
                </div>

                <div class="obs-grid" id="cameraGrid">
                    <?php
                    $cameras_result->data_seek(0);
                    while ($camera = $cameras_result->fetch_assoc()):
                        ?>
                        <div class="obs-card" data-name="<?php echo strtolower($camera['camera_name']); ?>"
                            data-loc="<?php echo strtolower($camera['location']); ?>">
                            <div class="obs-preview" id="preview-<?php echo $camera['camera_id']; ?>">
                                <button class="focus-close" title="Exit Focus"
                                    onclick="toggleFullscreen(<?php echo $camera['camera_id']; ?>, null)">×</button>
                                <div class="obs-status-pill">
                                    <div class="pulse-dot"
                                        style="background: <?php echo $camera['status'] === 'Online' ? 'var(--ok)' : 'var(--danger)'; ?>; box-shadow: 0 0 10px <?php echo $camera['status'] === 'Online' ? 'var(--ok)' : 'var(--danger)'; ?>;">
                                    </div>
                                    <?php echo strtoupper($camera['status']); ?>
                                </div>

                                <div class="camera-placeholder" id="placeholder-<?php echo $camera['camera_id']; ?>">
                                    <i style="font-size: 40px; opacity: 0.3;">📹</i>
                                    <div style="font-size: 11px; margin-top: 12px; font-weight: 700; color: #94a3b8;">SIGNAL
                                        CARRIER READY</div>
                                    <button class="btn primary"
                                        style="margin-top: 15px; font-size: 11px; padding: 8px 16px;"
                                        onclick="toggleStream(<?php echo $camera['camera_id']; ?>, this)">
                                        REQUEST FEED
                                    </button>
                                </div>
                            </div>

                            <div class="obs-info" style="background: var(--panel);">
                                <div class="obs-title-row">
                                    <div>
                                        <h3 class="obs-name"><?php echo htmlspecialchars($camera['camera_name']); ?></h3>
                                        <div class="obs-loc"><?php echo htmlspecialchars($camera['location']); ?></div>
                                    </div>
                                    <div
                                        style="font-size: 10px; font-weight: 800; color: var(--accent); background: rgba(34, 197, 94, 0.1); padding: 4px 8px; border-radius: 6px;">
                                        CAM_ID: <?php echo str_pad($camera['camera_id'], 3, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </div>

                                <div class="obs-hud">
                                    <div class="hud-tool" title="Quick Snapshot"
                                        onclick="takeSnapshot(<?php echo $camera['camera_id']; ?>, '<?php echo addslashes($camera['camera_name']); ?>', this)">
                                        📸</div>
                                    <div class="hud-tool focus-trigger-<?php echo $camera['camera_id']; ?>"
                                        title="Signal Focus"
                                        onclick="toggleFullscreen(<?php echo $camera['camera_id']; ?>, this)">⛶</div>
                                    <?php if (canDo('manage_cctv')): ?>
                                        <div class="hud-tool" title="Node Configuration"
                                            onclick="openSettings(<?php echo $camera['camera_id']; ?>, <?php echo htmlspecialchars(json_encode($camera)); ?>)">
                                            ⚙️</div>
                                    <?php endif; ?>

                                    <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                                        <div class="spec-item"
                                            style="text-align: right; border-right: 1px solid var(--border); padding-right: 10px;">
                                            <span class="spec-label">SIGNAL</span>
                                            <span
                                                class="spec-value"><?php echo strtoupper($camera['stream_type']); ?></span>
                                        </div>
                                        <div class="spec-item" style="text-align: right;">
                                            <span class="spec-label">PTCL</span>
                                            <span class="spec-value"><?php echo strtoupper($camera['protocol']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="obs-spec-grid" style="margin-top: 15px; border-top: none; padding-top: 0;">
                                    <div class="spec-item">
                                        <span class="spec-label">RESOLUTION MATRIX</span>
                                        <span
                                            class="spec-value"><?php echo $camera['resolution'] ?: 'AUTO_DETECT'; ?></span>
                                    </div>
                                </div>
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
                    <h3>🖥️ Connectivity Configuration</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Camera IP Address</label>
                            <input type="text" name="camera_ip" id="camera_ip" placeholder="192.168.1.64" required>
                        </div>
                        <div class="form-group">
                            <label>RTSP Port</label>
                            <input type="number" name="camera_port" id="camera_port" value="554" required>
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
                    <div class="tip-box">
                        <strong>💡 Tip:</strong> Use Sub Stream for remote viewing to save bandwidth. Main Stream for
                        recording.
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-danger" onclick="openDeleteCameraModal()">
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
                <h2
                    style="font-size: 20px; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px;">
                    ➕ Add New Camera</h2>
                <button type="button" onclick="closeAddCameraModal()"
                    style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">×</button>
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
                            <label>Camera IP Address <span style="color: red;">*</span></label>
                            <input type="text" name="new_camera_ip" placeholder="192.168.1.x" required>
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
                            <input type="number" name="new_camera_port" value="554">
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

                <div
                    style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn" onclick="closeAddCameraModal()">Cancel</button>
                    <button type="submit" class="btn primary">➕ Add Camera</button>
                </div>
            </form>
        </div>
    </div>

    <div id="playModal" class="settings-modal" onclick="if(event.target===this) closeCCTVModal('playModal')">
        <div class="modal-spring settings-content" style="max-width: 420px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div
                    style="background: #d1fae5; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    ▶️</div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Live Stream</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="playModalCamName">Camera Name
                    </div>
                </div>
            </div>
            <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
                The system is now connecting to the camera's RTSP stream. The live feed will be displayed in the camera
                preview window shortly.
            </p>
            <div style="display: flex; justify-content: flex-end;">
                <button onclick="closeCCTVModal('playModal')" class="btn primary" style="padding: 10px 24px;">Got
                    it</button>
            </div>
        </div>
    </div>

    <div id="snapshotModal" class="settings-modal" onclick="if(event.target===this) closeCCTVModal('snapshotModal')">
        <div class="modal-spring settings-content" style="max-width: 420px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div
                    style="background: #eff6ff; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    📸</div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Capture Snapshot</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="snapshotModalCamName">Camera
                        Name</div>
                </div>
            </div>
            <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
                The current frame has been successfully captured and saved to the snapshot gallery. You can view it in
                the reports section.
            </p>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button onclick="openGalleryModal(); closeCCTVModal('snapshotModal')" class="btn"
                    style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;">View Gallery</button>
                <button onclick="closeCCTVModal('snapshotModal')" class="btn primary"
                    style="background: #3b82f6; border: none;">Close</button>
            </div>
        </div>
    </div>

    <div id="snapshotGalleryModal" class="settings-modal"
        onclick="if(event.target===this) closeCCTVModal('snapshotGalleryModal')">
        <div class="modal-spring settings-content"
            style="max-width: 900px; width: 90%; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="settings-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="background: #f5f3ff; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                        🔐</div>
                    <div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Secure Snapshot Gallery</div>
                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">End-to-End Encrypted Storage
                        </div>
                    </div>
                </div>
                <button class="close-btn" onclick="closeCCTVModal('snapshotGalleryModal')">&times;</button>
            </div>

            <div id="galleryViewOnlyMessage"
                style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; text-align: center; margin: auto; width: 100%; max-width: 400px;">
                <span style="font-size: 32px; display: block; margin-bottom: 12px;">🔒</span>
                <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 8px;">View Only Access</h3>
                <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 20px;">
                    The snapshot gallery is encrypted and restricted to administrators. Observers have view-only access
                    to live feeds but cannot view captured evidence.
                </p>
                <div
                    style="padding: 12px; background: #f1f5f9; border-radius: 8px; color: #64748b; font-weight: 600; font-size: 0.875rem; border: 1px dashed #cbd5e1;">
                    RESTRICTED ACCESS
                </div>
                <button onclick="closeCCTVModal('snapshotGalleryModal')" class="btn"
                    style="width: 100%; margin-top: 20px;">Close Gallery</button>
            </div>

            <div id="galleryAuthGate"
                style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; text-align: center; margin: auto; width: 100%; max-width: 400px;">
                <span style="font-size: 32px; display: block; margin-bottom: 12px;">🔒</span>
                <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 8px;">Authentication Required</h3>
                <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 20px;">
                    These images are encrypted on disk. Please enter your administrator password to unlock and decrypt
                    the gallery.
                </p>
                <div style="margin-bottom: 20px; text-align: left;">
                    <label for="galleryAdminPassword"
                        style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">Administrator
                        Password</label>
                    <input type="password" id="galleryAdminPassword" placeholder="Enter password to decrypt"
                        style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;"
                        onfocus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139,92,246,0.1)'"
                        onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                    <div id="galleryPasswordError"
                        style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required
                    </div>
                </div>
                <button id="galleryUnlockBtn" onclick="verifyGalleryPassword()" class="btn primary"
                    style="width: 100%; background: #8b5cf6; border: none;">Unlock Gallery</button>
            </div>

            <div id="galleryContent" style="display: none; flex-grow: 1; overflow-y: auto; padding-right: 10px;">
                <div id="galleryGrid"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">

                </div>
                <div id="galleryLoading" style="text-align: center; padding: 40px; color: #64748b; font-size: 0.9rem;">
                    Loading encrypted snapshots...
                </div>
            </div>
        </div>
    </div>

    <div id="deleteCameraConfirmModal" class="settings-modal"
        onclick="if(event.target===this) closeCCTVModal('deleteCameraConfirmModal')">
        <div class="modal-spring settings-content" style="max-width: 420px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div
                    style="background: #fef2f2; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    🗑️</div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">Remove Camera?</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="deleteModalCamName">Camera ID
                    </div>
                </div>
            </div>
            <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
                Are you sure you want to completely remove this camera and its footage history from the system?
                <strong>This action cannot be undone.</strong>
            </p>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button onclick="closeCCTVModal('deleteCameraConfirmModal')" class="btn">Cancel</button>
                <button onclick="confirmDeleteCamera()" class="btn"
                    style="background: #ef4444; color: white; border: none; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">🗑️
                    Yes, Remove</button>
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
        const canSnapshot = <?php echo $canTakeSnapshotPermission ? 'true' : 'false'; ?>;
        const isObserver = <?php echo $isObserver ? 'true' : 'false'; ?>;
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
            if (currentCameraId) {
                document.getElementById('delete_camera_id').value = currentCameraId;
                document.getElementById('deleteCameraForm').submit();
            }
        }

        let originalParents = {};
        let originalSiblings = {};

        function toggleFullscreen(camId, btn) {
            const card = document.getElementById('camera-card-' + camId);
            if (!card) return;

            if (card.classList.contains('fullscreen')) {
                card.classList.remove('fullscreen');

                // Restore to original position
                const p = originalParents[camId];
                const s = originalSiblings[camId];
                if (p) {
                    if (s) p.insertBefore(card, s);
                    else p.appendChild(card);
                }

                btn.innerHTML = '⛶ Maximize';
                document.body.style.overflow = '';
            } else {
                // Save original position
                originalParents[camId] = card.parentNode;
                originalSiblings[camId] = card.nextSibling;

                // Move to body level to break out of grid/transform containers
                document.body.appendChild(card);
                card.classList.add('fullscreen');

                btn.innerHTML = '🗕 Minimize';
                document.body.style.overflow = 'hidden';
            }
        }

        function openGalleryModal() {
            openCCTVModal('snapshotGalleryModal');

            document.getElementById('galleryContent').style.display = 'none';
            document.getElementById('galleryGrid').innerHTML = '';

            if (isObserver) {
                document.getElementById('galleryAuthGate').style.display = 'none';
                document.getElementById('galleryViewOnlyMessage').style.display = 'block';
            } else {
                document.getElementById('galleryAuthGate').style.display = 'block';
                document.getElementById('galleryViewOnlyMessage').style.display = 'none';
                document.getElementById('galleryAdminPassword').value = '';
                document.getElementById('galleryPasswordError').style.display = 'none';
            }
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
                formData.append('source', 'gallery');

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
            } catch (err) {
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

            } catch (err) {
                console.error(err);
                loader.textContent = 'Failed to load snapshots. Please try again.';
                loader.style.color = '#ef4444';
            }
        }

        function toggleFullscreen(camId, btn) {
            const card = document.getElementById('preview-' + camId).closest('.obs-card');
            const hudBtn = btn || document.querySelector('.focus-trigger-' + camId);

            card.classList.toggle('fullscreen');

            if (card.classList.contains('fullscreen')) {
                hudBtn.innerHTML = '⛊';
                hudBtn.title = "Exit Focus";
                hudBtn.classList.add('active-focus');
                document.body.style.overflow = 'hidden';
            } else {
                hudBtn.innerHTML = '⛶';
                hudBtn.title = "Maximize";
                hudBtn.classList.remove('active-focus');
                document.body.style.overflow = '';
            }
        }

        function toggleStream(camId, btn) {
            const preview = document.getElementById('preview-' + camId);
            const placeholder = document.getElementById('placeholder-' + camId);

            if (!document.getElementById('stream-img-' + camId)) {
                placeholder.style.display = 'none';
                btn.textContent = 'TERMINATE';
                btn.classList.remove('primary');
                btn.style.background = 'rgba(239, 68, 68, 0.2)';
                btn.style.color = '#ef4444';
                btn.style.borderColor = '#ef4444';

                const img = document.createElement('img');
                img.src = 'cctv.php?proxy_stream=1&cam=' + camId;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.id = 'stream-img-' + camId;
                preview.appendChild(img);
            } else {
                placeholder.style.display = 'flex';
                btn.textContent = 'REQUEST FEED';
                btn.classList.add('primary');
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';
                const img = document.getElementById('stream-img-' + camId);
                if (img) img.remove();
            }
        }

        // Add fadeIn animation
        const style = document.createElement('style');
        style.textContent = '@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }';
        document.head.appendChild(style);

        function openCCTVModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('open');
        }

        function closeCCTVModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        function openSettings(cameraId, cameraData) {
            currentCameraId = cameraId;
            document.getElementById('settings_camera_id').value = cameraId;
            document.getElementById('camera_ip').value = cameraData.camera_ip || '';
            document.getElementById('camera_port').value = cameraData.camera_port || 554;
            document.getElementById('channel').value = cameraData.channel || 1;
            document.getElementById('username').value = cameraData.username || 'admin';
            document.getElementById('password').value = cameraData.password || '';
            document.getElementById('stream_type').value = cameraData.stream_type || 'main';
            document.getElementById('protocol').value = cameraData.protocol || 'rtsp';

            const modal = document.getElementById('settingsModal');
            modal.classList.add('open');
        }

        function closeSettings() {
            const modal = document.getElementById('settingsModal');
            modal.classList.remove('open');
        }

        document.getElementById('settingsModal').addEventListener('click', function (e) {
            if (e.target === this) closeSettings();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const camId = urlParams.get('id');
            if (camId) {
                const el = document.getElementById('preview-' + camId).closest('.obs-card');
                if (el) {
                    setTimeout(() => {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        el.style.borderColor = 'var(--accent)';
                        el.style.boxShadow = '0 0 0 4px rgba(34, 197, 94, 0.2)';
                        setTimeout(() => {
                            el.style.boxShadow = '';
                        }, 3000);
                    }, 500);
                }
            }
        });
    </script>

    <?php include 'assets/app_alert.php'; ?>
</body>

</html>
<?php
$conn->close();
?>