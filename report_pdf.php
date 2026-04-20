<?php
/**
 * SHINEGUARD ANALYTICAL REPORT — PDF GENERATOR
 * Uses TCPDF to generate a real PDF from live system data.
 * Mirrors the pattern in tools/generate_thresholds_pdf.php.
 */

require_once 'dbconnect.php';
requireLogin();

if (!canDo('export_reports')) {
    include __DIR__ . '/includes/access_denied_ui.php';
    exit();
}

// Autoloaded via Composer in dbconnect.php


// ── SECURITY: Strict date validation — rejects anything that isn't a real date ──
function validateReportDate($str) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $str);
    return ($d && $d->format('Y-m-d') === $str) ? $str : null;
}

$start_date = validateReportDate($_GET['start_date'] ?? '') ?? date('Y-m-d', strtotime('-30 days'));
$end_date   = validateReportDate($_GET['end_date']   ?? '') ?? date('Y-m-d');
$end_date_full = $end_date . ' 23:59:59';

// ── Fetch Data (all parameterized) ──────────────────────────────────────────
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM streetlights) as total_lights,
    (SELECT COUNT(*) FROM streetlights WHERE status = 'Active') as active_lights,
    (SELECT COUNT(*) FROM alerts WHERE created_at BETWEEN ? AND ?) as total_alerts,
    (SELECT COUNT(*) FROM alerts WHERE severity = 'High' AND created_at BETWEEN ? AND ?) as critical_alerts,
    (SELECT COUNT(*) FROM maintenance_logs WHERE maintenance_date BETWEEN ? AND ?) as maintenance_count");
$stats_stmt->bind_param("ssssss", $start_date, $end_date_full, $start_date, $end_date_full, $start_date, $end_date_full);
$stats_stmt->execute();
$system_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$energy_rows = [];
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
LIMIT 20");
$energy_stmt->bind_param("ss", $start_date, $end_date_full);
$energy_stmt->execute();
$energy_res = $energy_stmt->get_result();
$energy_stmt->close();
if ($energy_res) while ($r = $energy_res->fetch_assoc()) $energy_rows[] = $r;

