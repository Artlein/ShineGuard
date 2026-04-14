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
    <title>Security Operations — Shine Guard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .recovery-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
        }
        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pending { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        
        .action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-fulfill { background: #3b82f6; color: white; }
        .btn-fulfill:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-dismiss { background: #f1f5f9; color: #64748b; }
        .btn-dismiss:hover { background: #e2e8f0; color: #0f172a; }
        
        .retrieval-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 13px;
            color: #475569;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper" style="padding: 40px;">
            <div class="hdr-left" style="margin-bottom: 32px;">
                <h1 style="font-size: 28px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em;">Security Operations Center</h1>
                <p style="color: #64748b; font-weight: 500;">Manage administrative password recovery and perishable links</p>
            </div>

            <div class="recovery-card">
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

            <div class="recovery-card" style="border-color: rgba(148, 163, 184, 0.2); opacity: 0.8;">
                <h4 style="font-weight: 700; color: #475569; margin-bottom: 12px; font-size: 14px;">Corporate Protocol Reminder</h4>
                <p style="font-size: 13px; color: #64748b; line-height: 1.6;">
                    For compliance with ShineGuard Security standards, recovery links should only be provided after verifying the user's identity via official corporate channels. 
                    Links are <strong>perishable</strong> and will expire automatically after 1 hour.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
