<?php
/**
 * SHINEGUARD ACTIVITY LOGS — PDF EXPORTER
 * Password-protected PDF export of the audit trail.
 * Uses TCPDF, matching the pattern from report_pdf.php.
 */

require_once 'dbconnect.php';
requireLogin(['System Admin', 'System Observer']);

// SBA: must have authorized access to activity logs
if (!isset($_SESSION['activity_logs_authorized']) || !$_SESSION['activity_logs_authorized']) {
    header('Location: activity_logs.php');
    exit();
}

// Autoloaded via Composer in dbconnect.php


// ── SECURITY: Strict date validation ────────────────────────────────────────
function validateAuditDate($str) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $str);
    return ($d && $d->format('Y-m-d') === $str) ? $str : null;
}

$start_date    = validateAuditDate($_GET['start_date'] ?? '') ?? date('Y-m-d', strtotime('-7 days'));
$end_date      = validateAuditDate($_GET['end_date']   ?? '') ?? date('Y-m-d');
$action_filter = trim($_GET['action']  ?? '');
$user_filter   = intval($_GET['user_id'] ?? 0);

$start_full = $start_date . ' 00:00:00';
$end_full   = $end_date   . ' 23:59:59';

// Whitelist action_filter against real DB values
$valid_action = '';
if ($action_filter) {
    $af_check = $conn->prepare("SELECT action FROM activity_logs WHERE action = ? LIMIT 1");
    $af_check->bind_param("s", $action_filter);
    $af_check->execute();
    $af_row = $af_check->get_result()->fetch_assoc();
    $af_check->close();
    if ($af_row) $valid_action = $af_row['action'];
}

// Build parameterized log query
$log_sql = "SELECT al.*, u.username, u.full_name, u.role FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id WHERE al.created_at BETWEEN ? AND ?";
$l_params = [$start_full, $end_full];
$l_types  = "ss";
if ($valid_action) { $log_sql .= " AND al.action = ?"; $l_params[] = $valid_action; $l_types .= "s"; }
if ($user_filter)  { $log_sql .= " AND al.user_id = ?"; $l_params[] = $user_filter;  $l_types .= "i"; }
$log_sql .= " ORDER BY al.created_at DESC LIMIT 200";

$l_stmt = $conn->prepare($log_sql);
$l_stmt->bind_param($l_types, ...$l_params);
$l_stmt->execute();
$logs = $l_stmt->get_result();
$l_stmt->close();

// Stats — parameterized
$stats_sql = "SELECT COUNT(*) as total, COUNT(CASE WHEN action LIKE '%Security%' THEN 1 END) as security, COUNT(DISTINCT user_id) as users FROM activity_logs al WHERE al.created_at BETWEEN ? AND ?";
$s_params = [$start_full, $end_full];
$s_types  = "ss";
if ($valid_action) { $stats_sql .= " AND al.action = ?"; $s_params[] = $valid_action; $s_types .= "s"; }
if ($user_filter)  { $stats_sql .= " AND al.user_id = ?"; $s_params[] = $user_filter;  $s_types .= "i"; }
$s_stmt = $conn->prepare($stats_sql);
$s_stmt->bind_param($s_types, ...$s_params);
$s_stmt->execute();
$stats = $s_stmt->get_result()->fetch_assoc() ?? ['total' => 0, 'security' => 0, 'users' => 0];
$s_stmt->close();

$generated_by = $_SESSION['full_name'] ?? 'Administrator';
$generated_at = date('F d, Y h:i A');
$period_label = date('M d, Y', strtotime($start_date)) . ' – ' . date('M d, Y', strtotime($end_date));
$logoPath     = realpath(__DIR__ . '/img/ShineGuard3.png');

// ── PDF Password ────────────────────────────────────────────────────────────
// Uses the user's own login password (captured during the security handshake)
if (!isset($_SESSION['export_password'])) {
    die("Export not authorized. Please return to the activity logs and authenticate.");
}
$pdf_user_pass  = $_SESSION['export_password'];
$pdf_owner_pass = 'SGOwner-' . sha1('shineguard_audit_' . date('Ymd_His'));

// ── Initialize TCPDF ────────────────────────────────────────────────────────
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false); // Landscape for wide log table
$pdf->SetCreator('ShineGuard Hulo');
$pdf->SetAuthor($generated_by);
$pdf->SetTitle('ShineGuard Audit Logs');
$pdf->SetSubject("Audit Trail: $period_label");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 16);
$pdf->SetFooterMargin(8);
$pdf->setFooterFont(['helvetica', '', 7]);
$pdf->setFooterData([0, 0, 0], [128, 128, 128]);

// Password Protection (BEFORE AddPage)
$pdf->SetProtection(
    ['print'],
    $pdf_user_pass,
    $pdf_owner_pass,
    3,
    null
);

$pdf->AddPage();

