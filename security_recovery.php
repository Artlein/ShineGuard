<?php
/**
 * 🛡️ SECURITY RECOVERY & HARDWARE AUTHORIZATION
 * Central forensic hub for managing password resets and unknown hardware authorizations.
 */
require_once 'dbconnect.php';
requireLogin(['System Admin']);

$isAdmin = (getUserRole() === 'System Admin');
if (!$isAdmin) {
    include 'includes/access_denied_ui.php';
    exit();
}

// ── SECURE SESSION AUTHORIZATION (SBA) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'soc_auth') {
    checkCsrf();
    ob_clean();
    header('Content-Type: application/json');
    $admin_password = $_POST['admin_password'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if ($user_data && password_verify($admin_password, $user_data['password_hash'])) {
        setRecentlyAuthorized();
        logActivity($conn, $user_id, 'Security Clearance', 'User authorized secure session for SOC recovery');
        echo json_encode(['success' => true]);
    } else {
        logActivity($conn, $user_id, 'Security Violation', 'Failed SOC authorization attempt');
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }
    exit();
}

// ── ACTION PROCESSING (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isRecentlyAuthorized()) {
    checkCsrf();
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // 1. Manage Password Resets
        if ($action === 'dismiss_reset') {
            $email = $_POST['email'];
            $stmt = $conn->prepare("UPDATE password_resets SET status = 'Dismissed', admin_notes = 'Dismissed by admin' WHERE email = ? AND status = 'Pending'");
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Recovery Control', "Dismissed password reset for $email");
            }
        }
        
        // 2. Manage Unknown Devices
        if ($action === 'acknowledge_device') {
            $token = $_POST['device_token'];
            $stmt = $conn->prepare("UPDATE user_devices SET is_acknowledged = 1 WHERE device_token = ?");
            $stmt->bind_param("s", $token);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Hardware Authorization', "Authorized unknown device: " . substr($token, 0, 8) . "...");
            }
        }

        if ($action === 'block_device') {
            $token = $_POST['device_token'];
            $stmt = $conn->prepare("UPDATE user_devices SET is_blocked = 1, is_acknowledged = 1 WHERE device_token = ?");
            $stmt->bind_param("s", $token);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Hardware Blocking', "Blocked suspicious device: " . substr($token, 0, 8) . "...");
            }
        }
        
        header('Location: security_recovery.php?success=1');
        exit();
    }
}

$page_title = 'Security Recovery Hub';
$current_page = 'security_recovery.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// If not authorized for the session, show the shield
if (!isRecentlyAuthorized()) {
    include 'includes/secure_auth_ui.php';
    exit();
}

