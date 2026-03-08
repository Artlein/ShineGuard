<?php
require_once 'dbconnect.php';
requireLogin();

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM streetlights WHERE power_state = 'ON' AND status = 'Active') as online,
    (SELECT COUNT(*) FROM streetlights) as total,
    (SELECT COUNT(*) FROM alerts WHERE status = 'Open') as open_alerts,
    (SELECT COUNT(*) FROM alerts WHERE status = 'Open' AND severity = 'High') as critical_alerts,
    (SELECT COUNT(*) FROM alerts WHERE status = 'Open' AND severity = 'Medium') as warning_alerts,
    (SELECT COUNT(*) FROM streetlights WHERE status = 'Maintenance') as maintenance_count,
    (SELECT SUM((dimming_level/100) * 0.085 * 24) FROM streetlights WHERE power_state = 'ON') as energy_today";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$lights_color = 'success';
if ($stats['maintenance_count'] > 0) $lights_color = 'warning';
if ($stats['online'] < ($stats['total'] * 0.7)) $lights_color = 'danger';

$alerts_color = 'success';
if ($stats['warning_alerts'] > 0) $alerts_color = 'warning';
if ($stats['critical_alerts'] > 0) $alerts_color = 'danger';

$alerts_query = "SELECT a.*, s.node_name FROM alerts a 
                 INNER JOIN streetlights s ON a.light_id = s.light_id 
                 WHERE a.status = 'Open' ORDER BY a.created_at DESC LIMIT 5";
$alerts_result = $conn->query($alerts_query);

$logs_query = "SELECT al.*, u.username FROM activity_logs al 
               LEFT JOIN users u ON al.user_id = u.user_id 
               ORDER BY al.created_at DESC LIMIT 10";
$logs_result = $conn->query($logs_query);

