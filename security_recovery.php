<?php
require_once 'dbconnect.php';
requireLogin(['System Admin']);

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCsrf();
    $email = $_POST['email'] ?? '';
    
    if ($_POST['action'] === 'fulfill') {
        $stmt = $conn->prepare("UPDATE password_resets SET status = 'Fulfilled' WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        logActivity($conn, $_SESSION['user_id'], 'Recovery Fulfilled', "Administrator marked recovery for $email as fulfilled.");
    } elseif ($_POST['action'] === 'dismiss') {
        $stmt = $conn->prepare("UPDATE password_resets SET status = 'Dismissed' WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        logActivity($conn, $_SESSION['user_id'], 'Recovery Dismissed', "Administrator dismissed recovery request for $email.");
    } elseif ($_POST['action'] === 'hardware_acknowledge') {
        $token = $_POST['device_token'] ?? '';
        $stmt = $conn->prepare("UPDATE user_devices SET is_acknowledged = 1 WHERE device_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        logActivity($conn, $_SESSION['user_id'], 'Hardware Trusted', "Administrator acknowledged and trusted device: $token");
    } elseif ($_POST['action'] === 'hardware_revoke') {
        $token = $_POST['device_token'] ?? '';
        $stmt = $conn->prepare("UPDATE user_devices SET is_blocked = 1, is_acknowledged = 1 WHERE device_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        logActivity($conn, $_SESSION['user_id'], 'Hardware Blocked', "Administrator revoked access and blocked device: $token");
    }
    
    header('Location: security_recovery.php?success=status_updated');
    exit();
}

$theme_color = '#3b82f6'; // Security Blue

// Fetch Pending Requests
$pending_query = "SELECT * FROM password_resets WHERE status = 'Pending' ORDER BY created_at DESC";
$pending_res = $conn->query($pending_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Operations — Shine Guard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .recovery-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.06);
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .status-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pending { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        
        .action-btn {
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .btn-fulfill { background: #3b82f6; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25); }
        .btn-fulfill:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35); }
        .btn-dismiss { background: #f1f5f9; color: #64748b; }
        .btn-dismiss:hover { background: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body class="dashboard-body">
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="content-wrapper">
                <div class="page-header" style="margin-top: 2rem;">
                    <div class="hdr-left">
                        <h1>Security Operations Center</h1>
                        <p>Manage administrative password recovery and perishable links</p>
                    </div>
                    </div>
                </div>

                <div class="glass-panel">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 style="font-weight: 700; color: #334155;">Active Recovery Requests</h3>
                        <span class="status-pill status-pending"><?php echo $pending_res->num_rows; ?> Pending</span>
                    </div>

                    <?php if ($pending_res && $pending_res->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                    <th style="padding: 16px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">User Account</th>
                                    <th style="padding: 16px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">Requested At</th>
                                    <th style="padding: 16px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">Retrieval Source</th>
                                    <th style="padding: 16px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $pending_res->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 16px;">
                                            <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($row['email']); ?></div>
                                            <div style="font-size: 12px; color: #94a3b8;">Corporate ID Verification Required</div>
                                        </td>
                                        <td style="padding: 16px; color: #475569; font-size: 14px;">
                                            <?php echo date('M d, Y • H:i', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td style="padding: 16px;">
                                            <a href="activity_logs.php?action=Password+Reset+Request" class="action-btn" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2);">
                                                🔍 View Link in Audit Logs
                                            </a>
                                        </td>
                                        <td style="padding: 16px; text-align: right;">
                                            <form method="POST" style="display: inline-flex; gap: 8px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                <button type="submit" name="action" value="fulfill" class="action-btn btn-fulfill">
                                                    ✅ Mark as Fulfilled
                                                </button>
                                                <button type="submit" name="action" value="dismiss" class="action-btn btn-dismiss" title="Dismiss Request">
                                                    ✕
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🛡️</div>
                        <h4 style="font-weight: 700; color: #1e293b; margin-bottom: 8px;">No Pending Requests</h4>
                        <p style="color: #64748b; font-size: 14px;">All security recovery tasks have been fulfilled or dismissed.</p>
                    </div>
                <?php endif; ?>
            </div>

                <div class="glass-panel" style="margin-top: 40px;">
                    <?php
                    $unack_res = $conn->query("SELECT ud.*, u.username, u.full_name FROM user_devices ud JOIN users u ON ud.user_id = u.user_id WHERE ud.is_acknowledged = 0 ORDER BY ud.created_at DESC");
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: rgba(59, 130, 246, 0.1); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🛡️</div>
                            <h3 style="font-weight: 700; color: #334155; margin: 0;">Unrecognized Hardware Access</h3>
                        </div>
                        <span class="status-pill <?php echo $unack_res->num_rows > 0 ? 'status-pending' : ''; ?>"><?php echo $unack_res->num_rows; ?> New Device(s)</span>
                    </div>

                    <?php if ($unack_res && $unack_res->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                    <th style="padding: 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Authenticated User</th>
                                    <th style="padding: 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Technical Fingerprint</th>
                                    <th style="padding: 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Detected At</th>
                                    <th style="padding: 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: right;">Authorization</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($dev = $unack_res->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 16px;">
                                            <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($dev['full_name']); ?></div>
                                            <div style="font-size: 11px; color: #64748b;">@<?php echo htmlspecialchars($dev['username']); ?></div>
                                        </td>
                                        <td style="padding: 16px;">
                                            <div style="font-family: 'Inter', monospace; font-size: 12px; color: #334155;"><?php echo htmlspecialchars($dev['last_ip']); ?></div>
                                            <div style="font-size: 10px; color: #94a3b8; width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($dev['browser_agent']); ?>">
                                                <?php echo htmlspecialchars($dev['browser_agent']); ?>
                                            </div>
                                        </td>
                                        <td style="padding: 16px; color: #475569; font-size: 13px;">
                                            <?php echo date('M d • H:i', strtotime($dev['created_at'])); ?>
                                        </td>
                                        <td style="padding: 16px; text-align: right;">
                                            <form method="POST" style="display: inline-flex; gap: 8px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="device_token" value="<?php echo htmlspecialchars($dev['device_token']); ?>">
                                                <button type="submit" name="action" value="hardware_acknowledge" class="action-btn btn-fulfill" style="padding: 8px 14px; font-size: 12px;">
                                                    🛡️ Trust
                                                </button>
                                                <button type="submit" name="action" value="hardware_revoke" class="action-btn" style="background: #ef4444; color: white; padding: 8px 14px; font-size: 12px;">
                                                    🛑 Block
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="color: #cbd5e1; font-size: 32px; margin-bottom: 12px;">✅</div>
                        <p style="color: #94a3b8; font-size: 13px;">All currently active device fingerprints are acknowledged and trusted.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="glass-panel" style="opacity: 0.8; margin-top: 40px;">
                    <h4 style="font-weight: 700; color: #475569; margin-bottom: 12px; font-size: 14px;">Corporate Protocol Reminder</h4>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.6;">
                        For compliance with ShineGuard Security standards, recovery links should only be provided after verifying the user's identity via official corporate channels. 
                        Links are <strong>perishable</strong> and will expire automatically after 1 hour.
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
