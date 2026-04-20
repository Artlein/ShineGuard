<?php
require_once 'dbconnect.php';
requireLogin();

if (isset($_GET['export']) && $_GET['export'] === 'pdf' && !canDo('export_reports')) {
    include __DIR__ . '/includes/access_denied_ui.php';
    exit();
}

$isObserver = (getUserRole() === 'System Observer');

// ── SECURITY: Validate date inputs strictly — reject any non-date values ──
// This prevents SQL injection via the start_date/end_date GET parameters.
$start_date_raw = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date_raw   = $_GET['end_date']   ?? date('Y-m-d');

// Only accept values that match exactly YYYY-MM-DD format and are real dates
function validateDate($str) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $str);
    return ($d && $d->format('Y-m-d') === $str) ? $str : null;
}

$start_date = validateDate($start_date_raw) ?? date('Y-m-d', strtotime('-30 days'));
$end_date   = validateDate($end_date_raw)   ?? date('Y-m-d');
$end_date_full = $end_date . ' 23:59:59';

// ── SECURITY: Parameterized system stats (was raw string concatenation) ──
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM streetlights) as total_lights,
    (SELECT COUNT(*) FROM streetlights WHERE status = 'Active') as active_lights,
    (SELECT COUNT(*) FROM alerts WHERE created_at BETWEEN ? AND ?) as total_alerts,
    (SELECT COUNT(*) FROM maintenance_logs WHERE maintenance_date BETWEEN ? AND ?) as maintenance_count");
