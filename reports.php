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

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$system_stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM streetlights) as total_lights,
    (SELECT COUNT(*) FROM streetlights WHERE status = 'Active') as active_lights,
    (SELECT COUNT(*) FROM streetlights WHERE status = 'Maintenance') as maintenance_lights,
    (SELECT COUNT(*) FROM alerts WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59') as total_alerts,
    (SELECT COUNT(*) FROM alerts WHERE severity = 'High' AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59') as critical_alerts,
    (SELECT COUNT(*) FROM alerts WHERE status = 'Resolved' AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59') as resolved_alerts,
    (SELECT COUNT(*) FROM maintenance_logs WHERE maintenance_date BETWEEN '$start_date' AND '$end_date 23:59:59') as maintenance_count,
    (SELECT AVG(completion_time) FROM maintenance_logs WHERE maintenance_date BETWEEN '$start_date' AND '$end_date 23:59:59' AND status = 'Completed') as avg_completion_time")->fetch_assoc();

$energy_report = $conn->query("SELECT 
    DATE(timestamp) as date,
    COUNT(*) as readings,
    AVG(voltage) as avg_voltage,
    AVG(current_consumption) as avg_current,
    AVG(temperature) as avg_temperature,
    AVG(brightness_level) as avg_brightness
FROM sensor_data 
WHERE timestamp BETWEEN '$start_date' AND '$end_date 23:59:59'
GROUP BY DATE(timestamp)
ORDER BY date DESC
LIMIT 30");

$alerts_by_type = $conn->query("SELECT 
    alert_type,
    severity,
    COUNT(*) as count
FROM alerts 
WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
GROUP BY alert_type, severity
ORDER BY count DESC");

$problematic_lights = $conn->query("SELECT 
    s.node_name,
    s.location,
    COUNT(a.alert_id) as alert_count,
    COUNT(CASE WHEN a.severity = 'High' THEN 1 END) as critical_count,
    MAX(a.created_at) as last_alert
FROM streetlights s
LEFT JOIN alerts a ON s.light_id = a.light_id AND a.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
GROUP BY s.light_id
HAVING alert_count > 0
ORDER BY critical_count DESC, alert_count DESC
LIMIT 10");

$maintenance_perf = $conn->query("SELECT 
    u.full_name as technician,
    COUNT(ml.log_id) as work_orders,
    COUNT(CASE WHEN ml.status = 'Completed' THEN 1 END) as completed,
    AVG(ml.completion_time) as avg_time
FROM maintenance_logs ml
INNER JOIN users u ON ml.user_id = u.user_id
WHERE ml.maintenance_date BETWEEN '$start_date' AND '$end_date 23:59:59'
GROUP BY ml.user_id
ORDER BY work_orders DESC");

$snapshots_query = $conn->query("SELECT COUNT(*) as snapshot_count FROM camera_snapshots WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'");
$snapshot_count = ($snapshots_query) ? $snapshots_query->fetch_assoc()['snapshot_count'] : 0;

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    
    require_once('tcpdf/tcpdf.php');

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator('ShineGuard System');
    $pdf->SetAuthor('ShineGuard Hulo');
    $pdf->SetTitle('ShineGuard Audit Report - ' . date('Y-m-d'));

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    if (!empty($_POST['admin_password'])) {
        $user_pass = $_POST['admin_password'];
        
        $pdf->SetProtection(array('print', 'copy'), $user_pass, null, 0, null);
    }

    $generator_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown User';
    $current_time = date('F j, Y, g:i a T');
    $range_str = date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date));

$html = <<<EOD
<style>
    h1 { color: #0f172a; font-size: 22pt; margin-bottom: 0; padding-bottom: 0; }
    h2 { color: #1e40af; font-size: 13pt; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-top: 25px; }
    p { color: #475569; font-size: 10pt; line-height: 1.5; }
    table.data-table { width: 100%; border-collapse: collapse; margin-top: 12px; border: 1px solid #cbd5e1; }
    th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; border-bottom: 2px solid #cbd5e1; padding: 12px; text-align: left; font-size: 10pt; }
    td { border-bottom: 1px solid #e2e8f0; padding: 10px; font-size: 9pt; color: #334155; }
    .stripe { background-color: #f8fafc; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
</style>

<table width="100%" cellpadding="5">
    <tr>
        <td width="50%" style="vertical-align: middle;">
            <img src="img/ShineGuard3.png" style="width: 160px;" />
        </td>
        <td width="50%" style="text-align: right; vertical-align: middle;">
            <p style="margin: 0; font-size: 9pt; color: #64748b;">
                <strong>Report Period:</strong> {$range_str}<br>
                <strong>Generated By:</strong> {$generator_name}<br>
                <strong>Printed On:</strong> {$current_time}
            </p>
        </td>
    </tr>
</table>
<hr style="color: #cbd5e1; height: 1px;" />
<h1 style="text-align: center; margin-top: 15px;">Official System Audit Report</h1>
EOD;

    if (!empty($_POST['chart_image'])) {
        
        $img_base64 = preg_replace('#^data:image/[^;]+;base64,#', '', $_POST['chart_image']);
        
        $html .= <<<EOD
<div style="text-align: center; margin-bottom: 20px;">
    <h2>Visual Telemetry Overview</h2>
    <img src="@{$img_base64}" style="width: 100%; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;" />
</div>
EOD;
    }

    $act_lights = $system_stats['active_lights'] ?? 0;
    $crit_alerts = $system_stats['critical_alerts'] ?? 0;
    
    $system_health = ($crit_alerts > 5) ? 'Attention Required' : 'Nominal';
    $summary_intro = "During this period, the ShineGuard system maintained <strong>{$act_lights}</strong> active illuminating streetlights. System health is considered <strong>{$system_health}</strong> with {$crit_alerts} critical alerts recorded.";
    $summary_cctv = " Administrators captured <strong>{$snapshot_count}</strong> secure CCTV snapshots for surveillance and accountability auditing.";
    
    $html .= <<<EOD
<div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px; margin-bottom: 20px; font-size: 10pt; color: #475569; line-height: 1.5;">
    <strong>Executive Summary:</strong> {$summary_intro} {$summary_cctv}
</div>

<br><br>

<h2>1. System Overview</h2>
<table class="data-table">
    <tr><th width="60%">Metric</th><th width="40%" class="text-right">Value</th></tr>
    <tr><td>Total Streetlights Registed</td><td class="text-right" style="font-weight:bold;">{$system_stats['total_lights']}</td></tr>
    <tr class="stripe"><td>Active Illuminating Lights</td><td class="text-right" style="font-weight:bold; color:#047857;">{$system_stats['active_lights']}</td></tr>
    <tr><td>Currently Under Maintenance</td><td class="text-right" style="font-weight:bold; color:#b45309;">{$system_stats['maintenance_lights']}</td></tr>
    <tr class="stripe"><td>Total Alerts Triggered</td><td class="text-right" style="font-weight:bold;">{$system_stats['total_alerts']}</td></tr>
    <tr><td>Critical Severity Alerts</td><td class="text-right" style="font-weight:bold; color:#dc2626;">{$system_stats['critical_alerts']}</td></tr>
    <tr class="stripe"><td>Resolved Alerts</td><td class="text-right" style="font-weight:bold; color:#059669;">{$system_stats['resolved_alerts']}</td></tr>
    <tr><td>Maintenance Work Orders</td><td class="text-right" style="font-weight:bold;">{$system_stats['maintenance_count']}</td></tr>
    <tr class="stripe"><td>Avg. Resolution Time (Mins)</td><td class="text-right" style="font-weight:bold;">
EOD;
        $html .= round($system_stats['avg_completion_time'] ?? 0);
        $html .= <<<EOD
        </td></tr>
</table>

<br><br>

<h2>2. Alerts Breakdown</h2>
<table class="data-table">
    <tr>
        <th width="40%">Alert Type</th>
        <th width="30%">Severity</th>
        <th width="30%" class="text-center">Occurrences</th>
    </tr>
EOD;
    if ($alerts_by_type->num_rows > 0) {
        $alerts_by_type->data_seek(0);
        $rc = 0;
        while ($row = $alerts_by_type->fetch_assoc()) {
            $class = ($rc++ % 2 == 1) ? ' class="stripe"' : '';
            $html .= "<tr{$class}><td>{$row['alert_type']}</td><td>{$row['severity']}</td><td class=\"text-center\">{$row['count']}</td></tr>";
        }
    } else {
        $html .= "<tr><td colspan=\"3\" class=\"text-center\">No alerts recorded for this period.</td></tr>";
    }
$html .= <<<EOD
</table>

<br><br>

<h2>3. Top 10 Problematic Streetlights</h2>
<table class="data-table">
    <tr>
        <th width="30%">Node Name</th>
        <th width="30%">Location</th>
        <th width="15%" class="text-center">Total Alerts</th>
        <th width="15%" class="text-center">Critical</th>
        <th width="10%">Last Alert</th>
    </tr>
EOD;
    if ($problematic_lights->num_rows > 0) {
        $problematic_lights->data_seek(0);
        $rc = 0;
        while ($row = $problematic_lights->fetch_assoc()) {
            $class = ($rc++ % 2 == 1) ? ' class="stripe"' : '';
            $ld = date('M d', strtotime($row['last_alert']));
            $html .= "<tr{$class}><td>{$row['node_name']}</td><td>{$row['location']}</td><td class=\"text-center\">{$row['alert_count']}</td><td class=\"text-center\">{$row['critical_count']}</td><td>{$ld}</td></tr>";
        }
    } else {
        $html .= "<tr><td colspan=\"5\" class=\"text-center\">No problematic streetlights recorded.</td></tr>";
    }
$html .= <<<EOD
</table>

<br><br>

<h2>4. Technician Maintenance Performance</h2>
<table class="data-table">
    <tr>
        <th width="40%">Technician</th>
        <th width="20%" class="text-center">Work Orders</th>
        <th width="20%" class="text-center">Completed</th>
        <th width="20%" class="text-center">Avg Time (Mins)</th>
    </tr>
EOD;
    if ($maintenance_perf->num_rows > 0) {
        $maintenance_perf->data_seek(0);
        $rc = 0;
        while ($row = $maintenance_perf->fetch_assoc()) {
            $class = ($rc++ % 2 == 1) ? ' class="stripe"' : '';
            $at = round($row['avg_time'], 1);
            $html .= "<tr{$class}><td>{$row['technician']}</td><td class=\"text-center\">{$row['work_orders']}</td><td class=\"text-center\">{$row['completed']}</td><td class=\"text-center\">{$at}</td></tr>";
        }
    } else {
        $html .= "<tr><td colspan=\"4\" class=\"text-center\">No maintenance logs recorded.</td></tr>";
    }
$html .= <<<EOD
</table>

<br><br>

<h2>5. Energy & Sensor Daily Averages</h2>
<table class="data-table">
    <tr>
        <th width="18%">Date</th>
        <th width="12%" class="text-center">Readings</th>
        <th width="16%" class="text-right">Avg Volt (V)</th>
        <th width="18%" class="text-right">Avg Curr (A)</th>
        <th width="18%" class="text-right">Avg Temp (&deg;C)</th>
        <th width="18%" class="text-right">Avg Lux</th>
    </tr>
EOD;
    if ($energy_report->num_rows > 0) {
        $energy_report->data_seek(0);
        $rc = 0;
        while ($row = $energy_report->fetch_assoc()) {
            $class = ($rc++ % 2 == 1) ? ' class="stripe"' : '';
            $v = number_format($row['avg_voltage'], 2);
            $c = number_format($row['avg_current'], 3);
            $t = number_format($row['avg_temperature'], 1);
            $l = number_format($row['avg_brightness'], 1);
            $html .= "<tr{$class}><td>{$row['date']}</td><td class=\"text-center\">{$row['readings']}</td><td class=\"text-right\">{$v}</td><td class=\"text-right\">{$c}</td><td class=\"text-right\">{$t}</td><td class=\"text-right\">{$l}</td></tr>";
        }
    } else {
        $html .= "<tr><td colspan=\"6\" class=\"text-center\">No sensor telemetry recorded.</td></tr>";
    }
$html .= <<<EOD
</table>

<br><br>

<h2>6. System Audit Trail (Raw Logs)</h2>
<table class="data-table">
    <tr>
        <th width="18%">Timestamp</th>
        <th width="15%">User</th>
        <th width="15%">Action</th>
        <th width="15%">IP Address</th>
        <th width="37%">Detailed Operations Log</th>
    </tr>
EOD;
    
    $audit_query = "SELECT al.created_at, u.username, al.action, al.ip_address, al.details 
                    FROM activity_logs al 
                    LEFT JOIN users u ON al.user_id = u.user_id 
                    WHERE al.created_at BETWEEN '$start_date' AND '$end_date 23:59:59' 
                    ORDER BY al.created_at DESC";
    $audit_logs = $conn->query($audit_query);
    
    if ($audit_logs && $audit_logs->num_rows > 0) {
        $rc = 0;
        while ($row = $audit_logs->fetch_assoc()) {
            $class = ($rc++ % 2 == 1) ? ' class="stripe"' : '';
            $usr = $row['username'] ?? 'System';
            $html .= "<tr{$class}><td>{$row['created_at']}</td><td>{$usr}</td><td>{$row['action']}</td><td>{$row['ip_address']}</td><td>{$row['details']}</td></tr>";
        }
    } else {
        $html .= "<tr><td colspan=\"5\" class=\"text-center\">No audit logs found for this period.</td></tr>";
    }

$html .= <<<EOD
</table>
<br><br>
<p style="text-align: center; color: #94a3b8; font-size: 8pt;">
  -- END OF REPORT --<br>
  ShineGuard Smart Monitoring System &copy; 2026
</p>
EOD;

    $pdf->writeHTML($html, true, false, true, false, '');

    ob_end_clean();
    $pdf->Output('shineguard_audit_report_' . date('Ymd_Hi') . '.pdf', 'D');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>Reports - Shine Guard Hulo</title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
<?php include 'assets/style.css'; ?>
:root { 
    --theme-color: <?php echo $theme_color; ?>; 
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
}

:root {
  --bg:             #f0f4f8;
  --surface:        #ffffff;
  --surface-2:      #f7f9fc;
  --border:         #e4e9f0;
  --border-light:   #edf1f7;
  --text-primary:   #1a2035;
  --text-secondary: #6b7a99;
  --text-muted:     #a0aec0;
  --blue:           #3b82f6;
  --blue-dim:       #eff6ff;
  --blue-border:    #bfdbfe;
  --green:          #22c55e;
  --green-dim:      #f0fdf4;
  --green-border:   #bbf7d0;
  --red:            #ef4444;
  --red-dim:        #fef2f2;
  --red-border:     #fecaca;
  --purple:         #8b5cf6;
  --purple-dim:     #f5f3ff;
  --purple-border:  #ddd6fe;
  --amber:          #f59e0b;
  --amber-dim:      #fffbeb;
  --amber-border:   #fde68a;
  --radius:         16px;
  --radius-sm:      10px;
  --shadow:         0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.05);
  --shadow-md:      0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
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
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--text-primary);
  text-transform: uppercase;
  margin-bottom: 0.3rem;
}

.page-header p {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 1.6rem 1.8rem;
  margin-bottom: 1.4rem;
  overflow: hidden;
}

.panel.panel-filter  { border-top: 3px solid var(--blue); }
.panel.panel-overview { border-top: 3px solid var(--green); }
.panel.panel-chart   { border-top: 3px solid var(--purple); }
.panel.panel-table   { border-top: 3px solid var(--blue); }
.panel.panel-problems { border-top: 3px solid var(--red); }

.panel h2 {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.3rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.filter-form {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-form .form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-form label {
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--text-secondary);
}

.filter-form input[type="date"] {
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0 0.85rem;
  height: 38px;
  box-sizing: border-box;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}

.filter-form input[type="date"]:focus {
  border-color: var(--blue);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}

.btn-primary {
  background: var(--green);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.83rem;
  font-weight: 700;
  padding: 0 1.4rem;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all .15s;
  box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.4);
  white-space: nowrap;
}

.btn-primary:hover {
  background: #16a34a;
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(34, 197, 94, 0.5);
}

.btn-export {
  background: var(--red);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.83rem;
  font-weight: 700;
  padding: 0 1.4rem;
  height: 38px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  cursor: pointer;
  transition: all .15s;
  box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4);
  white-space: nowrap;
}

.btn-export:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.5);
  color: #fff;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.stat-card {
  border-radius: var(--radius-sm);
  padding: 1.2rem 1.3rem;
  position: relative;
  overflow: hidden;
}

.stat-card.green  { background: linear-gradient(135deg, var(--green-dim), #d1fae5); border: 1px solid var(--green-border); }
.stat-card.red    { background: linear-gradient(135deg, var(--red-dim), #fecaca); border: 1px solid var(--red-border); }
.stat-card.blue   { background: linear-gradient(135deg, var(--blue-dim), #bfdbfe); border: 1px solid var(--blue-border); }
.stat-card.purple { background: linear-gradient(135deg, var(--purple-dim), #ddd6fe); border: 1px solid var(--purple-border); }

.stat-card-label {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.stat-card.green  .stat-card-label { color: #065f46; }
.stat-card.red    .stat-card-label { color: #991b1b; }
.stat-card.blue   .stat-card-label { color: #1e40af; }
.stat-card.purple .stat-card-label { color: #5b21b6; }

.stat-card-value {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-card.green  .stat-card-value { color: #047857; }
.stat-card.red    .stat-card-value { color: #dc2626; }
.stat-card.blue   .stat-card-value { color: #2563eb; }
.stat-card.purple .stat-card-value { color: #7c3aed; }

.stat-card-sub {
  font-size: 0.72rem;
  font-weight: 500;
}

.stat-card.green  .stat-card-sub { color: #059669; }
.stat-card.red    .stat-card-sub { color: #dc2626; }

#energyChart {
  max-height: 340px;
  width: 100% !important;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

thead tr {
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
}

thead th {
  font-size: 0.71rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-secondary);
  padding: 0.8rem 1.1rem;
  text-align: left;
  white-space: nowrap;
}

tbody tr {
  border-bottom: 1px solid var(--border-light);
  transition: background .1s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }

tbody td {
  padding: 0.85rem 1.1rem;
  color: var(--text-primary);
  font-weight: 500;
  vertical-align: middle;
}

.rank-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px; height: 28px;
  border-radius: 7px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  font-size: 0.76rem;
  font-weight: 700;
  color: var(--text-secondary);
}

.rank-badge.top { background: var(--amber-dim); border-color: var(--amber-border); color: var(--amber); }

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  padding: 0.26rem 0.65rem;
  border-radius: 30px;
  white-space: nowrap;
}

.badge.fail {
  background: var(--red-dim);
  color: var(--red);
  border: 1px solid var(--red-border);
}

.badge.fail::before { content: '●'; font-size: 0.5rem; }

.badge.ok {
  background: var(--green-dim);
  color: var(--green);
  border: 1px solid var(--green-border);
}

.badge.ok::before { content: '●'; font-size: 0.5rem; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>
<main class="main-content">

  <div class="page-header">
    <br>
    <br>
    <h1>📊 System Reports & Analytics</h1>
    <p>Real-time data analysis and performance metrics</p>
  </div>

  <div class="panel panel-filter">
    <h2>🗓️ Date Range</h2>
    <form id="reportFilterForm" method="GET" class="filter-form" onsubmit="event.preventDefault(); openGenerateModal();">
      <input type="hidden" name="success" value="report_generated">
      <div class="form-group">
        <label>Start Date</label>
        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" required>
      </div>
      <div class="form-group">
        <label>End Date</label>
        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" required>
      </div>
      <div class="form-group">
        <label style="visibility: hidden; pointer-events: none;">Actions</label>
        <div style="display: flex; gap: 0.75rem;">
          <button type="submit" class="btn-primary">Generate Report</button>
            <button type="button" class="btn-export" onclick="openExportModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download PDF
            </button>
        </div>
      </div>
    </form>
  </div>

  <div class="panel panel-overview">
    <h2>📈 System Overview (<?php echo date('M d', strtotime($start_date)); ?> – <?php echo date('M d, Y', strtotime($end_date)); ?>)</h2>
    <div class="stat-grid">

      <div class="stat-card green">
        <div class="stat-card-label">Active Lights</div>
        <div class="stat-card-value"><?php echo $system_stats['active_lights']; ?><span style="font-size:1rem;font-weight:600;opacity:.6;">/<?php echo $system_stats['total_lights']; ?></span></div>
      </div>

      <div class="stat-card <?php echo $system_stats['critical_alerts'] > 0 ? 'red' : 'green'; ?>">
        <div class="stat-card-label">Total Alerts</div>
        <div class="stat-card-value"><?php echo $system_stats['total_alerts']; ?></div>
        <div class="stat-card-sub"><?php echo $system_stats['critical_alerts']; ?> critical</div>
      </div>

      <div class="stat-card blue">
        <div class="stat-card-label">Maintenance</div>
        <div class="stat-card-value"><?php echo $system_stats['maintenance_count']; ?></div>
      </div>

    </div>
  </div>

  <div class="panel panel-chart">
    <h2>⚡ Sensor Data Trends</h2>
    <canvas id="energyChart"></canvas>
  </div>

  <div class="panel panel-table">
    <h2>📋 Detailed Sensor Readings</h2>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Readings</th>
            <th>Avg Voltage (V)</th>
            <th>Avg Current (A)</th>
            <th>Avg Temp (°C)</th>
            <th>Avg Brightness (lux)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $energy_report->data_seek(0);
          while ($row = $energy_report->fetch_assoc()):
          ?>
          <tr>
            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
            <td><?php echo $row['readings']; ?></td>
            <td><?php echo number_format($row['avg_voltage'], 2); ?></td>
            <td><?php echo number_format($row['avg_current'], 3); ?></td>
            <td><?php echo number_format($row['avg_temperature'], 1); ?></td>
            <td><?php echo number_format($row['avg_brightness'], 1); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($problematic_lights->num_rows > 0): ?>
  <div class="panel panel-problems">
    <h2>⚠️ Top 10 Most Problematic Streetlights</h2>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Rank</th>
            <th>Node</th>
            <th>Location</th>
            <th>Total Alerts</th>
            <th>Critical</th>
            <th>Last Alert</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rank = 1;
          while ($light = $problematic_lights->fetch_assoc()):
          ?>
          <tr>
            <td><span class="rank-badge <?php echo $rank <= 3 ? 'top' : ''; ?>">#<?php echo $rank++; ?></span></td>
            <td><?php echo htmlspecialchars($light['node_name']); ?></td>
            <td><?php echo htmlspecialchars($light['location']); ?></td>
            <td><?php echo $light['alert_count']; ?></td>
            <td><span class="badge fail"><?php echo $light['critical_count']; ?></span></td>
            <td><?php echo date('M d, H:i', strtotime($light['last_alert'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<div id="exportModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background:#eff6ff; width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">📥</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color:#0f172a;">Export Report?</div>
        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;" id="exportModalRange">Date range will appear here</div>
      </div>
    </div>
    
    <p style="font-size:0.9rem; color:#475569; line-height:1.6; margin-bottom:24px;">
      You are about to download a comprehensive system audit report in PDF format. Please confirm your administrator password to proceed.
    </p>

    <div style="margin-bottom: 24px; text-align: left;">
        <label for="exportAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="exportAdminPassword" placeholder="Enter password to confirm export" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="exportPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button type="button" onclick="closeExportModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
      <button type="button" id="exportConfirmBtn" onclick="confirmExport()" style="padding:10px 22px; border-radius:10px; border:none; background:#3b82f6; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; box-shadow:0 4px 12px rgba(59,130,246,0.35); transition:all 0.2s;" onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#3b82f6'; this.style.transform='translateY(0)';">📥 Download PDF</button>
    </div>
  </div>
</div>

<div id="generateModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background:#f0fdf4; width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">📊</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color:#0f172a;">Generate Report?</div>
        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;" id="generateModalRange">Date range will appear here</div>
      </div>
    </div>
    
    <p style="font-size:0.9rem; color:#475569; line-height:1.6; margin-bottom:24px;">
      This will process and display a detailed visual report containing all system metrics, sensor data, and alerts for the selected period.
    </p>

    <div style="margin-bottom: 24px; text-align: left;">
        <label for="generateAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="generateAdminPassword" placeholder="Enter password to confirm generation" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="generatePasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button type="button" onclick="closeGenerateModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
      <button type="button" id="generateConfirmBtn" onclick="confirmGenerate()" style="padding:10px 22px; border-radius:10px; border:none; background:#10b981; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; box-shadow:0 4px 12px rgba(16,185,129,0.35); transition:all 0.2s;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)';">📊 Generate Report</button>
    </div>
  </div>
</div>

<script>
function openGenerateModal() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    
    if (new Date(start) > new Date(end)) {
        showAppAlert("⚠️ Error: The end date must be after the start date.", "warning");
        return;
    }
    
    document.getElementById('generateModalRange').textContent = `Period: ${start} to ${end}`;
    
    const modal = document.getElementById('generateModal');
    modal.style.display = 'flex';

    const content = modal.querySelector('.modal-spring');
    if (content) {
        content.classList.remove('modal-spring');
        void content.offsetWidth;
        content.classList.add('modal-spring');
    }
}

function closeGenerateModal() {
    document.getElementById('generateModal').style.display = 'none';
    const pwdInput = document.getElementById('generateAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('generatePasswordError').style.display = 'none';
        document.getElementById('generateConfirmBtn').innerHTML = '📊 Generate Report';
        document.getElementById('generateConfirmBtn').disabled = false;
    }
}

async function confirmGenerate() {
    const pwdInput = document.getElementById('generateAdminPassword');
    const pwdError = document.getElementById('generatePasswordError');
    const btn = document.getElementById('generateConfirmBtn');
    
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
        
        const response = await fetch('reports.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('reportFilterForm').submit();
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = '📊 Generate Report';
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
}

function openExportModal() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    
    if (new Date(start) > new Date(end)) {
        showAppAlert("⚠️ Error: Cannot export. The end date must be after the start date.", "warning");
        return;
    }
    
    document.getElementById('exportModalRange').textContent = `Period: ${start} to ${end}`;
    
    const modal = document.getElementById('exportModal');
    modal.style.display = 'flex';

    const content = modal.querySelector('.modal-spring');
    if (content) {
        content.classList.remove('modal-spring');
        void content.offsetWidth;
        content.classList.add('modal-spring');
    }
}

function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
    const pwdInput = document.getElementById('exportAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('exportPasswordError').style.display = 'none';
        document.getElementById('exportConfirmBtn').innerHTML = '📥 Download PDF';
        document.getElementById('exportConfirmBtn').disabled = false;
    }
}

async function confirmExport() {
    const pwdInput = document.getElementById('exportAdminPassword');
    const pwdError = document.getElementById('exportPasswordError');
    const btn = document.getElementById('exportConfirmBtn');
    
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
        
        const response = await fetch('reports.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        if (data.success) {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            
            if (window.sgToast) {
                window.sgToast('📥', 'Export Started', 'Your PDF report is being generated and downloaded.', '#10b981', '#ecfdf5');
            }

            const chartCanvas = document.getElementById('energyChart');
            let imgData = '';
            if (chartCanvas) {

                const ctx = chartCanvas.getContext('2d');
                const origGlobalCompositeOp = ctx.globalCompositeOperation;
                ctx.globalCompositeOperation = "destination-over";
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, chartCanvas.width, chartCanvas.height);
                
                imgData = chartCanvas.toDataURL('image/png', 1.0);

                ctx.globalCompositeOperation = origGlobalCompositeOp;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `?export=pdf&start_date=${start}&end_date=${end}`;
            
            if (imgData) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'chart_image';
                input.value = imgData;
                form.appendChild(input);
            }

            if (pwdInput.value) {
                const pwdHidden = document.createElement('input');
                pwdHidden.type = 'hidden';
                pwdHidden.name = 'admin_password';
                pwdHidden.value = pwdInput.value;
                form.appendChild(pwdHidden);
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            closeExportModal();
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = '📥 Download PDF';
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
}

<?php
$energy_report->data_seek(0);
$dates = [];
$voltages = [];
$temps = [];
while ($row = $energy_report->fetch_assoc()) {
    $dates[]    = date('M d', strtotime($row['date']));
    $voltages[] = round($row['avg_voltage'], 2);
    $temps[]    = round($row['avg_temperature'], 1);
}
?>

const energyCtx = document.getElementById('energyChart').getContext('2d');
new Chart(energyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_reverse($dates)); ?>,
        datasets: [{
            label: 'Avg Voltage (V)',
            data: <?php echo json_encode(array_reverse($voltages)); ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#3b82f6',
            pointRadius: 3,
            pointHoverRadius: 5,
            yAxisID: 'y',
            tension: 0.4,
            fill: true
        }, {
            label: 'Avg Temperature (°C)',
            data: <?php echo json_encode(array_reverse($temps)); ?>,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.06)',
            borderWidth: 2,
            pointBackgroundColor: '#ef4444',
            pointRadius: 3,
            pointHoverRadius: 5,
            yAxisID: 'y1',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                labels: {
                    font: { family: 'Inter', size: 12, weight: '600' },
                    color: '#6b7a99',
                    usePointStyle: true,
                    pointStyleWidth: 8
                }
            }
        },
        scales: {
            x: {
                grid: { color: '#f0f4f8' },
                ticks: { font: { family: 'Inter', size: 11 }, color: '#a0aec0' }
            },
            y: {
                type: 'linear',
                position: 'left',
                grid: { color: '#f0f4f8' },
                ticks: { font: { family: 'Inter', size: 11 }, color: '#a0aec0' },
                title: { display: true, text: 'Voltage (V)', font: { family: 'Inter', size: 11, weight: '600' }, color: '#6b7a99' }
            },
            y1: {
                type: 'linear',
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: { font: { family: 'Inter', size: 11 }, color: '#a0aec0' },
                title: { display: true, text: 'Temperature (°C)', font: { family: 'Inter', size: 11, weight: '600' }, color: '#6b7a99' }
            }
        }
    }
});
</script>

<?php include 'assets/app_alert.php'; ?>
</body>
</html>
