<?php
require_once 'dbconnect.php';

// ── ZERO TRUST: Session Validation ──
if (!\ShineGuard\Services\IdentityService::isLoggedIn() || \ShineGuard\Services\IdentityService::getUserRole() !== 'System Admin') {
    die("<div style='font-family:sans-serif; text-align:center; padding:100px;'><h1>🚫 Unauthorized</h1><p>Access restricted to System Administrators.</p></div>");
}

$sqlFile = 'db_far_migration.sql';
if (!file_exists($sqlFile)) {
    die("Migration source file missing.");
}

$sql = file_get_contents($sqlFile);
?>
<!DOCTYPE html>
<html>
<head>
    <title>FAR Migration Engine</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 40px; border-radius: 24px; text-align: center; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        h1 { color: #10b981; margin-bottom: 10px; }
        p { color: #94a3b8; line-height: 1.6; }
        .btn { display: inline-block; background: #10b981; color: white; padding: 12px 30px; border-radius: 12px; text-decoration: none; font-weight: 600; margin-top: 20px; transition: 0.2s; }
        .btn:hover { background: #059669; transform: translateY(-2px); }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size: 3rem; margin-bottom: 20px;">🛡️</div>
        <?php
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) { $result->free(); }
            } while ($conn->next_result());
            
            echo "<h1>Migration Successful</h1>";
            echo "<p>The Forensic Registry (FAR) has been successfully initialized on the AWS production environment. You can now generate snapshots.</p>";
            echo "<a href='settings.php?tab=data' class='btn'>Go to Dashboard</a>";
        } else {
            echo "<h1 class='error'>Migration Failed</h1>";
            echo "<p>SQL Execution Error: " . htmlspecialchars($conn->error) . "</p>";
            echo "<a href='settings.php?tab=data' class='btn' style='background:#475569;'>Back</a>";
        }
        ?>
    </div>
</body>
</html>
