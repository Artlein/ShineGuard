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

<link rel="stylesheet" href="assets/style.css">
<?php
if (!isset($theme_color)) {
    $theme_color = '#10b981';
    $tc_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($tc_result && $tc_row = $tc_result->fetch_assoc()) {
        $theme_color = $tc_row['config_value'];
    }
}
?>
<style>:root { --theme-color: <?php echo htmlspecialchars($theme_color); ?>; }</style>
<link rel="stylesheet" href="assets/css/dashboard.css">
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
                <?php
                $sev_class = match(strtolower($alert['severity'])) {
                    'high'   => 'critical',
                    'medium' => 'medium',
                    'low'    => 'low',
                    default  => strtolower($alert['severity'])
                };
                ?>
                <div class="alert-item <?php echo $sev_class; ?>">
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
        const ease = 1 - Math.pow(1 - progress, 3); 
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