$wo_query = "SELECT 
    (SELECT COUNT(*) FROM maintenance_logs WHERE status = 'Scheduled') as scheduled,
    (SELECT COUNT(*) FROM maintenance_logs WHERE status = 'In Progress') as in_progress,
    (SELECT COUNT(*) FROM maintenance_logs WHERE status = 'Completed' AND maintenance_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as completed_week";
$wo_result = $conn->query($wo_query);
$work_orders = $wo_result->fetch_assoc();

$theme_color = '#10b981';
if (isset($conn)) {
    $theme_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($theme_result && $row = $theme_result->fetch_assoc()) {
        $theme_color = $row['config_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Dashboard — Shine Guard Hulo</title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">

<style>
:root { 
    --theme-color: <?php echo $theme_color; ?>; 
}

<?php include 'assets/style.css'; ?>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: 100%;
    overflow-x: hidden;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.kpi-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: visible;
    display: flex;
    flex-direction: column;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.kpi-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
.kpi-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
.kpi-card.danger::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
.kpi-card.info::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }

.kpi-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 16px;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.kpi-card.success .kpi-icon { background: #d1fae5; }
.kpi-card.warning .kpi-icon { background: #fef3c7; }
.kpi-card.danger .kpi-icon { background: #fee2e2; }
.kpi-card.info .kpi-icon { background: #dbeafe; }

.kpi-trend {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.kpi-trend.up { background: #d1fae5; color: #059669; }
.kpi-trend.down { background: #fee2e2; color: #dc2626; }

.kpi-content {
    flex: 1;
}

.kpi-label {
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    line-height: 1.2;
}

.kpi-main {
    display: flex;
    align-items: baseline;
    gap: 4px;
    margin-bottom: 16px;
}

.kpi-value {
    font-size: 36px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.kpi-subvalue {
    font-size: 20px;
    color: #94a3b8;
    font-weight: 600;
}

.kpi-unit {
    font-size: 18px;
    color: #94a3b8;
    font-weight: 600;
    margin-left: 4px;
}

.kpi-footer {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}

.kpi-footer.success { color: #059669; }
.kpi-footer.warning { color: #d97706; }
.kpi-footer.danger { color: #dc2626; }
.kpi-footer.info { color: #2563eb; }

.iot-panel {
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 32px;

    position: relative;
}

.iot-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981, #3b82f6);
}

.iot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 16px;
}

.iot-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.iot-title-wrapper h2 {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.live-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #d1fae5;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    color: #059669;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.iot-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn.primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn.secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn.secondary:hover {
    background: #e2e8f0;
}

.iot-info {
    background: #f0f9ff;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #3b82f6;
    font-size: 13px;
    color: #1e40af;
    font-weight: 600;
}

.sensor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.sensor-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 2px solid #e2e8f0;
    transition: all 0.3s;
}

.sensor-card:hover {
    border-color: var(--theme-color);
    background: white;
    transform: translateY(-2px);
}

.sensor-icon {
    font-size: 28px;
    margin-bottom: 8px;
}

.sensor-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 8px;
}

.sensor-value {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

.panel {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.panel-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.view-all {
    color: var(--theme-color);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
}

.view-all:hover {
    text-decoration: underline;
}

.alert-item {
    padding: 16px;
    border-radius: 10px;
    background: #f8fafc;
    margin-bottom: 12px;
    border-left: 4px solid #e2e8f0;
    transition: all 0.2s;
}

.alert-item:hover {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.alert-item.critical {
    border-left-color: #ef4444;
    background: #fef2f2;
}

.alert-item.medium {
    border-left-color: #f59e0b;
    background: #fffbeb;
}

.alert-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.alert-node {
    font-weight: 700;
    color: #0f172a;
    font-size: 14px;
}

.alert-time {
    font-size: 12px;
    color: #64748b;
}

.alert-description {
    font-size: 13px;
    color: #475569;
}

.empty-state {
    text-align: center;
    padding: 48px 24px;
}

.empty-icon {
    font-size: 64px;
    opacity: 0.5;
    margin-bottom: 16px;
}

.empty-text {
    color: #64748b;
    font-size: 15px;
}

.wo-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.wo-stat {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    transition: all 0.2s;
}

.wo-stat:hover {
    border-color: var(--theme-color);
    background: white;
}

.wo-value {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 4px;
}

.wo-value.scheduled { color: #3b82f6; }
.wo-value.progress { color: #f59e0b; }
.wo-value.completed { color: #10b981; }

.wo-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}

.activity-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.2s;
}

.activity-item:hover {
    background: #f8fafc;
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    background: #f1f5f9;
}

.activity-content {
    flex: 1;
}

.activity-action {
    font-size: 14px;
    color: #0f172a;
    font-weight: 600;
    margin-bottom: 2px;
}

.activity-details {
    font-size: 13px;
    color: #64748b;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 16px !important;
        padding-bottom: 80px !important;
    }

    /* KPI cards — 1 column, full width */
    .kpi-grid {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }

    .kpi-card {
        padding: 16px;
        border-radius: 12px;
    }

    .kpi-value {
        font-size: 28px;
    }

    .kpi-top {
        margin-bottom: 10px;
    }

    .kpi-label {
        font-size: 13px;
        margin-bottom: 8px;
    }

    .kpi-footer {
        padding-top: 10px;
        font-size: 12px;
    }

    /* IoT panel */
    .iot-panel {
        padding: 18px;
    }

    .iot-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .iot-title-wrapper h2 {
        font-size: 16px;
    }

    .iot-actions {
        width: 100%;
        flex-wrap: wrap;
    }

    .iot-actions .btn {
        flex: 1;
        justify-content: center;
        font-size: 12px;
        padding: 10px 8px;
    }

    .sensor-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .sensor-card {
        padding: 14px 10px;
    }

    .sensor-value {
        font-size: 20px;
    }

    /* Dashboard panels */
    .panel {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 16px;
    }

    .panel-header h3 {
        font-size: 15px;
    }

    /* Work order stats */
    .wo-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .wo-stat {
        padding: 12px 6px;
    }

    .wo-value {
        font-size: 20px;
    }

    .wo-label {
        font-size: 10px;
    }

    /* Activity items */
    .activity-item {
        padding: 10px;
    }

    .activity-action {
        font-size: 13px;
    }

    .activity-details {
        font-size: 11px;
    }

    /* Alert items */
    .alert-item {
        padding: 12px;
    }

    .alert-node {
        font-size: 13px;
    }

    /* Page header */
    .page-header h1 {
        font-size: 18px;
    }

    .page-header p {
        font-size: 13px;
    }
}
</style>
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>

<main class="main-content">

<?php if(isset($_GET['login']) && $_GET['login'] === 'success'): ?>
<div id="loginToast" style="
    position: fixed; top: 24px; right: 24px; z-index: 99999;
    background: white; border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
    padding: 18px 24px; display: flex; align-items: center; gap: 16px;
    max-width: 380px; border-left: 4px solid #10b981;
    animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
    font-family: 'Inter', sans-serif;
">
    <div style="background: #ecfdf5; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">👋</div>
    <div style="flex: 1;">
        <div style="font-weight: 800; color: #0f172a; font-size: 0.9rem; margin-bottom: 2px;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</div>
        <div style="color: #64748b; font-size: 0.8rem;">You have successfully logged in.</div>
    </div>
    <button onclick="document.getElementById('loginToast').style.display='none'" style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
</div>
<style>
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(60px); }
        to   { opacity: 1; transform: translateX(0); }
    }
</style>
<script>
    setTimeout(() => {
        const t = document.getElementById('loginToast');
        if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
    }, 4000);
</script>
<?php endif; ?>

    <div class="page-header">
                <br>

      <center> <h1>🖥️ DASHBOARD OVERVIEW</h1>
        <p>Real-Time Monitoring of The Smart Streetlight System</p>
    </div></center> 

    <div class="kpi-grid">
        <div class="kpi-card <?php echo $lights_color; ?>">
            <div class="kpi-top">
                <div class="kpi-icon">💡</div>
                <div class="kpi-trend up">↑ 94%</div>
            </div>
            <div class="kpi-label">Streetlights Online</div>
            <div class="kpi-main">
                <div class="kpi-value"><?php echo $stats['online']; ?></div>
                <div class="kpi-subvalue">/<?php echo $stats['total']; ?></div>
            </div>
            <?php if ($stats['maintenance_count'] > 0): ?>
                <div class="kpi-footer warning">⚠️ <?php echo $stats['maintenance_count']; ?> under maintenance</div>
            <?php else: ?>
                <div class="kpi-footer success">✓ All systems operational</div>
            <?php endif; ?>
        </div>

        <div class="kpi-card <?php echo $alerts_color; ?>">
            <div class="kpi-top">
                <div class="kpi-icon">🚨</div>
            </div>
            <div class="kpi-label">Active Alerts</div>
            <div class="kpi-main">
                <div class="kpi-value"><?php echo $stats['open_alerts']; ?></div>
            </div>
            <?php if ($stats['critical_alerts'] > 0): ?>
                <div class="kpi-footer danger">🔴 <?php echo $stats['critical_alerts']; ?> critical</div>
            <?php elseif ($stats['warning_alerts'] > 0): ?>
                <div class="kpi-footer warning">⚠️ <?php echo $stats['warning_alerts']; ?> warnings</div>
            <?php else: ?>
                <div class="kpi-footer success">✓ No active alerts</div>
            <?php endif; ?>
        </div>

        <div class="kpi-card info">
            <div class="kpi-top">
                <div class="kpi-icon">⚡</div>
                <div class="kpi-trend down">↓ 15%</div>
            </div>
            <div class="kpi-label">Energy Today (Est.)</div>
            <div class="kpi-main">
                <div class="kpi-value"><?php echo number_format($stats['energy_today'], 1); ?></div>
                <div class="kpi-unit">kWh</div>
            </div>
            <div class="kpi-footer info">📉 Below average</div>
        </div>

        <div class="kpi-card success">
            <div class="kpi-top">
                <div class="kpi-icon">🔧</div>
            </div>
            <div class="kpi-label">Work Orders</div>
            <div class="kpi-main">
                <div class="kpi-value"><?php echo $work_orders['scheduled'] + $work_orders['in_progress']; ?></div>
            </div>
            <div class="kpi-footer success">✓ <?php echo $work_orders['completed_week']; ?> completed this week</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="panel">
            <div class="panel-header">
                <h3>🚨 Recent Alerts</h3>
                <a href="alerts.php" class="view-all">View All →</a>
            </div>
            
            <?php if ($alerts_result->num_rows > 0): ?>
                <?php while ($alert = $alerts_result->fetch_assoc()): ?>
                <div class="alert-item <?php echo strtolower($alert['severity']); ?>">
                    <div class="alert-header">
                        <span class="alert-node"><?php echo $alert['node_name']; ?></span>
                        <span class="alert-time"><?php echo date('H:i', strtotime($alert['created_at'])); ?></span>
                    </div>
                    <div class="alert-description">
                        <?php echo substr($alert['description'], 0, 80); ?>...
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <div class="empty-text">No active alerts</div>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="panel" style="margin-bottom: 24px;">
                <div class="panel-header">
                    <h3>🔧 Work Orders</h3>
                    <a href="work_orders.php" class="view-all">Manage →</a>
                </div>
                <div class="wo-grid">
                    <div class="wo-stat">
                        <div class="wo-value scheduled"><?php echo $work_orders['scheduled']; ?></div>
                        <div class="wo-label">Scheduled</div>
                    </div>
                    <div class="wo-stat">
                        <div class="wo-value progress"><?php echo $work_orders['in_progress']; ?></div>
                        <div class="wo-label">In Progress</div>
                    </div>
                    <div class="wo-stat">
                        <div class="wo-value completed"><?php echo $work_orders['completed_week']; ?></div>
                        <div class="wo-label">Completed</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>📝 Recent Activity</h3>
                </div>
                <?php 
                $count = 0;
                while ($log = $logs_result->fetch_assoc()): 
                    if ($count >= 5) break;
                    $count++;
                ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php 
                        $icons = ['Login' => '🔐', 'Logout' => '🚪', 'Control' => '💡', 'Alert' => '🚨', 'Work Order' => '🔧', 'Settings' => '⚙️'];
                        echo $icons[$log['action']] ?? '📌';
                        ?>
                    </div>
                    <div class="activity-content">
                        <div class="activity-action"><?php echo $log['action']; ?></div>
                        <div class="activity-details">
                            <?php echo $log['username'] ?? 'System'; ?> • 
                            <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <br>
 
    <div class="iot-panel">
        <div class="iot-header">
            <div class="iot-title-wrapper">
                <h2>🔥 IoT Node Real-Time Status</h2>
                <div class="live-badge">
                    <div class="live-dot"></div>
                    LIVE
                </div>
            </div>
            <div class="iot-actions">
                <a href="firebase_dashboard.php" class="btn primary">🔥 Open Full Dashboard</a>
                <button onclick="refreshFirebaseData()" class="btn secondary">🔄 Refresh</button>
            </div>
        </div>
        
        <div class="iot-info">
            Live data from <strong>SG-NODE2</strong> (Firebase) → <strong>SL-001</strong> (MySQL) <span style="color: #10b981; margin-left: 8px;">● Connected</span>
        </div>

        <div class="sensor-grid">
            <div class="sensor-card">
                <div class="sensor-icon">🌡️</div>
                <div class="sensor-label">Temperature</div>
                <div class="sensor-value" id="temp-value">--</div>
            </div>
            <div class="sensor-card">
                <div class="sensor-icon">💡</div>
                <div class="sensor-label">Brightness</div>
                <div class="sensor-value" id="brightness-value">--</div>
            </div>
            <div class="sensor-card">
                <div class="sensor-icon">⚡</div>
                <div class="sensor-label">Voltage</div>
                <div class="sensor-value" id="voltage-value">--</div>
            </div>
            <div class="sensor-card">
                <div class="sensor-icon">💧</div>
                <div class="sensor-label">Humidity</div>
                <div class="sensor-value" id="humidity-value">--</div>
            </div>
        </div>
    </div>

</main>
</div>

<script>
function refreshFirebaseData() {
    fetch('api/get_firebase_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('temp-value').textContent = data.temperature + '°C';
                document.getElementById('brightness-value').textContent = data.brightness + ' lux';
                document.getElementById('voltage-value').textContent = data.voltage.toFixed(2) + 'V';
                document.getElementById('humidity-value').textContent = data.humidity + '%';
            }
        })
        .catch(error => console.error('Error:', error));
}

setInterval(refreshFirebaseData, 30000);
refreshFirebaseData();

function animateCountUp(el) {
    const raw = parseFloat(el.textContent.replace(/[^0-9.]/g, ''));
    if (isNaN(raw) || raw === 0) return;
    const isDecimal = el.textContent.includes('.');
    const decimals = isDecimal ? (el.textContent.split('.')[1] || '').length : 0;
    const duration = 3500;
    const start = performance.now();
    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        const current = raw * ease;
        el.textContent = decimals > 0 ? current.toFixed(decimals) : Math.round(current);
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = decimals > 0 ? raw.toFixed(decimals) : raw;
    }
    requestAnimationFrame(step);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.kpi-value').forEach(el => animateCountUp(el));
});
</script>
</body>
</html>