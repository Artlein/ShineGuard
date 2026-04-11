<?php
require_once 'dbconnect.php';
requireLogin(['System Admin']);

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

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user_id'] ?? '';

// Build Query
$where = ["al.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'"];
if ($action_filter) $where[] = "al.action = " . $conn->real_escape_string("'$action_filter'");
if ($user_filter) $where[] = "al.user_id = " . intval($user_filter);

$where_clause = implode(' AND ', $where);

// Base Query
$query = "SELECT al.*, u.username, u.full_name, u.role 
          FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.user_id 
          WHERE $where_clause 
          ORDER BY al.created_at DESC";

$logs = $conn->query($query);

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
            $row['full_name'] ?: ($row['username'] ?: 'System Interface'),
            $row['role'] ?: 'Automated',
            $row['action'],
            $row['details'],
            $row['ip_address']
        ]);
    }
    fclose($output);
    exit();
}

// Stats for the current period
$stats_res = $conn->query("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN action LIKE '%Security%' THEN 1 END) as security,
    COUNT(DISTINCT user_id) as users
    FROM activity_logs 
    WHERE $where_clause");
$stats = $stats_res ? $stats_res->fetch_assoc() : ['total' => 0, 'security' => 0, 'users' => 0];

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

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
                <a href="activity_logs.php?export=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&action=<?php echo urlencode($action_filter); ?>&user_id=<?php echo $user_filter; ?>" class="btn-export">
                    <span>📥 Export to CSV</span>
                </a>
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
                                                    <strong><?php echo htmlspecialchars($log['full_name'] ?: 'System Interface'); ?></strong>
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
                                        <td class="details-col">
                                            <?php echo htmlspecialchars($log['details']); ?>
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
            </div>
        </main>
    </div>
</body>
</html>
