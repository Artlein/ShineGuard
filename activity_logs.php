<?php
require_once 'dbconnect.php';
requireLogin(['System Admin', 'System Observer']);

// Standalone "Critical Access" Authorization Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'critical_auth') {
    checkCsrf();
    $password = $_POST['admin_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && password_verify($password, $res['password_hash'])) {
        $_SESSION['activity_logs_authorized'] = true;
        logActivity($conn, $user_id, 'Critical Access Authorized', 'User authorized access to sensitive audit logs');
        echo json_encode(['success' => true]);
    } else {
        logActivity($conn, $user_id, 'Critical Access Denied', 'Failed password verification for audit log access');
        echo json_encode(['success' => false, 'error' => 'Invalid administrator password. Access denied.']);
    }
    exit();
}

// ── SECURITY FEATURE: BASE DEVICE ENFORCEMENT ──
$current_device_token = $_COOKIE['sg_device_fp'] ?? '';
$is_base_device = false;
if ($current_device_token && isset($_SESSION['user_id'])) {
    $base_check = $conn->prepare("SELECT device_token FROM user_devices WHERE user_id = ? ORDER BY created_at ASC LIMIT 1");
    $base_check->bind_param("i", $_SESSION['user_id']);
    $base_check->execute();
    $base_res = $base_check->get_result()->fetch_assoc();
    $base_check->close();
    
    if ($base_res && $base_res['device_token'] === $current_device_token) {
        $is_base_device = true;
    }
}

// ── SECURITY FEATURE: DEVICE REVOCATION (KILL SWITCH) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_device_token'])) {
    if (!isset($_SESSION['activity_logs_authorized']) || !$_SESSION['activity_logs_authorized']) {
        exit("Unauthorized");
    }
    
    // ENFORCEMENT: Only Base Device can block others
    if (!$is_base_device) {
        logActivity($conn, $_SESSION['user_id'], 'Security Alert', "Unauthorized attempt to revoke a device from a non-base hardware unit.");
        exit("Security Alert: Only your original Base Device is authorized to use the Kill Switch.");
    }
    
    checkCsrf();
    $token_to_block = $_POST['block_device_token'];
    $mfa_code = trim($_POST['block_mfa_code'] ?? '');
    
    // MFA VERIFICATION
    require_once 'src/Services/TOTPService.php';
    $mfa_check = $conn->prepare("SELECT mfa_secret FROM users WHERE user_id = ? AND mfa_enabled = 1");
    $mfa_check->bind_param("i", $_SESSION['user_id']);
    $mfa_check->execute();
    $mfa_res = $mfa_check->get_result()->fetch_assoc();
    $mfa_check->close();
    
    if (!$mfa_res || !\ShineGuard\Services\TOTPService::verifyCode($mfa_res['mfa_secret'], $mfa_code)) {
        header("Location: activity_logs.php?error=invalid_mfa&start_date=" . urlencode($_GET['start_date'] ?? '') . "&end_date=" . urlencode($_GET['end_date'] ?? ''));
        exit();
    }
    
    // Update DB to block the device
    $block_stmt = $conn->prepare("UPDATE user_devices SET is_blocked = 1 WHERE device_token = ?");
    $block_stmt->bind_param("s", $token_to_block);
    if ($block_stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Security Alert', "Administrator permanently revoked and blocked an unrecognized device footprint: " . substr($token_to_block, 0, 8) . "...");
    }
    $block_stmt->close();
    
    // Redirect back to same page with success
    header("Location: activity_logs.php?success=device_blocked&start_date=" . urlencode($_GET['start_date'] ?? '') . "&end_date=" . urlencode($_GET['end_date'] ?? ''));
    exit();
}

// Access Gate
if (!isset($_SESSION['activity_logs_authorized']) || !$_SESSION['activity_logs_authorized']) {
    include 'includes/secure_auth_ui.php';
    exit();
}

$theme_color = '#3b82f6';
if (isset($conn)) {
    $theme_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($theme_result && $row = $theme_result->fetch_assoc()) {
        $theme_color = $row['config_value'];
    }
}