// Fetch Pending Resets
$pending_resets = $conn->query("SELECT pr.*, u.full_name FROM password_resets pr 
                               LEFT JOIN users u ON pr.email = u.email 
                               WHERE pr.status = 'Pending' ORDER BY pr.created_at DESC");

// Fetch Unacknowledged Devices
$unknown_devices = $conn->query("SELECT ud.*, u.full_name FROM user_devices ud 
                                 JOIN users u ON ud.user_id = u.user_id 
                                 WHERE ud.is_acknowledged = 0 ORDER BY ud.last_seen_at DESC");
?>

<main class="main-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
            <h1 style="margin:0; font-weight:900; letter-spacing:-0.03em; color:var(--text); font-size:1.8rem; display:flex; align-items:center; gap:12px;">
                <span style="background:rgba(239,68,68,0.1); color:#ef4444; width:42px; height:42px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; font-size:22px;">🛡️</span>
                Security Recovery Hub
            </h1>
            <p style="color:var(--dim); margin:4px 0 0; font-size:14px;">Central forensic control for secure machine authorizations and credentials management.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="activity_logs.php" class="btn-action ghost">Forensic Logs</a>
            <button onclick="location.reload()" class="btn-action primary">Refresh Buffer</button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); color:#10b981; padding:12px 16px; border-radius:12px; margin-bottom:24px; font-weight:600; font-size:14px; display:flex; align-items:center; gap:10px;">
        🟢 Changes propagated to security protocols successfully.
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 1.2fr; gap:24px;">
        
        <!-- ── Pending Password Resets ── -->
        <section class="card" style="padding:0; overflow:hidden;">
            <div style="padding:20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h2 style="margin:0; font-size:15px; font-weight:800; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:18px;">🔑</span> Pending Resets
                </h2>
                <span class="badge" style="background:rgba(59,130,246,0.1); color:#3b82f6;"><?php echo $pending_resets->num_rows; ?> Requests</span>
            </div>
            
            <div style="padding:0;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="background:rgba(15,23,42,0.02); border-bottom:1px solid var(--border);">
                        <tr>
                            <th style="padding:12px 20px; text-align:left; color:var(--muted); font-weight:700;">Account</th>
                            <th style="padding:12px 20px; text-align:left; color:var(--muted); font-weight:700;">Requested</th>
                            <th style="padding:12px 20px; text-align:right; color:var(--muted); font-weight:700;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_resets->num_rows === 0): ?>
                        <tr><td colspan="3" style="padding:40px; text-align:center; color:var(--dim);">No pending credential resets.</td></tr>
                        <?php endif; ?>
                        <?php while($pr = $pending_resets->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:16px 20px;">
                                <div style="font-weight:700; color:var(--text);"><?php echo htmlspecialchars($pr['full_name'] ?? 'Guest'); ?></div>
                                <div style="color:var(--dim); font-size:12px; margin-top:2px;"><?php echo maskEmail($pr['email']); ?></div>
                            </td>
                            <td style="padding:16px 20px; color:var(--dim);">
                                <?php echo date('M d, H:i', strtotime($pr['created_at'])); ?>
                            </td>
                            <td style="padding:16px 20px; text-align:right;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="dismiss_reset">
                                    <input type="hidden" name="email" value="<?php echo $pr['email']; ?>">
                                    <button type="submit" class="btn-action ghost" style="padding:4px 10px; font-size:11px; border-color:#e2e8f0;">Dismiss</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Unknown Hardware Devices ── -->
        <section class="card" style="padding:0; overflow:hidden;">
            <div style="padding:20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h2 style="margin:0; font-size:15px; font-weight:800; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:18px;">💻</span> Hardware Authorizations
                </h2>
                <span class="badge fail"><?php echo $unknown_devices->num_rows; ?> Unknown</span>
            </div>
            
            <div style="padding:0;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="background:rgba(15,23,42,0.02); border-bottom:1px solid var(--border);">
                        <tr>
                            <th style="padding:12px 20px; text-align:left; color:var(--muted); font-weight:700;">Identity & Machine</th>
                            <th style="padding:12px 20px; text-align:left; color:var(--muted); font-weight:700;">Detection Info</th>
                            <th style="padding:12px 20px; text-align:right; color:var(--muted); font-weight:700;">Protocol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($unknown_devices->num_rows === 0): ?>
                        <tr><td colspan="3" style="padding:40px; text-align:center; color:var(--dim);">All hardware machines are authorized.</td></tr>
                        <?php endif; ?>
                        <?php while($ud = $unknown_devices->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:16px 20px;">
                                <div style="font-weight:700; color:var(--text);"><?php echo htmlspecialchars($ud['full_name']); ?></div>
                                <div style="color:var(--dim); font-size:11px; margin-top:2px; font-family:var(--mono);"><?php echo $ud['last_ip']; ?></div>
                            </td>
                            <td style="padding:16px 20px; color:var(--dim); font-size:12px; max-width:200px;">
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($ud['browser_agent']); ?>">
                                    <?php echo htmlspecialchars($ud['browser_agent']); ?>
                                </div>
                                <div style="margin-top:2px; font-size:11px;">Detected: <?php echo date('M d, H:i', strtotime($ud['created_at'])); ?></div>
                            </td>
                            <td style="padding:16px 20px; text-align:right; white-space:nowrap;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="acknowledge_device">
                                    <input type="hidden" name="device_token" value="<?php echo $ud['device_token']; ?>">
                                    <button type="submit" class="btn-action primary" style="padding:4px 10px; font-size:11px; background:#10b981; border:none;">Authorize</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="block_device">
                                    <input type="hidden" name="device_token" value="<?php echo $ud['device_token']; ?>">
                                    <button type="submit" class="btn-action fail" style="padding:4px 10px; font-size:11px; color:#ef4444; border-color:#fca5a5; background:none;">Block</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<style>
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 4px 12px var(--shadow); position: relative; transition: transform 0.2s; }
    .badge { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
    .badge.fail { background: rgba(239,68,68,0.1); color: #ef4444; }
    .btn-action { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s; text-decoration: none; border: 1px solid transparent; }
    .btn-action.primary { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3); }
    .btn-action.ghost { background: transparent; border-color: var(--border); color: var(--dim); }
</style>

<?php include 'includes/footer.php'; ?>