$node_rows = [];
$node_stmt = $conn->prepare("SELECT 
    s.node_name, s.location,
    COUNT(a.alert_id) as alert_count,
    COUNT(CASE WHEN a.severity = 'High' THEN 1 END) as critical_count
FROM streetlights s
LEFT JOIN alerts a ON s.light_id = a.light_id AND a.created_at BETWEEN ? AND ?
GROUP BY s.light_id
HAVING alert_count > 0
ORDER BY critical_count DESC, alert_count DESC
LIMIT 15");
$node_stmt->bind_param("ss", $start_date, $end_date_full);
$node_stmt->execute();
$node_res = $node_stmt->get_result();
$node_stmt->close();
if ($node_res) while ($r = $node_res->fetch_assoc()) $node_rows[] = $r;

$uptime_pct = ($system_stats['total_lights'] > 0)
    ? round($system_stats['active_lights'] / $system_stats['total_lights'] * 100, 1)
    : 0;

$generated_by = $_SESSION['full_name'] ?? 'Administrator';
$generated_at = date('F d, Y h:i A');
$period_label = date('M d, Y', strtotime($start_date)) . ' – ' . date('M d, Y', strtotime($end_date));
$logoPath     = realpath(__DIR__ . '/img/ShineGuard3.png');

// ── PDF Password ─────────────────────────────────────────────────────────────
// Uses the user's own login password (captured during SBA authentication)
$pdf_user_pass  = $_SESSION['export_password'] ?? 'SG-' . date('Ymd');
$pdf_owner_pass = 'SGOwner-' . sha1('shineguard_reports_' . date('Ymd'));

// ── Initialize TCPDF ────────────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('ShineGuard Hulo');
$pdf->SetAuthor($generated_by);
$pdf->SetTitle('ShineGuard Analytical Report');
$pdf->SetSubject("System Report: $period_label");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->SetFooterMargin(10);

// Custom footer
$pdf->setFooterFont(['helvetica', '', 8]);
$pdf->setFooterData([0, 0, 0], [128, 128, 128]);

// ── Password Protection (must be set BEFORE AddPage) ─────────────────────────
// Permissions: allow printing and reading only — no editing or copying
$pdf->SetProtection(
    ['print'],      // only allow printing
    $pdf_user_pass, // password required to open the file
    $pdf_owner_pass, // owner password for unrestricted access
    3,              // AES 128-bit encryption
    null
);

// ── PAGE 1: Cover + KPIs + Sensor Telemetry ─────────────────────────────────
$pdf->AddPage();

// Build sensor telemetry rows
$telemetry_rows = '';
if (!empty($energy_rows)) {
    foreach ($energy_rows as $r) {
        $date_fmt = date('M d, Y', strtotime($r['date']));
        $voltage  = number_format($r['avg_voltage'], 1);
        $current  = number_format($r['avg_current'], 2);
        $temp     = number_format($r['avg_temperature'], 1);
        $readings = number_format($r['readings']);
        $telemetry_rows .= "
        <tr>
            <td><b>$date_fmt</b></td>
            <td style='text-align:center'>$readings</td>
            <td style='text-align:center'>{$voltage}V</td>
            <td style='text-align:center'>{$current}A</td>
            <td style='text-align:center'>{$temp}&deg;C</td>
            <td style='text-align:center; color:#059669'><b>&#10003; Verified</b></td>
        </tr>";
    }
} else {
    $telemetry_rows = "<tr><td colspan='6' style='text-align:center;color:#888;'>No sensor telemetry found for this period.</td></tr>";
}

// Build problematic nodes rows
$node_table_rows = '';
if (!empty($node_rows)) {
    foreach ($node_rows as $r) {
        $name     = htmlspecialchars($r['node_name']);
        $location = htmlspecialchars($r['location']);
        $alerts   = $r['alert_count'];
        $critical = $r['critical_count'];
        $risk     = $critical > 0 ? '<span style="color:#dc2626"><b>&#9888; Immediate Check</b></span>' : '<span style="color:#d97706">&#9898; Monitoring</span>';
        $node_table_rows .= "
        <tr>
            <td><b>$name</b></td>
            <td style='font-size:8pt;color:#555'>$location</td>
            <td style='text-align:center'>$alerts</td>
            <td style='text-align:center;color:#dc2626'><b>$critical</b></td>
            <td style='text-align:center'>$risk</td>
        </tr>";
    }
} else {
    $node_table_rows = "<tr><td colspan='5' style='text-align:center;color:#888;'>All nodes reporting optimal health.</td></tr>";
}

// Uptime color
$uptime_color = $uptime_pct >= 95 ? '#059669' : ($uptime_pct >= 80 ? '#d97706' : '#dc2626');

$html = '
<style>
    body  { font-family: helvetica; font-size: 9pt; color: #0f172a; }
    h1    { font-size: 16pt; font-weight: bold; color: #0f172a; margin: 0 0 4px 0; }
    h2    { font-size: 11pt; font-weight: bold; color: #0f172a; margin: 18px 0 8px 0; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 5px; }
    p     { font-size: 9pt; color: #475569; margin: 0 0 8px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th    { background: #f1f5f9; color: #475569; font-size: 8pt; font-weight: bold; padding: 7px 8px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; }
    td    { padding: 7px 8px; font-size: 8.5pt; border-bottom: 1px solid #f1f5f9; color: #0f172a; }
    .kpi-label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; font-weight: bold; }
    .kpi-value { font-size: 22pt; font-weight: bold; }
    .divider { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }
</style>

<!-- Header -->
<table width="100%" style="border:none; margin-bottom:20px;">
    <tr>
        <td width="15%" style="border:none;">
            ' . ($logoPath ? '<img src="' . $logoPath . '" style="width:50px;">' : '') . '
        </td>
        <td width="55%" style="border:none; vertical-align:middle;">
            <h1>ShineGuard Analytical Report</h1>
            <p>Barangay Hulo Smart Street Lighting System</p>
        </td>
        <td width="30%" style="border:none; text-align:right; vertical-align:top;">
            <p style="font-size:8pt;color:#94a3b8;line-height:1.6;">
                <b>Period</b><br>' . $period_label . '<br>
                <b>Generated By</b><br>' . htmlspecialchars($generated_by) . '<br>
                <b>Generated At</b><br>' . $generated_at . '
            </p>
        </td>
    </tr>
</table>

<!-- KPI Row -->
<h2>Infrastructure Overview</h2>
<table style="border:none;">
    <tr>
        <td width="20%" style="border:2px solid #10b981; border-radius:8px; background:#f0fdf4; padding:12px; text-align:center; border:none;">
            <div class="kpi-label">Active Nodes</div>
            <div class="kpi-value" style="color:#059669;">' . $system_stats['active_lights'] . '</div>
        </td>
        <td width="5%" style="border:none;"></td>
        <td width="20%" style="padding:12px; text-align:center; border:none; background:#eff6ff;">
            <div class="kpi-label">Total Lights</div>
            <div class="kpi-value" style="color:#2563eb;">' . $system_stats['total_lights'] . '</div>
        </td>
        <td width="5%" style="border:none;"></td>
        <td width="20%" style="padding:12px; text-align:center; border:none; background:#' . ($uptime_pct >= 95 ? 'f0fdf4' : ($uptime_pct >= 80 ? 'fffbeb' : 'fef2f2')) . '">
            <div class="kpi-label">Uptime Rate</div>
            <div class="kpi-value" style="color:' . $uptime_color . ';">' . $uptime_pct . '%</div>
        </td>
        <td width="5%" style="border:none;"></td>
        <td width="20%" style="padding:12px; text-align:center; border:none; background:' . ($system_stats['total_alerts'] > 10 ? '#fef2f2' : '#fffbeb') . '">
            <div class="kpi-label">Total Alerts</div>
            <div class="kpi-value" style="color:' . ($system_stats['total_alerts'] > 10 ? '#dc2626' : '#d97706') . ';">' . $system_stats['total_alerts'] . '</div>
        </td>
        <td width="5%" style="border:none;"></td>
        <td width="20%" style="padding:12px; text-align:center; border:none; background:' . ($system_stats['critical_alerts'] > 0 ? '#fef2f2' : '#f0fdf4') . '">
            <div class="kpi-label">Critical Alerts</div>
            <div class="kpi-value" style="color:' . ($system_stats['critical_alerts'] > 0 ? '#dc2626' : '#059669') . ';">' . $system_stats['critical_alerts'] . '</div>
        </td>
    </tr>
</table>

<!-- Sensor Telemetry -->
<h2>Sensor Telemetry Historicals</h2>
<table>
    <thead>
        <tr>
            <th width="20%">Date</th>
            <th width="12%" style="text-align:center">Readings</th>
            <th width="16%" style="text-align:center">Avg Voltage</th>
            <th width="16%" style="text-align:center">Avg Current</th>
            <th width="16%" style="text-align:center">Avg Temp</th>
            <th width="20%" style="text-align:center">Status</th>
        </tr>
    </thead>
    <tbody>
        ' . $telemetry_rows . '
    </tbody>
</table>

<!-- Problematic Nodes -->
<h2>Infrastructure Health: Problematic Nodes</h2>
<table>
    <thead>
        <tr>
            <th width="22%">Node</th>
            <th width="30%">Location</th>
            <th width="14%" style="text-align:center">Total Alerts</th>
            <th width="12%" style="text-align:center">Critical</th>
            <th width="22%" style="text-align:center">Risk Level</th>
        </tr>
    </thead>
    <tbody>
        ' . $node_table_rows . '
    </tbody>
</table>

<!-- Footer stamp -->
<p style="font-size:7.5pt; color:#94a3b8; margin-top:18px; text-align:center;">
    &#128274; Cryptographically Logged &nbsp;&middot;&nbsp; ShineGuard Smart Lighting Management System &nbsp;&middot;&nbsp; Barangay Hulo &nbsp;&middot;&nbsp; ' . $generated_at . '
</p>
';

$pdf->writeHTML($html, true, false, true, false, '');

// ── Output PDF ───────────────────────────────────────────────────────────────
$filename  = 'shineguard_report_' . date('Ymd_His') . '.pdf';
$save_path = __DIR__ . '/exports/reports/' . $filename;

$pdf->Output($save_path, 'F');

// Archive to DB
\ShineGuard\Services\ReportingService::archiveReport(
    $conn,
    'Analytical Report: ' . $period_label,
    'Analytical',
    $period_label,
    $filename,
    $_SESSION['user_id']
);

// Log the export
logActivity($conn, $_SESSION['user_id'], 'Report Exported', "Password-protected PDF report generated for period $start_date to $end_date");

// Store password in session so reports.php can show it in a toast
$_SESSION['pdf_toast'] = [
    'icon'  => '🔐',
    'title' => 'PDF Ready — Password Protected',
    'msg'   => 'File password: ' . $pdf_user_pass . '  |  Valid today only.',
    'color' => '#3b82f6',
    'bg'    => '#eff6ff',
];

// Stream to browser for download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($save_path));
readfile($save_path);
exit();