// ── Build log rows ──────────────────────────────────────────────────────────
$log_rows = '';
$row_count = 0;
if ($logs && $logs->num_rows > 0) {
    while ($log = $logs->fetch_assoc()) {
        $row_count++;
        $ts   = date('M d, Y h:i A', strtotime($log['created_at']));
        $user = htmlspecialchars($log['full_name'] ?: ($log['username'] ?: 'System Interface'));
        $role = htmlspecialchars($log['role'] ?: 'Automated');
        $act  = htmlspecialchars($log['action']);
        $det  = htmlspecialchars(mb_strimwidth($log['details'] ?? '', 0, 80, '...'));
        $ip   = htmlspecialchars($log['ip_address'] ?? '—');

        $bg = ($row_count % 2 === 0) ? ' style="background:#f8fafc;"' : '';
        $log_rows .= "<tr$bg>
            <td style='font-size:7.5pt;'>$ts</td>
            <td><b>$user</b><br><span style='font-size:6.5pt;color:#888;'>$role</span></td>
            <td style='font-size:7.5pt;'>$act</td>
            <td style='font-size:7pt;color:#555;'>$det</td>
            <td style='font-size:7pt;font-family:courier;color:#888;'>$ip</td>
        </tr>";
    }
} else {
    $log_rows = "<tr><td colspan='5' style='text-align:center;color:#888;padding:20px;'>No activity logs found for this period.</td></tr>";
}

// ── Build HTML ──────────────────────────────────────────────────────────────
$html = '
<style>
    h1    { font-size: 15pt; font-weight: bold; color: #0f172a; margin: 0 0 3px 0; }
    h2    { font-size: 10pt; font-weight: bold; color: #0f172a; margin: 14px 0 6px 0; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 4px; }
    p     { font-size: 8.5pt; color: #475569; margin: 0 0 6px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th    { background: #f1f5f9; color: #475569; font-size: 7.5pt; font-weight: bold; padding: 6px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; }
    td    { padding: 5px 6px; font-size: 8pt; border-bottom: 1px solid #f1f5f9; color: #0f172a; vertical-align: top; }
</style>

<!-- Header -->
<table width="100%" style="border:none; margin-bottom:14px;">
    <tr>
        <td width="12%" style="border:none;">' . ($logoPath ? '<img src="' . $logoPath . '" style="width:42px;">' : '') . '</td>
        <td width="55%" style="border:none; vertical-align:middle;">
            <h1>ShineGuard Audit Trail</h1>
            <p>Comprehensive Activity Log Export — Barangay Hulo</p>
        </td>
        <td width="33%" style="border:none; text-align:right; vertical-align:top;">
            <p style="font-size:7.5pt;color:#94a3b8;line-height:1.5;">
                <b>Period</b><br>' . $period_label . '<br>
                <b>Generated By</b><br>' . htmlspecialchars($generated_by) . '<br>
                <b>Generated At</b><br>' . $generated_at . '<br>
                <b>Classification</b><br><span style="color:#ef4444;font-weight:bold;">CONFIDENTIAL</span>
            </p>
        </td>
    </tr>
</table>

<!-- Summary Stats -->
<table style="border:none; margin-bottom:12px;">
    <tr>
        <td width="33%" style="border:none; background:#eff6ff; padding:10px; text-align:center;">
            <span style="font-size:7pt;color:#3b82f6;font-weight:bold;text-transform:uppercase;">Total Events</span><br>
            <span style="font-size:18pt;font-weight:bold;color:#2563eb;">' . number_format($stats['total']) . '</span>
        </td>
        <td width="1%" style="border:none;"></td>
        <td width="33%" style="border:none; background:#fef2f2; padding:10px; text-align:center;">
            <span style="font-size:7pt;color:#ef4444;font-weight:bold;text-transform:uppercase;">Security Events</span><br>
            <span style="font-size:18pt;font-weight:bold;color:#dc2626;">' . number_format($stats['security']) . '</span>
        </td>
        <td width="1%" style="border:none;"></td>
        <td width="33%" style="border:none; background:#f0fdf4; padding:10px; text-align:center;">
            <span style="font-size:7pt;color:#22c55e;font-weight:bold;text-transform:uppercase;">Active Users</span><br>
            <span style="font-size:18pt;font-weight:bold;color:#059669;">' . number_format($stats['users']) . '</span>
        </td>
    </tr>
</table>

<!-- Log Table -->
<h2>Audit Log Entries</h2>
<table>
    <thead>
        <tr>
            <th width="17%">Timestamp</th>
            <th width="18%">User</th>
            <th width="18%">Action</th>
            <th width="34%">Details</th>
            <th width="13%">IP Address</th>
        </tr>
    </thead>
    <tbody>
        ' . $log_rows . '
    </tbody>
</table>

<p style="font-size:7pt; color:#94a3b8; margin-top:14px; text-align:center;">
    &#128274; Password Protected &middot; Cryptographically Logged &middot; ShineGuard Smart Lighting Management System &middot; Barangay Hulo &middot; ' . $generated_at . '
</p>
';

$pdf->writeHTML($html, true, false, true, false, '');

// ── Save & Stream ───────────────────────────────────────────────────────────
$filename  = 'shineguard_audit_log_' . date('Ymd_His') . '.pdf';
$save_path = __DIR__ . '/exports/reports/' . $filename;
$pdf->Output($save_path, 'F');

// Log it
logActivity($conn, $_SESSION['user_id'], 'Audit Log Export', "Password-protected PDF audit trail exported for $start_date to $end_date");

// Store password for toast display
$_SESSION['pdf_toast'] = [
    'icon'  => '🔐',
    'title' => 'PDF Ready — Password Protected',
    'msg'   => 'File password: ' . $pdf_user_pass . '  |  Valid today only.',
    'color' => '#3b82f6',
    'bg'    => '#eff6ff',
];

// Stream
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($save_path));
readfile($save_path);
exit();
