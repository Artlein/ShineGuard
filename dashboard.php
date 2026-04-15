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

// Enhanced Weather Fetch with 15-minute Caching
$weather_data = null;
$cache_file = __DIR__ . '/assets/weather_cache.json';
$cache_ttl = 900; // 15 minutes

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
    $weather_data = json_decode(file_get_contents($cache_file), true);
} else {
    try {
        $weather_url = 'https://api.open-meteo.com/v1/forecast?latitude=14.5794&longitude=121.0359&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,precipitation_probability&timezone=Asia%2FManila&forecast_days=1';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $weather_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP compatibility
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $weather_data = json_decode($response, true);
            if ($weather_data) {
                if (!is_dir(__DIR__ . '/assets')) @mkdir(__DIR__ . '/assets', 0777, true);
                @file_put_contents($cache_file, $response);
            }
        } elseif (file_exists($cache_file)) {
            // Fallback to expired cache if API fails
            $weather_data = json_decode(file_get_contents($cache_file), true);
        }
    } catch (Exception $e) {
        if (file_exists($cache_file)) {
            $weather_data = json_decode(file_get_contents($cache_file), true);
        }
    }
}

$WMO_ICONS = [
    0=>'☀️', 1=>'🌤️', 2=>'⛅', 3=>'☁️', 45=>'🌫️', 48=>'🌫️',
    51=>'🌦️', 53=>'🌦️', 55=>'🌧️', 61=>'🌧️', 63=>'🌧️', 65=>'🌧️',
    71=>'🌨️', 73=>'🌨️', 75=>'❄️', 80=>'🌦️', 81=>'🌧️', 82=>'⛈️',
    95=>'⛈️', 96=>'⛈️', 99=>'⛈️'
];
$WMO_DESC = [
    0=>'Clear Sky', 1=>'Mainly Clear', 2=>'Partly Cloudy', 3=>'Overcast',
    45=>'Fog', 48=>'Icy Fog', 51=>'Light Drizzle', 53=>'Drizzle', 55=>'Heavy Drizzle',
    61=>'Light Rain', 63=>'Rain', 65=>'Heavy Rain', 71=>'Light Snow', 73=>'Snow', 75=>'Heavy Snow',
    80=>'Light Showers', 81=>'Showers', 82=>'Heavy Showers', 95=>'Thunderstorm', 96=>'Thunderstorm', 99=>'Thunderstorm'
];

