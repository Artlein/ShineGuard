<?php
require_once 'dbconnect.php';
requireLogin(['System Admin']);

// ── SECURITY: SOC Access Authorization Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'soc_auth') {
    checkCsrf();
    $password = $_POST['admin_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && password_verify($password, $res['password_hash'])) {
        $_SESSION['soc_authorized'] = true;
        logActivity($conn, $user_id, 'SOC Authorized', 'Administrator verified password for Security Operations Center access.');
        echo json_encode(['success' => true]);
    } else {
        logActivity($conn, $user_id, 'SOC Access Denied', 'Failed password verification for SOC access.');
        echo json_encode(['success' => false, 'error' => 'Invalid administrator password. Access denied.']);
    }
    exit();
}

// Access Gate
if (!isset($_SESSION['soc_authorized']) || !$_SESSION['soc_authorized']) {
    include 'includes/secure_auth_ui.php';
    exit();
}

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
    } elseif ($_POST['action'] === 'hardware_acknowledge' || $_POST['action'] === 'hardware_revoke') {
        $token = $_POST['device_token'] ?? '';
        $mfa_code = trim($_POST['mfa_code'] ?? '');
        $admin_id = $_SESSION['user_id'];

        // Mandatory MFA Verification for hardware trust/block
        require_once 'src/Services/TOTPService.php';
        $mfa_check = $conn->prepare("SELECT mfa_secret FROM users WHERE user_id = ? AND mfa_enabled = 1");
        $mfa_check->bind_param("i", $admin_id);
        $mfa_check->execute();
        $mfa_res = $mfa_check->get_result()->fetch_assoc();
        $mfa_check->close();

        if (!$mfa_res || !\ShineGuard\Services\TOTPService::verifyCode($mfa_res['mfa_secret'], $mfa_code)) {
            logActivity($conn, $admin_id, 'Security Alert', "Failed MFA verification for hardware operation on device: " . substr($token, 0, 8) . "...");
            header("Location: security_recovery.php?error=invalid_mfa");
            exit();
        }

        if ($_POST['action'] === 'hardware_acknowledge') {
            $stmt = $conn->prepare("UPDATE user_devices SET is_acknowledged = 1 WHERE device_token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            logActivity($conn, $admin_id, 'Hardware Trusted', "Administrator passed MFA and trusted device: $token");
        } else {
            $stmt = $conn->prepare("UPDATE user_devices SET is_blocked = 1, is_acknowledged = 1 WHERE device_token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            logActivity($conn, $admin_id, 'Hardware Blocked', "Administrator passed MFA and revoked device: $token");
        }
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
                
                <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_mfa'): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 16px 24px; border-radius: 16px; border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    🛑 MFA Verification Failed: Invalid or expired security code.
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 16px 24px; border-radius: 16px; border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    ✅ Operation Successful: Security protocol authorized and finalized.
                </div>
                <?php endif; ?>

                <div class="page-header" style="margin-top: 1rem;">
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
                                            <div style="display: inline-flex; gap: 8px;">
                                                <button type="button" 
                                                        onclick="openMfaModal('hardware_acknowledge', '<?php echo $dev['device_token']; ?>', 'Trust Device', 'Authorize persistent trust for this hardware footprint?')"
                                                        class="action-btn btn-fulfill" style="padding: 8px 14px; font-size: 12px;">
                                                    🛡️ Trust
                                                </button>
                                                <button type="button" 
                                                        onclick="openMfaModal('hardware_revoke', '<?php echo $dev['device_token']; ?>', 'Block Device', 'Permanently revoke and blockade this device?')"
                                                        class="action-btn" style="background: #ef4444; color: white; padding: 8px 14px; font-size: 12px;">
                                                    🛑 Block
                                                </button>
                                            </div>
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

    <!-- MFA AUTHORIZATION MODAL -->
    <div id="mfaModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.8); backdrop-filter: blur(12px); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
        <div class="mfa-modal-card" style="background: white; border-radius: 24px; padding: 40px; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); text-align: center; transform: scale(0.9); opacity: 0; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div style="background: #eff6ff; width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 32px;">📱</div>
            <h2 id="mfaTitle" style="font-weight: 800; color: #0f172a; margin-bottom: 8px;">MFA Authorization</h2>
            <p id="mfaDesc" style="color: #64748b; font-size: 14px; margin-bottom: 32px;">Please enter your 6-digit Google Authenticator code to authorize this security protocol.</p>
            
            <form id="mfaFinalForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" id="mfaAction">
                <input type="hidden" name="device_token" id="mfaToken">
                
                <input type="text" name="mfa_code" id="mfaInput" 
                       maxlength="6" placeholder="000000" 
                       style="width: 100%; padding: 16px; border-radius: 16px; border: 2px solid #e2e8f0; font-size: 24px; font-weight: 800; text-align: center; letter-spacing: 0.5em; margin-bottom: 24px; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button type="button" onclick="closeMfaModal()" style="padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 14px; border-radius: 12px; border: none; background: #3b82f6; color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">Confirm Action</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMfaModal(action, token, title, desc) {
            const modal = document.getElementById('mfaModal');
            const card = document.querySelector('.mfa-modal-card');
            
            document.getElementById('mfaTitle').textContent = title;
            document.getElementById('mfaDesc').textContent = desc;
            document.getElementById('mfaAction').value = action;
            document.getElementById('mfaToken').value = token;
            
            modal.style.display = 'flex';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
                card.style.opacity = '1';
                document.getElementById('mfaInput').focus();
            }, 10);
        }

        function closeMfaModal() {
            const modal = document.getElementById('mfaModal');
            const card = document.querySelector('.mfa-modal-card');
            card.style.transform = 'scale(0.9)';
            card.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('mfaInput').value = '';
            }, 300);
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMfaModal();
        });
    </script>
</body>
</html>