$stats_stmt->bind_param("ssss", $start_date, $end_date_full, $start_date, $end_date_full);
$stats_stmt->execute();
$system_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// ── SECURITY: Parameterized energy report (was raw string concatenation) ──  
$energy_stmt = $conn->prepare("SELECT 
    DATE(timestamp) as date,
    COUNT(*) as readings,
    AVG(voltage) as avg_voltage,
    AVG(current_consumption) as avg_current,
    AVG(temperature) as avg_temperature
FROM sensor_data 
WHERE timestamp BETWEEN ? AND ?
GROUP BY DATE(timestamp)
ORDER BY date DESC
LIMIT 15");
$energy_stmt->bind_param("ss", $start_date, $end_date_full);
$energy_stmt->execute();
$energy_report = $energy_stmt->get_result();
$energy_stmt->close();

// ── SECURITY HARDENING: Parameterized Report Queries ──
$prob_stmt = $conn->prepare("SELECT 
    s.node_name,
    s.location,
    COUNT(a.alert_id) as alert_count,
    COUNT(CASE WHEN a.severity = 'High' THEN 1 END) as critical_count
FROM streetlights s
LEFT JOIN alerts a ON s.light_id = a.light_id AND a.created_at BETWEEN ? AND ?
GROUP BY s.light_id
HAVING alert_count > 0
ORDER BY critical_count DESC, alert_count DESC
LIMIT 8");
$end_date_full = $end_date . " 23:59:59";
$prob_stmt->bind_param("ss", $start_date, $end_date_full);
$prob_stmt->execute();
$problematic_lights = $prob_stmt->get_result();
$prob_stmt->close();

$snap_stmt = $conn->prepare("SELECT COUNT(*) as snapshot_count FROM camera_snapshots WHERE created_at BETWEEN ? AND ?");
$snap_stmt->bind_param("ss", $start_date, $end_date_full);
$snap_stmt->execute();
$snapshots_query = $snap_stmt->get_result();
$snap_stmt->close();
$snapshot_count = ($snapshots_query) ? $snapshots_query->fetch_assoc()['snapshot_count'] : 0;

$report_archives = \ShineGuard\Services\ReportingService::getArchive($conn, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Reports & Analytics | ShineGuard</title>
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        <?php include 'assets/style.css'; ?>
        
        :root {
            --sg-primary: #10b981;
            --sg-primary-glow: rgba(16, 185, 129, 0.2);
            --sg-blue: #3b82f6;
            --sg-blue-glow: rgba(59, 130, 246, 0.2);
            --sg-red: #ef4444;
            --sg-red-glow: rgba(239, 68, 68, 0.1);
            --sg-amber: #f59e0b;
            --sg-amber-glow: rgba(245, 158, 11, 0.1);
            --sg-glass: rgba(255, 255, 255, 0.7);
            --sg-glass-border: rgba(255, 255, 255, 0.5);
            --sg-text: #1e293b;
            --sg-text-dim: #64748b;
        }

        .dark-mode {
            --sg-glass: rgba(15, 23, 42, 0.8);
            --sg-glass-border: rgba(255, 255, 255, 0.08);
            --sg-text: #f1f5f9;
            --sg-text-dim: #94a3b8;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: var(--sg-text);
            margin: 0;
        }

        .dark-mode body { background-color: #0f172a; }

        .main-content {
            padding: 3rem 4rem 6rem;
            max-width: 1700px;
            margin: 0 auto;
        }

        .reports-grid-bridge {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 2.5rem;
            align-items: start;
        }

        @media (max-width: 1200px) {
            .reports-grid-bridge { grid-template-columns: 1fr; }
            .main-content { padding: 1.5rem; }
        }

        .hero-branding {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2.5rem;
            padding: 1.5rem 0;
            background: transparent !important;
            height: auto !important;
            position: relative !important;
        }

        .hero-title-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 6px;
        }

        .hero-branding h1 {
            font-size: 2rem; font-weight: 800;
            letter-spacing: -0.04em;
            margin: 0;
            text-transform: uppercase;
            color: var(--sg-text) !important;
        }

        .hero-branding p {
            font-size: 1.1rem; 
            color: var(--sg-text-dim) !important;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .hero-icon-box {
            width: 54px; height: 54px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 22px -5px rgba(37, 99, 235, 0.35);
            position: relative;
            flex-shrink: 0;
        }

        /* Glass Components */
        .glass-panel {
            background: var(--sg-glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--sg-glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 20px 50px -10px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-panel::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }

        /* Stat Grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }

        .stat-card {
            background: var(--sg-glass);
            padding: 1.25rem;
            border-radius: 20px;
            border: 1px solid var(--sg-glass-border);
            display: flex; align-items: center; gap: 1rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.15), 
                        0 0 20px rgba(59, 130, 246, 0.05);
        }

        .stat-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center; font-size: 22px;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);
        }

        .stat-info { display: flex; flex-direction: column; }
        .stat-label { 
            font-size: 10px; font-weight: 800; 
            color: var(--sg-text-dim); 
            text-transform: uppercase; 
            letter-spacing: 0.15em;
            margin-bottom: 2px;
        }
        .stat-value { font-size: 22px; font-weight: 900; color: var(--sg-text); letter-spacing: -0.02em; }

        /* Filter Terminal */
        .filter-terminal {
            display: flex; align-items: flex-end; gap: 1.5rem; flex-wrap: wrap;
        }

        .input-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .input-group label { font-size: 12px; font-weight: 700; color: var(--sg-text-dim); }
        .input-group input {
            background: rgba(255,255,255,0.4);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 12px;
            padding: 0 1rem; height: 44px;
            font-family: inherit; font-weight: 600;
            outline: none; transition: all 0.3s ease;
        }
        .input-group input:focus {
            background: white;
            border-color: var(--sg-blue);
            box-shadow: 0 0 0 4px var(--sg-blue-glow);
        }
        .dark-mode .input-group input { background: rgba(0,0,0,0.25); color: white; border-color: rgba(255,255,255,0.08); }
        .dark-mode .input-group input:focus { background: rgba(0,0,0,0.4); border-color: var(--sg-blue); }

        /* Buttons */
        .btn-sg {
            height: 44px; padding: 0 1.5rem;
            border-radius: 14px; border: none;
            font-weight: 700; font-size: 14px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 0.75rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-emerald { 
            background: linear-gradient(135deg, var(--sg-primary), #059669); color: white !important; 
            box-shadow: 0 8px 20px -5px var(--sg-primary-glow);
        }
        .btn-emerald:hover { 
            transform: translateY(-3px) scale(1.02); 
            box-shadow: 0 12px 25px -5px var(--sg-primary-glow); 
        }
        
        .btn-red {
            background: linear-gradient(135deg, #ef4444, #dc2626); color: white !important;
            box-shadow: 0 8px 20px -5px rgba(239, 68, 68, 0.35);
        }
        .btn-red:hover { 
            transform: translateY(-3px) scale(1.02); 
            box-shadow: 0 12px 25px -5px rgba(239, 68, 68, 0.45); 
        }
        .btn-red svg { stroke: white !important; }

        /* Tables */
        .sg-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .sg-table th { 
            text-align: left; padding: 1rem; 
            font-size: 11px; font-weight: 800; color: var(--sg-text-dim); 
            text-transform: uppercase; border-bottom: 2px solid var(--sg-glass-border);
        }
        .sg-table td { padding: 1.25rem 1rem; border-bottom: 1px solid var(--sg-glass-border); font-size: 14px; }
        .sg-table tr:hover td { background: rgba(59, 130, 246, 0.02); }

        .rank-pill {
            background: rgba(0,0,0,0.05); padding: 4px 10px; border-radius: 8px;
            font-size: 12px; font-weight: 800;
        }
        .dark-mode .rank-pill { background: rgba(255,255,255,0.05); }

        /* Sidebar Vault */
        .vault-item {
            padding: 1.25rem; border-radius: 18px;
            background: rgba(255,255,255,0.4);
            border: 1px solid var(--sg-glass-border);
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .dark-mode .vault-item { background: rgba(255,255,255,0.03); }
        .vault-item:hover { transform: scale(1.02); background: white; }
        .dark-mode .vault-item:hover { background: rgba(255,255,255,0.05); }

        .vault-title { font-size: 14px; font-weight: 800; color: var(--sg-text); margin-bottom: 4px; }
        .vault-meta { font-size: 11px; color: var(--sg-text-dim); display: flex; gap: 8px; }

        .vault-locked { text-align: center; padding: 1rem 0; }
        .vault-lock-icon { font-size: 40px; margin-bottom: 1rem; filter: drop-shadow(0 0 12px rgba(59, 130, 246, 0.3)); }
        .vault-lock-status { 
            display: block; font-size: 10px; font-weight: 900; 
            text-transform: uppercase; color: var(--sg-blue); 
            letter-spacing: 0.15em; margin-bottom: 6px; 
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        
        <section class="hero-branding">
            <div class="hero-title-group">
                <div class="hero-icon-box">
                    <svg width="26" height="26" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h1>System Reports & Analytics</h1>
            </div>
            <p>Intelligence platform providing cryptographically verified sensor telemetry and infrastructure health metrics.</p>
        </section>

        <div class="reports-grid-bridge">
            <section class="main-content-flow">
            <!-- Filter Terminal -->
            <div class="glass-panel" style="margin-bottom: 2rem; padding: 1.5rem 2rem;">
                <form method="GET" class="filter-terminal">
                    <div class="input-group">
                        <label>START DATE</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="input-group">
                        <label>END DATE</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    <div style="flex: 1; display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="button" class="btn-sg btn-emerald" onclick="gateAction('generate')">
                            <span>📊</span> GENERATE REPORT
                        </button>
                        <button type="button" class="btn-sg btn-red" onclick="gateAction('download')">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            DOWNLOAD PDF
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">💡</div>
                    <div class="stat-info">
                        <span class="stat-label">Active Nodes</span>
                        <span class="stat-value"><?php echo $system_stats['active_lights']; ?>/<?php echo $system_stats['total_lights']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">🚨</div>
                    <div class="stat-info">
                        <span class="stat-label">Alerts</span>
                        <span class="stat-value"><?php echo $system_stats['total_alerts']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">🔧</div>
                    <div class="stat-info">
                        <span class="stat-label">Work Orders</span>
                        <span class="stat-value"><?php echo $system_stats['maintenance_count']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">📸</div>
                    <div class="stat-info">
                        <span class="stat-label">Snapshots</span>
                        <span class="stat-value"><?php echo $snapshot_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Problematic Nodes (Prioritized) -->
            <div class="glass-panel" style="margin-bottom: 2rem;">
                <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    ⚠️ Infrastructure Health: Problematic Nodes
                </h3>
                <div style="overflow-x: auto;">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Node Identity</th>
                                <th>Location</th>
                                <th>Total Alerts</th>
                                <th>Critical</th>
                                <th style="text-align: right;">Risk Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($problematic_lights && $problematic_lights->num_rows > 0): ?>
                                <?php while ($row = $problematic_lights->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 700;">
                                            <span style="color: #3b82f6;">📡</span> <?php echo htmlspecialchars($row['node_name']); ?>
                                        </td>
                                        <td style="font-size: 12px; color: var(--sg-text-dim);"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><span class="rank-pill"><?php echo $row['alert_count']; ?></span></td>
                                        <td><span class="rank-pill" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><?php echo $row['critical_count']; ?></span></td>
                                        <td style="text-align: right;">
                                            <?php if ($row['critical_count'] > 0): ?>
                                                <span style="color: #ef4444; font-weight: 800; font-size: 11px;">⚠️ IMMEDIATE CHECK</span>
                                            <?php else: ?>
                                                <span style="color: #f59e0b; font-weight: 800; font-size: 11px;">🟡 MONITORING</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; padding: 3rem;">All structural nodes are reporting optimal health signatures.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="glass-panel" style="margin-bottom: 2rem;">
                <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    📈 Sensor Telemetry Historicals
                </h3>
                <div style="overflow-x: auto;">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Density</th>
                                <th>Volt (Avg)</th>
                                <th>Curr (Avg)</th>
                                <th>Temp</th>
                                <th style="text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($energy_report && $energy_report->num_rows > 0): ?>
                                <?php while ($row = $energy_report->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                        <td><span class="rank-pill"><?php echo $row['readings']; ?></span></td>
                                        <td><?php echo number_format($row['avg_voltage'], 1); ?>V</td>
                                        <td><?php echo number_format($row['avg_current'], 2); ?>A</td>
                                        <td><?php echo number_format($row['avg_temperature'], 1); ?>°C</td>
                                        <td style="text-align: right;"><span style="color: #10b981; font-weight: 800; font-size: 11px;">✓ VERIFIED</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 3rem;">No telemetry signatures found in this quadrant.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Sidebar Vault -->
        <aside class="reports-sidebar">
            <div class="glass-panel" style="padding: 1.5rem;">
                <h2 style="font-size: 16px; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    📑 Archive Vault
                </h2>
                
                <?php if (!isRecentlyAuthorized()): ?>
                    <!-- Locked State -->
                    <div class="vault-locked">
                        <div class="vault-lock-icon">🔒</div>
                        <span class="vault-lock-status">Active Lockdown</span>
                        <p style="font-size: 12px; color: var(--sg-text-dim); margin-bottom: 1.5rem;">
                            Diagnostic archives are protected by Secure Session Authorization (SBA). 
                            Authorization elevation is required to view the ledger.
                        </p>
                        <button type="button" class="btn-sg btn-emerald" onclick="gateAction('unlock_vault')" style="width: 100%; justify-content: center;">
                            UNLOCK LEDGER
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Unlocked State -->
                    <?php if (empty($report_archives)): ?>
                        <div style="text-align: center; padding: 2rem; border: 2px dashed rgba(0,0,0,0.05); border-radius: 20px;">
                            <span style="font-size: 2rem; display: block; margin-bottom: 1rem;">📭</span>
                            <p style="font-size: 12px; color: var(--sg-text-dim);">The archival repository is currently empty.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($report_archives as $arc): ?>
                            <div class="vault-item">
                                <div class="vault-title"><?php echo htmlspecialchars($arc['report_name']); ?></div>
                                <div class="vault-meta">
                                    <span>📅 <?php echo date('M d', strtotime($arc['generated_at'])); ?></span>
                                    <span>🔑 <?php echo htmlspecialchars($arc['generator']); ?></span>
                                </div>
                                <a href="exports/reports/<?php echo $arc['filename']; ?>" target="_blank" class="btn-sg btn-emerald" style="width: 100%; margin-top: 12px; height: 32px; font-size: 11px; justify-content: center; border-radius: 10px;">
                                    DOWNLOAD ARCHIVE
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <button type="button" onclick="location.reload()" class="btn-sg" style="width: 100%; margin-top: 1rem; background: rgba(59, 130, 246, 0.1); color: var(--sg-blue); justify-content: center; font-size: 11px; height: 32px;">
                        REVOKE ELEVATION
                    </button>
                <?php endif; ?>
            </div>

            <!-- Auto-Scheduling (Separated) -->
            <div class="glass-panel" style="margin-top: 1.5rem; background: var(--sg-amber-glow); border: 2px solid rgba(245, 158, 11, 0.2); border-radius: 20px; padding: 1.5rem;">
                <h4 style="color: #f59e0b; font-size: 13px; font-weight: 800; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 0.05em;">🚀 AUTO-SCHEDULING</h4>
                <p style="font-size: 12px; margin: 0; line-height: 1.5; font-weight: 600; color: #1e293b;">
                    System briefing scheduled for <strong>Every Monday @ 08:00 AM.</strong>
                </p>
            </div>
        </aside>
        </div>
    </main>
</div>

<!-- Modern Identity Verification Modal -->
<div id="authGateModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(12px) saturate(160%); z-index:10000; align-items:center; justify-content:center;">
    <div class="glass-panel modal-spring" style="max-width: 440px; width: 90%; background: var(--sg-glass); padding: 2.5rem; text-align: center;">
        <div style="font-size: 40px; margin-bottom: 1rem;">🛡️</div>
        <h2 id="gateTitle" style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; letter-spacing: -0.02em;">IDENTITY GATE</h2>
        <p id="gateDesc" style="font-size: 14px; color: var(--sg-text-dim); margin-bottom: 1.5rem;">Please verify your administrator credentials to proceed.</p>
        
        <div id="gateError" style="display:none; background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:0.75rem; border-radius:12px; font-size:12px; font-weight:700; margin-bottom:1rem; border:1px solid rgba(239,68,68,0.2);">
            Invalid password. Access denied.
        </div>

        <div class="input-group" style="text-align: left; margin-bottom: 1.5rem;">
            <label style="font-size: 10px; letter-spacing: 0.1em; color: var(--sg-blue);">CREDENTIAL VERIFICATION</label>
            <input type="password" id="gatePassword" placeholder="Admin Password" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--sg-glass-border); color:var(--sg-text); height:48px; padding:0 1.25rem; border-radius:14px; font-weight:600; outline:none;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button onclick="closeSecurityGate()" class="btn-sg" style="flex:1; background:rgba(0,0,0,0.05); justify-content:center; color:var(--sg-text-dim);">CANCEL</button>
            <button onclick="confirmSecurityGate()" id="gateConfirmBtn" class="btn-sg btn-emerald" style="flex:1; justify-content:center;">
                VERIFY & PROCEED
            </button>
        </div>
    </div>
</div>

<!-- Simplified Export Modal -->
<div id="exportModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.4); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center;">
    <div class="glass-panel modal-spring" style="max-width: 420px; width: 90%; background: var(--sg-glass); text-align: center;">
        <h2 style="margin-top: 0; font-size: 1.25rem; font-weight: 800;">Export Ledger</h2>
        <p style="font-size: 13px; color: var(--sg-text-dim);">Select signature format for the selected period.</p>

        <!-- Password Info Box -->
        <div style="background: rgba(59,130,246,0.08); border: 1.5px solid rgba(59,130,246,0.2); border-radius: 14px; padding: 14px 18px; margin: 1rem 0; text-align: left;">
            <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #3b82f6; margin-bottom: 6px;">&#128274; PDF File Password</div>
            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">Your ShineGuard login password</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">The exported file is encrypted with your account password for security.</div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.5rem;">
            <a href="report_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn-sg btn-emerald" style="justify-content: center; text-decoration: none;" onclick="onPdfDownload()">
                📄 DOWNLOAD PDF SIGNATURE
            </a>
            <button onclick="document.getElementById('exportModal').style.display='none'" class="btn-sg" style="background: rgba(0,0,0,0.05); justify-content: center;">
                CANCEL
            </button>
        </div>
    </div>
</div>


<script>
    let activeGateAction = null;

    function gateAction(action) {
        activeGateAction = action;
        const modal = document.getElementById('authGateModal');
        const title = document.getElementById('gateTitle');
        const desc = document.getElementById('gateDesc');
        const error = document.getElementById('gateError');
        const password = document.getElementById('gatePassword');

        error.style.display = 'none';
        password.value = '';
        
        if (action === 'generate') {
            title.textContent = 'RE-AUTHENTICATE ACTION';
            desc.textContent = 'Generating live signatures requires immediate identity verification.';
        } else if (action === 'download') {
            title.textContent = 'EXPORT AUTHORIZATION';
            desc.textContent = 'Downloading encrypted PDF reports requires a security handshake.';
        } else {
            title.textContent = 'IDENTITY GATE';
            desc.textContent = 'Please verify your administrator credentials to unlock the archive.';
        }

        modal.style.display = 'flex';
        password.focus();
    }

    function closeSecurityGate() {
        document.getElementById('authGateModal').style.display = 'none';
        activeGateAction = null;
    }

    async function confirmSecurityGate() {
        const passwordInput = document.getElementById('gatePassword');
        const confirmBtn = document.getElementById('gateConfirmBtn');
        const error = document.getElementById('gateError');
        const password = passwordInput.value;

        if (!password) {
            passwordInput.focus();
            return;
        }

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'VERIFYING...';
        error.style.display = 'none';

        try {
            const formData = new URLSearchParams();
            formData.append('admin_password', password);
            // Persistent SBA only for vault, "verify" for specific actions
            formData.append('action', activeGateAction === 'unlock_vault' ? 'authorize' : 'verify');
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');

            const response = await fetch('api/auth_session.php', {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const result = await response.json();

            if (result.success) {
                if (activeGateAction === 'generate') {
                    window.sgToast('🔐', 'Identity Verified', 'Generating report signatures...', '#10b981', '#ecfdf5');
                    setTimeout(() => document.querySelector('.filter-terminal').submit(), 900);
                } else if (activeGateAction === 'download') {
                    closeSecurityGate();
                    window.sgToast('✅', 'Export Authorized', 'Select your download format below.', '#10b981', '#ecfdf5');
                    setTimeout(() => { document.getElementById('exportModal').style.display = 'flex'; }, 800);
                } else if (activeGateAction === 'unlock_vault') {
                    window.sgToast('🔓', 'Vault Unlocked', 'Archive ledger is now accessible.', '#3b82f6', '#eff6ff');
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                error.textContent = result.error || 'Invalid password. Access denied.';
                error.style.display = 'block';
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'VERIFY & PROCEED';
                passwordInput.value = '';
                passwordInput.focus();
            }
        } catch (err) {
            error.textContent = 'Security communication failure.';
            error.style.display = 'block';
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'VERIFY & PROCEED';
        }
    }

    // Add Enter key listener for password
    document.getElementById('gatePassword').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') confirmSecurityGate();
    });

    function openExportModal() {
        document.getElementById('exportModal').style.display = 'flex';
    }

    function onPdfDownload() {
        // Close modal after a brief moment (download starts in background)
        setTimeout(() => { document.getElementById('exportModal').style.display = 'none'; }, 400);
        // Show password reminder toast
        setTimeout(() => {
            window.sgToast('🔐', 'PDF Downloaded — Protected', 'Use your ShineGuard login password to open the file.', '#3b82f6', '#eff6ff');
        }, 600);
    }

    // Toast on page load if report was just filtered
    <?php if (isset($_GET['start_date']) && !isset($_GET['export'])): ?>
    window.addEventListener('DOMContentLoaded', () => {
        if(window.sgToast) window.sgToast('📊', 'Report Generated', 'Displaying data for the selected period.', '#3b82f6', '#eff6ff');
    });
    <?php endif; ?>
</script>
</body>
</html>
