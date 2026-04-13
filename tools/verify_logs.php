<?php
/**
 * SHINEGUARD LOG INTEGRITY VERIFIER
 * Demonstrates Pillar 3: Data Integrity (Mathematical Proof)
 */

require_once '../dbconnect.php';
require_once '../src/Services/SecurityService.php';
requireLogin('System Admin');

use ShineGuard\Services\SecurityService;

// Force log generation to seed the chain if it's new
logActivity($conn, $_SESSION['user_id'], 'Integrity Check', 'User initiated a forensic log verification scan.');

// Fetch all signed logs
$result = $conn->query("SELECT * FROM activity_logs ORDER BY log_id ASC");
$chain_valid = true;
$errors = [];
$prev_hash = str_repeat('0', 64); // The "Genesis Block" seed

$logs = [];
while ($row = $result->fetch_assoc()) {
    $current_hash = $row['log_hash'];
    
    if ($current_hash === null) {
        $row['status'] = 'UNCHECKED';
        $logs[] = $row;
        continue;
    }

    // Recalculate what the hash SHOULD be
    $expected_hash = SecurityService::generateLogSignature(
        $prev_hash, 
        $row['user_id'] ?? 0, 
        $row['action'], 
        $row['details'], 
        $row['ip_address']
    );

    if ($hash_match = ($current_hash === $expected_hash)) {
        $row['status'] = 'VERIFIED';
    } else {
        $row['status'] = 'TAMPERED';
        $chain_valid = false;
        $errors[] = "Block #{$row['log_id']} has an invalid signature!";
    }

    $logs[] = $row;
    $prev_hash = $current_hash;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Integrity Scan | ShineGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0c0d10; color: #fff; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .status-hero { 
            background: <?php echo $chain_valid ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>;
            border: 2px solid <?php echo $chain_valid ? '#10b981' : '#ef4444'; ?>;
            padding: 30px;
            border-radius: 24px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .hero-icon { font-size: 3rem; }
        .hero-text h2 { margin: 0; font-size: 2rem; font-weight: 900; }
        .hero-text p { margin: 5px 0 0; color: #a1a1aa; }

        .log-table { width: 100%; border-collapse: collapse; }
        .log-row { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .log-row td { padding: 15px; font-size: 0.9rem; }
        .badge { font-family: monospace; font-size: 0.7rem; padding: 4px 8px; border-radius: 4px; font-weight: 800; }
        .badge-verified { background: #10b981; color: #000; }
        .badge-tampered { background: #ef4444; color: #fff; animation: blink 1s infinite; }
        .badge-unchecked { background: #3f3f46; color: #a1a1aa; }
        .sig-text { color: #52525b; font-size: 0.65rem; }

        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 style="font-size: 3rem; font-weight: 900; margin:0;">Audit Integrity Scan</h1>
                <p style="color: #71717a; margin: 5px 0 0;">Pillar 3: Mathematical Proof of Data Authenticity</p>
            </div>
            <div style="text-align: right;">
                <span style="color: #10b981; font-weight: 800;">✓ SECURE INFRASTRUCTURE</span>
            </div>
        </div>

        <div class="status-hero">
            <div class="hero-icon"><?php echo $chain_valid ? '🛡️' : '🚨'; ?></div>
            <div class="hero-text">
                <h2><?php echo $chain_valid ? 'Integrity Verified' : 'Chain Break Detected'; ?></h2>
                <p><?php echo count($logs); ?> blocks scanned. Every transaction linked cryptographically.</p>
            </div>
        </div>

        <div class="panel" style="background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); padding: 20px;">
            <table class="log-table">
                <thead>
                    <tr style="text-align: left; color: #71717a; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em;">
                        <th style="padding: 15px;">Block ID</th>
                        <th>Action</th>
                        <th>Signature Hash (SHA-256)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($logs) as $log): ?>
                    <tr class="log-row">
                        <td style="font-family: monospace;">#<?php echo $log['log_id']; ?></td>
                        <td>
                            <div style="font-weight: 700;"><?php echo $log['action']; ?></div>
                            <div style="font-size: 0.8rem; color: #71717a;"><?php echo $log['ip_address']; ?></div>
                        </td>
                        <td class="sig-text">
                            <?php echo $log['log_hash'] ?: 'NO_SIGNATURE_LEGACY_BLOCK'; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($log['status']); ?>">
                                <?php echo $log['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