// Defensive check for weather data structure
$curr_weather = (is_array($weather_data) && isset($weather_data['current'])) ? $weather_data['current'] : null;
if (!is_array($curr_weather)) $curr_weather = []; 
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
<link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
<style>
    .badge-mini {
        font-size: 0.65rem;
        font-weight: 800;
        padding: 4px 8px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
        background: var(--surface-elev);
        border: 1px solid var(--border);
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .badge-mini .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .pulse-blue {
        background: #3b82f6;
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
        animation: pulse-blue 2s infinite;
    }
    @keyframes pulse-blue {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
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
    background: var(--panel); border-radius: 16px;
    box-shadow: var(--shadow-md);
    padding: 18px 24px; display: flex; align-items: center; gap: 16px;
    max-width: 380px; border-left: 4px solid var(--accent);
    border: 1px solid var(--border);
    animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
    font-family: 'Inter', sans-serif;
">
    <div style="background: rgba(var(--sb-accent-rgb), 0.1); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">👋</div>
    <div style="flex: 1;">
        <div style="font-weight: 800; color: var(--text); font-size: 0.9rem; margin-bottom: 2px;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</div>
        <div style="color: var(--dim); font-size: 0.8rem;">You have successfully logged in.</div>
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
            <a href="streetlights.php" class="kpi-icon-link">
                <div class="kpi-icon-wrapper">💡</div>
            </a>
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
            <a href="alerts.php" class="kpi-icon-link">
                <div class="kpi-icon-wrapper">🚨</div>
            </a>
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
            <a href="reports.php" class="kpi-icon-link">
                <div class="kpi-icon-wrapper">⚡</div>
            </a>
            <div class="kpi-label">Energy Today (Est.)</div>
            <div class="kpi-main">
                <div class="kpi-value"><?php echo number_format($stats['energy_today'], 1); ?></div>
                <div class="kpi-unit">kWh</div>
            </div>
            <div class="kpi-footer info">📉 Below average</div>
        </div>

        <div class="kpi-card success">
            <a href="work_orders.php" class="kpi-icon-link">
                <div class="kpi-icon-wrapper">🔧</div>
            </a>
            <div class="kpi-label">Work Orders</div>
            <div class="kpi-main">
                <div class="kpi-value"><?php echo $work_orders['scheduled'] + $work_orders['in_progress']; ?></div>
            </div>
            <div class="kpi-footer success">✓ <?php echo $work_orders['completed_week']; ?> completed this week</div>
        </div>
    </div>

    <!-- Weather script removed: moved to server-side PHP for reliability -->

    <div class="dashboard-grid">
        <div class="panel" style="border-top: 5px solid #ef4444;">
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
                        <span class="alert-node"><?php echo htmlspecialchars($alert['node_name']); ?></span>
                        <span class="alert-time"><?php echo date('H:i', strtotime($alert['created_at'])); ?></span>
                    </div>
                    <div class="alert-description">
                        <?php echo htmlspecialchars(substr($alert['description'], 0, 80)); ?>...
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

        <div class="dashboard-side">
            <div class="panel" style="margin-bottom: 24px; border-top: 5px solid #3b82f6;">
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

            <!-- Integrated Weather Card -->
            <div class="weather-card">
                <div class="weather-main">
                    <div class="weather-icon"><?php echo $WMO_ICONS[$curr_weather['weather_code'] ?? -1] ?? '🌡️'; ?></div>
                    <div class="weather-info">
                        <div class="weather-location">📍 Mandaluyong City</div>
                        <div class="weather-temp-row">
                            <span class="weather-temp"><?php echo isset($curr_weather['temperature_2m']) ? round($curr_weather['temperature_2m']) : '--'; ?></span>
                            <span class="weather-unit">°C</span>
                        </div>
                        <div class="weather-desc"><?php echo $WMO_DESC[$curr_weather['weather_code'] ?? -1] ?? 'Weather Service Offline'; ?></div>
                    </div>
                </div>
                <div class="weather-stats">
                    <div class="w-stat">
                        <span class="w-stat-icon">💧</span>
                        <div class="w-stat-val"><?php echo $curr_weather['relative_humidity_2m'] ?? '--'; ?>%</div>
                        <div class="w-stat-label">Humidity</div>
                    </div>
                    <div class="w-stat">
                        <span class="w-stat-icon">💨</span>
                        <div class="w-stat-val"><?php echo isset($curr_weather['wind_speed_10m']) ? round($curr_weather['wind_speed_10m']) : '--'; ?></div>
                        <div class="w-stat-label">km/h</div>
                    </div>
                    <div class="w-stat">
                        <span class="w-stat-icon">🌧️</span>
                        <div class="w-stat-val"><?php echo $curr_weather['precipitation_probability'] ?? '--'; ?>%</div>
                        <div class="w-stat-label">Rain</div>
                    </div>
                </div>
            </div>

            <?php if (canDo('view_activity_logs')): ?>
            <div class="panel" style="border-top: 5px solid #64748b;">
                <div class="panel-header">
                    <h3>📝 Recent Activity</h3>
                    <a href="activity_logs.php" class="view-all">View All →</a>
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
                        <div class="activity-action"><?php echo htmlspecialchars($log['action']); ?></div>
                        <div class="activity-details">
                            <?php echo htmlspecialchars($log['username'] ?? 'System'); ?> • 
                            <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <br>
 
    <?php if (canDo('manage_firebase')): ?>
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
        
        <div class="iot-info" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                Live data from <strong>SG-NODE2</strong> (Firebase) → <strong>SL-001</strong> (MySQL) 
                <span style="color: #10b981; margin-left: 8px;">● Connected</span>
            </div>
            <div style="display: flex; gap: 12px;">
                <div class="badge-mini mqtt" title="MQTT Bridge is active and listening">
                    <span class="dot pulse-blue"></span> MQTT PUSH: ACTIVE
                </div>
                <div class="badge-mini protocol" title="Current Infrastructure Tier">
                    TIER 2: EVENT-DRIVEN
                </div>
            </div>
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
    <?php endif; ?>

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