// ── SECURITY: Strict input validation ──────────────────────────────────────
function validateLogDate($str) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $str);
    return ($d && $d->format('Y-m-d') === $str) ? $str : null;
}

$start_date    = validateLogDate($_GET['start_date'] ?? '') ?? date('Y-m-d', strtotime('-7 days'));
$end_date      = validateLogDate($_GET['end_date']   ?? '') ?? date('Y-m-d');
$action_filter = trim($_GET['action']   ?? '');
$user_filter   = intval($_GET['user_id'] ?? 0);

$start_full = $start_date . ' 00:00:00';
$end_full   = $end_date   . ' 23:59:59';

// Whitelist action_filter against real values in DB
$valid_action = '';
if ($action_filter) {
    $af_check = $conn->prepare("SELECT action FROM activity_logs WHERE action = ? LIMIT 1");
    $af_check->bind_param("s", $action_filter);
    $af_check->execute();
    $af_row = $af_check->get_result()->fetch_assoc();
    $af_check->close();
    if ($af_row) $valid_action = $af_row['action'];
}

// --- PAGINATION LOGIC ---
$page = max(1, intval($_GET['page'] ?? 1));
$limit = (isset($_GET['export']) && $_GET['export'] === 'csv') ? 1000000 : 25;
$offset = ($page - 1) * $limit;

// Count Query for Pagination
$count_sql = "SELECT COUNT(*) as total FROM activity_logs al WHERE al.created_at BETWEEN ? AND ?";
$count_params = [$start_full, $end_full];
$count_types  = "ss";
if ($valid_action) { $count_sql .= " AND al.action = ?"; $count_params[] = $valid_action; $count_types .= "s"; }
if ($user_filter)  { $count_sql .= " AND al.user_id = ?"; $count_params[] = $user_filter;  $count_types .= "i"; }
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();
$total_pages = max(1, ceil($total_records / $limit));

// Build parameterized log query
$base_sql = "SELECT al.*, u.username, u.full_name, u.role FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id WHERE al.created_at BETWEEN ? AND ?";
$params = [$start_full, $end_full];
$types  = "ss";
if ($valid_action) { $base_sql .= " AND al.action = ?"; $params[] = $valid_action; $types .= "s"; }
if ($user_filter)  { $base_sql .= " AND al.user_id = ?"; $params[] = $user_filter;  $types .= "i"; }
$base_sql .= " ORDER BY al.created_at DESC LIMIT ?, ?";

$types .= "ii";
$params[] = $offset;
$params[] = $limit;

$log_stmt = $conn->prepare($base_sql);
$log_stmt->bind_param($types, ...$params);
$log_stmt->execute();
$logs = $log_stmt->get_result();
$log_stmt->close();

// Get unique actions for filter
$actions_res = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actions = [];
if ($actions_res) while($r = $actions_res->fetch_assoc()) $actions[] = $r['action'];

// Get users for filter
$users_res = $conn->query("SELECT user_id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name ASC");

// Export CSV logic
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $logs) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ShineGuard_Audit_Logs_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Timestamp', 'User', 'Role', 'Action', 'Details', 'IP Address']);
    while ($row = $logs->fetch_assoc()) {
        fputcsv($output, [
            $row['log_id'],
            $row['created_at'],
            \ShineGuard\Services\SecurityService::decrypt($row['full_name'] ?: ($row['username'] ?: 'System Interface')),
            $row['role'] ?: 'Automated',
            $row['action'],
            \ShineGuard\Services\SecurityService::decrypt($row['details']),
            $row['ip_address']
        ]);
    }
    fclose($output);
    exit();
}

// Stats for the current period
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

