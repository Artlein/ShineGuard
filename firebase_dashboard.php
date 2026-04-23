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
        setRecentlyAuthorized();
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

// Fetch active node
$currentNode = $_GET['node'] ?? 'SG-NODE2';
if (!array_key_exists($currentNode, \FirebaseConfig::getAllIoTDevices())) {
    $currentNode = 'SG-NODE2';
}

$activeNodeConfig = \FirebaseConfig::getIoTDevice($currentNode);
$firebaseCreds = \FirebaseConfig::getNodeConfig($currentNode);
$firebaseCredsJson = json_encode($firebaseCreds);

// Fetch thresholds for JS
$thresh_result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key LIKE '%threshold%'");
$thresholds = [];
while ($t = $thresh_result->fetch_assoc()) {
    $thresholds[$t['config_key']] = floatval($t['config_value']);
}
$thresholds_json = json_encode($thresholds);

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>IoT Intelligence - Shine Guard Hulo</title>
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<style>
<?php include 'assets/style.css'; ?>

        :root {
            --theme:    <?php echo $theme_color; ?>;
            --bg:       #f8fafc; /* Activity Logs match */
            --bg-panel: #ffffff;
            --border:   rgba(15, 23, 42, 0.08);
            --text:     #0f172a;
            --dim:      #475569;
            --muted:    #64748b;
            --shadow:   rgba(15, 23, 42, 0.05);
            --mono:     system-ui, -apple-system, 'Inter', sans-serif;
        }

        .dark-mode {
            --bg:       #0b0f1a;
            --bg-panel: #111827;
            --border:   rgba(255, 255, 255, 0.08);
            --text:     #f8fafc;
            --dim:      #94a3b8;
            --muted:    #64748b;
            --shadow:   rgba(0, 0, 0, 0.3);
        }

        .layout { background: var(--bg); transition: background 0.3s; }

        body { font-family: system-ui, -apple-system, 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; transition: background 0.3s, color 0.3s; }
        .main-content { padding: 1.5rem 2rem 2rem; background: var(--bg); }

        /* ── LOCAL HEADER & SIDEBAR OVERRIDES (Specific to Dashboard) ── */
        .layout header { background: var(--bg-panel) !important; border-bottom: 1px solid var(--border) !important; color: var(--text) !important; }
        .layout .hdr-title { color: var(--text) !important; }
        .layout .hdr-sub { color: var(--dim) !important; }
        .layout .hdr-search input { background: var(--bg) !important; border-color: var(--border) !important; color: var(--text) !important; }
        .layout .hdr-icon-btn { background: var(--bg-panel) !important; border-color: var(--border) !important; color: var(--dim) !important; }

        /* ── PERMANENTLY DARK SIDEBAR (Command Center Aesthetic) ── */
        .layout .sidebar { 
            --sb-bg: #111827 !important; 
            --sb-bg-footer: #0b0f1a !important;
            --sb-txt-hi: #f1f5f9 !important;
            --sb-txt-nav: #94a3b8 !important;
            --sb-txt-muted: #4b5563 !important;
            --sb-lbl: #6b7280 !important;
            --sb-border: rgba(255, 255, 255, 0.06) !important;
            --sb-accent: #10b981 !important;
            --sb-font: 'Inter', sans-serif !important;
            
            background: var(--sb-bg) !important; 
            border-right: 1px solid var(--sb-border) !important; 
        }
        .layout .sb-nav a { color: var(--sb-txt-nav) !important; font-weight: 700 !important; font-size: 14px !important; }
        .layout .sb-nav a:hover { background: rgba(255,255,255,0.03) !important; color: #ffffff !important; }
        .layout .sb-nav a.active { background: rgba(16, 185, 129, 0.15) !important; color: #10b981 !important; }
        .layout .sb-lbl { color: var(--sb-lbl) !important; font-weight: 900 !important; letter-spacing: 0.1em !important; }
        .layout .sb-lbl::after { background: var(--sb-border) !important; }
        .layout .sb-brand-copy h2 { color: #ffffff !important; font-weight: 900 !important; letter-spacing: -0.02em !important; }
        .layout .sb-brand-copy p { color: var(--sb-txt-muted) !important; font-weight: 600 !important; }
        .layout .sb-toggle { background: #1f2937 !important; border: 1px solid var(--sb-border) !important; color: #ffffff !important; }
        .layout .sb-foot { border-top: 1px solid var(--sb-border) !important; background: var(--sb-bg-footer) !important; }

        /* ── PAGE HEADER ── */
        .page-hero {
            display: flex; align-items: center; justify-content: space-between;
            background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(59,130,246,0.08));
            border: 1px solid rgba(16,185,129,0.2);
            border-radius: 20px; padding: 20px 28px; margin-bottom: 24px;
        }
        .hero-left { display: flex; align-items: center; gap: 16px; }
        .hero-icon {
            width: 52px; height: 52px; border-radius: 16px;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 24px rgba(16,185,129,0.4);
        }
        .hero-icon svg { width: 26px; height: 26px; color: #ffffff; }
        .hero-title { font-size: 1.4rem; font-weight: 900; color: var(--text); letter-spacing: -0.04em; }
        .dark-mode .hero-title { color: #ffffff; }
        .hero-sub { font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 4px; }
        .hero-right { display: flex; align-items: center; gap: 10px; }

        /* ── ONLINE PILL ── */
        .status-pill {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 16px; border-radius: 999px; font-size: 12px; font-weight: 700;
            background: rgba(16,185,129,0.15); color: #10b981;
            border: 1px solid rgba(16,185,129,0.3); letter-spacing: 0.04em;
            transition: all 0.4s ease;
        }
        .status-pill.offline { background: rgba(239,68,68,0.1); color: var(--red); border-color: rgba(239,68,68,0.3); }
        .pulse-dot {
            width: 8px; height: 8px; border-radius: 50%; background: #10b981;
            animation: pulse 1.6s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(0.7)} }
        
        /* ── NODE SWITCHER (Pill Style) ── */
        .node-switcher {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 4px 12px var(--shadow);
            margin-right: 12px;
        }
        .btn-node-opt {
            padding: 7px 18px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--dim);
            border: none;
            background: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-node-opt:hover { color: var(--text); background: rgba(15, 23, 42, 0.04); }
        .btn-node-opt.active {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }
        .node-mini-pulse {
            width: 6px; height: 6px; border-radius: 50%; background: currentColor;
            opacity: 0.6;
        }

        /* ── GRID ── */
        .dash-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            grid-template-rows: auto auto;
            grid-template-areas:
                "sensors controls"
                "health   activity";
            gap: 20px;
        }
        @media (max-width: 1100px) {
            .dash-grid { grid-template-columns: 1fr; grid-template-areas: "sensors" "controls" "health" "activity"; }
        }

        /* ── CARD ── */
        .card {
            background: var(--bg-panel); border: 1px solid var(--border);
            border-radius: 20px; padding: 22px;
            position: relative; overflow: hidden;
            box-shadow: 0 4px 6px -1px var(--shadow), 0 2px 4px -1px var(--shadow);
        }
        .card-glow {
            position: absolute; top: -60px; right: -60px; width: 200px; height: 200px;
            border-radius: 50%; pointer-events: none; opacity: 0.06;
            filter: blur(40px);
        }
        .card-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .card-head h3 {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 900; color: var(--dim);
            text-transform: uppercase; letter-spacing: 0.1em; margin: 0;
        }
        .card-head h3 .head-icon {
            width: 30px; height: 30px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .card-head h3 svg { width: 15px; height: 15px; }

        /* ── SENSOR CARDS ── */
        .sensors-area { grid-area: sensors; }
        .sensor-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }

        .sensor-card {
            background: var(--bg-panel); border: 1px solid var(--border);
            border-radius: 16px; padding: 20px;
            position: relative; overflow: hidden; cursor: default;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px var(--shadow);
        }
        .sensor-card:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.15); }
        .sensor-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            border-radius: 16px 16px 0 0;
            background: var(--sensor-color, var(--green));
        }

        .sensor-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .sensor-label { font-size: 11px; font-weight: 900; color: var(--dim); text-transform: uppercase; letter-spacing: 0.08em; }
        .sensor-icon-wrap {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: var(--sensor-bg, rgba(16,185,129,0.12));
        }
        .sensor-icon-wrap svg { width: 16px; height: 16px; color: var(--sensor-color, var(--green)); }

        .sensor-value {
            font-size: 2.6rem; font-weight: 900;
            color: var(--text); line-height: 1; display: flex; align-items: baseline; gap: 4px;
            letter-spacing: -0.04em;
        }
        .sensor-unit { font-size: 1.1rem; font-weight: 600; color: var(--dim); }

        .sensor-bar-wrap { margin-top: 14px; }
        .sensor-bar-track {
            height: 4px; border-radius: 99px; background: rgba(255,255,255,0.08);
            overflow: hidden;
        }
        .sensor-bar-fill {
            height: 100%; border-radius: 99px; width: 0%; transition: width 0.8s cubic-bezier(0.4,0,0.2,1);
            background: var(--sensor-color, var(--green));
        }
        .sensor-bar-label {
            display: flex; justify-content: space-between;
            margin-top: 6px; font-size: 10px; color: var(--dim); font-weight: 700;
        }
        .sensor-status-tag {
            display: inline-block; margin-top: 10px;
            font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px;
            background: rgba(16,185,129,0.12); color: #10b981;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .sensor-status-tag.warn { background: rgba(245,158,11,0.12); color: var(--yellow); }
        .sensor-status-tag.crit { background: rgba(239,68,68,0.12); color: var(--red); }

        /* ── CONTROLS ── */
        .controls-area { grid-area: controls; }
        .mode-grid { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .btn-mode {
            display: flex; align-items: center; gap: 14px;
            background: var(--bg-panel); border: 1.5px solid var(--border);
            border-radius: 14px; padding: 14px 16px; cursor: pointer;
            transition: all 0.2s ease; text-align: left; width: 100%;
            color: var(--text);
        }
        .btn-mode:hover { border-color: rgba(255,255,255,0.2); background: var(--bg); }
        .btn-mode.active {
            border-color: var(--mode-color, var(--theme));
            background: rgba(16,185,129,0.1);
            box-shadow: 0 0 0 1px var(--mode-color, var(--theme)), 0 0 20px -8px var(--mode-color, var(--theme));
        }
        .btn-mode .mode-ico {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.06);
        }
        .btn-mode.active .mode-ico { background: var(--mode-color, var(--theme)); }
        .btn-mode svg { width: 18px; height: 18px; color: var(--muted); }
        .btn-mode.active svg { color: #ffffff; }
        .mode-label { font-size: 13px; font-weight: 900; color: var(--text); display: block; }
        .mode-sub { font-size: 10px; color: var(--dim); font-weight: 700; display: block; margin-top: 2px; }

        /* slider */
        .slider-section {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 14px; padding: 18px;
        }
        .slider-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .slider-title { font-size: 11px; font-weight: 900; color: var(--dim); text-transform: uppercase; letter-spacing: 0.08em; }
        .slider-val {
            font-size: 1.6rem; font-weight: 900;
            color: var(--theme); text-shadow: 0 0 20px rgba(16,185,129,0.3);
            letter-spacing: -0.03em;
        }
        input[type=range].custom-slider {
            -webkit-appearance: none; appearance: none;
            width: 100%; height: 6px; border-radius: 99px; outline: none;
            background: rgba(255,255,255,0.08); cursor: pointer;
        }
        input[type=range].custom-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%;
            background: white; border: 3px solid var(--theme);
            box-shadow: 0 0 10px rgba(16,185,129,0.5);
            cursor: pointer; transition: transform 0.1s;
        }
        input[type=range].custom-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }
        input[type=range].custom-slider::-moz-range-thumb {
            width: 20px; height: 20px; border-radius: 50%;
            background: white; border: 3px solid var(--theme); cursor: pointer;
        }
        .slider-marks { display: flex; justify-content: space-between; margin-top: 8px; }
        .slider-marks span { font-size: 10px; color: var(--dim); font-weight: 700; }

        /* Dimming Segmented Control */
        .dimmer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 15px;
        }
        .btn-dim {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 10px 4px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--dim);
            font-size: 12px;
            font-weight: 800;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .btn-dim:hover { border-color: rgba(255,255,255,0.15); background: rgba(59,130,246,0.05); }
        .btn-dim.active {
            background: rgba(59,130,246,0.1);
            border-color: #3b82f6;
            color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .btn-dim .dim-ico { font-size: 16px; margin-bottom: 2px; }

        /* ── HEALTH CARDS ── */
        .health-area {
            grid-area: health;
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;
        }
        .health-card {
            background: var(--bg-panel); border: 1px solid var(--border);
            border-radius: 16px; padding: 18px;
            display: flex; align-items: center; gap: 14px;
            transition: all 0.3s;
            box-shadow: 0 1px 3px var(--shadow);
        }
        .health-card.ok    { border-bottom: 2px solid var(--green); }
        .health-card.warn  { border-bottom: 2px solid var(--yellow); }
        .health-card.fail  { border-bottom: 2px solid var(--red); }
        .health-ico {
            width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .health-ico svg { width: 20px; height: 20px; }
        .health-lbl { font-size: 10px; font-weight: 900; color: var(--dim); text-transform: uppercase; letter-spacing: 0.08em; }
        .health-val { font-size: 1.1rem; font-weight: 900; color: var(--text); margin-top: 2px; display: block; }
        .health-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .hb-ok   { background: rgba(16,185,129,0.12); color: var(--green); }
        .hb-warn { background: rgba(245,158,11,0.12); color: var(--yellow); }
        .hb-fail { background: rgba(239,68,68,0.12); color: var(--red); }

        /* ── TERMINAL FEED ── */
        .activity-area { grid-area: activity; }
        .terminal {
            background: #080c14; border: 1px solid rgba(16,185,129,0.3);
            border-radius: 14px; overflow: hidden; height: 100%;
        }
        .dark-mode .terminal { background: #05080c; }
        .terminal-bar {
            display: flex; align-items: center; gap: 7px; padding: 10px 14px;
            background: rgba(16,185,129,0.08); border-bottom: 1px solid rgba(16,185,129,0.15);
        }
        .t-dot { width: 10px; height: 10px; border-radius: 50%; }
        .terminal-title { font-size: 11px; font-weight: 700; color: var(--green); margin-left: 6px; font-family: var(--mono); }
        .terminal-body { padding: 14px; max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; }
        .terminal-body::-webkit-scrollbar { width: 4px; }
        .terminal-body::-webkit-scrollbar-track { background: transparent; }
        .terminal-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        .log-entry { font-family: var(--mono); font-size: 11.5px; line-height: 1.6; display: flex; gap: 8px; }
        .log-ts { color: var(--muted); opacity: 0.6; white-space: nowrap; }
        .log-type { font-weight: 700; }
        .log-type.info    { color: #60a5fa; }
        .log-type.success { color: var(--green); }
        .log-type.error   { color: var(--red); }
        .log-type.warn    { color: var(--yellow); }
        .log-msg { color: #d1d5db; }
        .log-msg.success  { color: #34d318; }
        .log-msg.error    { color: #f87171; }
        .log-empty-state { color: var(--muted); font-family: var(--mono); font-size: 12px; text-align: center; padding: 30px 0; }

        /* ── ACTION BUTTONS ── */
        .btn-action {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 10px; font-size: 12px; font-weight: 700;
            cursor: pointer; border: 1px solid var(--border); transition: all 0.2s;
            letter-spacing: 0.02em;
        }
        .btn-action svg { width: 14px; height: 14px; }
        .btn-action.primary {
            background: var(--theme); color: white; border-color: var(--theme);
            box-shadow: 0 0 20px rgba(16,185,129,0.25);
        }
        .btn-action.primary:hover { transform: translateY(-1px); box-shadow: 0 0 28px rgba(16,185,129,0.4); }
        .btn-action.ghost { background: transparent; color: var(--muted); }
        .btn-action.ghost:hover { background: var(--bg3); color: var(--text); border-color: rgba(255,255,255,0.15); }

        /* ── LAST UPDATED ── */
        .live-chip {
            font-family: var(--mono); font-size: 10px; font-weight: 600;
            color: var(--green); background: rgba(16,185,129,0.1);
            padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(16,185,129,0.2);
        }

        /* ── SECURITY MODAL ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(5,8,18,0.85); backdrop-filter: blur(10px);
            z-index: 9999; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: var(--bg-panel); border: 1px solid var(--border);
            border-radius: 24px; padding: 32px; max-width: 440px; width: 90%;
            box-shadow: 0 40px 100px rgba(0,0,0,0.3);
            animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalScale { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .dark-mode .modal-box { box-shadow: 0 40px 100px rgba(0,0,0,0.7); }
        .modal-head { display: flex; align-items: center; gap: 18px; margin-bottom: 22px; }
        .modal-ico {
            width: 52px; height: 52px; border-radius: 16px; flex-shrink: 0;
            background: rgba(16, 185, 129, 0.1); color: var(--theme);
            display: flex; align-items: center; justify-content: center;
        }
        .modal-ico svg { width: 24px; height: 24px; }
        .modal-t { font-size: 1.25rem; font-weight: 900; color: var(--text); letter-spacing: -0.03em; }
        .modal-sub { font-size: 11px; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }
        .modal-desc { font-size: 15px; color: var(--dim); line-height: 1.6; margin-bottom: 24px; }
        .modal-field { margin-bottom: 28px; }
        .modal-field label { display: block; font-size: 12px; font-weight: 900; color: var(--text); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
        .modal-input {
            width: 100%; background: var(--bg); border: 2px solid var(--border);
            border-radius: 14px; padding: 14px 18px; font-size: 14px; font-weight: 600;
            color: var(--text); outline: none; transition: all 0.2s;
            box-sizing: border-box;
        }
        .modal-input:focus { border-color: var(--theme); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15); }
        .modal-err { color: var(--red); font-size: 12px; font-weight: 700; margin-top: 10px; display: none; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 12px; }
    </style>
    <style>
        /* Header, sidebar font unification handled by global style.css & header.php */
    </style>
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">

        <!-- PAGE HERO -->
        <div class="page-hero">
            <div class="hero-left">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="2"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                    </svg>
                </div>
                <div>
                    <div class="hero-title"><?php echo $currentNode; ?> · IoT Intelligence</div>
                    <div class="hero-sub">Real-time ESP32 Streetlight Controller</div>
                </div>
            </div>
            <div class="hero-right">
                <div class="node-switcher">
                    <a href="?node=SG-NODE2" class="btn-node-opt <?php echo $currentNode === 'SG-NODE2' ? 'active' : ''; ?>">
                        <span class="node-mini-pulse"></span>
                        SG-NODE 2
                    </a>
                    <a href="?node=SG-NODE3" class="btn-node-opt <?php echo $currentNode === 'SG-NODE3' ? 'active' : ''; ?>">
                        <span class="node-mini-pulse"></span>
                        SG-NODE 3
                    </a>
                </div>
                <div class="status-pill" id="statusPill">
                    <div class="pulse-dot"></div>
                    <span id="statusText">CONNECTING</span>
                </div>
                <button class="btn-action primary" onclick="syncNow()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 1-9 9 9 9 0 0 1-9-9 9 9 0 0 1 9-9"/><polyline points="16 12 21 12 21 7"/></svg>
                    Sync MySQL
                </button>
                <button class="btn-action ghost" onclick="location.reload()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                </button>
            </div>
        </div>

        <div class="dash-grid">

            <!-- ── LIVE TELEMETRY ── -->
            <div class="card sensors-area">
                <div class="card-glow" style="background: #10b981;"></div>
                <div class="card-head">
                    <h3>
                        <span class="head-icon" style="background:rgba(16,185,129,0.12);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        </span>
                        Live Telemetry
                    </h3>
                    <span class="live-chip" id="lastUpdated">UPDATING…</span>
                </div>
                <div class="sensor-grid">

                    <!-- Light -->
                    <div class="sensor-card" style="--sensor-color:#f59e0b; --sensor-bg:rgba(245,158,11,0.1);">
                        <div class="sensor-top">
                            <div class="sensor-label">Ambient Light</div>
                            <div class="sensor-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                            </div>
                        </div>
                        <div class="sensor-value">
                            <span id="ldrData">-- </span>
                            <span class="sensor-unit">lx</span>
                        </div>
                        <div class="sensor-bar-wrap">
                            <div class="sensor-bar-track"><div class="sensor-bar-fill" id="ldrBar" style="background:#f59e0b;"></div></div>
                            <div class="sensor-bar-label"><span>Dark</span><span>Bright</span></div>
                        </div>
                        <span class="sensor-status-tag" id="ldrTag">READING</span>
                    </div>

                    <!-- Temperature -->
                    <div class="sensor-card" style="--sensor-color:#ef4444; --sensor-bg:rgba(239,68,68,0.1);">
                        <div class="sensor-top">
                            <div class="sensor-label">Temperature</div>
                            <div class="sensor-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                            </div>
                        </div>
                        <div class="sensor-value">
                            <span id="temperature">--</span>
                            <span class="sensor-unit">°C</span>
                        </div>
                        <div class="sensor-bar-wrap">
                            <div class="sensor-bar-track"><div class="sensor-bar-fill" id="tempBar" style="background:#ef4444;"></div></div>
                            <div class="sensor-bar-label"><span>0°C</span><span>80°C</span></div>
                        </div>
                        <span class="sensor-status-tag" id="tempTag">READING</span>
                    </div>

                    <!-- Voltage -->
                    <div class="sensor-card" style="--sensor-color:#3b82f6; --sensor-bg:rgba(59,130,246,0.1);">
                        <div class="sensor-top">
                            <div class="sensor-label">Line Voltage</div>
                            <div class="sensor-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            </div>
                        </div>
                        <div class="sensor-value">
                            <span id="voltage">--</span>
                            <span class="sensor-unit">V</span>
                        </div>
                        <div class="sensor-bar-wrap">
                            <div class="sensor-bar-track"><div class="sensor-bar-fill" id="voltBar" style="background:#3b82f6;"></div></div>
                            <div class="sensor-bar-label"><span>0V</span><span>5V</span></div>
                        </div>
                        <span class="sensor-status-tag" id="voltTag">READING</span>
                    </div>

                    <!-- Humidity -->
                    <div class="sensor-card" style="--sensor-color:#06b6d4; --sensor-bg:rgba(6,182,212,0.1);">
                        <div class="sensor-top">
                            <div class="sensor-label">Air Humidity</div>
                            <div class="sensor-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                            </div>
                        </div>
                        <div class="sensor-value">
                            <span id="humidity">--</span>
                            <span class="sensor-unit">%</span>
                        </div>
                        <div class="sensor-bar-wrap">
                            <div class="sensor-bar-track"><div class="sensor-bar-fill" id="humBar" style="background:#06b6d4;"></div></div>
                            <div class="sensor-bar-label"><span>0%</span><span>100%</span></div>
                        </div>
                        <span class="sensor-status-tag" id="humTag">READING</span>
                    </div>

                </div>
            </div>

            <!-- ── COMMAND CENTER ── -->
            <div class="card controls-area">
                <div class="card-glow" style="background: #3b82f6;"></div>
                <div class="card-head">
                    <h3>
                        <span class="head-icon" style="background:rgba(59,130,246,0.12);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </span>
                        Command Center
                    </h3>
                </div>

                <div class="mode-grid">
                    <button class="btn-mode" id="btnAuto" style="--mode-color:#10b981;" onclick="confirmFirebaseCommand('setMode',0)">
                        <div class="mode-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                        </div>
                        <div>
                            <span class="mode-label">AUTO Mode</span>
                            <span class="mode-sub">Environmental logic active</span>
                        </div>
                    </button>
                    <button class="btn-mode" id="btnForceOn" style="--mode-color:#f59e0b;" onclick="confirmFirebaseCommand('setMode',1)">
                        <div class="mode-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/><circle cx="12" cy="12" r="10"/></svg>
                        </div>
                        <div>
                            <span class="mode-label">FORCE ON</span>
                            <span class="mode-sub">Manual override active</span>
                        </div>
                    </button>
                    <button class="btn-mode" id="btnForceOff" style="--mode-color:#64748b;" onclick="confirmFirebaseCommand('setMode',2)">
                        <div class="mode-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </div>
                        <div>
                            <span class="mode-label">FORCE OFF</span>
                            <span class="mode-sub">Safety override active</span>
                        </div>
                    </button>
                </div>

                <div class="slider-section">
                    <div class="slider-head">
                        <span class="slider-title">
                            <svg style="width:12px;height:12px;vertical-align:-1px;margin-right:4px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2"/></svg>
                            Light Intensity
                        </span>
                        <span class="slider-val" id="brightnessValue">--%</span>
                    </div>
                    
                    <div class="dimmer-grid">
                        <button class="btn-dim" id="dim25" onclick="setBrightness(25)">
                            <span class="dim-ico">🍃</span>
                            25%
                        </button>
                        <button class="btn-dim" id="dim50" onclick="setBrightness(50)">
                            <span class="dim-ico">🌓</span>
                            50%
                        </button>
                        <button class="btn-dim" id="dim75" onclick="setBrightness(75)">
                            <span class="dim-ico">🌔</span>
                            75%
                        </button>
                        <button class="btn-dim" id="dim100" onclick="setBrightness(100)">
                            <span class="dim-ico">🌕</span>
                            100%
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── SYSTEM HEALTH ── -->
            <div class="health-area">
                <div class="health-card ok" id="hcLamp">
                    <div class="health-ico" style="background:rgba(16,185,129,0.1);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 1 7 7c0 3.87-2.69 8-7 9-4.31-1-7-5.13-7-9a7 7 0 0 1 7-7z"/></svg>
                    </div>
                    <div>
                        <div class="health-lbl">Lamp Status</div>
                        <span class="health-badge hb-ok" id="lampStatus">--</span>
                    </div>
                </div>
                <div class="health-card ok" id="hcRelay">
                    <div class="health-ico" style="background:rgba(59,130,246,0.1);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                    </div>
                    <div>
                        <div class="health-lbl">Sensor Integration</div>
                        <span class="health-badge hb-ok" id="dhtStatus">--</span>
                    </div>
                </div>
                <div class="health-card ok" id="hcTemp">
                    <div class="health-ico" style="background:rgba(245,158,11,0.1);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                    </div>
                    <div>
                        <div class="health-lbl">Sensors Health</div>
                        <span class="health-badge hb-ok" id="envTempStatus">--</span>
                    </div>
                </div>
                <div class="health-card ok" id="hcFault">
                    <div class="health-ico" style="background:rgba(239,68,68,0.1);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div>
                        <div class="health-lbl">Fatal Faults</div>
                        <span class="health-val" id="lampFaultCounter" style="font-family:var(--mono); color:#ef4444;">0</span>
                    </div>
                </div>
            </div>

            <!-- ── PREDICTIVE ANALYSIS ── -->
            <div class="card" style="grid-area: predictive; background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);">
                <div class="card-glow" style="background: #a855f7; opacity: 0.15;"></div>
                <div class="card-head">
                    <h3 style="color:#a855f7;">
                        <span class="head-icon" style="background:rgba(168,85,247,0.15);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </span>
                        Predictive Analysis
                    </h3>
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div style="background:rgba(255,255,255,0.03); padding:15px; border-radius:12px; border:1px solid rgba(255,255,255,0.05);">
                        <div style="font-size:10px; color:#94a3b8; font-weight:800; text-transform:uppercase; margin-bottom:8px;">Lamp Integrity</div>
                        <div style="font-size:1.4rem; font-weight:900; color:#10b981;" id="lampHealth">STABLE</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding:15px; border-radius:12px; border:1px solid rgba(255,255,255,0.05);">
                        <div style="font-size:10px; color:#94a3b8; font-weight:800; text-transform:uppercase; margin-bottom:8px;">Power Stability</div>
                        <div style="font-size:1.4rem; font-weight:900; color:#3b82f6;" id="powerStability">--</div>
                    </div>
                </div>

                <div style="margin-top:20px; padding:12px; background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.1); border-radius:10px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                        <span style="width:6px; height:6px; border-radius:50%; background:#ef4444; box-shadow:0 0 8px #ef4444;"></span>
                        <span style="font-size:11px; font-weight:800; color:#ef4444; text-transform:uppercase;">Predictive Alert</span>
                    </div>
                    <div id="maintenanceAlert" style="font-size:12px; color:#e2e8f0; font-family:var(--mono);">Awaiting scan...</div>
                </div>
            </div>

            <!-- ── INTELLIGENCE FEED ── -->
            <div class="card activity-area" style="padding:0;">
                <div class="card-glow" style="background: #10b981; top: auto; bottom: -60px;"></div>
                <div class="card-head" style="padding:18px 22px 0; margin-bottom: 0;">
                    <h3>
                        <span class="head-icon" style="background:rgba(16,185,129,0.12);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                        </span>
                        Intelligence Feed
                    </h3>
                    <button onclick="clearLog()" class="btn-action ghost" style="font-size:11px;padding:5px 11px;">Clear</button>
                </div>
                <div class="terminal" style="margin:14px; border-radius:12px;">
                    <div class="terminal-bar">
                        <div class="t-dot" style="background:#ef4444;"></div>
                        <div class="t-dot" style="background:#f59e0b;"></div>
                        <div class="t-dot" style="background:#10b981;"></div>
                        <span class="terminal-title">sg-node2 ~ live-log</span>
                    </div>
                    <div class="terminal-body" id="controlLog">
                        <div class="log-empty-state">» Waiting for hardware events...</div>
                    </div>
                </div>
            </div>

        </div><!-- /dash-grid -->
    </main>
</div>

<!-- ── SECURITY MODAL ── -->
<div class="modal-overlay" id="securityModal">
    <div class="modal-box">
        <div class="modal-head">
            <div class="modal-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
                <div class="modal-t" id="secModalTitle">Confirm Action</div>
                <div class="modal-sub">Hardware Propagation Check</div>
            </div>
        </div>
        <p class="modal-desc" id="secModalDesc">Are you sure you want to proceed?</p>
        <div class="modal-field" id="pwdGroup">
            <label>🔐 Admin Password Required</label>
            <input type="password" class="modal-input" id="secModalPassword" placeholder="Enter your password…" autocomplete="current-password">
            <div class="modal-err" id="secModalError">Invalid password. Please try again.</div>
        </div>
        <div class="modal-actions">
            <button class="btn-action ghost" onclick="closeSecModal()">Cancel</button>
            <button class="btn-action primary" id="secModalConfirmBtn" onclick="confirmSecAction()">Confirm</button>
        </div>
    </div>
</div>

<script>
    // ── Terminal logger ──
    window.addLog = function(type, tag, msg) {
        const feed = document.getElementById('controlLog');
        if (!feed) return;
        const empty = feed.querySelector('.log-empty-state');
        if (empty) empty.remove();
        
        const div = document.createElement('div');
        div.className = 'log-entry';
        const now = new Date();
        const ts = now.getHours().toString().padStart(2,'0') + ':' + 
                   now.getMinutes().toString().padStart(2,'0') + ':' + 
                   now.getSeconds().toString().padStart(2,'0');
        
        div.innerHTML = `
            <span class="log-ts">${ts}</span>
            <span class="log-type ${type}">${tag}</span>
            <span class="log-msg ${type}">${msg}</span>
        `;
        feed.appendChild(div);
        feed.scrollTop = feed.scrollHeight;
    };

    addLog('info', 'SYS', 'System Core: OK');
</script>

<!-- Firebase SDKs (v8 compat) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>

<script>
    const CSRF = '<?php echo $csrf; ?>';
    const THRESHOLDS = <?php echo $thresholds_json; ?>;
    const NODE = "<?php echo $currentNode; ?>";
    const firebaseConfig = <?php echo $firebaseCredsJson; ?>;

    // --- BULLETPROOF INITIALIZATION ---
    let useRest = false;

    if (typeof firebase !== 'undefined') {
        try {
            firebase.initializeApp(firebaseConfig);
            window.db = firebase.database();
            addLog('success', 'SYS', 'Firebase SDK: ACTIVE');
            startSdkListeners();
        } catch(e) {
            addLog('warn', 'SYS', 'SDK Load Error. Switching to REST API.');
            useRest = true;
        }
    } else {
        addLog('warn', 'SYS', 'SDK Blocked. Switching to REST API.');
        useRest = true;
    }

    if (useRest) {
        addLog('info', 'SYS', 'REST Tunnel: INITIALIZING...');
        startRestPolling();
    }

    // --- OPTION A: SDK STREAMING ---
    function startSdkListeners() {
        db.ref(".info/connected").on("value", s => {
            if (s.val()) addLog('success', 'SYS', 'Mesh: ESTABLISHED');
            else addLog('error', 'SYS', 'Mesh: LOST');
        });

        db.ref(NODE + "/Sensor").on("value", s => updateUI(s.val()));
        db.ref(NODE + "/Control").on("value", s => updateControlUI(s.val()));
        db.ref(NODE + "/Health").on("value", s => updateHealthUI(s.val()));
        db.ref(NODE + "/Predictive").on("value", s => updatePredictiveUI(s.val()));
    }

    // --- OPTION B: SERVER PROXY (SOVEREIGN SYNC) ---
    async function startRestPolling() {
        const fetchOnce = async () => {
            try {
                // Fetch from our OWN server proxy to bypass network blocks
                const url = `firebase_proxy.php?node=${NODE}`;
                const res = await fetch(url);
                if (!res.ok) {
                    addLog('error', 'SYS', `Proxy Error: HTTP ${res.status}`);
                    return;
                }
                const data = await res.json();
                if (data && !data.error) {
                    addLog('success', 'SYS', 'Sovereign Sync: ACTIVE');
                    updateUI(data.Sensor);
                    updateControlUI(data.Control);
                    updateHealthUI(data.Health);
                    updatePredictiveUI(data.Predictive);
                    
                    document.getElementById('statusPill').classList.remove('offline');
                    document.getElementById('statusText').textContent = 'SOVEREIGN-SYNC';
                    document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
                } else {
                    addLog('warn', 'SYS', `Proxy Fail: ${data.error || 'Empty Data'}`);
                }
            } catch(e) { 
                addLog('error', 'SYS', `Local Proxy Fault: ${e.message}`);
            }
        };
        fetchOnce();
        setInterval(fetchOnce, 5000); 
    }

    // --- SHARED UI UPDATERS ---
    function updateUI(d) {
        if (!d) return;
        const ldr = d.ldrData ?? null;
        let lux = null;
        if (ldr !== null) lux = Math.max(0, 100 - (ldr / 40));
        
        document.getElementById('ldrData').textContent = lux !== null ? Math.round(lux) : '--';
        if (lux !== null) {
            setBar('ldrBar', (ldr / 4095) * 100);
            setTag('ldrTag', evalTag(lux, null, null, THRESHOLDS.lux_threshold_min || 50, THRESHOLDS.lux_threshold_critical || 30));
        }

        const temp = d.temperature ?? null;
        document.getElementById('temperature').textContent = temp !== null ? temp : '--';
        if (temp !== null) {
            setBar('tempBar', (temp / 80) * 100);
            setTag('tempTag', evalTag(temp, THRESHOLDS.temperature_threshold_max || 45, THRESHOLDS.temperature_threshold_critical || 55, null, null));
        }

        const volt = d.voltage ?? null;
        document.getElementById('voltage').textContent = volt !== null ? volt : '--';
        if (volt !== null) {
            setBar('voltBar', (volt / 5) * 100);
            setTag('voltTag', evalTag(volt, null, null, THRESHOLDS.voltage_threshold_min || 2.0, THRESHOLDS.voltage_threshold_critical || 1.5));
        }

        const hum = d.humidity ?? null;
        document.getElementById('humidity').textContent = hum !== null ? hum : '--';
        if (hum !== null) {
            setBar('humBar', hum);
            setTag('humTag', evalTag(hum, THRESHOLDS.humidity_threshold_max || 80, THRESHOLDS.humidity_threshold_critical || 90, null, null));
        }
    }

    function updateControlUI(d) {
        if (!d) return;
        const mode = d.mode ?? 0;
        ['btnAuto','btnForceOn','btnForceOff'].forEach((id, i) => {
            document.getElementById(id).classList.toggle('active', mode === i);
        });
        const bright = d.brightnessPercent ?? 0;
        document.getElementById('brightnessValue').textContent = bright + '%';
        [25, 50, 75, 100].forEach(v => {
            const el = document.getElementById('dim' + v);
            if (el) el.classList.toggle('active', bright === v);
        });
    }

    function updateHealthUI(d) {
        if (!d) return;
        const setH = (id, s) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = s;
            el.className = 'health-badge ' + (s === 'OK' || s === 'NORMAL' ? 'hb-ok' : 'hb-fail');
        };
        setH('lampStatus', d.lampStatus ?? 'OK');
        setH('dhtStatus', d.dhtStatus ?? 'OK');
        setH('envTempStatus', d.envTempStatus ?? 'OK');
        document.getElementById('lampFaultCounter').textContent = d.lampFaultCounter ?? 0;
    }

    function updatePredictiveUI(d) {
        if (!d) return;
        document.getElementById('lampHealth').textContent = d.lampHealth ?? 'STABLE';
        document.getElementById('powerStability').textContent = d.powerStability ?? 'NORMAL';
        document.getElementById('maintenanceAlert').textContent = d.maintenanceAlert ?? 'No issues detected.';
    }

    // --- Helpers ---
    function setBar(id, pct) {
        const el = document.getElementById(id);
        if (el) el.style.width = Math.min(Math.max(pct,0),100) + '%';
    }
    function setTag(id, status) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = status;
        el.className = 'sensor-status-tag' + (status === 'NORMAL' ? '' : status === 'WARNING' ? ' warn' : status === 'CRITICAL' ? ' crit' : '');
    }
    function evalTag(val, warnHi, critHi, warnLo, critLo) {
        if (critHi !== null && val >= critHi) return 'CRITICAL';
        if (warnHi !== null && val >= warnHi) return 'WARNING';
        if (critLo !== null && val <= critLo) return 'CRITICAL';
        if (warnLo !== null && val <= warnLo) return 'WARNING';
        return 'NORMAL';
    }

    // ── Commands via Proxy ──
    window.setMode = function(mode) {
        const labels = {0:'AUTO', 1:'FORCE ON', 2:'FORCE OFF'};
        addLog('info','CMD', `Propagating mode → ${labels[mode]}`);
        
        fetch(`firebase_proxy.php?node=${NODE}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ path: 'Control/mode', data: mode })
        })
        .then(r => r.json())
        .then(() => addLog('success', 'ACK', `Mode confirmed: ${labels[mode]}`))
        .catch(() => addLog('error', 'ERR', 'Command failed to reach Proxy'));
    };

    window.setBrightness = function(val) {
        addLog('info','CMD', `Propagating brightness → ${val}%`);
        
        fetch(`firebase_proxy.php?node=${NODE}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ path: 'Control/brightnessPercent', data: val })
        })
        .then(r => r.json())
        .then(() => addLog('success', 'ACK', `Brightness set: ${val}%`))
        .catch(() => addLog('error', 'ERR', 'Command failed to reach Proxy'));
    };

    window.syncNow = function() {
        addLog('info','SYN', 'Requesting Firebase → MySQL sync…');
        fetch('firebase_sync.php?run=1')
            .then(() => addLog('success','ACK', 'Local database updated successfully'))
            .catch(() => addLog('error','ERR', 'Local sync failed'));
    };

    // ── Security Modal ──
    window.confirmFirebaseCommand = function(actionType, param1) {
        const modal = document.getElementById('securityModal');
        // Simple mock for authorized session
        const isAuthorized = false; 
        document.getElementById('pwdGroup').style.display = isAuthorized ? 'none' : 'block';
        document.getElementById('secModalError').style.display = 'none';
        document.getElementById('secModalPassword').value = '';
        const labels = {0:'Activate AUTO Mode',1:'Activate FORCE ON',2:'Activate FORCE OFF'};
        document.getElementById('secModalTitle').textContent = labels[param1] || 'Confirm Action';
        document.getElementById('secModalDesc').textContent =
            `This will immediately propagate the command to the ${NODE} hardware via Firebase. Confirm to proceed.`;
        modal._actionType = actionType;
        modal._param1 = param1;
        modal.classList.add('open');
        setTimeout(() => document.getElementById('secModalPassword').focus(), 100);
    };

    window.closeSecModal = function() {
        document.getElementById('securityModal').classList.remove('open');
    };

    window.confirmSecAction = async function() {
        const modal = document.getElementById('securityModal');
        const pwd = document.getElementById('secModalPassword').value;
        const body = new URLSearchParams({action:'verify_password', admin_password:pwd, csrf_token: CSRF});
        const res = await fetch('firebase_dashboard.php', {method:'POST', body});
        const data = await res.json();
        if (!data.success) {
            document.getElementById('secModalError').style.display = 'block';
            return;
        }
        if (modal._actionType === 'setMode') setMode(modal._param1);
        closeSecModal();
    };

    window.clearLog = function() {
        const feed = document.getElementById('controlLog');
        feed.innerHTML = '<div class="log-empty-state">» Cleared. Waiting for events...</div>';
    };

    document.getElementById('securityModal').addEventListener('click', function(e) {
        if (e.target === this) closeSecModal();
    });

    document.getElementById('secModalPassword').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') window.confirmSecAction();
    });
</script>
</body>
</html>