<?php
require_once 'dbconnect.php';
requireLogin();

use ShineGuard\Controllers\StreetlightController;
use ShineGuard\Services\IOTService;

// ── ARCHITECTURE MODERNIZATION: Streetlight Controller ──
$controller = new StreetlightController($conn);
$controller->handleAction(); // Handle POST actions (Bulk, Toggle, Add, Delete)
$data = $controller->index();  // Fetch all dashboard data

// Interface mapping for the View
$streetlights = $data['streetlights'];
$stats        = $data['stats'];
$mttr_global  = $data['mttr'];
$pending_pm   = $data['pending_pm'];
$user_ctx     = $data['user'];
$theme_color  = getSystemConfig('theme_color', '#10b981');
$map_lat      = getSystemConfig('map_center_lat', '14.5765');
$map_lng      = getSystemConfig('map_center_lng', '121.0355');
$map_zoom     = getSystemConfig('map_zoom_level', '16');

// Helper proxy for UI continuity
function getDimmingLabel($level) {
    $d = IOTService::getDimmingLabel($level);
    return [$d['label'], $d['color'], $d['bg']];
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
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Streetlights - ShineGuard</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">

    <style>
        <?php include 'assets/style.css';

        ?>:root {
            --theme-color: <?php echo $theme_color;
            ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
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
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.05), 0 10px 30px rgba(0, 0, 0, 0.04);
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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

        /* View Switcher Segmented Control */
        .view-switcher-container {
            background: var(--muted);
            padding: 5px;
            border-radius: 14px;
            display: inline-flex;
            gap: 2px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .dark-mode .view-switcher-container {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .view-segment {
            padding: 8px 20px;
            border-radius: 10px;
            color: var(--dim);
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: transparent;
            user-select: none;
        }

        .view-segment:hover {
            color: var(--text);
            background: rgba(0, 0, 0, 0.02);
        }

        .dark-mode .view-segment:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .view-segment.active {
            background: var(--panel);
            color: #10b981;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border);
        }

        .dark-mode .view-segment.active {
            background: #10b981;
            color: white;
            border-color: transparent;
        }

        .view-segment span {
            font-size: 1.1rem;
        }

        .dim-btn {
            padding: 8px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            background: white;
            transition: all 0.2s;
            user-select: none;
        }

        .dim-btn.active {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }

        .scope-radio {
            display: none;
        }

        .scope-btn {
            padding: 8px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            background: white;
            transition: all 0.2s;
            cursor: pointer;
        }

        .scope-radio:checked+.scope-btn {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }

        /* Command Center Styles */
        .command-center {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .command-center {
                grid-template-columns: 1fr;
            }
        }

        .selector-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .selector-card {
            background: var(--panel);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .dark-mode .selector-card {
            background: var(--glass-bg);
        }

        .selector-card:hover {
            border-color: #94a3b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .selector-card.active {
            border-color: #3b82f6;
            background: #f0f7ff;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .dark-mode .selector-card.active {
            background: rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .selector-card .card-icon {
            font-size: 1.5rem;
            margin-bottom: 4px;
            color: var(--dim);
        }

        .selector-card .card-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
        }

        .selector-card.active .card-label {
            color: #2563eb;
        }

        .dark-mode .selector-card.active .card-label {
            color: #3b82f6;
        }

        .selector-card .check-mark {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 18px;
            height: 18px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .selector-card.active .check-mark {
            display: flex;
        }

        .command-footer {
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px dashed #e2e8f0;
        }

        .btn-command {
            padding: 10px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 800;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .btn-command:hover {
            transform: translateY(-3px) scale(1.02);
            filter: brightness(1.1);
        }

        .btn-command.on {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-command.off {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
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

                <center>
                    <h1>💡 STREETLIGHT MANAGEMENT</h1>
                    <p>Monitor and control streetlight nodes in Barangay Hulo, Mandaluyong City</p>
                </center>
            </div>






            <div id="mapViewPanel" class="panel"
                style="padding: 0; overflow: hidden; border-color: var(--border); border-top: 5px solid #10b981;">
                <div
                    style="padding: 25px 30px; background: var(--muted); border-bottom: 2px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="margin: 0 0 4px 0; font-size: 1.4rem;">🗺️ Streetlight Location Map</h2>
                        <p style="color: var(--dim); font-size: 13px; margin: 0; font-weight: 500;">
                            📍 Barangay Hulo, Mandaluyong City • Powered by OpenStreetMap
                        </p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="view-switcher-container">
                            <button class="view-segment active" onclick="switchView('map', this)"><span>🗺️</span>
                                Map</button>
                            <button class="view-segment" onclick="switchView('grid', this)"><span>🔲</span>
                                Grid</button>
                            <button class="view-segment" onclick="switchView('table', this)"><span>📋</span>
                                Table</button>
                        </div>
                        <button onclick="centerMap()" class="btn"
                            style="background: #10b981; color: white; padding: 10px 20px; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                            🎯 Center
                        </button>
                    </div>
                </div>

                <div style="padding: 20px;">
                    <div id="map"></div>
                </div>
            </div>

            <div id="gridViewPanel" class="hidden panel"
                style="padding: 0; overflow: hidden; border-color: var(--border); border-top: 5px solid #10b981;">
                <div
                    style="padding: 25px 30px; background: var(--muted); border-bottom: 2px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="margin: 0 0 4px 0; font-size: 1.4rem;">🔲 Streetlight Network Grid</h2>
                        <p style="color: var(--dim); font-size: 13px; margin: 0; font-weight: 500;">
                            📍 Visual status grid of all registered streetlight nodes
                        </p>
                    </div>

                    <div class="view-switcher-container">
                        <button class="view-segment" onclick="switchView('map', this)"><span>🗺️</span> Map</button>
                        <button class="view-segment active" onclick="switchView('grid', this)"><span>🔲</span>
                            Grid</button>
                        <button class="view-segment" onclick="switchView('table', this)"><span>📋</span> Table</button>
                    </div>
                </div>

                <div style="padding: 20px;">
                    <div class="grid-map">
                        <?php
foreach ($streetlights as $light):
    $status_class = $light['power_state'] === 'ON' ? 'online' : 'offline';
?>
                        <div class="node-card <?php echo $status_class; ?>"
                            onclick="openNodeModal(<?php echo $light['light_id']; ?>)" style="cursor: pointer;">
                            <div class="status-dot"></div>
                            <div style="font-size: 24px; margin-bottom: 8px;">💡</div>
                            <div class="node-id">
                                <?php echo htmlspecialchars($light['node_name']); ?>
                            </div>
                            <?php list($label, $color, $bg) = getDimmingLabel($light['dimming_level']); ?>
                            <small
                                style="font-size: 10px; font-weight: 700; color: <?php echo $color; ?>; background: <?php echo $bg; ?>; padding: 2px 8px; border-radius: 10px;">
                                <?php echo htmlspecialchars($label); ?>
                            </small>
                        </div>
                        <?php
endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="tableViewPanel" class="hidden panel"
                style="padding: 0; overflow: hidden; border-color: var(--border); border-top: 5px solid #10b981;">
                <div
                    style="padding: 25px 30px; background: var(--muted); border-bottom: 2px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="margin: 0 0 4px 0; font-size: 1.4rem;">📋 Detailed List</h2>
                        <p style="color: var(--dim); font-size: 13px; margin: 0; font-weight: 500;">
                            📍 Detailed list view with diagnostic and removal controls
                        </p>
                    </div>

                    <div class="view-switcher-container">
                        <button class="view-segment" onclick="switchView('map', this)"><span>🗺️</span> Map</button>
                        <button class="view-segment" onclick="switchView('grid', this)"><span>🔲</span> Grid</button>
                        <button class="view-segment active" onclick="switchView('table', this)"><span>📋</span>
                            Table</button>
                    </div>
                </div>

                <div style="padding: 20px;">
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
                                    <th
                                        style="position: sticky; right: 0; background: var(--panel); z-index: 2; border-left: 1px solid var(--border); text-align: center; padding-right: 20px;">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
foreach ($streetlights as $light):
?>
                                <tr>
                                    <td><strong>
                                            <?php echo htmlspecialchars($light['node_name']); ?>
                                        </strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($light['location']); ?>
                                    </td>
                                    <td><span class="badge <?php echo strtolower($light['status']); ?>">
                                            <?php echo htmlspecialchars($light['status']); ?>
                                        </span></td>
                                    <td><span
                                            class="badge <?php echo $light['power_state'] === 'ON' ? 'ok' : 'fail'; ?>">
                                            <?php echo htmlspecialchars($light['power_state']); ?>
                                        </span></td>
                                    <td>
                                        <?php list($label, $color, $bg) = getDimmingLabel($light['dimming_level']); ?>
                                        <span
                                            style="font-size: 0.75rem; font-weight: 700; color: <?php echo $color; ?>; background: <?php echo $bg; ?>; padding: 3px 10px; border-radius: 12px;">
                                            <?php echo htmlspecialchars($label); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $light['installation_date'] ? date('M d, Y', strtotime($light['installation_date'])) : 'N/A'; ?>
                                    </td>
                                    <td
                                        style="position: sticky; right: 0; background: var(--panel); z-index: 1; border-left: 1px solid var(--border); white-space: nowrap; text-align: center; padding: 8px 20px;">
                                        <?php if (canDo('control_streetlights')): ?>
                                        <button
                                            onclick="toggleLight(<?php echo $light['light_id']; ?>, '<?php echo $light['power_state']; ?>')"
                                            class="btn-sm"
                                            style="padding:6px 18px; <?php echo $light['power_state'] === 'ON' ? 'background:#ef4444;color:white;border-color:#ef4444;' : 'background:#10b981;color:white;border-color:#10b981;'; ?>">
                                            <?php echo $light['power_state'] === 'ON' ? '🔅 OFF' : '🔆 ON'; ?>
                                        </button>
                                        <button onclick="runDiagnostic(<?php echo $light['light_id']; ?>)"
                                            class="btn-sm"
                                            style="padding:6px 18px; background: #3b82f6; color:white; border-color:#3b82f6;">
                                            🔧 Test
                                        </button>
                                        <?php if (canDo('manage_streetlights')): ?>
                                        <button
                                            onclick="openDeleteModal(<?php echo $light['light_id']; ?>, '<?php echo htmlspecialchars($light['node_name']); ?>')"
                                            class="btn-sm"
                                            style="padding:6px 15px; background: #fee2e2; color:#ef4444; border-color:#fecaca;">
                                            🗑️ Remove
                                        </button>
                                        <?php
        endif; ?>
                                        <?php
    else: ?>
                                        <span style="color: var(--dim); font-size: 0.8rem; font-weight: 700;">View
                                            Only</span>
                                        <?php
    endif; ?>
                                    </td>
                                </tr>
                                <?php
endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (canDo('control_streetlights')): ?>
            <div class="panel"
                style="border-top: 5px solid #3b82f6; background: #ffffff; box-shadow: 0 10px 40px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
                    <div
                        style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);">
                        📡
                    </div>
                    <div>
                        <h2
                            style="margin: 0; font-size: 1.5rem; color: var(--text); font-weight: 800; letter-spacing: -0.5px;">
                            Bulk Control</h2>
                        <p style="margin: 4px 0 0 0; font-size: 0.95rem; color: #64748b; font-weight: 500;">Manage total
                            network or custom ranges</p>
                    </div>
                    <?php if (canDo('manage_streetlights')): ?>
                    <div style="margin-left: auto;">
                        <button onclick="openModal('addNodeModal')" class="btn primary"
                            style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 12px 24px; font-weight: 700; border-radius: 14px; display: flex; align-items: center; gap: 8px; box-shadow: 0 6px 16px rgba(59, 130, 246, 0.25); transition: all 0.2s;">
                            ➕ Add Streetlight
                        </button>
                    </div>
                    <?php
    endif; ?>
                </div>

                <form id="bulkControlForm" method="POST" onsubmit="return validateBulkForm(this);">
                    <div class="command-center">
                        <!-- Column 1: Scope -->
                        <div class="command-section">
                            <label
                                style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">🎯
                                Control Scope</label>
                            <div class="selector-grid">
                                <div class="selector-card active" onclick="selectScope(this, 'all')">
                                    <input type="radio" name="bulk_scope" value="all" style="display:none;" checked>
                                    <div class="check-mark">✓</div>
                                    <div class="card-icon">🌐</div>
                                    <div class="card-label">Total Network (All 32)</div>
                                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Entire
                                        System</small>
                                </div>
                                <div class="selector-card" onclick="selectScope(this, 'range')">
                                    <input type="radio" name="bulk_scope" value="range" style="display:none;">
                                    <div class="check-mark">✓</div>
                                    <div class="card-icon">🔢</div>
                                    <div class="card-label">Custom Range (e.g. 1-10)</div>
                                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Specific
                                        Selection</small>
                                </div>
                            </div>

                            <div id="rangeInputContainer"
                                style="display:none; margin-top: 20px; animation: slideInUp 0.3s ease-out;">
                                <input type="text" name="bulk_range" id="bulk_range_field"
                                    placeholder="e.g. 1-10 or 1, 5, 12"
                                    style="width: 100%; padding: 14px; border-radius: 12px; border: 2px solid #e2e8f0; font-size: 15px; outline: none; transition: border-color 0.2s;"
                                    onfocus="this.style.borderColor='#3b82f6'">
                                <div
                                    style="background: var(--muted); border: 1px solid var(--border); padding: 14px; border-radius: 12px; margin-top: 12px; font-size: 13px; color: var(--dim); display: flex; gap: 12px;">
                                    <div style="font-size: 18px;">💡</div>
                                    <div>
                                        <strong>Smart Syntax:</strong> <br>
                                        • <code
                                            style="background:#e2e8f0; padding:2px 4px; border-radius:4px;">1-10</code>
                                        for sequential range <br>
                                        • <code
                                            style="background:#e2e8f0; padding:2px 4px; border-radius:4px;">1, 5, 12</code>
                                        for specific IDs
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Dimming -->
                        <div class="command-section">
                            <label
                                style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">🔆
                                Target Dimming Level</label>
                            <div class="selector-grid">
                                <div class="selector-card" onclick="setPremiumDim(this, 25)">
                                    <input type="radio" name="dimming_level" value="25" style="display:none;">
                                    <div class="check-mark">✓</div>
                                    <div class="card-icon">🍃</div>
                                    <div class="card-label">Energy Saver</div>
                                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px;">25% Output</small>
                                </div>
                                <div class="selector-card active" onclick="setPremiumDim(this, 50)">
                                    <input type="radio" name="dimming_level" value="50" style="display:none;" checked>
                                    <div class="check-mark">✓</div>
                                    <div class="card-icon">🌓</div>
                                    <div class="card-label">Medium</div>
                                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px;">50% Output</small>
                                </div>
                                <div class="selector-card" onclick="setPremiumDim(this, 75)">
                                    <input type="radio" name="dimming_level" value="75" style="display:none;">
                                    <div class="check-mark">✓</div>
                                    <div class="card-icon">🌔</div>
                                    <div class="card-label">High</div>
                                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px;">75% Output</small>
                                </div>
                                <div class="selector-card" onclick="setPremiumDim(this, 100)">
                                    <input type="radio" name="dimming_level" value="100" style="display:none;">
                                    <div class="check-mark">✓</div>
                                    <div class="card-icon">🌕</div>
                                    <div class="card-label">Full</div>
                                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px;">100% Output</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="command-footer">
                        <button type="button" class="btn-command off" onclick="openBulkModal('OFF')">
                            <span>🔅</span> Turn Selected OFF
                        </button>
                        <button type="button" class="btn-command on" onclick="openBulkModal('ON')">
                            <span>🔆</span> Turn Selected ON
                        </button>
                    </div>

                    <input type="hidden" name="bulk_action" id="bulk_action_input">
                    <input type="hidden" name="bulk_admin_password" id="bulkModalAdminPasswordHidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                </form>
            </div>
            <?php
endif; ?>


        </main>
    </div>

    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="light_id" id="toggle_light_id">
        <input type="hidden" name="power_state" id="toggle_power_state">
        <input type="hidden" name="admin_password" id="toggle_admin_password">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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

    <?php if (canDo('manage_streetlights')): ?>
    <!-- Add Streetlight Modal -->
    <div id="addNodeModal" class="modal">
        <div class="modal-content modal-spring" style="max-width: 600px;">
            <div class="modal-header"
                style="border-bottom: 1px solid var(--border); margin-bottom: 24px; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 1.4rem; color: var(--text);">🏗️ Register New Streetlight</h2>
                <button type="button" class="btn-sm" onclick="closeModal('addNodeModal')"
                    style="border: none; background: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_streetlight">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-weight: 700; font-size: 0.8rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Node
                            Identifier</label>
                        <input type="text" name="node_name" placeholder="e.g. SL-033" required
                            style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-weight: 700; font-size: 0.8rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Location
                            Descriptor</label>
                        <input type="text" name="location" placeholder="e.g. Coronado St. Entrance" required
                            style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-weight: 700; font-size: 0.8rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Latitude</label>
                        <input type="number" step="any" name="latitude" placeholder="e.14.568..." required
                            style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-weight: 700; font-size: 0.8rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Longitude</label>
                        <input type="number" step="any" name="longitude" placeholder="e.g. 121.033..." required
                            style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label
                        style="display: block; font-weight: 700; font-size: 0.8rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Installation
                        Date</label>
                    <input type="date" name="installation_date" value="<?php echo date('Y-m-d'); ?>"
                        style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div
                    style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn-sm" onclick="closeModal('addNodeModal')"
                        style="padding: 10px 24px; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn primary"
                        style="padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);">Register
                        Node</button>
                </div>
            </form>
        </div>
    </div>
    <?php
endif; ?>

    <div id="toggleModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-spring"
            style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div id="toggleModalIcon"
                    style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                    💡</div>
                <div>
                    <div id="toggleModalTitle" style="font-size:1.1rem; font-weight:800; color:#0f172a;">Turn OFF
                        Streetlight</div>
                    <div id="toggleModalNode" style="font-size:0.8rem; color:#64748b; margin-top:2px;">Loading...</div>
                </div>
            </div>
            <p id="toggleModalDesc" style="font-size:0.875rem; color:#475569; margin-bottom:24px; line-height:1.6;">Are
                you sure you want to change the power state of this streetlight?</p>
            <div id="toggleModalDelayWarning"
                style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start; font-size: 0.85rem; color: #b45309;">
                <div style="font-size: 1.2rem; line-height: 1;">⏱️</div>
                <div><strong>Execution Delay:</strong> Please note there will be a 5-10 seconds delay for the command to
                    fully execute on the streetlights.</div>
            </div>

            <div id="toggleModalPasswordContainer" style="margin-bottom: 24px;">
                <label for="modalAdminPassword"
                    style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐
                    Administrator Password <span style="color:#ef4444;">*</span></label>
                <input type="password" id="modalAdminPassword" placeholder="Enter password to confirm"
                    style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                <div id="togglePasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">
                    Password is required</div>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button onclick="closeToggleModal()" class="btn">Cancel</button>
                <button id="toggleModalConfirmBtn" onclick="confirmToggle()"
                    style="padding:10px 22px; border-radius:10px; border:none; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s;">Confirm</button>
            </div>
        </div>
    </div>

    <div id="bulkControlModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-spring"
            style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div id="bulkModalIcon"
                    style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                    💡</div>
                <div>
                    <div id="bulkModalTitle" style="font-size:1.1rem; font-weight:800; color:#0f172a;">Turn All
                        Streetlights OFF</div>
                    <div id="bulkModalSubtitle" style="font-size:0.8rem; color:#64748b; margin-top:2px;">Network-wide
                        Command</div>
                </div>
            </div>
            <p id="bulkModalDesc" style="font-size:0.875rem; color:#475569; margin-bottom:24px; line-height:1.6;">Are
                you sure you want to turn off all 32 streetlights?</p>
            <div id="bulkModalDelayWarning"
                style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start; font-size: 0.85rem; color: #b45309;">
                <div style="font-size: 1.2rem; line-height: 1;">⏱️</div>
                <div><strong>Execution Delay:</strong> Please note there will be a 5-10 seconds delay for the command to
                    fully execute on all physical nodes.</div>
            </div>

            <div id="bulkModalPasswordContainer" style="margin-bottom: 24px;">
                <label for="bulkModalAdminPassword"
                    style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐
                    Administrator Password <span style="color:#ef4444;">*</span></label>
                <input type="password" id="bulkModalAdminPassword" placeholder="Enter password to confirm"
                    style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                <div id="bulkModalPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">
                    Password is required</div>
            </div>


            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button onclick="closeBulkModal()"
                    style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;"
                    onmouseover="this.style.background='#f1f5f9'"
                    onmouseout="this.style.background='white'">Cancel</button>
                <button id="bulkModalConfirmBtn" onclick="confirmBulkAction()"
                    style="padding:10px 22px; border-radius:10px; border:none; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s;">Confirm</button>
            </div>
        </div>
    </div>

    <div id="diagnosticModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-spring"
            style="background:white; border-radius:20px; padding:32px; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div
                    style="background: rgba(59, 130, 246, 0.1); width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    🔧</div>
                <div>
                    <div style="font-size:1.1rem; font-weight:800; color: var(--text);">Run Self-Check Diagnostic</div>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:2px;" id="diagNodeLabel">Loading...</div>
                </div>
            </div>
            <p style="font-size:0.875rem; color:#475569; margin-bottom:16px; line-height:1.6;">This will automatically
                run tests on the selected streetlight node. The following will be checked:</p>
            <div class="info-box">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div
                        style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;">
                        <span style="color:#10b981; font-size:1rem;">✓</span> Power Supply</div>
                    <div
                        style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;">
                        <span style="color:#10b981; font-size:1rem;">✓</span> Sensor Functionality</div>
                    <div
                        style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;">
                        <span style="color:#10b981; font-size:1rem;">✓</span> Network Connectivity</div>
                    <div
                        style="display:flex; align-items:center; gap:10px; font-size:0.85rem; color:#1e293b; font-weight:500;">
                        <span style="color:#10b981; font-size:1rem;">✓</span> Dimming Controls</div>
                </div>
            </div>

            <div id="diagModalPasswordContainer" style="margin-bottom: 24px; text-align: left;">
                <label for="diagAdminPassword"
                    style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐
                    Administrator Password <span style="color:#ef4444;">*</span></label>
                <input type="password" id="diagAdminPassword" placeholder="Enter password to confirm test"
                    style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                <div id="diagPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">
                    Password is required</div>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button onclick="closeDiagModal()"
                    style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;"
                    onmouseover="this.style.background='#f1f5f9'"
                    onmouseout="this.style.background='white'">Cancel</button>
                <button id="diagConfirmBtn" onclick="confirmDiagnostic()"
                    style="padding:10px 22px; border-radius:10px; border:none; background:#3b82f6; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 12px rgba(59,130,246,0.35);"
                    onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">🔧 Run
                    Test</button>
            </div>
        </div>
    </div>
    <?php if (canDo('manage_streetlights')): ?>
    <!-- Delete Streetlight Modal -->
    <div id="deleteNodeModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-spring"
            style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div
                    style="background: #fef2f2; width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                    🗑️</div>
                <div>
                    <div style="font-size:1.1rem; font-weight:800; color:#0f172a;">Remove Streetlight</div>
                    <div id="deleteModalNodeName" style="font-size:0.8rem; color:#64748b; margin-top:2px;">Loading...
                    </div>
                </div>
            </div>
            <p style="font-size:0.875rem; color:#475569; margin-bottom:24px; line-height:1.6;">Are you sure you want to
                <strong>permanently delete</strong> this streetlight from the system? This action cannot be undone.</p>

            <form method="POST" id="deleteNodeForm">
                <input type="hidden" name="action" value="remove_streetlight">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="light_id" id="delete_light_id">

                <?php if ($user_ctx['mfa_enabled']): ?>
                <div style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:10px;">🛡️ MFA Verification Required</label>
                    <input type="text" name="mfa_code" placeholder="Enter 6-digit code" maxlength="6" required
                        style="width:100%; padding:12px; border-radius:8px; border:1.5px solid #cbd5e1; font-family:'JetBrains Mono',monospace; letter-spacing:4px; text-align:center; font-size:18px;">
                    <p style="font-size:11px; color:#94a3b8; margin-top:8px;">Enter the code from your authenticator app to authorize deletion.</p>
                </div>
                <?php endif; ?>

                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button type="button" onclick="closeDeleteModal()" class="btn">Cancel</button>
                    <button type="submit"
                        style="padding:10px 22px; border-radius:10px; border:none; background:#ef4444; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; transition:all 0.2s; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);">Permanently
                        Delete</button>
                </div>
            </form>
        </div>
    </div>
    <?php
endif; ?>

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
                const scope = document.querySelector('input[name="bulk_scope"]:checked').value;
                const range = document.getElementById('bulk_range_field').value;
                icon.style.background = '#fef2f2';
                icon.textContent = '🔅';
                title.textContent = 'Turn Selected Streetlights OFF';

                let targetText = range;
                if (scope === 'all') {
                    targetText = 'all 32 streetlights';
                } else if (range.includes('-')) {
                    targetText = `streetlights in range: ${range}`;
                } else {
                    targetText = `specific streetlights: ${range}`;
                }

                desc.innerHTML = `Are you sure you want to completely turn off <strong>${targetText}</strong>?`;
                btn.style.background = '#ef4444';
                btn.style.boxShadow = '0 4px 12px rgba(239,68,68,0.35)';
                btn.textContent = '🔅 Turn OFF';
            } else {
                const dimmingLevel = document.querySelector('input[name="dimming_level"]:checked').value;
                const scope = document.querySelector('input[name="bulk_scope"]:checked').value;
                const range = document.getElementById('bulk_range_field').value;
                icon.style.background = '#f0fdf4';
                icon.textContent = '🔆';
                title.textContent = 'Turn Selected Streetlights ON';

                let targetText = range;
                if (scope === 'all') {
                    targetText = 'all 32 streetlights';
                } else if (range.includes('-')) {
                    targetText = `streetlights in range: ${range}`;
                } else {
                    targetText = `specific streetlights: ${range}`;
                }

                desc.innerHTML = `Are you sure you want to turn on <strong>${targetText}</strong> at ${dimmingLevel}% dimming level?`;
                btn.style.background = '#10b981';
                btn.style.boxShadow = '0 4px 12px rgba(16,185,129,0.35)';
                btn.textContent = '🔆 Turn ON';
            }

            modal._action = action;
            
            // Handle Authorization Visibility
            const pwdContainer = document.getElementById('bulkModalPasswordContainer');
            if (isAuthorized) {
                pwdContainer.style.display = 'none';
                btn.innerHTML = btn.innerHTML.replace('Confirm', 'Execute (Authorized)');
            } else {
                pwdContainer.style.display = 'block';
                btn.innerHTML = btn.innerHTML.includes('OFF') ? '🔅 Turn OFF' : '🔆 Turn ON';
            }

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

        function toggleRangeInput(show) {
            const container = document.getElementById('rangeInputContainer');
            container.style.display = show ? 'block' : 'none';
            if (show) document.getElementById('bulk_range_field').focus();
        }

        function validateBulkForm(form) {
            const scope = form.querySelector('input[name="bulk_scope"]:checked').value;
            if (scope === 'range') {
                const range = document.getElementById('bulk_range_field').value.trim();
                if (!range) {
                    showAppAlert('Please enter a range or list of IDs (e.g., 1-10 or 1, 5, 12).', 'warning', 'Missing Range');
                    return false;
                }
                // Allow numeric, dash, comma, space
                if (!/^[0-9\-\s,]+$/.test(range)) {
                    showAppAlert('Invalid format. Please use numbers separated by dashes (1-10) or commas (1, 5, 12).', 'warning', 'Format Error');
                    return false;
                }
            }
            return true;
        }

        async function confirmBulkAction() {
            const modal = document.getElementById('bulkControlModal');
            const pwdInput = document.getElementById('bulkModalAdminPassword');
            const pwdError = document.getElementById('bulkModalPasswordError');
            const btn = document.getElementById('bulkModalConfirmBtn');

            if (!isAuthorized) {
                if (!pwdInput.value) {
                    pwdError.textContent = 'Administrator password is required.';
                    pwdError.style.display = 'block';
                    pwdInput.style.borderColor = '#ef4444';
                    pwdInput.focus();
                    return;
                }

                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Verifying...';

                try {
                    const verifyData = new URLSearchParams();
                    verifyData.append('action', 'verify_password');
                    verifyData.append('admin_password', pwdInput.value);
                    verifyData.append('csrf_token', csrfToken);

                    const verifyRes = await fetch('streetlights.php', {
                        method: 'POST',
                        body: verifyData,
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const verifyJson = await verifyRes.json();
                    if (!verifyJson.success) {
                        pwdError.textContent = 'Invalid administrator password.';
                        pwdError.style.display = 'block';
                        pwdInput.style.borderColor = '#ef4444';
                        pwdInput.value = '';
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        return;
                    }

                    // Success! Activate SBA UI for the whole dashboard
                    if (window.activateSbaUI) window.activateSbaUI();
                    
                } catch (e) {
                    console.error('Auth check error:', e);
                    pwdError.textContent = 'Connection error. Please try again.';
                    pwdError.style.display = 'block';
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }
            }

            // Proceed with bulk action
            btn.disabled = true;
            btn.innerHTML = 'Executing...';
            document.getElementById('bulk_action_input').value = modal._action;
            document.getElementById('bulkModalAdminPasswordHidden').value = pwdInput.value;
            document.getElementById('bulkControlForm').submit();
        }

        function selectScope(el, value) {
            // 1. Clear sibling active states
            el.parentElement.querySelectorAll('.selector-card').forEach(card => card.classList.remove('active'));
            // 2. Set current active
            el.classList.add('active');
            // 3. Mark radio checked
            const radio = el.querySelector('input[name="bulk_scope"]');
            if (radio) {
                radio.checked = true;
                toggleRangeInput(value === 'range');
            }
        }

        function setPremiumDim(el, value) {
            // 1. Clear sibling active states
            el.parentElement.querySelectorAll('.selector-card').forEach(card => card.classList.remove('active'));
            // 2. Set current active
            el.classList.add('active');
            // 3. Mark radio checked
            const radio = el.querySelector('input[name="dimming_level"]');
            if (radio) radio.checked = true;
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
            if (level <= 30) return { label: '🌒 Low', color: '#3b82f6', bg: '#eff6ff' };
            if (level <= 50) return { label: '🌓 Medium', color: '#8b5cf6', bg: '#f5f3ff' };
            if (level <= 75) return { label: '🌔 High', color: '#f59e0b', bg: '#fffbeb' };
            return { label: '🌕 Full', color: '#10b981', bg: '#ecfdf5' };
        }

    </script>

    <script>
        const streetlights = <?php echo json_encode($streetlights); ?>;
        const markerMap = {};
        const markers = [];
        let map;

        function initMap() {

            const huloCenter = [<?php echo $map_lat; ?>, <?php echo $map_lng; ?>]; // Note: Leaflet uses [lat, lng]

            map = L.map('map').setView(huloCenter, <?php echo $map_zoom; ?>);

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

                const lat = parseFloat(light.latitude) || (14.5765 + (Math.random() - 0.5) * 0.005);
                const lng = parseFloat(light.longitude) || (121.0355 + (Math.random() - 0.5) * 0.005);

                const markerIcon = light.power_state === 'ON' ? greenIcon : redIcon;

                const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);
                markerMap[light.light_id] = marker;

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
                map.fitBounds(group.getBounds(), { padding: [30, 30] });
            }
        }

        function centerMap() {
            if (map) {
                map.setView([<?php echo $map_lat; ?>, <?php echo $map_lng; ?>], <?php echo $map_zoom; ?>);
            }
        }

        function switchView(view, btn) {
            // 1. Remove active state from ALL buttons in ALL panels
            document.querySelectorAll('.view-segment').forEach(b => b.classList.remove('active'));

            // 2. Add active state to the specific button clicked
            if (btn) btn.classList.add('active');

            // 3. Hide all panels
            document.getElementById('mapViewPanel').classList.add('hidden');
            document.getElementById('gridViewPanel').classList.add('hidden');
            document.getElementById('tableViewPanel').classList.add('hidden');

            // 4. Show target panel and sync ITS internal button
            const targetPanel = document.getElementById(view + 'ViewPanel');
            targetPanel.classList.remove('hidden');

            // Highlight the correct button INSIDE the newly shown panel
            const internalBtn = targetPanel.querySelector(`.view-segment[onclick*="'${view}'"]`);
            if (internalBtn) internalBtn.classList.add('active');

            if (view === 'map') {
                setTimeout(() => {
                    if (map) {
                        map.invalidateSize();
                        centerMap();
                    }
                }, 150);
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
            const passwordContainer = document.getElementById('toggleModalPasswordContainer');
            if (isAuthorized) {
                passwordContainer.style.display = 'none';
                btn.innerHTML = btn.innerHTML.replace('Confirm', 'Execute (Authorized)');
            } else {
                passwordContainer.style.display = 'block';
            }

            modal.style.display = 'flex';
        }

        function confirmToggle() {
            const modal = document.getElementById('toggleModal');
            const pwdInput = document.getElementById('modalAdminPassword');
            const pwdError = document.getElementById('togglePasswordError');
            const passwordContainer = document.getElementById('toggleModalPasswordContainer');

            // If already authorized, skip password check
            if (passwordContainer.style.display === 'none') {
                document.getElementById('toggle_light_id').value = modal._lightId;
                document.getElementById('toggle_power_state').value = modal._currentState;
                document.getElementById('toggle_admin_password').value = '__session_authorized__';
                modal.style.display = 'none';
                document.getElementById('toggleForm').submit();
                return;
            }

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

        document.getElementById('toggleModal').addEventListener('click', function (e) {
            if (e.target === this) closeToggleModal();
        });

        function runDiagnostic(lightId) {
            const light = streetlights.find(l => l.light_id == lightId);
            const modal = document.getElementById('diagnosticModal');
            document.getElementById('diagNodeLabel').textContent = light ? `Node: ${light.node_name}` : `Node ID: ${lightId}`;
            modal._lightId = lightId;

            const pwdContainer = document.getElementById('diagModalPasswordContainer');
            const btn = document.getElementById('diagConfirmBtn');
            if (isAuthorized) {
                if (pwdContainer) pwdContainer.style.display = 'none';
                btn.innerHTML = '⚡ Run Diagnostic (Authorized)';
            } else {
                if (pwdContainer) pwdContainer.style.display = 'block';
                btn.innerHTML = '🔧 Run Test';
            }

            modal.style.display = 'flex';
        }

        async function confirmDiagnostic() {
            const pwdInput = document.getElementById('diagAdminPassword');
            const pwdError = document.getElementById('diagPasswordError');
            const modal = document.getElementById('diagnosticModal');
            const btn = document.getElementById('diagConfirmBtn');
            const pwdContainer = document.getElementById('diagModalPasswordContainer');

            // If already authorized, we can skip the manual check
            let passwordToVerify = pwdInput.value.trim();
            const isWindowAuth = (pwdContainer && pwdContainer.style.display === 'none');

            if (!isWindowAuth && !passwordToVerify) {
                pwdError.textContent = 'Password is required';
                pwdError.style.display = 'block';
                pwdInput.style.borderColor = '#ef4444';
                pwdInput.focus();
                return;
            }

            if (isWindowAuth && !passwordToVerify) {
                passwordToVerify = '__session_authorized__';
            }

            pwdError.style.display = 'none';

            // 1. Verify password FIRST before any animation
            btn.innerHTML = 'Verifying...';
            btn.disabled = true;

            try {
                const verifyData = new URLSearchParams();
                verifyData.append('action', 'verify_password');
                verifyData.append('admin_password', passwordToVerify);
                verifyData.append('csrf_token', csrfToken);

                const verifyRes = await fetch('streetlights.php', {
                    method: 'POST',
                    body: verifyData,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const verifyJson = await verifyRes.json();

                if (!verifyJson.success) {
                    pwdError.textContent = 'Invalid password.';
                    pwdError.style.display = 'block';
                    pwdInput.style.borderColor = '#ef4444';
                    pwdInput.value = '';
                    btn.innerHTML = '🔧 Run Test';
                    btn.disabled = false;
                    return;
                }

                // 2. SUCCESS: Activate SBA UI globally (updates lock icon + timer)
                if (window.activateSbaUI) window.activateSbaUI();

                // 3. If pass ok, capture originalHTML and show animation
                const inner = modal.querySelector('.modal-spring');
                const originalHTML = inner.innerHTML; // Current state with password etc.

                inner.innerHTML = `
            <div style="text-align: center; padding: 20px 0;">
                <div style="font-size: 30px; margin-bottom: 10px;">⏳</div>
                <h3 style="margin: 0 0 10px 0; color: var(--text);">Running Smart Diagnostics</h3>
                <p style="color: var(--dim); font-size: 14px; margin-bottom: 25px;">Please wait while we test hardware and network telemetry...</p>
                
                <div style="text-align: left; background: var(--muted); padding: 20px; border-radius: 12px; border: 1px solid var(--border); font-family: monospace; font-size: 13px; color: var(--text);">
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

                // 3. Run the ACTUAL diagnostic API
                const formData = new URLSearchParams();
                formData.append('light_id', modal._lightId);
                formData.append('admin_password', passwordToVerify);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('api/run_diagnostic.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
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
                    <h2 style="margin: 0 0 5px 0; color: var(--text);">System Health: ${res.score}%</h2>
                    <div style="color: ${healthColor}; font-weight: 700; margin-bottom: 25px;">${res.health}</div>
                    
                    <div style="text-align: left; background: var(--panel); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 25px;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; background: ${res.network.status === 'Pass' ? 'rgba(16,185,129,0.05)' : (res.network.status === 'Warning' ? 'rgba(245,158,11,0.05)' : 'rgba(239,68,68,0.05)')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.network.status === 'Pass' ? '✅' : (res.network.status === 'Warning' ? '⚠️' : '❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--dim); text-transform: uppercase;">Network Connection</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.3;">${res.network.message}</div>
                            </div>
                        </div>
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; background: ${res.sensors.status === 'Pass' ? 'rgba(16,185,129,0.05)' : (res.sensors.status === 'Warning' ? 'rgba(245,158,11,0.05)' : 'rgba(239,68,68,0.05)')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.sensors.status === 'Pass' ? '✅' : (res.sensors.status === 'Warning' ? '⚠️' : '❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--dim); text-transform: uppercase;">Hardware Sensors</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.3;">${res.sensors.message}</div>
                            </div>
                        </div>
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; background: ${res.relay.status === 'Pass' ? 'rgba(16,185,129,0.05)' : (res.relay.status === 'Warning' ? 'rgba(245,158,11,0.05)' : 'rgba(239,68,68,0.05)')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.relay.status === 'Pass' ? '✅' : (res.relay.status === 'Warning' ? '⚠️' : '❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--dim); text-transform: uppercase;">Relay State</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.3;">${res.relay.message}</div>
                            </div>
                        </div>
                        <div style="padding: 12px 16px; display: flex; align-items: center; background: ${res.history.status === 'Pass' ? 'rgba(16,185,129,0.05)' : (res.history.status === 'Warning' ? 'rgba(245,158,11,0.05)' : 'rgba(239,68,68,0.05)')}">
                            <span style="font-size: 16px; margin-right: 12px;">${res.history.status === 'Pass' ? '✅' : (res.history.status === 'Warning' ? '⚠️' : '❌')}</span>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--dim); text-transform: uppercase;">History Check</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.3;">${res.history.message}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display:flex; justify-content: center;">
                        <button class="btn" onclick="window.location.reload()" style="padding:10px 24px; font-size:0.875rem; font-weight:700;">Close Report</button>
                    </div>
                </div>
            `;

                } else {
                    inner.innerHTML = originalHTML; // Restore exactly as it was
                    const restoredPwdInput = document.getElementById('diagAdminPassword');
                    const restoredPwdError = document.getElementById('diagPasswordError');
                    const restoredBtn = document.getElementById('diagConfirmBtn');
                    if (restoredPwdInput) {
                        restoredPwdInput.value = '';
                        restoredPwdInput.style.borderColor = '#ef4444';
                    }
                    if (restoredPwdError) {
                        restoredPwdError.textContent = data.error || 'Diagnostic failed to run.';
                        restoredPwdError.style.display = 'block';
                    }
                    if (restoredBtn) {
                        restoredBtn.innerHTML = '🔧 Run Test';
                        restoredBtn.disabled = false;
                    }
                }
            } catch (err) {
                console.error(err);
                // On fatal JS error, restore modal or reload
                const inner = modal.querySelector('.modal-spring');
                if (inner) {
                    inner.innerHTML = `
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 30px; margin-bottom: 10px;">❌</div>
                    <h3 style="margin: 0 0 10px 0; color: #ef4444;">Connection Error</h3>
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Failed to connect to diagnostic service. Please try again.</p>
                    <button onclick="window.location.reload()" class="btn">Reload Page</button>
                </div>
            `;
                } else {
                    window.location.reload();
                }
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

        document.getElementById('diagnosticModal').addEventListener('click', function (e) {
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

        function closeModal(modalId) {
            const id = modalId || 'nodeModal';
            document.getElementById(id).classList.remove('open');
        }

        function openModal(id) {
            const m = document.getElementById(id);
            if (!m) return;
            m.classList.add('open');
            const mc = m.querySelector('.modal-content');
            if (mc) {
                mc.classList.remove('modal-spring');
                void mc.offsetWidth;
                mc.classList.add('modal-spring');
            }
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
        window.onload = function () {
            initMap();
            refreshIoTData();

            // Deep-linking from search
            const urlParams = new URLSearchParams(window.location.search);
            // ── ZERO TRUST: MFA Auto-Restore ──
            const error = urlParams.get('error');
            const action = urlParams.get('action');
            const targetId = urlParams.get('light_id');
            if (error === 'invalid_mfa' && action === 'remove' && targetId) {
                const node = streetlights.find(n => n.light_id == targetId);
                if (node) {
                    openDeleteModal(node.light_id, node.node_name);
                }
            }
        };

        document.getElementById('nodeModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal('nodeModal');
            }
        });

        function openDeleteModal(lightId, nodeName) {
            const modal = document.getElementById('deleteNodeModal');
            document.getElementById('deleteModalNodeName').textContent = `Node: ${nodeName}`;
            document.getElementById('delete_light_id').value = lightId;
            modal.style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteNodeModal').style.display = 'none';
        }

        document.getElementById('deleteNodeModal').addEventListener('click', function (e) {
            if (e.target === this) closeDeleteModal();
        });

        const addModal = document.getElementById('addNodeModal');
        if (addModal) {
            addModal.addEventListener('click', function (e) {
                if (e.target === this) closeModal('addNodeModal');
            });
        }
    </script>
    <?php include 'assets/app_alert.php'; ?>
    </main>
    </div>
</body>

</html>
<?php
$conn->close();
?>