// ── SECURITY FEATURE: INTEGRITY VALIDATOR SCRIPT ──
$integrity_results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_integrity'])) {
    require_once 'src/Services/SecurityService.php';
    
    // We verify the logs currently visible in the filter
    $v_sql = "SELECT al.* FROM activity_logs al WHERE al.created_at BETWEEN ? AND ?";
    $v_params = [$start_full, $end_full];
    $v_types  = "ss";
    if ($valid_action) { $v_sql .= " AND al.action = ?"; $v_params[] = $valid_action; $v_types .= "s"; }
    if ($user_filter)  { $v_sql .= " AND al.user_id = ?"; $v_params[] = $user_filter;  $v_types .= "i"; }
    $v_sql .= " ORDER BY al.log_id ASC";
    
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param($v_types, ...$v_params);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    $v_stmt->close();
    
    // To verify a chain, we need the "Previous Hash" of the first record in our set.
    // If we're starting from the very first record ever, it's all zeros.
    $first_log = $conn->query("SELECT log_id FROM activity_logs ORDER BY log_id ASC LIMIT 1")->fetch_assoc();
    $current_prev_hash = str_repeat('0', 64);
    
    if ($v_res->num_rows > 0) {
        $v_data = $v_res->fetch_all(MYSQLI_ASSOC);
        
        // If the first log in our filter isn't the global first log, we need its actual predecessor
        if ($v_data[0]['log_id'] != $first_log['log_id']) {
            $pred_id = $v_data[0]['log_id'];
            // ── SECURITY HARDENING: Parameterized Chain Verification ──
            $pred_stmt = $conn->prepare("SELECT log_hash FROM activity_logs WHERE log_id < ? ORDER BY log_id DESC LIMIT 1");
            $pred_stmt->bind_param("i", $pred_id);
            $pred_stmt->execute();
            $pred_res = $pred_stmt->get_result();
            $pred_stmt->close();
            if ($pred_row = $pred_res->fetch_assoc()) {
                $current_prev_hash = $pred_row['log_hash'];
            }
        }

        foreach ($v_data as $row) {
            // ZERO-TRUST: Decrypt details before re-computing signature
            // (signature was computed on plaintext, so we must verify against plaintext)
            $plaintext_details = \ShineGuard\Services\SecurityService::decrypt($row['details']);
            $expected = \ShineGuard\Services\SecurityService::generateLogSignature(
                $current_prev_hash, 
                $row['user_id'], 
                $row['action'], 
                $plaintext_details, 
                $row['ip_address']
            );
            
            $integrity_results[$row['log_id']] = ($expected === $row['log_hash']);
            $current_prev_hash = $row['log_hash']; // Move to next link in chain
        }
    }
    
    if (!empty($integrity_results)) {
        logActivity($conn, $_SESSION['user_id'], 'Integrity Verification', 'User executed a mathematical forensic audit of the activity logs');
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Activity Logs - Shine Guard Hulo</title>
    <link rel="icon" type="image/png" href="img/ShineGuard3.png">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        :root { 
            --theme-color: <?php echo $theme_color; ?>;
            --glass: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            --font-main: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body { font-family: var(--font-main); }
        
        .main-content { padding: 2.2rem; background: #f8fafc; min-height: 100vh; }
        
        .glass-panel {
            background: var(--glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .sentinel-alert {
            animation: pulse-red 2s infinite;
            background: #fef2f2 !important;
            border-left: 4px solid #ef4444 !important;
        }

        @keyframes pulse-red {
            0% { background-color: #fef2f2; }
            50% { background-color: #fee2e2; }
            100% { background-color: #fef2f2; }
        }

        .action-badge.badge-unauthorized {
            background: #ef4444;
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .page-header { margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .page-header h1 { font-size: 2.2rem; font-weight: 800; margin: 0; color: #0f172a; letter-spacing: -0.04em; }
        .page-header p { color: #64748b; margin: 8px 0 0; font-size: 1.1rem; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.06);
            border-color: var(--theme-color);
        }

        .stat-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }

        .stat-card.total .stat-icon { background: #eff6ff; color: #3b82f6; }
        .stat-card.security .stat-icon { background: #fef2f2; color: #ef4444; }
        .stat-card.users .stat-icon { background: #f0fdf4; color: #22c55e; }

        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group { display: flex; flex-direction: column; gap: 10px; }
        .filter-group label { font-size: 13px; font-weight: 700; color: #475569; padding-left: 4px; }
        .filter-group select, .filter-group input {
            background: white;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 14px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: all 0.2s;
        }
        .filter-group select:focus, .filter-group input:focus { border-color: var(--theme-color); box-shadow: 0 0 0 4px rgba(var(--sb-accent-rgb), 0.1); }

        .btn-filter {
            background: var(--theme-color);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 4px 12px rgba(var(--sb-accent-rgb), 0.2);
        }
        .btn-filter:hover { opacity: 0.95; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(var(--sb-accent-rgb), 0.3); }

        .btn-export {
            background: white;
            color: #0f172a;
            border: 2px solid #e2e8f0;
            padding: 14px 28px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 10px;
            transition: all 0.3s;
        }
        .btn-export:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateY(-2px); }

        .table-panel { padding: 0; overflow: hidden; border-radius: 24px; }
        .log-table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { 
            text-align: left; padding: 20px; 
            font-size: 13px; font-weight: 700; 
            color: #64748b; text-transform: uppercase;
            border-bottom: 2px solid #f1f5f9;
            background: rgba(248, 250, 252, 0.5);
        }
        td { padding: 20px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr { transition: background 0.2s; }
        tr:hover td { background: rgba(59, 130, 246, 0.02); }

        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        
        .badge-security { background: #fef2f2; color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-control { background: #f0fdf4; color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .badge-auth { background: #eff6ff; color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .badge-default { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 12px;
            background: linear-gradient(135deg, var(--theme-color), #2dd4bf);
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px;
        }
        .user-info { display: flex; flex-direction: column; }
        .user-info strong { color: #0f172a; font-size: 14px; }
        .user-info span { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; }

        .timestamp-col { color: #0f172a; font-weight: 600; display: flex; flex-direction: column; gap: 4px; }
        .time-label { color: #94a3b8; font-size: 12px; font-weight: 600; }

        /* Dark Mode Overrides */
        .dark-mode .main-content { background: #0f172a; }
        .dark-mode .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .stat-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark-mode .stat-value, .dark-mode .page-header h1 { color: #f1f5f9; }
        .dark-mode .stat-label, .dark-mode .page-header p { color: #94a3b8; }

        .dark-mode .filter-group label { color: #cbd5e1; }
        .dark-mode .filter-group select, .dark-mode .filter-group input {
            background: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }

        .dark-mode .btn-export {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .dark-mode .btn-export:hover { background: #334155; }

        .dark-mode th {
            background: rgba(15, 23, 42, 0.5);
            color: #94a3b8;
            border-bottom-color: #334155;
        }

        .dark-mode td { border-bottom-color: #334155; }
        .dark-mode tr:hover td { background: rgba(255, 255, 255, 0.02); }

        .dark-mode .user-info strong { color: #f1f5f9; }
        .dark-mode .user-info span, .dark-mode .details-col, .dark-mode .time-label { color: #94a3b8; }
        .dark-mode .timestamp-col span { color: #f1f5f9; }

        .dark-mode .badge-default {
            background: #334155;
            color: #cbd5e1;
            border-color: #475569;
        }

        @media (max-width: 768px) {
            .filters-grid { grid-template-columns: 1fr; }
            .main-content { padding: 1.5rem; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>🛡️ Activity Logs</h1>
                    <p>Comprehensive audit trail for ShineGuard Hulo</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <form method="POST" style="margin: 0;" id="integrityForm">
                        <input type="hidden" name="verify_integrity" value="1">
                        <button type="button" class="btn-filter" style="background: #6366f1; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);" onclick="runIntegrityCheck()">
                            🛡️ Verify Log Integrity
                        </button>
                    </form>
                    <a href="activity_logs.php?export=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&action=<?php echo urlencode($action_filter); ?>&user_id=<?php echo $user_filter; ?>" class="btn-export" id="exportCsvBtn" onclick="handleCsvExport(event)">
                        <span>📥 Export to CSV</span>
                    </a>
                    <a href="activity_logs_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&action=<?php echo urlencode($action_filter); ?>&user_id=<?php echo $user_filter; ?>" class="btn-export" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-color: transparent;" onclick="handlePdfExport()">
                        <span>📄 Download PDF</span>
                    </a>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-card total">
                    <div class="stat-icon">📜</div>
                    <div class="stat-info">
                        <span class="stat-label">Total Events</span>
                        <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
                    </div>
                </div>
                <div class="stat-card security">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-label">Security Alerts</span>
                        <span class="stat-value"><?php echo number_format($stats['security']); ?></span>
                    </div>
                </div>
                <div class="stat-card users">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-label">Active Users</span>
                        <span class="stat-value"><?php echo number_format($stats['users']); ?></span>
                    </div>
                </div>
            </div>

            <div class="glass-panel">
                <form method="GET" class="filters-grid">
                    <div class="filter-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>User</label>
                        <select name="user_id">
                            <option value="">All Users</option>
                            <?php while($u = $users_res->fetch_assoc()): ?>
                                <option value="<?php echo $u['user_id']; ?>" <?php echo $user_filter == $u['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Action Type</label>
                        <select name="action">
                            <option value="">All Actions</option>
                            <?php foreach($actions as $act): ?>
                                <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($act); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-filter">🔍 Apply Filters</button>
                    </div>
                </form>
            </div>

            <div class="glass-panel table-panel">
                <div class="log-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User Activity</th>
                                <th>Action</th>
                                <th>Security Integrity</th>
                                <th>Observations</th>
                                <th>Technical ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs && $logs->num_rows > 0): ?>
                                <?php while($log = $logs->fetch_assoc()): ?>
                                    <?php
                                        $badge_class = 'badge-default';
                                        $icon = '📌';
                                        if (stripos($log['action'], 'Security') !== false) { $badge_class = 'badge-security'; $icon = '🛡️'; }
                                        if (stripos($log['action'], 'Control') !== false || stripos($log['action'], 'Manual Sync') !== false) { $badge_class = 'badge-control'; $icon = '💡'; }
                                        if (stripos($log['action'], 'Access') !== false || stripos($log['action'], 'Login') !== false) { $badge_class = 'badge-auth'; $icon = '🔐'; }
                                        
                                        $initials = '';
                                        if ($log['full_name']) {
                                            $parts = explode(' ', $log['full_name']);
                                            $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                                        } else {
                                            $initials = 'SYS';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="timestamp-col">
                                                <span><?php echo date('M d, Y', strtotime($log['created_at'])); ?></span>
                                                <span class="time-label"><?php echo date('h:i:s A', strtotime($log['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-pill">
                                                <div class="user-avatar"><?php echo $initials; ?></div>
                                                <div class="user-info">
                                                    <strong><?php echo htmlspecialchars(maskPII($log['full_name'] ?: 'System Interface')); ?></strong>
                                                    <span><?php echo htmlspecialchars($log['role'] ?: 'Automated'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="action-badge <?php echo $badge_class; ?>">
                                                <span style="font-size: 14px;"><?php echo $icon; ?></span>
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($integrity_results[$log['log_id']])): ?>
                                                <?php if ($integrity_results[$log['log_id']]): ?>
                                                    <span class="badge-control" style="padding: 4px 10px; border-radius: 8px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px;">
                                                        🛡️ Verified
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-security" style="padding: 4px 10px; border-radius: 8px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; animation: pulse 2s infinite;">
                                                        🚨 TAMPERED
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-style: italic; font-size: 12px;">Not Validated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="details-col">
                                            <?php
                                                $details = \ShineGuard\Services\SecurityService::decrypt($log['details']);
                                                $device_token = null;
                                                
                                                // Extract [DEVICE:token] safely
                                                if (preg_match('/\[DEVICE:([a-f0-9]+)\]/', $details, $matches)) {
                                                    $device_token = $matches[1];
                                                    // Strip it from the visible log text
                                                    $details = str_replace($matches[0], '', $details);
                                                }
                                                
                                                if (shouldMaskPII()) {
                                                    // Simple heuristic to mask emails or phones embedded in logs
                                                    $details = preg_replace_callback('/[a-zA-Z0-9._%+-]+@hulo\.gov\.ph/', function($m) { return maskEmail($m[0]); }, $details);
                                                }
                                                
                                                echo htmlspecialchars(trim($details)); 
                                                
                                                // If a device token was flagged, render the Revoke button right next to the log text
                                                // ENFORCEMENT: Button only appears if the user is on their Base Device
                                                if ($is_base_device && $device_token && $log['action'] === 'Security Alert') {
                                                    echo '<button type="button" onclick="initBlockDevice(\'' . htmlspecialchars($device_token) . '\')" style="display:inline-block; margin-left: 10px; background: #ef4444; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: bold; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);">🛑 Block Device</button>';
                                                }
                                            ?>
                                        </td>
                                        <td style="font-family: 'JetBrains Mono', monospace; color: #94a3b8; font-size: 12px;">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 60px; color: #94a3b8;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
                                        <div style="font-weight: 700; font-size: 1.1rem;">No matching activities found</div>
                                        <p style="margin-top: 8px;">Try adjusting your search filters or date range.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination UI -->
                <?php if (isset($total_pages) && $total_pages > 1 && (!isset($_GET['export']) || $_GET['export'] !== 'csv')): ?>
                <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; background: white; padding: 16px 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <div style="font-size: 14px; color: #64748b; font-weight: 500;">
                        Showing page <strong style="color: #0f172a;"><?php echo $page; ?></strong> of <strong style="color: #0f172a;"><?php echo $total_pages; ?></strong>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <?php 
                            $base_url = "activity_logs.php?start_date=$start_date&end_date=$end_date&action=" . urlencode($action_filter) . "&user_id=$user_filter";
                            
                            if ($page > 1) {
                                echo '<a href="' . $base_url . '&page=' . ($page - 1) . '" style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; color: #0f172a; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">Previous</a>';
                            } else {
                                echo '<span style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #cbd5e1; font-size: 14px; font-weight: 600; cursor: not-allowed;">Previous</span>';
                            }

                            if ($page < $total_pages) {
                                echo '<a href="' . $base_url . '&page=' . ($page + 1) . '" style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; color: #0f172a; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">Next</a>';
                            } else {
                                echo '<span style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #cbd5e1; font-size: 14px; font-weight: 600; cursor: not-allowed;">Next</span>';
                            }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

<!-- Integrity Confirm Modal -->
<div id="integrityConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); backdrop-filter:blur(10px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:20px; padding:2rem 2.5rem; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Inter',sans-serif; text-align:center; animation:slideInRight 0.35s cubic-bezier(0.34,1.56,0.64,1);">
        <div style="font-size:40px; margin-bottom:1rem;">🛡️</div>
        <div style="font-size:1.1rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Run Integrity Audit?</div>
        <p style="font-size:13px; color:#64748b; margin:0 0 1.5rem; line-height:1.6;">This will perform a mathematical forensic verification of all visible log entries.</p>
        <div style="display:flex; gap:0.75rem;">
            <button onclick="cancelIntegrity()" style="flex:1; padding:12px; border-radius:12px; border:2px solid #e2e8f0; background:white; font-weight:700; font-size:14px; cursor:pointer; color:#64748b; transition:all 0.2s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='white'">Cancel</button>
            <button onclick="confirmIntegrity()" style="flex:1; padding:12px; border-radius:12px; border:none; background:#6366f1; color:white; font-weight:700; font-size:14px; cursor:pointer; box-shadow:0 4px 12px rgba(99,102,241,0.3); transition:all 0.2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform='none'">🛡️ Yes, Verify</button>
        </div>
    </div>
</div>

<!-- Device Revocation MFA Modal -->
<div id="deviceBlockMfaModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.8); backdrop-filter:blur(10px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:20px; padding:2rem 2.5rem; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.5); font-family:'Inter',sans-serif; text-align:center; animation:slideInRight 0.35s cubic-bezier(0.34,1.56,0.64,1);">
        <div style="font-size:40px; margin-bottom:1rem; color:#ef4444;">🚨</div>
        <div style="font-size:1.1rem; font-weight:800; color:#ef4444; margin-bottom:0.5rem;">Revoke Device Access</div>
        <p style="font-size:13px; color:#64748b; margin:0 0 1.5rem; line-height:1.6;">This action will permanently block the targeted device. To execute the kill switch, please verify your identity using your Authenticator App.</p>
        
        <form id="blockDeviceForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="block_device_token" id="mfaBlockDeviceToken" value="">
            
            <input type="text" name="block_mfa_code" placeholder="6-digit MFA Code" required pattern="[0-9]{6}" autocomplete="off" style="width: 100%; box-sizing: border-box; text-align: center; font-size: 24px; letter-spacing: 4px; padding: 12px; border: 2px solid #e2e8f0; border-radius: 12px; margin-bottom: 20px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#e2e8f0'">

            <div style="display:flex; gap:0.75rem;">
                <button type="button" onclick="cancelBlockDevice()" style="flex:1; padding:12px; border-radius:12px; border:2px solid #e2e8f0; background:white; font-weight:700; font-size:14px; cursor:pointer; color:#64748b; transition:all 0.2s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='white'">Cancel</button>
                <button type="submit" style="flex:1; padding:12px; border-radius:12px; border:none; background:#ef4444; color:white; font-weight:700; font-size:14px; cursor:pointer; box-shadow:0 4px 12px rgba(239,68,68,0.3); transition:all 0.2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform='none'">🛑 Execute Block</button>
            </div>
        </form>
    </div>
</div>

<script>
    // MFA Block Device Handling
    function initBlockDevice(token) {
        document.getElementById('mfaBlockDeviceToken').value = token;
        document.getElementById('deviceBlockMfaModal').style.display = 'flex';
    }

    function cancelBlockDevice() {
        document.getElementById('deviceBlockMfaModal').style.display = 'none';
        document.getElementById('mfaBlockDeviceToken').value = '';
    }
    // CSV Export: show toast then proceed
    function handleCsvExport(e) {
        if(e) e.preventDefault();
        window.sgToast('📥', 'Exporting CSV', 'Preparing audit log download...', '#3b82f6', '#eff6ff');
        const href = e.currentTarget ? e.currentTarget.href : document.getElementById('exportCsvBtn').href;
        setTimeout(() => {
            window.location.href = href;
        }, 300);
    }

    // PDF Export: show password toast
    function handlePdfExport() {
        setTimeout(() => {
            window.sgToast('🔐', 'PDF Downloaded — Protected', 'Use your ShineGuard login password to open the file.', '#3b82f6', '#eff6ff');
        }, 500);
    }

    // Integrity Check: show inline confirm then submit form
    function runIntegrityCheck() {
        document.getElementById('integrityConfirmModal').style.display = 'flex';
    }

    function confirmIntegrity() {
        document.getElementById('integrityConfirmModal').style.display = 'none';
        window.sgToast('🔍', 'Verifying Integrity', 'Running cryptographic audit on log chain...', '#6366f1', '#eef2ff');
        setTimeout(() => document.getElementById('integrityForm').submit(), 800);
    }

    function cancelIntegrity() {
        document.getElementById('integrityConfirmModal').style.display = 'none';
    }

    // URL Parameter Toasts
    <?php if (isset($_GET['success']) && $_GET['success'] === 'device_blocked'): ?>
    window.addEventListener('DOMContentLoaded', () => {
        window.sgToast('✅', 'Device Revoked', 'Target device has been permanently blocked from accessing ShineGuard.', '#10b981', '#ecfdf5');
    });
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalid_mfa'): ?>
    window.addEventListener('DOMContentLoaded', () => {
        window.sgToast('❌', 'MFA Failed', 'Invalid authenticator code. Procedure rejected.', '#ef4444', 'rgba(239,68,68,0.1)');
    });
    <?php endif; ?>

    // Show result toast on page load if integrity check just ran
    <?php if (!empty($integrity_results)): ?>
    <?php
        $total = count($integrity_results);
        $tampered = count(array_filter($integrity_results, fn($v) => !$v));
    ?>
    window.addEventListener('DOMContentLoaded', () => {
        <?php if ($tampered > 0): ?>
        window.sgToast('🚨', 'Integrity Alert', '<?php echo $tampered; ?> tampered record(s) detected out of <?php echo $total; ?> entries.', '#ef4444', 'rgba(239,68,68,0.1)');
        <?php else: ?>
        window.sgToast('✅', 'Integrity Confirmed', 'All <?php echo $total; ?> log entries verified — chain intact.', '#10b981', '#ecfdf5');
        <?php endif; ?>
    });
    <?php endif; ?>
</script>
</body>
</html>
